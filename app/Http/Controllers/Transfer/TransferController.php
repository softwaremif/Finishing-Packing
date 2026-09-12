<?php

namespace App\Http\Controllers\Transfer;

use App\Http\Controllers\Controller;
use App\Services\OrderImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TransferController extends Controller
{
    // ===============  HALAMAN UTAMA/INDEX TRANSFER/POLIBAG ===========

    // Render halaman index -- cuma kirim config statis ke blade (judul,
    // label kolom, route AJAX). Data tabelnya SENDIRI diisi belakangan via
    // AJAX oleh getList(), bukan di method ini.
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
                    'inputUrlBase'  => url('/polibag/input'),
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

    // ===============  HALAMAN INPUT TRANSFER/POLIBAG ===========
    // Di file modal-material-list.blade.php memanggil file input.blade.php --
    // render pertama kali halaman Input (breakdown size + dropdown Line
    // untuk modal Add/Edit).
    public function inputTransfer($popk, Request $request)
    {
        $isStokSisaMode = $request->route()->getName() === 'stok-sisa.input';
        $mif = (int) $request->query('mif', session('pos'));

        [, $mop, $connection, $canonicalPopk] = $this->resolvePoAndMop($popk, $mif);
        $db = DB::connection($connection);

        $breakdown = $this->getBreakdownDataTransfer($db, $popk, $mop, $canonicalPopk);
        extract($breakdown);
        $cr = $request->cr;

        // ---- Dropdown Line utk modal Add/Edit -- gabungan Line yang
        // pernah dipakai di TF Finishing (manual/tfpb) + Line yang pernah
        // dipakai di baris 'bj' popk ini sendiri. ----
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

        // ---- $filterLines -- Line yang PERNAH dipakai carton/transaksi
        // popk ini (bj manual + barcode Polibag jnspk=4), utk dropdown
        // filter tabel Detail Data Polibag. ----
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

    // Di file input.blade.php memanggil file partial/breakdown_summary.blade.php --
    // reload AJAX breakdown Size & Qty (dipanggil ulang setiap kali data
    // 'bj' berubah, TANPA reload seluruh halaman).
    public function breakdownSummary($popk, Request $request)
    {
        $mif = (int) $request->query('mif', session('pos'));

        [, $mop, $connection, $canonicalPopk] = $this->resolvePoAndMop($popk, $mif);
        $db = DB::connection($connection);

        $breakdown = $this->getBreakdownDataTransfer($db, $popk, $mop, $canonicalPopk);

        return view('menu.transfer.partials.breakdown_summary', $breakdown);
    }

    // Resolve baris 'po' + 'mop' (mysql_finance_mif) untuk 1 popk. Connection
    // SEKARANG SELALU 'mysql' -- popk yang diterima method ini SELALU fisik
    // ada di 'mysql' (karena getList()/fetchAll() sekarang cuma pernah
    // mengambil baris dari situ), APAPUN nilai $mif-nya (mif sekarang murni
    // atribut data, BUKAN lagi penanda host). canonicalPopk juga SELALU
    // sama dengan popk itu sendiri, tidak perlu translasi lagi.
    private function resolvePoAndMop($popk, int $mif): array
    {
        $connection = 'mysql';

        $dt = DB::connection($connection)->table('po')->where('popk', $popk)->first();
        abort_unless($dt, 404, "Data po untuk popk {$popk} tidak ditemukan.");

        $mop = DB::connection('mysql_finance_mif')->table('mop')
            ->where('moppk', $dt->moppk)
            ->first();

        $canonicalPopk = (int) $popk;

        return [$dt, $mop, $connection, $canonicalPopk];
    }

    /**
     * Helper TUNGGAL untuk breakdown Size & Qty halaman Input Transfer/Polibag.
     * Dipakai oleh inputTransfer() (render pertama kali) DAN breakdownSummary()
     * (reload AJAX) -- supaya dijamin konsisten.
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

    // Di file input.blade.php get data di tabel Detail Data Polibag --
    // gabungan baris manual (bj) + barcode (output, dikelompokkan per
    // tanggal), diurutkan terbaru dulu, dipaginasi di sisi PHP.
    public function detailList($popk, Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;
        $cr     = $request->cr;
        $mif    = (int) $request->query('mif', session('pos'));

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

        // Dipakai frontend buat kunci/tandai baris (mis. larang hapus)
        // kalau popk ini sudah pernah masuk proses packing (status=5).
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

    // Di file input.blade.php proses tambah/edit data di tabel Detail Data
    // Polibag (tabel 'bj') -- validasi per size TIDAK boleh melebihi sisa
    // Transfer to Finishing (manual+barcode) yang belum terpakai baris lain.
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

        // ---- Manual (bj) yang SUDAH terpakai baris lain (baris yang
        // sedang diedit dikecualikan dari perhitungan). ----
        $existingBjQuery = $db->table('bj')->where('popk', $popk);
        if ($bjpkEditing) {
            $existingBjQuery->where('bjpk', '<>', $bjpkEditing);
        }
        $existingBjRow = $existingBjQuery
            ->selectRaw(collect(range(1, 40))->map(fn ($i) => "SUM(qty{$i}) as qty{$i}")->implode(', '))
            ->first();

        // ---- Barcode (output jnspk=4) yang SUDAH terpakai. ----
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

        // ---- Validasi PER SIZE: input tidak boleh melebihi sisa Transfer
        // to Finishing (cap) dikurangi yang sudah terpakai baris lain. ----
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
    // (tabel 'bj') -- connection SELALU 'mysql', konsisten dengan
    // detailList()/saveTransfer() yang juga selalu resolve ke 'mysql'.
    public function delete($bjpk, Request $request)
    {
        $connection = 'mysql';

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

    // Get/SUM data Polibag Manual (bj, per-koneksi) dan Barcode (output
    // jnspk=4, di-agregasi per TANGGAL) untuk 1 popk -- dipakai Breakdown
    // Summary dan tabel Detail Data Polibag. Kalau canonicalPopk null
    // (popk tanpa padanan di database kanonik), output dikosongkan --
    // AMAN, tidak query dengan popk yang salah.
    private function getBjAndOutputRows($db, $popk, ?int $canonicalPopk, array $sizeMap, $cr = null): array
    {
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

        if ($canonicalPopk === null) {
            return [$bjRows, collect()];
        }

        $outputRowsRaw = DB::connection('mysql_polibag')->table('output')
            ->leftJoin('line', 'line.linepk', '=', 'output.linepk')
            ->select('output.*', 'line.linenm')
            ->where('output.popk', $canonicalPopk)
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

    // Get data Transfer To Finishing PER SIZE (manual tfpbdt + barcode
    // output jnspk=10) untuk 1 popk -- dipakai Breakdown Summary DAN
    // validasi cap saat Tambah/Edit data Polibag (saveTransfer()).
    private function getFinishingQtyPerSizeForPopk(?object $mop, ?int $canonicalPopk, array $sizeMap): array
    {
        $finishingQty = array_fill(1, 40, 0);

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

    // Breakdown barcode (output, jnspk tertentu -- 4 utk Polibag) PER LINE
    // untuk 1 popk -- dipakai menampilkan rincian "barcode masuk dari Line
    // mana saja" di Breakdown Summary.
    private function getOutputLineBreakdown(string $connection, ?int $canonicalPopk, int $jnspk, array $sizeMap): array
    {
        if ($canonicalPopk === null) {
            return [];
        }

        $rows = DB::connection($connection)->table('output')
            ->leftJoin('line', 'line.linepk', '=', 'output.linepk')
            ->select('output.size', 'output.jmlpcs', 'output.linepk', 'line.linenm')
            ->where('output.popk', $canonicalPopk)
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
     * Breakdown per-line KHUSUS baris "Transfer To Finishing":
     * gabungan Manual (tfpbdt, join ke tfpb.linepk) + Barcode (output
     * jnspk=10) -- keduanya di-merge per linepk yang sama.
     */
    private function getFinishingLineBreakdown(?object $mop, ?int $canonicalPopk, array $sizeMap): array
    {
        $byLine = [];

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