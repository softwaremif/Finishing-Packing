<?php

namespace App\Http\Controllers\Packing;

use App\Http\Controllers\Controller;
use App\Services\OrderImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackingController extends Controller
{
    // ===============  HALAMAN UTAMA/INDEX PACKING LIST ===========

    public function index()
    {
        $gabung = DB::table('gabung')
            ->select('gabungpk', 'keterangan')
            ->orderBy('gabungpk')
            ->get();
        return view('menu.packing.index', compact('gabung'));
    }

    // Daftar Data OP index.blade.php.
    public function getList(Request $request)
    {
        $page   = max(1, (int) $request->page);
        $rows   = max(1, (int) $request->rows);
        $offset = ($page - 1) * $rows;
        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        // Query cukup 'mysql' SAJA, TANPA filter po.mif -- tampil ke SEMUA
        // user identik, tidak ada lagi pembedaan super user vs user biasa.
        $combined = $this->fetchAll('mysql', $request);

        // Ambil URL gambar order dari mysql_gis (+ fallback mysql_sample).
        $this->addOrderImageToRows($combined);

        // Agregasi per PO+OP, lalu validasi ULANG semua filter aktif
        // (safety-net -- lihat applyPostAggregationFilters()).
        $aggregated = $this->applyPostAggregationFilters(
            $this->aggregateByPoOp($combined),
            $request
        )->values();

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

        $this->addCtnBreakdownToRows($data);

        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
            unset($row->_popks); // field internal, tidak perlu dikirim ke frontend
        }

        return response()->json([
            'total' => $total,
            'rows'  => $data,
        ]);
    }

    // Ambil URL foto order per baris (jenis foto tergantung stsfoto: 1=GIS,
    // 2=Production, lainnya=fallback foto sample terbaru). No-image pakai
    // asset LOKAL aplikasi ini. SAMA PERSIS logic dengan TF Finishing/Polibag.
    private function addOrderImageToRows($rows): void
    {
        app(OrderImageService::class)->attachToRows($rows, 'ordpk', 'order_image');
    }

    // Penjumlahan nilai kolom QTY, Packing /Pcs, CTN per PO+OP.
    private function aggregateByPoOp($collection)
    {
        return $collection
            ->groupBy(fn ($row) => ($row->mif ?? '') . '|' . $row->POno . '|' . $row->OP . '|' . ($row->poref ?? ''))
            ->map(function ($group) {
                $representative = clone $group->sortByDesc('popk')->first();
                $representative->qty                 = (int) $group->sum('qty');
                $representative->packing_qty_plan    = (int) $group->sum('packing_qty_plan');
                $representative->packing_qty         = (int) $group->sum('packing_qty');
                $representative->ctn                 = (int) $group->sum('ctn');
                $representative->packing_ctn         = (int) $group->sum('packing_ctn');
                $representative->packing_qty_balance = $representative->packing_qty - $representative->packing_qty_plan;
                $representative->ctn_balance         = $representative->packing_ctn - $representative->ctn;

                $representative->_popks = $group->pluck('popk')->unique()->values()->all();

                // Status pembuatan Packing List (Plan) -- Pending (belum
                // ada plan sama sekali), Partial (plan sudah dibuat SEBAGIAN,
                // belum menutupi Order Qty), Complete (plan >= Order Qty).
                $representative->packing_plan_status = $this->resolvePackingPlanStatus(
                    $representative->qty,
                    $representative->packing_qty_plan
                );

                return $representative;
            })
            ->values();
    }

    // Tentukan status pembuatan Packing List (Plan) berdasarkan Plan Qty
    // vs Order Qty.
    private function resolvePackingPlanStatus($qty, $planQty): string
    {
        $qty     = (float) $qty;
        $planQty = (float) $planQty;

        if ($planQty <= 0) {
            return 'pending';
        }
        if ($planQty < $qty) {
            return 'partial';
        }
        return 'complete';
    }

    // Status 1 baris pack (1 combo Color/Sec Size dalam 1 carton).
    private function getCtnRowStatus($row): string
    {
        if ((int) ($row->segel ?? 0) === 1) {
            return 'sealed';
        }

        $hasAnyPlan = true;
        $allMatch = true;
        $foundPlan = false;

        for ($i = 1; $i <= 40; $i++) {
            $plan = (float) ($row->{"qtyp{$i}"} ?? 0);
            if ($plan <= 0) continue;

            $foundPlan = true;
            $actual = (float) ($row->{"qty{$i}"} ?? 0);
            if ($actual !== $plan) {
                $allMatch = false;
                break;
            }
        }

        if ($foundPlan && $allMatch) {
            return 'complete';
        }

        if ((float) ($row->pcs ?? 0) > 0) {
            return 'packing';
        }

        return 'planned';
    }

    // Status gabungan 1 carton FISIK (bisa terdiri dari beberapa baris pack
    // kalau Mixed).
    private function getCtnGroupStatus($cartonRows): string
    {
        $statuses = $cartonRows->map(fn ($r) => $this->getCtnRowStatus($r));

        if ($statuses->contains('sealed')) {
            return 'sealed';
        }
        if ($statuses->isNotEmpty() && $statuses->every(fn ($s) => $s === 'complete')) {
            return 'complete';
        }
        if ($statuses->contains(fn ($s) => $s === 'packing' || $s === 'complete')) {
            return 'packing';
        }

        return 'planned';
    }

    // Hitung breakdown jumlah carton per status (plan/partial/full/sealed)
    // untuk tiap baris hasil agregasi PO+OP -- dipakai kartu ringkasan di index.
    private function addCtnBreakdownToRows($rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        foreach ($rows as $r) {
            $r->ctn_plan_count    = 0;
            $r->ctn_partial_count = 0;
            $r->ctn_full_count    = 0;
            $r->ctn_sealed_count  = 0;
        }

        // GANTI TOTAL -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        // SEBELUMNYA baris dikelompokkan per 'mif' dulu, lalu tiap grup
        // query ke koneksi hasil resolveConnection($mif) -- itu BENAR
        // selama getList() masih gabung mysql_andon+mysql. SEKARANG
        // getList() HANYA PERNAH mengambil baris dari 'mysql', jadi popk
        // yang diterima method ini SELALU fisik ada di 'mysql', APAPUN
        // nilai po.mif-nya (mif sekarang murni atribut data, BUKAN lagi
        // penanda host). Sebagai bonus, ini juga jadi SATU query untuk
        // semua baris, bukan N query terpisah per grup mif seperti
        // sebelumnya.
        $db = DB::connection('mysql');

        $allPopks = $rows
            ->flatMap(fn ($r) => $r->_popks ?? [$r->popk])
            ->unique()
            ->values()
            ->all();

        if (empty($allPopks)) {
            return;
        }

        $packRows = $db->table('pack')->whereIn('popk', $allPopks)->get();
        $packRowsByPopk = $packRows->groupBy('popk');

        foreach ($rows as $r) {
            $popksForGroup = $r->_popks ?? [$r->popk];

            $groupPackRows = collect();
            foreach ($popksForGroup as $pk) {
                $groupPackRows = $groupPackRows->concat($packRowsByPopk->get($pk, collect()));
            }

            $byCarton = $groupPackRows->groupBy('carton');

            $r->ctn_plan_count = $byCarton->count(); // total carton yang SUDAH diplanningkan

            foreach ($byCarton as $cartonRows) {
                $status = $this->getCtnGroupStatus($cartonRows);

                if ($status === 'packing') {
                    $r->ctn_partial_count++;
                } elseif ($status === 'complete') {
                    $r->ctn_full_count++;
                } elseif ($status === 'sealed') {
                    $r->ctn_sealed_count++;
                }
                // 'planned' -- tidak masuk hitungan lain, cuma masuk total Plan (ctn_plan_count).
            }
        }
    }

    // Get All data list.
    private function fetchAll(string $connection, Request $request, ?string $po = null, ?string $op = null, ?string $poref = null)
    {
        $db = DB::connection($connection);
        $isDetailCall = $op !== null;

        $packing = $db->table('pack')
            ->selectRaw('popk, SUM(pcs) as packing_qty')
            ->groupBy('popk');
        $packingPlan = $db->table('pack')
            ->selectRaw('popk, SUM(pcsp) as packing_qty_plan')
            ->groupBy('popk');
        $packingCtn = $db->table('pack')
            ->selectRaw("popk, SUM(jmlpcs) as packing_ctn")
            ->where('status', '>=', 4)
            ->groupBy('popk');

        $query = $db->table('po')
            ->leftJoinSub($packing, 'pk', fn($join) => $join->on('po.popk', '=', 'pk.popk'))
            ->leftJoinSub($packingPlan, 'pkp', fn($join) => $join->on('po.popk', '=', 'pkp.popk'))
            ->leftJoinSub($packingCtn, 'pctn', fn($join) => $join->on('po.popk', '=', 'pctn.popk'))
            ->where('po.qty', '>', 0)
            ->where('po.OP', '<>', '');

            $selectFields = "po.popk, po.ordpk, po.sts, po.gabung, po.shipdate1, po.shipdate2,
            po.customer, po.season, po.POno, po.OP, po.poref, po.mif, po.GAC,
            po.buyer, po.style, po.qty, po.silhouette, po.ctn AS ctn,
            COALESCE(pkp.packing_qty_plan,0) AS packing_qty_plan,
            COALESCE(pk.packing_qty,0)       AS packing_qty,
            (COALESCE(pk.packing_qty,0) - COALESCE(pkp.packing_qty_plan,0)) AS packing_qty_balance,
            COALESCE(pctn.packing_ctn,0) AS packing_ctn,
            (COALESCE(pctn.packing_ctn,0) - po.ctn) AS ctn_balance
        ";

        if ($isDetailCall) {
            $transfer = $db->table('bj')
                ->selectRaw('popk, SUM(pcs) as transfer')
                ->where('check2', 0)
                ->groupBy('popk');
            $checked = $db->table('bj')
                ->selectRaw('popk, SUM(pcs) as checked_qty')
                ->where('check2', 1)
                ->groupBy('popk');
            $packStatus = $db->table('pack')
                ->selectRaw('popk, MAX(status) as status')
                ->groupBy('popk');
            $segel = $db->table('pack')
                ->selectRaw("
                popk,
                MAX(CASE WHEN part = '10' THEN 1 ELSE 0 END) as segel_complete,
                GROUP_CONCAT(
                    DISTINCT CASE WHEN part <> '10' THEN part END
                    ORDER BY CAST(part AS UNSIGNED)
                    SEPARATOR ', '
                ) as segel_partial_no
            ")
                ->where('status', 5)
                ->groupBy('popk');
            $lineInfo = $db->table('bj')
                ->leftJoin('line', 'line.linepk', '=', 'bj.linepk')
                ->selectRaw("
                bj.popk,
                GROUP_CONCAT(
                    DISTINCT TRIM(SUBSTRING(line.linenm,6,3))
                    ORDER BY line.linenm
                    SEPARATOR ';'
                ) AS linenm
            ")
                ->groupBy('bj.popk');

            $query
                ->leftJoinSub($transfer, 'trf', fn($join) => $join->on('po.popk', '=', 'trf.popk'))
                ->leftJoinSub($checked, 'chk', fn($join) => $join->on('po.popk', '=', 'chk.popk'))
                ->leftJoinSub($packStatus, 'pst', fn($join) => $join->on('po.popk', '=', 'pst.popk'))
                ->leftJoinSub($segel, 'sgl', fn($join) => $join->on('po.popk', '=', 'sgl.popk'))
                ->leftJoinSub($lineInfo, 'li', fn($join) => $join->on('po.popk', '=', 'li.popk'));

            $selectFields .= ",
            COALESCE(trf.transfer,0) AS transfer,
            COALESCE(chk.checked_qty,0) AS checked_qty,
            COALESCE(pst.status, 0)           AS status,
            COALESCE(sgl.segel_complete, 0)   AS segel_complete,
            sgl.segel_partial_no              AS segel_partial_no,
            (
                COALESCE(trf.transfer,0)
                - COALESCE(chk.checked_qty,0)
                - COALESCE(pk.packing_qty,0)
            ) AS balance,
            po.material,
            po.secsz,
            li.linenm
        ";
        }

        if ($po !== null) {
            $query->where('po.POno', $po);
        }
        if ($op !== null) {
            $query->where('po.OP', $op);
            if ($po !== null && $po !== '') {
                $query->where('po.POno', $po);
            } else {
                $query->where(function ($q) {
                    $q->whereNull('po.POno')->orWhere('po.POno', '');
                });
            }
            if ($poref !== null && $poref !== '') {
                $query->where('po.poref', $poref);
            } else {
                $query->where(function ($q) {
                    $q->whereNull('po.poref')->orWhere('po.poref', '');
                });
            }
        }

        $query->selectRaw($selectFields);

        if ($request->fin) {
            $query->having('packing_qty', '>', 0);
        }

        $this->applyListFilters($query, $request);

        return $query->orderBy('po.popk')->get();
    }

    // Filter di halaman index -- termasuk Ex Factory (po.GAC).
    private function applyListFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('po.POno', 'like', "%{$search}%")
                    ->orWhere('po.OP', 'like', "%{$search}%")
                    ->orWhere('po.customer', 'like', "%{$search}%")
                    ->orWhere('po.season', 'like', "%{$search}%")
                    ->orWhere('po.style', 'like', "%{$search}%")
                    ->orWhere('po.material', 'like', "%{$search}%")
                    ->orWhere('po.buyer', 'like', "%{$search}%")
                    ->orWhere('po.silhouette', 'like', "%{$search}%")
                    ->orWhere('po.secsz', 'like', "%{$search}%")
                    ->orWhere('po.poref', 'like', "%{$search}%");
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
    }

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

    // Normalisasi GAC (Ex Factory) jadi UNIX timestamp -- AMAN apa pun
    // format aslinya, SAMA PERSIS logic dengan TF Finishing/Polibag.
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

    // Safety-net: validasi ULANG setiap filter aktif (year, ex_factory,
    // buyer, packing_plan_status) terhadap hasil akhir yang SUDAH
    // diagregasi per PO+OP.
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
            $rows = $rows->filter(fn ($r) => (string) $r->buyer === (string) $buyer);
        }

        // Filter Status Packing List (Pending/Partial/Complete).
        if ($request->filled('packing_plan_status')) {
            $status = $request->packing_plan_status;
            $rows = $rows->filter(fn ($r) => $r->packing_plan_status === $status);
        }

        return $rows->values();
    }

    // Catat histori perubahan Actual Qty per carton (delta lama vs baru)
    // ke tabel 'actpack' -- dipakai historyActual() utk menampilkan riwayat.
    private function insertActpackHistory(int $packpk, ?object $oldRow, array $newQty): void
    {
        $deltaForHistory = [];
        $deltaPcs = 0;

        for ($i = 1; $i <= 25; $i++) {
            $oldVal = (int) ($oldRow->{"qty{$i}"} ?? 0);
            $newVal = (int) ($newQty[$i] ?? 0);
            $delta  = $newVal - $oldVal;

            if ($delta != 0) {
                $deltaForHistory["qty{$i}"] = $delta;
                $deltaPcs += $delta;
            }
        }

        if (!empty($deltaForHistory)) {
            DB::table('actpack')->insert(array_merge(
                [
                    'packpk'   => $packpk,
                    'tglinput' => now(),
                    'pcs'      => $deltaPcs,
                ],
                $deltaForHistory
            ));
        }
    }

    // Naikkan angka di ekor No Carton/Barcode sejumlah $offset, jaga lebar
    // digit asli (padding nol) -- dipakai Auto Split & Urutkan CTN.
    private function incrementCartonNumber(string $carton, int $offset): string
    {
        if ($offset === 0) {
            return $carton;
        }

        if (preg_match('/^(.*?)(\d+)$/', $carton, $m)) {
            $prefix = $m[1];
            $digits = $m[2];
            $width  = strlen($digits);
            $next   = (int) $digits + $offset;

            return $prefix . str_pad((string) $next, $width, '0', STR_PAD_LEFT);
        }

        return $carton . $offset;
    }

    // Riwayat perubahan Actual Qty 1 carton (dari 'actpack') -- dipakai
    // tombol "History" di card carton.
    public function historyActual($packpk)
    {
        $rows = DB::table('actpack')
            ->where('packpk', $packpk)
            ->orderByDesc('tglinput')
            ->orderByDesc('actpk')
            ->get();

        return response()->json([
            'total' => $rows->count(),
            'rows'  => $rows,
        ]);
    }

    // ===============  HALAMAN INPUT Packing List
    // Render pertama kali halaman Input Packing List Global -- breakdown
    // Color/Sec Size, ringkasan carton, dropdown Line, config tampilan.
    public function inputPackingGlobal(Request $request)
    {
        $request->validate([
            'po'    => 'nullable',
            'op'    => 'required',
            'poref' => 'nullable',
            'mif'   => 'nullable',
        ]);
        $po    = $request->input('po');
        $op    = $request->input('op');
        $poref = $request->input('poref');
        $mif   = $request->input('mif', session('pos'));

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql' -- popk
        // yang diakses dari sini SELALU fisik ada di 'mysql' (index sudah
        // cuma pernah menampilkan baris dari situ). $mif TETAP diteruskan
        // ke getBreakdownDataGlobal() untuk filter BISNIS (->where('mif', ..))
        // yang TIDAK diubah -- itu soal pengelompokan data, bukan pemilihan host.
        $connection = 'mysql';
        $db = DB::connection($connection);
        $breakdown = $this->getBreakdownDataGlobal($po, $op, $poref, (int) $mif, $connection);
        $dt          = $breakdown['dt'];
        $dt2         = $breakdown['dt2'];
        $activeSizes = $breakdown['activeSizes'];
        $groups      = $breakdown['groups'];
        $poNoList    = $breakdown['poNoList'];
        $allPopks    = $breakdown['allPopks'];
        $aggQty = $breakdown['aggQty'];
        $orderQty = $aggQty['orderQty'];
        $readyQty = $aggQty['readyQty'];
        $planQty  = $aggQty['planQty'];
        $transQty = $aggQty['transQty'];
        $popks = collect($groups)->flatMap(fn($g) => $g['popks'])->values();
        $totalCarton  = $breakdown['ctnSummary']['ctnPlan'];
        $sealedCarton = $db->table('pack')
            ->whereIn('popk', $popks)
            ->get()
            ->groupBy('carton')
            ->filter(fn($rowsInCarton) => $rowsInCarton->every(fn($r) => (int) $r->segel === 1))
            ->count();
        $openCarton = $totalCarton - $sealedCarton;
        $totalColors = collect($groups)->pluck('material')->filter()->unique()->count();
        $colorList   = collect($groups)->pluck('material')->filter()->unique()->values();
        $gabungList = $db->table('gabung')->orderBy('gabungpk')->get();
        $lines = $db->table('line')
            ->where('mif', $mif)
            ->whereNull('stsbar')
            ->orderBy('linenm')
            ->get();
        $secszList = collect($groups)->pluck('secsz')->filter()->unique()->values();
        $colorSecszCombos = $this->buildColorSecszCombos($db, $groups, $activeSizes);

        $existingData = [
            'po'           => $po,
            'op'           => $op,
            'poref'        => $poref,
            'mif'          => $mif,
            'connection'   => $connection,
            'dt'           => $dt,
            'dt2'          => $dt2,
            'activeSizes'  => $activeSizes,
            'groups'       => $groups,
            'orderQty'     => $orderQty,
            'readyQty'     => $readyQty,
            'planQty'      => $planQty,
            'transQty'     => $transQty,
            'popks'        => $popks,
            'poNoList'     => $poNoList,
            'allPopks'     => $allPopks,
            'totalCarton'  => $totalCarton,
            'sealedCarton' => $sealedCarton,
            'openCarton'   => $openCarton,
            'totalColors'  => $totalColors,
            'gabungList'   => $gabungList,
            'lines'        => $lines,
            'colorList'    => $colorList,
            'secszList'    => $secszList,
            'colorSecszCombos' => $colorSecszCombos,
        ];

        return view('menu.shared.packing-input-global', array_merge($existingData, [
            'pageConfig' => [
                'mode'                => 'packing',
                'pageTitlePrefix'     => 'Packing list',
                'guserpkSegel'        => [38],
                'showPlanningChips'   => true,
                'showShipmentChips'   => false,
                'showSizeFilter'      => true,
                'showPartFilter'      => false,
                'showAddPacking'      => true,
                'showCtnManagement'   => true,
                'showScanNobar'       => false,
                'showShipmentPlan'    => false,
                'showShipmentActions' => false,
                'backRouteName'       => 'packing.index',
                'routes' => [
                    'back'                   => route('packing.index'),
                    'listDetailGlobal'       => route('packing.list.detail.global'),
                    'breakdownSummaryGlobal' => route('packing.breakdownSummaryGlobal'),
                    'cardsInfoGlobal'        => route('packing.cardsInfoGlobal'),
                    'headerInfoGlobal'       => route('packing.headerInfoGlobal'),
                    'combosGlobal'           => route('packing.combosGlobal'),
                    'updateCtn'              => route('packing.update-ctn'),
                    'partSummaryGlobal'      => route('finish-good-stuffing.partSummaryGlobal'),
                    'distinctDimensiCtn'     => route('packing.distinct-dimensi-ctn'),
                    'bulkUpdateDimensiCtn'   => route('packing.bulk-update-dimensi-ctn'),
                    'crossPoOpLookup'    => route('packing.crossPoOpLookup'),
                    // 'crossPoComboLookup' => route('packing.crossPoComboLookup'),
                    'crossPoComboInfo'    => route('packing.crossPoComboInfo'),
                    'crossPoCartonList'   => route('packing.crossPoCartonList'),
                    'bundleOpLookup'     => route('packing.bundleOpLookup'),
                    'bundleCartonList'   => route('packing.bundleCartonList'),
                    'storeCartonBundle'  => route('packing.storeCartonBundle'),
                    'bundleDetail' => route('packing.bundleDetail'),
                    'headerPartial'          => 'menu.packing.partials.header_info_global',
                    'cardsInfoPartial'       => 'menu.packing.partials.cards_info_global',
                    'breakdownPartial'       => 'menu.packing.partials.breakdown_summary_global',
                    'modalEditInfoPacking'   => 'menu.packing.modal-edit-info-packing-global',
                    'modalPacking'           => 'menu.packing.modal-packing-global',
                    'modalActualCtn'         => 'menu.packing.modal-actual-ctn-global',
                    'modalDeleteActualCtn'   => 'menu.packing.modal-delete-actual-ctn-global',
                    'modalDeleteCtn'         => 'menu.packing.modal-delete-ctn-global',
                    'modalCopyCtn'           => 'menu.packing.modal-copy-ctn-global',
                    'modalUrutkanCtn'        => 'menu.packing.modal-urutkan-ctn-global',
                    'modalSegelCtn'          => 'menu.packing.modal-segel-ctn-global',
                    'modalBulkDimensiCtn'    => 'menu.packing.modal-bulk-dimensi-ctn',
                ],
            ],
        ]));
    }

    // Simpan info header PO (shipdate/sap/wh/ket) -- dipakai form info
    // singkat di header halaman Input Packing List Global.
    public function saveHeaderGlobal(Request $request)
    {
        $validated = $request->validate([
            'popks'   => 'required|array|min:1',
            'popks.*' => 'integer',
            'ship1'   => 'nullable|date',
            'sap1'    => 'nullable|string',
            'sap2'    => 'nullable|string',
            'wh'      => 'nullable|string',
            'ket'     => 'nullable|string',
        ]);

        $popks = $validated['popks'];

        $existingCount = DB::table('po')->whereIn('popk', $popks)->count();
        if ($existingCount === 0) {
            return response()->json([
                'icon'  => 'error',
                'title' => 'Data PO tidak ditemukan',
            ], 404);
        }

        $updateData = [
            'shipdate1' => $validated['ship1'] ?: null,
            'sap1'      => $validated['sap1'] ?? null,
            'sap2'      => $validated['sap2'] ?? null,
            'wh'        => $validated['wh'] ?? null,
            'ket'       => $validated['ket'] ?? null,
        ];

        DB::table('po')->whereIn('popk', $popks)->update($updateData);

        return response()->json([
            'icon'  => 'success',
            'title' => 'Informasi PO berhasil disimpan.',
            'data'  => $updateData,
        ]);
    }

    // Reload AJAX bagian header (nama PO/OP/buyer/dst) di halaman Input
    // Packing List Global.
    public function headerInfoGlobal(Request $request)
    {
        $po    = $request->query('po');
        $op    = $request->query('op');
        $poref = $request->query('poref');
        $mif   = (int) $request->query('mif', session('pos'));

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        $connection = 'mysql';

        $breakdown = $this->getBreakdownDataGlobal($po, $op, $poref, $mif, $connection);

        return view('menu.packing.partials.header_info_global', [
            'dt2'       => $breakdown['dt2'],
            'poNoList'  => $breakdown['poNoList'],
            'allPopks'  => $breakdown['allPopks'],
            'colorList' => collect($breakdown['groups'])->pluck('material')->filter()->unique()->values(),
        ]);
    }

    // Helper TUNGGAL untuk breakdown Color/Sec Size + Coverage Matrix + CTN
    // Summary -- dipakai inputPackingGlobal(), headerInfoGlobal(),
    // breakdownSummaryGlobal(), cardsInfoGlobal(), combosGlobal(). Filter
    // 'mif' DI SINI TETAP dipertahankan (->where('mif', $mif)) -- ini
    // logika BISNIS pengelompokan data per unit/factory, BUKAN pemilihan
    // host database, jadi TIDAK diubah oleh perbaikan connection ini.
    private function getBreakdownDataGlobal(?string $po, string $op, ?string $poref, int $mif, string $connection): array
    {
        $db = DB::connection($connection);

        $poRows = $db->table('po')
            ->where('OP', $op)
            ->where('mif', $mif)
            ->when(
                $po !== null && $po !== '',
                fn($q) => $q->where('POno', $po),
                fn($q) => $q->where(function ($qq) {
                    $qq->whereNull('POno')->orWhere('POno', '');
                })
            )
            ->when(
                $poref !== null && $poref !== '',
                fn($q) => $q->where('poref', $poref),
                fn($q) => $q->where(function ($qq) {
                    $qq->whereNull('poref')->orWhere('poref', '');
                })
            )
            ->get();

        if ($poRows->isEmpty()) {
            abort(404);
        }

        $dt  = $poRows->first();
        $dt2 = $dt;

        $activeSizes = [];
        foreach ($poRows as $poRow) {
            for ($i = 1; $i <= 40; $i++) {
                $sz = $poRow->{"size$i"} ?? null;
                if (!empty($sz) && !isset($activeSizes[$i])) {
                    $activeSizes[$i] = $sz;
                }
            }
        }
        ksort($activeSizes);

        // Reverse-map label size (teks) -> index i -- dibutuhkan karena
        // tabel `output` menyimpan size sebagai TEKS ("M", "L", dst), bukan
        // kolom qty1..40 seperti `bj`. Dipakai untuk menjumlahkan
        // output.jmlpcs ke index qty yang benar di $summary.
        $sizeLabelToIndex = array_flip($activeSizes);

        $sumQtyExpr  = collect(range(1, 40))->map(fn($i) => "SUM(qty$i) as qty$i")->implode(', ');
        $sumQtyPExpr = collect(range(1, 40))->map(fn($i) => "SUM(qtyp$i) as qtyp$i")->implode(', ');

        $groupedPopks = $poRows->groupBy(function ($row) {
            return $row->customer . '|' . $row->material . '|' . $row->secsz;
        });

        $groups = collect();

        foreach ($groupedPopks as $groupRows) {
            $popksInGroup = $groupRows->pluck('popk');
            $repRow       = $groupRows->first();

            $dt2Sum = $db->table('po')
                ->selectRaw("SUM(qty) as qty, {$sumQtyExpr}")
                ->whereIn('popk', $popksInGroup)
                ->first();

            $dt3 = $db->table('pack')
                ->selectRaw("SUM(pcs) as pcs, SUM(jmlpcs) as pack, SUM(pcsp) as pcsp, {$sumQtyExpr}, {$sumQtyPExpr}")
                ->whereIn('popk', $popksInGroup)
                ->first();

            $summary = $db->table('bj')
                ->selectRaw("SUM(pcs) as pcs, {$sumQtyExpr}")
                ->whereIn('popk', $popksInGroup)
                ->first();

            // Tambahkan SUM(output.jmlpcs) ke $summary -- Polibag di
            // Coverage Matrix mencakup bj + output, sama seperti modul Transfer.
            $outputSizeSums = DB::connection('mysql_polibag')
                ->table('output')
                ->whereIn('popk', $popksInGroup)
                ->where('jnspk', 4)
                ->select('size')
                ->selectRaw('SUM(jmlpcs) as total')
                ->groupBy('size')
                ->get();

            foreach ($outputSizeSums as $osRow) {
                $idx = $sizeLabelToIndex[$osRow->size] ?? null;
                if ($idx !== null) {
                    $qtyField = "qty{$idx}";
                    $summary->{$qtyField} = (int) ($summary->{$qtyField} ?? 0) + (int) $osRow->total;
                }
            }
            $summary->pcs = (int) ($summary->pcs ?? 0) + (int) $outputSizeSums->sum('total');

            $orderQty     = [];
            $readyQty     = [];
            $planQty      = [];
            $transQty     = [];
            $diffTransQty = [];
            $diffPackQty  = [];

            foreach ($activeSizes as $i => $sz) {
                $orderQty[$i]     = $dt2Sum->{"qty$i"} ?? 0;
                $readyQty[$i]     = $dt3->{"qty$i"} ?? 0;
                $planQty[$i]      = $dt3->{"qtyp$i"} ?? 0;
                $transQty[$i]     = $summary->{"qty$i"} ?? 0; // sudah termasuk output
                $diffTransQty[$i] = $transQty[$i] - $orderQty[$i];
                $diffPackQty[$i]  = $readyQty[$i] - $planQty[$i];
            }

            $totOrder     = array_sum($orderQty);
            $totDiffTrans = ($summary->pcs ?? 0) - $totOrder;
            $totDiffPack  = ($dt3->pcs ?? 0) - ($dt3->pcsp ?? 0);

            $tctnp      = (int) $groupRows->sum('ctn');
            $tctna      = (int) ($dt3->pack ?? 0);
            $balanceCtn = $tctna - $tctnp;

            $groups->push([
                'customer'     => $repRow->customer,
                'material'     => $repRow->material,
                'secsz'        => $repRow->secsz,
                'popks'        => $popksInGroup,
                'dt3'          => $dt3,
                'summary'      => $summary,
                'orderQty'     => $orderQty,
                'readyQty'     => $readyQty,
                'planQty'      => $planQty,
                'transQty'     => $transQty,
                'diffTransQty' => $diffTransQty,
                'diffPackQty'  => $diffPackQty,
                'totOrder'     => $totOrder,
                'totDiffTrans' => $totDiffTrans,
                'totDiffPack'  => $totDiffPack,
                'tctnp'        => $tctnp,
                'tctna'        => $tctna,
                'balanceCtn'   => $balanceCtn,
            ]);
        }

        // Agregat GLOBAL -- dijumlah lintas SEMUA grup. $transQtyAgg
        // OTOMATIS sudah termasuk output, karena diambil dari
        // $group['transQty'] yang sudah termasuk output di atas.
        $orderQtyAgg     = [];
        $readyQtyAgg     = [];
        $planQtyAgg      = [];
        $transQtyAgg     = [];
        $diffTransQtyAgg = [];
        $diffPackQtyAgg  = [];

        foreach ($activeSizes as $i => $sz) {
            $orderQtyAgg[$i] = 0;
            $readyQtyAgg[$i] = 0;
            $planQtyAgg[$i]  = 0;
            $transQtyAgg[$i] = 0;

            foreach ($groups as $group) {
                $orderQtyAgg[$i] += $group['orderQty'][$i] ?? 0;
                $readyQtyAgg[$i] += $group['readyQty'][$i] ?? 0;
                $planQtyAgg[$i]  += $group['planQty'][$i]  ?? 0;
                $transQtyAgg[$i] += $group['transQty'][$i] ?? 0;
            }

            $diffTransQtyAgg[$i] = $transQtyAgg[$i] - $orderQtyAgg[$i];
            $diffPackQtyAgg[$i]  = $readyQtyAgg[$i] - $planQtyAgg[$i];
        }

        $aggQty = [
            'orderQty'     => $orderQtyAgg,
            'readyQty'     => $readyQtyAgg,
            'planQty'      => $planQtyAgg,
            'transQty'     => $transQtyAgg,
            'diffTransQty' => $diffTransQtyAgg,
            'diffPackQty'  => $diffPackQtyAgg,
            'totOrder'     => array_sum($orderQtyAgg),
            'totTrans'     => array_sum($transQtyAgg),
            'totPlan'      => array_sum($planQtyAgg),
            'totReady'     => array_sum($readyQtyAgg),
            'totDiffTrans' => array_sum($transQtyAgg) - array_sum($orderQtyAgg),
            'totDiffPack'  => array_sum($readyQtyAgg) - array_sum($planQtyAgg),
        ];

        $allPopkIdsForCtn = $groups->flatMap(fn($g) => $g['popks'])->values();

        $packRowsForCtnSummary = $db->table('pack')
            ->whereIn('popk', $allPopkIdsForCtn)
            ->get();

        $cartonGroupsForCtnSummary = $packRowsForCtnSummary->groupBy('carton');

        $ctnPlanTotal = $cartonGroupsForCtnSummary->count();

        $ctnActualTotal = $cartonGroupsForCtnSummary->filter(function ($rowsInCarton) {
            $status = $this->getPackGroupStatus($rowsInCarton);
            return $status === 'sealed';
        })->count();

        $ctnSummary = [
            'ctnPlan'    => $ctnPlanTotal,
            'ctnActual'  => $ctnActualTotal, // = jumlah carton yang SUDAH Segel
            'ctnBalance' => $ctnActualTotal - $ctnPlanTotal, // Segel - Plan
        ];

        $poNoList = $poRows->pluck('POno')->filter(fn($v) => $v !== null && $v !== '')->unique()->values();
        $allPopks = $poRows->pluck('popk')->values();

        return compact('dt', 'dt2', 'activeSizes', 'groups', 'aggQty', 'ctnSummary', 'poNoList', 'allPopks');
    }

    // Reload AJAX Breakdown Summary (Coverage Matrix + CTN Summary) di
    // halaman Input Packing List Global.
    public function breakdownSummaryGlobal(Request $request)
    {
        $po    = $request->query('po');
        $op    = $request->query('op');
        $poref = $request->query('poref');
        $mif   = (int) $request->query('mif', session('pos'));

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        $connection = 'mysql';

        $breakdown = $this->getBreakdownDataGlobal($po, $op, $poref, $mif, $connection);

        return view('menu.packing.partials.breakdown_summary_global', $breakdown);
    }

    private function isPackRowComplete($row): bool
    {
        $hasAnyPlan = false;

        for ($i = 1; $i <= 40; $i++) {
            $plan = (int) ($row->{"qtyp{$i}"} ?? 0);
            if ($plan <= 0) continue; // size tanpa Plan diabaikan

            $hasAnyPlan = true;
            $actual = (int) ($row->{"qty{$i}"} ?? 0);
            if ($actual !== $plan) return false;
        }

        return $hasAnyPlan;
    }

    private function getPackRowStatus($row): string
    {
        if ((int) ($row->segel ?? 0) === 1) return 'sealed';
        if ($this->isPackRowComplete($row)) return 'complete';
        if ((int) ($row->pcs ?? 0) > 0) return 'packing';
        return 'planned';
    }

    private function getPackGroupStatus($groupRows): string
    {
        $statuses = [];
        foreach ($groupRows as $row) {
            $statuses[] = $this->getPackRowStatus($row);
        }

        if (in_array('sealed', $statuses, true)) return 'sealed';

        $allComplete = true;
        foreach ($statuses as $s) {
            if ($s !== 'complete') {
                $allComplete = false;
                break;
            }
        }
        if ($allComplete) return 'complete';

        foreach ($statuses as $s) {
            if ($s === 'packing' || $s === 'complete') return 'packing';
        }

        return 'planned';
    }

    // Data tabel Detail Packing/Carton di halaman Input Packing List Global
    // -- support filter size/color/secsz/part/status/search, paginasi PER
    // CARTON (bukan per baris pack).
    public function listDetailGlobal(Request $request)
    {
        $request->validate([
            'po'    => 'nullable',
            'op'    => 'required',
            'poref' => 'nullable',
            'mif'   => 'nullable',
        ]);

        $po    = $request->query('po');
        $op    = $request->query('op');
        $poref = $request->query('poref');
        $mif   = (int) $request->query('mif', session('pos'));

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        $connection = 'mysql';
        $db = DB::connection($connection);

        $popks = $db->table('po')
            ->where('OP', $op)->where('mif', $mif)
            ->when($po !== null && $po !== '', fn($q)=>$q->where('POno',$po), fn($q)=>$q->where(fn($qq)=>$qq->whereNull('POno')->orWhere('POno','')))
            ->when($poref !== null && $poref !== '', fn($q)=>$q->where('poref',$poref), fn($q)=>$q->where(fn($qq)=>$qq->whereNull('poref')->orWhere('poref','')))
            ->pluck('popk');

        if ($popks->isEmpty()) {
            return response()->json(['total' => 0, 'total_carton' => 0, 'rows' => []]);
        }

        $nativePackpks = $db->table('pack')->whereIn('popk', $popks)->pluck('packpk');
 
        $mixnosInvolved = $db->table('pack')
            ->whereIn('packpk', $nativePackpks)
            ->whereNotNull('mixno')
            ->distinct()
            ->pluck('mixno');
        
        $crossPoPackpks = collect();
        if ($mixnosInvolved->isNotEmpty()) {
            $crossPoPackpks = $db->table('pack')
                ->whereIn('mixno', $mixnosInvolved)
                ->whereNotIn('packpk', $nativePackpks)
                ->pluck('packpk');
        }

        $allRelevantPackpks = $nativePackpks->merge($crossPoPackpks)->unique()->values();

        if ($crossPoPackpks->isNotEmpty()) {
            $crossPoPopks = $db->table('pack')->whereIn('packpk', $crossPoPackpks)->pluck('popk')->unique();
            $popks = $popks->merge($crossPoPopks)->unique()->values();
        }

        $cr     = $request->cr;
        $size   = $request->size;
        $color  = $request->color;
        $secsz  = $request->secsz;
        $part   = $request->part;
        $page   = max(1, (int) $request->input('page', 1));
        $rowsPerPage = max(1, (int) $request->input('rows', 50));
        $search = trim($request->search ?? '');

        $shipRows = $db->table('pack')
            ->whereIn('popk', $popks)
            ->get(['packpk', 'popk', 'part', 'status', 'fca']);
        
        $shipInfoByPackpk = [];
        foreach ($shipRows as $sr) {
            $shipInfoByPackpk[$sr->packpk] = [
                'shipped'   => in_array((int) $sr->status, [6, 7], true),
                'inspect'   => (int) $sr->fca === 1,
                'returning' => (int) $sr->fca === 2,
                'popk'      => $sr->popk,
                'part'      => $sr->part,
            ];
        }

        $shipDateColumns = collect(range(1, 10))->map(fn($i) => "ship{$i}")->all();

        $poShipDateRows = $db->table('po')
            ->whereIn('popk', $popks)
            ->get(array_merge(['popk'], $shipDateColumns));

        $shipDateByPopkPart = [];
        foreach ($poShipDateRows as $pr) {
            for ($i = 1; $i <= 10; $i++) {
                $shipDateByPopkPart[$pr->popk][$i] = $pr->{"ship{$i}"};
            }
        }

        $matchingCartonQuery = $db->table('pack')
            ->whereIn('packpk', $allRelevantPackpks)
            ->when($cr, fn($q) => $q->where('carton', $cr))
            ->when($size, fn($q) => $q->where("qtyp{$size}", '>', 0))
            ->when($color, fn($q) => $q->where('material', $color))
            ->when($secsz, fn($q) => $q->where('secsz', $secsz))
            ->when($part, fn($q) => $q->where('part', $part))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($x) use ($search) {
                    $x->where('nobar', 'like', "%{$search}%")
                        ->orWhere('carton', 'like', "%{$search}%");
                });
            });
        
        $hasAnyFilter = $cr || $size || $color || $secsz || $part || $search;

        $baseQuery = $db->table('pack')->whereIn('packpk', $allRelevantPackpks);

        if ($hasAnyFilter) {
            $matchingCartons = $matchingCartonQuery->pluck('carton')->unique()->values();
            $baseQuery->whereIn('carton', $matchingCartons->isNotEmpty() ? $matchingCartons : ['__NONE__']);
        }

        $allMatchingRows = (clone $baseQuery)->get();

        foreach ($allMatchingRows as $r) {
            $shipInfo = $shipInfoByPackpk[$r->packpk] ?? null;
            $r->ship_shipped = $shipInfo['shipped'] ?? false;
            $r->ship_inspect = $shipInfo['inspect'] ?? false;
            $r->ship_returning = $shipInfo['returning'] ?? false;
        }

        $rowsByCartonForStatus = $allMatchingRows->groupBy('carton');

        $cartonStatusMap     = [];
        $cartonShipStatusMap = [];
        foreach ($rowsByCartonForStatus as $cartonKey => $groupRows) {
            $cartonStatusMap[$cartonKey] = $this->getPackGroupStatus($groupRows);

            $anyShipped   = $groupRows->contains(fn ($r) => $r->ship_shipped === true);
            $anyReturning = $groupRows->contains(fn ($r) => $r->ship_returning === true);
            $anyInspect   = $groupRows->contains(fn ($r) => $r->ship_inspect === true);

            $cartonShipStatusMap[$cartonKey] = $anyShipped ? 'shipped'
                : ($anyReturning ? 'returning'
                : ($anyInspect ? 'inspect' : null));
        }

        $statusCounts = [
            'all'      => count($cartonStatusMap),
            'planned'  => count(array_filter($cartonStatusMap, fn($s) => $s === 'planned')),
            'packing'  => count(array_filter($cartonStatusMap, fn($s) => $s === 'packing')),
            'complete' => count(array_filter($cartonStatusMap, fn($s) => $s === 'complete')),
            'sealed'   => count(array_filter($cartonStatusMap, fn($s) => $s === 'sealed')),
            'shipped'  => count(array_filter($cartonShipStatusMap, fn($s) => $s === 'shipped')),
            'inspect'  => count(array_filter($cartonShipStatusMap, fn($s) => $s === 'inspect')),
            'returning' => count(array_filter($cartonShipStatusMap, fn($s) => $s === 'returning')),
        ];

        $status = $request->input('status');

        $finalQuery = clone $baseQuery;
        if ($status !== '' && $status !== null) {
            if (in_array($status, ['shipped', 'inspect'], true)) {
                $matchingCartons = array_keys(array_filter($cartonShipStatusMap, fn($s) => $s === $status));
            } else {
                $matchingCartons = array_keys(array_filter($cartonStatusMap, fn($s) => $s === $status));
            }
            $finalQuery->whereIn('carton', !empty($matchingCartons) ? $matchingCartons : ['__NONE__']);
        }

        // Paginasi per CARTON, bukan per baris. Ambil SEMUA baris yang
        // lolos filter (tanpa limit), kelompokkan per carton, urutkan
        // CARTON-nya (bukan barisnya), baru slice sesuai halaman.
        $allFinalRows = $finalQuery->get();
        $rowsByCarton = $allFinalRows->groupBy('carton');

        $sort = $request->input('sort');
        $sortMap = [
            'carton_asc'  => ['carton', 'asc'],
            'carton_desc' => ['carton', 'desc'],
            'nobar_asc'   => ['nobar', 'asc'],
            'nobar_desc'  => ['nobar', 'desc'],
        ];

        $representativeByCarton = $rowsByCarton->map(function ($group) {
            return $group->sortByDesc(fn($r) => [$r->urut ?? 0, $r->packpk])->first();
        });

        $cartonKeys = $rowsByCarton->keys();

        if (isset($sortMap[$sort])) {
            [$sortColumn, $sortDir] = $sortMap[$sort];
            $cartonKeys = $cartonKeys->sort(function ($a, $b) use ($representativeByCarton, $sortColumn, $sortDir) {
                $valA = (string) ($representativeByCarton[$a]->{$sortColumn} ?? '');
                $valB = (string) ($representativeByCarton[$b]->{$sortColumn} ?? '');
                $cmp = ($sortColumn === 'carton') ? strnatcmp($valA, $valB) : strcmp($valA, $valB);
                return $sortDir === 'desc' ? -$cmp : $cmp;
            })->values();
        } else {
            $cartonKeys = $cartonKeys->sort(function ($a, $b) use ($representativeByCarton) {
                $valA = (string) ($representativeByCarton[$a]->carton ?? '');
                $valB = (string) ($representativeByCarton[$b]->carton ?? '');
                return strnatcmp($valA, $valB);
            })->values();
        }

        $totalCarton = $cartonKeys->count();

        $pageCartonKeys = $cartonKeys->slice(($page - 1) * $rowsPerPage, $rowsPerPage)->values();

        $data = collect();
        foreach ($pageCartonKeys as $ck) {
            foreach ($rowsByCarton[$ck] as $row) {
                $data->push($row);
            }
        }
        $data = $data->values();

        $distinctCreators = $data->pluck('created_by')->filter()->unique()->values();
        $posByUserpk = [];
        if ($distinctCreators->isNotEmpty()) {
            $userRows = DB::connection('mysql_akses')->table('user')
                ->whereIn('userpk', $distinctCreators)
                ->get(['userpk', 'pos']);
            foreach ($userRows as $ur) {
                $posByUserpk[$ur->userpk] = $ur->pos;
            }
        }
        
        $currentUserpk = session('userpk');
        $currentPos    = session('pos');
        
        foreach ($data as $row) {
            if (empty($row->created_by)) {
                // Data LAMA (belum punya created_by) -- tetap boleh diedit,
                // TIDAK dikunci oleh aturan baru ini.
                $row->can_edit = false;
                continue;
            }
        
            $ownerPos = $posByUserpk[$row->created_by] ?? null;
            $row->can_edit = ((int) $row->created_by === (int) $currentUserpk)
                && ((string) $ownerPos === (string) $currentPos);
        }

        $bundlepksInvolved = $data->pluck('bundlepk')->filter()->unique()->values();
        $bundleInfoMap = [];
        if ($bundlepksInvolved->isNotEmpty()) {
            $bundleRows = $db->table('carton_bundle')->whereIn('bundlepk', $bundlepksInvolved)->get();
            foreach ($bundleRows as $br) {
                $bundleInfoMap[$br->bundlepk] = $br->bundle_carton;
            }
        }
        foreach ($data as $row) {
            $row->bundle_carton = $bundleInfoMap[$row->bundlepk] ?? null;
        }

        foreach ($data as $index => $row) {
            $row->no      = $index + 1;
            $row->balance = ($row->pcs ?? 0) - ($row->pcsp ?? 0);

            $shipInfo = $shipInfoByPackpk[$row->packpk] ?? null;

            $row->ship_shipped = $shipInfo['shipped'] ?? false;
            $row->ship_inspect = $shipInfo['inspect'] ?? false;
            $row->ship_returning = $shipInfo['returning'] ?? false;

            $shipPopk = $shipInfo['popk'] ?? $row->popk;
            $shipPart = $shipInfo['part'] ?? $row->part;
            $row->ship_date = $shipDateByPopkPart[$shipPopk][$shipPart] ?? null;
        }

        return response()->json([
            'total'         => $totalCarton,
            'total_carton'  => $totalCarton,
            'status_counts' => $statusCounts,
            'rows'          => $data,
        ]);
    }

        // Proses tambah/edit carton dari modal "Add Packing" di halaman Input
        // Packing List Global -- termasuk mode Auto Split.
    public function storeGlobal(Request $request)
    {
        $po       = $request->input('po');
        $op       = $request->input('op');
        $poref    = $request->input('poref');
        $mif      = (int) $request->input('mif', session('pos'));
        $editMode = (int) $request->input('edit_mode', 0) === 1;
        $useAutoSplit = (int) $request->input('check', 0) === 1;

        $originalPackpks = collect($request->input('original_packpks', []))
            ->filter()
            ->map(fn($v) => (int) $v)
            ->values();

        // Connection SELALU 'mysql'.
        $connection = 'mysql';
        $db = DB::connection($connection);

        $nocar     = trim((string) $request->input('nocar'));
        $nobar     = trim((string) $request->input('nobar'));
        $breakdown = $request->input('breakdown', []);
        $panjang = $request->input('panjang');
        $lebar   = $request->input('lebar');
        $tinggi  = $request->input('tinggi');

        if ($nocar === '') {
            return response()->json(['icon' => 'warning', 'title' => 'No Carton wajib diisi.'], 422);
        }

        $breakdown = array_values(array_filter(
            $breakdown,
            fn($b) =>
            (int) ($b['plan'] ?? 0) > 0 || (int) ($b['actual'] ?? 0) > 0
        ));

        if (empty($breakdown)) {
            return response()->json(['icon' => 'warning', 'title' => 'Minimal satu Qty harus diisi.'], 422);
        }

        $editingPackpks = collect($breakdown)->pluck('packpk')->filter()->unique()->values();
        $removedPackpks = $originalPackpks->diff($editingPackpks)->values();

        DB::connection($connection)->beginTransaction();

        try {
            $excludedPackpks = $originalPackpks->isNotEmpty() ? $originalPackpks : $editingPackpks;

            $targetPopksInBreakdown = collect($breakdown)->pluck('target_popk')->filter()->unique()->values();

            $touchedPoOpPairs = $targetPopksInBreakdown->isNotEmpty()
                ? $db->table('po')->whereIn('popk', $targetPopksInBreakdown)->get(['POno', 'OP'])
                    ->unique(fn ($r) => $r->POno . '|' . $r->OP)
                    ->values()
                : collect();

            // Fallback -- payload lama yang belum kirim target_popk sama sekali,
            // pakai PO/OP halaman seperti sebelumnya (perilaku LAMA tetap jalan).
            if ($touchedPoOpPairs->isEmpty()) {
                $touchedPoOpPairs = collect([(object) ['POno' => $po, 'OP' => $op]]);
            }

            $currentPartByPoOp = [];
            foreach ($touchedPoOpPairs as $pair) {
                $key = $pair->POno . '|' . $pair->OP;
            
                if ($editMode && $originalPackpks->isNotEmpty()) {
                    // Ambil part dari baris ASLI carton ini yang MEMANG ada di PO/OP
                    // ini secara spesifik -- bisa null kalau carton ini belum ada
                    // baris di PO/OP tsb SEBELUMNYA (mis. baru ditambahkan lewat mix).
                    $currentPartByPoOp[$key] = $db->table('pack')
                        ->whereIn('packpk', $originalPackpks)
                        ->where('POno', $pair->POno)
                        ->where('OP', $pair->OP)
                        ->value('part');
                } else {
                    // Carton baru (bukan edit) -- part SELALU null saat dibuat.
                    $currentPartByPoOp[$key] = null;
                }
            }

            $checkDuplicate = function (string $column, string $value) use ($db, $excludedPackpks, $touchedPoOpPairs, $currentPartByPoOp) {
                foreach ($touchedPoOpPairs as $pair) {
                    $key = $pair->POno . '|' . $pair->OP;
                    $currentPartForThisPair = $currentPartByPoOp[$key] ?? null;
            
                    $query = $db->table('pack')
                        ->where($column, $value)
                        ->where('POno', $pair->POno)
                        ->where('OP', $pair->OP);
            
                    if ($excludedPackpks->isNotEmpty()) {
                        $query->whereNotIn('packpk', $excludedPackpks);
                    }
            
                    foreach ($query->get() as $row) {
                        // BLOCK kalau part SAMA (bukan beda).
                        if ((string) $row->part === (string) $currentPartForThisPair) {
                            return $row;
                        }
                    }
                }
                return null;
            };
            
            $dupCarton = $checkDuplicate('carton', $nocar);
            if ($dupCarton) {
                DB::connection($connection)->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => "No Carton <b>{$nocar}</b> sudah digunakan pada PO/OP <b>{$dupCarton->POno} - {$dupCarton->OP}</b> dengan Session/Part yang sama.",
                ], 422);
            }
            
            if ($nobar !== '') {
                $dupNobar = $checkDuplicate('nobar', $nobar);
                if ($dupNobar) {
                    DB::connection($connection)->rollBack();
                    return response()->json([
                        'icon'  => 'warning',
                        'title' => "No Barcode <b>{$nobar}</b> sudah digunakan pada PO/OP <b>{$dupNobar->POno} - {$dupNobar->OP}</b> dengan Session/Part yang sama.",
                    ], 422);
                }
            }

            $isCrossPoMix = $touchedPoOpPairs->count() > 1;
            $mixGroupId = null;

            if ($isCrossPoMix) {
                $existingMixRow = $db->table('pack')->where('carton', $nocar)->whereNotNull('mixno')->first();
                $mixGroupId = $existingMixRow->mixno ?? ((int) $db->table('pack')->max('mixno') + 1);
            }

            $bundlepkCandidates = collect();

            if ($editingPackpks->isNotEmpty()) {
                $bundlepkCandidates = $db->table('pack')
                    ->whereIn('packpk', $editingPackpks)
                    ->pluck('bundlepk')
                    ->filter()
                    ->unique();
            }

            $existingCartonBundlepks = $db->table('pack')
                ->where('carton', $nocar)
                ->when($excludedPackpks->isNotEmpty(), fn ($q) => $q->whereNotIn('packpk', $excludedPackpks))
                ->pluck('bundlepk')
                ->filter()
                ->unique();
            
            $bundlepkCandidates = $bundlepkCandidates->merge($existingCartonBundlepks)->unique()->values();
            
            if ($bundlepkCandidates->count() > 1) {
                DB::connection($connection)->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Carton yang digabung/diedit ternyata masing-masing SUDAH tergabung Carton Besar (Bundle) yang BERBEDA. Lepaskan dulu dari Bundle sebelumnya sebelum digabung di sini.',
                ], 422);
            }
            
            $resolvedBundlepk = $bundlepkCandidates->first();

            // GANTI -- FIX UTAMA: hapus HANYA dari 'pack' -- tabel 'ship' TIDAK
            // LAGI dipakai sama sekali di alur ini (kolom-kolomnya identik
            // dengan 'pack', jadi 'pack' sekarang SATU-SATUNYA sumber kebenaran).
            if ($removedPackpks->isNotEmpty()) {
                $removedRows = $db->table('pack')->whereIn('packpk', $removedPackpks)->get();
                foreach ($removedRows as $removedRow) {
                    $db->table('po')->where('popk', $removedRow->popk)->decrement('ctn');
                }
                $db->table('pack')->whereIn('packpk', $removedPackpks)->delete();
            }

            $grouped = collect($breakdown)->groupBy(
                fn($b) => ($b['material'] ?? '') . '|' . ($b['secsz'] ?? '') . '|' . ($b['target_popk'] ?? '')
            );
            
            $isSingleComboSingleSize = $grouped->count() === 1 && $grouped->first()->count() === 1;

            if (!$editMode && $useAutoSplit && $isSingleComboSingleSize) {
                $line      = $grouped->first()->first();
                $material  = $line['material'] ?? null;
                $secsz     = $line['secsz'] ?? null;
                $sizeIdx   = (int) $line['size'];
                $singleQty = (int) ($line['plan'] ?? 0);

                $targetPopk = (int) ($line['target_popk'] ?? 0);

                if ($targetPopk > 0) {
                    $poRow = $db->table('po')->where('popk', $targetPopk)->first();
                } else {
                    $poRow = $db->table('po')
                        ->where('OP', $op)
                        ->where('mif', $mif)
                        ->where('material', $material)
                        ->when(
                            $secsz !== null && $secsz !== '',
                            fn($q) => $q->where('secsz', $secsz),
                            fn($q) => $q->where(function ($qq) {
                                $qq->whereNull('secsz')->orWhere('secsz', '');
                            })
                        )
                        ->when($po !== null && $po !== '', fn($q) => $q->where('POno', $po))
                        ->when($poref !== null && $poref !== '', fn($q) => $q->where('poref', $poref))
                        ->first();
                }

                if (!$poRow) {
                    DB::connection($connection)->rollBack();
                    return response()->json(['icon' => 'warning', 'title' => 'Kombinasi Color/Sec Size tidak ditemukan pada PO+OP ini.'], 422);
                }

                $db->table('po')->where('popk', $poRow->popk)->update(['gabung' => 7]);

                $qtyField   = "qty{$sizeIdx}";
                $dtpoQtyCol = $db->table('po')->where('popk', $poRow->popk)->value($qtyField) ?? 0;

                if ($dtpoQtyCol <= 0 || $singleQty <= 0) {
                    DB::connection($connection)->rollBack();
                    return response()->json(['icon' => 'warning', 'title' => 'Order Qty untuk size ini tidak valid, Auto Split tidak dapat dijalankan.'], 422);
                }

                $planQtyField    = "qtyp{$sizeIdx}";
                $existingPlanQty = (int) $db->table('pack')
                    ->where('popk', $poRow->popk)
                    ->sum($planQtyField);

                $availableQty = $dtpoQtyCol - $existingPlanQty;

                if ($availableQty <= 0) {
                    DB::connection($connection)->rollBack();
                    $sizeName = $poRow->{"size{$sizeIdx}"} ?? "Size {$sizeIdx}";
                    return response()->json([
                        'icon'  => 'warning',
                        'title' => "Order Qty untuk size <b>{$sizeName}</b> SUDAH TERPAKAI SEPENUHNYA oleh carton lain "
                            . "(Plan yang sudah ada: <b>{$existingPlanQty}</b> dari Order <b>{$dtpoQtyCol}</b>). "
                            . "Tidak ada sisa untuk Auto Split carton baru.",
                    ], 422);
                }

                $bagi  = $availableQty / $singleQty;
                $kali  = intdiv($availableQty, $singleQty);
                $sisa  = $availableQty % $singleQty;
                $kali4 = $sisa > 0 ? 1 : 0;
                $totalToGenerate = $kali + $kali4;

                $usedCartons = $db->table('pack')
                    ->where('POno', $poRow->POno)->where('OP', $poRow->OP)
                    ->pluck('carton')->filter()->flip()->all();
                $usedNobars = $nobar !== ''
                    ? $db->table('pack')
                        ->where('POno', $poRow->POno)->where('OP', $poRow->OP)
                        ->pluck('nobar')->filter()->flip()->all()
                    : [];

                $generatedRows = [];
                $cartonOffset = 0;
                $nobarOffset  = 0;
                $safetyLimit  = 10000;

                for ($n = 0; $n < $totalToGenerate; $n++) {
                    $guard = 0;
                    do {
                        $candidateCarton = $this->incrementCartonNumber($nocar, $cartonOffset);
                        $cartonOffset++;
                        $guard++;
                    } while (isset($usedCartons[$candidateCarton]) && $guard < $safetyLimit);
                    $usedCartons[$candidateCarton] = true;

                    $candidateNobar = '';
                    if ($nobar !== '') {
                        $guard = 0;
                        do {
                            $candidateNobar = $this->incrementCartonNumber($nobar, $nobarOffset);
                            $nobarOffset++;
                            $guard++;
                        } while (isset($usedNobars[$candidateNobar]) && $guard < $safetyLimit);
                        $usedNobars[$candidateNobar] = true;
                    }

                    $generatedRows[] = [
                        'carton' => $candidateCarton,
                        'nobar'  => $candidateNobar,
                        'qty'    => ($n < $kali) ? $singleQty : $sisa,
                    ];
                }

                $generatedCartons = array_column($generatedRows, 'carton');
                $generatedNobars  = array_filter(array_column($generatedRows, 'nobar'));
                $dupCartonSplit = $db->table('pack')->where('POno', $poRow->POno)->where('OP', $poRow->OP)
                    ->whereIn('carton', $generatedCartons)->first();
                if ($dupCartonSplit) {
                    DB::connection($connection)->rollBack();
                    return response()->json(['icon' => 'warning', 'title' => "No Carton <b>{$dupCartonSplit->carton}</b> (hasil Auto Split) sudah digunakan pada PO/OP ini -- kemungkinan ada carton lain yang baru dibuat bersamaan, silakan coba lagi."], 422);
                }
                if (!empty($generatedNobars)) {
                    $dupNobarSplit = $db->table('pack')->where('POno', $poRow->POno)->where('OP', $poRow->OP)
                        ->whereIn('nobar', $generatedNobars)->first();
                    if ($dupNobarSplit) {
                        DB::connection($connection)->rollBack();
                        return response()->json(['icon' => 'warning', 'title' => "No Barcode <b>{$dupNobarSplit->nobar}</b> (hasil Auto Split) sudah digunakan pada PO/OP ini -- kemungkinan ada carton lain yang baru dibuat bersamaan, silakan coba lagi."], 422);
                    }
                }

                foreach ($generatedRows as $gen) {
                    $row = [
                        'carton' => $gen['carton'],
                        'nobar' => $gen['nobar'],
                        'OP' => $poRow->OP,
                        'POno' => $poRow->POno,
                        'popk' => $poRow->popk,
                        'customer' => $poRow->customer,
                        'material' => $poRow->material,
                        'nw' => $request->nw,
                        'gw' => $request->gw,
                        'panjang' => $panjang,
                        'lebar'   => $lebar,
                        'tinggi'  => $tinggi,
                        'secsz' => $poRow->secsz,
                        'keterangan' => $request->ket2,
                        'pcsp' => $gen['qty'],
                        'tanggal' => now(),
                        'waktu' => now(),
                        'status' => 4,
                        'created_by' => session('userpk'),
                    ];
                    for ($i = 1; $i <= 40; $i++) {
                        $row["qtyp$i"] = ($i === $sizeIdx) ? $gen['qty'] : null;
                    }
                    $db->table('pack')->insert($row);
                }

                $newCtn = ($poRow->ctn ?? 0) + $kali + $kali4;
                $db->table('po')->where('popk', $poRow->popk)->update(['ctn' => $newCtn]);

                DB::connection($connection)->commit();

                return response()->json([
                    'icon'  => 'success',
                    'title' => "Auto Split berhasil -- " . count($generatedRows) . " carton dibuat untuk {$material}" . ($secsz ? " - {$secsz}" : '') . ".",
                ]);
            }

            $insertedRows = 0;
            $updatedRows  = 0;
            $skippedCombos = [];

            foreach ($grouped as $lines) {
                $material   = $lines->first()['material'] ?? null;
                $secsz      = $lines->first()['secsz'] ?? null;
                $targetPopk = (int) ($lines->first()['target_popk'] ?? 0);
            
                $distinctPackpks = $lines->pluck('packpk')->filter()->map(fn ($v) => (int) $v)->unique()->values();
                $packpk = (int) ($distinctPackpks->first() ?? 0);
                $extraPackpksToMerge = $distinctPackpks->slice(1)->values();
            
                if ($extraPackpksToMerge->isNotEmpty()) {
                    $extraRows = $db->table('pack')->whereIn('packpk', $extraPackpksToMerge)->get();
                    foreach ($extraRows as $extraRow) {
                        $db->table('po')->where('popk', $extraRow->popk)->decrement('ctn');
                    }
                    $db->table('pack')->whereIn('packpk', $extraPackpksToMerge)->delete();
                }
            
                $qtyp = array_fill(1, 40, null);
                $qty  = array_fill(1, 40, null);
                $totalPlanGroup   = 0;
                $totalActualGroup = 0;
            
                foreach ($lines as $line) {
                    $idx       = (int) $line['size'];
                    $planVal   = (int) ($line['plan'] ?? 0);
                    $actualVal = (int) ($line['actual'] ?? 0);
            
                    if ($actualVal > $planVal) {
                        DB::connection($connection)->rollBack();
                        return response()->json([
                            'icon'  => 'warning',
                            'title' => "Qty Actual tidak boleh melebihi Qty Plan pada carton ini.",
                        ], 422);
                    }
            
                    $qtyp[$idx] = (int) ($qtyp[$idx] ?? 0) + $planVal;
                    $qty[$idx]  = (int) ($qty[$idx] ?? 0) + $actualVal;
                    $totalPlanGroup   += $planVal;
                    $totalActualGroup += $actualVal;
                }
            
                foreach ($qtyp as $i => $v) { if ($v === 0) $qtyp[$i] = null; }
                foreach ($qty as $i => $v)  { if ($v === 0) $qty[$i]  = null; }
            
                if ($targetPopk > 0) {
                    $poRow = $db->table('po')->where('popk', $targetPopk)->first();
                } else {
                    $poRow = $db->table('po')
                        ->where('OP', $op)
                        ->where('mif', $mif)
                        ->where('material', $material)
                        ->when(
                            $secsz !== null && $secsz !== '',
                            fn($q) => $q->where('secsz', $secsz),
                            fn($q) => $q->where(function ($qq) {
                                $qq->whereNull('secsz')->orWhere('secsz', '');
                            })
                        )
                        ->when($po !== null && $po !== '', fn($q) => $q->where('POno', $po))
                        ->when($poref !== null && $poref !== '', fn($q) => $q->where('poref', $poref))
                        ->first();
                }
            
                if (!$poRow) {
                    $skippedCombos[] = $secsz ? "{$material} - {$secsz}" : $material;
                    continue;
                }
            
                $db->table('po')->where('popk', $poRow->popk)->update(['gabung' => 7]);
            
                if ($totalActualGroup > 0) {
                    $existingRow = $packpk > 0 ? $db->table('pack')->where('packpk', $packpk)->first() : null;
            
                    for ($i = 1; $i <= 40; $i++) {
                        $actualBaru = (int) ($qty[$i] ?? 0);
                        if ($actualBaru <= 0) continue;
            
                        $actualLama = (int) ($existingRow->{"qty{$i}"} ?? 0);
                        $readyTotal = (int) $db->table('pack')->where('popk', $poRow->popk)->sum("qty{$i}");
            
                        $sizeLabel = $poRow->{"size{$i}"} ?? null;
                        $transfer  = $this->getTransferQtyGlobal($db, $poRow->popk, $i, $sizeLabel);
            
                        $totalReady = ($readyTotal - $actualLama) + $actualBaru;
            
                        if ($totalReady > $transfer) {
                            DB::connection($connection)->rollBack();
                            $maksimal = max(0, $transfer - ($readyTotal - $actualLama));
                            return response()->json([
                                'icon'  => 'warning',
                                'title' => "Qty Actual untuk {$material}" . ($secsz ? " - {$secsz}" : '') . " melebihi Transfer. Maksimal yang bisa diinput: <b>{$maksimal}</b>.",
                            ], 422);
                        }
                    }
                }
            
                for ($i = 1; $i <= 40; $i++) {
                    $planBaru = (int) ($qtyp[$i] ?? 0);
                    if ($planBaru <= 0) continue;
            
                    $orderQty = (int) ($poRow->{"qty{$i}"} ?? 0);
            
                    $existingPlanQuery = $db->table('pack')->where('popk', $poRow->popk);
                    if ($packpk > 0) {
                        $existingPlanQuery->where('packpk', '<>', $packpk);
                    }
                    $existingPlanOther = (int) $existingPlanQuery->sum("qtyp{$i}");
            
                    $totalPlan = $existingPlanOther + $planBaru;
            
                    if ($totalPlan > $orderQty) {
                        DB::connection($connection)->rollBack();
                        $sizeName = $poRow->{"size{$i}"} ?? "Size {$i}";
                        $maksimal = max(0, $orderQty - $existingPlanOther);
                        return response()->json([
                            'icon'  => 'warning',
                            'title' => "Qty Plan untuk {$material}" . ($secsz ? " - {$secsz}" : '') . " size <b>{$sizeName}</b> melebihi Order Qty. "
                                . "Order: <b>{$orderQty}</b>, sudah terpakai carton lain: <b>{$existingPlanOther}</b>. "
                                . "Maksimal Plan yang bisa diinput: <b>{$maksimal}</b>.",
                        ], 422);
                    }
                }
            
                $rowData = [
                    'carton'     => $nocar,
                    'nobar'      => $nobar,
                    'OP'         => $poRow->OP,
                    'POno'       => $poRow->POno,
                    'popk'       => $poRow->popk,
                    'customer'   => $poRow->customer,
                    'material'   => $poRow->material,
                    'nw'         => $request->nw,
                    'gw'         => $request->gw,
                    'panjang'    => $panjang,
                    'lebar'      => $lebar,
                    'tinggi'     => $tinggi,
                    'secsz'      => $poRow->secsz,
                    'keterangan' => $request->ket2,
                    'pcsp'       => $totalPlanGroup,
                    'pcs'        => $totalActualGroup > 0 ? $totalActualGroup : null,
                    'tanggal'    => now(),
                    'waktu'      => now(),
                    'status'     => 4,
                    'reject'     => null,
                ];
            
                $rowData['mixno']     = $isCrossPoMix ? $mixGroupId : null;
                $rowData['bundlepk']  = $resolvedBundlepk;  

                foreach ($qtyp as $i => $val) {
                    $rowData["qtyp$i"] = $val;
                }
                foreach ($qty as $i => $val) {
                    $rowData["qty$i"]  = $val;
                }
            
                if ($packpk > 0) {
                    $db->table('pack')->where('packpk', $packpk)->update($rowData);
                    $updatedRows++;
                } else {
                    $rowData['created_by'] = session('userpk');
                    $db->table('pack')->insert($rowData);
                    $db->table('po')->where('popk', $poRow->popk)->increment('ctn');
                    $insertedRows++;
                }
            }

            if ($insertedRows === 0 && $updatedRows === 0 && $removedPackpks->isEmpty()) {
                DB::connection($connection)->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Kombinasi Color/Sec Size yang dipilih tidak ditemukan pada PO+OP ini.',
                ], 422);
            }

            DB::connection($connection)->commit();

            $pesan = $editMode
                ? "Carton {$nocar} berhasil diperbarui ({$updatedRows} kombinasi Color/Sec Size)."
                : "Carton {$nocar} berhasil ditambahkan ({$insertedRows} kombinasi Color/Sec Size).";

            if ($removedPackpks->isNotEmpty()) {
                $pesan .= " {$removedPackpks->count()} kombinasi dihapus dari carton ini.";
            }
            if (!empty($skippedCombos)) {
                $pesan .= ' Dilewati (tidak ditemukan): ' . implode(', ', $skippedCombos) . '.';
            }

            return response()->json([
                'icon'  => empty($skippedCombos) ? 'success' : 'warning',
                'title' => $pesan,
            ]);
        } catch (\Throwable $e) {
            DB::connection($connection)->rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menyimpan data packing.'
            ], 500);
        }
    }

    // Proses simpan Input Actual Massal di halaman input packing list
    // Modal modal-actual-ctn-global.blade.php
    public function updateCtnGlobal(Request $request)
    {
        $request->validate([
            'packpk' => 'required',
        ], [
            'packpk.required' => 'Pilih minimal satu carton.',
        ]);

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        $connection = 'mysql';
        $db = DB::connection($connection);

        DB::connection($connection)->beginTransaction();

        try {
            $size = $request->size;
            $ids  = array_values(array_filter(explode(',', $request->packpk)));

            if (empty($ids)) {
                DB::connection($connection)->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Pilih minimal satu carton.',
                ], 422);
            }

            $packs = $db->table('pack')
                ->where('status', 4)
                ->whereIn('packpk', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('packpk');

            $orderedPacks = collect($ids)
                ->map(fn($id) => $packs->get($id))
                ->filter()
                ->values();

            if ($orderedPacks->isEmpty()) {
                DB::connection($connection)->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Data carton tidak ditemukan atau statusnya tidak valid.',
                ], 422);
            }

            $sizeIndexes = ($size !== '' && $size !== null) ? [(int) $size] : range(1, 40);

            $packsByPopk = $orderedPacks->groupBy('popk');

            $remaining = [];
            $isFilled  = [];

            foreach ($packsByPopk as $popkKey => $packsInPopk) {
                $remaining[$popkKey] = [];
                $isFilled[$popkKey]  = [];

                foreach ($sizeIndexes as $i) {
                    $ready    = (int) $db->table('pack')->where('popk', $popkKey)->sum("qty{$i}");
                    $transfer = $this->getTransferQtyGlobal($db, $popkKey, $i);

                    $isFilled[$popkKey][$i] = [];

                    foreach ($packsInPopk as $x) {
                        $plan  = (int) ($x->{"qtyp{$i}"} ?? 0);
                        $exist = (int) ($x->{"qty{$i}"} ?? 0);

                        if ($plan <= 0) {
                            $isFilled[$popkKey][$i][$x->packpk] = null;
                        } elseif ($exist >= $plan) {
                            $isFilled[$popkKey][$i][$x->packpk] = true;
                        } else {
                            $isFilled[$popkKey][$i][$x->packpk] = false;
                        }
                    }

                    $remaining[$popkKey][$i] = $transfer - $ready;
                }
            }

            $jumlahUpdate  = 0;
            $jumlahParsial = 0;
            $jumlahGagal   = 0;
            $jumlahSkip    = 0;

            foreach ($orderedPacks as $x) {
                $popkKey = $x->popk;
                $upd = [];
                $adaPerubahan  = false;
                $adaPartialRow = false;
                $adaGagalRow   = false;

                foreach ($sizeIndexes as $i) {
                    $plan = (int) ($x->{"qtyp{$i}"} ?? 0);
                    if ($plan <= 0) continue;

                    if ($isFilled[$popkKey][$i][$x->packpk] === true) {
                        continue;
                    }

                    $exist = (int) ($x->{"qty{$i}"} ?? 0);
                    $butuh = $plan - $exist;
                    $avail = max(0, $remaining[$popkKey][$i]);

                    if ($avail >= $butuh) {
                        $upd["qty{$i}"] = $plan;
                        $remaining[$popkKey][$i] -= $butuh;
                        $adaPerubahan = true;
                    } elseif ($avail > 0) {
                        $upd["qty{$i}"] = $exist + $avail;
                        $remaining[$popkKey][$i] -= $avail;
                        $adaPerubahan  = true;
                        $adaPartialRow = true;
                    } else {
                        $adaGagalRow = true;
                    }
                }

                if (!$adaPerubahan) {
                    $jumlahSkip++;
                    if ($adaGagalRow) $jumlahGagal++;
                    continue;
                }

                $pcs = 0;
                for ($j = 1; $j <= 40; $j++) {
                    $val = array_key_exists("qty{$j}", $upd) ? $upd["qty{$j}"] : ($x->{"qty{$j}"} ?? 0);
                    $pcs += (int) $val;
                }
                $upd['pcs']    = $pcs;
                $upd['jmlpcs'] = 1;

                $newQtyArr = [];
                foreach ($sizeIndexes as $i) {
                    $newQtyArr[$i] = array_key_exists("qty{$i}", $upd)
                        ? $upd["qty{$i}"]
                        : ($x->{"qty{$i}"} ?? 0);
                }

                $db->table('pack')->where('packpk', $x->packpk)->update($upd);
                $this->insertActpackHistory($x->packpk, $x, $newQtyArr);
                $jumlahUpdate++;

                if ($adaPartialRow) $jumlahParsial++;
                if ($adaGagalRow)   $jumlahGagal++;
            }

            DB::connection($connection)->commit();

            $pesan = "{$jumlahUpdate} carton berhasil diupdate.";
            if ($jumlahSkip > 0)    $pesan .= " {$jumlahSkip} carton dilewati (sudah penuh / transfer habis).";
            if ($jumlahParsial > 0) $pesan .= " {$jumlahParsial} carton terisi sebagian karena Transfer terbatas.";
            if ($jumlahGagal > 0)   $pesan .= " {$jumlahGagal} carton/size tidak bisa ditambah, Polibag sudah habis.";

            return response()->json([
                'icon'  => $jumlahGagal > 0 ? 'warning' : 'success',
                'title' => $pesan,
            ]);
        } catch (\Throwable $e) {
            DB::connection($connection)->rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal mengupdate Actual Qty Carton.'
            ], 500);
        }
    }

    // Dipakai buat hitung sisa Transfer/Polibag
    private function getTransferQtyGlobal($db, $popk, int $sizeIdx, ?string $sizeLabel = null): int
    {
        $bjQty = (int) $db->table('bj')->where('popk', $popk)->sum("qty{$sizeIdx}");

        if ($sizeLabel === null) {
            $sizeLabel = $db->table('po')->where('popk', $popk)->value("size{$sizeIdx}");
        }

        $outputQty = 0;
        if (!empty($sizeLabel)) {
            $outputQty = (int) DB::connection('mysql_polibag')
                ->table('output')
                ->where('popk', $popk)
                ->where('jnspk', 4)
                ->where('size', $sizeLabel)
                ->sum('jmlpcs');
        }

        return $bjQty + $outputQty;
    }

    // Proses delete Input Actual Massal di halaman input packing list
    // Modal modal-delete-actual-ctn-global.blade.php
    public function deleteActualGlobal(Request $request)
    {
        $request->validate([
            'packpk' => 'required',
        ], [
            'packpk.required' => 'Pilih minimal satu carton.'
        ]);

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        $connection = 'mysql';
        $db = DB::connection($connection);

        DB::connection($connection)->beginTransaction();

        try {
            $size = $request->size;
            $ids  = array_filter(explode(',', $request->packpk));

            $jumlah = 0;

            foreach ($ids as $id) {
                $pack = $db->table('pack')
                    ->where('packpk', $id)
                    ->where('status', 4)
                    ->first();

                if (!$pack) {
                    continue;
                }

                $update = [];

                if ($size !== '' && $size !== null) {
                    $update["qty{$size}"] = null;

                    $pcs = 0;
                    for ($i = 1; $i <= 40; $i++) {
                        $nilai = ($i == $size) ? null : $pack->{"qty{$i}"};
                        $pcs += (int) ($nilai ?? 0);
                    }
                    $update['pcs'] = $pcs;
                } else {
                    for ($i = 1; $i <= 40; $i++) {
                        $update["qty{$i}"] = null;
                    }
                    $update['pcs'] = 0;
                }

                $update['jmlpcs'] = $update['pcs'] == 0 ? 0 : 1;

                $sizeIndexesToClear = ($size !== '' && $size !== null) ? [(int) $size] : range(1, 40);

                $newQtyArr = [];
                for ($i = 1; $i <= 25; $i++) {
                    $newQtyArr[$i] = in_array($i, $sizeIndexesToClear) ? 0 : ($pack->{"qty{$i}"} ?? 0);
                }

                $db->table('pack')->where('packpk', $id)->update($update);
                $this->insertActpackHistory($id, $pack, $newQtyArr);
                $jumlah++;
            }

            DB::connection($connection)->commit();

            return response()->json([
                'icon'  => 'success',
                'title' => "{$jumlah} carton berhasil dihapus Actual Qty.",
            ]);
        } catch (\Throwable $e) {
            DB::connection($connection)->rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menghapus Actual Qty Carton.'
            ], 500);
        }
    }

    // Proses Copy Carton, dengan nilai Plan dan Actual(Jika sisa) Massal di halaman input packing list
    // Modal modal-copy-ctn-global.blade.php
    public function copyMultipleGlobal(Request $request)
    {
        $request->validate([
            'packpk' => 'required',
            'copy'   => 'required|integer|min:1',
        ]);

        $po    = $request->input('po');
        $op    = $request->input('op');
        $poref = $request->input('poref');
        $mif   = (int) $request->input('mif', session('pos'));

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        $connection = 'mysql';
        $db = DB::connection($connection);

        $ids = collect(explode(',', $request->packpk))
            ->map(fn($x) => (int) $x)
            ->filter()
            ->values();

        $copies = (int) $request->copy;

        if ($ids->isEmpty()) {
            return response()->json([
                'icon'  => 'warning',
                'title' => 'Pilih minimal satu carton.'
            ], 422);
        }

        DB::connection($connection)->beginTransaction();

        try {
            $rows = $db->table('pack')
                ->whereIn('packpk', $ids)
                ->orderBy('urut')
                ->orderBy('packpk')
                ->lockForUpdate()
                ->get();

            if ($rows->isEmpty()) {
                DB::connection($connection)->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Data tidak ditemukan'
                ], 422);
            }

            $rowsByCarton = $rows->groupBy('carton');

            $affectedPopks = $rows->pluck('popk')->unique()->values();
            $remaining = [];
            foreach ($affectedPopks as $popkKey) {
                $remaining[$popkKey] = [];
                for ($i = 1; $i <= 40; $i++) {
                    $ready    = (int) $db->table('pack')->where('popk', $popkKey)->sum("qty{$i}");
                    $transfer = $this->getTransferQtyGlobal($db, $popkKey, $i);
                    $remaining[$popkKey][$i] = $transfer - $ready;
                }
            }

            $popksInScope = $db->table('po')
                ->where('OP', $op)
                ->where('mif', $mif)
                ->when(
                    $po !== null && $po !== '',
                    fn($q) => $q->where('POno', $po),
                    fn($q) => $q->where(function ($qq) {
                        $qq->whereNull('POno')->orWhere('POno', '');
                    })
                )
                ->when(
                    $poref !== null && $poref !== '',
                    fn($q) => $q->where('poref', $poref),
                    fn($q) => $q->where(function ($qq) {
                        $qq->whereNull('poref')->orWhere('poref', '');
                    })
                )
                ->pluck('popk');

            $scopePopks = $popksInScope->isNotEmpty() ? $popksInScope : $affectedPopks;

            $usedCartonNames = $db->table('pack')
                ->whereIn('popk', $scopePopks)
                ->pluck('carton')
                ->unique()
                ->flip()
                ->toArray();

            $totalCopyDibuat     = 0;
            $totalActualDicopy   = 0;
            $totalActualDitolak  = 0;
            $sizeHabisInfoByPopk = [];

            foreach ($rowsByCarton as $sourceCarton => $rowsInCarton) {

                $suffixIndex   = 0;
                $copiesCreated = 0;
                $pengaman      = 0;

                while ($copiesCreated < $copies) {

                    $pengaman++;
                    if ($pengaman > 10000) {
                        break;
                    }

                    $suffix = $suffixIndex === 0 ? ' copy' : ' copy ' . $suffixIndex;
                    $candidateCarton = $sourceCarton . $suffix;

                    if (array_key_exists($candidateCarton, $usedCartonNames)) {
                        $suffixIndex++;
                        continue;
                    }

                    $usedCartonNames[$candidateCarton] = true;

                    foreach ($rowsInCarton as $row) {
                        $popkKey = $row->popk;

                        $rowPunyaActual = false;
                        for ($i = 1; $i <= 40; $i++) {
                            if ((int) ($row->{"qty{$i}"} ?? 0) > 0) {
                                $rowPunyaActual = true;
                                break;
                            }
                        }

                        $new = (array) $row;
                        unset($new['packpk']);
                        $new['carton']     = $candidateCarton;
                        $new['nobar']      = $row->nobar ? ($row->nobar . $suffix) : $row->nobar;
                        $new['tanggal']    = now();
                        $new['waktu']      = now();
                        $new['exportpk']   = null;
                        $new['exportdtpk'] = null;
                        $new['part']       = null;
                        $new['reject']     = null;
                        $new['contpk']     = null;

                        if ($rowPunyaActual) {
                            $pcsActual = 0;
                            $adaPartialDiCarton = false;

                            for ($i = 1; $i <= 40; $i++) {
                                $actualAsal = (int) ($row->{"qty{$i}"} ?? 0);

                                if ($actualAsal <= 0) {
                                    $new["qty{$i}"] = $row->{"qty{$i}"} ?? null;
                                    continue;
                                }

                                $avail = max(0, $remaining[$popkKey][$i] ?? 0);

                                if ($avail >= $actualAsal) {
                                    $new["qty{$i}"] = $actualAsal;
                                    $remaining[$popkKey][$i] -= $actualAsal;
                                    $pcsActual += $actualAsal;
                                    $totalActualDicopy++;
                                } elseif ($avail > 0) {
                                    $new["qty{$i}"] = $avail;
                                    $remaining[$popkKey][$i] -= $avail;
                                    $pcsActual += $avail;
                                    $totalActualDitolak++;
                                    $adaPartialDiCarton = true;
                                    $sizeHabisInfoByPopk[$popkKey][$i] = true;
                                } else {
                                    $new["qty{$i}"] = null;
                                    $totalActualDitolak++;
                                    $sizeHabisInfoByPopk[$popkKey][$i] = true;
                                }
                            }

                            $new['pcs']    = $pcsActual;
                            $new['jmlpcs'] = 1;
                        } else {
                            for ($i = 1; $i <= 40; $i++) {
                                $new["qty{$i}"] = null;
                            }
                            $new['pcs'] = 0;
                        }

                        $db->table('pack')->insert($new);
                        $totalCopyDibuat++;
                    }

                    $copiesCreated++;
                    $suffixIndex++;
                }
            }

            foreach ($affectedPopks as $popkKey) {
                $ctn = $db->table('pack')->where('popk', $popkKey)->count();
                $db->table('po')->where('popk', $popkKey)->update(['ctn' => $ctn]);
            }

            DB::connection($connection)->commit();

            $pesan = "{$totalCopyDibuat} baris packing berhasil dicopy.";

            if ($totalActualDitolak > 0) {
                $keteranganList = [];
                foreach ($sizeHabisInfoByPopk as $popkKey => $sizes) {
                    $poRow = $db->table('po')->where('popk', $popkKey)->first();
                    $labelSize = collect(array_keys($sizes))
                        ->map(fn($i) => $poRow->{"size{$i}"} ?? "Size {$i}")
                        ->implode(', ');
                    $keteranganList[] = ($poRow->material ?? '-') . ($poRow->secsz ? " - {$poRow->secsz}" : '') . ": {$labelSize}";
                }
                $pesan .= ' Namun sebagian Actual Qty tidak ikut disalin karena Polibag sudah habis untuk: '
                    . implode(' | ', $keteranganList) . '.';
            }

            return response()->json([
                'icon'  => $totalActualDitolak > 0 ? 'warning' : 'success',
                'title' => $pesan,
            ]);
        } catch (\Throwable $e) {
            DB::connection($connection)->rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menyalin carton.'
            ], 500);
        }
    }

    // Hapus baris/carton -- dipanggil dari tombol "Delete CTN" massal.
    public function deleteMultipleGlobal(Request $request)
    {
        $request->validate([
            'packpk' => 'required|string'
        ]);

        $ids = collect(explode(',', $request->packpk))
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return response()->json([
                'icon'  => 'warning',
                'title' => 'Tidak ada carton yang dipilih'
            ], 422);
        }

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        $connection = 'mysql';
        $db = DB::connection($connection);

        DB::connection($connection)->beginTransaction();

        try {
            $affectedPopks = $db->table('pack')
                ->whereIn('packpk', $ids)
                ->pluck('popk')
                ->unique()
                ->values();

            if ($affectedPopks->isEmpty()) {
                DB::connection($connection)->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Data carton tidak ditemukan'
                ], 422);
            }

            $deleted = $db->table('pack')
                ->whereIn('packpk', $ids)
                ->delete();

            // Renumbering TIDAK dilakukan -- carton yang tersisa TIDAK
            // diurutkan ulang. Nomor yang "bolong" dibiarkan apa adanya.
            foreach ($affectedPopks as $popkAffected) {
                $ctn = $db->table('pack')->where('popk', $popkAffected)->count();
                $db->table('po')->where('popk', $popkAffected)->update(['ctn' => $ctn]);
            }

            DB::connection($connection)->commit();

            return response()->json([
                'icon'  => 'success',
                'title' => "{$deleted} baris packing berhasil dihapus.",
            ]);
        } catch (\Throwable $e) {
            DB::connection($connection)->rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menghapus carton'
            ], 500);
        }
    }

    // Segel/buka segel carton massal.
    public function updateSegelStatusGlobal(Request $request)
    {
        $request->validate([
            'packpk' => 'required',
            'target' => 'required|in:0,1',
        ], [
            'packpk.required' => 'Pilih minimal satu carton.',
            'target.required' => 'Target status segel tidak valid.',
            'target.in'       => 'Target status segel tidak valid.',
        ]);

        $target = (int) $request->target;

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        $connection = 'mysql';
        $db = DB::connection($connection);

        DB::connection($connection)->beginTransaction();
        try {
            $ids = array_values(array_filter(explode(',', $request->packpk)));
            if (empty($ids)) {
                DB::connection($connection)->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Pilih minimal satu carton.',
                ], 422);
            }

            $packs = $db->table('pack')
                ->whereIn('packpk', $ids)
                ->lockForUpdate()
                ->get();

            if ($packs->isEmpty()) {
                DB::connection($connection)->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Data carton tidak ditemukan.',
                ], 422);
            }

            if ($target === 1) {
                foreach ($packs as $pack) {
                    for ($i = 1; $i <= 40; $i++) {
                        $plan = (int) ($pack->{"qtyp{$i}"} ?? 0);
                        if ($plan <= 0) continue;
                        $actual = (int) ($pack->{"qty{$i}"} ?? 0);
                        if ($actual <= 0) {
                            DB::connection($connection)->rollBack();
                            return response()->json([
                                'icon'  => 'warning',
                                'title' => "Carton <b>{$pack->carton}</b> belum lengkap -- masih ada Size dengan Plan yang Actual-nya belum diisi. Lengkapi dulu Actual-nya sebelum bisa disegel.",
                            ], 422);
                        }
                    }
                }
            }

            $sudahSesuai = $packs->where('segel', $target)->count();
            $perluDiubah = $packs->where('segel', '<>', $target)->pluck('packpk');

            if ($perluDiubah->isEmpty()) {
                DB::connection($connection)->rollBack();
                $labelStatus = $target === 1 ? 'Segel' : 'Buka Segel';
                return response()->json([
                    'icon'  => 'warning',
                    'title' => "Semua carton yang dipilih sudah berstatus {$labelStatus} sebelumnya.",
                ], 422);
            }

            $updateData = ['segel' => $target];
            if ($target === 1) {
                $updateData['sealdate'] = now();
            } else {
                $updateData['unsealdate'] = now();
            }

            $db->table('pack')
                ->whereIn('packpk', $perluDiubah)
                ->update($updateData);

            DB::connection($connection)->commit();

            $jumlah = $perluDiubah->count();
            $aksi   = $target === 1 ? 'disegel' : 'dibuka segelnya';
            $pesan  = "{$jumlah} baris packing berhasil {$aksi}.";
            if ($sudahSesuai > 0) {
                $pesan .= " {$sudahSesuai} baris dilewati (sudah sesuai status sebelumnya).";
            }

            return response()->json([
                'icon'  => 'success',
                'title' => $pesan,
            ]);
        } catch (\Throwable $e) {
            DB::connection($connection)->rollBack();
            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal mengubah status Segel Carton.'
            ], 500);
        }
    }

    // Urutkan ulang No Carton dan/atau Barcode -- 2 mode (carton/barcode).
    public function urutCtnGlobal(Request $request)
    {
        $request->validate([
            'op'   => 'required',
            'mode' => 'required|in:carton,barcode',
        ]);

        $po    = $request->input('po');
        $op    = $request->input('op');
        $poref = $request->input('poref');
        $mif   = (int) $request->input('mif', session('pos'));
        $mode  = $request->input('mode');

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        $connection = 'mysql';
        $db = DB::connection($connection);

        DB::connection($connection)->beginTransaction();

        try {
            $popks = $db->table('po')
                ->where('OP', $op)
                ->where('mif', $mif)
                ->when(
                    $po !== null && $po !== '',
                    fn($q) => $q->where('POno', $po),
                    fn($q) => $q->where(function ($qq) {
                        $qq->whereNull('POno')->orWhere('POno', '');
                    })
                )
                ->when(
                    $poref !== null && $poref !== '',
                    fn($q) => $q->where('poref', $poref),
                    fn($q) => $q->where(function ($qq) {
                        $qq->whereNull('poref')->orWhere('poref', '');
                    })
                )
                ->pluck('popk');

            if ($popks->isEmpty()) {
                DB::connection($connection)->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Data PO/OP tidak ditemukan.',
                ], 422);
            }

            $allPacks = $db->table('pack')
                ->whereIn('popk', $popks)
                ->where('status', 4)
                ->orderBy('urut')
                ->orderBy('packpk')
                ->get();

            if ($allPacks->isEmpty()) {
                DB::connection($connection)->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Tidak ada data carton untuk diurutkan.',
                ], 422);
            }

            $groupedByCarton = [];
            $naturalOrder = [];
            foreach ($allPacks as $row) {
                $key = $row->carton;
                if (!isset($groupedByCarton[$key])) {
                    $groupedByCarton[$key] = [];
                    $naturalOrder[] = $key;
                }
                $groupedByCarton[$key][] = $row;
            }

            if ($mode === 'carton') {
                $request->validate(['awal' => 'required|string|max:50']);

                $awal        = trim($request->input('awal'));
                $pairBarcode = (bool) $request->input('pair_barcode', false);
                $barcodeAwal = trim((string) $request->input('barcode_awal', ''));

                if ($pairBarcode && $barcodeAwal === '') {
                    DB::connection($connection)->rollBack();
                    return response()->json([
                        'icon'  => 'warning',
                        'title' => 'Barcode Awal wajib diisi kalau ingin mengurutkan Carton & Barcode sekaligus.',
                    ], 422);
                }

                $offset = 0;
                foreach ($naturalOrder as $oldCarton) {
                    $rowsInGroup = $groupedByCarton[$oldCarton];
                    $newCarton = $this->incrementCartonNumber($awal, $offset);
                    $updateData = ['carton' => $newCarton];
                    if ($pairBarcode) {
                        $updateData['nobar'] = $this->incrementCartonNumber($barcodeAwal, $offset);
                    }
                    foreach ($rowsInGroup as $row) {
                        $db->table('pack')->where('packpk', $row->packpk)->update($updateData);
                    }
                    $offset++;
                }

                DB::connection($connection)->commit();

                return response()->json([
                    'icon'  => 'success',
                    'title' => 'Nomor Carton berhasil diurutkan' . ($pairBarcode ? ' beserta Barcode-nya (berpasangan).' : '. Barcode tidak diubah.'),
                ]);
            }

            $request->validate([
                'sub_mode'     => 'required|in:manual,otomatis',
                'barcode_awal' => 'required|string|max:50',
            ]);

            $subMode     = $request->input('sub_mode');
            $barcodeAwal = trim($request->input('barcode_awal'));

            $sortedCartonKeys = array_keys($groupedByCarton);
            usort($sortedCartonKeys, function ($a, $b) {
                return $this->extractCartonNumber((string) $a) <=> $this->extractCartonNumber((string) $b);
            });

            $startIndex = 0;

            if ($subMode === 'manual') {
                $request->validate(['carton_awal' => 'required|string|max:50']);
                $cartonAwalInput = trim($request->input('carton_awal'));

                $foundIndex = array_search($cartonAwalInput, $sortedCartonKeys, true);

                if ($foundIndex === false) {
                    DB::connection($connection)->rollBack();
                    return response()->json([
                        'icon'  => 'warning',
                        'title' => "Nomor Carton <b>{$cartonAwalInput}</b> tidak ditemukan di sistem. Masukkan nomor carton yang sudah ada.",
                    ], 422);
                }

                $startIndex = $foundIndex;
            }

            $offset = 0;
            for ($idx = $startIndex; $idx < count($sortedCartonKeys); $idx++) {
                $cartonKey   = $sortedCartonKeys[$idx];
                $rowsInGroup = $groupedByCarton[$cartonKey];
                $newNobar = $this->incrementCartonNumber($barcodeAwal, $offset);
                foreach ($rowsInGroup as $row) {
                    $db->table('pack')->where('packpk', $row->packpk)->update(['nobar' => $newNobar]);
                }
                $offset++;
            }

            DB::connection($connection)->commit();

            return response()->json([
                'icon'  => 'success',
                'title' => 'Barcode berhasil diurutkan.' . ($subMode === 'manual' ? ' Carton sebelum titik awal tidak ikut berubah.' : ''),
            ]);
        } catch (\Throwable $e) {
            DB::connection($connection)->rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal mengurutkan.'
            ], 422);
        }
    }

    private function extractCartonNumber(string $carton): int
    {
        if (preg_match('/(\d+)$/', $carton, $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    // Reload AJAX kartu ringkasan (Total Pcs/Planned/Packed/Short/Color/Size/Carton) di halaman Input.
    public function cardsInfoGlobal(Request $request)
    {
        $po    = $request->query('po');
        $op    = $request->query('op');
        $poref = $request->query('poref');
        $mif   = (int) $request->query('mif', session('pos'));

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        $connection = 'mysql';
        $db = DB::connection($connection);

        $breakdown = $this->getBreakdownDataGlobal($po, $op, $poref, $mif, $connection);

        $groups = $breakdown['groups'];
        $aggQty = $breakdown['aggQty'];

        $totalPcs   = $aggQty['totOrder'];
        $plannedPcs = $aggQty['totPlan'];
        $packedPcs  = $aggQty['totReady'];
        $shortPcs   = max(0, $totalPcs - $plannedPcs);

        $totalColors = collect($groups)->pluck('material')->filter()->unique()->count();
        $totalSizes  = count($breakdown['activeSizes']);

        $popks = collect($groups)->flatMap(fn($g) => $g['popks'])->values();

        $totalCarton  = $breakdown['ctnSummary']['ctnPlan'];
        $sealedCarton = $db->table('pack')
            ->whereIn('popk', $popks)
            ->get()
            ->groupBy('carton')
            ->filter(fn($rowsInCarton) => $rowsInCarton->every(fn($r) => (int) $r->segel === 1))
            ->count();
        $openCarton = $totalCarton - $sealedCarton;

        return view('menu.packing.partials.cards_info_global', compact(
            'totalPcs',
            'plannedPcs',
            'packedPcs',
            'shortPcs',
            'totalColors',
            'totalSizes',
            'totalCarton',
            'sealedCarton',
            'openCarton'
        ));
    }

    // Kombinasi Color/Sec Size (untuk dropdown modal Add Packing) -- reuse
    // getBreakdownDataGlobal() (1 sumber kebenaran, sama dengan breakdownSummaryGlobal()).
    public function combosGlobal(Request $request)
    {
        $po    = $request->query('po');
        $op    = $request->query('op');
        $poref = $request->query('poref');
        $mif   = (int) $request->query('mif', session('pos'));

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        $connection = 'mysql';
        $db = DB::connection($connection);

        $breakdown   = $this->getBreakdownDataGlobal($po, $op, $poref, $mif, $connection);
        $groups      = $breakdown['groups'];
        $activeSizes = $breakdown['activeSizes'];

        return response()->json(
            $this->buildColorSecszCombos($db, $groups, $activeSizes)
        );
    }

    private function getSinglePopkQtyBreakdown($db, int $popk, array $activeSizes): array
    {
        $sumQtyExpr  = collect(range(1, 40))->map(fn($i) => "SUM(qty$i) as qty$i")->implode(', ');
        $sumQtyPExpr = collect(range(1, 40))->map(fn($i) => "SUM(qtyp$i) as qtyp$i")->implode(', ');

        $dt2Sum  = $db->table('po')->selectRaw("SUM(qty) as qty, {$sumQtyExpr}")->where('popk', $popk)->first();
        $dt3     = $db->table('pack')->selectRaw("SUM(pcs) as pcs, SUM(jmlpcs) as pack, SUM(pcsp) as pcsp, {$sumQtyExpr}, {$sumQtyPExpr}")->where('popk', $popk)->first();
        $summary = $db->table('bj')->selectRaw("SUM(pcs) as pcs, {$sumQtyExpr}")->where('popk', $popk)->first();

        $outputSizeSums = DB::connection('mysql_polibag')->table('output')
            ->where('popk', $popk)->where('jnspk', 4)
            ->select('size')->selectRaw('SUM(jmlpcs) as total')->groupBy('size')->get();

        $sizeLabelToIndex = array_flip($activeSizes);
        foreach ($outputSizeSums as $osRow) {
            $idx = $sizeLabelToIndex[$osRow->size] ?? null;
            if ($idx !== null) {
                $qtyField = "qty{$idx}";
                $summary->{$qtyField} = (int) ($summary->{$qtyField} ?? 0) + (int) $osRow->total;
            }
        }

        $orderQty = [];
        $readyQty = [];
        $planQty  = [];
        $transQty = [];
        foreach ($activeSizes as $i => $sz) {
            $orderQty[$i] = $dt2Sum->{"qty$i"} ?? 0;
            $readyQty[$i] = $dt3->{"qty$i"} ?? 0;
            $planQty[$i]  = $dt3->{"qtyp$i"} ?? 0;
            $transQty[$i] = $summary->{"qty$i"} ?? 0;
        }

        return [$orderQty, $planQty, $readyQty, $transQty];
    }

    private function buildColorSecszCombos($db, $groups, array $activeSizes)
    {
        $colorSecszCombos = collect();
    
        foreach ($groups as $g) {
            $popks = collect($g['popks'])->values();
    
            if ($popks->count() <= 1) {
                $colorSecszCombos->push([
                    'material'        => $g['material'],
                    'secsz'           => $g['secsz'],
                    'popk'            => $popks->first(),
                    'customer'        => $g['customer'] ?? null,   // BARU
                    'orderQty'        => $g['orderQty'],
                    'planQty'         => $g['planQty'],
                    'readyQty'        => $g['readyQty'],
                    'transQty'        => $g['transQty'],
                    'duplicateMarker' => null,
                ]);
                continue;
            }
    
            foreach ($popks as $idx => $popk) {
                [$orderQty, $planQty, $readyQty, $transQty] = $this->getSinglePopkQtyBreakdown($db, (int) $popk, $activeSizes);
                $customerForPopk = $db->table('po')->where('popk', $popk)->value('customer'); // BARU
    
                $colorSecszCombos->push([
                    'material'        => $g['material'],
                    'secsz'           => $g['secsz'],
                    'popk'            => $popk,
                    'customer'        => $customerForPopk,   // BARU
                    'orderQty'        => $orderQty,
                    'planQty'         => $planQty,
                    'readyQty'        => $readyQty,
                    'transQty'        => $transQty,
                    'duplicateMarker' => $idx + 1,
                ]);
            }
        }
    
        return $colorSecszCombos->values();
    }

    // Kombinasi dimensi (panjang/lebar/tinggi) dan berat (nw/gw) yang sudah
    // pernah dipakai di PO/OP ini -- dipakai autocomplete modal Bulk Dimensi.
    public function distinctDimensiCtn(Request $request)
    {
        $po    = $request->query('po');
        $op    = $request->query('op');
        $poref = $request->query('poref');
        $mif   = (int) $request->query('mif', session('pos'));

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        $connection = 'mysql';
        $db = DB::connection($connection);

        $popks = $db->table('po')
            ->where('OP', $op)
            ->when(
                $po !== null && $po !== '',
                fn ($q) => $q->where('POno', $po),
                fn ($q) => $q->where(function ($qq) {
                    $qq->whereNull('POno')->orWhere('POno', '');
                })
            )
            ->when(
                $poref !== null && $poref !== '',
                fn ($q) => $q->where('poref', $poref),
                fn ($q) => $q->where(function ($qq) {
                    $qq->whereNull('poref')->orWhere('poref', '');
                })
            )
            ->pluck('popk');

        if ($popks->isEmpty()) {
            return response()->json(['combos' => [], 'weights' => []]);
        }

        $combos = $db->table('pack')
            ->whereIn('popk', $popks)
            ->whereNotNull('panjang')
            ->whereNotNull('lebar')
            ->whereNotNull('tinggi')
            ->select('panjang', 'lebar', 'tinggi')
            ->distinct()
            ->orderBy('panjang')
            ->orderBy('lebar')
            ->orderBy('tinggi')
            ->limit(50)
            ->get();

        $weights = $db->table('pack')
            ->whereIn('popk', $popks)
            ->whereNotNull('nw')
            ->whereNotNull('gw')
            ->where('nw', '<>', '')
            ->where('gw', '<>', '')
            ->select('nw', 'gw')
            ->distinct()
            ->orderBy('nw')
            ->orderBy('gw')
            ->limit(50)
            ->get();

        return response()->json(['combos' => $combos, 'weights' => $weights]);
    }

    // Update dimensi/berat carton secara massal -- dimensi (panjang/lebar/
    // tinggi) cuma update 'pack', nw/gw update KEDUANYA ('pack' DAN 'ship').
    public function bulkUpdateDimensiCtn(Request $request)
    {
        $validated = $request->validate([
            'packpk'  => 'required|string',
            'panjang' => 'nullable|numeric|min:0',
            'lebar'   => 'nullable|numeric|min:0',
            'tinggi'  => 'nullable|numeric|min:0',
            'nw'      => 'nullable|numeric|min:0',
            'gw'      => 'nullable|numeric|min:0',
            'mif'     => 'nullable',
        ]);

        // GANTI -- FIX UTAMA: connection SEKARANG SELALU 'mysql'.
        $connection = 'mysql';
        $db = DB::connection($connection);

        $packpks = collect(explode(',', $validated['packpk']))
            ->map(fn ($v) => (int) trim($v))
            ->filter()
            ->unique()
            ->values();

        if ($packpks->isEmpty()) {
            return response()->json(['icon' => 'warning', 'title' => 'Tidak ada carton yang dipilih.'], 422);
        }

        $dimensiUpdate = array_filter([
            'panjang' => $validated['panjang'] ?? null,
            'lebar'   => $validated['lebar'] ?? null,
            'tinggi'  => $validated['tinggi'] ?? null,
        ], fn ($v) => $v !== null);

        $weightUpdate = array_filter([
            'nw' => $validated['nw'] ?? null,
            'gw' => $validated['gw'] ?? null,
        ], fn ($v) => $v !== null);

        if (empty($dimensiUpdate) && empty($weightUpdate)) {
            return response()->json(['icon' => 'warning', 'title' => 'Isi minimal salah satu: Panjang/Lebar/Tinggi atau NW/GW.'], 422);
        }

        if (!empty($dimensiUpdate)) {
            $db->table('pack')->whereIn('packpk', $packpks)->update($dimensiUpdate);
        }

        if (!empty($weightUpdate)) {
            $db->table('pack')->whereIn('packpk', $packpks)->update($weightUpdate);
        }

        $cartonCount = $db->table('pack')->whereIn('packpk', $packpks)->distinct()->count('carton');

        return response()->json([
            'icon'  => 'success',
            'title' => "Data berhasil diterapkan ke {$cartonCount} carton.",
        ]);
    }

    // STEP 1: list PO+OP (buyer SAMA dengan PO/OP halaman ini saat ini) --
    // dipakai modal pencarian "Campur Polibag dari PO Lain".
    public function crossPoOpLookup(Request $request)
    {
        $search    = trim((string) $request->query('search', ''));
        $buyer     = trim((string) $request->query('buyer', ''));
        $excludePo = $request->query('exclude_po');
        $excludeOp = $request->query('exclude_op');

        if ($buyer === '') {
            return response()->json(['rows' => []]);
        }

        $db = DB::connection('mysql');

        $query = $db->table('po')
            ->where('qty', '>', 0)
            ->where('OP', '<>', '')
            ->whereRaw('TRIM(UPPER(buyer)) = ?', [trim(mb_strtoupper($buyer))]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('POno', 'like', "%{$search}%")
                    ->orWhere('OP', 'like', "%{$search}%")
                    ->orWhere('material', 'like', "%{$search}%")
                    ->orWhere('customer', 'like', "%{$search}%");
            });
        }

        // Jangan tampilkan PO/OP halaman ini sendiri -- tidak masuk akal
        // "campur" dengan dirinya sendiri.
        if ($excludePo !== null && $excludeOp !== null) {
            $query->where(function ($q) use ($excludePo, $excludeOp) {
                $q->where('POno', '<>', $excludePo)->orWhere('OP', '<>', $excludeOp);
            });
        }

        $rows = $query->orderByDesc('popk')->limit(200)
            ->get(['POno', 'OP', 'poref', 'customer', 'buyer']);
        
        $grouped = $rows
            ->groupBy(fn ($r) => $r->POno . '|' . $r->OP . '|' . ($r->poref ?? ''))
            ->map(function ($group) {
                $rep = $group->first();
                $distinctCustomers = $group->pluck('customer')->filter()->unique()->values();
                return [
                    'POno'          => $rep->POno,
                    'OP'            => $rep->OP,
                    'poref'         => $rep->poref,
                    'customer'      => $rep->customer,             
                    'customerCount' => $distinctCustomers->count(), 
                    'customerList'  => $distinctCustomers->values(),
                    'buyer'         => $rep->buyer,
                ];
            })
            ->values();
        
        return response()->json(['rows' => $grouped]);
    }

    // STEP 2: GANTI TOTAL -- sekarang menerima PO/OP SPESIFIK (hasil pilihan
    // user di Step 1), bukan free-text search. Menampilkan breakdown
    // Color/Sec Size + sisa Order Qty untuk PO/OP itu.
    public function crossPoComboLookup(Request $request)
    {
        $po    = $request->query('po');
        $op    = $request->query('op');
        $poref = $request->query('poref');

        if (!$op) {
            return response()->json(['rows' => []]);
        }

        $db = DB::connection('mysql');

        $poRows = $db->table('po')
            ->where('OP', $op)
            ->when($po !== null && $po !== '', fn ($q) => $q->where('POno', $po))
            ->when($poref !== null && $poref !== '', fn ($q) => $q->where('poref', $poref))
            ->where('qty', '>', 0)
            ->get();

        $rows = [];
        foreach ($poRows as $poRow) {
            $activeSizes = [];
            for ($i = 1; $i <= 40; $i++) {
                $sz = $poRow->{"size$i"} ?? null;
                if (!empty($sz)) {
                    $activeSizes[$i] = $sz;
                }
            }
            if (empty($activeSizes)) continue;

            [$orderQty, $planQty, $readyQty, $transQty] = $this->getSinglePopkQtyBreakdown($db, (int) $poRow->popk, $activeSizes);

            $remainingQty = [];
            foreach ($activeSizes as $i => $sz) {
                $remainingQty[$i] = max(0, ($orderQty[$i] ?? 0) - ($planQty[$i] ?? 0));
            }
            $totalRemaining = array_sum($remainingQty);
            if ($totalRemaining <= 0) continue;

            $rows[] = [
                'popk'           => $poRow->popk,
                'POno'           => $poRow->POno,
                'OP'             => $poRow->OP,
                'poref'          => $poRow->poref,
                'customer'       => $poRow->customer,
                'material'       => $poRow->material,
                'secsz'          => $poRow->secsz,
                'activeSizes'    => $activeSizes,
                'orderQty'       => $orderQty,
                'planQty'        => $planQty,
                'remainingQty'   => $remainingQty,
                'totalRemaining' => $totalRemaining,
            ];
        }

        return response()->json(['rows' => $rows]);
    }

    public function crossPoCartonList(Request $request)
    {
        $po      = $request->query('po');
        $op      = $request->query('op');
        $poref   = $request->query('poref');
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = max(1, (int) $request->query('rows', 8));
    
        if (!$op) {
            return response()->json(['cartons' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage]);
        }
    
        $db = DB::connection('mysql');
    
        $popks = $db->table('po')
            ->where('OP', $op)
            ->when(
                $po !== null && $po !== '',
                fn ($q) => $q->where('POno', $po),
                fn ($q) => $q->where(fn ($qq) => $qq->whereNull('POno')->orWhere('POno', ''))
            )
            ->when(
                $poref !== null && $poref !== '',
                fn ($q) => $q->where('poref', $poref),
                fn ($q) => $q->where(fn ($qq) => $qq->whereNull('poref')->orWhere('poref', ''))
            )
            ->pluck('popk');
    
        if ($popks->isEmpty()) {
            return response()->json(['cartons' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage]);
        }
    
        $packRows = $db->table('pack')->whereIn('popk', $popks)->get();
        if ($packRows->isEmpty()) {
            return response()->json(['cartons' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage]);
        }
    
        $cartonGroups = $packRows->groupBy('carton');
    
        // Urut carton pakai natural sort, SAMA seperti daftar carton di
        // halaman utama, supaya konsisten.
        $cartonKeys = $cartonGroups->keys()
            ->sort(fn ($a, $b) => strnatcmp((string) $a, (string) $b))
            ->values();
    
        $total = $cartonKeys->count();
        $pageKeys = $cartonKeys->slice(($page - 1) * $perPage, $perPage)->values();
    
        // Cache baris 'po' per popk yang terlibat -- supaya tidak query
        // berulang untuk combo yang popk-nya sama di beberapa carton.
        $poRowCache = [];
        foreach ($popks as $pk) {
            $poRowCache[$pk] = $db->table('po')->where('popk', $pk)->first();
        }
    
        $cartons = $pageKeys->map(function ($cartonNo) use ($cartonGroups, $poRowCache, $db) {
            $rows = $cartonGroups[$cartonNo];
            $first = $rows->first();
    
            $combos = $rows->map(function ($r) use ($poRowCache, $db) {
                $poRow = $poRowCache[$r->popk] ?? null;
            
                $activeSizes = [];
                if ($poRow) {
                    for ($i = 1; $i <= 40; $i++) {
                        $sz = $poRow->{"size$i"} ?? null;
                        if (!empty($sz)) {
                            $activeSizes[$i] = $sz;
                        }
                    }
                }
            
                $planPerSize   = [];
                $actualPerSize = [];
                foreach ($activeSizes as $i => $sz) {
                    $planPerSize[$i]   = (int) ($r->{"qtyp{$i}"} ?? 0);
                    $actualPerSize[$i] = (int) ($r->{"qty{$i}"} ?? 0);
                }
            
                [$orderQty, $planQtyAll, $readyQtyAll, $transQtyAll] = $this->getSinglePopkQtyBreakdown($db, (int) $r->popk, $activeSizes);
            
                return [
                    'packpk'        => $r->packpk,
                    'popk'          => $r->popk,
                    'POno'          => $poRow->POno ?? null,
                    'OP'            => $poRow->OP ?? null,
                    'customer'      => $poRow->customer ?? null, 
                    'material'      => $r->material,
                    'secsz'         => $r->secsz,
                    'activeSizes'   => $activeSizes,
                    'planPerSize'   => $planPerSize,
                    'actualPerSize' => $actualPerSize,
                    'orderQty'      => $orderQty,
                    'planQtyAll'    => $planQtyAll,     // total Plan semua carton popk ini
                    'readyQtyAll'   => $readyQtyAll,    // total Actual semua carton popk ini
                    'transQtyAll'   => $transQtyAll,    // cap Transfer/Polibag
                    'totalPlan'     => array_sum($planPerSize),
                    'totalActual'   => array_sum($actualPerSize),
                ];
            })->values();
    
            return [
                'carton' => $cartonNo,
                'nobar'  => $first->nobar,
                'combos' => $combos,
            ];
        })->values();
    
        return response()->json([
            'cartons'  => $cartons,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ]);
    }

    public function crossPoComboInfo(Request $request)
    {
        $popks = collect(explode(',', $request->query('popks', '')))
            ->filter()->map(fn ($v) => (int) $v)->unique()->values();

        if ($popks->isEmpty()) {
            return response()->json(['rows' => []]);
        }

        $db = DB::connection('mysql');
        $rows = [];

        foreach ($popks as $popk) {
            $poRow = $db->table('po')->where('popk', $popk)->first();
            if (!$poRow) continue;
        
            $activeSizes = [];
            for ($i = 1; $i <= 40; $i++) {
                $sz = $poRow->{"size$i"} ?? null;
                if (!empty($sz)) $activeSizes[$i] = $sz;
            }
        
            [$orderQty, $planQty, $readyQty, $transQty] = $this->getSinglePopkQtyBreakdown($db, $popk, $activeSizes);
        
            $rows[] = [
                'popk'        => $popk,
                'POno'        => $poRow->POno,
                'OP'          => $poRow->OP,
                'customer'    => $poRow->customer ?? null,
                'material'    => $poRow->material,
                'secsz'       => $poRow->secsz,
                'activeSizes' => $activeSizes,
                'orderQty'    => $orderQty,
                'planQtyAll'  => $planQty,    
                'readyQtyAll' => $readyQty,   
                'transQtyAll' => $transQty,   
            ];
        }

        return response()->json(['rows' => $rows]);
    }

    public function bundleOpLookup(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $mif    = (int) $request->query('mif', session('pos'));

        if (strlen($search) < 2) {
            return response()->json(['rows' => []]);
        }

        $db = DB::connection('mysql');

        $rows = $db->table('po')
            ->where('qty', '>', 0)
            ->where('OP', '<>', '')
            ->where(function ($q) use ($search) {
                $q->where('POno', 'like', "%{$search}%")
                    ->orWhere('OP', 'like', "%{$search}%")
                    ->orWhere('customer', 'like', "%{$search}%")
                    ->orWhere('buyer', 'like', "%{$search}%");
            })
            ->orderByDesc('popk')
            ->limit(200)
            ->get(['POno', 'OP', 'poref', 'customer', 'buyer']);

        $grouped = $rows
            ->groupBy(fn ($r) => $r->POno . '|' . $r->OP . '|' . ($r->poref ?? ''))
            ->map(function ($group) {
                $rep = $group->first();
                $distinctCustomers = $group->pluck('customer')->filter()->unique()->values();
                return [
                    'POno'          => $rep->POno,
                    'OP'            => $rep->OP,
                    'poref'         => $rep->poref,
                    'customer'      => $rep->customer,
                    'customerCount' => $distinctCustomers->count(),
                    'customerList'  => $distinctCustomers->values(),
                    'buyer'         => $rep->buyer,
                ];
            })
            ->values()
            ->take(50);

        return response()->json(['rows' => $grouped]);
    }

    public function bundleCartonList(Request $request)
    {
        $po      = $request->query('po');
        $op      = $request->query('op');
        $poref   = $request->query('poref');
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = max(1, (int) $request->query('rows', 8));
        $excludeBundlepk = $request->query('exclude_bundlepk'); // BARU
    
        if (!$op) {
            return response()->json(['cartons' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage]);
        }
    
        $db = DB::connection('mysql');
    
        $popks = $db->table('po')
            ->where('OP', $op)
            ->when(
                $po !== null && $po !== '',
                fn ($q) => $q->where('POno', $po),
                fn ($q) => $q->where(fn ($qq) => $qq->whereNull('POno')->orWhere('POno', ''))
            )
            ->when(
                $poref !== null && $poref !== '',
                fn ($q) => $q->where('poref', $poref),
                fn ($q) => $q->where(fn ($qq) => $qq->whereNull('poref')->orWhere('poref', ''))
            )
            ->pluck('popk');
    
        if ($popks->isEmpty()) {
            return response()->json(['cartons' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage]);
        }
    
        $packRows = $db->table('pack')->whereIn('popk', $popks)->get();
        if ($packRows->isEmpty()) {
            return response()->json(['cartons' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage]);
        }
    
        $cartonGroups = $packRows->groupBy('carton');
        $cartonKeys = $cartonGroups->keys()
            ->sort(fn ($a, $b) => strnatcmp((string) $a, (string) $b))
            ->values();
    
        $total = $cartonKeys->count();
        $pageKeys = $cartonKeys->slice(($page - 1) * $perPage, $perPage)->values();
    
        $poRowCache = [];
        foreach ($popks as $pk) {
            $poRowCache[$pk] = $db->table('po')->where('popk', $pk)->first();
        }
    
        $cartons = $pageKeys->map(function ($cartonNo) use ($cartonGroups, $poRowCache, $excludeBundlepk) {
            $rows = $cartonGroups[$cartonNo];
            $first = $rows->first();
    
            // GANTI -- FIX UTAMA: carton yang bundlepk-nya SAMA dengan
            // $excludeBundlepk (bundle yang SEDANG diedit) TIDAK dianggap
            // konflik -- itu justru anggota bundle ini sendiri.
            $alreadyBundled = $rows->contains(function ($r) use ($excludeBundlepk) {
                if (empty($r->bundlepk)) return false;
                if ($excludeBundlepk && (int) $r->bundlepk === (int) $excludeBundlepk) return false;
                return true;
            });
            $hasExportpk = $rows->contains(fn ($r) => !empty($r->exportpk));
    
            $combos = $rows->map(function ($r) use ($poRowCache) {
                $poRow = $poRowCache[$r->popk] ?? null;
                $activeSizes = [];
                if ($poRow) {
                    for ($i = 1; $i <= 40; $i++) {
                        $sz = $poRow->{"size$i"} ?? null;
                        if (!empty($sz)) $activeSizes[$i] = $sz;
                    }
                }
                $planPerSize = []; $actualPerSize = [];
                foreach ($activeSizes as $i => $sz) {
                    $planPerSize[$i]   = (int) ($r->{"qtyp{$i}"} ?? 0);
                    $actualPerSize[$i] = (int) ($r->{"qty{$i}"} ?? 0);
                }
                return [
                    'packpk'        => $r->packpk,
                    'popk'          => $r->popk,
                    'POno'          => $poRow->POno ?? null,
                    'OP'            => $poRow->OP ?? null,
                    'customer'      => $poRow->customer ?? null,
                    'material'      => $r->material,
                    'secsz'         => $r->secsz,
                    'activeSizes'   => $activeSizes,
                    'planPerSize'   => $planPerSize,
                    'actualPerSize' => $actualPerSize,
                ];
            })->values();
    
            return [
                'carton'         => $cartonNo,
                'nobar'          => $first->nobar,
                'packpks'        => $rows->pluck('packpk')->values(),
                'alreadyBundled' => $alreadyBundled,
                'hasExportpk'    => $hasExportpk,
                'combos'         => $combos,
            ];
        })->values();
    
        return response()->json([
            'cartons'  => $cartons,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ]);
    }

    public function storeCartonBundle(Request $request)
    {
        $validated = $request->validate([
            'bundlepk'      => 'nullable|integer',   // BARU -- ada isinya kalau mode EDIT
            'bundle_carton' => 'required|string|max:50',
            'bundle_nobar'  => 'nullable|string|max:50',
            'nw'            => 'nullable|numeric',
            'gw'            => 'nullable|numeric',
            'panjang'       => 'nullable|numeric',
            'lebar'         => 'nullable|numeric',
            'tinggi'        => 'nullable|numeric',
            'keterangan'    => 'nullable|string',
            'packpks'       => 'required|array|min:1',
            'packpks.*'     => 'integer',
        ]);

        $db = DB::connection('mysql');
        DB::connection('mysql')->beginTransaction();

        try {
            $editingBundlepk = $validated['bundlepk'] ?? null;
            $packpks = collect($validated['packpks'])->map(fn ($v) => (int) $v)->unique()->values();

            $packRows = $db->table('pack')->whereIn('packpk', $packpks)->lockForUpdate()->get();
            if ($packRows->isEmpty()) {
                DB::connection('mysql')->rollBack();
                return response()->json(['icon' => 'warning', 'title' => 'Carton tidak ditemukan.'], 422);
            }

            $distinctExportpks = $packRows->pluck('exportpk')->filter()->unique()->values();
            if ($distinctExportpks->count() > 1) {
                DB::connection('mysql')->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Carton yang dipilih sudah punya Export Plan yang BERBEDA-BEDA. Tidak bisa digabung -- lepaskan dulu dari plan masing-masing.',
                ], 422);
            }
            $newExportpk = $distinctExportpks->first();

            // GANTI -- FIX UTAMA: kalau mode EDIT (ada $editingBundlepk), cari
            // bundle-nya lewat ID -- BUKAN lewat nama teks lagi. Ini penting
            // karena admin BISA mengubah nama/nomor Carton Besar saat edit --
            // kalau masih cari-by-nama, rename akan dikira "bundle baru" dan
            // bikin baris carton_bundle duplikat, bukan meng-update yang lama.
            $existingBundle = $editingBundlepk
                ? $db->table('carton_bundle')->where('bundlepk', $editingBundlepk)->lockForUpdate()->first()
                : $db->table('carton_bundle')->where('bundle_carton', $validated['bundle_carton'])->lockForUpdate()->first();

            if ($editingBundlepk && !$existingBundle) {
                DB::connection('mysql')->rollBack();
                return response()->json(['icon' => 'warning', 'title' => 'Carton Besar yang diedit tidak ditemukan (mungkin sudah dihapus).'], 422);
            }

            // BARU -- validasi: kalau EDIT dan admin ganti nama, pastikan nama
            // baru itu TIDAK bentrok dengan bundle LAIN (bundlepk berbeda).
            if ($editingBundlepk) {
                $nameCollision = $db->table('carton_bundle')
                    ->where('bundle_carton', $validated['bundle_carton'])
                    ->where('bundlepk', '<>', $editingBundlepk)
                    ->exists();
                if ($nameCollision) {
                    DB::connection('mysql')->rollBack();
                    return response()->json([
                        'icon'  => 'warning',
                        'title' => "No Carton Besar <b>{$validated['bundle_carton']}</b> sudah dipakai Carton Besar lain.",
                    ], 422);
                }
            }

            // Tolak kalau ADA packpk yang SUDAH tergabung bundle LAIN (beda
            // dari carton besar yang sedang dituju/diedit).
            $conflictRow = $packRows->first(function ($r) use ($existingBundle) {
                if (empty($r->bundlepk)) return false;
                return !$existingBundle || (int) $r->bundlepk !== (int) $existingBundle->bundlepk;
            });
            if ($conflictRow) {
                $conflictBundle = $db->table('carton_bundle')->where('bundlepk', $conflictRow->bundlepk)->first();
                DB::connection('mysql')->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => "Carton <b>{$conflictRow->carton}</b> sudah tergabung dalam Carton Besar <b>" . ($conflictBundle->bundle_carton ?? '-') . "</b> lain.",
                ], 422);
            }

            if ($existingBundle) {
                if ($existingBundle->exportpk && $newExportpk && (int) $existingBundle->exportpk !== (int) $newExportpk) {
                    DB::connection('mysql')->rollBack();
                    return response()->json([
                        'icon'  => 'warning',
                        'title' => "Carton Besar <b>{$validated['bundle_carton']}</b> sudah di-plan ke Export lain, tidak bisa digabung dengan carton yang Export-nya berbeda.",
                    ], 422);
                }

                $bundlepk = $existingBundle->bundlepk;
                $resolvedExportpk = $existingBundle->exportpk ?: $newExportpk;
                $resolvedContpk   = $existingBundle->contpk ?: optional($packRows->first(fn ($r) => !empty($r->contpk)))->contpk;
                $resolvedPart     = $existingBundle->part ?: optional($packRows->first(fn ($r) => !empty($r->part)))->part;

                $db->table('carton_bundle')->where('bundlepk', $bundlepk)->update([
                    'bundle_carton' => $validated['bundle_carton'], // BARU -- ikut di-update (mendukung rename saat edit)
                    'bundle_nobar'  => $validated['bundle_nobar'] ?? $existingBundle->bundle_nobar,
                    'nw'            => $validated['nw'] ?? $existingBundle->nw,
                    'gw'            => $validated['gw'] ?? $existingBundle->gw,
                    'panjang'       => $validated['panjang'] ?? $existingBundle->panjang,
                    'lebar'         => $validated['lebar'] ?? $existingBundle->lebar,
                    'tinggi'        => $validated['tinggi'] ?? $existingBundle->tinggi,
                    'keterangan'    => $validated['keterangan'] ?? $existingBundle->keterangan,
                    'exportpk'      => $resolvedExportpk,
                    'contpk'        => $resolvedContpk,
                    'part'          => $resolvedPart,
                ]);
            } else {
                $bundlepk = $db->table('carton_bundle')->insertGetId([
                    'bundle_carton' => $validated['bundle_carton'],
                    'bundle_nobar'  => $validated['bundle_nobar'] ?? null,
                    'nw'            => $validated['nw'] ?? null,
                    'gw'            => $validated['gw'] ?? null,
                    'panjang'       => $validated['panjang'] ?? null,
                    'lebar'         => $validated['lebar'] ?? null,
                    'tinggi'        => $validated['tinggi'] ?? null,
                    'keterangan'    => $validated['keterangan'] ?? null,
                    'exportpk'      => $newExportpk,
                    'contpk'        => $newExportpk ? data_get($packRows->first(fn ($r) => (int) $r->exportpk === (int) $newExportpk), 'contpk') : null,
                    'part'          => $newExportpk ? data_get($packRows->first(fn ($r) => (int) $r->exportpk === (int) $newExportpk), 'part') : null,
                    'created_by'    => session('guserpk'),
                ]);
            }

            // BARU -- FIX UTAMA (mode EDIT): carton yang SEBELUMNYA ada di
            // bundle ini tapi SEKARANG tidak ikut lagi (di-uncheck/dihapus
            // admin dari daftar) -- LEPASKAN sepenuhnya: bundlepk DAN
            // exportpk/contpk/part-nya, supaya carton itu kembali jadi carton
            // biasa (tidak nyangkut ke Export Plan yang tadinya hanya
            // dititipkan dari bundle).
            if ($editingBundlepk) {
                $originalMemberPackpks = $db->table('pack')->where('bundlepk', $editingBundlepk)->pluck('packpk');
                $removedPackpks = $originalMemberPackpks->diff($packpks)->values();
                if ($removedPackpks->isNotEmpty()) {
                    $db->table('pack')->whereIn('packpk', $removedPackpks)->update([
                        'bundlepk' => null,
                        'exportpk' => null,
                        'contpk'   => null,
                        'part'     => null,
                    ]);
                }
            }

            $bundleRow = $db->table('carton_bundle')->where('bundlepk', $bundlepk)->first();

            $db->table('pack')->whereIn('packpk', $packpks)->update(['bundlepk' => $bundlepk]);

            if ($bundleRow->exportpk) {
                $db->table('pack')->where('bundlepk', $bundlepk)->update([
                    'exportpk' => $bundleRow->exportpk,
                    'contpk'   => $bundleRow->contpk,
                    'part'     => $bundleRow->part,
                ]);
            }

            DB::connection('mysql')->commit();

            $totalCarton = $packRows->pluck('carton')->unique()->count();
            $pesan = $editingBundlepk
                ? "Carton Besar <b>{$validated['bundle_carton']}</b> berhasil diperbarui, {$totalCarton} carton kecil tergabung."
                : "Carton Besar <b>{$validated['bundle_carton']}</b> berhasil dibuat/diperbarui, {$totalCarton} carton kecil tergabung.";

            return response()->json(['icon' => 'success', 'title' => $pesan]);
        } catch (\Throwable $e) {
            DB::connection('mysql')->rollBack();
            return response()->json(['icon' => 'error', 'title' => 'Gagal menggabungkan carton.', 'error' => $e,], 500);
        }
    }

    public function bundleDetail(Request $request)
    {
        $bundlepk = (int) $request->query('bundlepk');
        $db = DB::connection('mysql');
    
        $bundle = $db->table('carton_bundle')->where('bundlepk', $bundlepk)->first();
        if (!$bundle) {
            return response()->json(['error' => 'Carton Besar tidak ditemukan.'], 404);
        }
    
        $memberRows = $db->table('pack')->where('bundlepk', $bundlepk)->get();
    
        $members = $memberRows->groupBy('carton')->map(function ($rows) {
            $first = $rows->first();
            return [
                'carton'  => $first->carton,
                'POno'    => $first->POno,
                'OP'      => $first->OP,
                'packpks' => $rows->pluck('packpk')->values(),
            ];
        })->values();
    
        return response()->json([
            'bundle'  => $bundle,
            'members' => $members,
        ]);
    }
}