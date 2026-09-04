<?php

namespace App\Http\Controllers\Packing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackingController extends Controller
{
    public function index()
    {
        $gabung = DB::table('gabung')
            ->select('gabungpk', 'keterangan')
            ->orderBy('gabungpk')
            ->get();

        return view('menu.packing.index', compact('gabung'));
    }

    public function getList(Request $request)
    {
        $page   = max(1, (int) $request->page);
        $rows   = max(1, (int) $request->rows);
        $offset = ($page - 1) * $rows;

        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        $isSuper = session('guserpk') == 34;

        if ($isSuper) {
            $rowsAndon = $this->fetchAll('mysql_andon', 1, $request);
            $rowsMysql = $this->fetchAll('mysql', 2, $request);
            $combined  = $rowsAndon->concat($rowsMysql);
        } else {
            $mif        = session('pos') == 1 ? 1 : 2;
            $connection = session('pos') == 1 ? 'mysql_andon' : 'mysql';
            $combined   = $this->fetchAll($connection, $mif, $request);
        }

        $aggregated = $this->aggregateByPoOp($combined)
            ->when(
                $sortDir === 'asc',
                fn ($c) => $c->sortBy('OP'),
                fn ($c) => $c->sortByDesc('OP')
            )
            ->values();

        $total = $aggregated->count();
        $data  = $aggregated->slice($offset, $rows)->values();

        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
        }

        $summary = [
            'qty'                  => $data->sum('qty'),
            'packing_qty_plan'     => $data->sum('packing_qty_plan'),
            'packing_qty'          => $data->sum('packing_qty'),
            'packing_qty_balance'  => $data->sum('packing_qty_balance'),
            'ctn'                  => $data->sum('ctn'),
            'packing_ctn'          => $data->sum('packing_ctn'),
            'ctn_balance'          => $data->sum('ctn_balance'),
        ];

        return response()->json([
            'total'   => $total,
            'rows'    => $data,
            'summary' => $summary,
        ]);
    }


    private function aggregateByPoOp($collection)
    {
        return $collection
            ->groupBy(fn ($row) => $row->POno . '|' . $row->OP)
            ->map(function ($group) {
                $representative = clone $group->sortByDesc('popk')->first();

                $representative->qty                = (int) $group->sum('qty');
                $representative->transfer           = (int) $group->sum('transfer');
                $representative->checked_qty        = (int) $group->sum('checked_qty');
                $representative->packing_qty_plan   = (int) $group->sum('packing_qty_plan');
                $representative->packing_qty        = (int) $group->sum('packing_qty');
                $representative->ctn                = (int) $group->sum('ctn');
                $representative->packing_ctn        = (int) $group->sum('packing_ctn');

                $representative->packing_qty_balance = $representative->packing_qty - $representative->packing_qty_plan;
                $representative->ctn_balance         = $representative->packing_ctn - $representative->ctn;
                $representative->balance             = $representative->transfer
                    - $representative->checked_qty
                    - $representative->packing_qty;

                // Kalau SALAH SATU baris di dalam grup sudah Complete/Segel,
                // anggap grup ini Complete/ada segel juga (representatif
                // untuk badge status di index).
                $representative->segel_complete = (int) $group->max('segel_complete');
                $representative->status         = (int) $group->max('status');

                return $representative;
            })
            ->values();
    }

    public function detailByPoOp(Request $request)
    {
        $validated = $request->validate([
            'po'  => 'nullable',   
            'op'  => 'required',
            'mif' => 'nullable',
        ]);
    
        $po = $validated['po'] ?? null;
        $op = $validated['op'];
    
        $mif        = $validated['mif'] ?? session('pos');
        $connection = $this->resolveConnection($mif);
    
        $rows = $this->fetchAll($connection, (int) $mif, $request, $po, $op)
            ->values();
    
        foreach ($rows as $i => $row) {
            $row->no = $i + 1;
        }
    
        $summary = [
            'qty'                  => $rows->sum('qty'),
            'packing_qty_plan'     => $rows->sum('packing_qty_plan'),
            'packing_qty'          => $rows->sum('packing_qty'),
            'packing_qty_balance'  => $rows->sum('packing_qty_balance'),
            'ctn'                  => $rows->sum('ctn'),
            'packing_ctn'          => $rows->sum('packing_ctn'),
            'ctn_balance'          => $rows->sum('ctn_balance'),
        ];
    
        return response()->json([
            'total'   => $rows->count(),
            'rows'    => $rows,
            'summary' => $summary,
        ]);
    }

    private function fetchAll(string $connection, int $mif, Request $request, ?string $po = null, ?string $op = null)
    {
        $db = DB::connection($connection);

        $transfer = $db->table('bj')
            ->selectRaw('popk, SUM(pcs) as transfer')
            ->where('check2', 0)
            ->groupBy('popk');

        $checked = $db->table('bj')
            ->selectRaw('popk, SUM(pcs) as checked_qty')
            ->where('check2', 1)
            ->groupBy('popk');

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

        $packStatus = $db->table('pack')
            ->selectRaw('popk, MAX(status) as status')
            ->groupBy('popk');

        $ctnGabung1 = $db->table('pack')
            ->selectRaw("POno, OP, customer, COUNT(*) as ctn")
            ->where('status', '>=', 4)
            ->groupBy('POno', 'OP', 'customer');

        $ctnGabung4 = $db->table('pack')
            ->selectRaw("POno, OP, material, COUNT(*) as ctn")
            ->where('status', '>=', 4)
            ->groupBy('POno', 'OP', 'material');

        $ctnGabung6 = $db->table('pack')
            ->selectRaw("POno, OP, material, MAX(carton) as carton")
            ->where('status', '>=', 4)
            ->groupBy('POno', 'OP', 'material');

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

        $query = $db->table('bj')
            ->join('po', 'po.popk', '=', 'bj.popk')
            ->leftJoinSub($transfer, 'trf', fn($join) => $join->on('bj.popk', '=', 'trf.popk'))
            ->leftJoinSub($checked, 'chk', fn($join) => $join->on('bj.popk', '=', 'chk.popk'))
            ->leftJoinSub($packing, 'pk', fn($join) => $join->on('bj.popk', '=', 'pk.popk'))
            ->leftJoinSub($packingPlan, 'pkp', fn($join) => $join->on('bj.popk', '=', 'pkp.popk'))
            ->leftJoinSub($packingCtn, 'pctn', fn($join) => $join->on('bj.popk', '=', 'pctn.popk'))
            ->leftJoinSub($packStatus, 'pst', fn($join) => $join->on('bj.popk', '=', 'pst.popk'))
            ->leftJoinSub($ctnGabung1, 'g1', function ($join) {
                $join->on('po.POno', '=', 'g1.POno')
                    ->on('po.OP', '=', 'g1.OP')
                    ->on('po.customer', '=', 'g1.customer');
            })
            ->leftJoinSub($ctnGabung4, 'g4', function ($join) {
                $join->on('po.POno', '=', 'g4.POno')
                    ->on('po.OP', '=', 'g4.OP')
                    ->on('po.material', '=', 'g4.material');
            })
            ->leftJoinSub($ctnGabung6, 'g6', function ($join) {
                $join->on('po.POno', '=', 'g6.POno')
                    ->on('po.OP', '=', 'g6.OP')
                    ->on('po.material', '=', 'g6.material');
            })
            ->leftJoinSub($segel, 'sgl', fn($join) => $join->on('bj.popk', '=', 'sgl.popk'))
            ->leftJoin('line', 'line.linepk', '=', 'bj.linepk')
            ->where('bj.check2', 0)
            ->where('po.qty', '>', 0)
            ->where('po.OP', '<>', '')
            ->where('po.mif', $mif)
            ->whereNotNull('trf.transfer')
            ->where('trf.transfer', '>', 0);

        // Filter PO+OP di SQL, kalau diminta (dipakai modal detail).
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
        }

        $query->selectRaw("
                po.popk,
                po.sts,
                po.gabung,
                po.shipdate1,
                po.shipdate2,
                po.customer,
                po.season,
                po.POno,
                po.OP,
                po.poref,
                po.mif,
                po.buyer,
                po.style,
                po.material,
                po.secsz,
                po.qty,
                po.silhouette,

                CASE
                    WHEN po.gabung = 1 THEN COALESCE(g1.ctn,0)
                    WHEN po.gabung = 4 THEN COALESCE(g4.ctn,0)
                    WHEN po.gabung = 6 THEN COALESCE(g6.carton,0)
                    ELSE po.ctn
                END as ctn,

                GROUP_CONCAT(
                    DISTINCT TRIM(SUBSTRING(line.linenm,6,3))
                    ORDER BY line.linenm
                    SEPARATOR ';'
                ) AS linenm,

                COALESCE(trf.transfer,0) AS transfer,
                COALESCE(chk.checked_qty,0) AS checked_qty,

                COALESCE(pkp.packing_qty_plan,0) AS packing_qty_plan,
                COALESCE(pk.packing_qty,0)       AS packing_qty,
                (COALESCE(pk.packing_qty,0) - COALESCE(pkp.packing_qty_plan,0)) AS packing_qty_balance,

                COALESCE(pctn.packing_ctn,0) AS packing_ctn,

                -- FIX: sebelumnya ctn_balance TIDAK PERNAH dihitung di sini
                -- sama sekali -- makanya kosong/0 terus di modal (yang tidak
                -- lewat aggregateByPoOp()). Sekarang dihitung langsung di SQL,
                -- pakai CASE yang SAMA seperti kolom `ctn` di atas (alias tidak
                -- bisa dipakai ulang di SELECT list yang sama, jadi diulang).
                (
                    COALESCE(pctn.packing_ctn,0)
                    - CASE
                        WHEN po.gabung = 1 THEN COALESCE(g1.ctn,0)
                        WHEN po.gabung = 4 THEN COALESCE(g4.ctn,0)
                        WHEN po.gabung = 6 THEN COALESCE(g6.carton,0)
                        ELSE po.ctn
                    END
                ) AS ctn_balance,

                COALESCE(pst.status, 0)           AS status,
                COALESCE(sgl.segel_complete, 0)   AS segel_complete,
                sgl.segel_partial_no              AS segel_partial_no,

                (
                    COALESCE(trf.transfer,0)
                    - COALESCE(chk.checked_qty,0)
                    - COALESCE(pk.packing_qty,0)
                ) AS balance
            ");

        if ($request->fin) {
            $query->having('packing_qty', '>', 0);
        }

        $query->groupBy(
            'po.popk', 'po.sts', 'po.gabung', 'po.shipdate1', 'po.shipdate2',
            'po.customer', 'po.season', 'po.POno', 'po.OP', 'po.poref',
            'po.mif', 'po.buyer', 'po.style', 'po.material', 'po.secsz',
            'po.qty', 'po.silhouette', 'po.ctn',
            'trf.transfer', 'chk.checked_qty', 'pk.packing_qty',
            'pkp.packing_qty_plan', 'pctn.packing_ctn', 'pst.status',
            'g1.ctn', 'g4.ctn', 'g6.carton',
            'sgl.segel_complete', 'sgl.segel_partial_no',
        );

        $this->applyListFilters($query, $request);

        return $query->orderByDesc('po.OP')->get();
    }

    private function resolveConnection($mif): string
    {
        return ((int) $mif) === 1 ? 'mysql_andon' : 'mysql';
    }

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
            $query->whereYear('bj.tanggal', (int) $request->year);
        }
    }

    public function inputPacking($popk, Request $request)
    {
        $mif        = $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);

        $breakdown = $this->getBreakdownData($popk, $connection);
        extract($breakdown);

        $cr   = $request->cr;
        $page = $request->page ?? 1;
        $size = $request->size ?? '';

        $gabungList = DB::connection($connection)->table('gabung')->orderBy('gabungpk')->get();

        $lines = DB::connection($connection)->table('line')
            ->where('mif', $mif)->whereNull('stsbar')
            ->orderBy('linenm')->get();

        $whereSize = $size ? ['urut' => $size] : [];
        $hsl = DB::connection($connection)->table('pack')
            ->where('popk', $popk)
            ->when($cr, fn($q) => $q->where('carton', $cr))
            ->where('status', 4)
            ->when($whereSize, fn($q) => $q->where($whereSize))
            ->first();

        $perPage    = 2;
        $packOffset = ($page - 1) * $perPage;

        $packQuery = DB::connection($connection)->table('pack')
            ->where('popk', $popk)
            ->when($cr,   fn($q) => $q->where('carton', $cr))
            ->when($size, fn($q) => $q->where('urut', $size));

        $packTotal = $packQuery->count();
        $packPages = ceil($packTotal / $perPage);

        $details = (clone $packQuery)
            ->orderByDesc('urut')->orderByDesc('packpk')
            ->offset($packOffset)->limit($perPage)
            ->get();

        return view('menu.packing.input', compact(
            'dt', 'dt2', 'dt3', 'hsl', 'summary', 'activeSizes', 'lines', 'gabungList',
            'orderQty', 'readyQty', 'planQty', 'transQty',
            'diffPackQty', 'diffTransQty', 'totDiffTrans', 'totDiffPack',
            'tctnp', 'tctna', 'balanceCtn',
            'details', 'packTotal', 'packPages',
            'cr', 'size', 'page', 'popk',
            'mif', 'connection'
        ));
    }

    private function getBreakdownData($popk, string $connection)
    {
        $db = DB::connection($connection);

        $dt = $db->table('po')->where('popk', $popk)->first();
        if (!$dt) abort(404);

        $activeSizes = [];
        for ($i = 1; $i <= 40; $i++) {
            $sz = $dt->{"size$i"} ?? null;
            if (!empty($sz)) $activeSizes[$i] = $sz;
        }

        $dt2 = $db->table('po')
            ->selectRaw(
                "ket, wh, sap1, sap2, shipdate1, shipdate2, ctn,
                customer, season, POno, OP, buyer, style, material,
                silhouette, secsz, sts, gabung, poref,
                SUM(qty) as qty, " .
                    collect(range(1, 40))->map(fn($i) => "SUM(qty$i) as qty$i")->implode(', ') . ', ' .
                    collect(range(1, 40))->map(fn($i) => "size$i")->implode(', ')
            )
            ->where('popk', $popk)
            ->groupBy('popk')
            ->first();

        $dt3 = $db->table('pack')
            ->selectRaw(
                "SUM(pcs) as pcs, SUM(jmlpcs) as pack, SUM(pcsp) as pcsp, " .
                    collect(range(1, 40))->map(fn($i) => "SUM(qty$i) as qty$i")->implode(', ') . ', ' .
                    collect(range(1, 40))->map(fn($i) => "SUM(qtyp$i) as qtyp$i")->implode(', ')
            )
            ->where('popk', $popk)
            ->first();

        $summary = $db->table('bj')
            ->selectRaw(
                "popk, SUM(pcs) as pcs, " .
                    collect(range(1, 40))->map(fn($i) => "SUM(qty$i) as qty$i")->implode(', ')
            )
            ->where('popk', $popk)
            ->first();

        $orderQty       = [];
        $readyQty       = [];
        $planQty        = [];
        $transQty       = [];
        $diffTransQty   = [];
        $diffPackQty    = [];

        for ($i = 1; $i <= 40; $i++) {
            $orderQty[$i]     = $dt2->{"qty$i"} ?? 0;
            $readyQty[$i]     = $dt3->{"qty$i"} ?? 0;
            $planQty[$i]      = $dt3->{"qtyp$i"} ?? 0;
            $transQty[$i]     = $summary->{"qty$i"} ?? 0;

            $diffTransQty[$i] = $transQty[$i] - $orderQty[$i];
            $diffPackQty[$i]  = $readyQty[$i] - $planQty[$i];
        }

        $totOrder = array_sum($orderQty);
        $totDiffTrans = ($summary->pcs ?? 0) - $totOrder;
        $totDiffPack = ($dt3->pcs ?? 0) - ($dt3->pcsp ?? 0);

        $tctnp      = $dt->ctn ?? 0;
        $tctna      = $dt3->pack ?? 0;
        $balanceCtn = $tctna - $tctnp;

        return compact(
            'dt', 'dt2', 'dt3', 'summary', 'activeSizes',
            'orderQty', 'readyQty', 'planQty', 'transQty',
            'diffTransQty', 'diffPackQty', 'totDiffTrans', 'totDiffPack',
            'tctnp', 'tctna', 'balanceCtn'
        );
    }

    public function breakdownSummary($popk, Request $request)
    {
        $mif        = $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);

        return view('menu.packing.partials.breakdown_summary', $this->getBreakdownData($popk, $connection));
    }

    public function listDetail($popk, Request $request)
    {
        $cr   = $request->cr;
        $size = $request->size;

        $page = (int) $request->input('page', 1);
        $rows = (int) $request->input('rows', 50);

        $search = trim($request->search);

        $query = DB::table('pack')
            ->where('popk', $popk)
            ->when($cr, fn($q) => $q->where('carton', $cr))
            ->when($size, fn($q) => $q->where("qtyp{$size}", '>', 0))
            ->when($search, function ($q) use ($search) {

                $q->where(function ($x) use ($search) {

                    $x->where('nobar', 'like', "%{$search}%")
                        ->orWhere('carton', 'like', "%{$search}%");
                });
            });

        $total = $query->count();

        $data = (clone $query)
            ->orderByDesc('urut')
            ->orderByDesc('packpk')
            ->offset(($page - 1) * $rows)
            ->limit($rows)
            ->get();

        foreach ($data as $index => $row) {

            $row->no = (($page - 1) * $rows) + $index + 1;

            $row->balance =
                ($row->pcs ?? 0)
                - ($row->pcsp ?? 0);
        }

        return response()->json([
            'total' => $total,
            'rows'  => $data
        ]);
    }

    public function saveHeader(Request $request)
    {
        $validated = $request->validate([
            'popk'  => 'required',
            'pono'  => 'required|string',
            'ship1' => 'nullable|date',
            'ship2' => 'nullable|date',
            'sap1'  => 'nullable|string',
            'sap2'  => 'nullable|string',
            'wh'    => 'nullable|string',
            'ket'   => 'nullable|string',
        ]);

        $popk = $validated['popk'];

        $dtpo = DB::table('po')->where('popk', $popk)->first();
        if (!$dtpo) {
            return response()->json([
                'icon'  => 'error',
                'title' => 'Data PO tidak ditemukan',
            ], 404);
        }

        DB::table('po')
            ->where('popk', $popk)
            ->update([
                'POno'      => $validated['pono'],
                'shipdate1' => $validated['ship1'] ?: null,
                'shipdate2' => $validated['ship2'] ?: null,
                'sap1'      => $validated['sap1'] ?? null,
                'sap2'      => $validated['sap2'] ?? null,
                'wh'        => $validated['wh'] ?? null,
                'ket'       => $validated['ket'] ?? null,
            ]);

        $dtpo = DB::table('po')->where('popk', $popk)->first();

        $syncData = [
            'OP'       => $dtpo->OP,
            'POno'     => $dtpo->POno,
            'customer' => $dtpo->customer,
            'material' => $dtpo->material,
            'secsz'    => $dtpo->secsz,
        ];

        DB::table('pack')->where('popk', $popk)->update($syncData);
        DB::table('bj')->where('popk', $popk)->update($syncData);
        DB::table('ship')->where('popk', $popk)->update($syncData);

        return response()->json([
            'icon'  => 'success',
            'title' => 'Informasi PO berhasil disimpan',
            'data'  => $dtpo,
        ]);
    }

    public function store(Request $request)
    {
        $errors = [];

        if (blank($request->nocar)) {
            $errors[] = 'No Carton wajib diisi.';
        }

        $totalp2 = 0;
        for ($i = 1; $i <= 40; $i++) {
            if ((int)$request->input("qty{$i}p") > 0) {
                $totalp2++;
            }
        }

        if ($totalp2 == 0) {
            $errors[] = 'Minimal satu Size Plan harus diisi.';
        }

        if (!empty($errors)) {
            return response()->json([
                'icon'  => 'warning',
                'title' => implode('<br>', $errors),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $popk = $request->popk;
            $dtpo = DB::table('po')->where('popk', $popk)->first();
            if (!$dtpo) abort(404);

            $totalp  = 0;
            $total   = 0;
            $urut    = 0;
            $qtyp_values = [];
            $qty_values  = [];
            for ($i = 1; $i <= 40; $i++) {
                $p = $request->input("qty{$i}p");
                $a = $request->input("qty{$i}");
                $qtyp_values[$i] = ($p === '' || $p === null) ? null : (int)$p;
                $qty_values[$i]  = ($a === '' || $a === null) ? null : (int)$a;
                $totalp += (int)($qtyp_values[$i] ?? 0);
                $total  += (int)($qty_values[$i] ?? 0);
                if (($qtyp_values[$i] ?? 0) > 0) {
                    $urut = $i;
                }
            }
            $totalp2 = count(array_filter($qtyp_values, fn($v) => $v > 0));
            $packpk = (int) $request->packpk;
            $cr     = $request->cr;

            // ===============================
            // Validasi Qty Actual <= Transfer
            // ===============================
            $existing = null;

            if ($packpk > 0) {
                $existing = DB::table('pack')
                    ->where('packpk', $packpk)
                    ->first();
            }

            for ($i = 1; $i <= 40; $i++) {

                $actualBaru = (int)($qty_values[$i] ?? 0);

                if ($actualBaru <= 0) {
                    continue;
                }

                $actualLama = (int)($existing->{"qty{$i}"} ?? 0);

                $ready = (int) DB::table('pack')
                    ->where('popk', $popk)
                    ->sum("qty{$i}");

                $transfer = (int) DB::table('bj')
                    ->where('popk', $popk)
                    ->sum("qty{$i}");

                $totalReady = ($ready - $actualLama) + $actualBaru;

                if ($totalReady > $transfer) {

                    DB::rollBack();

                    $sizeName = $dtpo->{"size{$i}"} ?? "Size {$i}";
                    $maksimal = max(0, $transfer - ($ready - $actualLama));

                    return response()->json([
                        'icon'  => 'warning',
                        'title' =>
                            "Qty Actual untuk size <b>{$sizeName}</b> tidak dapat disimpan.<br><br>" .
                            "Polibag Qty : <b>{$transfer}</b><br>" .
                            "Pack Qty : <b>{$ready}</b><br>" .
                            "Qty Sebelumnya : <b>{$actualLama}</b><br>" .
                            "Qty Diinput : <b>{$actualBaru}</b><br>" .
                            "Total Ready : <b>{$totalReady}</b><br><br>" .
                            "<span class='text-danger'>Maksimal Qty yang masih dapat diinput adalah <b>{$maksimal}</b>.</span>"
                    ], 422);
                }
            }

            if ($packpk > 0) {

                $existing = DB::table('pack')->where('packpk', $packpk)->first();
                $ket2  = $request->ket2;
                $urut2 = null;
                if ($ket2 == 21 || $ket2 == 22) {
                    $urut2 = $ket2;
                    $ket2  = '';
                    DB::table('pack')->where('packpk', $packpk)->update(['urut' => $urut2]);
                }
                if ($total > 0) {
                    $base = [
                        'carton'      => $request->nocar,
                        'nobar'       => $request->nobar,
                        'OP'          => $request->op,
                        'POno'        => $request->pono,
                        'popk'        => $popk,
                        'customer'    => $request->customer,
                        'material'    => $request->material,
                        'nw'          => $request->nw,
                        'gw'          => $request->gw,
                        'meas'        => $request->meas,
                        'secsz'       => $dtpo->secsz,
                        'keterangan'  => $ket2,
                        'pcs'         => $total,
                        'tanggal'     => now(),
                        'waktu'       => now(),
                        'status'      => 4,
                    ];
                    for ($i = 1; $i <= 40; $i++) {
                        $base["qty$i"] = $qty_values[$i];
                    }
                    if (($existing->pcsp ?? 0) == 0) {
                        DB::table('pack')->where('packpk', $packpk)->update($base);
                    } else {
                        $base['jmlpcs'] = 1;
                        $base['pcsp']   = $totalp;
                        for ($i = 1; $i <= 40; $i++) {
                            $base["qtyp$i"] = $qtyp_values[$i];
                        }
                        DB::table('pack')->where('packpk', $packpk)->update($base);
                    }
                } else {
                    if ($totalp == 0) {
                        $newCtn = ($dtpo->ctn ?? 0) - 1;
                        DB::table('po')->where('popk', $popk)->update(['ctn' => $newCtn]);
                        DB::table('pack')->where('packpk', $packpk)->update(['pcsp' => 0]);
                    } else {
                        if (($existing->pcsp ?? 0) == 0) {
                            $newCtn = ($dtpo->ctn ?? 0) + 1;
                            DB::table('po')->where('popk', $popk)->update(['ctn' => $newCtn]);
                        }
                        $upd = [
                            'carton'     => $request->nocar,
                            'nobar'      => $request->nobar,
                            'OP'         => $request->op,
                            'POno'       => $request->pono,
                            'popk'       => $popk,
                            'customer'   => $request->customer,
                            'material'   => $request->material,
                            'nw'         => $request->nw,
                            'gw'         => $request->gw,
                            'meas'       => $request->meas,
                            'secsz'      => $dtpo->secsz,
                            'keterangan' => $ket2,
                            'pcsp'       => $totalp,
                            'pcs'        => $total,
                            'jmlpcs'     => 0,
                            'tanggal'    => now(),
                            'waktu'      => now(),
                            'status'     => 4,
                        ];
                        for ($i = 1; $i <= 40; $i++) {
                            $upd["qtyp$i"] = $qtyp_values[$i];
                            $upd["qty$i"]  = $qty_values[$i];
                        }
                        DB::table('pack')->where('packpk', $packpk)->update($upd);
                    }
                }
            } else {
                $nocar = trim((string) $request->nocar);

                if ($nocar !== '') {
                    if ($totalp2 > 1) {
                        $newCtn = ($dtpo->ctn ?? 0) + 1;
                        DB::table('po')->where('popk', $popk)->update(['ctn' => $newCtn]);
                        $row = $this->buildPackRow($request, $dtpo, $popk, $nocar, $totalp, $qtyp_values, $qty_values, 21);
                        DB::table('pack')->insert($row);
                    } elseif ($totalp2 == 1) {
                        $singleIdx = 0;
                        $singleQty = 0;
                        foreach ($qtyp_values as $idx => $val) {
                            if ($val > 0) {
                                $singleIdx = $idx;
                                $singleQty = $val;
                                break;
                            }
                        }
                        $qtyField   = "qty{$singleIdx}";
                        $dtpoQtyCol = DB::table('po')
                            ->where('popk', $popk)
                            ->value($qtyField) ?? 0;
                        if ((int) $request->check == 1 && $dtpoQtyCol > 0) {
                            $bagi  = $dtpoQtyCol / $singleQty;
                            $kali  = (int) floor($bagi);
                            $sisa  = ($bagi - $kali) * $singleQty;
                            $kali4 = $sisa > 0 ? 1 : 0;
                            $newCtn = ($dtpo->ctn ?? 0) + $kali + $kali4;
                            DB::table('po')->where('popk', $popk)->update(['ctn' => $newCtn]);
                            $nobarBase = substr($request->nobar, 0, -4);

                            for ($offset = 0; $offset <= ($kali - 1); $offset++) {
                                $cartonValue = $this->incrementCartonNumber($nocar, $offset);
                                $nobarFull   = $nobarBase . sprintf('%04d', $offset + 1);

                                $rowFull = $this->buildPackRowSingleSize(
                                    $request,
                                    $dtpo,
                                    $popk,
                                    $cartonValue,
                                    $nobarFull,
                                    $singleIdx,
                                    $singleQty,
                                    $urut
                                );
                                DB::table('pack')->insert($rowFull);
                            }
                            if ($sisa > 0) {
                                $cartonSisa = $this->incrementCartonNumber($nocar, $kali);
                                $nobarSisa  = $nobarBase . sprintf('%04d', $kali + 1);

                                $rowSisa = $this->buildPackRowSingleSize(
                                    $request,
                                    $dtpo,
                                    $popk,
                                    $cartonSisa,
                                    $nobarSisa,
                                    $singleIdx,
                                    (int) $sisa,
                                    $urut
                                );
                                DB::table('pack')->insert($rowSisa);
                            }
                        } else {

                            $newCtn = ($dtpo->ctn ?? 0) + 1;
                            DB::table('po')->where('popk', $popk)->update(['ctn' => $newCtn]);
                            $row = $this->buildPackRow($request, $dtpo, $popk, $nocar, $totalp, $qtyp_values, $qty_values, $urut);
                            DB::table('pack')->insert($row);
                        }
                    }
                }
            }

            $dtpo = DB::table('po')->where('popk', $popk)->first();

            $syncData = [
                'OP'       => $dtpo->OP,
                'POno'     => $dtpo->POno,
                'customer' => $dtpo->customer,
                'material' => $dtpo->material,
                'secsz'    => $dtpo->secsz,
            ];
            DB::table('pack')->where('popk', $popk)->update($syncData);
            DB::table('bj')->where('popk', $popk)->update($syncData);
            DB::table('ship')->where('popk', $popk)->update($syncData);

            DB::commit();

            return response()->json([
                'icon'  => 'success',
                'title' => 'Data packing berhasil disimpan',
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menyimpan data packing'
                // 'title' => $e->getMessage(), // debug
            ], 500);
        }
    }

    /**
     * Naikkan nomor carton sebanyak $offset, sambil tetap AMAN untuk nilai
     * string non-numerik (misal "AB001").
     *
     * - Kalau carton diakhiri angka ("AB001", "001", dst) -> cuma bagian
     *   angka di akhir yang di-increment, prefix di depannya (kalau ada)
     *   dipertahankan, dan lebar digit (leading zero) tetap dijaga.
     *   Contoh: incrementCartonNumber("AB001", 2) -> "AB003".
     * - Kalau carton TIDAK punya angka di akhir sama sekali (misal "ABC")
     *   -> angka urut ditambahkan polos di belakang.
     *   Contoh: incrementCartonNumber("ABC", 2) -> "ABC2".
     */
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

    private function buildPackRow(
        Request $request,
        object  $dtpo,
        int     $popk,
        string  $carton,
        int     $pcsp,
        array   $qtyp,
        array   $qty,
        int     $urutVal
    ): array {
        $row = [
            'carton'     => $carton,
            'nobar'      => $request->nobar,
            'OP'         => $request->op,
            'POno'       => $request->pono,
            'popk'       => $popk,
            'customer'   => $request->customer,
            'material'   => $request->material,
            'nw'         => $request->nw,
            'gw'         => $request->gw,
            'meas'       => $request->meas,
            'secsz'      => $dtpo->secsz,
            'keterangan' => $request->ket2,
            'pcsp'       => $pcsp,
            'tanggal'    => now(),
            'waktu'      => now(),
            'status'     => 4,
            'urut'       => $urutVal,
        ];

        for ($i = 1; $i <= 40; $i++) {
            $row["qtyp$i"] = $qtyp[$i];
            $row["qty$i"]  = $qty[$i];
        }

        return $row;
    }

    private function buildPackRowSingleSize(
        Request $request,
        object  $dtpo,
        int     $popk,
        string  $carton,
        string  $nobar,
        int     $sizeIdx,
        int     $sizeQty,
        int     $urutVal
    ): array {
        $row = [
            'carton'     => $carton,
            'nobar'      => $nobar,
            'OP'         => $request->op,
            'POno'       => $request->pono,
            'popk'       => $popk,
            'customer'   => $request->customer,
            'material'   => $request->material,
            'nw'         => $request->nw,
            'gw'         => $request->gw,
            'meas'       => $request->meas,
            'secsz'      => $dtpo->secsz,
            'keterangan' => $request->ket2,
            'pcsp'       => $sizeQty,
            'tanggal'    => now(),
            'waktu'      => now(),
            'status'     => 4,
            'urut'       => $urutVal,
        ];

        for ($i = 1; $i <= 40; $i++) {
            $row["qtyp$i"] = ($i === $sizeIdx) ? $sizeQty : null;
            $row["qty$i"]  = null;
        }

        return $row;
    }

    public function updateOpsi(Request $request)
    {
        DB::table('po')->where('popk', $request->popk)->update(['gabung' => $request->opsi]);

        return response()->json(['success' => true]);
    }

    public function updateCtnNol(Request $request)
    {
        DB::table('po')->where('popk', $request->popk)->update(['ctn' => $request->nol]);

        return response()->json(['success' => true]);
    }

    public function updateCtn(Request $request)
    {
        $request->validate([
            'packpk' => 'required',
        ], [
            'packpk.required' => 'Pilih minimal satu carton.',
        ]);

        DB::beginTransaction();

        try {
            $popk = $request->popk;
            $size = $request->size; // '' = semua size, atau index tertentu

            $ids = array_values(array_filter(explode(',', $request->packpk)));

            if (empty($ids)) {
                DB::rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Pilih minimal satu carton.',
                ], 422);
            }

            $packs = DB::table('pack')
                ->where('popk', $popk)
                ->where('status', 4)
                ->whereIn('packpk', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('packpk');

            $orderedPacks = collect($ids)
                ->map(fn($id) => $packs->get($id))
                ->filter()
                ->values();

            $sizeIndexes = ($size !== '' && $size !== null) ? [(int) $size] : range(1, 40);

            $remaining = [];
            $isFilled  = [];

            foreach ($sizeIndexes as $i) {
                $ready    = (int) DB::table('pack')->where('popk', $popk)->sum("qty{$i}");
                $transfer = (int) DB::table('bj')->where('popk', $popk)->sum("qty{$i}");

                $sumToProcess = 0;
                $isFilled[$i] = [];

                foreach ($orderedPacks as $x) {
                    $plan  = (int) ($x->{"qtyp{$i}"} ?? 0);
                    $exist = (int) ($x->{"qty{$i}"} ?? 0);

                    if ($plan <= 0) {
                        $isFilled[$i][$x->packpk] = null;
                        continue;
                    }

                    if ($exist >= $plan) {
                        $isFilled[$i][$x->packpk] = true;
                    } else {
                        $isFilled[$i][$x->packpk] = false;
                        $sumToProcess += $exist;
                    }
                }

                $remaining[$i] = $transfer - ($ready - $sumToProcess);
            }

            $jumlahUpdate  = 0;
            $jumlahParsial = 0;
            $jumlahGagal   = 0;
            $jumlahSkip    = 0;

            foreach ($orderedPacks as $x) {

                $upd = [];
                $adaPerubahan  = false;
                $adaPartialRow = false;
                $adaGagalRow   = false;

                foreach ($sizeIndexes as $i) {

                    $plan = (int) ($x->{"qtyp{$i}"} ?? 0);
                    if ($plan <= 0) continue;

                    if ($isFilled[$i][$x->packpk] === true) {
                        continue;
                    }

                    $exist = (int) ($x->{"qty{$i}"} ?? 0);
                    $butuh = $plan - $exist;
                    $avail = max(0, $remaining[$i]);

                    if ($avail >= $butuh) {
                        $upd["qty{$i}"] = $plan;
                        $remaining[$i] -= $butuh;
                        $adaPerubahan = true;
                    } elseif ($avail > 0) {
                        $upd["qty{$i}"] = $exist + $avail;
                        $remaining[$i] -= $avail;
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

                DB::table('pack')->where('packpk', $x->packpk)->update($upd);

                $jumlahUpdate++;
                if ($adaPartialRow) $jumlahParsial++;
                if ($adaGagalRow)   $jumlahGagal++;
            }

            DB::commit();

            $pesan = "{$jumlahUpdate} carton berhasil diupdate.";
            if ($jumlahSkip > 0)    $pesan .= " {$jumlahSkip} carton dilewati (sudah penuh / transfer habis).";
            if ($jumlahParsial > 0) $pesan .= " {$jumlahParsial} carton terisi sebagian karena Transfer terbatas.";
            if ($jumlahGagal > 0)   $pesan .= " {$jumlahGagal} carton/size tidak bisa ditambah, Transfer sudah habis.";

            return response()->json([
                'icon'  => $jumlahGagal > 0 ? 'warning' : 'success',
                'title' => $pesan,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal mengupdate Actual Qty Carton.'
                // 'title' => $e->getMessage(), // debug
            ], 500);
        }
    }

    public function deleteActual(Request $request)
    {
        $request->validate([
            'packpk' => 'required',
        ], [
            'packpk.required' => 'Pilih minimal satu carton.'
        ]);

        DB::beginTransaction();

        try {

            $popk = $request->popk;
            $size = $request->size;

            $ids = array_filter(explode(',', $request->packpk));

            $jumlah = 0;

            foreach ($ids as $id) {

                $pack = DB::table('pack')
                    ->where('packpk', $id)
                    ->where('popk', $popk)
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

                        if ($i == $size) {
                            $nilai = null;
                        } else {
                            $nilai = $pack->{"qty{$i}"};
                        }

                        $pcs += (int)($nilai ?? 0);
                    }

                    $update['pcs'] = $pcs;

                } else {

                    for ($i = 1; $i <= 40; $i++) {
                        $update["qty{$i}"] = null;
                    }

                    $update['pcs'] = 0;
                }

                if ($update['pcs'] == 0) {
                    $update['jmlpcs'] = 0;
                } else {
                    $update['jmlpcs'] = 1;
                }

                DB::table('pack')
                    ->where('packpk', $id)
                    ->update($update);

                $jumlah++;
            }

            DB::commit();

            return response()->json([
                'icon'  => 'success',
                'title' => "{$jumlah} carton berhasil dihapus Actual Qty.",
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menghapus Actual Qty Carton.'
                // 'title' => $e->getMessage(), // debug
            ], 500);
        }
    }

    public function deleteMultiple(Request $request)
    {
        $request->validate([
            'packpk' => 'required|string'
        ]);

        $ids = collect(explode(',', $request->packpk))
            ->filter()
            ->map(fn($id) => (int)$id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return response()->json([
                'icon'  => 'warning',
                'title' => 'Tidak ada carton yang dipilih'
            ], 422);
        }

        DB::beginTransaction();

        try {

            $popk = DB::table('pack')
                ->whereIn('packpk', $ids)
                ->value('popk');

            if (!$popk) {

                DB::rollBack();

                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Data carton tidak ditemukan'
                ], 422);
            }

            $deleted = DB::table('pack')
                ->whereIn('packpk', $ids)
                ->delete();

            // update jumlah carton
            $ctn = DB::table('pack')
                ->where('popk', $popk)
                ->count();

            DB::table('po')
                ->where('popk', $popk)
                ->update([
                    'ctn' => $ctn
                ]);

            // ambil ulang data carton
            $packs = DB::table('pack')
                ->where('popk', $popk)
                ->orderBy('urut')
                ->orderBy('packpk')
                ->get();

            if ($packs->count()) {

                // nomor carton pertama dijadikan acuan
                $cartonAwal = $packs->first()->carton;

                $offset = 0;

                foreach ($packs as $pack) {

                    $nobarBase = substr($pack->nobar, 0, -4);

                    $nobarNew = $nobarBase . sprintf('%04d', $offset + 1);

                    $cartonNew = $this->incrementCartonNumber(
                        (string)$cartonAwal,
                        $offset
                    );

                    DB::table('pack')
                        ->where('packpk', $pack->packpk)
                        ->update([
                            'carton' => $cartonNew,
                            'nobar'  => $nobarNew,
                        ]);

                    $offset++;
                }
            }

            DB::commit();

            return response()->json([
                'icon'  => 'success',
                'title' => "{$deleted} carton berhasil dihapus"
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menghapus carton'
                // 'title' => $e->getMessage(),
            ], 500);
        }
    }

    public function copyMultiple(Request $request)
    {
        $request->validate([
            'packpk' => 'required',
            'copy'   => 'required|integer|min:1'
        ]);

        DB::beginTransaction();

        try {

            $ids = collect(explode(',', $request->packpk))
                ->map(fn($x) => (int)$x)
                ->filter()
                ->values();

            $copies = (int)$request->copy;

            if ($ids->isEmpty()) {
                DB::rollBack();

                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Pilih minimal satu carton.'
                ], 422);
            }

            $rows = DB::table('pack')
                ->whereIn('packpk', $ids)
                ->orderBy('urut')
                ->orderBy('packpk')
                ->lockForUpdate()
                ->get();

            if ($rows->isEmpty()) {

                DB::rollBack();

                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Data tidak ditemukan'
                ], 422);
            }

            $popk = $rows->first()->popk;

            /*
            |--------------------------------------------------------------------------
            | Hitung sisa qty transfer
            |--------------------------------------------------------------------------
            */

            $remaining = [];

            for ($i = 1; $i <= 40; $i++) {

                $ready = (int)DB::table('pack')
                    ->where('popk', $popk)
                    ->sum("qty{$i}");

                $transfer = (int)DB::table('bj')
                    ->where('popk', $popk)
                    ->sum("qty{$i}");

                $remaining[$i] = $transfer - $ready;
            }

            $totalCopyDibuat    = 0;
            $totalActualDicopy  = 0;
            $totalActualDitolak = 0;
            $sizeHabisInfo      = [];

            /*
            |--------------------------------------------------------------------------
            | Copy data
            |--------------------------------------------------------------------------
            */

            foreach ($rows as $row) {

                $rowPunyaActual = false;

                for ($i = 1; $i <= 40; $i++) {

                    if ((int)($row->{"qty{$i}"} ?? 0) > 0) {
                        $rowPunyaActual = true;
                        break;
                    }
                }

                for ($c = 1; $c <= $copies; $c++) {

                    $new = (array)$row;

                    unset($new['packpk']);

                    // sementara, nanti di-renumber ulang
                    $new['carton'] = $row->carton;

                    $new['tanggal'] = now()->toDateString();
                    $new['waktu']   = now()->format('H:i:s');

                    if ($rowPunyaActual) {

                        $pcsActual = 0;

                        for ($i = 1; $i <= 40; $i++) {

                            $actualAsal = (int)($row->{"qty{$i}"} ?? 0);

                            if ($actualAsal <= 0) {
                                $new["qty{$i}"] = $row->{"qty{$i}"} ?? null;
                                continue;
                            }

                            if ($remaining[$i] >= $actualAsal) {

                                $new["qty{$i}"] = $actualAsal;

                                $remaining[$i] -= $actualAsal;

                                $pcsActual += $actualAsal;

                                $totalActualDicopy++;

                            } else {

                                $new["qty{$i}"] = null;

                                $totalActualDitolak++;

                                $sizeHabisInfo[$i] = true;
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

                    DB::table('pack')->insert($new);

                    $totalCopyDibuat++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Renumber carton & barcode
            |--------------------------------------------------------------------------
            */

            $packs = DB::table('pack')
                ->where('popk', $popk)
                ->orderBy('urut')
                ->orderBy('packpk')
                ->get();

            if ($packs->count()) {

                $cartonAwal = (string)$packs->first()->carton;

                $nobarBase = substr($packs->first()->nobar, 0, -4);

                $offset = 0;

                foreach ($packs as $pack) {

                    $cartonBaru = $this->incrementCartonNumber(
                        $cartonAwal,
                        $offset
                    );

                    $nobarBaru = $nobarBase . sprintf('%04d', $offset + 1);

                    DB::table('pack')
                        ->where('packpk', $pack->packpk)
                        ->update([
                            'carton' => $cartonBaru,
                            'nobar'  => $nobarBaru,
                        ]);

                    $offset++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update jumlah carton
            |--------------------------------------------------------------------------
            */

            DB::table('po')
                ->where('popk', $popk)
                ->update([
                    'ctn' => $packs->count()
                ]);

            DB::commit();

            $pesan = "{$totalCopyDibuat} carton berhasil dicopy.";

            if ($totalActualDitolak > 0) {

                $po = DB::table('po')
                    ->where('popk', $popk)
                    ->first();

                $labelSize = collect(array_keys($sizeHabisInfo))
                    ->map(fn($i) => $po->{"size{$i}"} ?? "Size {$i}")
                    ->implode(', ');

                $pesan .= " Namun sebagian Actual Qty tidak ikut disalin karena Transfer sudah habis untuk size: {$labelSize}.";
            }

            return response()->json([
                'icon'  => $totalActualDitolak > 0 ? 'warning' : 'success',
                'title' => $pesan,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menyalin carton.'
                // 'title' => $e->getMessage(),
            ], 500);
        }
    }

    public function deletePack($packpk)
    {
        $pack = DB::table('pack')->where('packpk', $packpk)->first();
        DB::table('pack')->where('packpk', $packpk)->delete();

        return redirect()
            ->route('packing.input', ['popk' => $pack->popk ?? 0])
            ->with('success', 'Data berhasil dihapus.');
    }

    public function urutCtn(Request $request)
    {
        $request->validate([
            'popk' => 'required|integer',
            'awal' => 'required|string|max:50',
        ]);

        DB::beginTransaction();

        try {

            $popk  = $request->popk;
            $awal  = trim($request->awal);
            $check = (int) $request->check;

            $packs = DB::table('pack')
                ->where('popk', $popk)
                ->where('status', 4)
                ->orderBy('urut')
                ->orderBy('packpk')
                ->get();

            $offset = 0;

            foreach ($packs as $pack) {

                // barcode tetap urut 0001,0002,0003...
                $nobarBase = substr($pack->nobar, 0, -4);
                $nobarNew  = $nobarBase . sprintf('%04d', $offset + 1);

                // nomor carton
                if ($check == 1) {

                    // format 6 digit
                    $carton = sprintf('%06d', $offset + 1);

                } else {

                    // mengikuti nomor awal (A001, CTN001, BOX-001, dst)
                    $carton = $this->incrementCartonNumber($awal, $offset);

                }

                DB::table('pack')
                    ->where('packpk', $pack->packpk)
                    ->update([
                        'carton' => $carton,
                        'nobar'  => $nobarNew,
                    ]);

                $offset++;
            }

            DB::commit();

            return response()->json([
                'icon'  => 'success',
                'title' => 'Nomor Carton berhasil diurutkan.',
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal mengurutkan nomor Carton.',
                // 'title' => $e->getMessage(), // debug
            ], 422);
        }
    }

    public function updateGabung(Request $request)
    {
        $request->validate([
            'gabung' => 'required|integer',
            'popk'   => 'required|array|min:1',
            'popk.*' => 'required'
        ]);

        DB::beginTransaction();

        try {
            DB::table('po')
                ->whereIn('popk', $request->popk)
                ->update([
                    'gabung' => $request->gabung
                ]);

            DB::commit();

            return response()->json([
                'icon'    => 'success',
                'title'   => 'Status Packing berhasil diperbarui.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal memperbarui Status Packing.'
                // 'title' => $e->getMessage(), // debug
            ], 422);
        }
    }

    public function finGoods() {
        $gabung = DB::table('gabung')
            ->select('gabungpk', 'keterangan')
            ->orderBy('gabungpk')
            ->get();
        return view('menu.packing.finGoods', compact('gabung'));
    }
}
