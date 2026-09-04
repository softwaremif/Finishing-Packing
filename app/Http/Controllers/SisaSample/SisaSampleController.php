<?php

namespace App\Http\Controllers\SisaSample;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SisaSampleController extends Controller
{
    public function index()
    {
        return view('menu.sisa-sample.index');
    }

    private function isSuperUser(): bool
    {
        return auth()->check() && (auth()->user()->role ?? null) === 'super';
    }

    private function db()
    {
        return DB::connection('mysql_sample');
    }

    // ============================================================
    // INDEX -- TIDAK BERUBAH -- hanya statuspk yang SUDAH punya size
    // dengan masuk=1 (sudah di-Add lewat modal atau halaman detail).
    // ============================================================
    public function getList(Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 25);
        $offset = ($page - 1) * $rows;
        $cari   = $request->cari;
        $buyer  = $request->buyer;
    
        $db = $this->db();
    
        $qualifyingSize = $db->table('size')
            ->select('statuspk')
            ->where('masuk', 1)
            ->groupBy('statuspk');
    
        $query = $db->table('status')
            ->joinSub($qualifyingSize, 'qsz', function ($join) {
                $join->on('status.statuspk', '=', 'qsz.statuspk');
            })
            ->leftJoin('jadwal', 'jadwal.statuspk', '=', 'status.statuspk')
            ->leftJoin('request', 'request.srpk', '=', 'status.srpk')
            ->leftJoin('user', 'user.userpk', '=', 'status.userpk')
            ->leftJoin('buyer', 'buyer.buyerpk', '=', 'request.buyerpk')
            ->leftJoin('fu', 'fu.fupk', '=', 'user.fupk')
            ->where('status.sts', 4)
            ->when($cari, fn ($q) => $q->where('request.srno', 'like', "%{$cari}%"))
            ->when($buyer, fn ($q) => $q->where('buyer.buyernm', $buyer));
    
        $total = $query->count();
    
        $rowsData = $query->select(
                'status.statuspk', 'status.srpk', 'status.samplenm', 'status.opsi',
                'status.date', 'status.washing', 'status.tglin', 'status.tglout', 'status.keterangan',
                'request.srno', 'buyer.buyernm', 'fu.funm',
                'jadwal.sendingdate', 'jadwal.receiptdate', 'jadwal.jadwalpk'
            )
            ->orderByDesc('request.srno')
            ->orderBy('status.samplenm')
            ->orderBy('status.opsi')
            ->offset($offset)->limit($rows)
            ->get();
    
        $this->attachSizeSums($db, $rowsData);
        $this->attachOutSisaSums($db, $rowsData); // BARU
    
        foreach ($rowsData as $i => $row) {
            $row->no = $offset + $i + 1;
        }
    
        return response()->json([
            'total' => $total,
            'rows'  => $rowsData,
        ]);
    }

    private function attachOutSisaSums($db, $rowsData): void
    {
        $statuspks = $rowsData->pluck('statuspk')->unique()->values();
        if ($statuspks->isEmpty()) {
            foreach ($rowsData as $row) {
                $row->qty_keluar = 0;
                $row->tglout_terakhir = null;
            }
            return;
        }
    
        // CATATAN: sengaja menghitung SEMUA outsisa (apa pun status
        // approval-nya) -- kalau kamu mau "Qty Keluar" HANYA hitung yang
        // SUDAH fully-approved (stsapv1=1 DAN staapv2=1 DAN stsapv3=1),
        // tinggal tambah ->where('outsisa.stsapv1',1)->where('outsisa.staapv2',1)
        // ->where('outsisa.stsapv3',1) di query di bawah.
        $outSums = $db->table('outsisa')
            ->join('outsisadt', 'outsisadt.outpk', '=', 'outsisa.outpk')
            ->whereIn('outsisa.statuspk', $statuspks)
            ->groupBy('outsisa.statuspk')
            ->selectRaw('outsisa.statuspk, SUM(outsisadt.qty) as qty_keluar, MAX(outsisa.tglout) as tglout_terakhir')
            ->get()
            ->keyBy('statuspk');
    
        foreach ($rowsData as $row) {
            $row->qty_keluar = (float) ($outSums[$row->statuspk]->qty_keluar ?? 0);
            $row->tglout_terakhir = $outSums[$row->statuspk]->tglout_terakhir ?? null;
        }
    }

    // ============================================================
    // BARU: dipakai Step 1 MODAL (cari SR) -- BUKAN halaman lagi,
    // cuma endpoint JSON. Tetap browsing SEMUA status.sts=4, dengan
    // flag already_added untuk badge.
    // ============================================================
    public function cariList(Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 25);
        $offset = ($page - 1) * $rows;
        $cari   = $request->cari;
        $buyer  = $request->buyer; // BARU
    
        $db = $this->db();
    
        $query = $db->table('status')
            ->leftJoin('jadwal', 'jadwal.statuspk', '=', 'status.statuspk')
            ->leftJoin('request', 'request.srpk', '=', 'status.srpk')
            ->leftJoin('user', 'user.userpk', '=', 'status.userpk')
            ->leftJoin('buyer', 'buyer.buyerpk', '=', 'request.buyerpk')
            ->leftJoin('fu', 'fu.fupk', '=', 'user.fupk')
            ->where('status.sts', 4)
            ->when($cari, fn ($q) => $q->where('request.srno', 'like', "%{$cari}%"))
            ->when($buyer, fn ($q) => $q->where('buyer.buyernm', $buyer)); // BARU
    
        $total = $query->count();
    
        $rowsData = $query->select(
                'status.statuspk', 'status.srpk', 'status.samplenm', 'status.opsi',
                'status.date', 'status.washing', 'status.tglin', 'status.tglout', 'status.keterangan',
                'request.srno', 'buyer.buyernm', 'fu.funm',
                'jadwal.sendingdate', 'jadwal.receiptdate', 'jadwal.jadwalpk'
            )
            ->orderByDesc('request.srno')
            ->orderBy('status.samplenm')
            ->orderBy('status.opsi')
            ->offset($offset)->limit($rows)
            ->get();
    
        $this->attachSizeSums($db, $rowsData);
    
        $statuspks = $rowsData->pluck('statuspk')->unique()->values();
        $addedSet = $db->table('size')
            ->whereIn('statuspk', $statuspks)
            ->where('masuk', 1)
            ->distinct()
            ->pluck('statuspk')
            ->flip();
    
        foreach ($rowsData as $i => $row) {
            $row->no = $offset + $i + 1;
            $row->already_added = isset($addedSet[$row->statuspk]);
        }
    
        return response()->json([
            'total' => $total,
            'rows'  => $rowsData,
        ]);
    }

    private function attachSizeSums($db, $rowsData): void
    {
        $statuspks = $rowsData->pluck('statuspk')->unique()->values();

        $sizeSums = $db->table('size')
            ->whereIn('statuspk', $statuspks)
            ->groupBy('statuspk')
            ->selectRaw('statuspk, SUM(qty) as qty, SUM(qtys) as qtys')
            ->get()
            ->keyBy('statuspk');

        foreach ($rowsData as $row) {
            $row->qty  = (float) ($sizeSums[$row->statuspk]->qty  ?? 0);
            $row->qtys = (float) ($sizeSums[$row->statuspk]->qtys ?? 0);
            $row->washing_label = ((int) $row->washing === 1) ? 'Yes' : 'No';
        }
    }

    // ============================================================
    // BARU: detail() -- GANTI nama dari tambah(), sekarang jadi
    // HALAMAN DETAIL PENUH dengan tab "Sisa Masuk" (aktif) & "Sisa
    // Keluar Gudang" (placeholder, menyusul).
    // ============================================================
    public function detail($srpk, $statuspk)
    {
        $db = $this->db();

        $status = $db->table('status')
            ->leftJoin('request', 'request.srpk', '=', 'status.srpk')
            ->leftJoin('user', 'user.userpk', '=', 'status.userpk')
            ->leftJoin('buyer', 'buyer.buyerpk', '=', 'request.buyerpk')
            ->leftJoin('fu', 'fu.fupk', '=', 'user.fupk')
            ->where('request.srpk', $srpk)
            ->where('status.statuspk', $statuspk)
            ->select('status.*', 'request.srno', 'request.srpk', 'buyer.buyernm', 'fu.funm')
            ->first();

        abort_unless($status, 404);

        $jadwal = $db->table('jadwal')->where('statuspk', $statuspk)->first();
        $isSuper = $this->isSuperUser();

        return view('menu.sisa-sample.detail', compact('status', 'jadwal', 'srpk', 'statuspk', 'isSuper'));
    }

    public function sizeList($srpk, $statuspk)
    {
        $db = $this->db();

        $rows = $db->table('size')
            ->where('statuspk', $statuspk)
            ->orderBy('size')
            ->get();

        return response()->json(['rows' => $rows]);
    }

    // ============================================================
    // ADD -- 1 size (dipakai halaman Detail).
    // ============================================================
    public function addSize(Request $request, $sizepk)
    {
        $validated = $request->validate([
            'qtys' => 'required|numeric|min:0',
        ]);
    
        $db = $this->db();
    
        $row = $db->table('size')->where('sizepk', $sizepk)->first();
        if (!$row) {
            return response()->json(['icon' => 'warning', 'title' => 'Data size tidak ditemukan.'], 404);
        }
    
        if ((float) $validated['qtys'] > (float) $row->qty) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Qty Sisa ({$validated['qtys']}) tidak boleh melebihi Qty Order ({$row->qty}).",
            ], 422);
        }
    
        $db->table('size')->where('sizepk', $sizepk)->update([
            'masuk'    => 1,
            'qtys'     => $validated['qtys'],
            'tglmasuk' => now(),
        ]);
    
        // BARU -- FIX UTAMA: status.tglin ikut diupdate (tglin ada di
        // tabel 'status', bukan 'size', jadi WAJIB query terpisah).
        $db->table('status')->where('statuspk', $row->statuspk)->update([
            'tglin' => now(),
        ]);
    
        return response()->json([
            'icon'  => 'success',
            'title' => "Size {$row->size} berhasil ditambahkan ke Gudang LO.",
        ]);
    }
    
    // ============================================================
    // BARU: EDIT Qty Sisa untuk baris yang SUDAH di-Add. Setiap kali qty
    // diupdate, tglmasuk IKUT diupdate ke SEKARANG (sesuai permintaan --
    // "setiap update qty tgl masuk update juga").
    // ============================================================
    public function updateQtySize(Request $request, $sizepk)
    {
        $validated = $request->validate([
            'qtys' => 'required|numeric|min:0',
        ]);
    
        $db = $this->db();
    
        $row = $db->table('size')->where('sizepk', $sizepk)->first();
        if (!$row) {
            return response()->json(['icon' => 'warning', 'title' => 'Data size tidak ditemukan.'], 404);
        }
    
        if ((float) $validated['qtys'] > (float) $row->qty) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Qty Sisa ({$validated['qtys']}) tidak boleh melebihi Qty Order ({$row->qty}).",
            ], 422);
        }
    
        $db->table('size')->where('sizepk', $sizepk)->update([
            'qtys'     => $validated['qtys'],
            'tglmasuk' => now(),
        ]);
    
        // BARU -- FIX UTAMA: SAMA seperti addSize().
        $db->table('status')->where('statuspk', $row->statuspk)->update([
            'tglin' => now(),
        ]);
    
        return response()->json([
            'icon'  => 'success',
            'title' => "Qty Sisa untuk size {$row->size} berhasil diperbarui.",
        ]);
    }

    // ============================================================
    // BARU: ADD banyak size sekaligus -- dipakai Step 2 MODAL
    // ("Tambahkan yang Dipilih").
    // ============================================================
    public function addMultipleSizes(Request $request)
    {
        $validated = $request->validate([
            'sizes'          => 'required|array|min:1',
            'sizes.*.sizepk' => 'required|integer',
            'sizes.*.qtys'   => 'required|numeric|min:1',
        ], [
            'sizes.*.qtys.required' => 'Qty Sisa wajib diisi untuk setiap size yang dipilih.',
            'sizes.*.qtys.min'      => 'Qty Sisa tidak boleh 0.',
        ]);
    
        $db = $this->db();
    
        $sizepks = collect($validated['sizes'])->pluck('sizepk')->all();
    
        $sizeRows = $db->table('size')->whereIn('sizepk', $sizepks)->get()->keyBy('sizepk');
    
        $errors = [];
        foreach ($validated['sizes'] as $s) {
            $qtySample = (float) ($sizeRows[$s['sizepk']]->qty ?? 0);
            if ((float) $s['qtys'] > $qtySample) {
                $errors[] = "Qty Sisa ({$s['qtys']}) untuk sizepk {$s['sizepk']} melebihi Qty Sample ({$qtySample}).";
            }
        }
    
        if (!empty($errors)) {
            return response()->json([
                'icon'  => 'warning',
                'title' => implode(' ', $errors),
            ], 422);
        }
    
        $now = now();
    
        foreach ($validated['sizes'] as $s) {
            $db->table('size')->where('sizepk', $s['sizepk'])->update([
                'masuk'    => 1,
                'tglmasuk' => $now,
                'qtys'     => $s['qtys'],
            ]);
        }
    
        // BARU -- FIX UTAMA: update status.tglin utk SEMUA statuspk unik
        // yang terlibat dari size-size yang baru ditambahkan.
        $statuspks = $sizeRows->whereIn('sizepk', $sizepks)->pluck('statuspk')->unique()->values();
        if ($statuspks->isNotEmpty()) {
            $db->table('status')->whereIn('statuspk', $statuspks)->update([
                'tglin' => $now,
            ]);
        }
    
        return response()->json([
            'icon'  => 'success',
            'title' => count($validated['sizes']) . ' size berhasil ditambahkan ke Gudang LO.',
        ]);
    }

    public function deleteSize(Request $request, $sizepk)
    {
        if (!$this->isSuperUser()) {
            return response()->json(['icon' => 'error', 'title' => 'Hanya Super User yang dapat menghapus data ini.'], 403);
        }

        $db = $this->db();

        $row = $db->table('size')->where('sizepk', $sizepk)->first();
        if (!$row) {
            return response()->json(['icon' => 'warning', 'title' => 'Data tidak ditemukan.'], 404);
        }

        $db->table('size')->where('sizepk', $sizepk)->delete();

        return response()->json([
            'icon'  => 'success',
            'title' => "Size {$row->size} berhasil dihapus.",
        ]);
    }

    public function save(Request $request, $statuspk)
    {
        $validated = $request->validate([
            'tgl'            => 'nullable|date',
            'ket'            => 'nullable|string',
            'sizes'          => 'nullable|array',
            'sizes.*.sizepk' => 'required_with:sizes|integer',
            'sizes.*.qtys'   => 'nullable|numeric',
        ]);

        $db = $this->db();

        $db->beginTransaction();
        try {
            $db->table('status')->where('statuspk', $statuspk)->update([
                'tglout'     => $validated['tgl'] ?? null,
                'keterangan' => $validated['ket'] ?? null,
            ]);

            foreach ($validated['sizes'] ?? [] as $s) {
                $db->table('size')
                    ->where('sizepk', $s['sizepk'])
                    ->where('statuspk', $statuspk)
                    ->update(['qtys' => $s['qtys'] ?? 0]);
            }

            $db->commit();

            return response()->json([
                'icon'  => 'success',
                'title' => 'Data berhasil disimpan.',
            ]);
        } catch (\Throwable $e) {
            $db->rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menyimpan data.',
                'text'  => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function foto($statuspk)
    {
        $db = $this->db();
    
        $status = $db->table('status')->where('statuspk', $statuspk)->first();
        abort_unless($status, 404);
    
        if (empty($status->fotobyte)) {
            abort(404);
        }

        return response($status->fotobyte)
            ->header('Content-Type', 'image/jpg');
    }

    public function buyerList(Request $request)
    {
        $q = $request->q;
        $db = $this->db();
    
        $buyers = $db->table('buyer')
            ->select('buyernm')
            ->whereNotNull('buyernm')
            ->where('buyernm', '<>', '')
            ->when($q, fn ($qq) => $qq->where('buyernm', 'like', "%{$q}%"))
            ->distinct()
            ->orderBy('buyernm')
            ->get();
    
        $result = collect([(object) ['buyernm' => '', 'buyer_label' => 'All Buyer']])
            ->concat($buyers->map(fn ($b) => (object) ['buyernm' => $b->buyernm, 'buyer_label' => $b->buyernm]));
    
        return response()->json($result->values());
    }



    // SAMPLE KELUAR

    private function resolveEmailConnection(): string
    {
        return session('pos') == 1 ? 'mysql_andon' : 'mysql';
    }
    
    private function canEditOutsisa($out): bool
    {
        return $out->stsapv1 === null && $out->staapv2 === null && $out->stsapv3 === null;
    }
    
    // BARU -- format nomor "0001/KLR/VIII/2026" (TANPA segmen MIF, karena
    // modul Sisa Sample cuma 1 database, tidak ada konsep mif terpisah).
    private function buildNoOutsisaSample($outpk, $tglout): string
    {
        $romanMonths = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $date = \Carbon\Carbon::parse($tglout);
        $month = $romanMonths[$date->month - 1];
        return sprintf('%04d/KLR-SR/%s/%d', $outpk, $month, $date->year);
    }


    private function resolveOutSisaStatusLabel($out): string
    {
        $labels = [1 => 'Purchasing', 2 => 'HRD', 3 => 'HRD2'];
        $rejected = [];
        $pending = [];
        $approvedCount = 0;
    
        $vals = [1 => $out->stsapv1, 2 => $out->staapv2, 3 => $out->stsapv3]; // kolom 2 tetap 'staapv2' (typo asli)
    
        foreach ([1, 2, 3] as $lvl) {
            $val = $vals[$lvl];
            if ($val !== null && (int) $val === 0) $rejected[] = $labels[$lvl];
            elseif ((int) $val === 1) $approvedCount++;
            else $pending[] = $labels[$lvl];
        }
    
        if (!empty($rejected)) return 'Ditolak (' . implode(', ', $rejected) . ')';
        if ($approvedCount === 3 || empty($pending)) return 'Selesai (Approved)';
        return 'Menunggu Approve ' . implode(', ', $pending);
    }
    
    /**
     * AJAX -- size yang SUDAH di-Add (masuk=1) DAN masih ada SISA yang
     * belum dikeluarkan (qtys - SUM(outsisadt.qty) untuk sizepk itu).
     */
    public function keluarAvailableSizes(Request $request, $statuspk)
    {
        $db = $this->db();
        $excludeOutpk = $request->input('exclude_outpk'); // BARU
    
        $sizes = $db->table('size')
            ->where('statuspk', $statuspk)
            ->where('masuk', 1)
            ->orderBy('size')
            ->get();
    
        $sizepks = $sizes->pluck('sizepk');
    
        $releasedMap = collect();
        if ($sizepks->isNotEmpty()) {
            $releasedMap = $db->table('outsisadt')
                ->whereIn('sizepk', $sizepks)
                ->when($excludeOutpk, fn ($q) => $q->where('outpk', '!=', $excludeOutpk)) // BARU
                ->select('sizepk')
                ->selectRaw('SUM(qty) as released')
                ->groupBy('sizepk')
                ->pluck('released', 'sizepk');
        }
    
        $result = $sizes->map(function ($s) use ($releasedMap) {
            $released  = (float) ($releasedMap[$s->sizepk] ?? 0);
            $remaining = (float) $s->qtys - $released;
            return [
                'sizepk'    => $s->sizepk,
                'size'      => $s->size,
                'cw'        => $s->cw,
                'qtys'      => (float) $s->qtys,
                'released'  => $released,
                'remaining' => $remaining,
            ];
        })->filter(fn ($s) => $s['remaining'] > 0)->values();
    
        return response()->json(['rows' => $result]);
    }
    
    /**
     * STORE -- validasi ULANG sisa di server, insert outsisa+outsisadt.
     */
    public function keluarStore(Request $request)
    {
        $validated = $request->validate([
            'statuspk'       => 'required|integer',
            'penerima'       => 'nullable|string|max:100',
            'keterangan'     => 'nullable|string|max:250',
            'lines'          => 'required|array|min:1',
            'lines.*.sizepk' => 'required|integer',
            'lines.*.qty'    => 'required|numeric|min:0.01',
        ]);
    
        $db = $this->db();
    
        $sizepks  = collect($validated['lines'])->pluck('sizepk')->unique()->values();
        $sizeRows = $db->table('size')->whereIn('sizepk', $sizepks)->get()->keyBy('sizepk');
    
        $releasedMap = $db->table('outsisadt')
            ->whereIn('sizepk', $sizepks)
            ->select('sizepk')
            ->selectRaw('SUM(qty) as released')
            ->groupBy('sizepk')
            ->pluck('released', 'sizepk');
    
        $validLines = [];
        $skipped = 0;
    
        foreach ($validated['lines'] as $line) {
            $sizeRow = $sizeRows->get($line['sizepk']);
            if (!$sizeRow || (int) $sizeRow->masuk !== 1) { $skipped++; continue; }
    
            $released  = (float) ($releasedMap[$line['sizepk']] ?? 0);
            $remaining = (float) $sizeRow->qtys - $released;
    
            if ((float) $line['qty'] > $remaining) { $skipped++; continue; } // FIX UTAMA
    
            $validLines[] = $line;
        }
    
        if (empty($validLines)) {
            return response()->json([
                'icon'  => 'warning',
                'title' => 'Semua baris tidak valid (melebihi sisa yang tersedia, atau data sudah berubah).',
            ], 422);
        }
    
        try {
            $newOutpk = $db->transaction(function () use ($db, $validated, $validLines) {
                $newOutpk = (int) ($db->table('outsisa')->lockForUpdate()->max('outpk')) + 1;
                $db->table('outsisa')->insert([
                    'outpk'      => $newOutpk,
                    'statuspk'   => $validated['statuspk'],
                    'tglout'     => now(),
                    'penerima'   => $validated['penerima'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,
                    'stsapv1'    => null,
                    'staapv2'    => null,
                    'stsapv3'    => null,
                ]);
    
                $newOutdtpk = (int) ($db->table('outsisadt')->lockForUpdate()->max('outdtpk'));
                $insertRows = [];
                foreach ($validLines as $line) {
                    $newOutdtpk++;
                    $insertRows[] = [
                        'outdtpk' => $newOutdtpk,
                        'outpk'   => $newOutpk,
                        'sizepk'  => $line['sizepk'],
                        'qty'     => $line['qty'],
                    ];
                }
                $db->table('outsisadt')->insert($insertRows);
    
                return $newOutpk;
            });
    
            $skippedCount = count($validated['lines']) - count($validLines);
            $msg = "Data keluar #{$newOutpk} berhasil dibuat dengan " . count($validLines) . " baris.";
            if ($skippedCount > 0) {
                $msg .= " ({$skippedCount} baris dilewati karena melebihi sisa/data tidak valid.)";
            }
    
            return response()->json(['icon' => 'success', 'title' => $msg, 'outpk' => $newOutpk]);
        } catch (\Throwable $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal menyimpan data keluar.'], 500);
        }
    }
    
    /**
     * LIST -- semua outsisa milik 1 statuspk (dipakai tab Sisa Keluar).
     */
    public function keluarList($statuspk)
    {
        $db = $this->db();
    
        $rows = $db->table('outsisa')
            ->where('statuspk', $statuspk)
            ->orderByDesc('outpk')
            ->get();
    
        $outpks = $rows->pluck('outpk')->values();
    
        $lines = collect();
        if ($outpks->isNotEmpty()) {
            $lines = $db->table('outsisadt')
                ->join('size', 'size.sizepk', '=', 'outsisadt.sizepk')
                ->whereIn('outsisadt.outpk', $outpks)
                ->select('outsisadt.outpk', 'outsisadt.qty', 'size.size', 'size.cw')
                ->get()
                ->groupBy('outpk');
        }
    
        foreach ($rows as $out) {
            $groupLines = $lines->get($out->outpk, collect());
            $out->jumlah_item  = $groupLines->count();
            $out->status_label = $this->resolveOutSisaStatusLabel($out);
            $out->sizes = $groupLines->map(fn ($l) => ['label' => $l->size, 'qty' => (float) $l->qty])->values();
            $out->pcs   = $groupLines->sum('qty');
            $out->no_out   = $this->buildNoOutsisaSample($out->outpk, $out->tglout); // BARU
            $out->can_edit = $this->canEditOutsisa($out); // BARU
        }
    
        return response()->json(['rows' => $rows]);
    }
    
    /**
     * DETAIL -- 1 outsisa + baris-barisnya (join ke size utk label).
     */
    public function keluarDetail($outpk)
    {
        $db = $this->db();
    
        $out = $db->table('outsisa')->where('outpk', $outpk)->first();
        abort_unless($out, 404);
    
        $lines = $db->table('outsisadt')
            ->join('size', 'size.sizepk', '=', 'outsisadt.sizepk')
            ->where('outsisadt.outpk', $outpk)
            ->select('outsisadt.outdtpk', 'outsisadt.qty', 'size.sizepk', 'size.size', 'size.cw')
            ->get();
    
        $out->status_label = $this->resolveOutSisaStatusLabel($out);
        $out->no_out = $this->buildNoOutsisaSample($out->outpk, $out->tglout); // BARU
        $out->can_edit = $this->canEditOutsisa($out); // BARU
    
        return response()->json(['out' => $out, 'lines' => $lines]);
    }
    
    public function keluarApprove(Request $request, $outpk, $level)
    {
        $level = (int) $level;
        if (!in_array($level, [1, 2, 3], true)) {
            return response()->json(['icon' => 'error', 'title' => 'Level approval tidak valid.'], 422);
        }
    
        $db = $this->db();
        $out = $db->table('outsisa')->where('outpk', $outpk)->first();
        abort_unless($out, 404);
    
        $column = $level === 2 ? 'staapv2' : "stsapv{$level}";
        if ($out->{$column} !== null) {
            return response()->json(['icon' => 'warning', 'title' => "Level {$level} sudah pernah diproses sebelumnya."], 422);
        }
    
        $db->table('outsisa')->where('outpk', $outpk)->update([$column => 1]);
        return response()->json(['icon' => 'success', 'title' => "Level {$level} berhasil di-approve."]);
    }
    
    public function keluarReject(Request $request, $outpk, $level)
    {
        $level = (int) $level;
        if (!in_array($level, [1, 2, 3], true)) {
            return response()->json(['icon' => 'error', 'title' => 'Level approval tidak valid.'], 422);
        }
    
        $db = $this->db();
        $out = $db->table('outsisa')->where('outpk', $outpk)->first();
        abort_unless($out, 404);
    
        $column = $level === 2 ? 'staapv2' : "stsapv{$level}";
        if ($out->{$column} !== null) {
            return response()->json(['icon' => 'warning', 'title' => "Level {$level} sudah pernah diproses sebelumnya."], 422);
        }
    
        $db->table('outsisa')->where('outpk', $outpk)->update([$column => 0]);
        return response()->json(['icon' => 'success', 'title' => "Level {$level} ditolak."]);
    }
    
    public function keluarCancel($outpk)
    {
        $db = $this->db();
        $out = $db->table('outsisa')->where('outpk', $outpk)->first();
        abort_unless($out, 404);
    
        if (!$this->canEditOutsisa($out)) {
            return response()->json(['icon' => 'warning', 'title' => 'Data ini sudah ada approval yang masuk, tidak bisa dibatalkan lagi.'], 422);
        }
    
        try {
            $db->transaction(function () use ($db, $outpk) {
                $db->table('outsisadt')->where('outpk', $outpk)->delete();
                $db->table('outsisa')->where('outpk', $outpk)->delete();
            });
            return response()->json(['icon' => 'success', 'title' => 'Data keluar dibatalkan, sisa ter-unlock kembali.']);
        } catch (\Throwable $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal membatalkan.'], 500);
        }
    }
    
    public function keluarRemoveItem($outpk, $outdtpk)
    {
        $db = $this->db();
        $out = $db->table('outsisa')->where('outpk', $outpk)->first();
        abort_unless($out, 404);
    
        if (!$this->canEditOutsisa($out)) {
            return response()->json(['icon' => 'warning', 'title' => 'Data ini sudah ada approval yang masuk, tidak bisa diedit lagi.'], 422);
        }
    
        $db->table('outsisadt')->where('outpk', $outpk)->where('outdtpk', $outdtpk)->delete();
        return response()->json(['icon' => 'success', 'title' => 'Baris dihapus, sisa ter-unlock kembali.']);
    }

    public function keluarUpdate(Request $request, $outpk)
    {
        $validated = $request->validate([
            'penerima'       => 'nullable|string|max:100',
            'keterangan'     => 'nullable|string|max:250',
            'lines'          => 'required|array|min:1',
            'lines.*.sizepk' => 'required|integer',
            'lines.*.qty'    => 'required|numeric|min:0.01',
        ]);
    
        $db = $this->db();
        $out = $db->table('outsisa')->where('outpk', $outpk)->first();
        abort_unless($out, 404);
    
        if (!$this->canEditOutsisa($out)) {
            return response()->json(['icon' => 'warning', 'title' => 'Data ini sudah ada approval yang masuk, tidak bisa diedit lagi.'], 422);
        }
    
        $sizepks = collect($validated['lines'])->pluck('sizepk')->unique()->values();
        $sizeRows = $db->table('size')->whereIn('sizepk', $sizepks)->get()->keyBy('sizepk');
    
        $releasedMap = $db->table('outsisadt')
            ->whereIn('sizepk', $sizepks)
            ->where('outpk', '!=', $outpk) // BARU -- kecualikan baris milik outpk ini sendiri
            ->select('sizepk')
            ->selectRaw('SUM(qty) as released')
            ->groupBy('sizepk')
            ->pluck('released', 'sizepk');
    
        $validLines = [];
        $skipped = 0;
    
        foreach ($validated['lines'] as $line) {
            $sizeRow = $sizeRows->get($line['sizepk']);
            if (!$sizeRow || (int) $sizeRow->masuk !== 1) { $skipped++; continue; }
    
            $released  = (float) ($releasedMap[$line['sizepk']] ?? 0);
            $remaining = (float) $sizeRow->qtys - $released;
    
            if ((float) $line['qty'] > $remaining) { $skipped++; continue; }
    
            $validLines[] = $line;
        }
    
        if (empty($validLines)) {
            return response()->json(['icon' => 'warning', 'title' => 'Semua baris tidak valid (melebihi sisa/data berubah).'], 422);
        }
    
        try {
            $db->transaction(function () use ($db, $outpk, $validated, $validLines) {
                $db->table('outsisadt')->where('outpk', $outpk)->delete();
    
                $db->table('outsisa')->where('outpk', $outpk)->update([
                    'penerima'   => $validated['penerima'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);
    
                $newOutdtpk = (int) ($db->table('outsisadt')->lockForUpdate()->max('outdtpk'));
                $insertRows = [];
                foreach ($validLines as $line) {
                    $newOutdtpk++;
                    $insertRows[] = ['outdtpk' => $newOutdtpk, 'outpk' => $outpk, 'sizepk' => $line['sizepk'], 'qty' => $line['qty']];
                }
                $db->table('outsisadt')->insert($insertRows);
            });
    
            return response()->json(['icon' => 'success', 'title' => "Data keluar #{$outpk} berhasil diperbarui."]);
        } catch (\Throwable $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal memperbarui data.'], 500);
        }
    }
    
    
    // ============================================================
    // 8) BARU -- sendKeluarEmail() -- ambil email dari mysql/mysql_andon
    // (SESUAI session mif user login), jnsemail=2, group lines by
    // (size+cw) jadi struktur 'items' generik SAMA dgn view shared LO.
    // ============================================================
    public function sendKeluarEmail(Request $request, $outpk)
    {
        $db = $this->db();
        $out = $db->table('outsisa')->where('outpk', $outpk)->first();
        abort_unless($out, 404);
    
        // BARU -- ambil SR#, Sample Status, Style dari status+request.
        $statusInfo = $db->table('status')
            ->leftJoin('request', 'request.srpk', '=', 'status.srpk')
            ->where('status.statuspk', $out->statuspk)
            ->select('request.srno', 'status.samplenm', 'status.style')
            ->first();
    
        $emailRow = DB::connection($this->resolveEmailConnection())->table('email')->where('jnsemail', 2)->first();
        if (!$emailRow) {
            return response()->json(['icon' => 'error', 'title' => 'Data email approver (jnsemail=2) tidak ditemukan.'], 404);
        }
    
        $noOut = $this->buildNoOutsisaSample($out->outpk, $out->tglout);
    
        $lines = $db->table('outsisadt')
            ->join('size', 'size.sizepk', '=', 'outsisadt.sizepk')
            ->where('outsisadt.outpk', $outpk)
            ->select('size.size', 'size.cw', 'outsisadt.qty')
            ->get();
    
        $items = $lines->groupBy('cw')->map(function ($group, $cw) {
            return [
                'grade' => '-', 'POno' => 'CW# ' . ($cw ?: '-'), 'OP' => '', 'color' => '', 'secsz' => null,
                'pcs' => $group->sum('qty'),
                'sizes' => $group->map(fn ($l) => ['label' => $l->size, 'qty' => (float) $l->qty])->values()->all(),
            ];
        })->values();
    
        $approvers = [
            1 => ['name' => $emailRow->name1, 'email' => $emailRow->apv1, 'label' => 'Purchasing'],
            2 => ['name' => $emailRow->name2, 'email' => $emailRow->apv2, 'label' => 'HRD'],
            3 => ['name' => $emailRow->name3, 'email' => $emailRow->apv3, 'label' => 'HRD2'],
        ];
    
        $sentCount = 0;
        $failed = [];
        $vals = [1 => $out->stsapv1, 2 => $out->staapv2, 3 => $out->stsapv3];
    
        foreach ($approvers as $level => $approver) {
            if (empty($approver['email'])) continue;
            if ($vals[$level] !== null) continue;
    
            $approveUrl = URL::temporarySignedRoute(
                'sisa-sample.keluar.email-approve.page', now()->addDays(14), ['outpk' => $out->outpk, 'level' => $level]
            );
    
            try {
                Mail::send('menu.shared.lo-email-approval', [
                    'docNo'        => $noOut,
                    'docTitle'     => 'Keluar Sisa Sample dari Gudang',
                    'penerima'     => $out->penerima,
                    'keterangan'   => $out->keterangan,
                    'approverName' => $approver['name'],
                    'levelLabel'   => $approver['label'],
                    'approveUrl'   => $approveUrl,
                    'items'        => $items,
                    'srno'         => $statusInfo->srno ?? null,        // BARU
                    'sampleStatus' => $statusInfo->samplenm ?? null,    // BARU
                    'style'        => $statusInfo->style ?? null,       // BARU
                ], function ($message) use ($approver, $noOut) {
                    $message->to($approver['email'], $approver['name'])
                        ->subject("Approval Keluar Sisa Sample - {$noOut}");
                });
                $sentCount++;
            } catch (\Throwable $e) {
                $failed[] = $approver['label'] . ' (' . $approver['email'] . '): ' . $e->getMessage();
            }
        }
    
        if ($sentCount === 0 && empty($failed)) {
            return response()->json(['icon' => 'warning', 'title' => 'Tidak ada email yang dikirim (semua level sudah diproses, atau alamat email kosong).']);
        }
        if (!empty($failed)) {
            return response()->json([
                'icon' => $sentCount > 0 ? 'warning' : 'error',
                'title' => "Berhasil kirim ke {$sentCount} approver. Gagal ke " . count($failed) . ' approver.',
                'errors' => $failed,
            ]);
        }
        return response()->json(['icon' => 'success', 'title' => "Email approval berhasil dikirim ke {$sentCount} approver."]);
    }
    
    
    // ============================================================
    // 9) BARU -- landing page + aksi TANPA LOGIN, SAMA pola modul LO.
    // ============================================================
    public function keluarEmailApprovePage(Request $request, $outpk, $level)
    {
        abort_unless($request->hasValidSignature(), 403, 'Link tidak valid atau sudah kedaluwarsa.');
    
        $db = $this->db();
        $out = $db->table('outsisa')->where('outpk', $outpk)->first();
        abort_unless($out, 404);
    
        // BARU
        $statusInfo = $db->table('status')
            ->leftJoin('request', 'request.srpk', '=', 'status.srpk')
            ->where('status.statuspk', $out->statuspk)
            ->select('request.srno', 'status.samplenm', 'status.style')
            ->first();
    
        $level = (int) $level;
        $column = $level === 2 ? 'staapv2' : "stsapv{$level}";
        $alreadyDone = $out->{$column} !== null;
        $noOut = $this->buildNoOutsisaSample($out->outpk, $out->tglout);
        $levelLabel = [1 => 'Purchasing', 2 => 'HRD', 3 => 'HRD2'][$level] ?? "Level {$level}";
    
        $lines = $db->table('outsisadt')
            ->join('size', 'size.sizepk', '=', 'outsisadt.sizepk')
            ->where('outsisadt.outpk', $outpk)
            ->select('size.size', 'size.cw', 'outsisadt.qty')
            ->get();
    
        $items = $lines->groupBy('cw')->map(function ($group, $cw) {
            return [
                'grade' => '-', 'POno' => 'CW# ' . ($cw ?: '-'), 'OP' => '', 'color' => '', 'secsz' => null,
                'pcs' => $group->sum('qty'),
                'sizes' => $group->map(fn ($l) => ['label' => $l->size, 'qty' => (float) $l->qty])->values()->all(),
            ];
        })->values();
    
        $doApproveUrl = URL::temporarySignedRoute(
            'sisa-sample.keluar.email-approve.do-approve', now()->addDays(14), ['outpk' => $outpk, 'level' => $level]
        );
        $doRejectUrl = URL::temporarySignedRoute(
            'sisa-sample.keluar.email-approve.do-reject', now()->addDays(14), ['outpk' => $outpk, 'level' => $level]
        );
    
        return view('menu.shared.lo-email-confirm', [
            'docNo' => $noOut, 'penerima' => $out->penerima, 'keterangan' => $out->keterangan,
            'alreadyDone' => $alreadyDone, 'levelLabel' => $levelLabel, 'items' => $items,
            'doApproveUrl' => $doApproveUrl, 'doRejectUrl' => $doRejectUrl,
            'srno'         => $statusInfo->srno ?? null,        // BARU
            'sampleStatus' => $statusInfo->samplenm ?? null,    // BARU
            'style'        => $statusInfo->style ?? null,       // BARU
        ]);
    }
    
    public function keluarEmailDoApprove(Request $request, $outpk, $level)
    {
        abort_unless($request->hasValidSignature(), 403, 'Link tidak valid atau sudah kedaluwarsa.');
    
        $level = (int) $level;
        if (!in_array($level, [1, 2, 3], true)) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => 'Level approval tidak valid.']);
        }
    
        $db = $this->db();
        $out = $db->table('outsisa')->where('outpk', $outpk)->first();
        if (!$out) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => 'Data keluar tidak ditemukan.']);
        }
    
        $column = $level === 2 ? 'staapv2' : "stsapv{$level}";
        if ($out->{$column} !== null) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => "Level {$level} sudah pernah diproses sebelumnya."]);
        }
    
        $db->table('outsisa')->where('outpk', $outpk)->update([$column => 1]);
        return view('menu.shared.lo-email-approve-done', ['success' => true, 'message' => "Berhasil approve data keluar #{$outpk} untuk level ini."]);
    }
    
    public function keluarEmailDoReject(Request $request, $outpk, $level)
    {
        abort_unless($request->hasValidSignature(), 403, 'Link tidak valid atau sudah kedaluwarsa.');
    
        $level = (int) $level;
        if (!in_array($level, [1, 2, 3], true)) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => 'Level approval tidak valid.']);
        }
    
        $db = $this->db();
        $out = $db->table('outsisa')->where('outpk', $outpk)->first();
        if (!$out) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => 'Data keluar tidak ditemukan.']);
        }
    
        $column = $level === 2 ? 'staapv2' : "stsapv{$level}";
        if ($out->{$column} !== null) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => "Level {$level} sudah pernah diproses sebelumnya."]);
        }
    
        $db->table('outsisa')->where('outpk', $outpk)->update([$column => 0]);
        return view('menu.shared.lo-email-approve-done', ['success' => true, 'message' => "Data keluar #{$outpk} ditolak untuk level ini."]);
    }
}