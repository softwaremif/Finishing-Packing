<?php

namespace App\Http\Controllers\FinishgoodStuffing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinishgoodStuffingController extends Controller
{
    // ===============  HALAMAN INDEX FG/Stuffing ===========

    public function index()
    {
        $gabung = DB::table('gabung')
            ->select('gabungpk', 'keterangan')
            ->orderBy('gabungpk')
            ->get();
        return view('menu.finishgood-stuffing.index', compact('gabung'));
    }

    public function listContainersGlobal(Request $request)
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->get('http://192.168.0.8/EXIM2/api/getListExport');
            if (!$response->successful()) {
                return response()->json(['containers' => [], 'error' => 'Gagal mengambil data dari sistem EXIM.']);
            }
            $eximRows = collect($response->json('rows') ?? []);
        } catch (\Throwable $e) {
            return response()->json(['containers' => [], 'error' => 'Sistem EXIM tidak dapat dihubungi.']);
        }

        $containersRaw = $eximRows
            ->filter(fn ($r) => !empty($r['contpk']))
            ->filter(fn ($r) => !empty($r['actcontdate']))
            ->groupBy('contpk');

        if ($containersRaw->isEmpty()) {
            return response()->json(['containers' => []]);
        }

        $allRows = $containersRaw->flatten(1);
        $cartonPoByPackpk = collect();

        foreach ($allRows->groupBy('mif') as $mifVal => $rowsForMif) {
            $connName = $this->resolveConnection($mifVal);

            $packpks = $rowsForMif->pluck('packpk')->filter()->unique()->values();
            if ($packpks->isEmpty()) continue;

            $packRows = DB::connection($connName)->table('pack')
                ->whereIn('packpk', $packpks)
                ->get(['packpk', 'popk'])
                ->keyBy('packpk');

            $popks = $packRows->pluck('popk')->filter()->unique()->values();
            $poRows = $popks->isNotEmpty()
                ? DB::connection($connName)->table('po')->whereIn('popk', $popks)->get(['popk', 'POno', 'OP'])->keyBy('popk')
                : collect();

            foreach ($packRows as $packpk => $packRow) {
                $poRow = $poRows->get($packRow->popk);
                if ($poRow) {
                    $cartonPoByPackpk->put($packpk, ['POno' => $poRow->POno, 'OP' => $poRow->OP]);
                }
            }
        }

        $factoryByPair = collect();
        $containersRaw->each(function ($rows) use (&$factoryByPair) {
            $f = $rows->first();
            $ponoParts    = array_map('trim', explode(',', (string) ($f['POno'] ?? '')));
            $opParts      = array_map('trim', explode(',', (string) ($f['OP'] ?? '')));
            $factoryParts = array_map('trim', explode(',', (string) ($f['factory'] ?? '')));
            foreach (range(0, max(count($ponoParts), count($opParts)) - 1) as $i) {
                $pono = $ponoParts[$i] ?? null;
                $op   = $opParts[$i] ?? null;
                if (!$pono && !$op) continue;
                $factoryByPair->put($pono . '|' . $op, $factoryParts[$i] ?? null);
            }
        });

        $metaByPair = collect();
        foreach (['mysql', 'mysql_andon'] as $conn) {
            try {
                $rows = DB::connection($conn)->table('po')
                    ->whereIn('POno', $cartonPoByPackpk->pluck('POno')->filter()->unique()->values())
                    ->whereIn('OP', $cartonPoByPackpk->pluck('OP')->filter()->unique()->values())
                    ->get(['POno', 'OP', 'mif', 'poref']);
                foreach ($rows as $r) {
                    $metaByPair->put($r->POno . '|' . $r->OP, ['mif' => (int) $r->mif, 'poref' => $r->poref]);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        $sessionMif = session('pos');

        $containers = $containersRaw->map(function ($rows, $contpk) use ($cartonPoByPackpk, $factoryByPair, $metaByPair) {
            $f = $rows->first();

            $poOpList = $rows
                ->map(fn ($r) => $cartonPoByPackpk->get($r['packpk'] ?? null))
                ->filter()
                ->unique(fn ($p) => $p['POno'] . '|' . $p['OP'])
                ->map(function ($p) use ($factoryByPair, $metaByPair) {
                    $key  = $p['POno'] . '|' . $p['OP'];
                    $meta = $metaByPair->get($key);
                    return [
                        'POno'    => $p['POno'],
                        'OP'      => $p['OP'],
                        'factory' => $factoryByPair->get($key),
                        'mif'     => $meta['mif'] ?? null,
                        'poref'   => $meta['poref'] ?? null,
                    ];
                })
                ->values();

            return [
                'contpk'      => (int) $contpk,
                'contno'      => $f['contno'] ?? null,
                'type'        => $f['type'] ?? null,
                'typenm'      => $f['typenm'] ?? null,
                'exportpk'    => $f['exportpk'] ?? null,
                'pebno'       => $f['pebno'] ?? null,
                'buyer'       => $f['buyer'] ?? null,
                'exdate'      => $f['exdate'] ?? null,
                'actcontdate' => $f['actcontdate'] ?? null,
                'start_ship'  => $f['start_ship'] ?? null,
                'end_ship'    => $f['end_ship'] ?? null,
                'segel'       => $f['segel'] ?? null,
                'qty_ctn'     => $rows->pluck('carton')->filter()->unique()->count(),
                'po_op_list'  => $poOpList,
            ];
        })
        ->filter(function ($c) use ($sessionMif) {
            if (!$sessionMif) return true;
            return $c['po_op_list']->contains(function ($p) use ($sessionMif) {
                return empty($p['factory']) || (string) $p['factory'] === (string) $sessionMif;
            });
        })
        ->values();

        $containers = $containers
            ->sortByDesc(fn ($c) => $c['actcontdate'] ?? $c['exdate'] ?? '')
            ->values();

        return response()->json(['containers' => $containers]);
    }

    // Memisahkan koneksi database sesuai mif user yang login.
    private function resolveConnection($mif): string
    {
        return ((int) $mif) === 1 ? 'mysql_andon' : 'mysql';
    }

    // Daftar Data OP index.blade.php.
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
            $connection = $this->resolveConnection($mif);
            $combined   = $this->fetchAll($connection, $mif, $request);
        }
    
        // Ambil URL gambar order dari mysql_gis (+ fallback mysql_sample).
        $this->addOrderImageToRows($combined);
    
        // Agregasi per PO+OP, lalu validasi ULANG semua filter aktif.
        $aggregated = $this->applyPostAggregationFilters(
            $this->aggregateByPoOp($combined),
            $request
        )->values();
    
        //  hitung breakdown CTN (ctn_full_count/ctn_partial_count/
        // ctn_sealed_count/ctn_shipped_count/ctn_inspect_count) utk SEMUA
        // baris hasil agregasi -- SEBELUM filter status & SEBELUM pagination.
        // WAJIB di sini (bukan setelah slice) supaya filter status di bawah
        // bisa mengecek nilai-nilai ini.
        $this->addCtnBreakdownToRows($aggregated);
    
        //  SEBELUMNYA hanya tampilkan status Complete. SEKARANG
        // Partial JUGA ditampilkan, TAPI HANYA kalau sudah ada progres
        // Actual di carton-nya (Full ATAU Partial) -- SAMA kriteria dengan
        // menu index Packing biasa. Pending TETAP disembunyikan (belum ada
        // plan sama sekali, tidak relevan utk FG/Stuffing).
        $aggregated = $aggregated->filter(function ($r) {
            if ($r->packing_plan_status === 'complete') {
                return true;
            }
            if ($r->packing_plan_status === 'partial') {
                return ($r->ctn_full_count ?? 0) > 0 || ($r->ctn_partial_count ?? 0) > 0;
            }
            return false;
        })->values();
    
        // Normalisasi GAC (Ex Factory) jadi timestamp SEBELUM sort.
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
    
        // TIDAK PERLU lagi panggil addCtnBreakdownToRows($data) di sini --
        // sudah dihitung di atas utk SELURUH $aggregated sebelum di-slice.
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

        $ordRows = DB::connection('mysql_gis')->table('ord')
            ->whereIn('ordpk', $ordpks)
            ->get(['ordpk', 'srno', 'foto', 'foto2', 'stsfoto']);

        $ordByOrdpk = $ordRows->keyBy('ordpk');

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
                    if (!isset($fotoBySrpk[$r->srpk])) {
                        $fotoBySrpk[$r->srpk] = $r->foto;
                    }
                }
            }
        }

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

    // Penjumlahan nilai kolom QTY, Packing /Pcs, CTN per PO+OP.
    private function aggregateByPoOp($collection)
    {
        return $collection
            ->groupBy(fn($row) => ($row->mif ?? '') . '|' . $row->POno . '|' . $row->OP . '|' . ($row->poref ?? ''))
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

                //  status pembuatan FG/Stuffing (Plan) -- Pending (belum
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

    // Tentukan status pembuatan FG/Stuffing (Plan) berdasarkan Plan Qty
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

    private function getCtnGroupStatus($cartonRows): string
    {
        $statuses = $cartonRows->map(fn($r) => $this->getCtnRowStatus($r));

        if ($statuses->contains('sealed')) {
            return 'sealed';
        }
        if ($statuses->isNotEmpty() && $statuses->every(fn($s) => $s === 'complete')) {
            return 'complete';
        }
        if ($statuses->contains(fn($s) => $s === 'packing' || $s === 'complete')) {
            return 'packing';
        }

        return 'planned';
    }

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
            $r->ctn_shipped_count = 0; 
            $r->ctn_inspect_count = 0;
        }
    
        $rowsByMif = $rows->groupBy(fn ($r) => (int) ($r->mif ?? 0));
    
        foreach ($rowsByMif as $mif => $rowsForMif) {
            $connection = $this->resolveConnection($mif);
            $db = DB::connection($connection);
    
            $allPopks = $rowsForMif
                ->flatMap(fn ($r) => $r->_popks ?? [$r->popk])
                ->unique()
                ->values()
                ->all();
    
            if (empty($allPopks)) {
                continue;
            }
    
            $packRows = $db->table('pack')->whereIn('popk', $allPopks)->get();
            $packRowsByPopk = $packRows->groupBy('popk');
    
            // cek status shipment (ship.status IN [6,7]) per packpk --
            // SAMA kriteria dengan 'shipped' di listDetailGlobal()/getShipStampForGroup().
            $allPackpks = $packRows->pluck('packpk')->unique()->values()->all();
            $shippedPackpkSet = $db->table('ship')
                ->whereIn('packpk', $allPackpks)
                ->whereIn('status', [6, 7])
                ->pluck('packpk')
                ->flip();

            $inspectingPackpkSet = $db->table('ship')
                ->whereIn('packpk', $allPackpks)
                ->where('fca', 1)
                ->pluck('packpk')
                ->flip();
    
            foreach ($rowsForMif as $r) {
                $popksForGroup = $r->_popks ?? [$r->popk];
    
                $groupPackRows = collect();
                foreach ($popksForGroup as $pk) {
                    $groupPackRows = $groupPackRows->concat($packRowsByPopk->get($pk, collect()));
                }
    
                $byCarton = $groupPackRows->groupBy('carton');
    
                $r->ctn_plan_count = $byCarton->count();
    
                foreach ($byCarton as $cartonRows) {
                    $status = $this->getCtnGroupStatus($cartonRows);
    
                    if ($status === 'packing') {
                        $r->ctn_partial_count++;
                    } elseif ($status === 'complete') {
                        $r->ctn_full_count++;
                    } elseif ($status === 'sealed') {
                        $r->ctn_sealed_count++;
                    }
    
                    // carton dianggap "Shipped" kalau SALAH SATU packpk
                    // di dalamnya (carton Mixed bisa >1 packpk) sudah shipped --
                    // dihitung TERLEPAS dari status Pack di atas.
                    $isShipped = $cartonRows->contains(fn ($cr) => isset($shippedPackpkSet[$cr->packpk]));
                    if ($isShipped) {
                        $r->ctn_shipped_count++;
                    }

                    $isInspecting = $cartonRows->contains(fn ($cr) => isset($inspectingPackpkSet[$cr->packpk]));
                    if ($isInspecting) {
                        $r->ctn_inspect_count++;
                    }
                }
            }
        }
    }

    // Get All data list.
    private function fetchAll(string $connection, int $mif, Request $request, ?string $po = null, ?string $op = null, ?string $poref = null)
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
            ->where('po.OP', '<>', '')
            ->where('po.mif', $mif);

            //  tambah po.ordpk (utk addOrderImageToRows) dan po.GAC
            // (Ex Factory) -- sebelumnya TIDAK ada di select, jadi kolom Ex
            // Factory & foto tidak bisa ditampilkan.
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

    // Filter di halaman index -- SEKARANG termasuk Ex Factory (po.GAC).
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

        //  filter Ex Factory (po.GAC) berdasarkan preset rentang tanggal.
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

    /**
     * Hitung rentang tanggal [start, end] untuk preset filter Ex Factory.
     * SAMA PERSIS logic dengan TF Finishing/Polibag.
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

    // Normalisasi GAC (Ex Factory) jadi UNIX timestamp -- AMAN apa pun
    // format aslinya, SAMA PERSIS logic dengan TF Finishing/Polibag (lihat
    // controller tersebut untuk alasan lengkapnya).
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
    // buyer) terhadap hasil akhir yang SUDAH diagregasi per PO+OP.
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

        //  filter Status FG/Stuffing (Pending/Partial/Complete).
        if ($request->filled('packing_plan_status')) {
            $status = $request->packing_plan_status;
            $rows = $rows->filter(fn($r) => $r->packing_plan_status === $status);
        }

        return $rows->values();
    }

    // =============== MODAL ===========
    // Di file index.blade.php memanggil file modal-material-list.blade.php.
    public function detailByPoOp(Request $request)
    {
        $validated = $request->validate([
            'po'    => 'nullable',
            'op'    => 'required',
            'poref' => 'nullable',
            'mif'   => 'nullable',
        ]);
        $po    = $validated['po'] ?? null;
        $op    = $validated['op'];
        $poref = $validated['poref'] ?? null;

        $mif        = $validated['mif'] ?? session('pos');
        $connection = $this->resolveConnection($mif);

        // Ikut filter poref juga -- supaya modal detail cuma menampilkan
        // baris yang MEMANG satu grup (PO+OP+poref sama).
        $rows = $this->fetchAll($connection, (int) $mif, $request, $po, $op, $poref)
            ->values();

        foreach ($rows as $i => $row) {
            $row->no = $i + 1;
        }

        return response()->json([
            'total' => $rows->count(),
            'rows'  => $rows,
        ]);
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
            'dt',
            'dt2',
            'dt3',
            'hsl',
            'summary',
            'activeSizes',
            'lines',
            'gabungList',
            'orderQty',
            'readyQty',
            'planQty',
            'transQty',
            'diffPackQty',
            'diffTransQty',
            'totDiffTrans',
            'totDiffPack',
            'tctnp',
            'tctna',
            'balanceCtn',
            'details',
            'packTotal',
            'packPages',
            'cr',
            'size',
            'page',
            'popk',
            'mif',
            'connection'
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
            'dt',
            'dt2',
            'dt3',
            'summary',
            'activeSizes',
            'orderQty',
            'readyQty',
            'planQty',
            'transQty',
            'diffTransQty',
            'diffPackQty',
            'totDiffTrans',
            'totDiffPack',
            'tctnp',
            'tctna',
            'balanceCtn'
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
            // 'ship2' => 'nullable|date',
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
                // 'shipdate2' => $validated['ship2'] ?: null,
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

            $poNo = $dtpo->POno;
            $op   = $dtpo->OP;

            $baseDupQuery = function () use ($poNo, $op, $popk, $packpk) {
                $q = DB::table('pack')
                    ->where('POno', $poNo)
                    ->where('OP', $op)
                    ->where('popk', $popk);

                if ($packpk > 0) {
                    $q->where('packpk', '<>', $packpk);
                }
                return $q;
            };

            $nocarValue = trim((string) $request->nocar);
            $nobarValue = trim((string) $request->nobar);

            $dupCartonRow = $nocarValue !== '' ? $baseDupQuery()->where('carton', $nocarValue)->first() : null;
            if ($dupCartonRow) {
                DB::rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => "No Carton <b>{$nocarValue}</b> sudah digunakan pada PO/OP ini "
                        . ". Gunakan nomor carton yang lain.",
                ], 422);
            }

            $dupNobarRow = $nobarValue !== '' ? $baseDupQuery()->where('nobar', $nobarValue)->first() : null;
            if ($dupNobarRow) {
                DB::rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => "No Barcode <b>{$nobarValue}</b> sudah digunakan pada PO/OP ini "
                        . ". Gunakan barcode yang lain.",
                ], 422);
            }

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
                $planIni   = (int) ($qtyp_values[$i] ?? 0);
                $actualIni = (int) ($qty_values[$i] ?? 0);

                if ($actualIni > $planIni) {
                    DB::rollBack();

                    $sizeName = $dtpo->{"size{$i}"} ?? "Size {$i}";

                    return response()->json([
                        'icon'  => 'warning',
                        'title' =>
                        "Qty Actual untuk size <b>{$sizeName}</b> tidak dapat disimpan.<br>" .
                            "<span class='text-danger'>Qty Actual tidak boleh melebihi Plan Qty pada carton ini.</span>",
                    ], 422);
                }
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
                            "<span class='text-danger'>Maksimal Qty yang masih dapat diinput adalah <b>{$maksimal}</b>.</span>"
                    ], 422);
                }
            }

            if ($packpk > 0) {

                // Fetch FRESH sebelum update apa pun -- ini yang dipakai
                // sebagai "nilai LAMA" pembanding untuk histori actpack.
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
                        //  histori actpack -- qty$i di $base ditulis di sini.
                        $this->insertActpackHistory($packpk, $existing, $qty_values);
                    } else {
                        $base['jmlpcs'] = 1;
                        $base['pcsp']   = $totalp;
                        for ($i = 1; $i <= 40; $i++) {
                            $base["qtyp$i"] = $qtyp_values[$i];
                        }
                        DB::table('pack')->where('packpk', $packpk)->update($base);
                        //  histori actpack -- qty$i di $base ditulis di sini juga.
                        $this->insertActpackHistory($packpk, $existing, $qty_values);
                    }
                } else {
                    if ($totalp == 0) {
                        $newCtn = ($dtpo->ctn ?? 0) - 1;
                        DB::table('po')->where('popk', $popk)->update(['ctn' => $newCtn]);
                        DB::table('pack')->where('packpk', $packpk)->update(['pcsp' => 0]);
                        // TIDAK ada histori di sini -- cabang ini SENGAJA tidak
                        // menyentuh kolom qty$i sama sekali (cuma pcsp), jadi
                        // Actual TIDAK berubah di database, tidak perlu dicatat.
                    } else {
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
                        //  histori actpack -- qty$i di $upd ditulis (di-clear
                        // ke $qty_values, yang di cabang ini berarti mengecilkan/
                        // mengosongkan Actual -- akan tercatat sebagai delta NEGATIF
                        // secara otomatis oleh insertActpackHistory()).
                        $this->insertActpackHistory($packpk, $existing, $qty_values);
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

                            $nobarBase = substr($request->nobar, 0, -4);

                            $generatedRows = [];

                            for ($offset = 0; $offset <= ($kali - 1); $offset++) {
                                $generatedRows[] = [
                                    'carton' => $this->incrementCartonNumber($nocar, $offset),
                                    'nobar'  => $nobarBase . sprintf('%04d', $offset + 1),
                                    'qty'    => $singleQty,
                                ];
                            }
                            if ($sisa > 0) {
                                $generatedRows[] = [
                                    'carton' => $this->incrementCartonNumber($nocar, $kali),
                                    'nobar'  => $nobarBase . sprintf('%04d', $kali + 1),
                                    'qty'    => (int) $sisa,
                                ];
                            }

                            $generatedCartons = array_column($generatedRows, 'carton');
                            $generatedNobars  = array_column($generatedRows, 'nobar');

                            $dupCarton = $baseDupQuery()->whereIn('carton', $generatedCartons)->first();
                            if ($dupCarton) {
                                DB::rollBack();
                                return response()->json([
                                    'icon'  => 'warning',
                                    'title' => "No Carton <b>{$dupCarton->carton}</b> (hasil Auto Split) sudah digunakan pada PO/OP ini. Ubah No. Carton awal supaya tidak bentrok.",
                                ], 422);
                            }

                            $dupNobar = $baseDupQuery()->whereIn('nobar', $generatedNobars)->first();
                            if ($dupNobar) {
                                DB::rollBack();
                                return response()->json([
                                    'icon'  => 'warning',
                                    'title' => "No Barcode <b>{$dupNobar->nobar}</b> (hasil Auto Split) sudah digunakan pada PO/OP ini. Ubah Scan Barcode awal supaya tidak bentrok.",
                                ], 422);
                            }

                            $newCtn = ($dtpo->ctn ?? 0) + $kali + $kali4;
                            DB::table('po')->where('popk', $popk)->update(['ctn' => $newCtn]);

                            foreach ($generatedRows as $gen) {
                                $rowFull = $this->buildPackRowSingleSize(
                                    $request,
                                    $dtpo,
                                    $popk,
                                    $gen['carton'],
                                    $gen['nobar'],
                                    $singleIdx,
                                    $gen['qty'],
                                    $urut
                                );
                                DB::table('pack')->insert($rowFull);
                            }
                        } else {

                            $newCtn = ($dtpo->ctn ?? 0) + 1;
                            DB::table('po')->where('popk', $popk)->update(['ctn' => $newCtn]);
                            $row = $this->buildPackRow($request, $dtpo, $popk, $nocar, $totalp, $qtyp_values, $qty_values, $urut);
                            DB::table('pack')->insert($row);
                        }
                    }
                }
                // TIDAK ada histori di cabang ini (packpk == 0, mode Tambah
                // carton baru) -- histori actpack memang khusus mencatat
                // PERUBAHAN Actual pada carton yang SUDAH ADA, bukan carton
                // yang baru dibuat pertama kali.
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

                // ============================================================
                //  catat HISTORI ke tabel actpack -- SATU baris per
                // event update, berisi DELTA (jumlah pcs yang BARU DITAMBAHKAN
                // pada aksi ini), BUKAN total qty carton. Tabel actpack cuma
                // punya kolom qty1..qty25 (bukan sampai 40), jadi size index
                // di atas 25 tidak ikut dicatat ke histori (tetap ter-update
                // normal di tabel pack, cuma histori-nya yang terbatas).
                // ============================================================
                $newQtyArr = [];
                foreach ($sizeIndexes as $i) {
                    $newQtyArr[$i] = array_key_exists("qty{$i}", $upd)
                        ? $upd["qty{$i}"]
                        : ($x->{"qty{$i}"} ?? 0);
                }

                DB::table('pack')->where('packpk', $x->packpk)->update($upd);

                $this->insertActpackHistory($x->packpk, $x, $newQtyArr);

                $jumlahUpdate++;
                if ($adaPartialRow) $jumlahParsial++;
                if ($adaGagalRow)   $jumlahGagal++;
            }

            DB::commit();

            $pesan = "{$jumlahUpdate} carton berhasil diupdate.";
            if ($jumlahSkip > 0)    $pesan .= " {$jumlahSkip} carton dilewati (sudah penuh / polibag habis).";
            if ($jumlahParsial > 0) $pesan .= " {$jumlahParsial} carton terisi sebagian karena Polibag terbatas.";
            if ($jumlahGagal > 0)   $pesan .= " {$jumlahGagal} carton/size tidak bisa ditambah, Polibag sudah habis.";

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

                // ============================================================
                //  catat HISTORI ke tabel actpack -- SAMA seperti di
                // updateCtn(), tapi nilainya NEGATIF (delta pengurangan),
                // supaya jelas dibedakan dari histori penambahan. Hanya size
                // yang BENAR-BENAR punya Actual sebelumnya (>0) yang dicatat
                // -- size yang memang sudah kosong tidak perlu histori.
                // Tabel actpack cuma sampai qty25, size index >25 tidak ikut
                // tercatat ke histori (sama seperti updateCtn()).
                // ============================================================
                $sizeIndexesToClear = ($size !== '' && $size !== null) ? [(int) $size] : range(1, 40);

                $newQtyArr = [];
                for ($i = 1; $i <= 25; $i++) {
                    if (in_array($i, $sizeIndexesToClear)) {
                        $newQtyArr[$i] = 0; // di-clear -> null di DB, dianggap 0
                    } else {
                        $newQtyArr[$i] = $pack->{"qty{$i}"} ?? 0; // tidak disentuh, tetap nilai lama
                    }
                }

                DB::table('pack')
                    ->where('packpk', $id)
                    ->update($update);

                $this->insertActpackHistory($id, $pack, $newQtyArr);

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

                $pesan .= " Namun sebagian Actual Qty tidak ikut disalin karena Polibag sudah habis untuk size: {$labelSize}.";
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

    public function updateSegelStatus(Request $request)
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

        DB::beginTransaction();

        try {
            $popk = $request->popk;
            $ids  = array_values(array_filter(explode(',', $request->packpk)));

            if (empty($ids)) {
                DB::rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Pilih minimal satu carton.',
                ], 422);
            }

            $packs = DB::table('pack')
                ->where('popk', $popk)
                ->whereIn('packpk', $ids)
                ->lockForUpdate()
                ->get();

            if ($packs->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Data carton tidak ditemukan.',
                ], 422);
            }

            // Validasi kelengkapan Actual HANYA relevan saat MENYEGEL (target=1)
            // -- tidak perlu dicek saat membuka segel (target=0).
            if ($target === 1) {
                foreach ($packs as $pack) {
                    for ($i = 1; $i <= 40; $i++) {
                        $plan = (int) ($pack->{"qtyp{$i}"} ?? 0);
                        if ($plan <= 0) continue;

                        $actual = (int) ($pack->{"qty{$i}"} ?? 0);
                        if ($actual <= 0) {
                            DB::rollBack();
                            return response()->json([
                                'icon'  => 'warning',
                                'title' => "Carton <b>{$pack->carton}</b> belum lengkap -- masih ada Size dengan Plan yang Actual-nya belum diisi. Lengkapi dulu Actual-nya sebelum bisa disegel.",
                            ], 422);
                        }
                    }
                }
            }

            // Cuma proses carton yang statusnya BELUM sesuai target -- yang
            // sudah sesuai (misal mau disegel tapi sudah segel=1) dilewati.
            $sudahSesuai = $packs->where('segel', $target)->count();
            $perluDiubah = $packs->where('segel', '<>', $target)->pluck('packpk');

            if ($perluDiubah->isEmpty()) {
                DB::rollBack();
                $labelStatus = $target === 1 ? 'Segel' : 'Buka Segel';
                return response()->json([
                    'icon'  => 'warning',
                    'title' => "Semua carton yang dipilih sudah berstatus {$labelStatus} sebelumnya.",
                ], 422);
            }

            // ============================================================
            //  catat tanggal/waktu perubahan status segel.
            // - target=1 (menyegel)     -> isi sealdate = sekarang.
            // - target=0 (buka segel)   -> isi unsealdate = sekarang.
            // Kolom yang BUKAN untuk aksi ini TIDAK disentuh (misal saat
            // menyegel, unsealdate lama TETAP dipertahankan sebagai jejak
            // riwayat "kapan terakhir dibuka", bukan direset ke null).
            // ============================================================
            $updateData = ['segel' => $target];

            if ($target === 1) {
                $updateData['sealdate'] = now();
            } else {
                $updateData['unsealdate'] = now();
            }

            DB::table('pack')
                ->whereIn('packpk', $perluDiubah)
                ->update($updateData);

            DB::commit();

            $jumlah = $perluDiubah->count();
            $aksi   = $target === 1 ? 'disegel' : 'dibuka segelnya';
            $pesan  = "{$jumlah} carton berhasil {$aksi}.";

            if ($sudahSesuai > 0) {
                $pesan .= " {$sudahSesuai} carton dilewati (sudah sesuai status sebelumnya).";
            }

            return response()->json([
                'icon'  => 'success',
                'title' => $pesan,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal mengubah status Segel Carton.'
                // 'title' => $e->getMessage(), // debug
            ], 500);
        }
    }

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

    public function finGoods()
    {
        $gabung = DB::table('gabung')
            ->select('gabungpk', 'keterangan')
            ->orderBy('gabungpk')
            ->get();
        return view('menu.packing.finGoods', compact('gabung'));
    }


    // ===============  PACKING NEW V2 HALAMAN INPUT TRANSFER/POLIBAG 
    // ===============  HALAMAN INPUT FG/Stuffing
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
        $connection = $this->resolveConnection($mif);
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

        // $existingData WAJIB berisi SEMUA variabel di atas --
        // SEBELUMNYA variabel ini TIDAK PERNAH didefinisikan sama sekali,
        // menyebabkan "Undefined variable $existingData", DAN blade akan
        // gagal lagi setelahnya karena $po/$dt2/$activeSizes/dst tidak
        // pernah ikut terkirim ke view.
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
                'mode'                => 'fgstuffing',
                'pageTitlePrefix'     => 'Packing list',
                'guserpkSegel'        => [38],
                'guserpkShipmentFlow' => [35],
                'guserpkTerima'       => [38],

                'showPlanningChips'   => false,
                'showShipmentChips'   => true,
                'showSizeFilter'      => true,
                'showPartFilter'      => true,
                'showAddPacking'      => false,
                'showCtnManagement'   => false,
                'showScanNobar'       => true,
                'showShipmentPlan'    => true,
                'showShipmentActions' => true,
                'showEditButton'      => false,
                'backRouteName'       => 'finish-good-stuffing.index',

                'routes' => [
                    'back'                   => route('finish-good-stuffing.index'),
                    'listDetailGlobal'       => route('finish-good-stuffing.list.detail.global'),
                    'breakdownSummaryGlobal' => route('finish-good-stuffing.breakdownSummaryGlobal'),
                    'cardsInfoGlobal'        => route('finish-good-stuffing.cardsInfoGlobal'),
                    'headerInfoGlobal'       => route('finish-good-stuffing.headerInfoGlobal'),
                    'combosGlobal'           => route('finish-good-stuffing.combosGlobal'),
                    'updateCtn'              => route('finish-good-stuffing.update-ctn'),
                    'bulkShipAction'         => route('finish-good-stuffing.bulk-ship-action'),
                    'scanNobar'              => route('finish-good-stuffing.scan-nobar'),
                    'partSummaryGlobal'      => route('finish-good-stuffing.partSummaryGlobal'),
                    'eximUpdateShipment'     => route('finish-good-stuffing.exim-update-shipment'),
                    'shipmentPlanDetailGlobal' => route('finish-good-stuffing.shipmentPlanDetailGlobal'),

                    'headerPartial'          => 'menu.finishgood-stuffing.partials.header_info_global',
                    'cardsInfoPartial'       => 'menu.finishgood-stuffing.partials.cards_info_global',
                    'breakdownPartial'       => 'menu.finishgood-stuffing.partials.breakdown_summary_global',

                    'modalSegelCtn'          => 'menu.finishgood-stuffing.modal-segel-ctn-global',
                    'modalShipmentCtn'       => 'menu.finishgood-stuffing.modal-shipment-ctn-global',
                    'modalEndSession'        => 'menu.finishgood-stuffing.modal-end-session-global',
                    'modalTerimaCarton'      => 'menu.finishgood-stuffing.modal-terima-carton-global',
                ],
            ],
        ]));
    }

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

        // Sync field yang sama ke pack/bj/ship -- HANYA field yang memang
        // relevan disinkronkan (sebelumnya juga sync OP/POno/customer/
        // material/secsz per popk -- itu TIDAK PERLU diulang di sini
        // karena field itu TIDAK diedit lewat form ini lagi).
        return response()->json([
            'icon'  => 'success',
            'title' => 'Informasi PO berhasil disimpan.',
            'data'  => $updateData,
        ]);
    }

    public function headerInfoGlobal(Request $request)
    {
        $po    = $request->query('po');
        $op    = $request->query('op');
        $poref = $request->query('poref');
        $mif   = (int) $request->query('mif', session('pos'));

        $connection = $this->resolveConnection($mif);

        $breakdown = $this->getBreakdownDataGlobal($po, $op, $poref, $mif, $connection);

        return view('menu.finishgood-stuffing.partials.header_info_global', [
            'dt2'       => $breakdown['dt2'],
            'poNoList'  => $breakdown['poNoList'],
            'allPopks'  => $breakdown['allPopks'],
            'colorList' => collect($breakdown['groups'])->pluck('material')->filter()->unique()->values(),
        ]);
    }
    // Di file input-global.blade.php memanggil file partial/breakdown_summary_global.blade.php
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

        // ============================================================
        //  reverse-map label size (teks) -> index i -- dibutuhkan
        // karena tabel `output` menyimpan size sebagai TEKS ("M", "L",
        // dst), bukan kolom qty1..40 seperti `bj`. Dipakai untuk
        // menjumlahkan output.jmlpcs ke index qty yang benar di $summary.
        // ============================================================
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

            // ============================================================
            //  tambahkan SUM(output.jmlpcs) ke $summary -- Polibag di
            // Coverage Matrix sekarang mencakup bj + output, sama seperti
            // fix yang sudah dilakukan di modul Transfer.
            // ============================================================
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

        // ============================================================
        // Agregat GLOBAL -- dijumlah lintas SEMUA grup. $transQtyAgg di
        // sini OTOMATIS sudah termasuk output, karena diambil dari
        // $group['transQty'] yang sudah termasuk output di atas.
        // ============================================================
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
            'ctnActual'  => $ctnActualTotal, // sekarang = jumlah carton yang SUDAH Segel
            'ctnBalance' => $ctnActualTotal - $ctnPlanTotal, // Segel - Plan
        ];

        $poNoList = $poRows->pluck('POno')->filter(fn($v) => $v !== null && $v !== '')->unique()->values();
        $allPopks = $poRows->pluck('popk')->values();

        return compact('dt', 'dt2', 'activeSizes', 'groups', 'aggQty', 'ctnSummary', 'poNoList', 'allPopks');
    }

    public function breakdownSummaryGlobal(Request $request)
    {
        $po    = $request->query('po');
        $op    = $request->query('op');
        $poref = $request->query('poref');
        $mif   = (int) $request->query('mif', session('pos'));

        $connection = $this->resolveConnection($mif);

        $breakdown = $this->getBreakdownDataGlobal($po, $op, $poref, $mif, $connection);

        return view('menu.finishgood-stuffing.partials.breakdown_summary_global', $breakdown);
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

    private function buildNoInspec($inspecpk, $tgl): string
    {
        $romanMonths = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $date = \Carbon\Carbon::parse($tgl);
        $month = $romanMonths[$date->month - 1];
        return sprintf('%04d/INS/%s/%d', $inspecpk, $month, $date->year);
    }

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

        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);

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
            return response()->json(['total' => 0, 'total_carton' => 0, 'rows' => []]);
        }

        $cr     = $request->cr;
        $size   = $request->size;
        $color  = $request->color;
        $secsz  = $request->secsz;
        $part   = $request->part;
        $page   = max(1, (int) $request->input('page', 1));
        $rowsPerPage = max(1, (int) $request->input('rows', 50));
        $search = trim($request->search ?? '');

        // ============================================================
        // Lookup ship -- pakai koneksi PER-MIF ($db), SAMA seperti pack/po.
        // $shipInfoByPackpk adalah SATU-SATUNYA sumber kebenaran ship info --
        // dipakai lagi nanti di loop final ($data), BUKAN mengandalkan
        // property yang "menempel" ke object hasil query lain.
        // ============================================================
        $shipRows = $db->table('ship')
            ->whereIn('popk', $popks)
            ->get(['packpk', 'popk', 'part', 'status', 'fca', 'pinjam', 'kembali', 'shippk']);

        $shipInfoByPackpk = [];
        foreach ($shipRows as $sr) {
            $shipInfoByPackpk[$sr->packpk] = [
                'shipped'        => in_array((int) $sr->status, [6, 7], true),
                'inspect'        => (int) $sr->fca === 1,
                'returning'      => (int) $sr->fca === 2,
                'ever_inspected' => !empty($sr->pinjam),
                'kembali'        => $sr->kembali ?? null,
                'pinjam'         => $sr->pinjam ?? null,
                'popk'           => $sr->popk,
                'part'           => $sr->part,
            ];
        }

        // ============================================================
        // lookup dokumen inspec TERBARU per shippk,
        // lalu tempel ke $shipInfoByPackpk (SUMBER KEBENARAN yang sama),
        // supaya has_inspec_doc/no_inspec ikut terbawa ke SEMUA tempat
        // yang sudah lookup dari $shipInfoByPackpk (SAMA pola dgn
        // shipped/inspect/returning).
        // ============================================================
        $shippkList = $shipRows->pluck('shippk')->filter()->unique()->values();

        $inspecInfoByShippk = [];
        if ($shippkList->isNotEmpty()) {
            $inspecdtRows = $db->table('inspecdt')
                ->join('inspec', 'inspec.inspecpk', '=', 'inspecdt.inspecpk')
                ->whereIn('inspecdt.shippk', $shippkList)
                ->select('inspecdt.shippk', 'inspec.inspecpk', 'inspec.tgl', 'inspec.hasil', 'inspec.aql', 'inspec.totpcs') // BARU
                ->orderByDesc('inspec.inspecpk')
                ->get();
        
            foreach ($inspecdtRows as $row) {
                if (!isset($inspecInfoByShippk[$row->shippk])) {
                    $inspecInfoByShippk[$row->shippk] = [
                        'inspecpk'  => $row->inspecpk,
                        'no_inspec' => $this->buildNoInspec($row->inspecpk, $row->tgl),
                        'hasil'     => (int) $row->hasil, // BARU -- 1=Lulus, 0=Reject
                        'aql'       => $row->aql,          // BARU
                        'totpcs'    => $row->totpcs,       // BARU
                    ];
                }
            }
        }

        $shippkByPackpk = $shipRows->pluck('shippk', 'packpk');
 
        foreach ($shipInfoByPackpk as $packpk => &$info) {
            $shippkForThisPack = $shippkByPackpk[$packpk] ?? null;
            $inspecInfo = $shippkForThisPack !== null ? ($inspecInfoByShippk[$shippkForThisPack] ?? null) : null;
            $info['has_inspec_doc'] = $inspecInfo !== null;
            $info['no_inspec']      = $inspecInfo['no_inspec'] ?? null;
            $info['inspec_hasil']   = $inspecInfo['hasil'] ?? null; // BARU
            $info['inspec_aql']     = $inspecInfo['aql'] ?? null;   // BARU
        }
        unset($info);

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
            ->whereIn('popk', $popks)
            ->when($cr, fn($q) => $q->where('carton', $cr))
            ->when($size, fn($q) => $q->where("qtyp{$size}", '>', 0))
            ->when($color, fn($q) => $q->where('material', $color))
            ->when($secsz, fn($q) => $q->where('secsz', $secsz))
            ->when($part, fn($q) => $q->where('exportpk', $part)) // FIX UTAMA -- ganti 'part' jadi 'exportpk'
            ->when($search, function ($q) use ($search) {
                $q->where(function ($x) use ($search) {
                    $x->where('nobar', 'like', "%{$search}%")
                        ->orWhere('carton', 'like', "%{$search}%");
                });
            });

        $hasAnyFilter = $cr || $size || $color || $secsz || $part || $search;

        $baseQuery = $db->table('pack')->whereIn('popk', $popks);
        if ($hasAnyFilter) {
            $matchingCartons = $matchingCartonQuery->pluck('carton')->unique()->values();
            $baseQuery->whereIn('carton', $matchingCartons->isNotEmpty() ? $matchingCartons : ['__NONE__']);
        }

        $allMatchingRows = (clone $baseQuery)->get();

        // Tempel status ship LEBIH AWAL -- dibutuhkan untuk hitung statusCounts
        // & filter status='shipped'/'inspect'/'returning' di bawah.
        foreach ($allMatchingRows as $r) {
            $shipInfo = $shipInfoByPackpk[$r->packpk] ?? null;
            $r->ship_shipped   = $shipInfo['shipped'] ?? false;
            $r->ship_inspect   = $shipInfo['inspect'] ?? false;
            $r->ship_returning = $shipInfo['returning'] ?? false;
            $r->has_inspec_doc = $shipInfo['has_inspec_doc'] ?? false; // BARU
            $r->no_inspec      = $shipInfo['no_inspec'] ?? null;       // BARU
            $r->inspec_hasil = $shipInfo['inspec_hasil'] ?? null; // 1=Lulus, 0=Reject, null=belum ada dokumen
            $r->inspec_aql   = $shipInfo['inspec_aql'] ?? null;
        }

        $rowsByCartonForStatus = $allMatchingRows->groupBy('carton');

        $cartonStatusMap     = [];
        $cartonShipStatusMap = [];
        $cartonEverInspectedMap = [];
        foreach ($rowsByCartonForStatus as $cartonKey => $groupRows) {
            $cartonStatusMap[$cartonKey] = $this->getPackGroupStatus($groupRows);

            $anyShipped   = $groupRows->contains(fn ($r) => $r->ship_shipped === true);
            $anyReturning = $groupRows->contains(fn ($r) => $r->ship_returning === true);
            $anyInspect   = $groupRows->contains(fn ($r) => $r->ship_inspect === true);

            $cartonShipStatusMap[$cartonKey] = $anyShipped ? 'shipped'
                : ($anyReturning ? 'returning'
                : ($anyInspect ? 'inspect' : null));
            $cartonEverInspectedMap[$cartonKey] = $groupRows->contains(
                fn ($r) => ($shipInfoByPackpk[$r->packpk]['ever_inspected'] ?? false) === true
            );
        }

        $statusCounts = [
            'all'       => count($cartonStatusMap),
            'planned'   => count(array_filter($cartonStatusMap, fn($s) => $s === 'planned')),
            'packing'   => count(array_filter($cartonStatusMap, fn($s) => $s === 'packing')),
            'complete'  => count(array_filter($cartonStatusMap, fn($s) => $s === 'complete')),
            'sealed'    => count(array_filter($cartonStatusMap, fn($s) => $s === 'sealed')),
            'shipped'   => count(array_filter($cartonShipStatusMap, fn($s) => $s === 'shipped')),
            'inspect'   => count(array_filter($cartonShipStatusMap, fn($s) => $s === 'inspect')),
            'returning' => count(array_filter($cartonShipStatusMap, fn($s) => $s === 'returning')),
            'inspect_history' => count(array_filter($cartonEverInspectedMap, fn($v) => $v === true)),
        ];

        $status = $request->input('status');

        $finalQuery = clone $baseQuery;
        if ($status !== '' && $status !== null) {
            if ($status === 'inspect_history') {
                $matchingCartons = array_keys(array_filter($cartonEverInspectedMap, fn($v) => $v === true));
            } elseif (in_array($status, ['shipped', 'inspect', 'returning'], true)) {
                $matchingCartons = array_keys(array_filter($cartonShipStatusMap, fn($s) => $s === $status));
            } else {
                $matchingCartons = array_keys(array_filter($cartonStatusMap, fn($s) => $s === $status));
            }
            $finalQuery->whereIn('carton', !empty($matchingCartons) ? $matchingCartons : ['__NONE__']);
        }

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
                // strnatcmp() bukan strcmp(), khusus utk kolom
                // 'carton' (nobar tetap strcmp() biasa, kecuali kamu mau natural
                // sort juga -- tinggal ganti kondisi di bawah).
                $cmp = ($sortColumn === 'carton') ? strnatcmp($valA, $valB) : strcmp($valA, $valB);
                return $sortDir === 'desc' ? -$cmp : $cmp;
            })->values();
        } else {
            // Urutan DEFAULT -- No Carton A-Z, SEKARANG pakai natural sort.
            $cartonKeys = $cartonKeys->sort(function ($a, $b) use ($representativeByCarton) {
                $valA = (string) ($representativeByCarton[$a]->carton ?? '');
                $valB = (string) ($representativeByCarton[$b]->carton ?? '');
                return strnatcmp($valA, $valB); // BARU -- FIX UTAMA
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

        foreach ($data as $index => $row) {
            $row->no      = $index + 1;
            $row->balance = ($row->pcs ?? 0) - ($row->pcsp ?? 0);
        
            $shipInfo = $shipInfoByPackpk[$row->packpk] ?? null;
            $row->ship_shipped   = $shipInfo['shipped'] ?? false;
            $row->ship_inspect   = $shipInfo['inspect'] ?? false;
            $row->ship_returning = $shipInfo['returning'] ?? false;
            $row->ship_pinjam    = $shipInfo['pinjam'] ?? null;
            $row->ship_kembali   = $shipInfo['kembali'] ?? null;
            $row->has_inspec_doc = $shipInfo['has_inspec_doc'] ?? false;
            $row->no_inspec      = $shipInfo['no_inspec'] ?? null;
            $row->inspec_hasil   = $shipInfo['inspec_hasil'] ?? null; // sebelumnya hilang di loop INI
            $row->inspec_aql     = $shipInfo['inspec_aql'] ?? null;   // BARU
        
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

        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);

        $nocar     = trim((string) $request->input('nocar'));
        $nobar     = trim((string) $request->input('nobar'));
        $breakdown = $request->input('breakdown', []);

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

            $dupCartonQuery = $db->table('pack')->where('POno', $po)->where('OP', $op)->where('carton', $nocar);
            if ($excludedPackpks->isNotEmpty()) {
                $dupCartonQuery->whereNotIn('packpk', $excludedPackpks);
            }
            $dupCarton = $dupCartonQuery->first();
            if ($dupCarton) {
                DB::connection($connection)->rollBack();
                return response()->json([
                    'icon'  => 'warning',
                    'title' => "No Carton <b>{$nocar}</b> sudah digunakan pada PO/OP ini.",
                ], 422);
            }

            if ($nobar !== '') {
                $dupNobarQuery = $db->table('pack')->where('POno', $po)->where('OP', $op)->where('nobar', $nobar);
                if ($excludedPackpks->isNotEmpty()) {
                    $dupNobarQuery->whereNotIn('packpk', $excludedPackpks);
                }
                $dupNobar = $dupNobarQuery->first();
                if ($dupNobar) {
                    DB::connection($connection)->rollBack();
                    return response()->json([
                        'icon'  => 'warning',
                        'title' => "No Barcode <b>{$nobar}</b> sudah digunakan pada PO/OP ini.",
                    ], 422);
                }
            }

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
                    // Fallback -- SAMA seperti sebelumnya, dipakai kalau payload lama
                    // (belum mengirim target_popk) atau kasus Auto Split yang cuma 1
                    // popk (tidak ambigu).
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

                //  tandai popk ini sebagai bagian dari sistem Global
                // (gabung = 7), supaya bisa dibedakan dari po lama yang masih
                // pakai skema gabung 1-10 (per-popk).
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
                $kali  = (int) floor($bagi);
                $sisa  = ($bagi - $kali) * $singleQty;
                $kali4 = $sisa > 0 ? 1 : 0;
                $nobarBase = $nobar !== '' ? substr($nobar, 0, -4) : '';

                $generatedRows = [];
                for ($offset = 0; $offset <= ($kali - 1); $offset++) {
                    $generatedRows[] = [
                        'carton' => $this->incrementCartonNumber($nocar, $offset),
                        'nobar'  => $nobarBase !== '' ? $nobarBase . sprintf('%04d', $offset + 1) : '',
                        'qty'    => $singleQty,
                    ];
                }
                if ($sisa > 0) {
                    $generatedRows[] = [
                        'carton' => $this->incrementCartonNumber($nocar, $kali),
                        'nobar'  => $nobarBase !== '' ? $nobarBase . sprintf('%04d', $kali + 1) : '',
                        'qty'    => (int) $sisa,
                    ];
                }

                $generatedCartons = array_column($generatedRows, 'carton');
                $generatedNobars  = array_filter(array_column($generatedRows, 'nobar'));

                $dupCartonSplit = $db->table('pack')->where('POno', $poRow->POno)->where('OP', $poRow->OP)
                    ->whereIn('carton', $generatedCartons)->first();
                if ($dupCartonSplit) {
                    DB::connection($connection)->rollBack();
                    return response()->json(['icon' => 'warning', 'title' => "No Carton <b>{$dupCartonSplit->carton}</b> (hasil Auto Split) sudah digunakan pada PO/OP ini."], 422);
                }
                if (!empty($generatedNobars)) {
                    $dupNobarSplit = $db->table('pack')->where('POno', $poRow->POno)->where('OP', $poRow->OP)
                        ->whereIn('nobar', $generatedNobars)->first();
                    if ($dupNobarSplit) {
                        DB::connection($connection)->rollBack();
                        return response()->json(['icon' => 'warning', 'title' => "No Barcode <b>{$dupNobarSplit->nobar}</b> (hasil Auto Split) sudah digunakan pada PO/OP ini."], 422);
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
                        'meas' => $request->meas,
                        'secsz' => $poRow->secsz,
                        'keterangan' => $request->ket2,
                        'pcsp' => $gen['qty'],
                        'tanggal' => now(),
                        'waktu' => now(),
                        'status' => 4,
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
                $packpk     = (int) ($lines->first()['packpk'] ?? 0);
                $targetPopk = (int) ($lines->first()['target_popk'] ?? 0); // BARU

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

                    $qtyp[$idx] = $planVal;
                    if ($actualVal > 0) $qty[$idx] = $actualVal;
                    $totalPlanGroup   += $planVal;
                    $totalActualGroup += $actualVal;
                }

                // resolve poRow via target_popk kalau ada, BUKAN lagi
                // SELALU re-query material+secsz (yang ambigu kalau ada popk
                // kembaran).
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

                // tandai popk ini sebagai bagian dari sistem Global (gabung = 7).
                $db->table('po')->where('popk', $poRow->popk)->update(['gabung' => 7]);

                // ============================================================
                // Validasi 2 -- total Actual GABUNGAN semua carton popk ini
                // tidak boleh melebihi Transfer. FIX: Transfer sekarang bj +
                // output (lewat getTransferQtyGlobal()), bukan cuma bj.
                // ============================================================
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
                    'meas'       => $request->meas,
                    'secsz'      => $poRow->secsz,
                    'keterangan' => $request->ket2,
                    'pcsp'       => $totalPlanGroup,
                    'pcs'        => $totalActualGroup > 0 ? $totalActualGroup : null,
                    'tanggal'    => now(),
                    'waktu'      => now(),
                    'status'     => 4,
                ];
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
                // 'title' => $e->getMessage(), // debug
            ], 500);
        }
    }

    // Proses simpan Input Actual Massal di halaman input FG/Stuffing
    // Modal modal-actual-ctn-global.blade.php
    public function updateCtnGlobal(Request $request)
    {
        $request->validate([
            'packpk' => 'required',
        ], [
            'packpk.required' => 'Pilih minimal satu carton.',
        ]);

        $mif        = (int) $request->input('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);

        DB::connection($connection)->beginTransaction();

        try {
            $size = $request->size; // '' = semua size, atau index tertentu
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

            $remaining = []; // [popk][size] => sisa Transfer SEBENARNYA
            $isFilled  = []; // [popk][size][packpk] => true/false/null

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
                // 'title' => $e->getMessage(), // debug
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

    // Proses delete Input Actual Massal di halaman input FG/Stuffing
    // Modal modal-delete-actual-ctn-global.blade.php
    public function deleteActualGlobal(Request $request)
    {
        $request->validate([
            'packpk' => 'required',
        ], [
            'packpk.required' => 'Pilih minimal satu carton.'
        ]);

        //  resolve connection via mif -- SEBELUMNYA tidak ada
        // sama sekali, selalu pakai koneksi default (bisa salah database).
        $mif        = (int) $request->input('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
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

    // Proses Copy Carton, dengan nilai Plan dan Actual(Jika sisa) Massal di halaman input FG/Stuffing
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

        $connection = $this->resolveConnection($mif);
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

            // Group berdasarkan NOMOR CARTON ASLI -- 1 carton fisik (lintas
            // banyak popk kalau Mixed) di-copy sebagai 1 kesatuan.
            $rowsByCarton = $rows->groupBy('carton');

            // ============================================================
            // Sisa Transfer dihitung TERPISAH per popk. Transfer
            // sekarang bj + output (lewat getTransferQtyGlobal()), bukan
            // cuma bj seperti sebelumnya.
            // ============================================================
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

            // Kumpulkan SEMUA nomor carton yang SUDAH DIPAKAI di scope
            // PO+OP+poref ini (lintas SEMUA popk) -- lookup set untuk cek
            // tabrakan nama carton hasil copy.
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

                $suffixIndex   = 0; // 0 => ' copy', 1 => ' copy 1', 2 => ' copy 2', dst
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
                        $new['carton']  = $candidateCarton;
                        $new['nobar']   = $row->nobar ? ($row->nobar . $suffix) : $row->nobar;
                        $new['tanggal'] = now();
                        $new['waktu']   = now();

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
                                    // Cukup penuh.
                                    $new["qty{$i}"] = $actualAsal;
                                    $remaining[$popkKey][$i] -= $actualAsal;
                                    $pcsActual += $actualAsal;
                                    $totalActualDicopy++;
                                } elseif ($avail > 0) {
                                    // FIX: PARTIAL -- isi sebesar sisa yang ADA, bukan nol.
                                    $new["qty{$i}"] = $avail;
                                    $remaining[$popkKey][$i] -= $avail;
                                    $pcsActual += $avail;
                                    $totalActualDitolak++;
                                    $adaPartialDiCarton = true;
                                    $sizeHabisInfoByPopk[$popkKey][$i] = true;
                                } else {
                                    // Sisa benar-benar 0 -- tidak ada yang bisa diisi.
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
                // 'title' => $e->getMessage(), // debug
            ], 500);
        }
    }

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

        $mif = (int) $request->input('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
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

            // Update po.ctn untuk SETIAP popk yang terdampak.
            // ============================================================
            // FIX: renumbering DIHAPUS total -- carton yang tersisa TIDAK
            // diurutkan ulang lagi. Nomor yang "bolong" (misal 1,2,3,5
            // setelah 4 dihapus) dibiarkan apa adanya.
            // ============================================================
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
                // 'title' => $e->getMessage(), // debug
            ], 500);
        }
    }

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

        // resolve connection via mif -- SEBELUMNYA tidak ada
        // sama sekali, selalu pakai koneksi default (bisa salah database).
        $mif        = (int) $request->input('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
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

        $connection = $this->resolveConnection($mif);
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

            // Group berdasarkan CARTON FISIK -- 1 nomor carton (lintas semua
            // popk kalau Mixed) diperlakukan sebagai 1 kesatuan.
            $groupedByCarton = [];
            $naturalOrder = []; // urutan ALAMI (urut+packpk) -- dipakai mode carton
            foreach ($allPacks as $row) {
                $key = $row->carton;
                if (!isset($groupedByCarton[$key])) {
                    $groupedByCarton[$key] = [];
                    $naturalOrder[] = $key;
                }
                $groupedByCarton[$key][] = $row;
            }

            // ============================================================
            // MODE: CARTON -- nomor carton diurutkan ulang. Opsional: kalau
            // 'pair_barcode' dicentang, Barcode JUGA diurutkan bersamaan
            // (berpasangan 1:1 dengan urutan carton). Kalau tidak, Barcode
            // ikut pola LAMA (prefix tetap, digit belakang ikut offset).
            // ============================================================
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
                        // Barcode ikut diurutkan bersamaan, berpasangan 1:1
                        // dengan urutan carton (bukan cuma ganti digit).
                        $updateData['nobar'] = $this->incrementCartonNumber($barcodeAwal, $offset);
                    }
                    // FIX: kalau TIDAK dicentang, 'nobar' TIDAK dimasukkan ke
                    // $updateData sama sekali -- barcode dibiarkan APA ADANYA,
                    // tidak ikut berubah sedikit pun (sebelumnya barcode tetap
                    // ikut di-generate ulang mengikuti pola lama, sekarang
                    // sengaja tidak disentuh kalau checkbox tidak dicentang).

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

            // ============================================================
            // MODE: BARCODE -- carton TIDAK disentuh sama sekali. Urutan
            // yang dipakai SELALU berdasarkan ANGKA nomor carton (kecil ->
            // besar), supaya barcode akhirnya konsisten dengan urutan carton
            // yang sudah ada.
            //
            // Sub-mode 'otomatis' : proses SEMUA carton dari yang terkecil.
            // Sub-mode 'manual'   : proses MULAI DARI carton tertentu yang
            //                       ditentukan user (harus SUDAH ADA di
            //                       sistem -- divalidasi) -- carton-carton
            //                       SEBELUM itu (dalam urutan angka) TIDAK
            //                       ikut diubah barcode-nya.
            // ============================================================
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
                    $db->table('pack')->where('packpk', $row->packpk)->update([
                        'nobar' => $newNobar,
                    ]);
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
                // 'title' => $e->getMessage(), // debug
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

    public function cardsInfoGlobal(Request $request)
    {
        $po    = $request->query('po');
        $op    = $request->query('op');
        $poref = $request->query('poref');
        $mif   = (int) $request->query('mif', session('pos'));

        $connection = $this->resolveConnection($mif);
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

        // Sama persis logic yang dipakai inputPackingGlobal() -- reuse
        // ctnSummary (carton fisik unik) untuk Total Carton, dan hitung
        // Sealed Carton terpisah (khusus segel=1 di SEMUA popk-nya).
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

    // ============================================================
    // getBreakdownDataGlobal() yang SUDAH ADA (1 sumber kebenaran,
    // SAMA dengan yang dipakai breakdownSummaryGlobal()).
    // ============================================================
    public function combosGlobal(Request $request)
    {
        $po    = $request->query('po');
        $op    = $request->query('op');
        $poref = $request->query('poref');
        $mif   = (int) $request->query('mif', session('pos'));

        $connection = $this->resolveConnection($mif);
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
                    'secsz'            => $g['secsz'],
                    'popk'             => $popks->first(),
                    'orderQty'         => $g['orderQty'],
                    'planQty'          => $g['planQty'],
                    'readyQty'         => $g['readyQty'],
                    'transQty'         => $g['transQty'],
                    'duplicateMarker'  => null,
                ]);
                continue;
            }

            // >1 popk share customer+material+secsz SAMA -- pecah per popk,
            // kasih penanda.
            foreach ($popks as $idx => $popk) {
                [$orderQty, $planQty, $readyQty, $transQty] = $this->getSinglePopkQtyBreakdown($db, (int) $popk, $activeSizes);

                $colorSecszCombos->push([
                    'material'        => $g['material'],
                    'secsz'            => $g['secsz'],
                    'popk'             => $popk,
                    'orderQty'         => $orderQty,
                    'planQty'          => $planQty,
                    'readyQty'         => $readyQty,
                    'transQty'         => $transQty,
                    'duplicateMarker'  => $idx + 1, //  cukup angka (1, 2, dst)
                ]);
            }
        }

        return $colorSecszCombos->values();
    }

    public function bulkShipAction(Request $request)
    {
        $request->validate([
            'packpk' => 'required|string',
            'action' => 'required|in:inspect,shipment,lock,request_return,accept_return',
            'reject' => 'nullable|in:0,1', // BARU -- hanya relevan untuk action='request_return'
        ]);

        $mif        = (int) $request->input('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);

        $ids = array_values(array_filter(explode(',', $request->packpk)));
        if (empty($ids)) {
            return response()->json([
                'icon'  => 'warning',
                'title' => 'Pilih minimal satu carton.',
            ], 422);
        }

        $action = $request->action;

        if ($action === 'inspect') {
            $label   = 'Proses Inspect';
            $updated = $db->table('ship')
                ->whereIn('packpk', $ids)
                ->whereNull('fca')
                ->update(['fca' => 1, 'pinjam' => now()]);

            if ($updated > 0) {
                $db->table('pack')->whereIn('packpk', $ids)->update(['segel' => null]);
            }
        } elseif ($action === 'shipment') {
            $label   = 'Proses Shipment';
            $updated = $db->table('ship')
                ->whereIn('packpk', $ids)
                ->where('status', '<', 6)
                ->update([
                    'status'   => 6,
                    'datescan' => now(),
                ]);
        } elseif ($action === 'lock') {
            $label   = 'End Session (Lock)';
            $updated = $db->table('ship')
                ->whereIn('packpk', $ids)
                ->where('status', '<', 7)
                ->update(['status' => 7]);
        } elseif ($action === 'request_return') {
            // konversi packpk -> shippk dulu (1 packpk = 1
            // shippk lewat ship.packpk), karena getLatestInspecHasilForShippks()
            // query berdasarkan inspecdt.shippk, BUKAN packpk.
            $shippksForSelected = $db->table('ship')
                ->whereIn('packpk', $ids)
                ->pluck('shippk')
                ->filter()
                ->unique()
                ->values()
                ->all();
        
            $hasil = $this->getLatestInspecHasilForShippks($db, $shippksForSelected); // FIX -- pakai shippk, bukan $ids (packpk)
        
            if ($hasil === null) {
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Carton ini belum punya Dokumen Inspect. Buat Dokumen Inspect dulu sebelum bisa dikembalikan.',
                ], 422);
            }
        
            $isReject = $hasil === 0;
        
            $label   = $isReject ? 'Kembalikan ke FinishGood (Reject)' : 'Kembalikan ke FinishGood (Lulus)';
            $updated = $db->table('ship')
                ->whereIn('packpk', $ids)
                ->where('fca', 1)
                ->update(['fca' => 2]);
        
            if ($isReject) {
                $affectedParts = $db->table('ship')
                    ->whereIn('packpk', $ids)
                    ->whereNotNull('part')
                    ->pluck('part')
                    ->unique()
                    ->values();
        
                if ($affectedParts->isNotEmpty()) {
                    $db->table('pack')
                        ->whereIn('part', $affectedParts)
                        ->update(['reject' => 1, 'segel' => null]);
                } else {
                    $db->table('pack')->whereIn('packpk', $ids)->update(['reject' => 1, 'segel' => null]);
                }
            } else {
                $db->table('pack')->whereIn('packpk', $ids)->update(['reject' => null]);
            }
        } else { // 'accept_return'
            // TAHAP 2 dari alur 2-tahap (dipicu dari halaman FG/FinishGood, tombol
            // "Terima Carton"). fca: 2 -> null, DAN 'kembali' BARU diisi sekarang.
            // pack.reject TIDAK diubah di sini -- status Reject/tidak-nya TETAP
            // dipertahankan apa adanya, supaya FG/FinishGood tahu carton mana yang
            // masih perlu rework sebelum bisa di-Seal ulang.
            $label   = 'Terima Carton dari Inspect';
            $updated = $db->table('ship')
                ->whereIn('packpk', $ids)
                ->where('fca', 2)
                ->update(['fca' => null, 'kembali' => now()]);
        }

        if ($updated < 1) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "{$label}: tidak ada carton yang memenuhi syarat (mungkin sudah diproses sebelumnya).",
            ], 422);
        }

        return response()->json([
            'icon'  => 'success',
            'title' => "{$label}: {$updated} baris berhasil diproses.",
        ]);
    }

    public function cardsSummaryGlobal(Request $request)
    {
        $isSuper = session('guserpk') == 34;
        $mifs = $isSuper ? [1, 2] : [((int) session('pos') === 1) ? 1 : 2];
        $perMif = [];
    
        foreach ($mifs as $mif) {
            $connection = $this->resolveConnection($mif);
            $db = DB::connection($connection);
    
            // ---- Shipped (status IN 6,7) -- COUNT DISTINCT LANGSUNG di SQL,
            //      JOIN (bukan whereExists) supaya optimizer MySQL lebih cepat. ----
            $shippedCount = (int) $db->table('ship')
                ->join('po', 'po.popk', '=', 'ship.popk')
                ->where('po.mif', $mif)
                ->whereIn('ship.status', [6, 7])
                ->selectRaw("COUNT(DISTINCT CONCAT(ship.popk, '|', ship.carton)) as cnt")
                ->value('cnt');
    
            // ---- Inspecting (fca=1) -- SAMA pola ----
            $inspectingCount = (int) $db->table('ship')
                ->join('po', 'po.popk', '=', 'ship.popk')
                ->where('po.mif', $mif)
                ->where('ship.fca', 1)
                ->selectRaw("COUNT(DISTINCT CONCAT(ship.popk, '|', ship.carton)) as cnt")
                ->value('cnt');
    
            // ---- Sealed (segel=1) -- metrik referensi saja ----
            $sealedCount = (int) $db->table('pack')
                ->join('po', 'po.popk', '=', 'pack.popk')
                ->where('po.mif', $mif)
                ->where('pack.segel', 1)
                ->selectRaw("COUNT(DISTINCT CONCAT(pack.popk, '|', pack.carton)) as cnt")
                ->value('cnt');
    
            // ---- Ready: carton (SEMUA, tanpa syarat segel) yang SUDAH ada
            //      Actual (SUM(pcs) > 0) DAN belum ada packpk-nya SAMA
            //      SEKALI di ship. 1 query: JOIN + LEFT JOIN + GROUP BY +
            //      HAVING, dibungkus subquery supaya COUNT()-nya juga di
            //      SQL (TIDAK ditarik ke PHP).
            $readyCount = $db->table('pack')
                ->join('po', 'po.popk', '=', 'pack.popk')
                ->leftJoin('ship', 'ship.packpk', '=', 'pack.packpk')
                ->where('po.mif', $mif)
                ->groupBy('pack.popk', 'pack.carton')
                ->havingRaw('SUM(pack.pcs) > 0')
                ->havingRaw('COUNT(ship.packpk) = 0')
                ->select('pack.popk')
                ->get()
                ->count();
    
            $perMif[$mif] = [
                'total_sealed_carton'  => $sealedCount,
                'total_shipped_carton' => $shippedCount,
                'total_ready_carton'   => $readyCount,
                'total_inspect_carton' => $inspectingCount,
            ];
        }
    
        if ($isSuper) {
            return response()->json([
                'total_sealed_carton'  => array_sum(array_column($perMif, 'total_sealed_carton')),
                'total_shipped_carton' => array_sum(array_column($perMif, 'total_shipped_carton')),
                'total_ready_carton'   => array_sum(array_column($perMif, 'total_ready_carton')),
                'total_inspect_carton' => array_sum(array_column($perMif, 'total_inspect_carton')),
                'per_mif'              => $perMif,
            ]);
        }
    
        $ownMif = $mifs[0];
        return response()->json(array_merge(
            $perMif[$ownMif] ?? [
                'total_sealed_carton' => 0, 'total_shipped_carton' => 0,
                'total_ready_carton' => 0, 'total_inspect_carton' => 0,
            ],
            ['per_mif' => $perMif]
        ));
    }

    public function scanNobarGlobal(Request $request)
    {
        $po    = $request->input('po');
        $op    = $request->input('op');
        $poref = $request->input('poref');
        $mif   = (int) $request->input('mif', session('pos'));
        $nobar = trim((string) $request->input('nobar'));
    
        if ($nobar === '') {
            return response()->json(['icon' => 'warning', 'title' => 'Nobar kosong.'], 422);
        }
    
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);
    
        // Scope popk ke PO+OP+poref ini -- SAMA pola dengan listDetailGlobal().
        $popks = $db->table('po')
            ->where('OP', $op)
            ->where('mif', $mif)
            ->when(
                $po !== null && $po !== '',
                fn($q) => $q->where('POno', $po),
                fn($q) => $q->where(fn($qq) => $qq->whereNull('POno')->orWhere('POno', ''))
            )
            ->when(
                $poref !== null && $poref !== '',
                fn($q) => $q->where('poref', $poref),
                fn($q) => $q->where(fn($qq) => $qq->whereNull('poref')->orWhere('poref', ''))
            )
            ->pluck('popk');
    
        if ($popks->isEmpty()) {
            return response()->json(['icon' => 'warning', 'title' => 'Data PO/OP tidak ditemukan.'], 422);
        }
    
        $matchedRow = $db->table('pack')
            ->whereIn('popk', $popks)
            ->where('nobar', $nobar)
            ->first();
    
        if (!$matchedRow) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Nobar \"{$nobar}\" tidak ditemukan pada PO/OP ini.",
            ], 422);
        }
    
        $carton = $matchedRow->carton;
    
        // Ambil SEMUA baris carton fisik yang SAMA (carton Mixed bisa >1
        // popk/packpk sekaligus).
        $cartonRows = $db->table('pack')
            ->whereIn('popk', $popks)
            ->where('carton', $carton)
            ->get();
    
        $packpks = $cartonRows->pluck('packpk')->values()->all();
    
        $shipRows = $db->table('ship')->whereIn('packpk', $packpks)->get();
    
        // Validasi 1: sudah Shipped?
        $anyShipped = $shipRows->contains(fn ($r) => in_array((int) $r->status, [6, 7], true));
        if ($anyShipped) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Carton <b>{$carton}</b> sudah Shipment sebelumnya.",
            ], 422);
        }
    
        // Validasi 2: sedang Inspect?
        $anyInspecting = $shipRows->contains(fn ($r) => (int) $r->fca === 1);
        if ($anyInspecting) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Carton <b>{$carton}</b> sedang proses Inspect, tidak bisa langsung Shipment.",
            ], 422);
        }
    
        // Validasi 3: sudah Segel?
        $allSegel = $cartonRows->every(fn ($r) => (int) $r->segel === 1);
        if (!$allSegel) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Carton <b>{$carton}</b> belum Segel, tidak bisa diproses Shipment.",
            ], 422);
        }
    
        // Validasi 4: sudah punya Part?
        $hasPart = $cartonRows->contains(fn ($r) => $r->part !== null && $r->part !== '' && (int) $r->part !== 0);
        if (!$hasPart) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Carton <b>{$carton}</b> belum punya Session, tidak bisa diproses Shipment.",
            ], 422);
        }

        // Validasi 5: session (exportpk) carton ini SUDAH dimulai di EXIM?
        $exportpk = $matchedRow->exportpk ?? null;
        $contpk   = $matchedRow->contpk ?? null; // pakai contpk milik carton ini langsung
        
        if (!$exportpk) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Carton <b>{$carton}</b> belum punya Session Export (exportpk), tidak bisa diproses Shipment.",
            ], 422);
        }
        if (!$contpk) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Carton <b>{$carton}</b> belum punya Container (contpk), tidak bisa diproses Shipment.",
            ], 422);
        }
        
        try {
            $eximResponse = \Illuminate\Support\Facades\Http::timeout(10)
                ->get('http://192.168.0.8/EXIM2/api/getListExport');
        } catch (\Throwable $e) {
            return response()->json([
                'icon'  => 'error',
                'title' => 'Sistem EXIM tidak dapat dihubungi, gagal memvalidasi status Session.',
            ], 500);
        }
        
        // cocokkan row EXIM lewat KOMBINASI (exportpk,
        // contpk) milik carton ini -- akurat per-container, karena 1 export
        // bisa punya banyak container yang statusnya independen.
        $eximRow = collect($eximResponse->json('rows') ?? [])
            ->first(fn ($r) =>
                (int) ($r['exportpk'] ?? 0) === (int) $exportpk
                && (int) ($r['contpk'] ?? 0) === (int) $contpk
            );
        
        if (!$eximRow) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Data Container untuk carton <b>{$carton}</b> tidak ditemukan di EXIM.",
            ], 422);
        }
        
        $sessionStarted = !empty($eximRow['start_ship']) && empty($eximRow['end_ship']);
        if (!$sessionStarted) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Container untuk carton <b>{$carton}</b> belum dimulai (klik \"Mulai\" di Shipment Plan dulu sebelum bisa Shipment).",
            ], 422);
        }
        
        // Jalankan aksi Shipment -- SAMA PERSIS logic dengan bulkShipAction('shipment').
        $updated = $db->table('ship')
            ->whereIn('packpk', $packpks)
            ->where('status', '<', 6)
            ->update([
                'status'   => 6,
                'datescan' => now(), 
            ]);
    
        if ($updated < 1) {
            return response()->json([
                'icon'  => 'warning',
                'title' => "Carton <b>{$carton}</b>: tidak ada baris yang memenuhi syarat (mungkin sudah diproses).",
            ], 422);
        }
    
        return response()->json([
            'icon'        => 'success',
            'title'       => "Carton <b>{$carton}</b> berhasil diproses Shipment via scan.",
            'carton'      => $carton,
            'carton_part' => $matchedRow->part, 
        ]);
    }

    public function partSummaryGlobal(Request $request)
    {
        $po    = $request->query('po');
        $op    = $request->query('op');
        $poref = $request->query('poref');
        $mif   = (int) $request->query('mif', session('pos'));

        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);

        $popks = $db->table('po')
            ->where('OP', $op)
            ->where('mif', $mif)
            ->when($po !== null && $po !== '', fn($q) => $q->where('POno', $po))
            ->when($poref !== null && $poref !== '', fn($q) => $q->where('poref', $poref))
            ->pluck('popk');

        if ($popks->isEmpty()) {
            return response()->json(['parts' => []]);
        }

        $packRows = $db->table('pack')
            ->whereIn('popk', $popks)
            ->whereNotNull('exportpk')
            ->select('packpk', 'carton', 'popk', 'part', 'exportpk', 'contpk')
            ->get();

        if ($packRows->isEmpty()) {
            return response()->json(['parts' => []]);
        }

        $exportpks = $packRows->pluck('exportpk')->unique()->filter()->values();

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->get('http://192.168.0.8/EXIM2/api/getListExport');

            if (!$response->successful()) {
                return response()->json(['parts' => [], 'error' => 'Gagal mengambil data dari sistem EXIM.']);
            }

            $eximRows = collect($response->json('rows') ?? []);
        } catch (\Throwable $e) {
            return response()->json(['parts' => [], 'error' => 'Sistem EXIM tidak dapat dihubungi.']);
        }

        // filter stsexp=1 DIHAPUS -- shipment plan
        // SEKARANG tetap tampil walau EXIM belum approve (stsexp apa pun).
        $relevantExim = $eximRows->filter(fn ($r) => $exportpks->contains((int) ($r['exportpk'] ?? 0)));

        $exportMetaByExportpk = $relevantExim->groupBy('exportpk')->map(fn ($rows) => $rows->first());

        $contMetaByKey = $relevantExim
            ->filter(fn ($r) => !empty($r['contpk']))
            ->groupBy(fn ($r) => $r['exportpk'] . '|' . $r['contpk'])
            ->map(fn ($rows) => $rows->first());

        $shipRowsMap = $db->table('ship')
            ->whereIn('packpk', $packRows->pluck('packpk')->unique())
            ->select('packpk', 'status')
            ->get()
            ->keyBy('packpk');

        $parts = [];
        foreach ($packRows->groupBy('exportpk') as $exportpk => $rowsForExport) {
            $exportMeta = $exportMetaByExportpk->get($exportpk);
            if (!$exportMeta) continue; // exportpk ini tidak ditemukan sama sekali di EXIM

            $cartonUnique = $rowsForExport->pluck('carton')->filter()->unique();
            $total = $cartonUnique->count();

            $partComplete = $rowsForExport->contains(fn ($r) => (string) $r->part === '10');

            $shippedCartons = $rowsForExport->filter(function ($r) use ($shipRowsMap) {
                $status = (int) ($shipRowsMap[$r->packpk]->status ?? 0);
                return in_array($status, [6, 7], true);
            })->pluck('carton')->filter()->unique();

            $lockedCartons = $rowsForExport->filter(function ($r) use ($shipRowsMap) {
                $status = (int) ($shipRowsMap[$r->packpk]->status ?? 0);
                return $status === 7;
            })->pluck('carton')->filter()->unique();

            $containers = $rowsForExport->groupBy('contpk')->map(function ($rowsForCont, $contpk) use ($exportpk, $contMetaByKey) {
                $meta = $contMetaByKey->get($exportpk . '|' . $contpk);
                return [
                    'contpk'     => (int) $contpk,
                    'contno'     => $meta['contno'] ?? null,
                    'type'       => $meta['type'] ?? null,
                    'typenm'     => $meta['typenm'] ?? null,
                    'qty_ctn'    => $rowsForCont->pluck('carton')->filter()->unique()->count(),
                    'start_ship' => $meta['start_ship'] ?? null,
                    'end_ship'   => $meta['end_ship'] ?? null,
                    'segel'      => $meta['segel'] ?? null,
                ];
            })->values();

            $containerStartTimes = $containers->pluck('start_ship')->filter()->values();
            $containerSegelTimes = $containers->pluck('segel')->filter()->values();

            $startship = $containerStartTimes->isNotEmpty() ? $containerStartTimes->min() : null;
            $endship   = ($containers->isNotEmpty() && $containerSegelTimes->count() === $containers->count())
                ? $containerSegelTimes->max()
                : null;

            $parts[] = [
                'part'          => $exportpk,
                'exportpk'      => $exportpk,
                'pebno'         => $exportMeta['pebno'] ?? null,
                'exdate'        => $exportMeta['exdate'] ?? null,
                'ship_date'     => $exportMeta['exdate'] ?? null,
                'etdvess'       => $exportMeta['etdvess'] ?? null,
                'etavess'       => $exportMeta['etavess'] ?? null,
                'vessname'      => $exportMeta['vessname'] ?? null,
                'remark'        => $exportMeta['remark'] ?? null,
                'actcontdate'   => $exportMeta['actcontdate'] ?? null, // BARU -- FIX UTAMA
                'startship'     => $startship,
                'endship'       => $endship,
                'part_complete' => $partComplete,
                'total'         => $total,
                'shipped'       => $shippedCartons->count(),
                'locked'        => $lockedCartons->count(),
                'containers'    => $containers,
            ];
        }

        usort($parts, fn($a, $b) => $a['exportpk'] <=> $b['exportpk']);

        foreach ($parts as $idx => &$p) {
            $p['session_no'] = $idx + 1;
        }
        unset($p);

        return response()->json(['parts' => $parts]);
    }

    public function eximUpdateShipment(Request $request)
    {
        $request->validate([
            'exportpk' => 'required|integer',
            'contpk'   => 'required|integer',
            'action'   => 'required|in:start,end',
        ]);

        $exportpk = (int) $request->input('exportpk');
        $contpk   = (int) $request->input('contpk');
        $action   = $request->input('action');
        $now      = now()->format('Y-m-d H:i:s');

        $payload = [
            'exportpk' => $exportpk,
            'contpk'   => $contpk, 
        ];
        if ($action === 'start') {
            $payload['start_ship'] = $now;
        } else {
            $payload['end_ship'] = $now;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->asJson()
                ->post('http://192.168.0.8/EXIM2/api/updateShipment', $payload);

            if (!$response->successful()) {
                return response()->json([
                    'icon'   => 'error',
                    'title'  => 'Gagal mengirim data ke sistem EXIM (respons tidak sukses).',
                    'detail' => $response->body(),
                ], 422);
            }

            $label = $action === 'start' ? 'Mulai Session' : 'End Session';
            return response()->json([
                'icon'  => 'success',
                'title' => "{$label} berhasil dikirim ke sistem EXIM.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'icon'  => 'error',
                'title' => 'Sistem EXIM tidak dapat dihubungi.',
            ], 500);
        }
    }

    public function inspectAvailableCartons(Request $request)
    {
        $po   = $request->po;
        $op   = $request->op;
        $mif  = (int) $request->input('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);
    
        $qtyColumns  = collect(range(1, 40))->map(fn($i) => "ship.qty{$i}")->implode(', ');
        $sizeColumns = collect(range(1, 40))->map(fn($i) => "po.size{$i}")->implode(', ');
    
        $rows = $db->table('ship')
            ->join('po', 'po.popk', '=', 'ship.popk')
            ->where('ship.POno', $po)
            ->where('ship.OP', $op)
            ->where('ship.fca', 1)
            ->selectRaw("
                ship.shippk, ship.carton, ship.nobar, ship.material, ship.secsz, ship.part, ship.pcs,
                {$qtyColumns}, {$sizeColumns}
            ")
            ->orderBy('ship.carton')
            ->get();
    
        foreach ($rows as $row) {
            $sizes = [];
            for ($i = 1; $i <= 40; $i++) {
                $label = $row->{"size{$i}"} ?? null;
                $qty   = $row->{"qty{$i}"} ?? null;
                if (!empty($label) && (int) $qty > 0) {
                    $sizes[] = ['label' => $label, 'qty' => (int) $qty];
                }
                unset($row->{"size{$i}"}, $row->{"qty{$i}"});
            }
            $row->sizes = $sizes;
        }
    
        return response()->json(['rows' => $rows]);
    }
    
    
    /**
     * STORE -- simpan dokumen inspec + inspecdt + inspecsz.
     * lines: [{ shippk, size, color, secsz, qty }]
     */
    public function storeInspecDocument(Request $request)
    {
        $validated = $request->validate([
            'aql'           => 'required|numeric|min:0',
            'hasil'         => 'required|in:0,1',
            'lines'         => 'required|array|min:1',
            'lines.*.shippk' => 'required|integer',
            'lines.*.size'   => 'required|string',
            'lines.*.color'  => 'nullable|string',
            'lines.*.secsz'  => 'nullable|string',
            'lines.*.qty'    => 'required|numeric|min:0.01',
        ]);
    
        $mif        = (int) $request->input('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);
    
        $totPcs = collect($validated['lines'])->sum('qty');
    
        try {
            $newInspecpk = DB::connection($connection)->transaction(function () use ($db, $validated, $totPcs) {
                $newInspecpk = (int) ($db->table('inspec')->lockForUpdate()->max('inspecpk')) + 1;
                $db->table('inspec')->insert([
                    'inspecpk' => $newInspecpk,
                    'tgl'      => now(),
                    'aql'      => $validated['aql'],
                    'totpcs'   => $totPcs,
                    'hasil'    => (int) $validated['hasil'],
                ]);
    
                // Group lines per shippk -- 1 shippk = 1 baris inspecdt,
                // bisa punya BEBERAPA baris inspecsz (beda size/color).
                $byShippk = collect($validated['lines'])->groupBy('shippk');
    
                $newInspecdtpk = (int) ($db->table('inspecdt')->lockForUpdate()->max('inspecdtpk'));
                $newInspecszpk = (int) ($db->table('inspecsz')->lockForUpdate()->max('inspecszpk'));
    
                foreach ($byShippk as $shippk => $lines) {
                    $newInspecdtpk++;
                    $db->table('inspecdt')->insert([
                        'inspecdtpk' => $newInspecdtpk,
                        'inspecpk'   => $newInspecpk,
                        'shippk'     => $shippk,
                    ]);
    
                    foreach ($lines as $line) {
                        $newInspecszpk++;
                        $db->table('inspecsz')->insert([
                            'inspecszpk' => $newInspecszpk,
                            'inspecdtpk' => $newInspecdtpk,
                            'size'       => $line['size'],
                            'color'      => $line['color'] ?? null,
                            'secsz'      => $line['secsz'] ?? null,
                            'qty'        => $line['qty'],
                        ]);
                    }
                }
    
                return $newInspecpk;
            });
    
            $hasilLabel = (int) $validated['hasil'] === 1 ? 'LULUS' : 'REJECT';
            return response()->json([
                'icon'  => (int) $validated['hasil'] === 1 ? 'success' : 'warning',
                'title' => "Dokumen Inspect #{$newInspecpk} tersimpan. Hasil: {$hasilLabel}.",
                'inspecpk' => $newInspecpk,
                'hasil' => (int) $validated['hasil'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal menyimpan dokumen inspect.'], 500);
        }
    }
    
    
    /**
     * BARU -- helper: cari dokumen inspec TERBARU yang menyangkut carton
     * (shippk) manapun dalam 'part' yang sama dengan carton yang mau
     * dikembalikan. Dipakai bulkShipAction('request_return') untuk
     * menentukan reject massal.
     */
    private function getLatestInspecHasilForShippks($db, array $shippks): ?int
    {
        if (empty($shippks)) return null;
    
        $latest = $db->table('inspecdt')
            ->join('inspec', 'inspec.inspecpk', '=', 'inspecdt.inspecpk')
            ->whereIn('inspecdt.shippk', $shippks) // <-- ini memang shippk, bukan packpk
            ->orderByDesc('inspec.inspecpk')
            ->select('inspec.hasil')
            ->first();
    
        return $latest ? (int) $latest->hasil : null;
    }


    public function shipmentPlanDetailGlobal(Request $request)
    {
        $exportpk = (int) $request->query('exportpk');
        if (!$exportpk) {
            return response()->json(['error' => 'exportpk wajib diisi.'], 422);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->get('http://192.168.0.8/EXIM2/api/getListExport');

            if (!$response->successful()) {
                return response()->json(['error' => 'Gagal mengambil data dari sistem EXIM.'], 502);
            }

            $eximRows = collect($response->json('rows') ?? []);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Sistem EXIM tidak dapat dihubungi.'], 502);
        }

        $rowsForExport = $eximRows->filter(fn ($r) => (int) ($r['exportpk'] ?? 0) === $exportpk);
        if ($rowsForExport->isEmpty()) {
            return response()->json(['error' => 'Data shipment plan tidak ditemukan di EXIM.'], 404);
        }

        $meta = $rowsForExport->first();

        // ============================================================
        // 'factory' DIGABUNG ke tiap chip PO/OP (posisi
        // sama, sama pola dengan sebelumnya), TIDAK LAGI field terpisah.
        // ============================================================
        $ponoParts    = array_map('trim', explode(',', (string) ($meta['POno'] ?? '')));
        $opParts      = array_map('trim', explode(',', (string) ($meta['OP'] ?? '')));
        $factoryParts = array_map('trim', explode(',', (string) ($meta['factory'] ?? '')));

        $poOpPairs = collect(range(0, max(count($ponoParts), count($opParts)) - 1))
            ->map(fn ($i) => [
                'POno'    => $ponoParts[$i] ?? null,
                'OP'      => $opParts[$i] ?? null,
                'factory' => $factoryParts[$i] ?? null,
            ])
            ->filter(fn ($p) => $p['POno'] || $p['OP'])
            ->unique(fn ($p) => $p['POno'] . '|' . $p['OP'])
            ->values();

        // MIF per pasangan PO/OP diambil dari tabel `po`
        // KITA SENDIRI (sumber kebenaran utk mif), bukan dari EXIM -- coba
        // cek di KEDUA koneksi (mysql & mysql_andon) karena belum tahu mif-
        // nya sebelum ketemu barisnya.
        foreach (['mysql', 'mysql_andon'] as $conn) {
            try {
                $matches = DB::connection($conn)->table('po')
                    ->whereIn('POno', $poOpPairs->pluck('POno')->filter()->values())
                    ->whereIn('OP', $poOpPairs->pluck('OP')->filter()->values())
                    ->get(['POno', 'OP', 'mif'])
                    ->keyBy(fn ($r) => $r->POno . '|' . $r->OP);
            } catch (\Throwable $e) {
                continue;
            }
            $poOpPairs = $poOpPairs->map(function ($p) use ($matches) {
                if (!isset($p['mif'])) {
                    $found = $matches->get($p['POno'] . '|' . $p['OP']);
                    if ($found) $p['mif'] = (int) $found->mif;
                }
                return $p;
            });
        }
        $poOpPairs = $poOpPairs->map(fn ($p) => $p + ['mif' => $p['mif'] ?? null])->values();

        // ============================================================
        // daftar CARTON individual per container, masing-
        // masing ditandai POno/OP-nya -- dipakai visualisasi kotak container.
        // exportdt hanya bawa packpk+mif (bukan POno/OP langsung), jadi kita
        // resolve lewat pack->po DI DATABASE KITA SENDIRI, di-batch per mif
        // supaya query-nya sedikit.
        // ============================================================
        $poByPopk = collect();
        $cartonByPackpk = collect();
        $shipStatusByPackpk = collect(); // BARU -- FIX UTAMA
        
        foreach ($rowsForExport->groupBy('mif') as $mifVal => $rowsForMif) {
            $connName = ((int) $mifVal) === 1 ? 'mysql_andon' : 'mysql';
            $packpks = $rowsForMif->pluck('packpk')->filter()->unique()->values();
            if ($packpks->isEmpty()) continue;
        
            $packRows = DB::connection($connName)->table('pack')
                ->whereIn('packpk', $packpks)
                ->get(['packpk', 'popk', 'carton'])
                ->keyBy('packpk');
            $cartonByPackpk = $cartonByPackpk->union($packRows);
        
            $popks = $packRows->pluck('popk')->filter()->unique()->values();
            if ($popks->isNotEmpty()) {
                $poRows = DB::connection($connName)->table('po')
                    ->whereIn('popk', $popks)
                    ->get(['popk', 'POno', 'OP'])
                    ->keyBy('popk');
                $poByPopk = $poByPopk->union($poRows);
            }
        
            // status ship per packpk (utk tandai "sudah masuk").
            $shipRows = DB::connection($connName)->table('ship')
                ->whereIn('packpk', $packpks)
                ->get(['packpk', 'status'])
                ->keyBy('packpk');
            $shipStatusByPackpk = $shipStatusByPackpk->union($shipRows);
        }

        $containers = $rowsForExport->groupBy('contpk')->map(function ($rows) use ($cartonByPackpk, $poByPopk, $shipStatusByPackpk) {
            // $shipStatusByPackpk ditambahkan ke 'use' closure
            // LUAR ini juga -- sebelumnya cuma ada di closure DALAM, padahal PHP
            // butuh variabel itu di-'use' di SETIAP level closure yang membungkusnya
            // (tidak otomatis mewarisi dari scope terluar).
            $f = $rows->first();

            $cartons = $rows->map(function ($r) use ($cartonByPackpk, $poByPopk, $shipStatusByPackpk) {
            $packRow = $cartonByPackpk->get($r['packpk'] ?? null);
            $poRow   = $packRow ? $poByPopk->get($packRow->popk) : null;
            $shipRow = $shipStatusByPackpk->get($r['packpk'] ?? null);
            $shipped = $shipRow && in_array((int) $shipRow->status, [6, 7], true);
            return [
                'carton'  => $packRow->carton ?? $r['carton'] ?? '-',
                'POno'    => $poRow->POno ?? null,
                'OP'      => $poRow->OP ?? null,
                'shipped' => $shipped,
            ];
        })
        // unique berdasarkan KOMBINASI carton+POno+OP, BUKAN
        // carton saja -- carton nomor sama dari PO/OP berbeda TIDAK BOLEH
        // dianggap duplikat/digabung.
        ->unique(fn ($c) => $c['carton'] . '|' . $c['POno'] . '|' . $c['OP'])
        ->sortBy(fn ($c) => $c['carton'], SORT_NATURAL)
        ->values();

            return [
                'contpk'     => $f['contpk'] ?? null,
                'contno'     => $f['contno'] ?? null,
                'type'       => $f['type'] ?? null,
                'typenm'     => $f['typenm'] ?? null,
                'qty_ctn'    => $cartons->count(),
                'start_ship' => $f['start_ship'] ?? null,
                'end_ship'   => $f['end_ship'] ?? null,
                'cartons'    => $cartons,
            ];
        })->values();

        return response()->json([
            'export' => [
                'exportpk'    => $meta['exportpk'] ?? null,
                'buyer'       => $meta['buyer'] ?? null,
                'pebno'       => $meta['pebno'] ?? null,
                'exdate'      => $meta['exdate'] ?? null,
                'contdate'    => $meta['contdate'] ?? null,
                'actcontdate' => $meta['actcontdate'] ?? null,
                'closetime'   => $meta['closetime'] ?? null,
                'vessname'    => $meta['vessname'] ?? null,
                'etdvess'     => $meta['etdvess'] ?? null,
                'etavess'     => $meta['etavess'] ?? null,
                'totqty'      => $meta['totqty'] ?? null,
                'totctn'      => $meta['totctn'] ?? null,
                'totnw'       => $meta['totnw'] ?? null,
                'totgw'       => $meta['totgw'] ?? null,
                'totvol'      => $meta['totvol'] ?? null,
                'ukctn'       => $meta['ukctn'] ?? null,
                'remark'      => $meta['remark'] ?? null,
                'inv'         => $meta['inv'] ?? null,
                'GAC'         => $meta['GAC'] ?? null,
                // 'factory' DIHAPUS dari sini -- sudah digabung ke po_op_list
            ],
            'po_op_list' => $poOpPairs,
            'containers' => $containers,
        ]);
    }
}
