<?php

namespace App\Http\Controllers\FinishgoodStuffing;

use App\Http\Controllers\Controller;
use App\Services\OrderImageService;
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
        return 'mysql';
    }

    // Daftar Data OP index.blade.php.
    public function getList(Request $request)
    {
        $page   = max(1, (int) $request->page);
        $rows   = max(1, (int) $request->rows);
        $offset = ($page - 1) * $rows;
        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        // GANTI -- FIX UTAMA: TIDAK ADA LAGI pembedaan isSuper vs user biasa
        // berdasarkan 'pos' -- SEMUA user melihat data yang SAMA (gabungan
        // mif 1 & 2), sama seperti perbaikan yang sudah diterapkan di
        // PackingController. Koneksi tetap 'mysql' untuk keduanya; 'mif'
        // tetap dipertahankan sebagai filter DATA bisnis (po.mif), bukan
        // pemilih host.
        $rowsMif1 = $this->fetchAll('mysql', 1, $request);
        $rowsMif2 = $this->fetchAll('mysql', 2, $request);
        $combined = $rowsMif1->concat($rowsMif2);

        $aggregated = $this->applyPostAggregationFilters(
            $this->aggregateByPoOp($combined),
            $request
        )->values();

        $this->addCtnBreakdownToRows($aggregated);

        $aggregated = $aggregated->filter(function ($r) {
            if ($r->packing_plan_status === 'complete') {
                return true;
            }
            if ($r->packing_plan_status === 'partial') {
                return ($r->ctn_full_count ?? 0) > 0 || ($r->ctn_partial_count ?? 0) > 0;
            }
            return false;
        })->values();

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

        $this->addOrderImageToRows($data);

        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
            unset($row->_popks);
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

            $packRows = collect();
            foreach (array_chunk($allPopks, 1000) as $popkChunk) {
                $packRows = $packRows->concat(
                    $db->table('pack')->whereIn('popk', $popkChunk)->get()
                );
            }
            $packRowsByPopk = $packRows->groupBy('popk');

            // GANTI -- FIX UTAMA: shipped/inspecting SEKARANG dibaca LANGSUNG
            // dari kolom pack.status/pack.fca pada baris yang SUDAH kita ambil
            // di atas -- TIDAK PERLU lagi query terpisah ke 'ship' (yang sudah
            // tidak ditulisi dan chunk-nya rawan limit placeholder).
            $shippedPackpkSet = $packRows
                ->filter(fn ($p) => in_array((int) $p->status, [6, 7], true))
                ->pluck('packpk')->unique()->flip();

            $inspectingPackpkSet = $packRows
                ->filter(fn ($p) => (int) $p->fca === 1)
                ->pluck('packpk')->unique()->flip();

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

        $packSelect = "
            popk,
            SUM(pcs) as packing_qty,
            SUM(pcsp) as packing_qty_plan,
            SUM(CASE WHEN status >= 4 THEN jmlpcs ELSE 0 END) as packing_ctn
        ";
        if ($isDetailCall) {
            $packSelect .= ",
            MAX(status) as status,
            MAX(CASE WHEN status = 5 AND part = '10' THEN 1 ELSE 0 END) as segel_complete,
            GROUP_CONCAT(
                DISTINCT CASE WHEN status = 5 AND part <> '10' THEN part END
                ORDER BY CAST(part AS UNSIGNED)
                SEPARATOR ', '
            ) as segel_partial_no
            ";
        }
        $packAgg = $db->table('pack')
            ->selectRaw($packSelect)
            ->groupBy('popk');

        $query = $db->table('po')
            ->leftJoinSub($packAgg, 'pk', fn ($join) => $join->on('po.popk', '=', 'pk.popk'))
            ->where('po.qty', '>', 0)
            ->where('po.OP', '<>', '')
            ->where('po.mif', $mif);

        $selectFields = "po.popk, po.ordpk, po.sts, po.gabung, po.shipdate1, po.shipdate2,
            po.customer, po.season, po.POno, po.OP, po.poref, po.mif, po.GAC,
            po.buyer, po.style, po.qty, po.silhouette, po.ctn AS ctn,
            COALESCE(pk.packing_qty_plan,0) AS packing_qty_plan,
            COALESCE(pk.packing_qty,0)       AS packing_qty,
            (COALESCE(pk.packing_qty,0) - COALESCE(pk.packing_qty_plan,0)) AS packing_qty_balance,
            COALESCE(pk.packing_ctn,0) AS packing_ctn,
            (COALESCE(pk.packing_ctn,0) - po.ctn) AS ctn_balance
        ";

        if ($isDetailCall) {
            $bjAgg = $db->table('bj')
                ->selectRaw("
                    popk,
                    SUM(CASE WHEN check2 = 0 THEN pcs ELSE 0 END) as transfer,
                    SUM(CASE WHEN check2 = 1 THEN pcs ELSE 0 END) as checked_qty
                ")
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
                ->leftJoinSub($bjAgg, 'bja', fn ($join) => $join->on('po.popk', '=', 'bja.popk'))
                ->leftJoinSub($lineInfo, 'li', fn ($join) => $join->on('po.popk', '=', 'li.popk'));

            $selectFields .= ",
                COALESCE(bja.transfer,0) AS transfer,
                COALESCE(bja.checked_qty,0) AS checked_qty,
                COALESCE(pk.status, 0)           AS status,
                COALESCE(pk.segel_complete, 0)   AS segel_complete,
                pk.segel_partial_no              AS segel_partial_no,
                (
                    COALESCE(bja.transfer,0)
                    - COALESCE(bja.checked_qty,0)
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
                    'listDetailGlobal' => route('packing.list.detail.global'),
                    'breakdownSummaryGlobal' => route('packing.breakdownSummaryGlobal'),
                    'cardsInfoGlobal'        => route('packing.cardsInfoGlobal'),
                    'headerInfoGlobal'       => route('packing.headerInfoGlobal'),
                    'combosGlobal' => route('packing.combosGlobal'),
                    'updateCtn'              => route('finish-good-stuffing.update-ctn'),
                    'bulkShipAction'         => route('finish-good-stuffing.bulk-ship-action'),
                    'scanNobar'              => route('finish-good-stuffing.scan-nobar'),
                    'partSummaryGlobal'      => route('finish-good-stuffing.partSummaryGlobal'),
                    'eximUpdateShipment'     => route('finish-good-stuffing.exim-update-shipment'),
                    'shipmentPlanDetailGlobal' => route('finish-good-stuffing.shipmentPlanDetailGlobal'),
                    'bundleSegel' => route('packing.bundleSegel'),

                    'headerPartial'    => 'menu.packing.partials.header_info_global',
                    'cardsInfoPartial' => 'menu.packing.partials.cards_info_global',
                    'breakdownPartial' => 'menu.packing.partials.breakdown_summary_global',

                    'modalSegelCtn'          => 'menu.finishgood-stuffing.modal-segel-ctn-global',
                    'modalShipmentCtn'       => 'menu.finishgood-stuffing.modal-shipment-ctn-global',
                    'modalEndSession'        => 'menu.finishgood-stuffing.modal-end-session-global',
                    'modalTerimaCarton'      => 'menu.finishgood-stuffing.modal-terima-carton-global',
                ],
            ],
        ]));
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
            'reject' => 'nullable|in:0,1',
        ]);

        $db = DB::connection('mysql'); // resolveConnection() sudah selalu 'mysql' -- langsung saja

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
            $updated = $db->table('pack')
                ->whereIn('packpk', $ids)
                ->whereNull('fca')
                ->update(['fca' => 1, 'pinjam' => now(), 'segel' => null]);
        } elseif ($action === 'shipment') {
            $result = $this->processShipmentForPackpks($db, $ids);

            if (!$result['success']) {
                return $result['response'];
            }

            return response()->json([
                'icon'  => 'success',
                'title' => "Proses Shipment: {$result['updated']} baris berhasil diproses.",
            ]);
        } elseif ($action === 'lock') {
            // BARU -- FIX UTAMA: branch ini SEBELUMNYA HILANG, action 'lock'
            // salah jatuh ke blok else (dieksekusi sebagai accept_return).
            $label   = 'End Session (Lock)';
            $updated = $db->table('pack')
                ->whereIn('packpk', $ids)
                ->where('status', '<', 7)
                ->update(['status' => 7]);
        } elseif ($action === 'request_return') {
            // BARU -- FIX UTAMA: branch ini SEBELUMNYA JUGA HILANG. Sekaligus
            // sudah disesuaikan dengan redesain Inspect -- shippk dihapus
            // total, cari hasil Inspect terakhir lewat CARTON (snapshot di
            // inspecdt.carton), bukan shippk lagi.
            $cartonsForSelected = $db->table('pack')
                ->whereIn('packpk', $ids)
                ->pluck('carton')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $hasil = $this->getLatestInspecHasilForCartons($db, $cartonsForSelected);

            if ($hasil === null) {
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Carton ini belum punya Dokumen Inspect. Buat Dokumen Inspect dulu sebelum bisa dikembalikan.',
                ], 422);
            }

            $isReject = $hasil === 0;

            $label   = $isReject ? 'Kembalikan ke FinishGood (Reject)' : 'Kembalikan ke FinishGood (Lulus)';
            $updated = $db->table('pack')
                ->whereIn('packpk', $ids)
                ->where('fca', 1)
                ->update(['fca' => 2]);

            if ($isReject) {
                $affectedParts = $db->table('pack')
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
            $label = 'Terima Carton dari Inspect';
            $now   = now();
        
            $updated = $db->table('pack')
                ->whereIn('packpk', $ids)
                ->where('fca', 2)
                ->update(['fca' => null, 'kembali' => $now]);
        
            if ($updated > 0) {
                // sinkronkan ke SEMUA baris inspecdt yang mereferensi
                // packpk ini (matching via packpk, snapshot yang sudah ada).
                $db->table('inspecdt')->whereIn('packpk', $ids)->update(['kembali' => $now]);
            }
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

    // PASTIKAN helper ini ADA di controller (dari redesain Inspect sebelumnya):
    private function getLatestInspecHasilForCartons($db, array $cartons): ?int
    {
        if (empty($cartons)) return null;

        $latest = $db->table('inspecdt')
            ->join('inspec', 'inspec.inspecpk', '=', 'inspecdt.inspecpk')
            ->whereIn('inspecdt.carton', $cartons)
            ->orderByDesc('inspec.inspecpk')
            ->select('inspec.hasil')
            ->first();

        return $latest ? (int) $latest->hasil : null;
    }

    public function cardsSummaryGlobal(Request $request)
    {
        $mifs = [1, 2];
        $perMif = [];

        foreach ($mifs as $mif) {
            $connection = $this->resolveConnection($mif);
            $db = DB::connection($connection);

            // GANTI -- Shipped (status IN 6,7) SEKARANG dari 'pack' langsung.
            $shippedCount = (int) $db->table('pack')
                ->join('po', 'po.popk', '=', 'pack.popk')
                ->where('po.mif', $mif)
                ->whereIn('pack.status', [6, 7])
                ->selectRaw("COUNT(DISTINCT CONCAT(pack.popk, '|', pack.carton)) as cnt")
                ->value('cnt');

            // GANTI -- Inspecting (fca=1) SEKARANG dari 'pack' langsung.
            $inspectingCount = (int) $db->table('pack')
                ->join('po', 'po.popk', '=', 'pack.popk')
                ->where('po.mif', $mif)
                ->where('pack.fca', 1)
                ->selectRaw("COUNT(DISTINCT CONCAT(pack.popk, '|', pack.carton)) as cnt")
                ->value('cnt');

            // Sealed (segel=1) -- SUDAH dari 'pack' sejak awal, tidak berubah.
            $sealedCount = (int) $db->table('pack')
                ->join('po', 'po.popk', '=', 'pack.popk')
                ->where('po.mif', $mif)
                ->where('pack.segel', 1)
                ->selectRaw("COUNT(DISTINCT CONCAT(pack.popk, '|', pack.carton)) as cnt")
                ->value('cnt');

            // GANTI TOTAL -- FIX UTAMA: definisi "Ready" SEBELUMNYA "carton yang
            // punya Actual DAN belum PERNAH punya baris di 'ship' sama sekali"
            // -- definisi itu SUDAH TIDAK BISA DIPAKAI, karena carton baru
            // (dibuat setelah migrasi ke pack-only) MEMANG TIDAK PERNAH akan
            // punya baris 'ship' lagi (bukan indikator "belum diproses").
            //
            // Definisi BARU: carton yang SUDAH ada Actual (SUM(pcs) > 0), BELUM
            // Shipped/Locked (status < 6 atau NULL), DAN TIDAK sedang Inspect
            // (fca bukan 1). Carton yang SEDANG returning (fca=2, sudah balik
            // dari Inspect tapi belum diterima ulang) TETAP dihitung Ready --
            // itu sudah kembali ke alur normal, tinggal menunggu Segel ulang.
            $readyCount = $db->table('pack')
                ->join('po', 'po.popk', '=', 'pack.popk')
                ->where('po.mif', $mif)
                ->where(function ($q) {
                    $q->whereNull('pack.status')->orWhere('pack.status', '<', 6);
                })
                ->where(function ($q) {
                    $q->whereNull('pack.fca')->orWhere('pack.fca', '<>', 1);
                })
                ->groupBy('pack.popk', 'pack.carton')
                ->havingRaw('SUM(pack.pcs) > 0')
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

       return response()->json([
            'total_sealed_carton'  => array_sum(array_column($perMif, 'total_sealed_carton')),
            'total_shipped_carton' => array_sum(array_column($perMif, 'total_shipped_carton')),
            'total_ready_carton'   => array_sum(array_column($perMif, 'total_ready_carton')),
            'total_inspect_carton' => array_sum(array_column($perMif, 'total_inspect_carton')),
            'per_mif'              => $perMif,
        ]);
    }

    private function normalizePartKey($p): string
    {
        if ($p === null || $p === '') return '';
        if (is_numeric($p) && (float) $p === 0.0) return '';
        return (string) $p;
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

        $db = DB::connection('mysql');

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

        // Cek DULU apakah nobar cocok barcode CARTON BESAR (carton_bundle.bundle_nobar).
        $matchedBundle = $db->table('carton_bundle')->where('bundle_nobar', $nobar)->first();
        $matchedRow    = null;

        if ($matchedBundle) {
            $initialPackpks = $db->table('pack')->where('bundlepk', $matchedBundle->bundlepk)->pluck('packpk');

            if ($initialPackpks->isEmpty()) {
                return response()->json([
                    'icon'  => 'warning',
                    'title' => "Carton Besar <b>{$matchedBundle->bundle_carton}</b> belum punya carton anggota.",
                ], 422);
            }
        } else {
            // Scan barcode carton KECIL -- cari di scope PO/OP halaman ini.
            $matchingRows = $db->table('pack')->whereIn('popk', $popks)->where('nobar', $nobar)->get();
 
            if ($matchingRows->isEmpty()) {
                return response()->json([
                    'icon'  => 'warning',
                    'title' => "Nobar \"{$nobar}\" tidak ditemukan pada PO/OP ini.",
                ], 422);
            }
            
            $distinctPhysicalCartons = $matchingRows->groupBy(function ($r) {
                return $r->carton . '|' . $this->normalizePartKey($r->part);
            });
            
            if ($distinctPhysicalCartons->count() > 1) {
                // BARU -- tarik data EXIM SEKALI, dipakai utk cek status container
                // tiap kandidat (exportpk+contpk).
                $eximRowsForDisambiguation = collect();
                try {
                    $eximResponse = \Illuminate\Support\Facades\Http::timeout(10)
                        ->get('http://192.168.0.8/EXIM2/api/getListExport');
                    if ($eximResponse->successful()) {
                        $eximRowsForDisambiguation = collect($eximResponse->json('rows') ?? []);
                    }
                } catch (\Throwable $e) {
                    // Kalau EXIM tidak bisa dihubungi, biarkan kosong -- kandidat
                    // dengan exportpk/contpk yang TIDAK ditemukan di EXIM otomatis
                    // dianggap TIDAK eligible (aman, tidak menebak).
                }
            
                // BARU -- helper: apakah container kandidat ini MASIH TERBUKA di EXIM
                // (start_ship sudah diisi, TAPI end_ship & segel BELUM diisi)?
                $isContainerStillOpen = function ($row) use ($eximRowsForDisambiguation) {
                    if (empty($row->exportpk) || empty($row->contpk)) {
                        return false; // belum punya Session Export/Container -- tidak relevan utk Shipment
                    }
                    $eximRow = $eximRowsForDisambiguation->first(fn ($r) =>
                        (int) ($r['exportpk'] ?? 0) === (int) $row->exportpk
                        && (int) ($r['contpk'] ?? 0) === (int) $row->contpk
                    );
                    if (!$eximRow) return false;
            
                    return !empty($eximRow['start_ship']) && empty($eximRow['end_ship']) && empty($eximRow['segel']);
                };
            
                $eligibleGroups = $distinctPhysicalCartons->filter(function ($rowsInGroup) use ($isContainerStillOpen) {
                    return $rowsInGroup->every(function ($r) use ($isContainerStillOpen) {
                        return (int) $r->segel === 1
                            && !in_array((int) $r->status, [6, 7], true)
                            && (int) $r->fca !== 1
                            && (int) $r->fca !== 2
                            && $isContainerStillOpen($r); // BARU -- ikutkan status container EXIM
                    });
                });
            
                if ($eligibleGroups->count() === 1) {
                    $matchedRow = $eligibleGroups->first()->first();
                } else {
                    $partLabels = $distinctPhysicalCartons->keys()->map(function ($key) {
                        [$carton, $part] = explode('|', $key, 2);
                        return $part !== '' ? "{$carton} (Session {$part})" : $carton;
                    })->implode(', ');
            
                    return response()->json([
                        'icon'  => 'warning',
                        'title' => "Barcode \"{$nobar}\" dipakai lebih dari satu carton fisik, dan tidak ada (atau lebih dari satu) yang statusnya jelas eligible untuk Shipment saat ini ({$partLabels}). "
                            . "Cek status Segel carton & status Container di masing-masing Session sebelum scan ulang.",
                    ], 422);
                }
            } else {
                $matchedRow = $matchingRows->first();
            }

            // Cek closure mixno DULU (tanpa bundlepk expansion penuh) -- kalau
            // ADA sibling mixno (di PO/OP lain) yang ternyata punya bundlepk,
            // TOLAK -- WAJIB scan barcode Carton Besar, bukan carton kecil ini.
            $mixCheckIds = collect([$matchedRow->packpk])
                ->merge(
                    $db->table('pack')
                        ->whereIn('popk', $popks)
                        ->where('carton', $matchedRow->carton)
                        ->where(function ($q) use ($matchedRow) {
                            if ($matchedRow->part === null || $matchedRow->part === '') {
                                $q->whereNull('part')->orWhere('part', '');
                            } else {
                                $q->where('part', $matchedRow->part);
                            }
                        })
                        ->pluck('packpk')
                )
                ->unique()->values();
            


            do {
                $before = $mixCheckIds->count();
                $mixnos = $db->table('pack')->whereIn('packpk', $mixCheckIds)->whereNotNull('mixno')->distinct()->pluck('mixno');
                if ($mixnos->isNotEmpty()) {
                    $viaMixno = $db->table('pack')->whereIn('mixno', $mixnos)->pluck('packpk');
                    $mixCheckIds = $mixCheckIds->merge($viaMixno)->unique()->values();
                }
                $after = $mixCheckIds->count();
            } while ($after > $before);

            $bundlepkFound = $db->table('pack')->whereIn('packpk', $mixCheckIds)->whereNotNull('bundlepk')->value('bundlepk');

            if ($bundlepkFound) {
                $bundleInfo  = $db->table('carton_bundle')->where('bundlepk', $bundlepkFound)->first();
                $bundleName  = $bundleInfo->bundle_carton ?? 'Carton Besar';
                $bundleNobar = $bundleInfo->bundle_nobar ?? null;

                $petunjuk = $bundleNobar
                    ? "Scan barcode Carton Besar (<b>{$bundleNobar}</b>) untuk memproses Shipment seluruh isinya."
                    : "Carton Besar ini belum punya barcode -- lengkapi dulu barcode-nya (Edit Bundle) sebelum bisa diproses Shipment.";

                return response()->json([
                    'icon'  => 'warning',
                    'title' => "Carton <b>{$matchedRow->carton}</b> adalah bagian dari Bundle <b>{$bundleName}</b>, tidak bisa di-scan langsung. {$petunjuk}",
                ], 422);
            }

            $initialPackpks = $mixCheckIds;
        }

        // Delegasikan closure expansion penuh + validasi + eksekusi ke method
        // BERSAMA (SAMA PERSIS dipakai bulkShipAction() action 'shipment').
        $result = $this->processShipmentForPackpks($db, $initialPackpks->all());

        if (!$result['success']) {
            return $result['response'];
        }

        $displayLabel = $matchedBundle
            ? "Carton Besar <b>{$matchedBundle->bundle_carton}</b>"
            : "Carton <b>{$matchedRow->carton}</b>";

        return response()->json([
            'icon'   => 'success',
            'title'  => "{$displayLabel} berhasil diproses Shipment via scan.",
            'carton' => $matchedBundle ? $matchedBundle->bundle_carton : $matchedRow->carton,
        ]);
    }

    /**
     * proses closure expansion (mixno + bundlepk),
     * validasi lengkap, dan eksekusi Shipment (status -> 6 + datescan) untuk
     * sekumpulan packpk awal. Dipakai baik oleh scanNobarGlobal() maupun
     * bulkShipAction() action 'shipment' -- keduanya SECARA FUNGSIONAL
     * IDENTIK, cuma beda cara menemukan packpk awalnya (scan barcode vs
     * pilih manual dari card).
     */
    private function processShipmentForPackpks($db, array $initialPackpks): array
    {
        // ---- 1) Closure expansion: mixno + bundlepk, bolak-balik sampai stabil ----
        $idsCollection = collect($initialPackpks)->map(fn ($v) => (int) $v)->unique()->values();

        do {
            $before = $idsCollection->count();

            $mixnos = $db->table('pack')
                ->whereIn('packpk', $idsCollection)
                ->whereNotNull('mixno')
                ->distinct()
                ->pluck('mixno');
            if ($mixnos->isNotEmpty()) {
                $viaMixno = $db->table('pack')->whereIn('mixno', $mixnos)->pluck('packpk');
                $idsCollection = $idsCollection->merge($viaMixno)->unique()->values();
            }

            $bundlepksLoop = $db->table('pack')
                ->whereIn('packpk', $idsCollection)
                ->whereNotNull('bundlepk')
                ->distinct()
                ->pluck('bundlepk');
            if ($bundlepksLoop->isNotEmpty()) {
                $viaBundle = $db->table('pack')->whereIn('bundlepk', $bundlepksLoop)->pluck('packpk');
                $idsCollection = $idsCollection->merge($viaBundle)->unique()->values();
            }

            $after = $idsCollection->count();
        } while ($after > $before);

        $packpks    = $idsCollection->values()->all();
        $cartonRows = $db->table('pack')->whereIn('packpk', $packpks)->get();

        if ($cartonRows->isEmpty()) {
            return $this->shipmentError('Carton tidak ditemukan.');
        }

        // ---- 2) Label per baris: "Carton Besar {nama}" kalau anggota Bundle ----
        $bundlepksForLabels = $cartonRows->pluck('bundlepk')->filter()->unique()->values();
        $bundleNameMap = [];
        if ($bundlepksForLabels->isNotEmpty()) {
            $bundleNameMap = $db->table('carton_bundle')
                ->whereIn('bundlepk', $bundlepksForLabels)
                ->pluck('bundle_carton', 'bundlepk');
        }
        $labelFor = function ($row) use ($bundleNameMap) {
            if (!empty($row->bundlepk)) {
                $name = $bundleNameMap[$row->bundlepk] ?? null;
                return $name ? "Carton Besar {$name}" : "Carton Besar #{$row->bundlepk}";
            }
            return $row->carton;
        };

        // ---- 3) Akses per-user: created_by kosong = boleh; ada = pos harus sama ----
        $distinctCreators = $cartonRows->pluck('created_by')->filter()->unique()->values();
        $posByUserpk = [];
        if ($distinctCreators->isNotEmpty()) {
            $userRows = DB::connection('mysql_akses')->table('user')
                ->whereIn('userpk', $distinctCreators)
                ->get(['userpk', 'pos']);
            foreach ($userRows as $ur) {
                $posByUserpk[$ur->userpk] = $ur->pos;
            }
        }
        $currentPos = session('pos');
        $notAllowedLabels = $cartonRows
            ->filter(fn ($r) => !empty($r->created_by) && (string) ($posByUserpk[$r->created_by] ?? null) !== (string) $currentPos)
            ->map($labelFor)->unique()->values();
        if ($notAllowedLabels->isNotEmpty()) {
            return $this->shipmentError('Berikut dibuat oleh user/pos lain, tidak bisa diproses Shipment: ' . $notAllowedLabels->implode(', '));
        }

        // ---- 4) Sudah Shipped? ----
        $shippedLabels = $cartonRows->filter(fn ($r) => in_array((int) $r->status, [6, 7], true))->map($labelFor)->unique()->values();
        if ($shippedLabels->isNotEmpty()) {
            return $this->shipmentError('Berikut sudah Shipment sebelumnya: ' . $shippedLabels->implode(', '));
        }

        // ---- 5) Sedang Inspect? ----
        $inspectingLabels = $cartonRows->filter(fn ($r) => (int) $r->fca === 1)->map($labelFor)->unique()->values();
        if ($inspectingLabels->isNotEmpty()) {
            return $this->shipmentError('Berikut sedang proses Inspect, tidak bisa langsung Shipment: ' . $inspectingLabels->implode(', '));
        }

        // ---- 6) Sudah Segel semua? ----
        $belumSegel = $cartonRows->filter(fn ($r) => (int) $r->segel !== 1)->map($labelFor)->unique()->values();
        if ($belumSegel->isNotEmpty()) {
            return $this->shipmentError('Berikut belum Segel, tidak bisa diproses Shipment: ' . $belumSegel->implode(', '));
        }

        // ---- 7) Session (part) & exportpk/contpk -- TIDAK diseragamkan, tiap
        // baris boleh beda nilai (PO A sesi ke-3, PO B sesi ke-2 itu SAH),
        // yang penting masing-masing SUDAH punya nilainya sendiri. ----
        $missingSession = $cartonRows->filter(fn ($r) => $r->part === null || $r->part === '' || (int) $r->part === 0);
        if ($missingSession->isNotEmpty()) {
            return $this->shipmentError('Berikut belum punya Session, tidak bisa diproses Shipment: ' . $missingSession->map($labelFor)->unique()->implode(', '));
        }

        $missingExportContpk = $cartonRows->filter(fn ($r) => empty($r->exportpk) || empty($r->contpk));
        if ($missingExportContpk->isNotEmpty()) {
            return $this->shipmentError('Berikut belum punya Session Export/Container, tidak bisa diproses Shipment: ' . $missingExportContpk->map($labelFor)->unique()->implode(', '));
        }

        // ---- 8) Kesiapan EXIM per kombinasi (exportpk, contpk) yang UNIK ----
        $exportContpkPairs = $cartonRows->map(fn ($r) => $r->exportpk . '|' . $r->contpk)->unique()->values();

        try {
            $eximResponse = \Illuminate\Support\Facades\Http::timeout(10)
                ->get('http://192.168.0.8/EXIM2/api/getListExport');
        } catch (\Throwable $e) {
            return $this->shipmentError('Sistem EXIM tidak dapat dihubungi, gagal memvalidasi status Session.', 500, 'error');
        }

        $eximRows = collect($eximResponse->json('rows') ?? []);

        foreach ($exportContpkPairs as $pair) {
            [$exportpkCheck, $contpkCheck] = explode('|', $pair);

            $eximRow = $eximRows->first(fn ($r) =>
                (int) ($r['exportpk'] ?? 0) === (int) $exportpkCheck
                && (int) ($r['contpk'] ?? 0) === (int) $contpkCheck
            );

            if (!$eximRow) {
                return $this->shipmentError("Data Container (Export {$exportpkCheck} / Cont {$contpkCheck}) tidak ditemukan di EXIM.");
            }

            $contnoLabel = $eximRow['contno'] ?? "Export {$exportpkCheck} / Cont {$contpkCheck}";

            if (empty($eximRow['start_ship'])) {
                return $this->shipmentError("Container {$contnoLabel} belum dimulai (klik \"Mulai\" di Shipment Plan dulu sebelum bisa Shipment).");
            }
            if (!empty($eximRow['end_ship'])) {
                return $this->shipmentError("Container {$contnoLabel} sudah ditutup/Shipment (End Ship sudah diisi), tidak bisa diproses lagi.");
            }
        }

        // ---- 9) Eksekusi ----
        $updated = $db->table('pack')
            ->whereIn('packpk', $packpks)
            ->where('status', '<', 6)
            ->update(['status' => 6, 'datescan' => now()]);

        $bundlepksInvolved = $cartonRows->pluck('bundlepk')->filter()->unique()->values();
        if ($bundlepksInvolved->isNotEmpty()) {
            $db->table('carton_bundle')->whereIn('bundlepk', $bundlepksInvolved)->update(['datescan' => now()]);
        }

        if ($updated < 1) {
            return $this->shipmentError('Tidak ada carton yang memenuhi syarat (mungkin sudah diproses sebelumnya).');
        }

        return [
            'success'    => true,
            'updated'    => $updated,
            'cartonRows' => $cartonRows,
            'labelFor'   => $labelFor,
        ];
    }

    private function shipmentError(string $message, int $status = 422, string $icon = 'warning'): array
    {
        return [
            'success'  => false,
            'response' => response()->json(['icon' => $icon, 'title' => $message], $status),
        ];
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
            ->select('packpk', 'carton', 'popk', 'part', 'exportpk', 'contpk', 'status', 'bundlepk') // BARU -- bundlepk
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

        $parts = [];
        foreach ($packRows->groupBy('exportpk') as $exportpk => $rowsForExport) {
            $exportMeta = $exportMetaByExportpk->get($exportpk);
            if (!$exportMeta) continue; // exportpk ini tidak ditemukan sama sekali di EXIM

            $countingUnits = $rowsForExport->groupBy(function ($r) {
                if ($r->bundlepk) return 'bundle_' . $r->bundlepk;
                return 'single_' . $r->carton . '|' . $this->normalizePartKey($r->part);
            });
            $total = $countingUnits->count();
            
            $partComplete = $rowsForExport->contains(fn ($r) => (string) $r->part === '10');
            
            $shippedUnitsCount = $countingUnits->filter(
                fn ($group) => $group->contains(fn ($r) => in_array((int) $r->status, [6, 7], true))
            )->count();
            
            $lockedUnitsCount = $countingUnits->filter(
                fn ($group) => $group->contains(fn ($r) => (int) $r->status === 7)
            )->count();

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
                'total'   => $total,
                'shipped' => $shippedUnitsCount,
                'locked'  => $lockedUnitsCount,
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
        $po  = $request->po;
        $op  = $request->op;
        $mif = (int) $request->input('mif', session('pos'));

        $db = DB::connection('mysql');

        $qtyColumns  = collect(range(1, 40))->map(fn($i) => "pack.qty{$i}")->implode(', ');
        $sizeColumns = collect(range(1, 40))->map(fn($i) => "po.size{$i}")->implode(', ');

        // GANTI -- pack.packpk sekarang jadi identitas baris (menggantikan
        // ship.shippk), dan filter fca=1 dibaca LANGSUNG dari pack.
        $rows = $db->table('pack')
            ->join('po', 'po.popk', '=', 'pack.popk')
            ->where('po.POno', $po)
            ->where('po.OP', $op)
            ->where('po.mif', $mif)
            ->where('pack.fca', 1)
            ->selectRaw("
                pack.packpk, pack.carton, pack.nobar, pack.material, pack.secsz, pack.part, pack.pcs,
                {$qtyColumns}, {$sizeColumns}
            ")
            ->orderBy('pack.carton')
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
            'aql'             => 'required|numeric|min:0',
            'hasil'           => 'required|in:0,1',
            'lines'           => 'required|array|min:1',
            'lines.*.packpk'  => 'required|integer', // GANTI -- packpk, bukan shippk lagi
            'lines.*.size'    => 'required|string',
            'lines.*.color'   => 'nullable|string',
            'lines.*.secsz'   => 'nullable|string',
            'lines.*.qty'     => 'required|numeric|min:0.01',
            'lines.*.stspass' => 'required|in:0,1',       // BARU -- SEBELUMNYA tidak divalidasi/disimpan sama sekali
            'lines.*.defects' => 'nullable|array',          // BARU
            'lines.*.defects.*' => 'integer',
        ]);

        $db = DB::connection('mysql');

        $totPcs = collect($validated['lines'])->sum('qty');

        // BARU -- lookup carton/nobar/POno/OP/poref/mif utk SEMUA packpk yang
        // terlibat, LANGSUNG dari pack+po (bukan dari input frontend) --
        // supaya snapshot yang tersimpan konsisten & tidak bisa dipalsukan
        // dari sisi klien.
        $packpksInvolved = collect($validated['lines'])->pluck('packpk')->unique()->values();
        $packInfoByPackpk = $db->table('pack')
            ->whereIn('packpk', $packpksInvolved)
            ->get(['packpk', 'popk', 'carton', 'nobar'])
            ->keyBy('packpk');

        $popksInvolved = $packInfoByPackpk->pluck('popk')->unique()->values();
        $poInfoByPopk = $db->table('po')
            ->whereIn('popk', $popksInvolved)
            ->get(['popk', 'POno', 'OP', 'poref', 'mif'])
            ->keyBy('popk');

        try {
            $newInspecpk = DB::connection('mysql')->transaction(function () use ($db, $validated, $totPcs, $packInfoByPackpk, $poInfoByPopk) {
                $newInspecpk = (int) ($db->table('inspec')->lockForUpdate()->max('inspecpk')) + 1;
                $db->table('inspec')->insert([
                    'inspecpk' => $newInspecpk,
                    'tgl'      => now(),
                    'aql'      => $validated['aql'],
                    'totpcs'   => $totPcs,
                    'hasil'    => (int) $validated['hasil'],
                ]);

                // GANTI -- grouping SEKARANG per nomor CARTON (fisik), bukan
                // shippk lagi. Mix Polibag lintas PO otomatis tergabung jadi
                // SATU baris inspecdt (berbagi nomor carton yang sama). Bundle
                // TIDAK otomatis tergabung (tiap carton kecil nomornya beda,
                // tetap terpisah -- sesuai kesepakatan).
                $linesWithCarton = collect($validated['lines'])->map(function ($line) use ($packInfoByPackpk) {
                    $packInfo = $packInfoByPackpk->get($line['packpk']);
                    $line['carton'] = $packInfo->carton ?? ('UNKNOWN-' . $line['packpk']);
                    return $line;
                });

                $byCarton = $linesWithCarton->groupBy('carton');

                $newInspecdtpk = (int) ($db->table('inspecdt')->lockForUpdate()->max('inspecdtpk'));
                $newInspecszpk = (int) ($db->table('inspecsz')->lockForUpdate()->max('inspecszpk'));
                $newInsdefpk   = (int) ($db->table('insdef')->lockForUpdate()->max('insdefpk'));

                foreach ($byCarton as $carton => $lines) {
                    $repLine     = $lines->first();
                    $repPackInfo = $packInfoByPackpk->get($repLine['packpk']);
                    $repPoInfo   = $repPackInfo ? $poInfoByPopk->get($repPackInfo->popk) : null;

                    $newInspecdtpk++;
                    $db->table('inspecdt')->insert([
                        'inspecdtpk' => $newInspecdtpk,
                        'inspecpk'   => $newInspecpk,
                        'packpk'     => $repLine['packpk'],           // soft-reference, representatif
                        'carton'     => $carton,                      // KUNCI UTAMA pencocokan
                        'nobar'      => $repPackInfo->nobar ?? null,
                        'POno'       => $repPoInfo->POno ?? null,
                        'OP'         => $repPoInfo->OP ?? null,
                        'poref'      => $repPoInfo->poref ?? null,
                        'mif'        => $repPoInfo->mif ?? null,
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
                            'stspass'    => (int) $line['stspass'], // BARU -- SEBELUMNYA hilang, tidak pernah disimpan
                        ]);

                        // BARU -- SEBELUMNYA defect yang dipilih di picker frontend
                        // TIDAK PERNAH tersimpan ke 'insdef' sama sekali.
                        foreach (($line['defects'] ?? []) as $defectpk) {
                            $newInsdefpk++;
                            $db->table('insdef')->insert([
                                'insdefpk'   => $newInsdefpk,
                                'inspecszpk' => $newInspecszpk,
                                'defectpk'   => $defectpk,
                            ]);
                        }
                    }
                }

                return $newInspecpk;
            });

            $hasilLabel = (int) $validated['hasil'] === 1 ? 'LULUS' : 'REJECT';
            return response()->json([
                'icon'     => (int) $validated['hasil'] === 1 ? 'success' : 'warning',
                'title'    => "Dokumen Inspect #{$newInspecpk} tersimpan. Hasil: {$hasilLabel}.",
                'inspecpk' => $newInspecpk,
                'hasil'    => (int) $validated['hasil'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal menyimpan dokumen inspect.'], 500);
        }
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

        // GANTI -- FIX UTAMA: cukup query 'mysql' SEKALI, tidak perlu loop
        // ke 'mysql_andon' lagi (sudah tidak bisa dihubungi).
        $matches = DB::connection('mysql')->table('po')
            ->whereIn('POno', $poOpPairs->pluck('POno')->filter()->values())
            ->whereIn('OP', $poOpPairs->pluck('OP')->filter()->values())
            ->get(['POno', 'OP', 'mif'])
            ->keyBy(fn ($r) => $r->POno . '|' . $r->OP);

        $poOpPairs = $poOpPairs->map(function ($p) use ($matches) {
            $found = $matches->get($p['POno'] . '|' . $p['OP']);
            $p['mif'] = $found ? (int) $found->mif : null;
            return $p;
        })->values();

        // GANTI TOTAL -- FIX UTAMA: HAPUS percabangan koneksi per-mif
        // ('mysql_andon' vs 'mysql') -- SEKARANG cukup satu query ke 'mysql'
        // langsung dari SELURUH packpk yang terlibat, tanpa perlu groupBy('mif')
        // lagi. Blok query ke tabel 'ship' yang lama JUGA DIHAPUS -- itu kode
        // mati (variabel $shipStatusByPackpk tidak pernah diinisialisasi,
        // hasilnya juga tidak dipakai di mana pun; status 'shipped' di bawah
        // sudah benar dibaca langsung dari pack.status).
        $allPackpks = $rowsForExport->pluck('packpk')->filter()->unique()->values();

        $cartonByPackpk = collect();
        $poByPopk = collect();
        
        if ($allPackpks->isNotEmpty()) {
            $packRows = DB::connection('mysql')->table('pack')
                ->whereIn('packpk', $allPackpks)
                ->get(['packpk', 'popk', 'carton', 'status', 'mixno', 'bundlepk', 'part']);  // BARU -- mixno, bundlepk
        
            // BARU -- lookup nama carton besar (SAMA pola dengan listDetailGlobal()).
            $bundlepksInvolved = $packRows->pluck('bundlepk')->filter()->unique()->values();
            $bundleNameMap = [];
            if ($bundlepksInvolved->isNotEmpty()) {
                $bundleNameMap = DB::connection('mysql')->table('carton_bundle')
                    ->whereIn('bundlepk', $bundlepksInvolved)
                    ->pluck('bundle_carton', 'bundlepk');
            }
            foreach ($packRows as $pr) {
                $pr->bundle_carton = $bundleNameMap[$pr->bundlepk] ?? null;
            }
        
            $cartonByPackpk = $packRows->keyBy('packpk');
        
            $popks = $packRows->pluck('popk')->filter()->unique()->values();
            if ($popks->isNotEmpty()) {
                $poByPopk = DB::connection('mysql')->table('po')
                    ->whereIn('popk', $popks)
                    ->get(['popk', 'POno', 'OP'])
                    ->keyBy('popk');
            }
        }

       $containers = $rowsForExport->groupBy('contpk')->map(function ($rows) use ($cartonByPackpk, $poByPopk) {
            $f = $rows->first();
        
            $rawCartons = $rows->map(function ($r) use ($cartonByPackpk, $poByPopk) {
            $packRow = $cartonByPackpk->get($r['packpk'] ?? null);
            $poRow   = $packRow ? $poByPopk->get($packRow->popk) : null;
            $shipped = $packRow && in_array((int) $packRow->status, [6, 7], true);
        
            return [
                'carton'        => $packRow->carton ?? $r['carton'] ?? '-',
                'part'          => $packRow->part ?? null, // BARU
                'POno'          => $poRow->POno ?? null,
                'OP'            => $poRow->OP ?? null,
                'shipped'       => $shipped,
                'bundlepk'      => $packRow->bundlepk ?? null,
                'bundle_carton' => $packRow->bundle_carton ?? null,
            ];
        });
        
        // GANTI -- groupBy carton+part, BUKAN carton saja.
        $cartons = $rawCartons->groupBy(fn ($c) => $c['carton'] . '|' . $this->normalizePartKey($c['part']))
            ->map(function ($group) {
                $first = $group->first();
        
                $poOpList = $group
                    ->map(fn ($c) => ['POno' => $c['POno'], 'OP' => $c['OP']])
                    ->filter(fn ($p) => $p['POno'] || $p['OP'])
                    ->unique(fn ($p) => $p['POno'] . '|' . $p['OP'])
                    ->values();
        
                return [
                    'carton'        => $first['carton'],
                    'part'          => $first['part'], // BARU
                    'POno'          => $first['POno'],
                    'OP'            => $first['OP'],
                    'po_op_list'    => $poOpList,
                    'is_mix'        => $poOpList->count() > 1,
                    'shipped'       => $group->contains('shipped', true),
                    'bundlepk'      => $first['bundlepk'],
                    'bundle_carton' => $first['bundle_carton'],
                ];
            })
            ->sortBy(fn ($c) => $c['carton'], SORT_NATURAL)
            ->values();
        
        // GANTI -- countingUnits ikut +part.
        $countingUnits = $cartons->groupBy(function ($c) {
            if ($c['bundlepk']) return 'bundle_' . $c['bundlepk'];
            return 'single_' . $c['carton'] . '|' . $this->normalizePartKey($c['part']);
        });
            
            return [
                'contpk'     => $f['contpk'] ?? null,
                'contno'     => $f['contno'] ?? null,
                'type'       => $f['type'] ?? null,
                'typenm'     => $f['typenm'] ?? null,
                'qty_ctn'    => $countingUnits->count(), 
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
            ],
            'po_op_list' => $poOpPairs,
            'containers' => $containers,
        ]);
    }
}
