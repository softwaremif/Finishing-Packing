<?php

namespace App\Http\Controllers\Transfer;

use App\Http\Controllers\Controller;
use App\Services\OrderImageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TransferController extends Controller
{
    // ===============  HALAMAN UTAMA/INDEX TRANSFER/POLIBAG ===========

    // Render halaman index -- data tabel diisi via AJAX oleh getList().
    public function index()
    {
        return view('menu.shared.transfer-index', [
            'pageConfig' => [
                'title'              => 'Daftar Data OP',
                'polibagColumnLabel' => 'Polibag',
                'showQtyColumn'      => false,
                'showPackingColumn'  => false,
                'showKeluarColumn'   => false,
                'routes' => [
                    'list'          => route('transfer.list'),
                    'detailByPoOp'  => route('transfer.detail-by-po-op'),
                    'modalView'     => 'menu.shared.transfer-detail-modal',
                    'navStateKey'   => 'transferListState',
                    'inputUrlBase'  => url('/polibag/input'), // BARU
                ],
            ],
        ]);
    }

    // Memisahkan koneksi database sesuai mif user yang login (1=mysql_andon, 2=mysql).
    private function resolveConnection($mif): string
    {
        return ((int) $mif) === 1 ? 'mysql_andon' : 'mysql';
    }

    // Daftar Data OP index.blade.php -- endpoint utama datagrid. Alur:
    // fetch mentah per-mif -> hitung Polibag penuh (manual+barcode) &
    // Transfer to Finishing -> ambil foto order -> agregasi per PO+OP ->
    // filter (safety-net + TF Finishing>0) -> sort berdasarkan Ex Factory
    // -> paginasi.
    public function getList(Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;
        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $isSuper = session('guserpk') == 34;

        if ($isSuper) {
            $rowsAndon = $this->fetchAll('mysql_andon', 1, $request); // sudah bawa canonical_popk
            $rowsMysql = $this->fetchAll('mysql', 2, $request);
            $this->overrideTransferWithFullTotal($rowsAndon, 'mysql_andon');
            $this->overrideTransferWithFullTotal($rowsMysql, 'mysql');
            $combined = $rowsAndon->concat($rowsMysql);
        } else {
            $mif        = session('pos') == 1 ? 1 : 2;
            $connection = session('pos') == 1 ? 'mysql_andon' : 'mysql';
            $combined   = $this->fetchAll($connection, $mif, $request); // sudah bawa canonical_popk
            $this->overrideTransferWithFullTotal($combined, $connection);
        }

        $this->addTransferFinishingToRows($combined);
        $this->addOrderImageToRows($combined);

        $aggregated = $this->applyPostAggregationFilters(
            $this->aggregateByPoOp($combined),
            $request
        )
            ->filter(fn ($r) => (float) ($r->transfer_finishing ?? 0) > 0)
            ->values();

        foreach ($aggregated as $r) {
            $r->gac_sort_ts = $this->normalizeGacForSort($r->GAC);
        }

        $aggregated = $aggregated
            ->when(
                $sortDir === 'asc',
                fn ($c) => $c->sortBy('gac_sort_ts'),
                fn ($c) => $c->sortByDesc('gac_sort_ts')
            )
            ->values();

        $total = $aggregated->count();
        $data  = $aggregated->slice($offset, $rows)->values();

        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
        }

        return response()->json([
            'total' => $total,
            'rows'  => $data,
        ]);
    }

    // Ambil URL foto order per baris (jenis foto tergantung stsfoto: 1=GIS,
    // 2=Production, lainnya=fallback foto sample terbaru). No-image pakai
    // asset LOKAL aplikasi ini, bukan server remote.
    private function addOrderImageToRows($rows): void
    {
        app(OrderImageService::class)->attachToRows($rows, 'ordpk', 'order_image');
    }

    // Hitung jumlah data Polibag PENUH (manual dari tabel bj + barcode dari
    // output jnspk=4) per popk -- dipakai overrideTransferWithFullTotal().
    private function computeFullTransferPerPopk($db, array $popks, array $canonicalPopkMap = []): array
    {
        if (empty($popks)) {
            return [];
        }
    
        // Manual (bj) -- TETAP popk mentah, tabel ini ada di koneksi per-mif sendiri.
        $manualByPopk = $db->table('bj')
            ->whereIn('popk', $popks)
            ->groupBy('popk')
            ->selectRaw('popk, SUM(pcs) as total_manual')
            ->pluck('total_manual', 'popk');
    
        // GANTI -- FIX UTAMA: barcode (output) pakai canonical_popk.
        $canonicalPopks = collect($popks)
            ->map(fn ($p) => $canonicalPopkMap[$p] ?? $p)
            ->filter()
            ->unique()
            ->values()
            ->all();
    
        $barcodeByCanonicalPopk = DB::connection('mysql_polibag')
            ->table('output')
            ->whereIn('popk', $canonicalPopks)
            ->where('jnspk', 4)
            ->whereNotNull('hari')
            ->groupBy('popk')
            ->selectRaw('popk, SUM(jmlpcs) as total_barcode')
            ->pluck('total_barcode', 'popk');
    
        $result = [];
        foreach ($popks as $popk) {
            $canonicalPopk = $canonicalPopkMap[$popk] ?? $popk;
            $result[$popk] = (int) ($manualByPopk[$popk] ?? 0)
                + (int) ($barcodeByCanonicalPopk[$canonicalPopk] ?? 0);
        }
        return $result;
    }

    /**
     * Override properti `transfer` di setiap baris $rows dengan total
     * PENUH (manual+barcode) dari computeFullTransferPerPopk() -- kolom
     * `transfer` dari fetchAll() sendiri cuma hitung manual (bj) via
     * subquery, jadi perlu ditimpa supaya termasuk barcode juga.
     */
    private function overrideTransferWithFullTotal($rows, string $connection): void
    {
        if ($rows->isEmpty()) {
            return;
        }
        $db = DB::connection($connection);
        $popks = $rows->pluck('popk')->unique()->values()->all();
    
        $canonicalPopkMap = [];
        foreach ($rows as $r) {
            if (isset($r->canonical_popk)) {
                $canonicalPopkMap[$r->popk] = $r->canonical_popk;
            }
        }
    
        $totalByPopk = $this->computeFullTransferPerPopk($db, $popks, $canonicalPopkMap);
        foreach ($rows as $r) {
            $r->transfer = (int) ($totalByPopk[$r->popk] ?? 0);
        }
    }

    // Hitung jumlah Transfer To Finishing Manual (tfpb) dan Barcode (output
    // jnspk=10) per popk, di-scope ke unit/mif yang benar lewat mop.noop.
    private function addTransferFinishingToRows($rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }
        foreach ($rows as $r) {
            $r->transfer_finishing = 0;
        }

        // GANTI -- FIX UTAMA: manual (tfpb) via 'moppk' (SAMA di kedua
        // database), BUKAN join ke mop.popk (NULL untuk mif=1).
        $moppks = $rows->pluck('moppk')->filter()->unique()->values()->all();
        $manualByMoppk = [];
        if (!empty($moppks)) {
            $manualByMoppk = DB::connection('mysql_finance_mif')
                ->table('tfpb')
                ->whereIn('moppk', $moppks)
                ->groupBy('moppk')
                ->selectRaw('moppk, SUM(tot) as total_manual')
                ->pluck('total_manual', 'moppk')
                ->all();
        }

        // GANTI -- FIX UTAMA: barcode (output) via 'canonical_popk'.
        $canonicalPopks = $rows->map(fn ($r) => $r->canonical_popk ?? $r->popk)->filter()->unique()->values()->all();
        $barcodeByCanonicalPopk = [];
        if (!empty($canonicalPopks)) {
            $barcodeByCanonicalPopk = DB::connection('mysql_polibag')
                ->table('output')
                ->whereIn('popk', $canonicalPopks)
                ->where('jnspk', 10)
                ->groupBy('popk')
                ->selectRaw('popk, SUM(jmlpcs) as total_barcode')
                ->pluck('total_barcode', 'popk')
                ->all();
        }

        foreach ($rows as $r) {
            $manual = (int) ($manualByMoppk[$r->moppk] ?? 0);
            $canonicalPopk = $r->canonical_popk ?? $r->popk;
            $barcode = (int) ($barcodeByCanonicalPopk[$canonicalPopk] ?? 0);
            $r->transfer_finishing = $manual + $barcode;
        }
    }


    private function resolveCanonicalPopks(array $mif1Popks): array
    {
        if (empty($mif1Popks)) {
            return [];
        }

        $bdownpkByPopk = DB::connection('mysql_andon')->table('po')
            ->whereIn('popk', $mif1Popks)
            ->pluck('bdownpk', 'popk');

        $bdownpks = $bdownpkByPopk->filter()->unique()->values()->all();
        if (empty($bdownpks)) {
            return [];
        }

        $canonicalByBdownpk = DB::connection('mysql')->table('po')
            ->whereIn('bdownpk', $bdownpks)
            ->pluck('popk', 'bdownpk');

        $result = [];
        foreach ($bdownpkByPopk as $mif1Popk => $bdownpk) {
            if ($bdownpk !== null && isset($canonicalByBdownpk[$bdownpk])) {
                $result[$mif1Popk] = $canonicalByBdownpk[$bdownpk];
            }
        }

        return $result;
    }

    private function attachCanonicalPopk($rows, string $connection): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        if ($connection === 'mysql_andon') {
            $popks = $rows->pluck('popk')->unique()->values()->all();
            $canonicalMap = $this->resolveCanonicalPopks($popks);
            foreach ($rows as $r) {
                $r->canonical_popk = $canonicalMap[$r->popk] ?? null;
            }
        } else {
            foreach ($rows as $r) {
                $r->canonical_popk = $r->popk;
            }
        }
    }

    // Get All data list -- query mentah per popk dari tabel po (+ subquery bj
    // untuk manual Polibag), dengan filter search/buyer/year/ex_factory di
    // level SQL. Dipanggil oleh getList() (index) dan detailByPoOp() (modal)
    // supaya kedua endpoint otomatis konsisten satu sama lain.
    private function fetchAll(string $connection, int $mif, Request $request)
    {
        $bj = DB::connection($connection)->table('bj')
            ->selectRaw("
                popk,
                SUM(CASE WHEN check2 = 0 THEN pcs ELSE 0 END) AS transfer
            ")
            ->groupBy('popk');

        $query = DB::connection($connection)->table('po')
            ->leftJoinSub($bj, 'bj', function ($join) {
                $join->on('po.popk', '=', 'bj.popk');
            })
            ->leftJoin('bj as bj_line', 'bj_line.popk', '=', 'po.popk')
            ->leftJoin('line', 'line.linepk', '=', 'bj_line.linepk')
            ->where('po.sts', 0)
            ->where('po.qty', '>', 0)
            ->where('po.mif', $mif)
            ->selectRaw("po.popk, po.ordpk, po.moppk, po.POno, po.poref, po.OP, po.customer, po.season, po.style, po.material, po.buyer, po.qty, po.mif, po.secsz, po.GAC, po.silhouette,
                GROUP_CONCAT(
                    DISTINCT TRIM(SUBSTRING(line.linenm,6,3))
                    ORDER BY line.linenm
                    SEPARATOR ';'
                ) AS linenm,
                COALESCE(bj.transfer,0) AS transfer
            ")
                ->groupBy(
                    'po.popk',
                    'po.ordpk',
                    'po.moppk',   
                    'po.POno',
                    'po.poref',
                    'po.OP',
                    'po.customer',
                    'po.season',
                    'po.style',
                    'po.material',
                    'po.buyer',
                    'po.qty',
                    'po.mif',
                    'po.secsz',
                    'po.GAC',
                    'po.silhouette',
                    'bj.transfer'
                );

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('po.POno', 'like', "%{$search}%")
                    ->orWhere('po.OP', 'like', "%{$search}%")
                    ->orWhere('po.poref', 'like', "%{$search}%")
                    ->orWhere('po.customer', 'like', "%{$search}%")
                    ->orWhere('po.season', 'like', "%{$search}%")
                    ->orWhere('po.material', 'like', "%{$search}%")
                    ->orWhere('po.secsz', 'like', "%{$search}%")
                    ->orWhere('po.silhouette', 'like', "%{$search}%")
                    ->orWhere('po.style', 'like', "%{$search}%");
            });
        }

        if ($request->filled('buyer')) {
            $query->where('po.buyer', $request->buyer);
        }

        if ($request->filled('year')) {
            $year = (int) $request->year;
            $shortYear = $year - 2000;
            $query->whereRaw("LEFT(TRIM(po.OP),2) = ?", [sprintf('%02d', $shortYear)]);
        }

        // Filter Ex Factory (po.GAC) berdasarkan preset rentang tanggal.
        if ($request->filled('ex_factory')) {
            $range = $this->resolveExFactoryRange($request->ex_factory);
            if ($range) {
                $query->whereBetween('po.GAC', [
                    $range['start']->format('Y-m-d 00:00:00'),
                    $range['end']->format('Y-m-d 23:59:59'),
                ]);
            }
        }

        // Parameter sort dari tombol toggle di komponen table-default.
        // Default 'desc' (Terbaru), sesuai data-value bawaan tombolnya.
        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $rows = $query->orderBy('po.popk', $sortDir)->get();
        
        $this->attachCanonicalPopk($rows, $connection);
        
        return $rows;
    }

    // Normalisasi GAC (Ex Factory) jadi UNIX timestamp -- AMAN apa pun
    // format aslinya (string tanggal, datetime, dsb), karena sortByDesc()
    // biasa membandingkan sebagai STRING dan bisa keliru kalau ada
    // perbedaan format/tipe antar baris (misal super user gabung 2 koneksi
    // mysql_andon + mysql yang mungkin simpan GAC beda representasi).
    // Nilai kosong/'0000-00-00' -> 0 (dianggap paling awal, aman untuk
    // Earliest/Latest).
    private function normalizeGacForSort($gac): int
    {
        if (empty($gac)) {
            return 0;
        }

        $gacStr = (string) $gac;
        if ($gacStr === '0000-00-00' || $gacStr === '0000-00-00 00:00:00') {
            return 0;
        }

        try {
            return \Carbon\Carbon::parse($gacStr)->timestamp;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Hitung rentang tanggal [start, end] untuk preset filter Ex Factory.
     * Semua preset dihitung dari "hari ini" di server (Carbon), supaya
     * konsisten lintas client/browser/timezone.
     *
     * @return array{start: \Carbon\Carbon, end: \Carbon\Carbon}|null
     */
    private function resolveExFactoryRange(string $preset): ?array
    {
        $today = \Carbon\Carbon::today();

        switch ($preset) {
            case 'today':
                return ['start' => $today->copy(), 'end' => $today->copy()];

            // case 'next_week':
            //     return ['start' => $today->copy(), 'end' => $today->copy()->addWeek()];
            case 'this_week':
                return ['start' => $today->copy()->startOfWeek(), 'end' => $today->copy()->endOfWeek()];

            case 'next_2_weeks':
                return ['start' => $today->copy(), 'end' => $today->copy()->addWeeks(2)];

            case 'this_month':
                return ['start' => $today->copy()->startOfMonth(), 'end' => $today->copy()->endOfMonth()];

            case 'next_month':
                return [
                    'start' => $today->copy()->addMonthNoOverflow()->startOfMonth(),
                    'end'   => $today->copy()->addMonthNoOverflow()->endOfMonth(),
                ];

            case 'next_3_months':
                return ['start' => $today->copy(), 'end' => $today->copy()->addMonths(3)];

            case 'next_6_months':
                return ['start' => $today->copy(), 'end' => $today->copy()->addMonths(6)];

            case 'this_year':
                return ['start' => $today->copy()->startOfYear(), 'end' => $today->copy()->endOfYear()];

            default:
                return null;
        }
    }

    // Penjumlahan nilai kolom Qty, Polibag, Transfer to Finishing per PO+OP --
    // representative diambil dari popk TERBESAR dalam grup. Balance =
    // Polibag - Order Qty (BEDA dari Transfer to Finishing yang pakai
    // Transfer to Finishing - Qty -- ini formula bisnis Polibag sendiri).
    private function aggregateByPoOp($collection)
    {
        return $collection
            ->groupBy(fn($row) => implode('|', [
                $row->POno,
                $row->OP,
                $row->poref ?? '',
            ]))
            ->map(function ($group) {
                $representative = clone $group->sortByDesc('popk')->first();
                $representative->qty                = (int) $group->sum('qty');
                $representative->transfer           = (int) $group->sum('transfer');
                $representative->transfer_finishing = (int) $group->sum('transfer_finishing');
                $representative->balance = $representative->transfer - $representative->qty;
                return $representative;
            })
            ->values();
    }

    // Safety-net: validasi ULANG setiap filter aktif (year, ex_factory,
    // buyer) terhadap hasil akhir yang SUDAH diagregasi per PO+OP --
    // menjamin baris yang tampil BENAR-BENAR memenuhi SEMUA filter secara
    // bersamaan (AND), bukan cuma mengandalkan filter di level baris po
    // sebelum agregasi (fetchAll()).
    private function applyPostAggregationFilters($rows, Request $request)
    {
        if ($request->filled('year')) {
            $year = (int) $request->year;
            $shortYear = sprintf('%02d', $year - 2000);
            $rows = $rows->filter(function ($r) use ($shortYear) {
                return strtoupper(substr(trim((string) $r->OP), 0, 2)) === $shortYear;
            });
        }

        if ($request->filled('ex_factory')) {
            $range = $this->resolveExFactoryRange($request->ex_factory);
            if ($range) {
                $start = $range['start']->format('Y-m-d');
                $end   = $range['end']->format('Y-m-d');
                $rows = $rows->filter(function ($r) use ($start, $end) {
                    $gac = !empty($r->GAC) ? substr((string) $r->GAC, 0, 10) : null;
                    return $gac && $gac !== '0000-00-00' && $gac >= $start && $gac <= $end;
                });
            }
        }

        if ($request->filled('buyer')) {
            $buyer = $request->buyer;
            $rows = $rows->filter(fn($r) => (string) $r->buyer === (string) $buyer);
        }

        return $rows->values();
    }

    // =============== MODAL ===========
    // Di file index.blade.php memanggil file modal-material-list.blade.php --
    // rincian per Color/Secondary Size untuk 1 PO+OP tertentu (dibuka dari
    // kolom Aksi index).
    public function detailByPoOp(Request $request)
    {
        $validated = $request->validate([
            'po'       => 'nullable',
            'op'       => 'required',
            'mif'      => 'nullable',
            'material' => 'nullable',
            'secsz'    => 'nullable',
        ]);
        $po       = $validated['po'] ?? null;
        $op       = $validated['op'];
        $material = $validated['material'] ?? null;
        $secsz    = $validated['secsz'] ?? null;
        $mif        = $validated['mif'] ?? session('pos');
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);

        $rows = $this->fetchAll($connection, (int) $mif, $request) // sudah bawa canonical_popk
            ->where('POno', $po)
            ->where('OP', $op)
            ->values();

        if ($material !== null && $material !== '') {
            $rows = $rows->where('material', $material)->values();
            if ($secsz !== null && $secsz !== '') {
                $rows = $rows->where('secsz', $secsz)->values();
            } else {
                $rows = $rows->filter(fn ($r) => empty($r->secsz))->values();
            }
        }

        $popks = $rows->pluck('popk')->unique()->values()->all();

        // GANTI -- FIX UTAMA: bangun canonicalPopkMap dari $rows.
        $canonicalPopkMap = [];
        foreach ($rows as $r) {
            if (isset($r->canonical_popk)) {
                $canonicalPopkMap[$r->popk] = $r->canonical_popk;
            }
        }

        $transferByPopk = $this->computeFullTransferPerPopk($db, $popks, $canonicalPopkMap);
        foreach ($rows as $r) {
            $r->transfer = (int) ($transferByPopk[$r->popk] ?? 0);
        }

        $this->addTransferFinishingToRows($rows);

        foreach ($rows as $r) {
            $r->balance = (int) ($r->transfer ?? 0) - (int) ($r->qty ?? 0);
        }

        $rows = $rows
            ->sortBy(fn ($r) => ($r->customer ?? '') . '|' . ($r->poref ?? ''))
            ->values();

        foreach ($rows as $i => $row) {
            $row->no = $i + 1;
        }

        return response()->json([
            'total' => $rows->count(),
            'rows'  => $rows,
        ]);
    }

    // ===============  HALAMAN INPUT TRANSFER/POLIBAG ===========
    // Di file modal-material-list.blade.php memanggil file input.blade.php
    public function inputTransfer($popk, Request $request)
    {
        $isStokSisaMode = $request->route()->getName() === 'stok-sisa.input';
        $mif = (int) $request->query('mif', session('pos'));

        // GANTI TOTAL -- FIX UTAMA: pakai resolvePoAndMop() -- dapat $mop dan
        // $canonicalPopk sekaligus, TIDAK cari mop lewat popk lagi.
        [, $mop, $connection, $canonicalPopk] = $this->resolvePoAndMop($popk, $mif);
        $db = DB::connection($connection);

        $breakdown = $this->getBreakdownDataTransfer($db, $popk, $mop, $canonicalPopk);
        extract($breakdown);
        $cr = $request->cr;

        // ---- Dropdown Line utk modal Add/Edit -- GANTI: pakai $mop->moppk
        // (bukan cari mop lagi) + $popk mentah utk 'bj' (per-koneksi, aman). ----
        $linepksFromTfFinishingManual = $mop
            ? DB::connection('mysql_finance_mif')->table('tfpb')
                ->where('moppk', $mop->moppk)
                ->whereNotNull('linepk')
                ->where('linepk', '>', 0)
                ->distinct()
                ->pluck('linepk')
            : collect();

        $linepksFromExistingBj = $db->table('bj')
            ->where('popk', $popk)
            ->where('linepk', '>', 0)
            ->distinct()
            ->pluck('linepk');

        $linepksForModal = $linepksFromTfFinishingManual
            ->concat($linepksFromExistingBj)
            ->unique()
            ->values();

        $lines = $linepksForModal->isNotEmpty()
            ? DB::connection('mysql_polibag')->table('line')
                ->whereIn('linepk', $linepksForModal)
                ->orderBy('linenm')
                ->get()
            : collect();

        // ---- $filterLines -- GANTI: barcode-nya pakai $canonicalPopk. ----
        $bjLinepks = $db->table('bj')
            ->where('popk', $popk)
            ->where('linepk', '>', 0)
            ->distinct()
            ->pluck('linepk');

        $outputLinepks = $canonicalPopk !== null
            ? DB::connection('mysql_polibag')->table('output')
                ->where('popk', $canonicalPopk)
                ->where('jnspk', 4)
                ->where('linepk', '>', 0)
                ->distinct()
                ->pluck('linepk')
            : collect();

        $usedLinepks = $bjLinepks->merge($outputLinepks)->unique()->values();
        $filterLines = $usedLinepks->isNotEmpty()
            ? DB::connection('mysql_polibag')->table('line')
                ->whereIn('linepk', $usedLinepks)
                ->orderBy('linenm')
                ->get()
            : collect();

        return view('menu.transfer.input', array_merge(compact(
            'dt',
            'activeSizes',
            'summary',
            'lines',
            'filterLines',
            'orderQty',
            'manualQty',
            'manualTotalPcs',
            'barcodeQty',
            'barcodeTotalPcs',
            'readyQty',
            'diffQty',
            'totalBalance',
            'finishingQty',
            'finishingTotalPcs',
            'cr',
            'mif',
            'connection'
        ), ['isStokSisaMode' => $isStokSisaMode]));
    }
    // Di file input.blade.php memanggil file partial/breakdown_summary.blade.php
    public function breakdownSummary($popk, Request $request)
    {
        $mif = (int) $request->query('mif', session('pos'));

        [, $mop, $connection, $canonicalPopk] = $this->resolvePoAndMop($popk, $mif);
        $db = DB::connection($connection);

        $breakdown = $this->getBreakdownDataTransfer($db, $popk, $mop, $canonicalPopk);

        return view('menu.transfer.partials.breakdown_summary', $breakdown);
    }

    private function resolvePoAndMop($popk, int $mif): array
    {
        $connection = $this->resolveConnection($mif);
        $dt = DB::connection($connection)->table('po')->where('popk', $popk)->first();
        abort_unless($dt, 404, "Data po untuk popk {$popk} (mif={$mif}) tidak ditemukan.");
    
        $mop = DB::connection('mysql_finance_mif')->table('mop')
            ->where('moppk', $dt->moppk)
            ->first();
    
        if ($connection === 'mysql_andon') {
            $canonicalMap = $this->resolveCanonicalPopks([(int) $popk]);
            $canonicalPopk = $canonicalMap[(int) $popk] ?? null;
        } else {
            $canonicalPopk = (int) $popk;
        }
    
        return [$dt, $mop, $connection, $canonicalPopk];
    }
    /**
     * Helper TUNGGAL untuk breakdown Size & Qty halaman Input Transfer/Polibag.
     * Dipakai oleh inputTransfer() (render pertama kali) DAN breakdownSummary()
     */
    private function getBreakdownDataTransfer($db, $popk, ?object $mop, ?int $canonicalPopk): array
    {
        $dt = $db->table('po')->where('popk', $popk)->first();
        if (!$dt) abort(404);
    
        $activeSizes = [];
        for ($i = 1; $i <= 40; $i++) {
            $sz = $dt->{"size$i"} ?? null;
            if (!empty($sz)) $activeSizes[$i] = $sz;
        }
        $sizeMap = [];
        foreach ($activeSizes as $i => $szName) {
            $sizeMap[$szName] = $i;
        }
    
        [$bjRows, $outputAggRows] = $this->getBjAndOutputRows($db, $popk, $canonicalPopk, $sizeMap, null);
    
        $manualQty      = array_fill(1, 40, 0);
        $manualTotalPcs = 0;
        foreach ($bjRows as $row) {
            foreach ($activeSizes as $i => $sz) {
                $manualQty[$i] += (int) ($row->{"qty$i"} ?? 0);
            }
            $manualTotalPcs += (int) ($row->pcs ?? 0);
        }
    
        $barcodeQty      = array_fill(1, 40, 0);
        $barcodeTotalPcs = 0;
        foreach ($outputAggRows as $row) {
            foreach ($activeSizes as $i => $sz) {
                $barcodeQty[$i] += (int) ($row->{"qty$i"} ?? 0);
            }
            $barcodeTotalPcs += (int) ($row->pcs ?? 0);
        }
    
        // GANTI -- FIX UTAMA: terima $mop (moppk) + $canonicalPopk langsung,
        // TIDAK cari mop lewat popk lagi di dalam getFinishingQtyPerSizeForPopk().
        $finishingQty      = $this->getFinishingQtyPerSizeForPopk($mop, $canonicalPopk, $sizeMap);
        $finishingTotalPcs = array_sum($finishingQty);
    
        $barcodeLineBreakdown   = $this->getOutputLineBreakdown('mysql_polibag', $canonicalPopk, 4, $sizeMap);
        $finishingLineBreakdown = $this->getFinishingLineBreakdown($mop, $canonicalPopk, $sizeMap);
    
        $orderQty = [];
        $readyQty = [];
        $diffQty  = [];
        for ($i = 1; $i <= 40; $i++) {
            $orderQty[$i] = $dt->{"qty$i"} ?? 0;
            $readyQty[$i] = $manualQty[$i] + $barcodeQty[$i];
            $diffQty[$i]  = $readyQty[$i] - $orderQty[$i];
        }
        $readyTotalPcs = $manualTotalPcs + $barcodeTotalPcs;
        $totalBalance  = $readyTotalPcs - ($dt->qty ?? 0);
    
        $summary = (object) ['pcs' => $manualTotalPcs];
    
        return compact(
            'dt',
            'activeSizes',
            'sizeMap',
            'summary',
            'orderQty',
            'manualQty',
            'manualTotalPcs',
            'barcodeQty',
            'barcodeTotalPcs',
            'readyQty',
            'diffQty',
            'totalBalance',
            'finishingQty',
            'finishingTotalPcs',
            'barcodeLineBreakdown',
            'finishingLineBreakdown'
        );
    }
    // Di file input.blade.php get data di tabel Detail Data Polibag
    public function detailList($popk, Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;
        $cr     = $request->cr;
        $mif    = (int) $request->query('mif', session('pos'));

        // GANTI TOTAL -- FIX UTAMA: pakai resolvePoAndMop() -- dapat
        // $canonicalPopk sekaligus.
        [$poRow, , $connection, $canonicalPopk] = $this->resolvePoAndMop($popk, $mif);
        $db = DB::connection($connection);

        $sizeMap = [];
        if ($poRow) {
            for ($i = 1; $i <= 40; $i++) {
                $szName = $poRow->{"size$i"} ?? null;
                if (!empty($szName)) {
                    $sizeMap[$szName] = $i;
                }
            }
        }

        [$bjRows, $outputAggRows] = $this->getBjAndOutputRows($db, $popk, $canonicalPopk, $sizeMap, $cr);

        $bjRowsSorted = $bjRows
            ->sortByDesc(fn ($r) => \Illuminate\Support\Carbon::parse($r->tanggal)->format('Y-m-d'))
            ->values();
        $outputRowsSorted = $outputAggRows
            ->sortByDesc(fn ($r) => \Illuminate\Support\Carbon::parse($r->tanggal)->format('Y-m-d'))
            ->values();
        $allRows = $bjRowsSorted->concat($outputRowsSorted)->values();

        $total = $allRows->count();
        $data  = $allRows->slice($offset, $rows)->values();
        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
        }

        $hasShipped = $db->table('pack')
            ->where('popk', $popk)
            ->where('status', 5)
            ->exists();

        return response()->json([
            'total'       => $total,
            'rows'        => $data,
            'has_shipped' => $hasShipped,
        ]);
    }
    // Di file input.blade.php proses edit dan tambah data di tabel Detail Data Polibag
    public function saveTransfer(Request $request)
    {
        $isStokSisaMode = $request->boolean('stok_sisa_mode');
        $gradeRule = $isStokSisaMode ? 'required|in:A,B,C' : 'nullable|in:A,B,C';
        $validator = Validator::make(
            $request->all(),
            [
                'popk'    => 'required',
                'tanggal' => 'required|date',
                'linepk'  => 'required',
                'grade'   => $gradeRule,
            ],
            [
                'tanggal.required' => 'Tanggal masuk harus diisi.',
                'tanggal.date'     => 'Format tanggal tidak valid.',
                'linepk.required'  => 'Line harus dipilih.',
                'grade.required'   => 'Grade wajib diisi.',
                'grade.in'         => 'Grade harus salah satu dari A, B, atau C.',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'icon'   => 'error',
                'title'  => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $mif = (int) $request->input('mif', session('pos'));
        $popk = $request->popk;
        $bjpkEditing = $request->filled('bjpk') ? (int) $request->bjpk : null;

        // GANTI TOTAL -- FIX UTAMA: pakai resolvePoAndMop() -- dapat $mop dan
        // $canonicalPopk sekaligus.
        [$poRow, $mop, $connection, $canonicalPopk] = $this->resolvePoAndMop($popk, $mif);
        $db = DB::connection($connection);

        $sizeMap = [];
        for ($i = 1; $i <= 40; $i++) {
            $szName = $poRow->{"size$i"} ?? null;
            if (!empty($szName)) {
                $sizeMap[$szName] = $i;
            }
        }

        $finishingQtyPerSize = $this->getFinishingQtyPerSizeForPopk($mop, $canonicalPopk, $sizeMap);

        $existingBjQuery = $db->table('bj')->where('popk', $popk);
        if ($bjpkEditing) {
            $existingBjQuery->where('bjpk', '<>', $bjpkEditing);
        }
        $existingBjRow = $existingBjQuery
            ->selectRaw(collect(range(1, 40))->map(fn ($i) => "SUM(qty{$i}) as qty{$i}")->implode(', '))
            ->first();

        // GANTI -- FIX UTAMA: barcode existing pakai $canonicalPopk.
        $existingBarcodeQty = array_fill(1, 40, 0);
        if ($canonicalPopk !== null) {
            $existingBarcodeRows = DB::connection('mysql_polibag')->table('output')
                ->where('popk', $canonicalPopk)
                ->where('jnspk', 4)
                ->get(['size', 'jmlpcs']);
            foreach ($existingBarcodeRows as $r) {
                $idx = $sizeMap[$r->size] ?? null;
                if ($idx !== null) {
                    $existingBarcodeQty[$idx] += (int) ($r->jmlpcs ?? 0);
                }
            }
        }

        $errorsPerSize = [];
        for ($i = 1; $i <= 40; $i++) {
            $qtyInput = $request->filled("qty{$i}") ? (int) $request->input("qty{$i}") : 0;
            if ($qtyInput <= 0) continue;
            $finishingCap = (int) ($finishingQtyPerSize[$i] ?? 0);
            $alreadyUsedManual  = (int) ($existingBjRow->{"qty{$i}"} ?? 0);
            $alreadyUsedBarcode = (int) ($existingBarcodeQty[$i] ?? 0);
            $alreadyUsed        = $alreadyUsedManual + $alreadyUsedBarcode;
            $available = max(0, $finishingCap - $alreadyUsed);
            if ($qtyInput > $available) {
                $sizeName = $poRow->{"size{$i}"} ?? "Size {$i}";
                $errorsPerSize[] = "Size <b>{$sizeName}</b>: maksimal <b>{$available}</b> "
                    . "(Transfer to Finishing: {$finishingCap}, sudah terpakai: {$alreadyUsed} "
                    . "= Manual {$alreadyUsedManual} + Barcode {$alreadyUsedBarcode}).";
            }
        }
        if (!empty($errorsPerSize)) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Qty tidak dapat disimpan karena melebihi Transfer to Finishing<br>",
            ], 422);
        }

        DB::connection($connection)->beginTransaction();
        try {
            $tanggal = Carbon::parse($request->tanggal)->format('Y-m-d');
            $data = [
                'popk'    => $request->popk,
                'POno'    => $request->po,
                'OP'      => $request->op,
                'linepk'  => $request->linepk,
                'tanggal' => $tanggal,
                'grade'   => $request->filled('grade') ? $request->grade : null,
                'check2'  => 0,
                'waktu'   => now(),
            ];
            $total = 0;
            $hasValue = false;
            for ($i = 1; $i <= 40; $i++) {
                $qty = $request->filled("qty{$i}") ? (int) $request->input("qty{$i}") : 0;
                if ($qty > 0) {
                    $data["qty{$i}"] = $qty;
                    $total += $qty;
                    $hasValue = true;
                } else {
                    $data["qty{$i}"] = null;
                }
            }
            $data['pcs'] = $hasValue ? $total : null;

            if ($request->filled('bjpk')) {
                DB::connection($connection)->table('bj')
                    ->where('bjpk', $request->bjpk)
                    ->update($data);
                $msg = 'Data berhasil diupdate';
            } else {
                DB::connection($connection)->table('bj')->insert($data);
                $msg = 'Data berhasil disimpan';
            }
            DB::connection($connection)->commit();
            return response()->json([
                'icon'  => 'success',
                'title' => $msg,
            ]);
        } catch (\Throwable $e) {
            DB::connection($connection)->rollBack();
            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menyimpan data.',
                'text'  => config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }
    // Di file input.blade.php proses hapus data di tabel Detail Data Polibag
    public function delete($bjpk, Request $request)
    {
        // PENTING: mif dikirim dari frontend (query string ?mif=...) supaya
        // tahu baris bjpk ini ada di koneksi mana. bjpk juga TIDAK unik
        // lintas 2 database, sama seperti popk.
        $mif        = $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);

        try {
            $data = DB::connection($connection)->table('bj')->where('bjpk', $bjpk)->first();

            if (!$data) {
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Data tidak ditemukan',
                ], 404);
            }

            DB::connection($connection)->table('bj')->where('bjpk', $bjpk)->delete();

            return response()->json([
                'icon'  => 'success',
                'title' => 'Data berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menghapus data',
            ], 500);
        }
    }
    // Get atau SUM data Polibag Manual dan Barcode di Breakdown Summary dan tabel Detail Data Polibag
    private function getBjAndOutputRows($db, $popk, ?int $canonicalPopk, array $sizeMap, $cr = null): array
    {
        // ---- Baris MANUAL (bj) -- TETAP $popk mentah (tabel per-koneksi, aman). ----
        $bjRows = $db->table('bj')
            ->leftJoin('line', 'line.linepk', '=', 'bj.linepk')
            ->select('bj.*', 'line.linenm')
            ->where('bj.popk', $popk)
            ->when($cr, fn ($q) => $q->where('bj.linepk', $cr))
            ->get()
            ->map(function ($row) {
                $row->source = 'bj';
                return $row;
            });

        // GANTI -- FIX UTAMA: kalau canonicalPopk tidak ketemu (mif=1 tanpa
        // padanan di database 19), aman kosongkan output -- tidak query
        // dengan popk yang salah.
        if ($canonicalPopk === null) {
            return [$bjRows, collect()];
        }

        $outputRowsRaw = DB::connection('mysql_polibag')->table('output')
            ->leftJoin('line', 'line.linepk', '=', 'output.linepk')
            ->select('output.*', 'line.linenm')
            ->where('output.popk', $canonicalPopk) // GANTI -- canonicalPopk, bukan $popk
            ->where('output.jnspk', 4)
            ->when($cr, fn ($q) => $q->where('output.linepk', $cr))
            ->whereNotNull('output.hari')
            ->get();

        $outputByDate = $outputRowsRaw->groupBy(function ($row) {
            return \Illuminate\Support\Carbon::parse($row->hari)->format('Y-m-d');
        });

        $outputAggRows = collect();

        foreach ($outputByDate as $dateKey => $rowsOnDate) {
            $qty = array_fill(1, 40, 0);
            $totalPcs = 0;

            foreach ($rowsOnDate as $r) {
                $idx = $sizeMap[$r->size] ?? null;
                if ($idx !== null) {
                    $qty[$idx] += (int) ($r->jmlpcs ?? 0);
                }
                $totalPcs += (int) ($r->jmlpcs ?? 0);
            }

            $firstWaktu = $rowsOnDate->pluck('tanggal')->filter()->sort()->first();

            $row = (object) array_merge([
                'bjpk'    => null,
                'popk'    => $popk,
                'tanggal' => $dateKey,
                'waktu'   => $firstWaktu,
                'linenm'  => $rowsOnDate->first()->linenm ?? null,
                'linepk'  => $rowsOnDate->first()->linepk ?? null,
                'grade'   => null,
                'status'  => null,
                'pcs'     => $totalPcs,
                'source'  => 'output',
            ], collect($qty)->mapWithKeys(fn ($v, $i) => ["qty$i" => $v])->all());

            $outputAggRows->push($row);
        }

        return [$bjRows, $outputAggRows];
    }
    // Get data Transfer To Finishing di Breakdown Summary dan validasi saat Tambah/Edit data Polibag
    private function getFinishingQtyPerSizeForPopk(?object $mop, ?int $canonicalPopk, array $sizeMap): array
    {
        $finishingQty = array_fill(1, 40, 0);

        // ---- Manual (tfpbdt) -- GANTI: pakai $mop->moppk LANGSUNG, TIDAK
        // cari mop lewat popk lagi (SELALU gagal untuk mif=1). ----
        if ($mop) {
            $tfpbIds = DB::connection('mysql_finance_mif')->table('tfpb')
                ->where('moppk', $mop->moppk)
                ->pluck('tfpbpk');
            if ($tfpbIds->isNotEmpty()) {
                $tfpbdtRows = DB::connection('mysql_finance_mif')->table('tfpbdt')
                    ->whereIn('tfpbpk', $tfpbIds)
                    ->get(['ukuran', 'qty']);
                foreach ($tfpbdtRows as $r) {
                    $idx = $sizeMap[trim((string) $r->ukuran)] ?? null;
                    if ($idx !== null) {
                        $finishingQty[$idx] += (int) ($r->qty ?? 0);
                    }
                }
            }
        }

        // ---- Barcode (output jnspk=10) -- GANTI: pakai $canonicalPopk. ----
        if ($canonicalPopk !== null) {
            $barcodeRows = DB::connection('mysql_polibag')->table('output')
                ->where('popk', $canonicalPopk)
                ->where('jnspk', 10)
                ->get(['size', 'jmlpcs']);
            foreach ($barcodeRows as $r) {
                $idx = $sizeMap[$r->size] ?? null;
                if ($idx !== null) {
                    $finishingQty[$idx] += (int) ($r->jmlpcs ?? 0);
                }
            }
        }

        return $finishingQty;
    }

    private function getOutputLineBreakdown(string $connection, ?int $canonicalPopk, int $jnspk, array $sizeMap): array
    {
        if ($canonicalPopk === null) {
            return [];
        }

        $rows = DB::connection($connection)->table('output')
            ->leftJoin('line', 'line.linepk', '=', 'output.linepk')
            ->select('output.size', 'output.jmlpcs', 'output.linepk', 'line.linenm')
            ->where('output.popk', $canonicalPopk) // GANTI -- canonicalPopk
            ->where('output.jnspk', $jnspk)
            ->where('output.linepk', '>', 0)
            ->get();

        $byLine = [];
        foreach ($rows as $r) {
            $idx = $sizeMap[$r->size] ?? null;
            if ($idx === null) continue;

            $linepk = (int) $r->linepk;
            if (!isset($byLine[$linepk])) {
                $byLine[$linepk] = [
                    'linenm'     => $r->linenm ?? "Line #{$linepk}",
                    'qtyPerSize' => array_fill(1, 40, 0),
                    'total'      => 0,
                ];
            }
            $qty = (int) ($r->jmlpcs ?? 0);
            $byLine[$linepk]['qtyPerSize'][$idx] += $qty;
            $byLine[$linepk]['total'] += $qty;
        }

        $result = array_values(array_filter($byLine, fn ($l) => $l['total'] > 0));
        usort($result, fn ($a, $b) => strcmp($a['linenm'], $b['linenm']));
        return $result;
    }
    
    /**
     * breakdown per-line KHUSUS baris "Transfer To Finishing":
     * gabungan Manual (tfpbdt, join ke tfpb.linepk) + Barcode (output
     * jnspk=10) -- keduanya di-merge per linepk yang sama.
     */
    private function getFinishingLineBreakdown(?object $mop, ?int $canonicalPopk, array $sizeMap): array
    {
        $byLine = [];

        // ---- Manual (tfpbdt + tfpb.linepk) -- GANTI: pakai $mop->moppk. ----
        if ($mop) {
            $tfpbRows = DB::connection('mysql_finance_mif')->table('tfpb')
                ->where('moppk', $mop->moppk)
                ->get(['tfpbpk', 'linepk']);

            $tfpbById = $tfpbRows->keyBy('tfpbpk');
            $tfpbIds  = $tfpbRows->pluck('tfpbpk');

            if ($tfpbIds->isNotEmpty()) {
                $tfpbdtRows = DB::connection('mysql_finance_mif')->table('tfpbdt')
                    ->whereIn('tfpbpk', $tfpbIds)
                    ->get(['tfpbpk', 'ukuran', 'qty']);

                foreach ($tfpbdtRows as $r) {
                    $idx = $sizeMap[trim((string) $r->ukuran)] ?? null;
                    if ($idx === null || $r->qty === null) continue;

                    $tfpb   = $tfpbById[$r->tfpbpk] ?? null;
                    $linepk = (int) ($tfpb->linepk ?? 0);
                    if ($linepk <= 0) continue;

                    if (!isset($byLine[$linepk])) {
                        $byLine[$linepk] = ['qtyPerSize' => array_fill(1, 40, 0), 'total' => 0];
                    }
                    $qty = (int) $r->qty;
                    $byLine[$linepk]['qtyPerSize'][$idx] += $qty;
                    $byLine[$linepk]['total'] += $qty;
                }
            }
        }

        // ---- Barcode (output jnspk=10) -- GANTI: pakai $canonicalPopk. ----
        if ($canonicalPopk !== null) {
            $barcodeRows = DB::connection('mysql_polibag')->table('output')
                ->select('output.size', 'output.jmlpcs', 'output.linepk')
                ->where('output.popk', $canonicalPopk)
                ->where('output.jnspk', 10)
                ->where('output.linepk', '>', 0)
                ->get();

            foreach ($barcodeRows as $r) {
                $idx = $sizeMap[$r->size] ?? null;
                if ($idx === null) continue;

                $linepk = (int) $r->linepk;
                if (!isset($byLine[$linepk])) {
                    $byLine[$linepk] = ['qtyPerSize' => array_fill(1, 40, 0), 'total' => 0];
                }
                $qty = (int) ($r->jmlpcs ?? 0);
                $byLine[$linepk]['qtyPerSize'][$idx] += $qty;
                $byLine[$linepk]['total'] += $qty;
            }
        }

        $linepks = array_keys($byLine);
        $lineNames = !empty($linepks)
            ? DB::connection('mysql_polibag')->table('line')->whereIn('linepk', $linepks)->pluck('linenm', 'linepk')
            : collect();

        $result = [];
        foreach ($byLine as $linepk => $data) {
            if ($data['total'] <= 0) continue;
            $result[] = [
                'linenm'     => $lineNames[$linepk] ?? "Line #{$linepk}",
                'qtyPerSize' => $data['qtyPerSize'],
                'total'      => $data['total'],
            ];
        }
        usort($result, fn ($a, $b) => strcmp($a['linenm'], $b['linenm']));
        return $result;
    }

    // Filter Buyer
    public function buyerList(Request $request)
    {
        $q = $request->q;

        $buyers = DB::table('po')
            ->select('buyer')
            ->whereNotNull('buyer')
            ->where('buyer', '<>', '')
            ->when($q, function ($query) use ($q) {
                $query->where('buyer', 'like', "%{$q}%");
            })
            ->distinct()
            ->orderBy('buyer')
            ->get()
            ->toArray();

        array_unshift($buyers, (object) [
            'buyer' => '',
            'buyer_name' => 'All Buyer'
        ]);

        foreach ($buyers as $item) {
            if (!isset($item->buyer_name)) {
                $item->buyer_name = $item->buyer;
            }
        }

        return response()->json($buyers);
    }

    // API CHECK
    public function checkFinishing(Request $request)
    {
        $op = trim((string) $request->query('op', ''));

        if ($op === '') {
            return response()->json([
                'error' => 'Parameter "op" wajib diisi. Contoh: ?op=26-0835',
            ], 422);
        }

        $result = [
            'op_dicari' => $op,
        ];

        // ---- STEP 1: cari baris po di KEDUA koneksi (mysql & mysql_andon) ----
        $poRowsMysql = DB::connection('mysql')->table('po')
            ->where('OP', $op)
            ->get(['popk', 'POno', 'OP', 'mif', 'material', 'secsz']);

        $poRowsAndon = DB::connection('mysql_andon')->table('po')
            ->where('OP', $op)
            ->get(['popk', 'POno', 'OP', 'mif', 'material', 'secsz']);

        $result['step1_po_mysql']       = $poRowsMysql->toArray();
        $result['step1_po_mysql_andon'] = $poRowsAndon->toArray();

        $allPoRows = $poRowsMysql->concat($poRowsAndon);

        if ($allPoRows->isEmpty()) {
            $result['kesimpulan'] = "STOP di Step 1 -- tidak ada baris 'po' dengan OP = '{$op}' di kedua koneksi. Cek lagi penulisan No OP-nya.";
            return response()->json($result);
        }

        // ---- STEP 2: ambil SEMUA popk dari baris po tadi ----
        // FIX: sekarang pakai popk (BUKAN ordpk) -- 1 moppk = 1 popk, jadi
        // ini yang jadi kunci penghubung ke mop, bukan ordpk lagi.
        $popks = $allPoRows->pluck('popk')->unique()->values();

        $result['step2_popk_ditemukan'] = $popks->all();

        // ---- STEP 3: cari baris mop yang popk-nya cocok ----
        $mopRows = DB::connection('mysql_finance_mif')->table('mop')
            ->whereIn('popk', $popks)
            ->get(['moppk', 'popk', 'ordpk', 'noop', 'material', 'secsz']);

        $result['step3_mop'] = $mopRows->toArray();

        if ($mopRows->isEmpty()) {
            $result['kesimpulan'] = "STOP di Step 3 -- popk ditemukan (" . implode(',', $popks->all()) . "), tapi TIDAK ADA baris 'mop' di mysql_finance_mif yang punya popk itu. Cek apakah nilai popk di po benar-benar sama persis dengan mop.popk.";
            return response()->json($result);
        }

        // ---- STEP 4: cari baris tfpb yang moppk-nya cocok ----
        $moppks = $mopRows->pluck('moppk')->unique()->values();

        $tfpbRows = DB::connection('mysql_finance_mif')->table('tfpb')
            ->whereIn('moppk', $moppks)
            ->get(['tfpbpk', 'moppk', 'linepk', 'tot']);

        $result['step4_tfpb'] = $tfpbRows->toArray();

        if ($tfpbRows->isEmpty()) {
            $result['kesimpulan'] = "STOP di Step 4 -- mop ditemukan, tapi TIDAK ADA baris 'tfpb' yang moppk-nya cocok. Berarti belum ada transaksi Transfer to Finishing untuk popk-popk ini.";
            return response()->json($result);
        }

        // ---- STEP 5: rincian per POPK -- moppk mana yang punya tfpb, dan
        //      berapa totalnya masing-masing (SUDAH benar per popk, TIDAK
        //      dibagi/diduplikasi ke popk lain). ----
        $tfpbByMoppk = $tfpbRows->groupBy('moppk')->map(fn($g) => $g->sum('tot'));

        $rincianPerPopk = [];
        foreach ($mopRows as $mop) {
            $totalMop = (int) ($tfpbByMoppk[$mop->moppk] ?? 0);
            $rincianPerPopk[] = [
                'popk'      => $mop->popk,
                'moppk'     => $mop->moppk,
                'material'  => $mop->material,
                'secsz'     => $mop->secsz,
                'total_tfpb' => $totalMop,
            ];
        }

        $result['step5_rincian_per_popk'] = $rincianPerPopk;

        $grandTotal = collect($rincianPerPopk)->sum('total_tfpb');
        $result['total_transfer_finishing_seharusnya'] = (int) $grandTotal;

        if ($grandTotal == 0) {
            $result['kesimpulan'] = "Ada baris 'mop' & 'tfpb', tapi semua moppk untuk OP ini totalnya 0 (belum ada transaksi nyata). Lihat step5_rincian_per_popk untuk detail per Color/Sec Size.";
        } else {
            $result['kesimpulan'] = "DATA LENGKAP DAN VALID -- total_transfer_finishing_seharusnya = {$grandTotal} (lihat step5_rincian_per_popk untuk breakdown per Color/Sec Size, karena nilai ini SEKARANG per popk, bukan dibagi rata per ordpk lagi). Kalau di getList()/modal masih beda, masalahnya di logic PHP, bukan di data database.";
        }

        return response()->json($result);
    }

    public function checkFinishingList(Request $request)
    {
        $limit  = (int) $request->query('limit', 50);
        $search = trim((string) $request->query('search', ''));

        // ---- Ambil semua baris po (dari kedua koneksi) -- SEKARANG per
        //      POPK (bukan per ordpk), konsisten dengan fix addTransfer
        //      FinishingToRows() yang join lewat mop.popk. ----
        $poMysql = DB::connection('mysql')->table('po')
            ->when($search !== '', fn($q) => $q->where('OP', 'like', "%{$search}%"))
            ->select('popk', 'OP', 'POno', 'material', 'secsz')
            ->get();

        $poAndon = DB::connection('mysql_andon')->table('po')
            ->when($search !== '', fn($q) => $q->where('OP', 'like', "%{$search}%"))
            ->select('popk', 'OP', 'POno', 'material', 'secsz')
            ->get();

        $allPo = $poMysql->concat($poAndon)->values();

        if ($allPo->isEmpty()) {
            return response()->json([
                'message' => $search !== ''
                    ? "Tidak ada 'po' dengan OP mengandung '{$search}'."
                    : "Tidak ada data 'po' sama sekali.",
            ]);
        }

        $popks = $allPo->pluck('popk')->unique()->values()->all();

        // ---- Sum tfpb.tot per POPK (FIX: join lewat mop.popk, bukan
        //      mop.ordpk -- 1 moppk = 1 popk, jadi tidak ada lagi resiko
        //      1 nilai finance ke-assign ke banyak popk sekaligus). ----
        $sumByPopk = DB::connection('mysql_finance_mif')
            ->table('tfpb')
            ->leftJoin('mop', 'mop.moppk', '=', 'tfpb.moppk')
            ->whereIn('mop.popk', $popks)
            ->groupBy('mop.popk')
            ->selectRaw('mop.popk, SUM(tfpb.tot) as total_jmlpcs')
            ->pluck('total_jmlpcs', 'popk');

        // ---- Susun hasil: HANYA popk yang total-nya > 0 ----
        $result = [];
        foreach ($allPo as $row) {
            $total = (int) ($sumByPopk[$row->popk] ?? 0);
            if ($total > 0) {
                $result[] = [
                    'popk'                     => $row->popk,
                    'OP'                       => $row->OP,
                    'POno'                     => $row->POno,
                    'material'                 => $row->material,
                    'secsz'                    => $row->secsz,
                    'total_transfer_finishing' => $total,
                ];
            }
        }

        // Urutkan dari yang total-nya PALING BESAR, lalu batasi limit.
        usort($result, fn($a, $b) => $b['total_transfer_finishing'] <=> $a['total_transfer_finishing']);

        $totalPopkBernilai = count($result);
        $result = array_slice($result, 0, $limit);

        return response()->json([
            'total_popk_dicek'      => $allPo->count(),
            'jumlah_popk_ada_nilai' => $totalPopkBernilai,
            'ditampilkan'           => count($result),
            'data'                  => $result,
        ]);
    }

    public function checkBjRows($popk, Request $request)
    {
        $mif        = $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);

        $rows = $db->table('bj')
            ->where('popk', $popk)
            ->orderBy('tanggal')
            ->get(['bjpk', 'tanggal', 'waktu', 'linepk', 'grade', 'check2', 'pcs']);

        return response()->json([
            'popk'            => $popk,
            'koneksi_dipakai' => $connection,
            'jumlah_baris'    => $rows->count(),
            'total_pcs'       => (int) $rows->sum('pcs'),
            'rincian'         => $rows->toArray(),
        ]);
    }
}
