<?php

namespace App\Http\Controllers\StokSisa;

use App\Http\Controllers\Controller;
use App\Services\OrderImageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StokSisaController extends Controller
{
        // ===============  HALAMAN UTAMA/INDEX STOK SISA ===========

    // Render halaman index -- cuma kirim config statis ke blade (judul,
    // label kolom, route AJAX). Data tabelnya SENDIRI diisi belakangan via
    // AJAX oleh getList(), bukan di method ini.
    public function index()
    {
        return view('menu.stok-sisa.index', [
            'pageConfig' => [
                'title'              => 'Daftar Data OP',
                'polibagColumnLabel' => 'Polibag',
                'showQtyColumn'      => false,
                'showPackingColumn'  => false,
                'showKeluarColumn'   => false,
                'showSisaGradeColumn' => true, // BARU
                'routes' => [
                    'list'          => route('stok-sisa.list'),
                    'detailByPoOp'  => route('stok-sisa.detail-by-po-op'),
                    'modalView'     => 'menu.stok-sisa.stoksisa-detail-modal',
                    'navStateKey'   => 'transferListState',
                    'inputUrlBase'  => url('/stok-sisa/input'),
                ],
            ],
        ]);
    }

    // Daftar Data OP index.blade.php -- endpoint utama datagrid. Alur:
    // fetch mentah dari 'mysql' -> hitung Polibag penuh (manual+barcode) &
    // Transfer to Finishing SEKALIGUS (satu scan tabel output, bukan dua)
    // -> ambil foto order -> agregasi per PO+OP -> filter (safety-net +
    // TF Finishing>0) -> sort berdasarkan Ex Factory -> paginasi.
    public function getList(Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;
        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        // Query cukup 'mysql' saja, TANPA filter po.mif -- tampil ke SEMUA
        // user identik, tidak ada lagi pembedaan super user vs user biasa.
        $combined = $this->fetchAll('mysql', $request);

        // SEBELUMNYA overrideTransferWithFullTotal()
        // dan addTransferFinishingToRows() dipanggil TERPISAH, masing-masing
        // query SENDIRI ke 'mysql_polibag.output' (satu utk jnspk=4, satu lagi
        // utk jnspk=10) -- 2x scan tabel yang SAMA. SEKARANG digabung jadi
        // SATU pemanggilan yang di dalamnya cuma SATU query ke 'output'.
        $this->addTransferAndFinishingToRows($combined, 'mysql');

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

    // ambil SEKALIGUS total barcode Polibag
    // (output.jnspk=4, WHERE hari IS NOT NULL) DAN total barcode Transfer to
    // Finishing (output.jnspk=10) untuk sekumpulan popk KANONIK, dalam SATU
    // query ke 'mysql_polibag.output'. SEBELUMNYA ini 2 query TERPISAH
    // (masing-masing method query 'output' sendiri dengan jnspk berbeda) --
    // MySQL scan tabel yang SAMA 2x. Angka yang dihasilkan MATEMATIS IDENTIK
    // dengan sebelumnya (SUM(CASE WHEN kondisi...) == SUM(...) WHERE kondisi
    // yang sama) -- TIDAK ADA informasi yang hilang, cuma dihitung 1x scan.
    //
    // @return array<int, array{polibag:int, finishing:int}> key = canonical popk
    private function getOutputBarcodeTotals(array $canonicalPopks): array
    {
        if (empty($canonicalPopks)) {
            return [];
        }

        $rows = DB::connection('mysql_polibag')->table('output')
            ->whereIn('popk', $canonicalPopks)
            ->whereIn('jnspk', [4, 10])
            ->selectRaw("
                popk,
                SUM(CASE WHEN jnspk = 4 AND hari IS NOT NULL THEN jmlpcs ELSE 0 END) AS total_polibag,
                SUM(CASE WHEN jnspk = 10 THEN jmlpcs ELSE 0 END) AS total_finishing
            ")
            ->groupBy('popk')
            ->get();

        $result = [];
        foreach ($rows as $r) {
            $result[(int) $r->popk] = [
                'polibag'   => (int) $r->total_polibag,
                'finishing' => (int) $r->total_finishing,
            ];
        }
        return $result;
    }

    // method BARU yang menggantikan
    // overrideTransferWithFullTotal() + computeFullTransferPerPopk() +
    // addTransferFinishingToRows() sekaligus. Menghitung DUA metrik per
    // baris $rows dalam SATU alur:
    //   - transfer            : total Polibag PENUH = manual (tabel 'bj')
    //                           + barcode (output.jnspk=4).
    //   - transfer_finishing  : total Transfer to Finishing = manual
    //                           (tabel 'tfpb') + barcode (output.jnspk=10).
    // Query ke 'bj' dan ke 'tfpb' TETAP terpisah (beda koneksi/tabel, tidak
    // bisa digabung), tapi query ke 'output' SEKARANG cuma SATU kali (lewat
    // getOutputBarcodeTotals()) -- sebelumnya dua kali. Hasil akhir SAMA
    // PERSIS dengan sebelumnya untuk kedua kolom.
    private function addTransferAndFinishingToRows($rows, string $connection): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $db = DB::connection($connection);

        // ---- Manual Polibag (bj) -- per popk mentah, tabel per-koneksi. ----
        $popks = $rows->pluck('popk')->unique()->values()->all();
        $manualPolibagByPopk = $db->table('bj')
            ->whereIn('popk', $popks)
            ->groupBy('popk')
            ->selectRaw('popk, SUM(pcs) as total_manual')
            ->pluck('total_manual', 'popk');

        // ---- Manual Transfer to Finishing (tfpb) -- via moppk. ----
        $moppks = $rows->pluck('moppk')->filter()->unique()->values()->all();
        $manualFinishingByMoppk = [];
        if (!empty($moppks)) {
            $manualFinishingByMoppk = DB::connection('mysql_finance_mif')
                ->table('tfpb')
                ->whereIn('moppk', $moppks)
                ->groupBy('moppk')
                ->selectRaw('moppk, SUM(tot) as total_manual')
                ->pluck('total_manual', 'moppk')
                ->all();
        }

        // ---- Barcode (Polibag + Transfer Finishing) -- SATU query gabungan. ----
        $canonicalPopks = $rows->map(fn ($r) => $r->canonical_popk ?? $r->popk)->filter()->unique()->values()->all();
        $barcodeTotals = $this->getOutputBarcodeTotals($canonicalPopks);

        foreach ($rows as $r) {
            $canonicalPopk = $r->canonical_popk ?? $r->popk;
            $barcode = $barcodeTotals[$canonicalPopk] ?? ['polibag' => 0, 'finishing' => 0];

            $r->transfer = (int) ($manualPolibagByPopk[$r->popk] ?? 0) + $barcode['polibag'];

            $manualFinishing = (int) ($manualFinishingByMoppk[$r->moppk] ?? 0);
            $r->transfer_finishing = $manualFinishing + $barcode['finishing'];
        }
    }

    // Tempel field 'canonical_popk' ke setiap baris -- karena koneksi
    // SEKARANG SELALU 'mysql', popk-nya SUDAH kanonik dengan sendirinya
    // (tidak perlu translasi lewat bdownpk seperti versi lama yang masih
    // mendukung mysql_andon).
    private function attachCanonicalPopk($rows, string $connection): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        foreach ($rows as $r) {
            $r->canonical_popk = $r->popk;
        }
    }

    // Get All data list -- query mentah per popk dari tabel po, dengan
    // filter search/buyer/year/ex_factory di level SQL. Dipanggil oleh
    // getList() (index) dan detailByPoOp() (modal) supaya kedua endpoint
    // otomatis konsisten satu sama lain.
    private function fetchAll(string $connection, Request $request)
    {
        // SEBELUMNYA tabel 'bj' di-scan 2x --
        // sekali lewat subquery agregasi (SUM manual Polibag per popk),
        // sekali lagi lewat leftJoin('bj as bj_line', ...) HANYA untuk
        // ambil nama Line (GROUP_CONCAT), yang lalu memaksa query utama
        // butuh GROUP BY 15 kolom supaya baris tidak terduplikasi akibat
        // join mentah itu. SEKARANG SUM dan GROUP_CONCAT dihitung SEKALIGUS
        // di DALAM satu subquery (1 baris per popk) -- MySQL cuma scan 'bj'
        // SEKALI, dan karena hasil subquery sudah 1:1 dengan po.popk, GROUP
        // BY di query utama TIDAK DIPERLUKAN LAGI SAMA SEKALI. Angka &
        // nama Line yang dihasilkan IDENTIK dengan sebelumnya.
        $bjAgg = DB::connection($connection)->table('bj')
            ->leftJoin('line', 'line.linepk', '=', 'bj.linepk')
            ->selectRaw("
                bj.popk,
                SUM(CASE WHEN bj.check2 = 0 THEN bj.pcs ELSE 0 END) AS transfer,
                GROUP_CONCAT(
                    DISTINCT TRIM(SUBSTRING(line.linenm,6,3))
                    ORDER BY line.linenm
                    SEPARATOR ';'
                ) AS linenm
            ")
            ->groupBy('bj.popk');

        $query = DB::connection($connection)->table('po')
            ->leftJoinSub($bjAgg, 'bjagg', function ($join) {
                $join->on('po.popk', '=', 'bjagg.popk');
            })
            ->where('po.sts', 0)
            ->where('po.qty', '>', 0)
            ->select([
                'po.popk', 'po.ordpk', 'po.moppk', 'po.POno', 'po.poref', 'po.OP', 'po.customer', 'po.season',
                'po.style', 'po.material', 'po.buyer', 'po.qty', 'po.mif', 'po.secsz', 'po.GAC', 'po.silhouette',
            ])
            ->selectRaw('bjagg.linenm, COALESCE(bjagg.transfer, 0) AS transfer');

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

        $popksForShipped = $rows->pluck('popk')->filter()->unique()->values();
        $shippedMap = collect();
        $gradedMap  = collect();
        $gradeInfoMap = collect(); // popk -> ['count' => n, 'latest_grade' => 'A'|null]
        
        if ($popksForShipped->isNotEmpty()) {
            $shippedMap = DB::connection($connection)->table('pack')
                ->whereIn('popk', $popksForShipped)
                ->whereIn('status', [6, 7])
                ->groupBy('popk')
                ->selectRaw('popk, SUM(pcs) as shipped_pcs')
                ->pluck('shipped_pcs', 'popk');
        
            $gradedMap = DB::connection($connection)->table('bjgrade')
                ->whereIn('popk', $popksForShipped)
                ->groupBy('popk')
                ->selectRaw('popk, SUM(pcs) as graded_pcs')
                ->pluck('graded_pcs', 'popk');
        
            $gradeRows = DB::connection($connection)->table('bjgrade')
                ->whereIn('popk', $popksForShipped)
                ->select('popk', 'grade', 'bjpk')
                ->orderByDesc('bjpk')
                ->get();
            foreach ($gradeRows->groupBy('popk') as $popkKey => $rowsForPopk) {
                $gradeInfoMap[$popkKey] = [
                    'count'        => $rowsForPopk->count(),
                    'latest_grade' => $rowsForPopk->first()->grade ?? null,
                ];
            }
        }
        
        foreach ($rows as $row) {
            $row->shipped_qty = (float) ($shippedMap[$row->popk] ?? 0);
            $row->graded_qty  = (float) ($gradedMap[$row->popk] ?? 0);
            $row->sisa_belum_digrade = max(0, (float) $row->qty - $row->shipped_qty - $row->graded_qty);
            $row->grade_count   = $gradeInfoMap[$row->popk]['count'] ?? 0;
            $row->latest_grade  = $gradeInfoMap[$row->popk]['latest_grade'] ?? null;
        }
        
        $this->attachCanonicalPopk($rows, $connection);
        
        return $rows;
    }

    // Normalisasi GAC (Ex Factory) jadi UNIX timestamp -- AMAN apa pun
    // format aslinya (string tanggal, datetime, dsb), karena sortByDesc()
    // biasa membandingkan sebagai STRING dan bisa keliru kalau ada
    // perbedaan format/tipe antar baris. Nilai kosong/'0000-00-00' -> 0
    // (dianggap paling awal, aman untuk Earliest/Latest).
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
            ->groupBy(fn ($row) => implode('|', [
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
    
                // BARU -- agregat Shipped/Graded/Sisa per PO+OP.
                $representative->shipped_qty        = (float) $group->sum('shipped_qty');
                $representative->graded_qty         = (float) $group->sum('graded_qty');
                $representative->sisa_belum_digrade = max(0, $representative->qty - $representative->shipped_qty - $representative->graded_qty);
                $representative->grade_count        = (int) $group->sum('grade_count');
                // grade terbaru -- ambil dari baris popk TERBESAR di grup (representative
                // sudah itu), fallback null kalau belum ada grade sama sekali.
                $representative->latest_grade = $representative->latest_grade ?? null;
    
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
    // kolom Aksi index). Connection SELALU 'mysql' -- baris yang diklik
    // dari index (yang sekarang cuma dari 'mysql') bisa saja punya
    // po.mif=1 sebagai NILAI DATA biasa, BUKAN lagi penanda host.
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

        $connection = 'mysql';

        $rows = $this->fetchAll($connection, $request)
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

        // SEBELUMNYA panggil
        // computeFullTransferPerPopk() lalu addTransferFinishingToRows()
        // terpisah (2 query ke 'output') -- SEKARANG satu pemanggilan yang
        // di dalamnya cuma 1 query ke 'output'. Hasil kolom transfer &
        // transfer_finishing SAMA PERSIS dengan sebelumnya.
        $this->addTransferAndFinishingToRows($rows, $connection);

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

    // ===============  HALAMAN INPUT STOK SISA ===========
    // Di file modal-material-list.blade.php memanggil file input.blade.php --
    // render pertama kali halaman Input (breakdown size + dropdown Line
    // untuk modal Add/Edit).
    public function inputTransfer($popk, Request $request)
    {
        $mif = (int) $request->query('mif', session('pos'));
        $db = DB::connection('mysql');
    
        $dt = $db->table('po')->where('popk', $popk)->first();
        abort_unless($dt, 404, "Data po untuk popk {$popk} tidak ditemukan.");
    
        $breakdown = $this->getBreakdownDataTransfer($db, $popk);
    
        return view('menu.stok-sisa.input', array_merge($breakdown, [
            'mif' => $mif,
        ]));
    }

    // Di file input.blade.php memanggil file partial/breakdown_summary.blade.php --
    // reload AJAX breakdown Size & Qty (dipanggil ulang setiap kali data
    // 'bj' berubah, TANPA reload seluruh halaman).
    public function breakdownSummary($popk, Request $request)
    {
        $db = DB::connection('mysql');
        $breakdown = $this->getBreakdownDataTransfer($db, $popk);
        return view('menu.stok-sisa.partials.breakdown_summary', $breakdown);
    }

    /**
     * Helper TUNGGAL untuk breakdown Size & Qty halaman Input STOK SISA.
     * Dipakai oleh inputTransfer() (render pertama kali) DAN breakdownSummary()
     * (reload AJAX) -- supaya dijamin konsisten.
     */
    private function getBreakdownDataTransfer($db, $popk): array
    {
        $dt = $db->table('po')->where('popk', $popk)->first();
        abort_unless($dt, 404);
    
        $activeSizes = [];
        for ($i = 1; $i <= 40; $i++) {
            $sz = $dt->{"size{$i}"} ?? null;
            if (!empty($sz)) $activeSizes[$i] = $sz;
        }
    
        $sumQtyExpr = collect(range(1, 40))->map(fn ($i) => "SUM(qty{$i}) as qty{$i}")->implode(', ');
    
        $shipped = $db->table('pack')->where('popk', $popk)->whereIn('status', [6, 7])
            ->selectRaw($sumQtyExpr)->first();
    
        $graded = $db->table('bjgrade')->where('popk', $popk)
            ->selectRaw($sumQtyExpr . ', SUM(pcs) as total_pcs')->first();
    
        $orderQty = [];
        $shippedQty = [];
        $gradedQty = [];
        $sisaQty = [];
        foreach ($activeSizes as $i => $sz) {
            $orderQty[$i]   = (float) ($dt->{"qty{$i}"} ?? 0);
            $shippedQty[$i] = (float) ($shipped->{"qty{$i}"} ?? 0);
            $gradedQty[$i]  = (float) ($graded->{"qty{$i}"} ?? 0);
            $sisaQty[$i]    = max(0, $orderQty[$i] - $shippedQty[$i] - $gradedQty[$i]);
        }
    
        $totalOrder   = array_sum($orderQty);
        $totalShipped = array_sum($shippedQty);
        $totalGraded  = (float) ($graded->total_pcs ?? 0);
        $totalSisaBelumDigrade = array_sum($sisaQty);
    
        return compact('dt', 'activeSizes', 'orderQty', 'shippedQty', 'gradedQty', 'sisaQty', 'totalOrder', 'totalShipped', 'totalGraded', 'totalSisaBelumDigrade');
    }

    // Di file input.blade.php get data di tabel Detail Data Polibag --
    // gabungan baris manual (bj) + barcode (output, dikelompokkan per
    // tanggal), diurutkan terbaru dulu, dipaginasi di sisi PHP.
    public function detailList($popk, Request $request)
    {
        $page   = max(1, (int) ($request->page ?? 1));
        $rows   = max(1, (int) ($request->rows ?? 50));
        $offset = ($page - 1) * $rows;
    
        $db = DB::connection('mysql');
        $query = $db->table('bjgrade')->where('popk', $popk)->orderByDesc('tanggal')->orderByDesc('bjpk');
    
        $total = (clone $query)->count();
        $data = $query->skip($offset)->take($rows)->get();
    
        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
        }
    
        return response()->json(['total' => $total, 'rows' => $data]);
    }

    // Di file input.blade.php proses tambah/edit data di tabel Detail Data
    // Polibag (tabel 'bj') -- validasi per size TIDAK boleh melebihi sisa
    // Transfer to Finishing (manual+barcode) yang belum terpakai baris lain.
    public function saveTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'popk'    => 'required|integer',
            'tanggal' => 'required|date',
            'grade'   => 'required|in:A,B,C',
        ], [
            'tanggal.required' => 'Tanggal masuk harus diisi.',
            'tanggal.date'     => 'Format tanggal tidak valid.',
            'grade.required'   => 'Grade wajib diisi.',
            'grade.in'         => 'Grade harus salah satu dari A, B, atau C.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'icon'   => 'error',
                'title'  => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }
    
        $db = DB::connection('mysql');
        $popk = (int) $request->popk;
        $bjpkEditing = $request->filled('bjpk') ? (int) $request->bjpk : null;
    
        $dt = $db->table('po')->where('popk', $popk)->first();
        abort_unless($dt, 404);
    
        $sumQtyExpr = collect(range(1, 40))->map(fn ($i) => "SUM(qty{$i}) as qty{$i}")->implode(', ');
    
        $shipped = $db->table('pack')->where('popk', $popk)->whereIn('status', [6, 7])
            ->selectRaw($sumQtyExpr)->first();
    
        $existingGradedQuery = $db->table('bjgrade')->where('popk', $popk);
        if ($bjpkEditing) {
            $existingGradedQuery->where('bjpk', '<>', $bjpkEditing);
        }
        $existingGraded = $existingGradedQuery->selectRaw($sumQtyExpr)->first();
    
        // ---- Validasi PER SIZE: input tidak boleh melebihi SISA (Order -
        // Shipped - sudah digrade entri lain). ----
        $errorsPerSize = [];
        $qtyData = [];
        $total = 0;
        for ($i = 1; $i <= 40; $i++) {
            $qtyInput = $request->filled("qty{$i}") ? (float) $request->input("qty{$i}") : 0;
            if ($qtyInput <= 0) {
                $qtyData[$i] = null;
                continue;
            }
    
            $orderQty   = (float) ($dt->{"qty{$i}"} ?? 0);
            $shippedQty = (float) ($shipped->{"qty{$i}"} ?? 0);
            $usedGraded = (float) ($existingGraded->{"qty{$i}"} ?? 0);
            $available  = max(0, $orderQty - $shippedQty - $usedGraded);
    
            if ($qtyInput > $available) {
                $sizeName = $dt->{"size{$i}"} ?? "Size {$i}";
                $errorsPerSize[] = "Size <b>{$sizeName}</b>: maksimal <b>{$available}</b> pcs "
                    . "(Order: {$orderQty}, Shipped: {$shippedQty}, sudah digrade entri lain: {$usedGraded}).";
            }
    
            $qtyData[$i] = $qtyInput;
            $total += $qtyInput;
        }
        if (!empty($errorsPerSize)) {
            return response()->json(['icon' => 'warning', 'title' => implode('<br>', $errorsPerSize)], 422);
        }
        if ($total <= 0) {
            return response()->json(['icon' => 'warning', 'title' => 'Isi minimal 1 qty size.'], 422);
        }
    
        $data = [
            'popk'     => $popk,
            'OP'       => $dt->OP,
            'POno'     => $dt->POno,
            'customer' => $dt->customer,
            'material' => $dt->material,
            'secsz'    => $dt->secsz,
            'grade'    => $request->grade,
            'tanggal'  => Carbon::parse($request->tanggal)->format('Y-m-d'),
            'waktu'    => now()->format('H:i:s'),
            'pcs'      => $total,
        ];
        foreach ($qtyData as $i => $v) {
            $data["qty{$i}"] = $v;
        }
    
        try {
            if ($bjpkEditing) {
                $existing = $db->table('bjgrade')->where('bjpk', $bjpkEditing)->first();
                if (!$existing) {
                    return response()->json(['icon' => 'warning', 'title' => 'Data tidak ditemukan.'], 404);
                }
                if ((int) $existing->status !== 0) {
                    return response()->json(['icon' => 'warning', 'title' => 'Data ini sudah dikirim ke LO, tidak bisa diubah lagi disini.'], 422);
                }
                $db->table('bjgrade')->where('bjpk', $bjpkEditing)->update($data);
                $msg = 'Data Grade Sisa berhasil diupdate.';
            } else {
                $newBjpk = (int) ($db->table('bjgrade')->lockForUpdate()->max('bjpk')) + 1;
                $data['bjpk']   = $newBjpk;
                $data['status'] = 0;
                $db->table('bjgrade')->insert($data);
                $msg = 'Data Grade Sisa berhasil disimpan.';
            }
    
            return response()->json(['icon' => 'success', 'title' => $msg]);
        } catch (\Throwable $e) {
            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menyimpan data.',
                'text'  => config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    // Di file input.blade.php proses hapus data di tabel Detail Data Polibag
    // (tabel 'bj') -- connection SELALU 'mysql', konsisten dengan
    // detailList()/saveTransfer() yang juga selalu resolve ke 'mysql'.
    public function delete($bjpk, Request $request)
    {
        $db = DB::connection('mysql');
    
        try {
            $row = $db->table('bjgrade')->where('bjpk', $bjpk)->first();
            if (!$row) {
                return response()->json(['icon' => 'warning', 'title' => 'Data tidak ditemukan'], 404);
            }
            if ((int) $row->status !== 0) {
                return response()->json(['icon' => 'warning', 'title' => 'Data ini sudah dikirim ke LO, tidak bisa dihapus disini.'], 422);
            }
    
            $db->table('bjgrade')->where('bjpk', $bjpk)->delete();
    
            return response()->json(['icon' => 'success', 'title' => 'Data Grade Sisa berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal menghapus data'], 500);
        }
    }

    // Filter dropdown Buyer -- daftar buyer unik dari 'po' (koneksi
    // default), dengan opsi "All Buyer" di posisi paling atas.
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
}
