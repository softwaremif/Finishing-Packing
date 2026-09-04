<?php

namespace App\Http\Controllers\TransferFinishing;

use App\Http\Controllers\Controller;
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

    // Memisahkan koneksi database sesuai mif user yang login (1=mysql_andon, 2=mysql).
    private function resolveConnection($mif): string
    {
        return ((int) $mif) === 1 ? 'mysql_andon' : 'mysql';
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

        $isSuper = session('guserpk') == 34;

        if ($isSuper) {
            // Super user melihat GABUNGAN 2 mif sekaligus (mysql_andon + mysql).
            $rowsAndon = $this->fetchAll('mysql_andon', 1, $request);
            $rowsMysql = $this->fetchAll('mysql', 2, $request);
            $this->addRQToRows($rowsAndon);
            $this->addRQToRows($rowsMysql);
            $combined = $rowsAndon->concat($rowsMysql);
        } else {
            // User biasa HANYA melihat mif sesuai session('pos') miliknya.
            $mif        = session('pos') == 1 ? 1 : 2;
            $connection = $this->resolveConnection($mif);
            $combined   = $this->fetchAll($connection, $mif, $request);
            $this->addRQToRows($combined);
        }

        $this->addTransferFinishingToRows($combined);

        // Ambil URL gambar order dari mysql_gis (+ fallback mysql_sample).
        $this->addOrderImageToRows($combined);

        // Agregasi per PO+OP, lalu validasi ULANG semua filter aktif
        // (safety-net -- lihat applyPostAggregationFilters()), lalu
        // sembunyikan PO+OP yang R+Q-nya 0/null (belum ada aktivitas sama
        // sekali, tidak relevan ditampilkan di menu ini).
        $aggregated = $this->applyPostAggregationFilters(
            $this->aggregateByPoOp($combined),
            $request
        )
            ->filter(fn($r) => (float) ($r->transfer ?? 0) > 0)
            ->values();

        // Normalisasi GAC (Ex Factory) jadi timestamp SEBELUM sort -- lihat
        // normalizeGacForSort() untuk alasannya.
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

        // Base URL tiap server foto -- taruh di config/env supaya gampang
        // diubah per environment tanpa edit kode.
        $gisFotoBase        = rtrim(config('services.foto.gis_base'), '/');
        $productionFotoBase = rtrim(config('services.foto.production_base'), '/');
        $sampleFotoBase     = rtrim(config('services.foto.sample_base'), '/');
        $noImageUrl = asset('public/css/images/no-img.png');

        // Default semua row -> no image dulu.
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
                continue; // tetap pakai default no-image
            }

            $stsfoto = $ord->stsfoto ?? null;
            $foto1   = $ord->foto ?? null;
            $srno    = $ord->srno ?? null;

            $srpk  = $srno ? ($srpkBySrno[$srno] ?? null) : null;
            $foto2 = $srpk ? ($fotoBySrpk[$srpk] ?? null) : ($ord->foto2 ?? null);

            if ($stsfoto == 1) {
                $r->order_image = !empty($foto1)
                    ? "{$gisFotoBase}/{$foto1}"
                    : $noImageUrl;
            } elseif ($stsfoto == 2) {
                $r->order_image = !empty($foto1)
                    ? "{$productionFotoBase}/{$foto1}"
                    : $noImageUrl;
            } else {
                $r->order_image = !empty($foto2)
                    ? "{$sampleFotoBase}/{$foto2}"
                    : $noImageUrl;
            }
        }
    }

    // Hitung R+Q (sumber tunggal, dari output jnspk 2,6 -- mysql_polibag) per popk.
    private function addRQToRows($rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }
    
        $normalize = fn($v) => trim(mb_strtoupper((string) $v));
    
        // Kelompokkan SEMUA popk yang ada di $rows per grup ORDER
        // (ordpk+OP+POno) -- $rows di sini biasanya SUDAH mencakup semua
        // warna dalam order yang sama (fetchAll() tidak memfilter material).
        $popksByOrderKey = $rows
            ->groupBy(fn($r) => implode('|', [$r->ordpk, $r->OP, $r->POno]))
            ->map(fn($group) => $group->pluck('popk')->unique()->values()->all());
    
        $allPopks = $popksByOrderKey->flatten()->unique()->values();
    
        if ($allPopks->isEmpty()) {
            foreach ($rows as $r) {
                $r->transfer = 0;
            }
            return;
        }
    
        // Ambil SEMUA baris output R+Q (jnspk 2,6) untuk SELURUH popk dalam
        // order-order ini, LENGKAP dengan kolom material asli -- supaya baris
        // yang "kesasar" di popk warna lain tetap ikut dihitung ke warna yang
        // BENAR (output.material), bukan ke warna popk tempat ia tercatat.
        $outputRows = DB::connection('mysql_polibag')->table('output')
            ->whereIn('popk', $allPopks)
            ->whereIn('jnspk', [2, 6])
            ->where('linepk', '>', 0)
            ->where('statuspk', '>', 0)
            ->get(['popk', 'material', 'jmlpcs']);
    
        // popk -> orderKey, supaya tiap baris output tahu ia milik order yang
        // mana (walau popk-nya bisa jadi popk warna lain dalam order itu).
        $orderKeyByPopk = [];
        foreach ($popksByOrderKey as $orderKey => $popksInGroup) {
            foreach ($popksInGroup as $p) {
                $orderKeyByPopk[$p] = $orderKey;
            }
        }
    
        // Total R+Q per kombinasi (orderKey + material asli output) -- INI
        // kunci pengelompokan yang BENAR.
        $rqByOrderMaterial = [];
        foreach ($outputRows as $o) {
            $orderKey = $orderKeyByPopk[$o->popk] ?? null;
            if ($orderKey === null) {
                continue; // popk di luar scope $rows, aman diabaikan
            }
            $key = $orderKey . '||' . $normalize($o->material);
            $rqByOrderMaterial[$key] = ($rqByOrderMaterial[$key] ?? 0) + (float) $o->jmlpcs;
        }
    
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

        $popks = $rows->pluck('popk')->unique()->values()->all();
        if (empty($popks)) {
            return;
        }

        // ---- MANUAL: SUM(tfpb.tot) per popk -- join LANGSUNG mop.popk,
        //      TANPA filter mop.noop (tidak reliable, sudah dihapus). ----
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

    // Buang popk yang tidak punya data mop (mysql_finance_mif) -- Transfer to
    // Finishing tidak relevan untuk PO yang belum ada di sistem finishing.
    private function filterRowsWithMop($rows)
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $popks = $rows->pluck('popk')->unique()->values()->all();

        $popksWithMop = DB::connection('mysql_finance_mif')
            ->table('mop')
            ->whereIn('popk', $popks)
            ->pluck('popk')
            ->unique()
            ->flip(); // lookup set O(1) -- isset() jauh lebih cepat dari contains()

        return $rows->filter(fn($r) => isset($popksWithMop[$r->popk]))->values();
    }

    // Get All data list -- query mentah per popk dari tabel po, dengan
    // filter search/buyer/year/ex_factory di level SQL. Dipanggil oleh
    // getList() (index) dan detailByPoOp() (modal) supaya kedua endpoint
    // otomatis konsisten satu sama lain.
    private function fetchAll(string $connection, int $mif, Request $request)
    {
        $query = DB::connection($connection)->table('po')
            ->leftJoin('bj as bj_line', 'bj_line.popk', '=', 'po.popk')
            ->leftJoin('line', 'line.linepk', '=', 'bj_line.linepk')
            ->where('po.sts', 0)
            ->where('po.qty', '>', 0)
            ->where('po.mif', $mif)
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

        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $rows = $query->orderBy('po.popk', $sortDir)->get();

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

        $mif        = $validated['mif'] ?? session('pos');
        $connection = $this->resolveConnection($mif);

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
                // Kombinasi ini memang tidak punya secsz -- cocokkan yang
                // secsz-nya kosong/null saja, bukan yang punya secsz lain.
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
        $fin = DB::connection('mysql_finance_mif');
        $mop = $fin->table('mop')->where('popk', $popk)->first();
        abort_unless($mop, 404, "Data mop untuk popk {$popk} tidak ditemukan.");

        $mifGuess   = str_contains((string) $mop->noop, 'MIF1') ? 1 : 2;
        $connection = $this->resolveConnection($mifGuess);
        $dt = DB::connection($connection)->table('po')->where('popk', $popk)->first();
        abort_unless($dt, 404, "Data po untuk popk {$popk} tidak ditemukan.");

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

        // $lines -- line R+Q yang stsbar-nya null/0 SAJA -- dipakai KHUSUS
        // dropdown di modal Add/Edit (input manual, jadi line yang sudah
        // pakai barcoding tidak boleh dipilih di sini). TETAP SAMA, tidak
        // diubah.
        $lines = $usedLinepks->isNotEmpty()
            ? DB::connection('mysql_polibag')->table('line')
                ->whereIn('linepk', $usedLinepks)
                ->where(function ($q) {
                    $q->whereNull('stsbar')->orWhere('stsbar', 0);
                })
                ->orderBy('linenm')
                ->get()
            : collect();

        // ============================================================
        // GANTI TOTAL: $filterLines -- SEBELUMNYA diambil dari output
        // jnspk [2,6] (kriteria BEDA dari yang benar-benar ditampilkan di
        // tabel Detail Data Transfer to Finishing), sekarang diambil
        // LANGSUNG dari 2 SUMBER DATA ASLI yang dipakai detailList():
        //   1) tfpb (baris manual)          -> linepk milik moppk ini
        //   2) output dengan jnspk = 10     -> baris barcode (BUKAN [2,6])
        // supaya dropdown filter PERSIS mencerminkan line yang benar-benar
        // ada di data tabel, dari kedua sumber sekaligus.
        // ============================================================
        $linepksFromManual = $fin->table('tfpb')
            ->where('moppk', $mop->moppk)
            ->whereNotNull('linepk')
            ->where('linepk', '>', 0)
            ->distinct()
            ->pluck('linepk');

        $linepksFromBarcode = DB::connection('mysql_polibag')->table('output')
            ->leftJoin('po', 'po.popk', '=', 'output.popk')
            ->where('po.moppk', $mop->moppk)
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
            ['mop' => $mop, 'dt' => $dt, 'popk' => $popk, 'lines' => $lines, 'filterLines' => $filterLines],
            $breakdown
        ));
    }


    // GANTI breakdownSummary() -- SEBELUMNYA:
    //
    //     $breakdown = $this->getBreakdownDataFinishing($mop->moppk);
    //
    // JADI (resolve mif/connection dulu, baru tambah $popk dan $connection):

    public function breakdownSummary($popk, Request $request)
    {
        $fin = DB::connection('mysql_finance_mif');
        $mop = $fin->table('mop')->where('popk', $popk)->first();
        abort_unless($mop, 404);
    
        $mifGuess   = str_contains((string) $mop->noop, 'MIF1') ? 1 : 2;
        $connection = $this->resolveConnection($mifGuess);
    
        // FIX: butuh $dt->material untuk filter output.material yang benar.
        $dt = DB::connection($connection)->table('po')->where('popk', $popk)->first(['material']);
        abort_unless($dt, 404);
    
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

        // BARU: arah sort tanggal dari dropdown Terbaru/Terlama.
        // 'desc' (default) = Terbaru dulu, 'asc' = Terlama dulu.
        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        $fin = DB::connection('mysql_finance_mif');

        $mop = $fin->table('mop')->where('popk', $popk)->first();
        abort_unless($mop, 404);

        [$sizes, $canonicalMap,] = $this->getDedupedSizes($mop->moppk);

        $normalize = fn($v) => mb_strtoupper(trim((string) $v));
        $ukuranToMopdtpk = $sizes->pluck('mopdtpk', 'ukuran')
            ->mapWithKeys(fn($mopdtpk, $ukuran) => [$normalize($ukuran) => $mopdtpk])
            ->all();

        // ---- Baris MANUAL (tfpb) -- bisa diedit/dihapus ----
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

        // ---- Baris BARCODE (output jnspk=10) -- read-only, diagregasi per
        //      tanggal, size di-map via prefix match (findMopdtpkBySizeText). ----
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

        // ============================================================
        // GANTI: gabungkan SEMUA baris (manual + barcode) dulu jadi SATU
        // koleksi, baru di-sort BERSAMA sesuai $sortDir -- sebelumnya
        // manual & barcode di-sort TERPISAH lalu di-concat begitu saja,
        // yang berarti urutan globalnya SALAH (semua manual selalu duluan
        // sebelum barcode, walau tanggal barcode lebih baru/lama).
        // Sekarang keduanya benar-benar tercampur berdasarkan tanggal.
        // ============================================================
        $allRows = $manualRows->concat($barcodeRows)
            ->sortBy(
                fn($r) => \Illuminate\Support\Carbon::parse($r->tanggal)->format('Y-m-d'),
                SORT_REGULAR,
                $sortDir === 'desc' // descending kalau true
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
        $mop  = $fin->table('mop')->where('popk', $popk)->first();
        abort_unless($mop, 404, "Data mop untuk popk {$popk} tidak ditemukan.");
        
        $tfpbpkEditing = $request->filled('tfpbpk') ? (int) $request->tfpbpk : null;
        $sizesInput    = $request->input('sizes', []);
        
        [$sizes,, $mopdtpksByUkuran] = $this->getDedupedSizes($mop->moppk);
        $mopdtpkToUkuran = $sizes->pluck('ukuran', 'mopdtpk')->all();
        
        // resolve connection & sibling popks (warna sama) dulu --
        // SAMA pola dengan getBreakdownDataFinishing()/addRQToRowsViaBreakdown().
        $mifGuess     = str_contains((string) $mop->noop, 'MIF1') ? 1 : 2;
        $connection   = $this->resolveConnection($mifGuess);
        
        $poRowForMaterial = DB::connection($connection)->table('po')->where('popk', $popk)->first(['material']);
        $currentMaterial  = $poRowForMaterial ? (string) $poRowForMaterial->material : '';
        
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

        // ---- Validasi PER SIZE: Barcode + Manual (TFPB lain) + yang mau
        //      diinput sekarang TIDAK BOLEH melebihi R+Q size tersebut. ----
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

        // Resolve Line -- SAMA seperti native: substring linenm sesuai mif.
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
                    $qtyValue = (isset($s['qty']) && $s['qty'] !== null && $s['qty'] !== '')
                        ? $s['qty']
                        : null;

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
                // PK manual (MAX+1) -- tabel tfpb tidak pakai AUTO_INCREMENT.
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
                    $qtyValue = (isset($s['qty']) && $s['qty'] !== null && $s['qty'] !== '')
                        ? $s['qty']
                        : null;

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

    private function addRQToRowsViaBreakdown($rows, string $connection): void
    {
        if ($rows->isEmpty()) {
            return;
        }
        $fin = DB::connection('mysql_finance_mif');
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
    
            $mop = $fin->table('mop')->where('popk', $r->popk)->first(['moppk']);
            if (!$mop) {
                $r->transfer = 0;
                continue;
            }
            [$sizes,,] = $this->getDedupedSizes($mop->moppk);
            $mopdtpkToUkuran = $sizes->pluck('ukuran', 'mopdtpk')->all();
    
            // FIX: kirim $r->material -- INI yang menentukan filter warna yang
            // BENAR (dari kolom output.material), bukan dari kecocokan popk.
            [$rqQty,] = $this->getRqBarcodePerSize($siblingPopks, $mopdtpkToUkuran, (string) $r->material);
            $r->transfer = (int) array_sum($rqQty);
        }
    }

    private function getSiblingPopksByColor(string $connection, int $popk): array
    {
        $db = DB::connection($connection);

        $current = $db->table('po')->where('popk', $popk)->first(['ordpk', 'material', 'OP', 'POno']);
        if (!$current) {
            return [$popk];
        }

        return $db->table('po')
            ->where('ordpk', $current->ordpk)
            ->where('OP', $current->OP)
            ->where('POno', $current->POno)
            ->where('material', $current->material)
            ->pluck('popk')
            ->values()
            ->all();
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

    public function outputByOp(Request $request)
    {
        $op   = $request->get('op');
        $pono = $request->get('pono');

        if (!$op) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter OP wajib diisi.',
            ], 422);
        }

        if (!$pono) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter POno wajib diisi.',
            ], 422);
        }

        $rows = DB::connection('mysql_andon')->select("
        SELECT
            po.OP,
            po.POno,
            o.material,
            o.size,
            SUM(o.jmlpcs) AS qty

        FROM po

        INNER JOIN status s
            ON s.OP = po.OP
            AND s.popk = po.popk

        INNER JOIN output o
            ON o.statuspk = s.statuspk

        WHERE po.OP = ?
          AND po.POno = ?
          AND o.jnspk IN (2, 6)

        GROUP BY
            po.OP,
            po.POno,
            o.material,
            o.size

        ORDER BY
            o.material,
            o.size
        ", [$op, $pono]);

        /*
        |--------------------------------------------------------------------------
        | Ambil semua size yang ditemukan
        |--------------------------------------------------------------------------
        */
        $sizes = collect($rows)
            ->pluck('size')
            ->filter()
            ->unique()
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Pivot:
        | material -> size -> qty
        |--------------------------------------------------------------------------
        */
        $result = [];

        foreach ($rows as $row) {

            $material = $row->material;

            if (!isset($result[$material])) {

                $result[$material] = [
                    'OP'       => $row->OP,
                    'POno'     => $row->POno,
                    'material' => $material,
                    'sizes'    => [],
                    'total'    => 0,
                ];

                foreach ($sizes as $size) {
                    $result[$material]['sizes'][$size] = 0;
                }
            }

            $qty = (float) $row->qty;

            $result[$material]['sizes'][$row->size] += $qty;

            $result[$material]['total'] += $qty;
        }

        return response()->json([
            'success' => true,
            'op'      => $op,
            'pono'    => $pono,
            'sizes'   => $sizes,
            'data'    => array_values($result),
        ]);
    }

    public function debugSizeFormat(Request $request)
    {
        $moppk = $request->query('moppk');

        if (!$moppk) {
            return response()->json(['error' => 'Parameter ?moppk=... wajib diisi.'], 422);
        }

        $fin = DB::connection('mysql_finance_mif');

        // 1. Semua ukuran + qty order di mopdt
        $mopdt = $fin->table('mopdt')
            ->where('moppk', $moppk)
            ->orderBy('mopdtpk')
            ->get(['mopdtpk', 'ukuran', 'qty']);

        // 2. R+Q (jnspk 2,6) -- GROUP BY size, TANPA limit, tampilkan SEMUA
        //    variasi size + total jmlpcs + jumlah baris per size.
        $rq = DB::connection('mysql_polibag')->table('output')
            ->leftJoin('po', 'po.popk', '=', 'output.popk')
            ->where('po.moppk', $moppk)
            ->where('output.linepk', '>', 0)
            ->whereIn('output.jnspk', [2, 6])
            ->groupBy('output.size', 'output.jnspk')
            ->selectRaw('output.size, output.jnspk, COUNT(*) as jml_baris, SUM(output.jmlpcs) as total_jmlpcs')
            ->orderBy('output.size')
            ->get();

        // 3. Barcode TF (jnspk 10) -- GROUP BY size, TANPA limit.
        $barcode = DB::connection('mysql_polibag')->table('output')
            ->leftJoin('po', 'po.popk', '=', 'output.popk')
            ->where('po.moppk', $moppk)
            ->where('output.linepk', '>', 0)
            ->where('output.jnspk', 10)
            ->groupBy('output.size')
            ->selectRaw('output.size, COUNT(*) as jml_baris, SUM(output.jmlpcs) as total_jmlpcs')
            ->orderBy('output.size')
            ->get();

        // 4. BONUS: cek juga statuspk (kalau ada) -- kadang barcode/RQ dibedakan
        //    juga oleh kolom lain yang belum kita perhitungkan.
        $rqDistinctSizes = $rq->pluck('size')->unique()->values();
        $barcodeDistinctSizes = $barcode->pluck('size')->unique()->values();

        return response()->json([
            'moppk'                    => $moppk,
            'mopdt_ukuran'              => $mopdt,
            'rq_grouped_by_size'        => $rq,
            'barcode_grouped_by_size'   => $barcode,
            'rq_distinct_size_texts'    => $rqDistinctSizes,
            'barcode_distinct_size_texts' => $barcodeDistinctSizes,
        ]);
    }
}
