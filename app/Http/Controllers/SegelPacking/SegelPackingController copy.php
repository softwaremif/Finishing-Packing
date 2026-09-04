<?php

namespace App\Http\Controllers\SegelPacking;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SegelPackingController extends Controller
{
    /**
     * Nama koneksi DB untuk opit/bj/bjo/notran — server FISIK berbeda dari
     * koneksi default (po/pack/ship), sesuai config/database.php: 'mysql_lpb'.
     * Karena beda host, tidak bisa ikut dalam DB::transaction() koneksi default.
     */
    private const LPB_CONNECTION = 'mysql_lpb';

    /**
     * Simpan Segel Packing (Complete atau Partial Shipment) untuk satu atau
     * lebih popk yang dicentang di datagrid.
     *
     * Catatan: ini hanya mem-port cabang `guser == "user"` dari native.
     * Cabang `guser == "super"` di action x=komplit adalah fitur BATAL/UNDO
     * shipment (bukan "complete shipment") — sengaja tidak diikutkan di sini.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'popk'          => ['required', 'array', 'min:1'],
            'popk.*'        => ['integer'],
            'gabung'        => ['nullable'],
            'actual_shipment'  => ['required'],
            'shipment_type' => ['required', 'in:full,partial'],
            'partialNo'     => ['nullable', 'required_if:shipment_type,partial', 'integer', 'between:1,10'],
        ]);

        $ctx = $this->shipperContext();
        $processed = [];
        $currentPopk = null;
        $mif = $request->query('mif', session('pos'));
        $connection = ((int) $mif) === 1 ? 'mysql_andon' : 'mysql';

        try {
            foreach ($validated['popk'] as $popk) {
                $currentPopk = $popk;

                if ($validated['shipment_type'] === 'full') {
                    $this->processComplete((int) $popk, $ctx);
                    DB::connection($connection)->table('po')->where('popk', $popk)->update([
                        'shipdate2' => $request->actual_shipment,
                        'ship10'    => $request->actual_shipment,
                    ]);
               
                } else {
                    $this->processPartial((int) $popk, (int) $validated['partialNo'], $ctx);

                    // Ambil partialNo, pastikan antara 1 - 10.
                    $partialNo = (int) $validated['partialNo'];
                    $partialNo = max(1, min($partialNo, 10));

                    $shipField = "ship{$partialNo}";
                    // Update sesuai partialNo
                    DB::connection($connection)->table('po')->where('popk', $popk)->update([
                        'shipdate2'   => $request->actual_shipment,
                        $shipField => $request->actual_shipment,
                    ]);
          
                }

                $processed[] = $popk;
            }
        } catch (\Throwable $e) {
            $msg = $processed
                ? "Sebagian data berhasil disimpan, tapi popk {$currentPopk} gagal diproses. Silakan cek log & hubungi admin untuk reconcile."
                : "Segel packing gagal disimpan untuk popk {$currentPopk}. Silakan cek log & hubungi admin.";

            return response()->json([
                'icon'  => 'error',
                'title' => $msg,
            ], 422);
        }

        return response()->json([
            'icon'  => 'success',
            'title' => 'Segel packing berhasil disimpan.',
        ]);
    }

    /* =========================================================================
     * COMPLETE SHIPMENT  (native: x=komplit, guser=user)
     * ========================================================================= */
    private function processComplete(int $popk, array $ctx): void
    {
        $po = DB::table('po')->where('popk', $popk)->first();
        abort_unless($po, 404, "PO dengan popk {$popk} tidak ditemukan.");

        $agg = $this->packAggregate($popk);
        $qtyship = (int) ($agg->pcs ?? 0);

        // Sama seperti native: kalau tidak ada qty untuk dikirim, tidak ada apa-apa yang dilakukan.
        if ($qtyship <= 0) {
            return;
        }

        $jkarton = $this->resolveJkarton($po->gabung, $agg->jml ?? 0);

        // ---- Transaksi 1: koneksi mysql_lpb (opit/bj/bjo/notran) ----------
        // NOTE (URUTAN DIBALIK dari versi sebelumnya, DIKEMBALIKAN seperti
        // native): LPB diproses LEBIH DULU, sebelum menyentuh pack/ship.
        // Alasan: step LPB adalah yang paling rawan gagal (unique key
        // nopoop, race condition opitpk manual). Kalau step ini gagal,
        // pack/ship BELUM disentuh sama sekali → operator tinggal retry
        // ulang dari awal, TANPA perlu reconcile manual seperti kasus
        // sebelumnya (popk 2117943).
        //
        // NOTE: native hardcode part='3' di sini untuk opit/bj/bjo (bukan part='10').
        try {
            DB::connection(self::LPB_CONNECTION)->transaction(function () use ($po, $qtyship, $jkarton, $popk, $ctx) {
                $this->postToInventory($po, $qtyship, $jkarton, $popk, '3', $ctx);
            });
        } catch (\Throwable $e) {
            Log::critical('Segel Packing: posting ke LPB gagal, pack/ship BELUM disentuh (aman untuk di-retry).', [
                'popk'     => $popk,
                'part'     => '3',
                'qtyship'  => $qtyship,
                'jkarton'  => $jkarton,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }

        // ---- Transaksi 2: koneksi default (pack/ship) --------------------
        // NOTE: native hardcode part='10' + status='5' untuk SEMUA baris pack
        // yang pcs>0 & status=4 (tanpa batas packpk) saat complete shipment.
        // Kalau step ini gagal SETELAH LPB berhasil, maka LPB sudah
        // ter-posting tapi pack/ship belum — kasus ini tetap butuh reconcile
        // manual, tapi jauh lebih jarang terjadi dibanding kegagalan di LPB.
        try {
            DB::transaction(function () use ($popk, $po) {
                $packs = DB::table('pack')
                    ->where('popk', $popk)->where('pcs', '>', 0)->where('status', 4)
                    ->orderBy('packpk')->lockForUpdate()->get();

                foreach ($packs as $pack) {
                    DB::table('pack')->where('packpk', $pack->packpk)->update([
                        'status' => 5,
                        'part'   => '10',
                    ]);

                    DB::table('ship')->insert($this->packToShipData($pack, '10', $po->gabung));
                }
            });
        } catch (\Throwable $e) {
            Log::critical('Segel Packing: LPB SUDAH ter-posting tapi update pack/ship gagal. Perlu reconcile manual.', [
                'popk'    => $popk,
                'part'    => '10',
                'qtyship' => $qtyship,
                'jkarton' => $jkarton,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /* =========================================================================
     * PARTIAL SHIPMENT  (native: x=partial)
     * ========================================================================= */
    private function processPartial(int $popk, int $partialNo, array $ctx): void
    {
        $po = DB::table('po')->where('popk', $popk)->first();
        abort_unless($po, 404, "PO dengan popk {$popk} tidak ditemukan.");

        $agg = $this->packAggregate($popk);
        $qtyship = (int) ($agg->pcs ?? 0);

        if ($qtyship <= 0) {
            return;
        }

        $jkarton = $this->resolveJkarton($po->gabung, $agg->jml ?? 0);
        $part = (string) $partialNo;

        // ---- Transaksi 1: koneksi mysql_lpb (opit/bj/bjo/notran) ----------
        // Sama seperti processComplete: LPB diproses LEBIH DULU.
        try {
            DB::connection(self::LPB_CONNECTION)->transaction(function () use ($po, $qtyship, $jkarton, $popk, $part, $ctx) {
                $this->postToInventory($po, $qtyship, $jkarton, $popk, $part, $ctx);
            });
        } catch (\Throwable $e) {
            Log::critical('Segel Packing (partial): posting ke LPB gagal, pack/ship BELUM disentuh (aman untuk di-retry).', [
                'popk'    => $popk,
                'part'    => $part,
                'qtyship' => $qtyship,
                'jkarton' => $jkarton,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }

        // ---- Transaksi 2: koneksi default (po/pack/ship) ------------------
        try {
            DB::transaction(function () use ($popk, $partialNo, $part, $po) {
                // native: update po set ship{N}=shipdate2 (kolom shipdate per partial ke-N)
                DB::table('po')->where('popk', $popk)->update([
                    "ship{$partialNo}" => $po->shipdate2,
                ]);

                // native: batas atas packpk diambil dari baris terakhir (urut desc, packpk desc)
                // yang jmlpcs=1 & status=4 — lalu semua baris pcs>0 & status=4 s.d. packpk itu diproses.
                $lastPack = DB::table('pack')
                    ->where('popk', $popk)->where('jmlpcs', 1)->where('status', 4)
                    ->orderByDesc('urut')->orderByDesc('packpk')
                    ->lockForUpdate()->first();

                if (!$lastPack) {
                    return;
                }

                $packs = DB::table('pack')
                    ->where('popk', $popk)->where('pcs', '>', 0)->where('status', 4)
                    ->where('packpk', '<=', $lastPack->packpk)
                    ->orderBy('packpk')->lockForUpdate()->get();

                foreach ($packs as $pack) {
                    DB::table('pack')->where('packpk', $pack->packpk)->update([
                        'status' => 5,
                        'part'   => $part,
                    ]);

                    DB::table('ship')->insert($this->packToShipData($pack, $part, $po->gabung));
                }
            });
        } catch (\Throwable $e) {
            Log::critical('Segel Packing (partial): LPB SUDAH ter-posting tapi update pack/ship gagal. Perlu reconcile manual.', [
                'popk'    => $popk,
                'part'    => $part,
                'qtyship' => $qtyship,
                'jkarton' => $jkarton,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /* =========================================================================
     * SHARED HELPERS
     * ========================================================================= */

    /**
     * Agregasi pack untuk satu popk (sum qty1..40 + pcs + jmlpcs), diambil dari
     * baris paling baru (order by tanggal desc). Sama seperti query native
     * "select sum(pcs)... from pack where popk=... and jmlpcs=1 and status=4
     * group by popk order by tanggal desc limit 1".
     */
    private function packAggregate(int $popk)
    {
        return DB::table('pack')
            ->where('popk', $popk)->where('jmlpcs', 1)->where('status', 4)
            ->groupBy('popk')->orderByDesc('tanggal')
            ->selectRaw('sum(pcs) as pcs, sum(jmlpcs) as jml, tanggal, ' .
                collect(range(1, 40))->map(fn ($i) => "sum(qty{$i}) as qty{$i}")->implode(', '))
            ->first();
    }

    /**
     * native: jkarton hanya dihitung dari jmlpcs (jml) untuk gabung tertentu
     * (kosong/null, 0, 2, 7). Untuk gabung lain, jkarton = 0 (perilaku asli,
     * kemungkinan oversight di kode lama, tapi dipertahankan supaya konsisten
     * dengan data historis).
     */
    private function resolveJkarton($gabung, $jml): int
    {
        $g = $gabung === null ? '' : (string) $gabung;
        return in_array($g, ['', '0', '2', '7'], true) ? (int) $jml : 0;
    }

    /**
     * Resolusi konteks user finishing (userpk, kolom counter bj, basis nomor
     * bukti) dari session('login'), sama seperti native $_SESSION['login'].
     */
    private function shipperContext(): array
    {
        // $login = session('login');
        $login = session('pos');

        switch ($login) {
            case '1':
                return ['userpk' => 62, 'bj_column' => 'bjd1', 'bj_base' => 1000000];

            case '2':
                return ['userpk' => 63, 'bj_column' => 'bjd2', 'bj_base' => 2000000];

            default:
                abort(422, "Login finishing tidak dikenali: {$login}");
        }
    }

    /**
     * Ambil nomor urut dari tabel notran (koneksi mysql_lpb) untuk tblnm
     * tertentu, lalu simpan nilai berikutnya. Mengembalikan nilai LAMA
     * (sebelum increment) sebagai nomor dokumen baris ini — pola ini
     * menyamai perilaku native persis:
     * "$notrann = sprintf('%07d', $dt2['no']); update notran set no=$dt2['no']+1".
     *
     * WARNING: pakai lockForUpdate() dan HARUS dipanggil di dalam
     * DB::connection('mysql_lpb')->transaction() supaya tidak race condition
     * antar request bersamaan (native tidak punya locking sama sekali).
     */
    private function takeNotranNumber(string $table, string $column = 'no'): int
    {
        $row = DB::connection(self::LPB_CONNECTION)
            ->table('notran')->where('tblnm', $table)->lockForUpdate()->first();
        $current = $row->{$column} ?? 0;

        DB::connection(self::LPB_CONNECTION)
            ->table('notran')->where('tblnm', $table)->update([$column => $current + 1]);

        return (int) $current;
    }

    /**
     * Posting ke modul inventory (opit/bj/bjo) pada koneksi mysql_lpb
     * (server terpisah, 192.168.0.50) — dipakai baik oleh complete maupun
     * partial shipment, hanya beda nilai $part.
     *
     * WAJIB dipanggil di dalam DB::connection('mysql_lpb')->transaction(...)
     * dari method pemanggil (processComplete/processPartial), karena method
     * ini sendiri tidak membuka transaksi.
     */
    private function postToInventory($po, int $qtyship, int $jkarton, int $popk, string $part, array $ctx): void
    {
        $lpb = DB::connection(self::LPB_CONNECTION);

        $opit = $this->findOpit($lpb, $po);

        if ($opit && $opit->opitpk > 0) {
            $opitpk = $opit->opitpk;

            $lpb->table('opit')->where('opitpk', $opitpk)->update([
                'qtyship' => $opit->qtyship + $qtyship,
                'popk'    => $popk,
                'part'    => $part,
            ]);
        } else {
            // NOTE: native generate opitpk manual via MAX(opitpk)+1, bukan
            // AUTO_INCREMENT. Kalau opitpk di schema Anda AUTO_INCREMENT,
            // hapus baris 'opitpk' di insert() di bawah dan biarkan DB yang isi.
            $opitpk = (int) ($lpb->table('opit')->lockForUpdate()->max('opitpk')) + 1;
            $notranNo = $this->takeNotranNumber('opit');

            try {
                $lpb->table('opit')->insert([
                    'opitpk'  => $opitpk,
                    'opitid'  => sprintf('%07d', $notranNo),
                    'nopo'    => $po->POno,
                    'buyer'   => $po->buyer,
                    'produk'  => $po->silhouette,
                    'tgldel'  => $po->shipdate2,
                    'noop'    => $po->OP,
                    'unit'    => 'PCS',
                    'userpk'  => $ctx['userpk'],
                    'qtyship' => $qtyship,
                    'popk'    => $popk,
                    'part'    => $part,
                ]);
            } catch (QueryException $e) {
                // FIX: kolom nopo/noop di skema lama lebih pendek dari data
                // asli (mis. VARCHAR(20)), sehingga MySQL diam-diam
                // men-truncate nilainya saat INSERT. Kalau baris "versi
                // terpotong" itu SUDAH ADA duluan (tapi lolos dari
                // pencarian exact-match di findOpit() karena kita
                // membandingkan versi utuh), insert ini gagal dengan
                // duplicate key error 1062 pada unique index `nopoop`.
                //
                // Recovery: anggap baris itu memang sudah ada, cari ulang
                // pakai fallback prefix-match, lalu UPDATE alih-alih
                // INSERT — supaya qty tidak hilang & shipment tetap
                // ter-posting dengan benar.
                if ($this->isDuplicateKeyError($e)) {
                    $opit = $this->findOpit($lpb, $po, true);

                    if (!$opit) {
                        Log::critical('postToInventory: duplicate key pada opit tapi baris existing tidak ditemukan via fallback truncation.', [
                            'popk' => $popk,
                            'nopo' => $po->POno,
                            'noop' => $po->OP,
                            'part' => $part,
                            'error' => $e->getMessage(),
                        ]);
                        throw $e;
                    }

                    $opitpk = $opit->opitpk;

                    $lpb->table('opit')->where('opitpk', $opitpk)->update([
                        'qtyship' => $opit->qtyship + $qtyship,
                        'popk'    => $popk,
                        'part'    => $part,
                    ]);
                } else {
                    throw $e;
                }
            }
        }

        // ---- bj -----------------------------------------------------------
        $bjNo = $this->takeNotranNumber('bj', $ctx['bj_column']);

        $lpb->table('bj')->insert([
            'opitpk'   => $opitpk,
            'nobukti'  => $ctx['bj_base'] + $bjNo,
            'tgl'      => $po->shipdate2,
            'qty'      => $qtyship,
            'userpk'   => $ctx['userpk'],
            'posting'  => 1,
            'popk'     => $popk,
            'part'     => $part,
            'ctn'      => $jkarton,
            'shipdate' => $po->shipdate2,
        ]);

        // ---- bjo ------------------------------------------------------------
        $bjo = $lpb->table('bjo')
            ->where('opitpk', $opitpk)->where('tgldok', $po->shipdate2)
            ->lockForUpdate()->first();

        if ($bjo && $bjo->bjopk) {
            $lpb->table('bjo')->where('bjopk', $bjo->bjopk)->update([
                'qty'     => $bjo->qty + $qtyship,
                'jkarton' => $bjo->jkarton + $jkarton,
                'popk'    => $popk,
                'part'    => $part,
            ]);
        } else {
            $bjoNo = $this->takeNotranNumber('bjo');

            $lpb->table('bjo')->insert([
                'opitpk'   => $opitpk,
                'nobukti'  => sprintf('%07d', $bjoNo),
                'tglkirim' => $po->shipdate2,
                'unit'     => 'PCS',
                'userpk'   => 79, // hardcoded di native
                'qty'      => $qtyship,
                'jkarton'  => $jkarton,
                'popk'     => $popk,
                'part'     => $part,
            ]);
        }
    }

    /**
     * Cari baris opit untuk PO+OP tertentu.
     *
     * $strict = false (default): coba exact-match dulu, lalu fallback ke
     * pencocokan "nopo/noop tersimpan adalah prefix dari nilai asli" — untuk
     * menangani kasus kolom nopo/noop di skema lama lebih pendek dari data
     * asli sehingga nilainya ke-truncate diam-diam oleh MySQL saat insert
     * lama.
     *
     * $strict = true: langsung pakai fallback prefix-match saja (dipakai
     * saat recovery dari duplicate-key error, di mana kita sudah tahu
     * exact-match tidak akan ketemu).
     */
    private function findOpit($lpb, $po, bool $strict = false)
    {
        if (!$strict) {
            $opit = $lpb->table('opit')
                ->where('nopo', $po->POno)->where('noop', $po->OP)
                ->lockForUpdate()->first();

            if ($opit) {
                return $opit;
            }
        }

        return $lpb->table('opit')
            ->whereRaw('? LIKE CONCAT(nopo, \'%\')', [$po->POno])
            ->whereRaw('? LIKE CONCAT(noop, \'%\')', [$po->OP])
            ->lockForUpdate()->first();
    }

    /**
     * Cek apakah QueryException disebabkan oleh duplicate-key violation
     * (MySQL error 1062 / SQLSTATE 23000).
     */
    private function isDuplicateKeyError(QueryException $e): bool
    {
        return (int) $e->getCode() === 23000
            || str_contains($e->getMessage(), '1062');
    }

    /**
     * Bangun payload untuk insert ke tabel `ship`, menduplikasi seluruh
     * kolom dari satu baris `pack` (termasuk qty1..40 dan qtyp1..40).
     */
    private function packToShipData($pack, string $part, $gabung): array
    {
        $data = [
            'packpk'     => $pack->packpk,
            'carton'     => $pack->carton,
            'nobar'      => $pack->nobar,
            'OP'         => $pack->OP,
            'POno'       => $pack->POno,
            'popk'       => $pack->popk,
            'customer'   => $pack->customer,
            'material'   => $pack->material,
            'nw'         => $pack->nw,
            'gw'         => $pack->gw,
            'meas'       => $pack->meas,
            'secsz'      => $pack->secsz,
            'keterangan' => $pack->keterangan,
            'jmlpcs'     => $pack->jmlpcs,
            'pcs'        => $pack->pcs,
            'pcsp'       => $pack->pcsp,
            'tanggal'    => $pack->tanggal,
            'waktu'      => $pack->waktu,
            'status'     => 5,
            'urut'       => $pack->urut,
            'part'       => $part,
            'gabung'     => $gabung,
        ];

        foreach (range(1, 40) as $i) {
            $data["qty{$i}"]  = $pack->{"qty{$i}"} ?? 0;
            $data["qtyp{$i}"] = $pack->{"qtyp{$i}"} ?? 0;
        }

        return $data;
    }

    // SegelPackingController.php
    /**
     * Ambil daftar nomor partial yang SUDAH pernah dipakai untuk popk tertentu,
     * dilihat dari kolom po.ship1..ship10 (terisi tanggal jika sudah diproses).
     */
    public function partialStatus(int $popk)
    {
        $po = DB::table('po')->where('popk', $popk)->first();
        abort_unless($po, 404, "PO dengan popk {$popk} tidak ditemukan.");

        $used = [];
        foreach (range(1, 10) as $i) {
            if (!empty($po->{"ship{$i}"})) {
                $used[] = $i;
            }
        }

        return response()->json([
            'used'      => $used,       // contoh: [1, 2] artinya partial ke-1 & ke-2 sudah pernah
            'available' => array_values(array_diff(range(1, 10), $used)),
        ]);
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'popk' => ['required', 'integer'],
            'part' => ['required', 'string'],
        ]);
    
        $popk = (int) $validated['popk'];
    
        try {
            $this->processCancel($popk, (string) $validated['part']);
        } catch (\Throwable $e) {
            return response()->json([
                'icon'  => 'error',
                'title' => "Gagal membatalkan shipment untuk popk {$popk}. Silakan cek log & hubungi admin untuk reconcile.",
            ], 422);
        }
    
        return response()->json([
            'icon'  => 'success',
            'title' => 'Shipment berhasil dibatalkan, carton dikembalikan ke status Ready.',
        ]);
    }
    
    private function processCancel(int $popk, string $requestedPart): void
    {
        $po = DB::table('po')->where('popk', $popk)->first();
        abort_unless($po, 404, "PO dengan popk {$popk} tidak ditemukan.");

        // Sumber kebenaran "part" yang dibatalkan: baris `ship` TERAKHIR untuk
        // popk ini — sama seperti native, bukan sekadar percaya nilai $part
        // yang dikirim dari client.
        //
        // PENTING: `part` adalah VARCHAR ('1'..'10'), jadi ORDER BY part DESC
        // biasa akan salah urutan secara leksikografis ('9' > '10' sebagai
        // string!). Harus di-cast ke unsigned dulu supaya urut secara numerik
        // — kalau tidak, PO yang punya riwayat partial 1-9 LALU di-Complete
        // (part='10') akan salah mengambil part='9' sebagai "yang terakhir".
        $lastShip = DB::table('ship')
            ->where('popk', $popk)
            ->orderByDesc(DB::raw('CAST(part AS UNSIGNED)'))
            ->first();

        abort_unless($lastShip, 404, "Tidak ditemukan data shipment (ship) untuk popk {$popk}.");

        $part = (string) $lastShip->part;

        // Diperketat: kalau part dari client TIDAK SAMA dengan part terakhir
        // yang valid di server, TOLAK request-nya (bukan cuma warning + lanjut).
        // Ini mencegah pembatalan record yang salah akibat data di datagrid
        // sudah basi (race condition/belum reload) atau bug lain yang tidak
        // terdeteksi.
        if ($part !== $requestedPart) {
            Log::warning('Batal Segel: part dari client tidak sama dengan part terakhir di server, request ditolak.', [
                'popk'           => $popk,
                'requested_part' => $requestedPart,
                'actual_part'    => $part,
            ]);

            abort(409, "Data sudah berubah (part terakhir saat ini: {$part}). Silakan reload halaman dan coba lagi.");
        }

        $dtOP    = $po->OP;
        $dtPO    = $po->POno;
        $dtship  = $po->shipdate2;

        // ---- Transaksi 1: koneksi mysql_lpb (opit/bjo/bj) --------------------
        // NOTE: LPB diproses LEBIH DULU (sama seperti processComplete/
        // processPartial) — kalau step ini gagal, pack/ship/po BELUM disentuh
        // sama sekali, aman untuk di-retry tanpa reconcile manual.
        try {
            DB::connection(self::LPB_CONNECTION)->transaction(function () use ($dtOP, $dtPO, $dtship, $popk, $part) {
                $lpb = DB::connection(self::LPB_CONNECTION);

                $opit = $lpb->table('opit')
                    ->where('noop', $dtOP)->where('nopo', $dtPO)
                    ->lockForUpdate()->first();

                if (!$opit) {
                    // Tidak ada data opit -> tidak ada penyesuaian LPB yang bisa dilakukan.
                    return;
                }

                $opitpk = $opit->opitpk;

                $bj = $lpb->table('bj')
                    ->where('opitpk', $opitpk)->where('popk', $popk)
                    ->where('part', $part)->where('shipdate', $dtship)
                    ->lockForUpdate()->first();

                $bjo = $lpb->table('bjo')
                    ->where('opitpk', $opitpk)->where('tgldok', $dtship)
                    ->lockForUpdate()->first();

                $dtqty = (int) ($bj->qty ?? 0);
                $dtctn = (int) ($bj->ctn ?? 0);

                // Kurangi qtyship di opit sebesar qty yang dibatalkan.
                $lpb->table('opit')->where('opitpk', $opitpk)->update([
                    'qtyship' => $opit->qtyship - $dtqty,
                ]);

                if ($bjo) {
                    // NOTE: literal port dari native — native meng-update kolom
                    // `jkarton` dengan nilai $dtctn APA ADANYA (bukan hasil
                    // pengurangan $bjo->jkarton - $dtctn). Ini kemungkinan
                    // typo di kode asli, tapi dipertahankan sama persis supaya
                    // konsisten dengan perilaku/data historis. Kalau ternyata
                    // ini memang bug, ganti baris 'jkarton' di bawah menjadi:
                    // ($bjo->jkarton - $dtctn).
                    $lpb->table('bjo')->where('bjopk', $bjo->bjopk)->update([
                        'qty'     => $bjo->qty - $dtqty,
                        'jkarton' => $dtctn,
                    ]);
                }

                if ($bj) {
                    $lpb->table('bj')->where('bjpk', $bj->bjpk)->delete();
                }
            });
        } catch (\Throwable $e) {
            Log::critical('Batal Segel: gagal menyesuaikan LPB (opit/bjo/bj), pack/ship/po BELUM disentuh (aman untuk di-retry).', [
                'popk'  => $popk,
                'part'  => $part,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        // ---- Transaksi 2: koneksi default (po/pack/ship) ---------------------
        try {
            DB::transaction(function () use ($popk, $part) {
                // native: kalau part yang dibatalkan adalah Complete (part=10),
                // po.sts juga dikembalikan ke '0'.
                if ($part === '10') {
                    DB::table('po')->where('popk', $popk)->update(['sts' => '0']);
                }

                DB::table('pack')
                    ->where('popk', $popk)->where('part', $part)
                    ->update(['status' => 4, 'part' => '']);

                DB::table('ship')->where('popk', $popk)->where('part', $part)->delete();
            });
        } catch (\Throwable $e) {
            Log::critical('Batal Segel: LPB SUDAH disesuaikan tapi update pack/ship/po gagal. Perlu reconcile manual.', [
                'popk'  => $popk,
                'part'  => $part,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}