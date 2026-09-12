<?php

namespace App\Http\Controllers\TransferFinishing;

use App\Http\Controllers\Controller;
use App\Services\OrderImageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TransferFinishingController extends Controller
{
    // ===============  HALAMAN UTAMA/INDEX TRANSFER TO FINISHING ===========
    // Render halaman index -- data tabel diisi via AJAX oleh getList().
    public function index()
    {
        return view('menu.transfer-finishing.index');
    }

    // Daftar Data OP index.blade.php -- endpoint utama datagrid. Alur:
    // fetch mentah per-mif -> hitung R+Q & Transfer to Finishing -> ambil
    // foto order -> agregasi per PO+OP -> filter (safety-net + R+Q>0) ->
    // sort berdasarkan Ex Factory -> paginasi.
    public function getList(Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;
        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
    
        $combined = $this->fetchAll('mysql', $request);
        $this->addRQToRows($combined);
    
        $this->addTransferFinishingToRows($combined);
        $this->addOrderImageToRows($combined);
    
        $aggregated = $this->applyPostAggregationFilters(
            $this->aggregateByPoOp($combined),
            $request
        )
            ->filter(fn($r) => (float) ($r->transfer ?? 0) > 0)
            ->values();
    
        foreach ($aggregated as $r) {
            $r->gac_sort_ts = $this->normalizeGacForSort($r->GAC);
        }
    
        $aggregated = $aggregated
            ->when(
                $sortDir === 'asc',
                fn($c) => $c->sortBy('gac_sort_ts'),
                fn($c) => $c->sortByDesc('gac_sort_ts')
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

    // Hitung R+Q (sumber tunggal, dari output jnspk 2,6 -- mysql_polibag) per popk.
    private function addRQToRows($rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $normalize = fn($v) => trim(mb_strtoupper((string) $v));

        // GANTI -- FIX UTAMA: kelompokkan popk KANONIK (bukan popk mentah)
        // per orderKey -- popk kanonik inilah yang cocok dengan output.popk.
        $popksByOrderKey = $rows
            ->groupBy(fn($r) => implode('|', [$r->ordpk, $r->OP, $r->POno]))
            ->map(function ($group) {
                return $group
                    ->map(fn($r) => $r->canonical_popk ?? $r->popk)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            });

        $allPopks = $popksByOrderKey->flatten()->unique()->values();

        if ($allPopks->isEmpty()) {
            foreach ($rows as $r) {
                $r->transfer = 0;
            }
            return;
        }

        $outputRows = DB::connection('mysql_polibag')->table('output')
            ->whereIn('popk', $allPopks)
            ->whereIn('jnspk', [2, 6])
            ->where('linepk', '>', 0)
            ->where('statuspk', '>', 0)
            ->get(['popk', 'material', 'jmlpcs']);

        // orderKeyByPopk SEKARANG di-key oleh popk KANONIK (SAMA persis
        // dengan yang dipakai query di atas).
        $orderKeyByPopk = [];
        foreach ($popksByOrderKey as $orderKey => $popksInGroup) {
            foreach ($popksInGroup as $p) {
                $orderKeyByPopk[$p] = $orderKey;
            }
        }

        $rqByOrderMaterial = [];
        foreach ($outputRows as $o) {
            $orderKey = $orderKeyByPopk[$o->popk] ?? null;
            if ($orderKey === null) {
                continue;
            }
            $key = $orderKey . '||' . $normalize($o->material);
            $rqByOrderMaterial[$key] = ($rqByOrderMaterial[$key] ?? 0) + (float) $o->jmlpcs;
        }

        // Assign balik TETAP pakai orderKey dari ordpk/OP/POno -- ini SUDAH
        // konsisten lintas database, TIDAK BERUBAH.
        foreach ($rows as $r) {
            $orderKey = implode('|', [$r->ordpk, $r->OP, $r->POno]);
            $key = $orderKey . '||' . $normalize($r->material);
            $r->transfer = (int) ($rqByOrderMaterial[$key] ?? 0);
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

        // ---- MANUAL: SUM(tfpb.tot) per moppk -- GANTI: pakai moppk
        // (SAMA di kedua database), BUKAN join ke mop.popk. ----
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

        // ---- BARCODE: SUM(output.jmlpcs) jnspk=10 -- GANTI: pakai
        // canonical_popk (output.popk merujuk ke popk database 19). ----
        $canonicalPopks = $rows->map(fn($r) => $r->canonical_popk ?? $r->popk)->filter()->unique()->values()->all();
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
            $manual  = (int) ($manualByMoppk[$r->moppk] ?? 0);
            $canonicalPopk = $r->canonical_popk ?? $r->popk;
            $barcode = (int) ($barcodeByCanonicalPopk[$canonicalPopk] ?? 0);
            $r->transfer_finishing = $manual + $barcode;
        }
    }

    // Buang popk yang tidak punya data mop (mysql_finance_mif) -- Transfer to
    // Finishing tidak relevan untuk PO yang belum ada di sistem finishing.
    private function filterRowsWithMop($rows)
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        // GANTI TOTAL -- FIX UTAMA: pakai 'moppk' (SAMA PERSIS di
        // mysql_andon & mysql untuk baris logis yang sama), BUKAN popk
        // (yang untuk mif=1 tidak pernah cocok ke mop.popk).
        $moppks = $rows->pluck('moppk')->filter()->unique()->values()->all();

        if (empty($moppks)) {
            return $rows->filter(fn() => false)->values();
        }

        $moppksWithMop = DB::connection('mysql_finance_mif')
            ->table('mop')
            ->whereIn('moppk', $moppks)
            ->pluck('moppk')
            ->unique()
            ->flip();

        return $rows->filter(fn($r) => isset($moppksWithMop[$r->moppk]))->values();
    }

    // Get All data list -- query mentah per popk dari tabel po, dengan
    // filter search/buyer/year/ex_factory di level SQL. Dipanggil oleh
    // getList() (index) dan detailByPoOp() (modal) supaya kedua endpoint
    // otomatis konsisten satu sama lain.
    private function fetchAll(string $connection, Request $request)
    {
        $query = DB::connection($connection)->table('po')
            ->leftJoin('bj as bj_line', 'bj_line.popk', '=', 'po.popk')
            ->leftJoin('line', 'line.linepk', '=', 'bj_line.linepk')
            ->where('po.sts', 0)
            ->where('po.qty', '>', 0)
            ->selectRaw("
            po.popk, po.ordpk, po.moppk, po.POno, po.poref, po.OP, po.customer, po.season,
            po.style, po.material, po.buyer, po.qty, po.mif, po.secsz, po.GAC, po.silhouette,
            GROUP_CONCAT(
                DISTINCT TRIM(SUBSTRING(line.linenm,6,3))
                ORDER BY line.linenm
                SEPARATOR ';'
            ) AS linenm
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
                'po.silhouette'
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
        if ($request->filled('ex_factory')) {
            $range = $this->resolveExFactoryRange($request->ex_factory);
            if ($range) {
                $query->whereBetween('po.GAC', [
                    $range['start']->format('Y-m-d 00:00:00'),
                    $range['end']->format('Y-m-d 23:59:59'),
                ]);
            }
        }
        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $rows = $query->orderBy('po.popk', $sortDir)->get();
    
        // GANTI TOTAL -- FIX UTAMA: connection SEKARANG SELALU 'mysql', jadi
        // popk-nya SUDAH kanonik dengan sendirinya -- translasi lewat bdownpk
        // (resolveCanonicalPopks) TIDAK LAGI DIPERLUKAN di sini.
        foreach ($rows as $r) {
            $r->canonical_popk = $r->popk;
        }
    
        return $this->filterRowsWithMop($rows);
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

    // Penjumlahan nilai kolom Qty, R+Q, Transfer to Finishing per PO+OP --
    // representative diambil dari popk TERBESAR dalam grup (termasuk GAC-nya,
    // lihat CATATAN di bawah). Balance = Transfer to Finishing - Order Qty.
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
                $representative->transfer            = (int) $group->sum('transfer');
                $representative->transfer_finishing  = (int) $group->sum('transfer_finishing');
                $representative->balance             = $representative->transfer_finishing - $representative->qty;
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
                $rows = $rows->filter(fn($r) => empty($r->secsz))->values();
            }
        }
    
        $this->addRQToRowsViaBreakdown($rows, $connection);
        $this->addTransferFinishingToRows($rows);
    
        foreach ($rows as $r) {
            $r->balance = (int) ($r->transfer_finishing ?? 0) - (int) ($r->qty ?? 0);
        }
    
        $rows = $rows
            ->sortBy(fn($r) => ($r->customer ?? '') . '|' . ($r->poref ?? ''))
            ->values();
    
        foreach ($rows as $i => $row) {
            $row->no = $i + 1;
        }
    
        return response()->json([
            'total' => $rows->count(),
            'rows'  => $rows,
        ]);
    }

    // ===============  HALAMAN INPUT TRANSFER TO FINISHING ===========
    public function inputTransfer($popk, Request $request)
    {
        $mif = $request->filled('mif') ? (int) $request->query('mif') : null;

        // GANTI TOTAL -- FIX UTAMA: pakai resolvePoAndMop() -- TIDAK LAGI
        // cari 'mop' lewat 'popk' langsung sebagai langkah pertama.
        [$dt, $mop, $connection] = $this->resolvePoAndMop($popk, $mif);

        $breakdown = $this->getBreakdownDataFinishing($mop->moppk, (int) $popk, $connection, (string) $dt->material);
        $siblingPopks = $this->getSiblingPopksByOrder($connection, (int) $popk);

        $usedLinepks = DB::connection('mysql_polibag')->table('output')
            ->whereIn('popk', $siblingPopks)
            ->where('linepk', '>', 0)
            ->whereIn('jnspk', [2, 6]) // R+Q
            ->when(
                (string) $dt->material !== '',
                fn ($q) => $q->whereRaw('TRIM(UPPER(material)) = ?', [trim(mb_strtoupper($dt->material))])
            )
            ->distinct()
            ->pluck('linepk');

        $lines = $usedLinepks->isNotEmpty()
            ? DB::connection('mysql_polibag')->table('line')
                ->whereIn('linepk', $usedLinepks)
                ->where(function ($q) {
                    $q->whereNull('stsbar')->orWhere('stsbar', 0);
                })
                ->orderBy('linenm')
                ->get()
            : collect();

        $fin = DB::connection('mysql_finance_mif');

        $linepksFromManual = $fin->table('tfpb')
            ->where('moppk', $mop->moppk)
            ->whereNotNull('linepk')
            ->where('linepk', '>', 0)
            ->distinct()
            ->pluck('linepk');

        // GANTI -- FIX UTAMA: query output DISINI juga pakai popk KANONIK
        // ($siblingPopks, BUKAN join po.moppk=output.popk yang sebelumnya
        // salah asumsi output.popk sama dengan po mentah).
        $linepksFromBarcode = DB::connection('mysql_polibag')->table('output')
            ->whereIn('output.popk', $siblingPopks)
            ->where('output.linepk', '>', 0)
            ->where('output.jnspk', 10)
            ->distinct()
            ->pluck('output.linepk');

        $filterLinepks = $linepksFromManual
            ->concat($linepksFromBarcode)
            ->unique()
            ->values();

        $filterLines = $filterLinepks->isNotEmpty()
            ? DB::connection('mysql_polibag')->table('line')
                ->whereIn('linepk', $filterLinepks)
                ->orderBy('linenm')
                ->get()
            : collect();

        return view('menu.transfer-finishing.input', array_merge(
            ['mop' => $mop, 'dt' => $dt, 'popk' => $popk, 'mif' => $mif, 'lines' => $lines, 'filterLines' => $filterLines],
            $breakdown
        ));
    }

    public function breakdownSummary($popk, Request $request)
    {
        $mif = $request->filled('mif') ? (int) $request->query('mif') : null;

        // GANTI TOTAL -- FIX UTAMA: pakai resolvePoAndMop().
        [$dt, $mop, $connection] = $this->resolvePoAndMop($popk, $mif);

        $breakdown = $this->getBreakdownDataFinishing($mop->moppk, (int) $popk, $connection, (string) $dt->material);

        return view('menu.transfer-finishing.partials.breakdown_summary', array_merge(
            ['mop' => $mop],
            $breakdown
        ));
    }

    /**
     * R+Q & Barcode per size -- match via PREFIX (bukan exact, bukan substring
     * di mana pun). Kolom mopdt.ukuran cuma varchar(10) dan KEPOTONG untuk
     * label size yang panjang (contoh: "38 IN Waist" jadi "38 IN Wais"),
     * sehingga exact match tidak akan pernah cocok. Substring "di mana pun"
     * juga salah karena size pendek seperti "L" bisa ketemu di dalam "XL".
     * Prefix match ("size dimulai dengan ukuran") aman untuk kedua kasus.
     */
    private function getRqBarcodePerSize(array $siblingPopks, array $mopdtpkToUkuran, ?string $material = null): array
    {
        $rqQty      = array_fill_keys(array_keys($mopdtpkToUkuran), 0.0);
        $barcodeQty = array_fill_keys(array_keys($mopdtpkToUkuran), 0.0);
    
        // BARU -- FIX UTAMA: tambah kolom 'linepk' di query R+Q.
        $rqRowsRaw = DB::connection('mysql_polibag')->table('output')
            ->whereIn('popk', $siblingPopks)
            ->where('linepk', '>', 0)
            ->whereIn('jnspk', [2, 6])
            ->when($material !== null, fn($q) => $q->whereRaw('TRIM(UPPER(material)) = ?', [trim(mb_strtoupper($material))]))
            ->get(['size', 'jmlpcs', 'linepk']); // BARU -- tambah linepk
    
        $barcodeRowsRaw = DB::connection('mysql_polibag')->table('output')
            ->whereIn('popk', $siblingPopks)
            ->where('linepk', '>', 0)
            ->where('jnspk', 10)
            ->when($material !== null, fn($q) => $q->whereRaw('TRIM(UPPER(material)) = ?', [trim(mb_strtoupper($material))]))
            ->get(['size', 'jmlpcs', 'linepk']);
    
        // BARU -- FIX UTAMA: breakdown per line utk R+Q juga.
        $rqByLine      = [];
        $barcodeByLine = [];
    
        foreach ($mopdtpkToUkuran as $mopdtpk => $ukuran) {
            $needle = trim((string) $ukuran);
            if ($needle === '') continue;
    
            $sumRq = 0.0;
            foreach ($rqRowsRaw as $r) {
                if (stripos((string) $r->size, $needle) === 0) {
                    $sumRq += (float) $r->jmlpcs;
    
                    // BARU -- FIX UTAMA: akumulasi ke breakdown per line (R+Q).
                    $linepk = (int) $r->linepk;
                    if (!isset($rqByLine[$linepk])) {
                        $rqByLine[$linepk] = array_fill_keys(array_keys($mopdtpkToUkuran), 0.0);
                    }
                    $rqByLine[$linepk][$mopdtpk] += (float) $r->jmlpcs;
                }
            }
            $rqQty[$mopdtpk] = $sumRq;
    
            $sumBarcode = 0.0;
            foreach ($barcodeRowsRaw as $r) {
                if (stripos((string) $r->size, $needle) === 0) {
                    $sumBarcode += (float) $r->jmlpcs;
    
                    $linepk = (int) $r->linepk;
                    if (!isset($barcodeByLine[$linepk])) {
                        $barcodeByLine[$linepk] = array_fill_keys(array_keys($mopdtpkToUkuran), 0.0);
                    }
                    $barcodeByLine[$linepk][$mopdtpk] += (float) $r->jmlpcs;
                }
            }
            $barcodeQty[$mopdtpk] = $sumBarcode;
        }
    
        return [$rqQty, $barcodeQty, $barcodeByLine, $rqByLine]; // BARU -- tambah $rqByLine
    }

    // Cari mopdtpk (representative) yang cocok dengan teks size dari output --
    // pola PREFIX match yang SAMA dengan getRqBarcodePerSize(), dipakai detailList().
    private function findMopdtpkBySizeText(string $sizeText, $sizes)
    {
        foreach ($sizes as $s) {
            $ukuran = trim((string) $s->ukuran);
            if ($ukuran !== '' && stripos($sizeText, $ukuran) === 0) {
                return $s->mopdtpk;
            }
        }
        return null;
    }

    /**
     * Helper TUNGGAL untuk breakdown Size & Qty halaman Input Transfer to
     * Finishing. Dipakai oleh inputTransfer() (render pertama kali) DAN
     * breakdownSummary() (reload AJAX) -- supaya dijamin konsisten.
     */
    private function getBreakdownDataFinishing($moppk, int $popk, string $connection, string $material): array
    {
        $fin = DB::connection('mysql_finance_mif');
        [$sizes,, $mopdtpksByUkuran] = $this->getDedupedSizes($moppk);
        $orderQty = [];
        foreach ($sizes as $s) {
            $orderQty[$s->mopdtpk] = (float) ($s->qty ?? 0);
        }
        $orderTotalPcs = array_sum($orderQty);
        $mopdtpkToUkuran = $sizes->pluck('ukuran', 'mopdtpk')->all();
    
        $siblingPopks = $this->getSiblingPopksByOrder($connection, $popk);
    
        // BARU -- FIX UTAMA: tangkap $rqByLine juga.
        [$rqQty, $barcodeQty, $barcodeByLine, $rqByLine] = $this->getRqBarcodePerSize($siblingPopks, $mopdtpkToUkuran, $material);
    
        $rqTotalPcs      = array_sum($rqQty);
        $barcodeTotalPcs = array_sum($barcodeQty);
    
        // Helper lokal -- susun breakdown per line + ambil nama line, dipakai
        // utk barcode DAN R+Q (logic-nya identik, cuma sumber datanya beda).
        $buildLineBreakdown = function (array $byLine) {
            if (empty($byLine)) return [];
    
            $linepks = array_keys($byLine);
            $lineNames = DB::connection('mysql_polibag')->table('line')
                ->whereIn('linepk', $linepks)
                ->pluck('linenm', 'linepk');
    
            $out = [];
            foreach ($byLine as $linepk => $qtyPerSize) {
                $rowTotal = array_sum($qtyPerSize);
                if ($rowTotal <= 0) continue;
    
                $out[] = [
                    'linepk'     => $linepk,
                    'linenm'     => $lineNames[$linepk] ?? "Line #{$linepk}",
                    'qtyPerSize' => $qtyPerSize,
                    'total'      => $rowTotal,
                ];
            }
            usort($out, fn($a, $b) => strcmp($a['linenm'], $b['linenm']));
            return $out;
        };
    
        $lineBreakdown   = $buildLineBreakdown($barcodeByLine);
        $rqLineBreakdown = $buildLineBreakdown($rqByLine); // BARU -- FIX UTAMA
    
        $tfpbIdsForMoppk = $fin->table('tfpb')->where('moppk', $moppk)->pluck('tfpbpk');
        $manualQtyByUkuran = [];
        if ($tfpbIdsForMoppk->isNotEmpty()) {
            $tfpbdtAllRows = $fin->table('tfpbdt')
                ->whereIn('tfpbpk', $tfpbIdsForMoppk)
                ->get(['ukuran', 'qty']);
            foreach ($tfpbdtAllRows as $r) {
                $key = trim(mb_strtoupper($r->ukuran));
                $manualQtyByUkuran[$key] = ($manualQtyByUkuran[$key] ?? 0) + (float) $r->qty;
            }
        }
        $manualQty = [];
        foreach ($sizes as $s) {
            $key = trim(mb_strtoupper($s->ukuran));
            $manualQty[$s->mopdtpk] = (float) ($manualQtyByUkuran[$key] ?? 0);
        }
        $manualTotalPcs = (float) ($fin->table('tfpb')->where('moppk', $moppk)
            ->selectRaw('SUM(tot) as tot')->first()->tot ?? 0);
    
        return compact(
            'sizes',
            'orderQty',
            'orderTotalPcs',
            'rqQty',
            'rqTotalPcs',
            'barcodeQty',
            'barcodeTotalPcs',
            'manualQty',
            'manualTotalPcs',
            'lineBreakdown',
            'rqLineBreakdown' // BARU
        );
    }

    // Di file input.blade.php get data di tabel Detail Data Transfer to Finishing
    public function detailList($popk, Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;
        $filterLinepk = $request->cr;
        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        $mif = $request->filled('mif') ? (int) $request->query('mif') : null;

        // GANTI TOTAL -- FIX UTAMA: pakai resolvePoAndMop() -- TIDAK LAGI
        // cari 'mop' lewat 'popk' langsung.
        [$dt, $mop, $connection] = $this->resolvePoAndMop($popk, $mif);

        $fin = DB::connection('mysql_finance_mif');

        [$sizes, $canonicalMap,] = $this->getDedupedSizes($mop->moppk);
        $normalize = fn($v) => mb_strtoupper(trim((string) $v));
        $ukuranToMopdtpk = $sizes->pluck('mopdtpk', 'ukuran')
            ->mapWithKeys(fn($mopdtpk, $ukuran) => [$normalize($ukuran) => $mopdtpk])
            ->all();

        $tfpbRows = $fin->table('tfpb')
            ->where('moppk', $mop->moppk)
            ->when($filterLinepk, fn($q) => $q->where('linepk', $filterLinepk))
            ->get();
        $tfpbpks = $tfpbRows->pluck('tfpbpk')->values();
        $tfpbdtRows = $tfpbpks->isNotEmpty()
            ? $fin->table('tfpbdt')->whereIn('tfpbpk', $tfpbpks)->orderBy('mopdtpk')->get()
            : collect();
        $tfpbdtByTfpbpk = $tfpbdtRows->groupBy('tfpbpk');
        $manualRows = $tfpbRows->map(function ($row) use ($tfpbdtByTfpbpk, $canonicalMap, $ukuranToMopdtpk, $normalize) {
            $sizesForRow = $tfpbdtByTfpbpk->get($row->tfpbpk, collect());
            $flat = [];
            foreach ($sizesForRow as $d) {
                $ukuranKey = $normalize($d->ukuran);
                $targetMopdtpk = $ukuranToMopdtpk[$ukuranKey] ?? ($canonicalMap[$d->mopdtpk] ?? $d->mopdtpk);
                $key = "qty_{$targetMopdtpk}";
                if ($d->qty === null) {
                    if (!array_key_exists($key, $flat)) {
                        $flat[$key] = null;
                    }
                    continue;
                }
                $flat[$key] = ($flat[$key] ?? 0) + $d->qty;
            }
            return (object) array_merge([
                'tfpbpk'  => $row->tfpbpk,
                'tanggal' => $row->tgl,
                'waktu'   => $row->jam,
                'linenm'  => $row->line,
                'linepk'  => $row->linepk,
                'pcs'     => $row->tot,
                'source'  => 'manual',
            ], $flat);
        });

        // GANTI -- FIX UTAMA: barcode di-filter pakai 'po.moppk' via
        // 'mysql_polibag' -- SAMA seperti sebelumnya (join po di dalam
        // koneksi mysql_polibag sendiri, host 19, jadi moppk-nya SUDAH
        // otomatis benar/kanonik -- TIDAK PERLU translasi tambahan di sini).
        $barcodeRowsRaw = DB::connection('mysql_polibag')->table('output')
            ->leftJoin('po', 'po.popk', '=', 'output.popk')
            ->leftJoin('line', 'line.linepk', '=', 'output.linepk')
            ->where('po.moppk', $mop->moppk)
            ->where('output.linepk', '>', 0)
            ->where('output.jnspk', 10)
            ->when($filterLinepk, fn($q) => $q->where('output.linepk', $filterLinepk))
            ->select('output.*', 'line.linenm')
            ->get();
        $barcodeByDate = $barcodeRowsRaw->groupBy(function ($row) {
            return \Illuminate\Support\Carbon::parse($row->hari ?? $row->tanggal)->format('Y-m-d');
        });
        $barcodeRows = collect();
        foreach ($barcodeByDate as $dateKey => $rowsOnDate) {
            $flat = [];
            $totalPcs = 0;
            foreach ($rowsOnDate as $r) {
                $mopdtpk = $this->findMopdtpkBySizeText((string) $r->size, $sizes);
                if ($mopdtpk !== null) {
                    $flat["qty_{$mopdtpk}"] = ($flat["qty_{$mopdtpk}"] ?? 0) + (float) ($r->jmlpcs ?? 0);
                }
                $totalPcs += (float) ($r->jmlpcs ?? 0);
            }
            $barcodeRows->push((object) array_merge([
                'tfpbpk'  => null,
                'tanggal' => $dateKey,
                'waktu'   => $rowsOnDate->first()->tanggal ?? null,
                'linenm'  => $rowsOnDate->first()->linenm ?? null,
                'linepk'  => null,
                'pcs'     => $totalPcs,
                'source'  => 'barcode',
            ], $flat));
        }

        $allRows = $manualRows->concat($barcodeRows)
            ->sortBy(
                fn($r) => \Illuminate\Support\Carbon::parse($r->tanggal)->format('Y-m-d'),
                SORT_REGULAR,
                $sortDir === 'desc'
            )
            ->values();

        $total = $allRows->count();
        $data  = $allRows->slice($offset, $rows)->values();
        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
        }

        return response()->json([
            'total' => $total,
            'rows'  => $data,
        ]);
    }

    /**
     * mopdt sering punya SET LENGKAP terduplikasi (bukan soal Sec Size --
     * mopdt tidak punya kolom itu). Dedupe per ukuran (qty TIDAK di-SUM,
     * karena tiap duplikat punya qty yang SAMA), ambil mopdtpk TERKECIL
     * sebagai representative kolom tabel.
     */
    private function getDedupedSizes($moppk): array
    {
        $fin = DB::connection('mysql_finance_mif');

        $sizesRaw = $fin->table('mopdt')
            ->where('moppk', $moppk)
            ->orderBy('mopdtpk')
            ->get(['mopdtpk', 'ukuran', 'qty']);

        $normalize = fn($v) => trim(mb_strtoupper((string) $v));
        $groupedByUkuran = $sizesRaw->groupBy(fn($s) => $normalize($s->ukuran));

        $sizes = $groupedByUkuran
            ->map(fn($group) => $group->sortBy('mopdtpk')->first())
            ->sortBy('mopdtpk')
            ->values();

        // mopdtpk APA PUN (termasuk duplikat) -> mopdtpk representative-nya --
        // dipakai detailList() supaya breakdown tfpbdt lama tetap muncul benar.
        $canonicalMap = [];
        foreach ($groupedByUkuran as $group) {
            $representative = $group->sortBy('mopdtpk')->first()->mopdtpk;
            foreach ($group as $row) {
                $canonicalMap[$row->mopdtpk] = $representative;
            }
        }

        // ukuran (normalized) -> SEMUA mopdtpk duplikatnya.
        $mopdtpksByUkuran = $groupedByUkuran->map(fn($g) => $g->pluck('mopdtpk')->values());

        return [$sizes, $canonicalMap, $mopdtpksByUkuran];
    }

    // Di file input.blade.php proses tambah/edit data di tabel Detail Data
    // Transfer to Finishing (tfpb + tfpbdt)
    public function saveTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'popk'    => 'required',
            'mif'     => 'nullable|integer',
            'tanggal' => 'required|date',
            'linepk'  => 'required',
            'sizes'   => 'required|array|min:1',
            'sizes.*.mopdtpk' => 'required|integer',
            'sizes.*.ukuran'  => 'required|string',
            'sizes.*.qty'     => 'nullable|numeric|min:0',
        ], [
            'tanggal.required' => 'Tanggal harus diisi.',
            'linepk.required'  => 'Line harus dipilih.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'icon'   => 'error',
                'title'  => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $fin  = DB::connection('mysql_finance_mif');
        $popk = $request->popk;
        $mif  = $request->filled('mif') ? (int) $request->mif : null;

        // GANTI TOTAL -- FIX UTAMA: pakai resolvePoAndMop() -- TIDAK LAGI
        // cari 'mop' lewat 'popk' langsung.
        [$poRowForMaterial, $mop, $connection] = $this->resolvePoAndMop($popk, $mif);

        $tfpbpkEditing = $request->filled('tfpbpk') ? (int) $request->tfpbpk : null;
        $sizesInput    = $request->input('sizes', []);

        [$sizes,, $mopdtpksByUkuran] = $this->getDedupedSizes($mop->moppk);
        $mopdtpkToUkuran = $sizes->pluck('ukuran', 'mopdtpk')->all();

        $currentMaterial = (string) $poRowForMaterial->material;

        $siblingPopks = $this->getSiblingPopksByOrder($connection, (int) $popk);

        [$rqQtyPerSize, $barcodeQtyPerSize] = $this->getRqBarcodePerSize($siblingPopks, $mopdtpkToUkuran, $currentMaterial);

        // ---- Manual yang SUDAH terpakai di TFPB LAIN (baris yang sedang
        //      diedit dikecualikan) -- di-match via ukuran TEKS, supaya entry
        //      lama yang mopdtpk-nya orphan tetap ikut terhitung. ----
        $tfpbIdsOtherQuery = $fin->table('tfpb')->where('moppk', $mop->moppk);
        if ($tfpbpkEditing) {
            $tfpbIdsOtherQuery->where('tfpbpk', '<>', $tfpbpkEditing);
        }
        $tfpbIdsOther = $tfpbIdsOtherQuery->pluck('tfpbpk');
        $manualOtherRaw = $tfpbIdsOther->isNotEmpty()
            ? $fin->table('tfpbdt')->whereIn('tfpbpk', $tfpbIdsOther)->get(['mopdtpk', 'ukuran', 'qty'])
            : collect();
        $ukuranToRepresentative = $sizes->mapWithKeys(
            fn($s) => [trim(mb_strtoupper($s->ukuran)) => $s->mopdtpk]
        )->all();
        $manualOtherPerSize = [];
        foreach ($manualOtherRaw as $r) {
            $ukuranKey = trim(mb_strtoupper($r->ukuran));
            $representative = $ukuranToRepresentative[$ukuranKey] ?? $r->mopdtpk;
            $manualOtherPerSize[$representative] = ($manualOtherPerSize[$representative] ?? 0) + (float) $r->qty;
        }

        // ---- Validasi PER SIZE ----
        $errorsPerSize = [];
        $totalBaru = 0;
        foreach ($sizesInput as $s) {
            $mopdtpk  = (int) ($s['mopdtpk'] ?? 0);
            $qtyInput = (float) ($s['qty'] ?? 0);
            $totalBaru += $qtyInput;
            if ($qtyInput <= 0) continue;
            $rqCap       = (float) ($rqQtyPerSize[$mopdtpk] ?? 0);
            $barcodeUsed = (float) ($barcodeQtyPerSize[$mopdtpk] ?? 0);
            $manualOther = (float) ($manualOtherPerSize[$mopdtpk] ?? 0);
            $available   = max(0, $rqCap - $barcodeUsed - $manualOther);
            if ($qtyInput > $available) {
                $ukuranName = $mopdtpkToUkuran[$mopdtpk] ?? "Size {$mopdtpk}";
                $errorsPerSize[] = "Size <b>{$ukuranName}</b>: maksimal <b>{$available}</b> "
                    . "(R+Q: {$rqCap}, Barcode: {$barcodeUsed}, Manual lain: {$manualOther}).";
            }
        }
        if (!empty($errorsPerSize)) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Qty tidak dapat disimpan karena melebihi R+Q per size",
            ], 422);
        }

        $lineRow = $fin->table('line')->where('linepk', $request->linepk)->first();
        abort_unless($lineRow, 422, 'Line tidak ditemukan.');
        $lineText = ((int) $lineRow->mif === 1)
            ? substr($lineRow->linenm, -2)
            : substr($lineRow->linenm, -3);

        $fin->beginTransaction();
        try {
            $tanggal = Carbon::parse($request->tanggal)->format('Y-m-d');

            if ($tfpbpkEditing) {
                $fin->table('tfpb')->where('tfpbpk', $tfpbpkEditing)->update([
                    'linepk' => $request->linepk,
                    'line'   => $lineText,
                    'tgl'    => $tanggal,
                    'jam'    => now()->format('H:i:s'),
                    'input'  => now(),
                    'tot'    => $totalBaru,
                    'userpk' => session('userpk'),
                    'moppk'  => $mop->moppk,
                ]);
                foreach ($sizesInput as $s) {
                    $qtyValue = (isset($s['qty']) && $s['qty'] !== null && $s['qty'] !== '') ? $s['qty'] : null;
                    $existing = $fin->table('tfpbdt')
                        ->where('tfpbpk', $tfpbpkEditing)
                        ->where('mopdtpk', $s['mopdtpk'])
                        ->first();
                    if ($existing) {
                        $fin->table('tfpbdt')->where('tfpbdtpk', $existing->tfpbdtpk)
                            ->update(['ukuran' => $s['ukuran'], 'qty' => $qtyValue]);
                    } else {
                        $fin->table('tfpbdt')->insert([
                            'tfpbpk'  => $tfpbpkEditing,
                            'mopdtpk' => $s['mopdtpk'],
                            'ukuran'  => $s['ukuran'],
                            'qty'     => $qtyValue,
                        ]);
                    }
                }
                $msg = 'Data berhasil diupdate';
            } else {
                $newTfpbpk = (int) ($fin->table('tfpb')->lockForUpdate()->max('tfpbpk')) + 1;
                $fin->table('tfpb')->insert([
                    'tfpbpk'  => $newTfpbpk,
                    'nobukti' => $newTfpbpk,
                    'linepk'  => $request->linepk,
                    'line'    => $lineText,
                    'tgl'     => $tanggal,
                    'jam'     => now()->format('H:i:s'),
                    'input'   => now(),
                    'tot'     => $totalBaru,
                    'userpk'  => session('userpk'),
                    'moppk'   => $mop->moppk,
                ]);
                foreach ($sizesInput as $s) {
                    $qtyValue = (isset($s['qty']) && $s['qty'] !== null && $s['qty'] !== '') ? $s['qty'] : null;
                    if ($qtyValue === null || (float) $qtyValue <= 0) continue;
                    $fin->table('tfpbdt')->insert([
                        'tfpbpk'  => $newTfpbpk,
                        'mopdtpk' => $s['mopdtpk'],
                        'ukuran'  => $s['ukuran'],
                        'qty'     => $qtyValue,
                    ]);
                }
                $msg = 'Data berhasil disimpan';
            }
            $fin->commit();
            return response()->json(['icon' => 'success', 'title' => $msg]);
        } catch (\Throwable $e) {
            $fin->rollBack();
            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menyimpan data.',
                'text'  => config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    // Di file input.blade.php proses hapus data di tabel Detail Data Transfer to Finishing
    public function delete($tfpbpk, Request $request)
    {
        $fin = DB::connection('mysql_finance_mif');

        try {
            $data = $fin->table('tfpb')->where('tfpbpk', $tfpbpk)->first();
            if (!$data) {
                return response()->json(['icon' => 'warning', 'title' => 'Data tidak ditemukan'], 404);
            }

            $fin->beginTransaction();
            $fin->table('tfpbdt')->where('tfpbpk', $tfpbpk)->delete();
            $fin->table('tfpb')->where('tfpbpk', $tfpbpk)->delete();
            $fin->commit();

            return response()->json(['icon' => 'success', 'title' => 'Data berhasil dihapus']);
        } catch (\Exception $e) {
            $fin->rollBack();
            return response()->json(['icon' => 'error', 'title' => 'Gagal menghapus data'], 500);
        }
    }

    private function resolvePoAndMop($popk, ?int $mif): array
    {
        $fin = DB::connection('mysql_finance_mif');
        $connection = 'mysql';
    
        $dt = DB::connection($connection)->table('po')->where('popk', $popk)->first();
        abort_unless($dt, 404, "Data po untuk popk {$popk} tidak ditemukan.");
    
        $mop = $fin->table('mop')->where('moppk', $dt->moppk)->first();
        abort_unless($mop, 404, "Data mop untuk moppk {$dt->moppk} tidak ditemukan.");
    
        return [$dt, $mop, $connection];
    }

    private function addRQToRowsViaBreakdown($rows, string $connection): void
    {
        if ($rows->isEmpty()) {
            return;
        }
        // FIX: cache key SEKARANG tanpa material -- siblings per ORDER
        // (dipakai bersama lintas warna dalam order yang sama), lebih hemat
        // query dan lebih benar.
        $siblingCache = [];
        foreach ($rows as $r) {
            $groupKey = implode('|', [$r->ordpk ?? '', $r->OP ?? '', $r->POno ?? '']);
            if (!isset($siblingCache[$groupKey])) {
                $siblingCache[$groupKey] = $this->getSiblingPopksByOrder($connection, (int) $r->popk);
            }
            $siblingPopks = $siblingCache[$groupKey];

            // GANTI TOTAL -- FIX UTAMA: pakai $r->moppk LANGSUNG (sudah ada
            // dari selectRaw fetchAll()) -- TIDAK LAGI cari 'mop' lewat
            // 'popk' (yang untuk mif=1 SELALU gagal, karena mop.popk null).
            // Kalau $r->moppk kosong/null, aman set transfer=0.
            if (empty($r->moppk)) {
                $r->transfer = 0;
                continue;
            }
            [$sizes,,] = $this->getDedupedSizes($r->moppk);
            $mopdtpkToUkuran = $sizes->pluck('ukuran', 'mopdtpk')->all();

            // FIX: kirim $r->material -- INI yang menentukan filter warna yang
            // BENAR (dari kolom output.material), bukan dari kecocokan popk.
            [$rqQty,] = $this->getRqBarcodePerSize($siblingPopks, $mopdtpkToUkuran, (string) $r->material);
            $r->transfer = (int) array_sum($rqQty);
        }
    }

    private function getSiblingPopksByOrder(string $connection, int $popk): array
    {
        $db = DB::connection($connection);
        $current = $db->table('po')->where('popk', $popk)->first(['ordpk', 'OP', 'POno']);
        if (!$current) {
            return [$popk];
        }
    
        return $db->table('po')
            ->where('ordpk', $current->ordpk)
            ->where('OP', $current->OP)
            ->where('POno', $current->POno)
            ->pluck('popk')
            ->values()
            ->all();
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
}
