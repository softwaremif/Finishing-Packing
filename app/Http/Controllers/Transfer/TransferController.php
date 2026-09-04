<?php

namespace App\Http\Controllers\Transfer;

use App\Http\Controllers\Controller;
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
            // Super user melihat GABUNGAN 2 mif sekaligus (mysql_andon + mysql).
            $rowsAndon = $this->fetchAll('mysql_andon', 1, $request);
            $rowsMysql = $this->fetchAll('mysql', 2, $request);
            $this->overrideTransferWithFullTotal($rowsAndon, 'mysql_andon');
            $this->overrideTransferWithFullTotal($rowsMysql, 'mysql');
            $combined = $rowsAndon->concat($rowsMysql);
        } else {
            // User biasa HANYA melihat mif sesuai session('pos') miliknya.
            $mif        = session('pos') == 1 ? 1 : 2;
            $connection = session('pos') == 1 ? 'mysql_andon' : 'mysql';
            $combined   = $this->fetchAll($connection, $mif, $request);
            $this->overrideTransferWithFullTotal($combined, $connection);
        }

        $this->addTransferFinishingToRows($combined);

        // Ambil URL gambar order dari mysql_gis (+ fallback mysql_sample).
        $this->addOrderImageToRows($combined);

        // Agregasi per PO+OP, lalu validasi ULANG semua filter aktif
        // (safety-net -- lihat applyPostAggregationFilters()), lalu
        // sembunyikan PO+OP yang Transfer To Finishing-nya 0/null -- belum
        // ada apa pun untuk di-Polibag-kan, tidak relevan ditampilkan di
        // menu ini.
        $aggregated = $this->applyPostAggregationFilters(
            $this->aggregateByPoOp($combined),
            $request
        )
            ->filter(fn($r) => (float) ($r->transfer_finishing ?? 0) > 0)
            ->values();

        // Normalisasi GAC (Ex Factory) jadi timestamp SEBELUM sort -- lihat
        // normalizeGacForSort() untuk alasannya (menghindari sortByDesc()
        // membandingkan sebagai STRING yang bisa keliru).
        foreach ($aggregated as $r) {
            $r->gac_sort_ts = $this->normalizeGacForSort($r->GAC);
        }

        $aggregated = $aggregated
            ->when(
                $sortDir === 'asc',
                fn($c) => $c->sortBy('gac_sort_ts'),       // Earliest Ex Factory dulu
                fn($c) => $c->sortByDesc('gac_sort_ts')    // Latest Ex Factory dulu
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
        if ($rows->isEmpty()) {
            return;
        }

        $gisFotoBase        = rtrim(config('services.foto.gis_base'), '/');
        $productionFotoBase = rtrim(config('services.foto.production_base'), '/');
        $sampleFotoBase     = rtrim(config('services.foto.sample_base'), '/');

        $noImageUrl = asset('public/css/images/no-img.png');

        foreach ($rows as $r) {
            $r->order_image = $noImageUrl;
        }

        $ordpks = $rows->pluck('ordpk')->filter()->unique()->values()->all();
        if (empty($ordpks)) {
            return;
        }

        // ---- Ambil data dasar dari mysql_gis.ord ----
        $ordRows = DB::connection('mysql_gis')->table('ord')
            ->whereIn('ordpk', $ordpks)
            ->get(['ordpk', 'srno', 'foto', 'foto2', 'stsfoto']);

        $ordByOrdpk = $ordRows->keyBy('ordpk');

        // ---- Fallback sample: butuh srno -> srpk -> status.foto (terbaru) ----
        $srnos = $ordRows->pluck('srno')->filter()->unique()->values()->all();

        $srpkBySrno = [];
        $fotoBySrpk = [];

        if (!empty($srnos)) {
            $reqRows = DB::connection('mysql_sample')->table('request')
                ->whereIn('srno', $srnos)
                ->get(['srno', 'srpk']);

            foreach ($reqRows as $r) {
                $srpkBySrno[$r->srno] = $r->srpk;
            }

            $srpks = array_values(array_unique(array_values($srpkBySrno)));

            if (!empty($srpks)) {
                $statusRows = DB::connection('mysql_sample')->table('status')
                    ->whereIn('srpk', $srpks)
                    ->orderByDesc('statuspk')
                    ->get(['srpk', 'foto', 'statuspk']);

                foreach ($statusRows as $r) {
                    // Ambil yang PALING BARU saja (statuspk desc, isi pertama menang).
                    if (!isset($fotoBySrpk[$r->srpk])) {
                        $fotoBySrpk[$r->srpk] = $r->foto;
                    }
                }
            }
        }

        // ---- Terapkan aturan sumber gambar per row ----
        foreach ($rows as $r) {
            $ord = $ordByOrdpk->get($r->ordpk);
            if (!$ord) {
                continue;
            }

            $stsfoto = $ord->stsfoto ?? null;
            $foto1   = $ord->foto ?? null;
            $srno    = $ord->srno ?? null;

            $srpk  = $srno ? ($srpkBySrno[$srno] ?? null) : null;
            $foto2 = $srpk ? ($fotoBySrpk[$srpk] ?? null) : ($ord->foto2 ?? null);

            if ($stsfoto == 1) {
                $r->order_image = !empty($foto1) ? "{$gisFotoBase}/{$foto1}" : $noImageUrl;
            } elseif ($stsfoto == 2) {
                $r->order_image = !empty($foto1) ? "{$productionFotoBase}/{$foto1}" : $noImageUrl;
            } else {
                $r->order_image = !empty($foto2) ? "{$sampleFotoBase}/{$foto2}" : $noImageUrl;
            }
        }
    }

    // Hitung jumlah data Polibag PENUH (manual dari tabel bj + barcode dari
    // output jnspk=4) per popk -- dipakai overrideTransferWithFullTotal().
    private function computeFullTransferPerPopk($db, array $popks): array
    {
        if (empty($popks)) {
            return [];
        }

        $manualByPopk = $db->table('bj')
            ->whereIn('popk', $popks)
            ->groupBy('popk')
            ->selectRaw('popk, SUM(pcs) as total_manual')
            ->pluck('total_manual', 'popk');

        $barcodeByPopk = DB::connection('mysql_polibag')
            ->table('output')
            ->whereIn('popk', $popks)
            ->where('jnspk', 4)
            ->whereNotNull('hari')
            ->groupBy('popk')
            ->selectRaw('popk, SUM(jmlpcs) as total_barcode')
            ->pluck('total_barcode', 'popk');

        $result = [];
        foreach ($popks as $popk) {
            $result[$popk] = (int) ($manualByPopk[$popk] ?? 0) + (int) ($barcodeByPopk[$popk] ?? 0);
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
        $totalByPopk = $this->computeFullTransferPerPopk($db, $popks);

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

        $popks = $rows->pluck('popk')->unique()->values()->all();
        if (empty($popks)) {
            return;
        }

        // ---- MANUAL: SUM(tfpb.tot) per popk -- join LANGSUNG mop.popk,
        //      TANPA filter mop.noop (tidak reliable, dihapus). ----
        $manualByPopk = DB::connection('mysql_finance_mif')
            ->table('tfpb')
            ->leftJoin('mop', 'mop.moppk', '=', 'tfpb.moppk')
            ->whereIn('mop.popk', $popks)
            ->groupBy('mop.popk')
            ->selectRaw('mop.popk, SUM(tfpb.tot) as total_manual')
            ->pluck('total_manual', 'popk');

        // ---- BARCODE: SUM(output.jmlpcs) jnspk=10 per popk. ----
        $barcodeByPopk = DB::connection('mysql_polibag')
            ->table('output')
            ->whereIn('popk', $popks)
            ->where('jnspk', 10)
            ->groupBy('popk')
            ->selectRaw('popk, SUM(jmlpcs) as total_barcode')
            ->pluck('total_barcode', 'popk');

        foreach ($rows as $r) {
            $manual  = (int) ($manualByPopk[$r->popk] ?? 0);
            $barcode = (int) ($barcodeByPopk[$r->popk] ?? 0);
            $r->transfer_finishing = $manual + $barcode;
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
            ->selectRaw("po.popk, po.ordpk, po.POno, po.poref, po.OP, po.customer, po.season, po.style, po.material, po.buyer, po.qty, po.mif, po.secsz, po.GAC, po.silhouette,
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
        return $query->orderBy('po.popk', $sortDir)->get();
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

        $rows = $this->fetchAll($connection, (int) $mif, $request)
            ->where('POno', $po)
            ->where('OP', $op)
            ->values();

        // BARU: kalau material dikirim (klik dari kombinasi spesifik di
        // index), filter HANYA popk yang cocok kombinasi Color+SecSize itu.
        if ($material !== null && $material !== '') {
            $rows = $rows->where('material', $material)->values();

            if ($secsz !== null && $secsz !== '') {
                $rows = $rows->where('secsz', $secsz)->values();
            } else {
                $rows = $rows->filter(fn($r) => empty($r->secsz))->values();
            }
        }

        $popks = $rows->pluck('popk')->unique()->values()->all();
        $transferByPopk = $this->computeFullTransferPerPopk($db, $popks);
        foreach ($rows as $r) {
            $r->transfer = (int) ($transferByPopk[$r->popk] ?? 0);
        }

        $this->addTransferFinishingToRows($rows);

        // FIX UTAMA: TIDAK ada lagi groupBy()+map()+sum() -- setiap popk
        // tetap 1 baris utuh dengan angka MILIKNYA SENDIRI. Balance dihitung
        // ULANG di sini (sebelumnya di-set di dalam ->map() yang sekarang
        // dihapus, jadi HARUS di-set ulang, bukan diam-diam hilang).
        foreach ($rows as $r) {
            $r->balance = (int) ($r->transfer ?? 0) - (int) ($r->qty ?? 0);
        }

        // Sort by customer+poref HANYA untuk visual grouping, TIDAK mengubah data.
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

    // ===============  HALAMAN INPUT TRANSFER/POLIBAG ===========
    // Di file modal-material-list.blade.php memanggil file input.blade.php
    public function inputTransfer($popk, Request $request)
    {
        $isStokSisaMode = $request->route()->getName() === 'stok-sisa.input';

        $mif        = $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);

        $breakdown = $this->getBreakdownDataTransfer($db, $popk);
        extract($breakdown);

        $cr = $request->cr;

        // ============================================================
        // GANTI TOTAL -- $lines (dropdown modal Add/Edit Polibag, manual
        // saja) SEKARANG:
        //   1) TIDAK LAGI difilter by mif (dihapus ->where('mif', $mif)).
        //   2) Kalau Transfer To Finishing (tfpb) PUNYA line manual utk
        //      popk ini, dropdown HANYA berisi line-line tsb -- konsisten
        //      dengan line yang sudah dipakai di TF Finishing.
        //   3) Kalau TF Finishing BELUM punya data manual sama sekali,
        //      fallback ke kriteria lama: line dgn stsbar null/0.
        // ============================================================
        $mopRow = DB::connection('mysql_finance_mif')->table('mop')->where('popk', $popk)->first();

        $linepksFromTfFinishingManual = collect();
        if ($mopRow) {
            $linepksFromTfFinishingManual = DB::connection('mysql_finance_mif')->table('tfpb')
                ->where('moppk', $mopRow->moppk)
                ->whereNotNull('linepk')
                ->where('linepk', '>', 0)
                ->distinct()
                ->pluck('linepk');
        }

        // BARU -- FIX UTAMA: TIDAK ADA LAGI fallback ke "semua line stsbar
        // null/0" (itu global system-wide, salah kalau popk ini memang cuma
        // pernah barcode). Sumber $lines SEKARANG gabungan dari HISTORI
        // MANUAL popk ini sendiri saja:
        //   1) tfpb.linepk (manual TF Finishing utk moppk ini)
        //   2) bj.linepk   (manual Polibag yang SUDAH ADA utk popk ini)
        // Kalau KEDUANYA kosong (popk ini murni barcode, belum pernah ada
        // input manual sama sekali) -> $lines KOSONG, modal TIDAK menawarkan
        // line apa pun.
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
            : collect(); // BARU -- kosong total, TIDAK fallback ke stsbar null/0

        // $filterLines -- TIDAK BERUBAH (gabungan manual+barcode utk popk ini,
        // sudah tidak terpengaruh mif sebelumnya).
        $bjLinepks = $db->table('bj')
            ->where('popk', $popk)
            ->where('linepk', '>', 0)
            ->distinct()
            ->pluck('linepk');

        $outputLinepks = DB::connection('mysql_polibag')->table('output')
            ->where('popk', $popk)
            ->where('jnspk', 4)
            ->where('linepk', '>', 0)
            ->distinct()
            ->pluck('linepk');

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
        $mif        = $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);

        $breakdown = $this->getBreakdownDataTransfer($db, $popk);

        return view('menu.transfer.partials.breakdown_summary', $breakdown);
    }
    /**
     * Helper TUNGGAL untuk breakdown Size & Qty halaman Input Transfer/Polibag.
     * Dipakai oleh inputTransfer() (render pertama kali) DAN breakdownSummary()
     */
    private function getBreakdownDataTransfer($db, $popk): array
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

        [$bjRows, $outputAggRows] = $this->getBjAndOutputRows($db, $popk, $sizeMap, null);

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

        $finishingQty      = $this->getFinishingQtyPerSizeForPopk($popk, $sizeMap);
        $finishingTotalPcs = array_sum($finishingQty);

        // BARU -- FIX UTAMA: breakdown per line utk 2 baris yang diminta.
        $barcodeLineBreakdown   = $this->getOutputLineBreakdown('mysql_polibag', (int) $popk, 4, $sizeMap);
        $finishingLineBreakdown = $this->getFinishingLineBreakdown((int) $popk, $sizeMap);

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
            'barcodeLineBreakdown',   // BARU
            'finishingLineBreakdown' // BARU
        );
    }
    // Di file input.blade.php get data di tabel Detail Data Polibag
    public function detailList($popk, Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;
        $cr     = $request->cr;

        $mif        = $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);

        $poRow = $db->table('po')->where('popk', $popk)->first();
        $sizeMap = [];
        if ($poRow) {
            for ($i = 1; $i <= 40; $i++) {
                $szName = $poRow->{"size$i"} ?? null;
                if (!empty($szName)) {
                    $sizeMap[$szName] = $i;
                }
            }
        }

        // Helper bersama -- SAMA PERSIS dengan yang dipakai breakdownSummary(),
        // supaya dijamin konsisten.
        [$bjRows, $outputAggRows] = $this->getBjAndOutputRows($db, $popk, $sizeMap, $cr);

        // ============================================================
        // FIX: bj dan output TIDAK disortir bareng lintas semua baris lagi
        // (yang bikin keduanya campur aduk sesuai tanggal) -- masing-masing
        // diurutkan SENDIRI dulu (tanggal terbaru duluan), lalu digabung
        // dengan bj SELALU DI ATAS, output SELALU MENEMPEL DI BAWAH
        // sebagai grup tersendiri (bukan diselang-seling per tanggal).
        // ============================================================
        $bjRowsSorted = $bjRows
            ->sortByDesc(fn($r) => \Illuminate\Support\Carbon::parse($r->tanggal)->format('Y-m-d'))
            ->values();

        $outputRowsSorted = $outputAggRows
            ->sortByDesc(fn($r) => \Illuminate\Support\Carbon::parse($r->tanggal)->format('Y-m-d'))
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

        $mif        = $request->input('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);

        $popk        = $request->popk;
        $bjpkEditing = $request->filled('bjpk') ? (int) $request->bjpk : null;

        $poRow = $db->table('po')->where('popk', $popk)->first();
        $sizeMap = [];
        if ($poRow) {
            for ($i = 1; $i <= 40; $i++) {
                $szName = $poRow->{"size$i"} ?? null;
                if (!empty($szName)) {
                    $sizeMap[$szName] = $i;
                }
            }
        }

        $finishingQtyPerSize = $this->getFinishingQtyPerSizeForPopk($popk, $sizeMap);

        // ---- Manual (bj) yang SUDAH ADA -- baris yang SEDANG diedit
        //      dikecualikan, SAMA seperti sebelumnya. ----
        $existingBjQuery = $db->table('bj')->where('popk', $popk);
        if ($bjpkEditing) {
            $existingBjQuery->where('bjpk', '<>', $bjpkEditing);
        }
        $existingBjRow = $existingBjQuery
            ->selectRaw(collect(range(1, 40))->map(fn($i) => "SUM(qty{$i}) as qty{$i}")->implode(', '))
            ->first();

        // ============================================================
        // FIX UTAMA: Barcode (output jnspk=4, SELALU mysql_polibag) yang
        // SUDAH ADA -- SEBELUMNYA TIDAK PERNAH dihitung sama sekali di
        // validasi ini, jadi "sudah terpakai" cuma cerminan Manual saja,
        // padahal aturannya Manual + Barcode GABUNGAN yang tidak boleh
        // melebihi Transfer to Finishing.
        // ============================================================
        $existingBarcodeRows = DB::connection('mysql_polibag')->table('output')
            ->where('popk', $popk)
            ->where('jnspk', 4)
            ->get(['size', 'jmlpcs']);

        $existingBarcodeQty = array_fill(1, 40, 0);
        foreach ($existingBarcodeRows as $r) {
            $idx = $sizeMap[$r->size] ?? null;
            if ($idx !== null) {
                $existingBarcodeQty[$idx] += (int) ($r->jmlpcs ?? 0);
            }
        }

        $errorsPerSize = [];
        for ($i = 1; $i <= 40; $i++) {
            $qtyInput = $request->filled("qty{$i}") ? (int) $request->input("qty{$i}") : 0;
            if ($qtyInput <= 0) continue;

            $finishingCap = (int) ($finishingQtyPerSize[$i] ?? 0);

            // FIX: gabungan Manual + Barcode, bukan Manual saja.
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
                'text'  => config('app.debug')
                    ? $e->getMessage()
                    : 'Terjadi kesalahan pada sistem.',
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
    private function getBjAndOutputRows($db, $popk, array $sizeMap, $cr = null): array
    {
        // ---- Baris MANUAL (bj) -- TETAP dari $db (per-mif: mysql/mysql_andon). ----
        $bjRows = $db->table('bj')
            ->leftJoin('line', 'line.linepk', '=', 'bj.linepk')
            ->select('bj.*', 'line.linenm')
            ->where('bj.popk', $popk)
            ->when($cr, fn ($q) => $q->where('bj.linepk', $cr)) // FIX UTAMA -- linepk, bukan line.linenm
            ->get()
            ->map(function ($row) {
                $row->source = 'bj';
                return $row;
            });
    
        $outputRowsRaw = DB::connection('mysql_polibag')->table('output')
            ->leftJoin('line', 'line.linepk', '=', 'output.linepk')
            ->select('output.*', 'line.linenm')
            ->where('output.popk', $popk)
            ->where('output.jnspk', 4)
            ->when($cr, fn ($q) => $q->where('output.linepk', $cr)) // FIX UTAMA -- linepk, bukan line.linenm
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
                'linepk'  => $rowsOnDate->first()->linepk ?? null, // BARU -- ikut disertakan (belum ada sebelumnya)
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
    private function getFinishingQtyPerSizeForPopk($popk, array $sizeMap): array
    {
        $finishingQty = array_fill(1, 40, 0);

        // ---- Manual (tfpbdt) ----
        $mopRow = DB::connection('mysql_finance_mif')->table('mop')
            ->where('popk', $popk)
            ->first();

        if ($mopRow) {
            $tfpbIds = DB::connection('mysql_finance_mif')->table('tfpb')
                ->where('moppk', $mopRow->moppk)
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

        // ---- Barcode (output jnspk=10, SELALU mysql_polibag) ----
        $barcodeRows = DB::connection('mysql_polibag')->table('output')
            ->where('popk', $popk)
            ->where('jnspk', 10)
            ->get(['size', 'jmlpcs']);

        foreach ($barcodeRows as $r) {
            $idx = $sizeMap[$r->size] ?? null;
            if ($idx !== null) {
                $finishingQty[$idx] += (int) ($r->jmlpcs ?? 0);
            }
        }

        return $finishingQty;
    }

    private function getOutputLineBreakdown(string $connection, int $popk, int $jnspk, array $sizeMap): array
    {
        $rows = DB::connection($connection)->table('output')
            ->leftJoin('line', 'line.linepk', '=', 'output.linepk')
            ->select('output.size', 'output.jmlpcs', 'output.linepk', 'line.linenm')
            ->where('output.popk', $popk)
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
    private function getFinishingLineBreakdown(int $popk, array $sizeMap): array
    {
        $byLine = []; // linepk => ['qtyPerSize' => [...], 'total' => int]
    
        // ---- Manual (tfpbdt + tfpb.linepk) -- CUMA ambil linepk-nya,
        //      nama line TIDAK diambil dari sini lagi. ----
        $mopRow = DB::connection('mysql_finance_mif')->table('mop')->where('popk', $popk)->first();
        if ($mopRow) {
            $tfpbRows = DB::connection('mysql_finance_mif')->table('tfpb')
                ->where('moppk', $mopRow->moppk)
                ->get(['tfpbpk', 'linepk']); // FIX -- 'line' (teks) tidak diambil lagi
    
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
    
        // ---- Barcode (output jnspk=10, SELALU mysql_polibag) ----
        $barcodeRows = DB::connection('mysql_polibag')->table('output')
            ->select('output.size', 'output.jmlpcs', 'output.linepk')
            ->where('output.popk', $popk)
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
    
        // BARU -- FIX UTAMA: ambil nama line dari SATU sumber otoritatif
        // (mysql_polibag.line.linenm) utk SEMUA linepk yang terkumpul --
        // baik dari manual maupun barcode, KONSISTEN.
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
