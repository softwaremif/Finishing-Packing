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
     * shipment (bukan "complete shipment") — ada di cancel()/processCancel().
     *
     * PERBAIKAN vs versi sebelumnya (dibandingkan ke simpan_packing.php asli):
     * 1) po.shipdate2 sekarang di-update SEBELUM processComplete/processPartial
     *    dipanggil — bukan sesudahnya. Native mengasumsikan po.shipdate2 SUDAH
     *    fresh (diisi lewat request "input" terpisah) saat opit/bj/bjo diposting,
     *    dan dipakai konsisten sebagai opit.tgldel, bj.tgl/shipdate,
     *    bjo.tglkirim, DAN sebagai KEY LOOKUP bjo.tgldok. Kalau update-nya
     *    terjadi setelah proses (urutan lama), semua itu keliru pakai tanggal
     *    lama, dan lookup bjo via tgldok bisa salah cocok / bikin baris baru
     *    yang tidak seharusnya.
     * 2) po.ship10 / po.ship{N} TIDAK lagi di-set tanpa syarat di sini. Native
     *    membungkus update tsb di dalam if($qtyship>0) — kalau tidak ada pack
     *    berstatus 4 utk di-ship, TIDAK ADA apa pun yang terjadi. Sekarang
     *    ship10 di-set di dalam processComplete() (dan ship{N} sudah lebih
     *    dulu benar di dalam processPartial()), keduanya di belakang guard
     *    $qtyship<=0.
     * 3) $connection (mysql vs mysql_andon, tergantung ?mif=) sekarang
     *    diteruskan ke processComplete/processPartial supaya SEMUA query ke
     *    po/pack/ship pakai koneksi yang sama dengan yang dipakai store() —
     *    sebelumnya store() eksplisit pakai DB::connection($connection),
     *    tapi processComplete/processPartial diam-diam pakai koneksi default.
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
        $skipped   = []; // popk yg tidak ada carton Ready (status=4, pcs>0) utk dikirim
        $currentPopk = null;

        // FIX: sebelumnya pakai $request->query('mif', ...) — query() cuma baca
        // query string URL (?mif=...), padahal JS (saveSegelPacking()) mengirim
        // mif lewat POST body. Akibatnya mif selalu jatuh ke default/session,
        // $connection bisa salah, dan packAggregate() query ke database yang
        // salah (menemukan 0 baris walau data aslinya ada di connection lain).
        // input() membaca dari query string ATAUPUN body, jadi konsisten dengan
        // method lain (updateCtnGlobal, storeGlobal) yang sudah pakai input().
        $mif = $request->input('mif', session('pos'));
        $connection = ((int) $mif) === 1 ? 'mysql_andon' : 'mysql';

        try {
            foreach ($validated['popk'] as $popk) {
                $currentPopk = $popk;

                // (1) Set shipdate2 DULU, sebelum posting ke LPB — lihat catatan
                // perbaikan di atas.
                DB::connection($connection)->table('po')->where('popk', $popk)->update([
                    'shipdate2' => $request->actual_shipment,
                ]);

                if ($validated['shipment_type'] === 'full') {
                    $shipped = $this->processComplete((int) $popk, $ctx, $connection);
                } else {
                    $partialNo = max(1, min((int) $validated['partialNo'], 10));
                    $shipped = $this->processPartial((int) $popk, $partialNo, $ctx, $connection);
                }

                if ($shipped) {
                    $processed[] = $popk;
                } else {
                    // qtyship<=0 — tidak ada carton berstatus Ready utk popk ini.
                    // Native juga tidak melakukan apa-apa dlm kasus ini, tapi di sini
                    // kita catat supaya user TIDAK dikasih pesan sukses palsu.
                    //
                    // Ditampilkan sbg "customer, material, secsz" (bukan angka popk
                    // mentah) supaya operator lebih mudah mengenali kombinasi mana
                    // yang belum bisa dikirim.
                    $skipped[] = $this->describePopk($popk, $connection);
                }
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

        if (empty($processed)) {
            // Semua popk yang dipilih tidak punya carton berstatus Ready (status=4,
            // pcs>0, segel=1) untuk dikirim — biasanya karena belum ada Input Packing
            // baru sejak part sebelumnya, atau carton-nya belum disegel. Segel
            // Packing HANYA mengirim carton yang sedang Ready & disegel, bukan
            // "memindahkan" carton yang sudah ter-part sebelumnya.
            return response()->json([
                'icon'  => 'error',
                'title' => 'Tidak ada carton berstatus Ready & sudah Disegel untuk dikirim pada: '
                    . implode('; ', $skipped) . '. Pastikan carton sudah di-Input Packing DAN disegel sebelum Kirim ke Stuffing.',
            ], 422);
        }

        if (!empty($skipped)) {
            return response()->json([
                'icon'  => 'warning',
                'title' => 'Kirim ke Stuffing berhasil untuk sebagian data. Kombinasi berikut TIDAK diproses karena tidak ada carton Ready & disegel: '
                    . implode('; ', $skipped) . '.',
            ]);
        }

        return response()->json([
            'icon'  => 'success',
            'title' => 'Proses berhasil disimpan.',
        ]);
    }

    /* =========================================================================
     * COMPLETE SHIPMENT  (native: x=komplit, guser=user)
     * ========================================================================= */
    private function processComplete(int $popk, array $ctx, string $connection): bool
    {
        $po = DB::connection($connection)->table('po')->where('popk', $popk)->first();
        abort_unless($po, 404, "PO dengan popk {$popk} tidak ditemukan.");

        $agg = $this->packAggregate($popk, $connection);
        $qtyship = (int) ($agg->pcs ?? 0);

        // Sama seperti native: kalau tidak ada qty untuk dikirim, tidak ada apa-apa
        // yang dilakukan — termasuk TIDAK menyentuh po.ship10. Kembalikan false
        // supaya store() bisa melapor jujur ke user (bukan selalu bilang sukses).
        if ($qtyship <= 0) {
            return false;
        }

        $jkarton = $this->resolveJkarton($po->gabung, $agg->jml ?? 0);

        // ---- Transaksi 1: koneksi mysql_lpb (opit/bj/bjo/notran) ----------
        // LPB diproses LEBIH DULU. Kalau step ini gagal, pack/ship/po BELUM
        // disentuh sama sekali → operator tinggal retry ulang dari awal,
        // TANPA perlu reconcile manual.
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

        // ---- Transaksi 2: koneksi $connection (po/pack/ship) --------------------
        // NOTE: native hardcode part='10' + status='5' untuk SEMUA baris pack
        // yang pcs>0 & status=4 (tanpa batas packpk) saat complete shipment.
        try {
            DB::connection($connection)->transaction(function () use ($popk, $po, $connection) {
                // TAMBAHAN: cuma carton segel=1 yang ikut di-complete-kan. Carton
                // status=4 tapi segel=0 dilewati (tetap Ready, belum dikirim).
                $packs = DB::connection($connection)->table('pack')
                    ->where('popk', $popk)->where('pcs', '>', 0)->where('status', 4)->where('segel', 1)
                    ->orderBy('packpk')->lockForUpdate()->get();

                foreach ($packs as $pack) {
                    DB::connection($connection)->table('pack')->where('packpk', $pack->packpk)->update([
                        'status' => 5,
                        'part'   => '10',
                    ]);

                    DB::connection($connection)->table('ship')->insert($this->packToShipData($pack, '10', $po->gabung));
                }

                // Ditaruh di sini (bukan di store()) supaya po.ship10 cuma ke-set
                // kalau shipment ini benar-benar diproses (qtyship>0) — meniru
                // native yang membungkus update tsb dalam if(qtyship>0).
                DB::connection($connection)->table('po')->where('popk', $popk)->update([
                    'ship10' => $po->shipdate2,
                ]);
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

        return true;
    }

    /* =========================================================================
     * PARTIAL SHIPMENT  (native: x=partial)
     * ========================================================================= */
    private function processPartial(int $popk, int $partialNo, array $ctx, string $connection): bool
    {
        $po = DB::connection($connection)->table('po')->where('popk', $popk)->first();
        abort_unless($po, 404, "PO dengan popk {$popk} tidak ditemukan.");

        $agg = $this->packAggregate($popk, $connection);
        $qtyship = (int) ($agg->pcs ?? 0);

        if ($qtyship <= 0) {
            return false;
        }

        $jkarton = $this->resolveJkarton($po->gabung, $agg->jml ?? 0);
        $part = (string) $partialNo;

        // ---- Transaksi 1: koneksi mysql_lpb (opit/bj/bjo/notran) ----------
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

        // ---- Transaksi 2: koneksi $connection (po/pack/ship) ------------------
        try {
            DB::connection($connection)->transaction(function () use ($popk, $partialNo, $part, $po, $connection) {
                // native: update po set ship{N}=shipdate2 (kolom shipdate per partial ke-N)
                // po->shipdate2 di sini sudah fresh karena store() meng-update-nya
                // sebelum memanggil processPartial().
                DB::connection($connection)->table('po')->where('popk', $popk)->update([
                    "ship{$partialNo}" => $po->shipdate2,
                ]);

                // REVISI: native ambil batas atas packpk dari baris jmlpcs=1 & status=4
                // terakhir (urut desc, packpk desc). Tapi jmlpcs=1 ternyata tidak pernah
                // ke-set di data production saat ini (lihat catatan di packAggregate()),
                // jadi query ini diganti: batas atas = packpk TERBESAR di antara carton
                // yang benar-benar siap kirim (status=4, pcs>0, DAN segel=1). lockForUpdate()
                // di sini sekaligus jadi snapshot supaya baris yang masuk batch ini konsisten
                // walau ada insert baru di tengah transaksi.
                $lastPack = DB::connection($connection)->table('pack')
                    ->where('popk', $popk)->where('status', 4)->where('pcs', '>', 0)->where('segel', 1)
                    ->orderByDesc('packpk')
                    ->lockForUpdate()->first();

                if (!$lastPack) {
                    return;
                }

                // TAMBAHAN: cuma carton segel=1 yang ikut di-partial-kan. Carton
                // status=4 tapi segel=0 dilewati (tetap Ready, belum dikirim).
                $packs = DB::connection($connection)->table('pack')
                    ->where('popk', $popk)->where('pcs', '>', 0)->where('status', 4)->where('segel', 1)
                    ->where('packpk', '<=', $lastPack->packpk)
                    ->orderBy('packpk')->lockForUpdate()->get();

                foreach ($packs as $pack) {
                    DB::connection($connection)->table('pack')->where('packpk', $pack->packpk)->update([
                        'status' => 5,
                        'part'   => $part,
                    ]);

                    DB::connection($connection)->table('ship')->insert($this->packToShipData($pack, $part, $po->gabung));
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

        return true;
    }

    /* =========================================================================
     * SHARED HELPERS
     * ========================================================================= */

    /**
     * Agregasi pack untuk satu popk (sum qty1..40 + pcs + jumlah carton "nyata").
     *
     * REVISI (bukan lagi filter jmlpcs=1 seperti native): native mengandalkan
     * asumsi tepat SATU baris pack per batch ditandai jmlpcs=1 (dari default
     * insert di simpan_packing.php lama), baris lain di batch yang sama
     * jmlpcs=0. Di data production saat ini, baris-baris carton yang baru
     * di-Input Packing semuanya jmlpcs=0 — tidak ada yang pernah ditandai 1 —
     * sehingga filter jmlpcs=1 selalu mengembalikan 0 baris walau carton-nya
     * nyata ada & siap dikirim (status=4, pcs>0). Ini bikin shipment tidak
     * bisa diproses sama sekali untuk carton baru.
     *
     * Jadi sekarang: qtyship = SUM(pcs) dari SEMUA baris status=4 (carton yang
     * qty aktualnya sudah 0, seperti baris "planning-only", otomatis tidak
     * menambah apa-apa ke SUM). jml (jumlah carton) = COUNT baris status=4
     * yang pcs>0 — yaitu carton yang benar-benar sudah ada qty aktualnya,
     * bukan cuma qty rencana (qtyp) yang belum discan/di-input actual-nya.
     *
     * TAMBAHAN: hanya carton dengan segel=1 yang dianggap siap dikirim. Carton
     * dengan segel=0 (belum disegel/masih dalam proses packing fisik) TETAP
     * status=4 tapi TIDAK ikut dihitung/dikirim di Segel Packing ini — akan
     * ikut di batch berikutnya kalau nanti sudah disegel.
     */
    private function packAggregate(int $popk, string $connection = 'mysql')
    {
        return DB::connection($connection)->table('pack')
            ->where('popk', $popk)->where('status', 4)->where('segel', 1)
            ->selectRaw('sum(pcs) as pcs, count(case when pcs > 0 then 1 end) as jml, ' .
                collect(range(1, 40))->map(fn ($i) => "sum(qty{$i}) as qty{$i}")->implode(', '))
            ->first();
    }

    /**
     * Bikin label yang gampang dibaca operator untuk sebuah popk yang di-skip
     * (tidak ada carton Ready & disegel) — "customer, material, secsz" alih-alih
     * angka popk mentah yang tidak berarti apa-apa buat orang lantai produksi.
     * Fallback ke "popk {id}" kalau po-nya sendiri tidak ketemu (kasus langka).
     */
    private function describePopk(int $popk, string $connection): string
    {
        $po = DB::connection($connection)->table('po')
            ->where('popk', $popk)->first(['customer', 'material', 'secsz']);
 
        if (!$po) {
            return "popk {$popk}";
        }
 
        $label = collect([$po->customer, $po->material, $po->secsz])
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->implode(' - ');
 
        return $label !== '' ? $label : "popk {$popk}";
    }

    /**
     * native: jkarton hanya dihitung dari jmlpcs (jml) untuk gabung tertentu
     * (kosong/null, 0, 2, 7) — persis 4 blok if($gabung==...) di simpan_packing.php.
     * Untuk gabung lain (1,3,4,5,6,8,9,...), native TIDAK PERNAH men-set variabel
     * $jkarton sama sekali (dipakai kosong/0 di query berikutnya) — perilaku ini
     * DIPERTAHANKAN sama persis di sini, bukan bug, dikonfirmasi dari
     * simpan_packing.php asli.
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
     * (server terpisah) — dipakai baik oleh complete maupun partial
     * shipment, hanya beda nilai $part.
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
                    ->update(['status' => 4, 'part' => '', 'segel' => 0]);

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