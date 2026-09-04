<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function print(Request $request, $popk)
    {
        $gab = (string) $request->query('gab', '0');

        // 1. Timestamp terakhir update
        $dtp = DB::table('pack')
            ->where('popk', $popk)
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu', 'desc')
            ->first();

        // 2. Data PO utama
        $dt = DB::table('po')->where('popk', $popk)->first();
        if (!$dt) abort(404, 'Data PO tidak ditemukan.');

        $customer = $dt->customer;

        // 3. Data PO Grouping (Order Qty per size)
        $dt2 = DB::table('po')
            ->selectRaw(
                '
                ket, wh, sap1, sap2, gabung, shipdate1, shipdate2, ctn, customer, season, POno, OP,
                buyer, style, material, silhouette,
                size1,size2,size3,size4,size5,size6,size7,size8,size9,size10,
                size11,size12,size13,size14,size15,size16,size17,size18,size19,size20,
                size21,size22,size23,size24,size25,size26,size27,size28,size29,size30,
                size31,size32,size33,size34,size35,size36,size37,size38,size39,size40,
                sum(qty) as qty,' . $this->sumFields('qty', 40)
            )
            ->where('popk', $dt->popk)
            ->groupBy('popk')
            ->first();

        // 4. Hitung kolom size aktif
        $cjml = 0;
        for ($i = 1; $i <= 40; $i++) {
            if (!empty($dt->{"size{$i}"})) $cjml++;
        }
        $cheader = $cjml + 1;

        // 5. List Customer (gabung 4/5/6/8)
        $customersList = [];
        if (in_array($dt->gabung, [4, 5, 6, 8])) {
            $customersList = DB::table('po')
                ->where('gabung', $dt->gabung)->where('POno', $dt->POno)
                ->where('OP', $dt->OP)->where('material', $dt->material)
                ->orderBy('customer')->get();
        }

        // 6. List Material (gabung 1/2/9/10)
        $materialsList = [];
        if (in_array($dt->gabung, [1, 2, 9, 10])) {
            $materialsList = DB::table('po')
                ->where('gabung', $dt->gabung)->where('POno', $dt->POno)
                ->where('OP', $dt->OP)->where('customer', $customer)
                ->orderBy('material')->get();
        }

        // 7. Total CTN
        $totalCtn = $this->calcTotalCtn($dt, $dt2, $customer);

        // 8. MEAS CTN
        $measList = DB::table('pack')
            ->select('meas')
            ->where('meas', '<>', '')->where('POno', $dt->POno)
            ->where('OP', $dt->OP)->where('material', $dt->material)
            ->where('customer', $customer)
            ->groupBy('meas')->orderBy('meas')->get();

        // 9. Ship Qty per size (untuk pdf0 — single material)
        $dt3 = DB::table('pack')
            ->selectRaw($this->sumQtypFields() . ', sum(pcsp) as pcs')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('customer', $customer)
            ->first();

        // 10. N.W list
        $nwList = DB::table('pack')
            ->select('nw', 'urut')
            ->where('nw', '>', 0)->where('POno', $dt->POno)
            ->where('OP', $dt->OP)->where('material', $dt->material)
            ->where('customer', $customer)
            ->groupBy('nw', 'urut')->orderBy('urut')->get();

        // 11. G.W list
        $gwList = DB::table('pack')
            ->select('gw', 'urut')
            ->where('gw', '>', 0)->where('POno', $dt->POno)
            ->where('OP', $dt->OP)->where('material', $dt->material)
            ->where('customer', $customer)
            ->groupBy('gw', 'urut')->orderBy('urut')->get();

        // 12. Detail Packing per size group (untuk pdf0)
        $detailPacking = $this->buildDetailPacking($dt, $customer);

        // 13. Data spesifik per gab — builder dipanggil sesuai nilai $gab
        $gabData = $this->buildGabData($gab, $dt, $dt2, $dt3, $popk, $customer);

        return view('menu.laporan.pdf', compact(
            'dtp',
            'dt',
            'dt2',
            'dt3',
            'gab',
            'cheader',
            'cjml',
            'customersList',
            'materialsList',
            'totalCtn',
            'measList',
            'nwList',
            'gwList',
            'detailPacking',
            'gabData'
        ));
    }

    private function buildGabData(string $gab, $dt, $dt2, $dt3, $popk, string $customer): array
    {
        switch ($gab) {
            case '1':
                return $this->buildGab1Data($dt, $customer);
            case '2':
                return $this->buildGab2Data($dt, $customer);
            case '3':
                return $this->buildGab3Data($dt, $popk, $customer);
            case '4':
                return $this->buildGab4Data($dt, $customer);
            case '5':
                return $this->buildGab5Data($dt, $customer);
            case '6':
                return $this->buildGab6Data($dt, $customer);
            case '7':
                return $this->buildGab7Data($dt, $dt2, $dt3, $popk, $customer);
            case '8':
                return $this->buildGab8Data($dt, $customer);
            case '9':
                return $this->buildGab9Data($dt, $customer);
            case '10':
                return $this->buildGab10Data($dt, $customer);
            default:
                return [];
        }
    }

    private function buildGab1Data($dt, string $customer): array
    {
        // Breakdown per material
        $materials = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('customer', $customer)->where('gabung', '1')
            ->selectRaw('material, qty, ' . $this->sumFields('qty', 40))
            ->groupBy('material')->orderBy('qty', 'desc')
            ->get();

        $materialRows = [];
        foreach ($materials as $mat) {
            $shipAgg = DB::table('pack')
                ->where('material', $mat->material)->where('POno', $dt->POno)
                ->where('OP', $dt->OP)->where('customer', $customer)
                ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())
                ->first();

            [$order, $ship, $diff, $pct] = $this->calcSizeArrays($mat, $shipAgg, 'qty');
            $pcsTotal = $shipAgg->pcs ?? 0;
            $pcsDiff  = $pcsTotal - ($mat->qty ?? 0);
            $ppcs     = !empty($mat->qty) ? round($pcsDiff / $mat->qty * 100, 2) : 0;

            $materialRows[] = [
                'material'    => $mat->material,
                'order'       => $order,
                'ship'        => $ship,
                'diff'        => $diff,
                'pct'         => $pct,
                'order_total' => $mat->qty,
                'ship_total'  => $pcsTotal,
                'diff_total'  => $pcsDiff,
                'pct_total'   => $ppcs,
            ];
        }

        // Grand Total
        $totalOrder = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('customer', $customer)->where('gabung', '1')
            ->selectRaw('sum(qty) as qty, ' . $this->sumFields('qty', 40))
            ->first();
        $totalShip = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('customer', $customer)
            ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())
            ->first();
        $grandTotal = $this->buildGrandTotalBlock($totalOrder, $totalShip);

        // Detail packing per material (Size/Ratio table)
        $detailMaterials = DB::table('pack')
            ->leftJoin('po', 'po.popk', '=', 'pack.popk')
            ->where('pack.POno', $dt->POno)->where('pack.OP', $dt->OP)
            ->where('pack.customer', $customer)->where('po.gabung', '1')
            ->selectRaw('pack.material, ' . $this->sumPrefixQtyp('pack') . ', sum(pack.pcsp) as pcsp')
            ->groupBy('pack.material')->orderBy('pcsp', 'desc')
            ->get();

        $detailMaterialRows = [];
        $tpcsp = 0;
        foreach ($detailMaterials as $row) {
            $vals = [];
            for ($i = 1; $i <= 40; $i++) {
                $v = $row->{"qtyp{$i}"} ?? 0;
                $vals[$i] = $v == 0 ? '' : $v;
            }
            $tpcsp += $row->pcsp;
            $detailMaterialRows[] = [
                'material' => $row->material,
                'qty'      => $vals,
                'pcsp'     => $row->pcsp,
            ];
        }

        // Carton block bawah (1 popk representatif, ikuti logika lama)
        $ctnHead = DB::table('pack')
            ->leftJoin('po', 'po.popk', '=', 'pack.popk')
            ->where('pack.POno', $dt->POno)->where('pack.OP', $dt->OP)
            ->where('pack.customer', $customer)->where('po.gabung', '1')
            ->selectRaw('count(*) as ctn, pack.POno, pack.OP, pack.material, pack.customer')
            ->groupBy('pack.popk')->orderBy('pack.pcsp', 'desc')
            ->limit(1)->first();

        $cartonRows = [];
        if ($ctnHead) {
            $cartonList = DB::table('pack')
                ->leftJoin('po', 'po.popk', '=', 'pack.popk')
                ->where('pack.POno', $ctnHead->POno)->where('pack.OP', $ctnHead->OP)
                ->where('pack.material', $ctnHead->material)->where('pack.customer', $ctnHead->customer)
                ->where('po.gabung', '1')
                ->select('pack.carton', 'pack.pcs', 'pack.keterangan', 'pack.status')
                ->orderBy('pack.urut')->orderBy('pack.packpk')->get();

            // Batch: total pcs per carton dalam satu query, bukan query per baris
            $cartonNames = $cartonList->pluck('carton')->unique()->values()->all();
            $pcsByCarton = DB::table('pack')
                ->leftJoin('po', 'po.popk', '=', 'pack.popk')
                ->whereIn('pack.carton', $cartonNames)
                ->where('pack.POno', $ctnHead->POno)->where('pack.OP', $ctnHead->OP)
                ->where('pack.customer', $ctnHead->customer)->where('po.gabung', '1')
                ->groupBy('pack.carton')
                ->selectRaw('pack.carton, sum(pack.pcs) as pcs')
                ->pluck('pcs', 'carton');

            foreach ($cartonList as $c) {
                $sumPcs = $pcsByCarton[$c->carton] ?? 0;
                $cartonRows[] = $this->buildCartonInput($c, $tpcsp, $sumPcs);
            }
        }

        return [
            'materialRows'       => $materialRows,
            'grandTotal'         => $grandTotal,
            'detailMaterialRows' => $detailMaterialRows,
            'tpcsp'              => $tpcsp,
            'ctn'                => $ctnHead->ctn ?? 0,
            'cartonRows'         => $cartonRows,
        ];
    }

    private function buildGab2Data($dt, string $customer): array
    {
        // Daftar material gabung=2
        $materials = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('customer', $customer)->where('gabung', '2')
            ->selectRaw('material, qty, ' . $this->sumFields('qty', 40))
            ->orderBy('material')
            ->get();

        $materialRows = [];
        $nwByMaterial = [];
        $gwByMaterial = [];

        foreach ($materials as $mat) {
            $shipAgg = DB::table('pack')
                ->where('material', $mat->material)->where('POno', $dt->POno)
                ->where('OP', $dt->OP)->where('customer', $customer)
                ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())
                ->groupBy('material')->orderBy('pcs', 'desc')
                ->first();

            [$order, $ship, $diff, $pct] = $this->calcSizeArrays($mat, $shipAgg);
            $pcsTotal = $shipAgg->pcs ?? 0;
            $pcsDiff  = $pcsTotal - ($mat->qty ?? 0);
            $ppcs     = !empty($mat->qty) ? round($pcsDiff / $mat->qty * 100, 2) : 0;

            $materialRows[] = [
                'material'    => $mat->material,
                'order'       => $order,
                'ship'        => $ship,
                'diff'        => $diff,
                'pct'         => $pct,
                'order_total' => $mat->qty,
                'ship_total'  => $pcsTotal,
                'diff_total'  => $pcsDiff,
                'pct_total'   => $ppcs,
            ];

            // N.W / G.W per material — satu query per material, bukan 80 query (40 size x 2)
            [$nwRow, $gwRow] = $this->buildMeasureRowsForMaterial($dt, $customer, $mat->material);
            $nwByMaterial[] = ['material' => $mat->material, 'nw' => $nwRow];
            $gwByMaterial[] = ['material' => $mat->material, 'gw' => $gwRow];
        }

        // Grand Total
        $totalOrder = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('customer', $customer)->where('gabung', '2')
            ->selectRaw('sum(qty) as qty, ' . $this->sumFields('qty', 40))
            ->first();
        $totalShip = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('customer', $customer)
            ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())
            ->first();
        $grandTotal = $this->buildGrandTotalBlock($totalOrder, $totalShip);

        // Detail packing per material → per size group
        $detailByMaterial = [];
        foreach ($materials as $mat) {
            $groups = DB::table('pack')
                ->selectRaw('count(*) as ctn, pcsp, urut, POno, customer, OP, material,
                    qtyp1,qtyp2,qtyp3,qtyp4,qtyp5,qtyp6,qtyp7,qtyp8,qtyp9,qtyp10,
                    qtyp11,qtyp12,qtyp13,qtyp14,qtyp15,qtyp16,qtyp17,qtyp18,qtyp19,qtyp20,
                    qtyp21,qtyp22,qtyp23,qtyp24,qtyp25,qtyp26,qtyp27,qtyp28,qtyp29,qtyp30,
                    qtyp31,qtyp32,qtyp33,qtyp34,qtyp35,qtyp36,qtyp37,qtyp38,qtyp39,qtyp40,carton')
                ->where('POno', $dt->POno)->where('OP', $dt->OP)
                ->where('customer', $customer)->where('material', $mat->material)
                ->groupBy('pcsp', 'urut')->orderBy('urut')->orderBy('packpk')
                ->get();

            // Batch semua carton untuk material ini dalam satu query
            $allCartons = DB::table('pack')
                ->select('carton', 'pcs', 'keterangan', 'status', 'pcsp', 'urut')
                ->where('POno', $dt->POno)->where('OP', $dt->OP)
                ->where('material', $mat->material)->where('customer', $customer)
                ->orderBy('urut')->orderBy('packpk')->get()
                ->groupBy(fn($c) => $c->urut . '|' . $c->pcsp);

            $sizeGroups = [];
            foreach ($groups as $g) {
                $size = null;
                for ($i = 1; $i <= 40; $i++) {
                    if (($g->{"qtyp{$i}"} ?? 0) > 0) $size = $dt->{"size{$i}"} ?? null;
                }
                $cartons = $allCartons->get($g->urut . '|' . $g->pcsp, collect());

                $cartonRows = [];
                foreach ($cartons as $c) {
                    $cartonRows[] = $this->buildCartonInput($c, $g->pcsp, $c->pcs);
                }
                $sizeGroups[] = ['size' => $size, 'ctn' => $g->ctn, 'pcsp' => $g->pcsp, 'cartonRows' => $cartonRows];
            }
            $detailByMaterial[] = ['material' => $mat->material, 'groups' => $sizeGroups];
        }

        return [
            'materialRows'    => $materialRows,
            'grandTotal'      => $grandTotal,
            'nwByMaterial'    => $nwByMaterial,
            'gwByMaterial'    => $gwByMaterial,
            'detailByMaterial' => $detailByMaterial,
        ];
    }

    private function buildGab3Data($dt, $popk, string $customer): array
    {
        // Ratio table: satu baris per popk (pcsp terbesar), label sesuai secsz/inseam/material
        $ratioRows = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('customer', $customer)->where('material', $dt->material)
            ->where('popk', $popk)
            ->selectRaw('*, ' . $this->sumPrefixQtyp('pack'))
            ->groupBy('pcsp')->orderBy('pcsp', 'desc')
            ->limit(1)->get();

        $customerShort = substr($customer, 0, 6);

        $ratioData = [];
        $tpcsp     = 0;
        $k         = 0;
        foreach ($ratioRows as $row) {
            $k++;
            $label = $k > 1 ? "({$row->carton})" : '';
            if (!empty($row->secsz)) {
                $rowLabel = "{$row->secsz} {$label}";
            } elseif ($customerShort === 'INSEAM') {
                $rowLabel = "{$row->customer} {$label}";
            } else {
                $rowLabel = "{$row->material} {$label}";
            }

            $vals = [];
            for ($i = 1; $i <= 40; $i++) {
                $v = $row->{"qtyp{$i}"} ?? 0;
                $vals[$i] = $v == 0 ? '' : $v;
            }
            $tpcsp += $row->pcsp ?? 0;
            $ratioData[] = ['label' => $rowLabel, 'qty' => $vals, 'pcsp' => $row->pcsp ?? 0];
        }

        // Detail carton: per pcsp group, filter by popk
        $detailGroups = DB::table('pack')
            ->selectRaw('count(*) as ctn, pcsp, POno, OP, material, customer')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('customer', $customer)->where('material', $dt->material)
            ->where('popk', $popk)
            ->groupBy('pcsp')->orderBy('pcsp', 'desc')
            ->get();

        $allCartons = DB::table('pack')
            ->select('carton', 'pcs', 'keterangan', 'status', 'pcsp')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('customer', $customer)->where('material', $dt->material)
            ->where('popk', $popk)
            ->orderBy('urut')->orderBy('packpk')->get()
            ->groupBy(fn($c) => (string) ($c->pcsp ?? 0));

        $pcsByCarton = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('customer', $customer)
            ->groupBy('carton')
            ->selectRaw('carton, sum(pcs) as pcs')
            ->pluck('pcs', 'carton');

        $detailPacking = [];
        foreach ($detailGroups as $g) {
            // Key akses HARUS sama persis dengan key yang dipakai saat
            // grouping di atas (string, null -> '0').
            $cartons = $allCartons->get((string) ($g->pcsp ?? 0), collect());

            $cartonRows = [];
            foreach ($cartons as $c) {
                // carton bisa saja null -> nulkan jadi 0 (bukan crash).
                $sumPcs = $c->carton !== null ? ($pcsByCarton[$c->carton] ?? 0) : 0;
                $cartonRows[] = $this->buildCartonInput($c, $tpcsp, $sumPcs);
            }

            $detailPacking[] = ['ctn' => $g->ctn, 'pcsp' => $g->pcsp ?? 0, 'cartonRows' => $cartonRows];
        }

        return [
            'ratioData'     => $ratioData,
            'tpcsp'         => $tpcsp,
            'detailPacking' => $detailPacking,
        ];
    }

    private function buildGab4Data($dt, string $customer): array
    {
        $hasSecsz = !empty($dt->secsz);

        $poList = $hasSecsz
            ? DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('customer', $customer)
            ->where('gabung', '4')
            ->selectRaw('*, ' . $this->sumFields('qty', 40))
            ->orderBy('secsz')->get()
            : DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('gabung', '4')
            ->selectRaw('*, ' . $this->sumFields('qty', 40))
            ->orderBy('customer')->get();

        $entityRows = [];
        foreach ($poList as $po) {
            $entityLabel = $hasSecsz ? $po->secsz : $po->customer;

            $shipQuery = DB::table('pack')
                ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material);
            $shipAgg = $hasSecsz
                ? $shipQuery->where('secsz', $po->secsz)->where('customer', $customer)
                ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())->groupBy('secsz')->first()
                : $shipQuery->where('customer', $po->customer)
                ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())->groupBy('customer')->first();

            [$order, $ship, $diff, $pct] = $this->calcSizeArrays($po, $shipAgg);
            $pcsTotal = $shipAgg->pcs ?? 0;
            $pcsDiff  = $pcsTotal - ($po->qty ?? 0);
            $ppcs     = !empty($po->qty) ? round($pcsDiff / $po->qty * 100, 2) : 0;

            $entityRows[] = [
                'label'       => $entityLabel,
                'order'       => $order,
                'ship'        => $ship,
                'diff'        => $diff,
                'pct'         => $pct,
                'order_total' => $po->qty,
                'ship_total'  => $pcsTotal,
                'diff_total'  => $pcsDiff,
                'pct_total'   => $ppcs,
            ];
        }

        // Grand Total
        $orderQuery = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material)->where('gabung', '4');
        $shipQuery = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material);

        $totalOrder = $hasSecsz
            ? $orderQuery->where('customer', $customer)->selectRaw('sum(qty) as qty, ' . $this->sumFields('qty', 40))->first()
            : $orderQuery->selectRaw('sum(qty) as qty, ' . $this->sumFields('qty', 40))->first();
        $totalShip = $hasSecsz
            ? $shipQuery->where('customer', $customer)->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())->first()
            : $shipQuery->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())->first();

        $grandTotal = $this->buildGrandTotalBlock($totalOrder, $totalShip);

        // Ratio table (Detail Packing — size/ratio)
        $packGroupsQuery = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material);
        $packGroups = $hasSecsz
            ? $packGroupsQuery->where('customer', $customer)->groupBy('secsz')->orderBy('secsz')
            ->selectRaw('secsz, customer, POno, OP, material')->get()
            : $packGroupsQuery->groupBy('customer')->orderBy('customer')
            ->selectRaw('secsz, customer, POno, OP, material')->get();

        $ratioRows = [];
        $tpcsp     = 0;
        foreach ($packGroups as $pg) {
            $entityLabel = $hasSecsz ? $pg->secsz : $pg->customer;

            $packRowQuery = DB::table('pack')
                ->where('POno', $pg->POno)->where('OP', $pg->OP)
                ->where('material', $pg->material)->where('customer', $pg->customer);
            $packRow = $hasSecsz
                ? $packRowQuery->where('secsz', $pg->secsz)->orderBy('pcsp', 'desc')->first()
                : $packRowQuery->orderBy('pcsp', 'desc')->first();

            if (!$packRow) continue;

            $vals = [];
            for ($i = 1; $i <= 40; $i++) {
                $v = $packRow->{"qtyp{$i}"} ?? 0;
                $vals[$i] = $v == 0 ? '' : $v;
            }
            $tpcsp += $packRow->pcsp;
            $ratioRows[] = ['label' => $entityLabel, 'qty' => $vals, 'pcsp' => $packRow->pcsp];
        }

        // Detail carton per pcsp group (kondisional secsz)
        $ctnGroupsQuery = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('customer', $customer);
        $ctnGroups = $hasSecsz
            ? $ctnGroupsQuery->where('secsz', $dt->secsz)
            ->selectRaw('pcsp, count(*) as ctn, POno, OP, material, customer, secsz, carton')
            ->groupBy('pcsp')->orderBy('pcsp', 'desc')->get()
            : $ctnGroupsQuery
            ->selectRaw('pcsp, count(*) as ctn, POno, OP, material, customer, secsz, carton')
            ->groupBy('pcsp')->orderBy('pcsp', 'desc')->get();

        $detailPacking = [];
        foreach ($ctnGroups as $g) {
            $pcspTotalQuery = DB::table('pack')
                ->where('POno', $g->POno)->where('OP', $g->OP)
                ->where('material', $g->material)->where('carton', $g->carton);
            $pcspTotal = $hasSecsz
                ? ($pcspTotalQuery->where('customer', $g->customer)->selectRaw('sum(pcsp) as pcsp')->value('pcsp') ?? 0)
                : ($pcspTotalQuery->selectRaw('sum(pcsp) as pcsp')->value('pcsp') ?? 0);

            $cartonsQuery = DB::table('pack')
                ->select('carton', 'keterangan', 'status')
                ->where('pcsp', $g->pcsp)->where('POno', $g->POno)
                ->where('OP', $g->OP)->where('material', $g->material)->where('customer', $g->customer);
            $cartons = $hasSecsz
                ? $cartonsQuery->where('secsz', $g->secsz)->orderBy('urut')->orderBy('packpk')->get()
                : $cartonsQuery->orderBy('urut')->orderBy('packpk')->get();

            // Batch total pcs per carton dalam satu query
            $cartonNames = $cartons->pluck('carton')->unique()->values()->all();
            $sumPcsQuery = DB::table('pack')
                ->whereIn('carton', $cartonNames)
                ->where('POno', $g->POno)->where('OP', $g->OP)->where('material', $g->material);
            $pcsByCarton = $hasSecsz
                ? $sumPcsQuery->where('customer', $g->customer)->groupBy('carton')
                ->selectRaw('carton, sum(pcs) as pcs')->pluck('pcs', 'carton')
                : $sumPcsQuery->groupBy('carton')->selectRaw('carton, sum(pcs) as pcs')->pluck('pcs', 'carton');

            $cartonRows = [];
            foreach ($cartons as $c) {
                $sumPcs = $pcsByCarton[$c->carton] ?? 0;
                $cartonRows[] = $this->buildCartonInput($c, $pcspTotal, $sumPcs);
            }

            $detailPacking[] = ['ctn' => $g->ctn, 'pcsp' => $pcspTotal, 'cartonRows' => $cartonRows];
        }

        return [
            'hasSecsz'      => $hasSecsz,
            'entityRows'    => $entityRows,
            'grandTotal'    => $grandTotal,
            'ratioRows'     => $ratioRows,
            'tpcsp'         => $tpcsp,
            'detailPacking' => $detailPacking,
        ];
    }

    private function buildGab5Data($dt, string $customer): array
    {
        $hasSecsz = !empty($dt->secsz);

        $poList = $hasSecsz
            ? DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('customer', $customer)
            ->where('gabung', '5')
            ->selectRaw('*, ' . $this->sumFields('qty', 40))
            ->orderBy('secsz')->get()
            : DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('gabung', '5')
            ->selectRaw('*, ' . $this->sumFields('qty', 40))
            ->orderBy('customer')->get();

        $entityRows = [];
        foreach ($poList as $po) {
            $entityLabel = $hasSecsz ? $po->secsz : $po->customer;

            $shipQuery = DB::table('pack')
                ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material);
            $shipAgg = $hasSecsz
                ? $shipQuery->where('secsz', $po->secsz)->where('customer', $customer)
                ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())->groupBy('secsz')->first()
                : $shipQuery->where('customer', $po->customer)
                ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())->groupBy('customer')->first();

            [$order, $ship, $diff, $pct] = $this->calcSizeArrays($po, $shipAgg);
            $pcsTotal = $shipAgg->pcs ?? 0;
            $pcsDiff  = $pcsTotal - ($po->qty ?? 0);
            $ppcs     = !empty($po->qty) ? round($pcsDiff / $po->qty * 100, 2) : 0;

            // N.W / G.W per entity — satu query, bukan 80 query
            $entityCustomer = $hasSecsz ? $customer : $po->customer;
            $entitySecsz    = $hasSecsz ? $po->secsz : null;
            [$nwRow, $gwRow] = $this->buildMeasureRowsForMaterial(
                $dt,
                $entityCustomer,
                $dt->material,
                $entitySecsz
            );

            $entityRows[] = [
                'label' => $entityLabel,
                'order' => $order,
                'ship' => $ship,
                'diff' => $diff,
                'pct' => $pct,
                'order_total' => $po->qty,
                'ship_total' => $pcsTotal,
                'diff_total' => $pcsDiff,
                'pct_total' => $ppcs,
                'nw' => $nwRow,
                'gw' => $gwRow,
            ];
        }

        // Grand Total
        $orderQuery = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material)->where('gabung', '5');
        $shipQuery = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material);

        $totalOrder = $hasSecsz
            ? $orderQuery->where('customer', $customer)->selectRaw('sum(qty) as qty, ' . $this->sumFields('qty', 40))->first()
            : $orderQuery->selectRaw('sum(qty) as qty, ' . $this->sumFields('qty', 40))->first();
        $totalShip = $hasSecsz
            ? $shipQuery->where('customer', $customer)->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())->first()
            : $shipQuery->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())->first();

        $grandTotal = $this->buildGrandTotalBlock($totalOrder, $totalShip);

        // Ratio table per entity
        $packGroupsQuery = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material);
        $packGroups = $hasSecsz
            ? $packGroupsQuery->where('customer', $customer)->groupBy('secsz')->orderBy('secsz')
            ->selectRaw('secsz, customer, POno, OP, material')->get()
            : $packGroupsQuery->groupBy('customer')->orderBy('customer')
            ->selectRaw('secsz, customer, POno, OP, material')->get();

        $ratioRows = [];
        $tpcsp     = 0;
        foreach ($packGroups as $pg) {
            $entityLabel = $hasSecsz ? $pg->secsz : $pg->customer;

            $packRowQuery = DB::table('pack')
                ->where('POno', $pg->POno)->where('OP', $pg->OP)
                ->where('material', $pg->material)->where('customer', $pg->customer);
            $packRow = $hasSecsz
                ? $packRowQuery->where('secsz', $pg->secsz)->orderBy('pcsp', 'desc')->first()
                : $packRowQuery->orderBy('pcsp', 'desc')->first();

            if (!$packRow) continue;

            $vals = [];
            for ($i = 1; $i <= 40; $i++) {
                $v = $packRow->{"qtyp{$i}"} ?? 0;
                $vals[$i] = $v == 0 ? '' : $v;
            }
            $tpcsp += $packRow->pcsp;
            $ratioRows[] = ['label' => $entityLabel, 'qty' => $vals, 'pcsp' => $packRow->pcsp];
        }

        // Detail packing: urut < 21 (normal per entity), urut = 21 (mixed carton)
        $normalDetails = [];
        foreach ($packGroups as $pg) {
            $entityLabel = $hasSecsz ? $pg->secsz : $pg->customer;

            $groupsQuery = DB::table('pack')
                ->where('urut', '<', 21)->where('POno', $pg->POno)
                ->where('OP', $pg->OP)->where('customer', $pg->customer)->where('material', $pg->material)
                ->selectRaw('count(*) as ctn, pcsp, urut, POno, customer, secsz, OP, material,
                    qtyp1,qtyp2,qtyp3,qtyp4,qtyp5,qtyp6,qtyp7,qtyp8,qtyp9,qtyp10,
                    qtyp11,qtyp12,qtyp13,qtyp14,qtyp15,qtyp16,qtyp17,qtyp18,qtyp19,qtyp20,
                    qtyp21,qtyp22,qtyp23,qtyp24,qtyp25,qtyp26,qtyp27,qtyp28,qtyp29,qtyp30,
                    qtyp31,qtyp32,qtyp33,qtyp34,qtyp35,qtyp36,qtyp37,qtyp38,qtyp39,qtyp40,carton');
            $groups = $hasSecsz
                ? $groupsQuery->where('secsz', $pg->secsz)->groupBy('pcsp', 'urut')->orderBy('urut')->orderBy('pcsp', 'desc')->orderBy('packpk')->get()
                : $groupsQuery->groupBy('pcsp', 'urut')->orderBy('urut')->orderBy('pcsp', 'desc')->orderBy('packpk')->get();

            // Batch semua carton untuk entitas ini dalam satu query
            $cartonQuery = DB::table('pack')
                ->select('carton', 'pcs', 'keterangan', 'status', 'pcsp', 'urut')
                ->where('POno', $pg->POno)->where('OP', $pg->OP)
                ->where('customer', $pg->customer)->where('material', $pg->material);
            $allCartons = ($hasSecsz ? $cartonQuery->where('secsz', $pg->secsz) : $cartonQuery)
                ->orderBy('urut')->orderBy('packpk')->get()
                ->groupBy(fn($c) => $c->urut . '|' . $c->pcsp);

            $sizeGroups = [];
            foreach ($groups as $g) {
                $size = null;
                for ($i = 1; $i <= 40; $i++) {
                    if (($g->{"qtyp{$i}"} ?? 0) > 0) $size = $dt->{"size{$i}"} ?? null;
                }
                $pcsp2 = $g->pcsp == 0 ? '' : $g->pcsp;
                $ctn2  = $g->pcsp == 0 ? '' : $g->ctn;

                $cartons = $allCartons->get($g->urut . '|' . $g->pcsp, collect());

                $cartonRows = [];
                foreach ($cartons as $c) {
                    $cartonRows[] = $this->buildCartonInput($c, $g->pcsp, $c->pcs);
                }
                $sizeGroups[] = ['size' => $size, 'ctn' => $ctn2, 'pcsp' => $pcsp2, 'cartonRows' => $cartonRows];
            }
            $normalDetails[] = ['label' => $entityLabel, 'groups' => $sizeGroups];
        }

        // Mixed carton (urut = 21)
        $mixedQuery = DB::table('pack')
            ->where('urut', 21)->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material)
            ->selectRaw('count(*) as ctn, status, keterangan, carton, pcsp, pcs, urut,
                POno, customer, secsz, OP, material,
                qtyp1,qtyp2,qtyp3,qtyp4,qtyp5,qtyp6,qtyp7,qtyp8,qtyp9,qtyp10,
                qtyp11,qtyp12,qtyp13,qtyp14,qtyp15,qtyp16,qtyp17,qtyp18,qtyp19,qtyp20,
                qtyp21,qtyp22,qtyp23,qtyp24,qtyp25,qtyp26,qtyp27,qtyp28,qtyp29,qtyp30,
                qtyp31,qtyp32,qtyp33,qtyp34,qtyp35,qtyp36,qtyp37,qtyp38,qtyp39,qtyp40');
        $mixedCartons = $hasSecsz
            ? $mixedQuery->where('customer', $customer)->groupBy('carton')->orderBy('urut')->orderBy('packpk')->get()
            : $mixedQuery->groupBy('carton')->orderBy('urut')->orderBy('packpk')->get();

        // Batch semua sub-baris (per secsz/customer) untuk semua mixed carton sekaligus
        $mixedCartonNames = $mixedCartons->pluck('carton')->unique()->values()->all();
        $subRowsQuery = DB::table('pack')
            ->whereIn('carton', $mixedCartonNames)
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material)
            ->selectRaw('carton, count(*) as no, keterangan, pcsp, pcs, urut,
                POno, customer, secsz, OP, material,
                qtyp1,qtyp2,qtyp3,qtyp4,qtyp5,qtyp6,qtyp7,qtyp8,qtyp9,qtyp10,
                qtyp11,qtyp12,qtyp13,qtyp14,qtyp15,qtyp16,qtyp17,qtyp18,qtyp19,qtyp20,
                qtyp21,qtyp22,qtyp23,qtyp24,qtyp25,qtyp26,qtyp27,qtyp28,qtyp29,qtyp30,
                qtyp31,qtyp32,qtyp33,qtyp34,qtyp35,qtyp36,qtyp37,qtyp38,qtyp39,qtyp40');
        $allSubRows = $hasSecsz
            ? $subRowsQuery->where('customer', $customer)->groupBy('carton', 'secsz')->orderBy('secsz')->get()->groupBy('carton')
            : $subRowsQuery->groupBy('carton', 'customer')->orderBy('customer')->get()->groupBy('carton');

        $mixedRows = [];
        foreach ($mixedCartons as $mc) {
            $subRows = $allSubRows->get($mc->carton, collect());

            $labelLines = [];
            $pcsLines   = [];
            foreach ($subRows as $sub) {
                $entityLabel = $hasSecsz ? $sub->secsz : $sub->customer;
                $isMultiple  = $sub->no > 1;

                if (!$isMultiple) {
                    for ($i = 1; $i <= 40; $i++) {
                        if (($sub->{"qtyp{$i}"} ?? 0) > 0) {
                            $sizeName = $dt->{"size{$i}"} ?? '';
                            $labelLines[] = "{$entityLabel} | {$sizeName}";
                            $pcsLines[]   = $sub->{"qtyp{$i}"};
                        }
                    }
                } else {
                    $size = null;
                    $qty  = null;
                    for ($i = 1; $i <= 40; $i++) {
                        if (($sub->{"qtyp{$i}"} ?? 0) > 0) {
                            $size = $dt->{"size{$i}"} ?? null;
                            $qty  = $sub->{"qtyp{$i}"};
                        }
                    }
                    $labelLines[] = "{$entityLabel} | {$size}";
                    $pcsLines[]   = $qty ?? '';
                }
            }

            $cartonInput = $this->buildCartonInput($mc, $mc->pcsp, $mc->pcs);
            $mixedRows[] = [
                'labelLines'  => $labelLines,
                'pcsLines'    => $pcsLines,
                'cartonInput' => $cartonInput,
            ];
        }

        return [
            'hasSecsz'      => $hasSecsz,
            'entityRows'    => $entityRows,
            'grandTotal'    => $grandTotal,
            'ratioRows'     => $ratioRows,
            'tpcsp'         => $tpcsp,
            'normalDetails' => $normalDetails,
            'mixedRows'     => $mixedRows,
        ];
    }

    private function buildGab6Data($dt, string $customer): array
    {
        $poList = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('gabung', '6')
            ->selectRaw('*, ' . $this->sumFields('qty', 40))
            ->orderBy('customer')->get();

        $entityRows = [];
        foreach ($poList as $po) {
            $shipAgg = DB::table('pack')
                ->where('customer', $po->customer)->where('POno', $dt->POno)
                ->where('OP', $dt->OP)->where('material', $dt->material)
                ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())
                ->groupBy('customer')->first();

            [$order, $ship, $diff, $pct] = $this->calcSizeArrays($po, $shipAgg);
            $pcsTotal = $shipAgg->pcs ?? 0;
            $pcsDiff  = $pcsTotal - ($po->qty ?? 0);
            $ppcs     = !empty($po->qty) ? round($pcsDiff / $po->qty * 100, 2) : 0;

            $entityRows[] = [
                'label'       => $po->customer,
                'order'       => $order,
                'ship'        => $ship,
                'diff'        => $diff,
                'pct'         => $pct,
                'order_total' => $po->qty,
                'ship_total'  => $pcsTotal,
                'diff_total'  => $pcsDiff,
                'pct_total'   => $ppcs,
            ];
        }

        // Detail packing per customer (urut < 21)
        $custGroups = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('gabung', '6')
            ->groupBy('customer')->orderBy('customer')
            ->select('customer')->get();

        $detailByCustomer = [];
        foreach ($custGroups as $cg) {
            $groups = DB::table('pack')
                ->where('urut', '<', 21)
                ->where('POno', $dt->POno)->where('OP', $dt->OP)
                ->where('customer', $cg->customer)->where('material', $dt->material)
                ->selectRaw('count(*) as ctn, pcsp, urut, POno, customer, OP, material,
                    qtyp1,qtyp2,qtyp3,qtyp4,qtyp5,qtyp6,qtyp7,qtyp8,qtyp9,qtyp10,
                    qtyp11,qtyp12,qtyp13,qtyp14,qtyp15,qtyp16,qtyp17,qtyp18,qtyp19,qtyp20,
                    qtyp21,qtyp22,qtyp23,qtyp24,qtyp25,qtyp26,qtyp27,qtyp28,qtyp29,qtyp30,
                    qtyp31,qtyp32,qtyp33,qtyp34,qtyp35,qtyp36,qtyp37,qtyp38,qtyp39,qtyp40,carton')
                ->groupBy('pcsp', 'urut')->orderBy('urut')->orderBy('packpk')
                ->get();

            $allCartons = DB::table('pack')
                ->select('carton', 'pcs', 'keterangan', 'status', 'pcsp', 'urut')
                ->where('POno', $dt->POno)->where('OP', $dt->OP)
                ->where('customer', $cg->customer)->where('material', $dt->material)
                ->orderBy('urut')->orderBy('packpk')->get()
                ->groupBy(fn($c) => $c->urut . '|' . $c->pcsp);

            $sizeGroups = [];
            foreach ($groups as $g) {
                $size = null;
                for ($i = 1; $i <= 40; $i++) {
                    if (($g->{"qtyp{$i}"} ?? 0) > 0) $size = $dt->{"size{$i}"} ?? null;
                }
                $pcsp2 = $g->pcsp == 0 ? '' : $g->pcsp;
                $ctn2  = $g->pcsp == 0 ? '' : $g->ctn;

                $cartons = $allCartons->get($g->urut . '|' . $g->pcsp, collect());

                $cartonRows = [];
                foreach ($cartons as $c) {
                    $cartonRows[] = $this->buildCartonInput($c, $g->pcsp, $c->pcs);
                }
                $sizeGroups[] = ['size' => $size, 'ctn' => $ctn2, 'pcsp' => $pcsp2, 'cartonRows' => $cartonRows];
            }
            $detailByCustomer[] = ['label' => $cg->customer, 'groups' => $sizeGroups];
        }

        // Mixed carton (urut = 21) — shared across the whole material, not per customer
        $mixedCartons = DB::table('pack')
            ->where('urut', 21)->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)
            ->selectRaw('count(*) as ctn, status, keterangan, carton, pcsp, pcs, urut,
                POno, customer, OP, material,
                qtyp1,qtyp2,qtyp3,qtyp4,qtyp5,qtyp6,qtyp7,qtyp8,qtyp9,qtyp10,
                qtyp11,qtyp12,qtyp13,qtyp14,qtyp15,qtyp16,qtyp17,qtyp18,qtyp19,qtyp20,
                qtyp21,qtyp22,qtyp23,qtyp24,qtyp25,qtyp26,qtyp27,qtyp28,qtyp29,qtyp30,
                qtyp31,qtyp32,qtyp33,qtyp34,qtyp35,qtyp36,qtyp37,qtyp38,qtyp39,qtyp40')
            ->groupBy('carton')->orderBy('urut')->orderBy('packpk')->get();

        // Batch semua sub-baris per customer untuk semua mixed carton sekaligus
        $mixedCartonNames = $mixedCartons->pluck('carton')->unique()->values()->all();
        $allSubRows = DB::table('pack')
            ->whereIn('carton', $mixedCartonNames)
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material)
            ->selectRaw('carton, customer,
                qtyp1,qtyp2,qtyp3,qtyp4,qtyp5,qtyp6,qtyp7,qtyp8,qtyp9,qtyp10,
                qtyp11,qtyp12,qtyp13,qtyp14,qtyp15,qtyp16,qtyp17,qtyp18,qtyp19,qtyp20,
                qtyp21,qtyp22,qtyp23,qtyp24,qtyp25,qtyp26,qtyp27,qtyp28,qtyp29,qtyp30,
                qtyp31,qtyp32,qtyp33,qtyp34,qtyp35,qtyp36,qtyp37,qtyp38,qtyp39,qtyp40')
            ->groupBy('carton', 'customer')->orderBy('customer')->get()
            ->groupBy('carton');

        $mixedRows = [];
        foreach ($mixedCartons as $mc) {
            $subRows = $allSubRows->get($mc->carton, collect());

            $labelLines = [];
            $pcsLines   = [];
            foreach ($subRows as $sub) {
                $size = null;
                $qty  = null;
                for ($i = 1; $i <= 40; $i++) {
                    if (($sub->{"qtyp{$i}"} ?? 0) > 0) {
                        $size = $dt->{"size{$i}"} ?? null;
                        $qty  = $sub->{"qtyp{$i}"};
                    }
                }
                $labelLines[] = "{$sub->customer} | {$size}";
                $pcsLines[]   = $qty ?? '';
            }

            $cartonInput = $this->buildCartonInput($mc, $mc->pcsp, $mc->pcs);
            $mixedRows[] = ['labelLines' => $labelLines, 'pcsLines' => $pcsLines, 'cartonInput' => $cartonInput];
        }

        return [
            'entityRows'       => $entityRows,
            'detailByCustomer' => $detailByCustomer,
            'mixedRows'        => $mixedRows,
        ];
    }

    private function buildGab7Data($dt, $dt2, $dt3, $popk, string $customer): array
    {
        // 1) Order vs Ship breakdown (same shape as gab=3)
        [$order, $ship, $diff, $pct] = $this->calcSizeArrays($dt2, $dt3);
        $orderTotal = $dt2->qty ?? 0;
        $shipTotal  = $dt3->pcs ?? 0;
        $diffTotal  = $shipTotal - $orderTotal;
        $pctTotal   = !empty($orderTotal) ? round($diffTotal / $orderTotal * 100, 2) : 0;

        $orderShip = [
            'order' => $order,
            'ship' => $ship,
            'diff' => $diff,
            'pct' => $pct,
            'order_total' => $orderTotal,
            'ship_total' => $shipTotal,
            'diff_total' => $diffTotal,
            'pct_total' => $pctTotal,
        ];

        // 2) N.W / G.W cells (fixed: no longer references undefined $dt2 internally)
        $nwCells = $this->buildMeasureCells($dt, $customer, 'nw');
        $gwCells = $this->buildMeasureCells($dt, $customer, 'gw');

        // 3) Detail packing — normal groups (urut < 21), scoped to this popk
        $groups = DB::table('pack')
            ->where('popk', $popk)->where('urut', '<', 21)
            ->selectRaw('count(*) as ctn, pcsp, urut, part, POno, customer, OP, material,
                qtyp1,qtyp2,qtyp3,qtyp4,qtyp5,qtyp6,qtyp7,qtyp8,qtyp9,qtyp10,
                qtyp11,qtyp12,qtyp13,qtyp14,qtyp15,qtyp16,qtyp17,qtyp18,qtyp19,qtyp20,
                qtyp21,qtyp22,qtyp23,qtyp24,qtyp25,qtyp26,qtyp27,qtyp28,qtyp29,qtyp30,
                qtyp31,qtyp32,qtyp33,qtyp34,qtyp35,qtyp36,qtyp37,qtyp38,qtyp39,qtyp40,carton')
            ->groupBy('pcsp', 'urut')->orderBy('packpk')->orderBy('urut')
            ->get();

        $allCartons = DB::table('pack')
            ->select('carton', 'pcs', 'keterangan', 'status', 'pcsp', 'urut')
            ->where('popk', $popk)
            ->orderBy('packpk')->orderBy('urut')->get()
            ->groupBy(fn($c) => $c->urut . '|' . $c->pcsp);

        $normalGroups = [];
        foreach ($groups as $g) {
            $size = null;
            for ($i = 1; $i <= 40; $i++) {
                if (($g->{"qtyp{$i}"} ?? 0) > 0) $size = $dt->{"size{$i}"} ?? null;
            }
            $pcsp1 = $g->pcsp == 0 ? '' : $g->pcsp;
            $ctn1  = $g->pcsp == 0 ? '' : $g->ctn;

            $cartons = $allCartons->get($g->urut . '|' . $g->pcsp, collect());

            $cartonRows = [];
            foreach ($cartons as $c) {
                $cartonRows[] = $this->buildCartonInput($c, $g->pcsp, $c->pcs);
            }
            $normalGroups[] = ['size' => $size, 'ctn' => $ctn1, 'pcsp' => $pcsp1, 'cartonRows' => $cartonRows];
        }

        // 4) Detail packing — mixed carton (urut = 21), scoped to this popk
        $mixedCartons = DB::table('pack')
            ->where('popk', $popk)->where('urut', 21)
            ->select(
                'carton',
                'pcsp',
                'pcs',
                'keterangan',
                'status',
                'qtyp1',
                'qtyp2',
                'qtyp3',
                'qtyp4',
                'qtyp5',
                'qtyp6',
                'qtyp7',
                'qtyp8',
                'qtyp9',
                'qtyp10',
                'qtyp11',
                'qtyp12',
                'qtyp13',
                'qtyp14',
                'qtyp15',
                'qtyp16',
                'qtyp17',
                'qtyp18',
                'qtyp19',
                'qtyp20',
                'qtyp21',
                'qtyp22',
                'qtyp23',
                'qtyp24',
                'qtyp25',
                'qtyp26',
                'qtyp27',
                'qtyp28',
                'qtyp29',
                'qtyp30',
                'qtyp31',
                'qtyp32',
                'qtyp33',
                'qtyp34',
                'qtyp35',
                'qtyp36',
                'qtyp37',
                'qtyp38',
                'qtyp39',
                'qtyp40'
            )
            ->groupBy('carton')->orderBy('packpk')->orderBy('urut')
            ->get();

        $mixedRows = [];
        foreach ($mixedCartons as $mc) {
            $subRows = DB::table('pack')
                ->where('carton', $mc->carton)->where('popk', $popk)->where('urut', 21)
                ->get();

            $labelLines = [];
            $pcsLines   = [];
            foreach ($subRows as $sub) {
                for ($i = 1; $i <= 40; $i++) {
                    $v = $sub->{"qtyp{$i}"} ?? 0;
                    if ($v > 0) {
                        $labelLines[] = $dt->{"size{$i}"} ?? '';
                        $pcsLines[]   = $v;
                    }
                }
            }

            $pcsp2 = DB::table('pack')
                ->where('carton', $mc->carton)->where('popk', $popk)->where('urut', 21)
                ->sum('pcsp');

            $cartonInput = $this->buildCartonInput($mc, $mc->pcsp, $mc->pcs);
            $mixedRows[] = [
                'labelLines'  => $labelLines,
                'pcsLines'    => $pcsLines,
                'pcsp'        => $pcsp2,
                'cartonInput' => $cartonInput,
            ];
        }

        return [
            'orderShip'    => $orderShip,
            'nwCells'      => $nwCells,
            'gwCells'      => $gwCells,
            'normalGroups' => $normalGroups,
            'mixedRows'    => $mixedRows,
        ];
    }

    private function buildGab8Data($dt, string $customer): array
    {
        // ---- Part A: packing summary -----------------------------------------
        $matTotal = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material)
            ->selectRaw('sum(qty) as qty, sum(ctn) as ctn, ' . $this->sumFields('qty', 40))
            ->first();

        $orderRow = [
            'qty'   => [],
            'total' => $matTotal->qty ?? 0,
        ];
        for ($i = 1; $i <= 40; $i++) {
            $v = $matTotal->{"qty{$i}"} ?? 0;
            $orderRow['qty'][$i] = $v == 0 ? '' : $v;
        }

        $custPacks = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material)
            ->groupBy('customer')->orderBy('packpk')
            ->select('customer', 'popk')->get();

        // Batch: ambil semua po terkait dalam satu query, bukan satu query per customer
        $popks = $custPacks->pluck('popk')->unique()->values()->all();
        $posByPopk = DB::table('po')->whereIn('popk', $popks)->get()->keyBy('popk');

        $tqtyp = array_fill(1, 40, 0);
        $tctn2 = 0;
        $tpcsp2 = 0;

        $customerBlocks = [];
        foreach ($custPacks as $cp) {
            $po = $posByPopk->get($cp->popk);
            if (!$po) continue;

            $qtyRow = [];
            for ($i = 1; $i <= 40; $i++) {
                $v = $po->{"qty{$i}"} ?? 0;
                $qtyRow[$i] = $v == 0 ? '' : $v;
            }

            $groups = DB::table('pack')
                ->where('POno', $dt->POno)->where('OP', $dt->OP)
                ->where('customer', $cp->customer)->where('material', $dt->material)
                ->selectRaw('meas, nw, gw, count(*) as ctn, pcsp, urut, POno, customer, OP, material,
                    qtyp1,qtyp2,qtyp3,qtyp4,qtyp5,qtyp6,qtyp7,qtyp8,qtyp9,qtyp10,
                    qtyp11,qtyp12,qtyp13,qtyp14,qtyp15,qtyp16,qtyp17,qtyp18,qtyp19,qtyp20,
                    qtyp21,qtyp22,qtyp23,qtyp24,qtyp25,qtyp26,qtyp27,qtyp28,qtyp29,qtyp30,
                    qtyp31,qtyp32,qtyp33,qtyp34,qtyp35,qtyp36,qtyp37,qtyp38,qtyp39,qtyp40')
                ->groupBy('pcsp', 'urut')->orderBy('packpk')->orderBy('urut')
                ->get();

            $allCartons = DB::table('pack')
                ->select('carton', 'pcs', 'keterangan', 'status', 'pcsp', 'urut')
                ->where('POno', $dt->POno)->where('OP', $dt->OP)
                ->where('customer', $cp->customer)->where('material', $dt->material)
                ->orderBy('packpk')->orderBy('urut')->get()
                ->groupBy(fn($c) => $c->urut . '|' . $c->pcsp);

            $cartonGroupRows = [];
            foreach ($groups as $g) {
                $qtyPRow = [];
                for ($i = 1; $i <= 40; $i++) {
                    $v = $g->{"qtyp{$i}"} ?? 0;
                    $qtyPRow[$i] = $v == 0 ? '' : $v;
                    $tqtyp[$i] += $v * $g->ctn;
                }
                $totalPcs = $g->ctn * $g->pcsp;
                $tctn2  += $g->ctn;
                $tpcsp2 += $totalPcs;

                $cartons = $allCartons->get($g->urut . '|' . $g->pcsp, collect());

                $cartonRows = [];
                foreach ($cartons as $c) {
                    $cartonRows[] = $this->buildCartonInput($c, $g->pcsp, $c->pcs);
                }

                $cartonGroupRows[] = [
                    'cartonRows' => $cartonRows,
                    'qty'        => $qtyPRow,
                    'pcsp'       => $g->pcsp,
                    'ctn'        => $g->ctn,
                    'totalPcs'   => $totalPcs,
                    'meas'       => $g->meas,
                    'nw'         => $g->nw,
                    'gw'         => $g->gw,
                ];
            }

            $customerBlocks[] = [
                'label'        => $cp->customer,
                'qty'          => $qtyRow,
                'order_total'  => $po->qty ?? 0,
                'cartonGroups' => $cartonGroupRows,
            ];
        }

        $grandQty = [];
        for ($i = 1; $i <= 40; $i++) {
            $grandQty[$i] = $tqtyp[$i] == 0 ? '' : $tqtyp[$i];
        }

        $packingSummary = [
            'orderRow'       => $orderRow,
            'customerBlocks' => $customerBlocks,
            'grandTotal'     => ['qty' => $grandQty, 'ctn' => $tctn2, 'totalPcs' => $tpcsp2],
        ];

        // ---- Part B: gab=8 breakdown (Order/Ship/+/-/% per customer + Total) --
        $poList = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('gabung', '8')
            ->selectRaw('*, ' . $this->sumFields('qty', 40))
            ->orderBy('customer')->get();

        $entityRows = [];
        foreach ($poList as $po) {
            $shipAgg = DB::table('pack')
                ->where('customer', $po->customer)->where('POno', $dt->POno)
                ->where('OP', $dt->OP)->where('material', $dt->material)
                ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())
                ->groupBy('customer')->first();

            [$order, $ship, $diff, $pct] = $this->calcSizeArrays($po, $shipAgg);
            $pcsTotal = $shipAgg->pcs ?? 0;
            $pcsDiff  = $pcsTotal - ($po->qty ?? 0);
            $ppcs     = !empty($po->qty) ? round($pcsDiff / $po->qty * 100, 2) : 0;

            $entityRows[] = [
                'label' => $po->customer,
                'order' => $order,
                'ship' => $ship,
                'diff' => $diff,
                'pct' => $pct,
                'order_total' => $po->qty,
                'ship_total' => $pcsTotal,
                'diff_total' => $pcsDiff,
                'pct_total' => $ppcs,
            ];
        }

        $totalOrder = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('gabung', '8')
            ->selectRaw('sum(qty) as qty, ' . $this->sumFields('qty', 40))->first();
        $totalShip = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('material', $dt->material)
            ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())->first();
        $grandTotal = $this->buildGrandTotalBlock($totalOrder, $totalShip);

        return [
            'packingSummary' => $packingSummary,
            'entityRows'     => $entityRows,
            'grandTotal'     => $grandTotal,
        ];
    }

    private function buildGab9Data($dt, string $customer): array
    {
        $poList = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('customer', $customer)
            ->where('gabung', '9')
            ->selectRaw('*, ' . $this->sumFields('qty', 40))
            ->orderBy('material')->get();

        [$materialRows, $nwByMaterial, $gwByMaterial] = $this->buildGab9And10MaterialRows($dt, $customer, $poList, true);

        $totalOrder = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('customer', $customer)
            ->where('gabung', '9')
            ->selectRaw('sum(qty) as qty, ' . $this->sumFields('qty', 40))->first();
        $totalShip = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('customer', $customer)
            ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())->first();
        $grandTotal = $this->buildGrandTotalBlock($totalOrder, $totalShip);

        $materials = $poList->pluck('material')->unique()->values()->all();
        $detailByMaterial   = $this->buildMaterialDetailPacking($dt, $customer, $materials);
        $crossMaterialMixed = $this->buildCrossMaterialMixed($dt, $customer);

        return [
            'materialRows'       => $materialRows,
            'nwByMaterial'       => $nwByMaterial,
            'gwByMaterial'       => $gwByMaterial,
            'grandTotal'         => $grandTotal,
            'detailByMaterial'   => $detailByMaterial,
            'crossMaterialMixed' => $crossMaterialMixed,
        ];
    }

    private function buildGab10Data($dt, string $customer): array
    {
        $poList = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('customer', $customer)
            ->where('gabung', '10')
            ->selectRaw('*, ' . $this->sumFields('qty', 40))
            ->orderBy('material')->get();

        [$materialRows, $nwByMaterial, $gwByMaterial] = $this->buildGab9And10MaterialRows($dt, $customer, $poList, false);

        $totalOrder = DB::table('po')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('customer', $customer)
            ->where('gabung', '10')
            ->selectRaw('sum(qty) as qty, ' . $this->sumFields('qty', 40))->first();
        $totalShip = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)->where('customer', $customer)
            ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())->first();
        $grandTotal = $this->buildGrandTotalBlock($totalOrder, $totalShip);

        $materials = $poList->pluck('material')->unique()->values()->all();
        $detailByMaterial   = $this->buildMaterialDetailPacking($dt, $customer, $materials);
        $crossMaterialMixed = $this->buildCrossMaterialMixed($dt, $customer);

        return [
            'materialRows'       => $materialRows,
            'nwByMaterial'       => $nwByMaterial,
            'gwByMaterial'       => $gwByMaterial,
            'grandTotal'         => $grandTotal,
            'detailByMaterial'   => $detailByMaterial,
            'crossMaterialMixed' => $crossMaterialMixed,
        ];
    }

    /**
     * Shared per-material Order/Ship/+/-/% + N.W/G.W builder for gab=9/10.
     * $byPopk = true  -> gab=9 (ship tied to the specific po row's popk)
     * $byPopk = false -> gab=10 (ship tied to material+customer broadly)
     *
     * N.W/G.W are resolved with one query per material (see
     * buildMeasureRowsForMaterial) instead of 40 x 2 queries per material.
     */
    private function buildGab9And10MaterialRows($dt, string $customer, $poList, bool $byPopk): array
    {
        $materialRows = [];
        $nwByMaterial = [];
        $gwByMaterial = [];

        foreach ($poList as $po) {
            if ($byPopk) {
                $shipAgg = DB::table('pack')
                    ->where('popk', $po->popk)
                    ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())
                    ->groupBy('material')->first();
            } else {
                $shipAgg = DB::table('pack')
                    ->where('material', $po->material)->where('POno', $dt->POno)
                    ->where('OP', $dt->OP)->where('customer', $customer)
                    ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty())
                    ->groupBy('material')->first();
            }

            [$order, $ship, $diff, $pct] = $this->calcSizeArrays($po, $shipAgg);
            $pcsTotal = $shipAgg->pcs ?? 0;
            $pcsDiff  = $pcsTotal - ($po->qty ?? 0);
            $ppcs     = !empty($po->qty) ? round($pcsDiff / $po->qty * 100, 2) : 0;

            $materialRows[] = [
                'material'    => $po->material,
                'order'       => $order,
                'ship' => $ship,
                'diff' => $diff,
                'pct' => $pct,
                'order_total' => $po->qty,
                'ship_total' => $pcsTotal,
                'diff_total'  => $pcsDiff,
                'pct_total' => $ppcs,
            ];

            [$nwRow, $gwRow] = $this->buildMeasureRowsForMaterial($dt, $customer, $po->material);
            $nwByMaterial[] = ['material' => $po->material, 'nw' => $nwRow];
            $gwByMaterial[] = ['material' => $po->material, 'gw' => $gwRow];
        }

        return [$materialRows, $nwByMaterial, $gwByMaterial];
    }

    /**
     * Resolves N.W/G.W per size column for one material in a single query,
     * replacing the old "one query per size per measure" (80 queries)
     * pattern used previously by buildGab2Data and buildGab9And10MaterialRows.
     *
     * For each active size i, picks the nw/gw of the first pack row (by
     * packpk) where qtyp{i} > 0 — equivalent to the legacy
     * "group by nw order by packpk value('nw')" query when there's a single
     * nw/gw per size, which holds for realistic packing data.
     */
    private function buildMeasureRowsForMaterial($dt, ?string $customer, ?string $material, ?string $secsz = null): array
    {
        $query = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP);

        $material === null ? $query->whereNull('material') : $query->where('material', $material);
        $customer === null ? $query->whereNull('customer') : $query->where('customer', $customer);

        if ($secsz !== null) {
            $query->where('secsz', $secsz);
        }

        $rows = $query
            ->orderBy('packpk')
            ->selectRaw('nw, gw, ' . collect(range(1, 40))->map(fn($i) => "qtyp{$i}")->implode(', '))
            ->get();

        $nwRow = array_fill(1, 40, '');
        $gwRow = array_fill(1, 40, '');
        $assigned = array_fill(1, 40, false);

        foreach ($rows as $r) {
            for ($i = 1; $i <= 40; $i++) {
                if (!$assigned[$i] && ($r->{"qtyp{$i}"} ?? 0) > 0) {
                    $nwRow[$i] = $r->nw;
                    $gwRow[$i] = $r->gw;
                    $assigned[$i] = true;
                }
            }
        }

        return [$nwRow, $gwRow];
    }

    private function buildGrandTotalBlock($totalOrder, $totalShip): array
    {
        [$grandOrder, $grandShip, $grandDiff, $grandPct] = $this->calcSizeArrays($totalOrder, $totalShip);
        $grandPcsTotal = $totalShip->pcs ?? 0;
        $grandPcsDiff  = $grandPcsTotal - ($totalOrder->qty ?? 0);
        $grandPpcs     = !empty($totalOrder->qty) ? round($grandPcsDiff / $totalOrder->qty * 100, 2) : 0;

        return [
            'order' => $grandOrder,
            'ship' => $grandShip,
            'diff' => $grandDiff,
            'pct' => $grandPct,
            'order_total' => $totalOrder->qty ?? 0,
            'ship_total' => $grandPcsTotal,
            'diff_total' => $grandPcsDiff,
            'pct_total' => $grandPpcs,
        ];
    }

    /**
     * Detail packing per material for one customer: normal size groups
     * (urut < 21) plus single-carton "mixed" rows (urut = 21) scoped to that
     * one material. Shared by gab=9 and gab=10.
     *
     * Mixed-carton rows are now fetched in one query per material (grouped
     * in PHP) instead of one query per distinct carton.
     */
    private function buildMaterialDetailPacking($dt, string $customer, array $materials): array
    {
        $detailByMaterial = [];

        foreach ($materials as $material) {
            $groups = DB::table('pack')
                ->where('urut', '<', 21)
                ->where('POno', $dt->POno)->where('OP', $dt->OP)
                ->where('material', $material)->where('customer', $customer)
                ->selectRaw('count(*) as ctn, pcsp, urut, POno, customer, OP, material,
                    qtyp1,qtyp2,qtyp3,qtyp4,qtyp5,qtyp6,qtyp7,qtyp8,qtyp9,qtyp10,
                    qtyp11,qtyp12,qtyp13,qtyp14,qtyp15,qtyp16,qtyp17,qtyp18,qtyp19,qtyp20,
                    qtyp21,qtyp22,qtyp23,qtyp24,qtyp25,qtyp26,qtyp27,qtyp28,qtyp29,qtyp30,
                    qtyp31,qtyp32,qtyp33,qtyp34,qtyp35,qtyp36,qtyp37,qtyp38,qtyp39,qtyp40,carton')
                ->groupBy('pcsp', 'urut')->orderBy('packpk')
                ->get();

            $allCartons = DB::table('pack')
                ->select('carton', 'pcs', 'keterangan', 'status', 'pcsp', 'urut')
                ->where('POno', $dt->POno)->where('OP', $dt->OP)
                ->where('material', $material)->where('customer', $customer)
                ->orderBy('packpk')->get()
                ->groupBy(fn($c) => $c->urut . '|' . $c->pcsp);

            $sizeGroups = [];
            foreach ($groups as $g) {
                $size = null;
                for ($i = 1; $i <= 40; $i++) {
                    if (($g->{"qtyp{$i}"} ?? 0) > 0) $size = $dt->{"size{$i}"} ?? null;
                }
                $pcsp1 = $g->pcsp == 0 ? '' : $g->pcsp;
                $ctn1  = $g->pcsp == 0 ? '' : $g->ctn;

                $cartons = $allCartons->get($g->urut . '|' . $g->pcsp, collect());

                $cartonRows = [];
                foreach ($cartons as $c) {
                    $cartonRows[] = $this->buildCartonInput($c, $g->pcsp, $c->pcs);
                }
                $sizeGroups[] = ['size' => $size, 'ctn' => $ctn1, 'pcsp' => $pcsp1, 'cartonRows' => $cartonRows];
            }

            // Mixed carton (urut = 21): satu query untuk seluruh material,
            // dikelompokkan per carton di PHP, ambil baris pertama per carton
            // (menyamai perilaku ->first() versi sebelumnya).
            $mixedRowsRaw = DB::table('pack')
                ->where('POno', $dt->POno)->where('OP', $dt->OP)
                ->where('material', $material)->where('customer', $customer)->where('urut', 21)
                ->orderBy('packpk')->get();

            $mixedRows = [];
            foreach ($mixedRowsRaw->groupBy('carton') as $rowsForCarton) {
                $row = $rowsForCarton->first();

                $labelLines = [];
                $pcsLines   = [];
                for ($i = 1; $i <= 40; $i++) {
                    $v = $row->{"qtyp{$i}"} ?? 0;
                    if ($v > 0) {
                        $labelLines[] = $dt->{"size{$i}"} ?? '';
                        $pcsLines[]   = $v;
                    }
                }

                $cartonInput = $this->buildCartonInput($row, $row->pcsp, $row->pcs);
                $mixedRows[] = ['labelLines' => $labelLines, 'pcsLines' => $pcsLines, 'pcsp' => $row->pcsp, 'cartonInput' => $cartonInput];
            }

            $detailByMaterial[] = ['material' => $material, 'groups' => $sizeGroups, 'mixedRows' => $mixedRows];
        }

        return $detailByMaterial;
    }

    /**
     * Cross-material "mixed carton" block (urut = 22): a single carton that
     * holds pieces from more than one material for the same customer.
     * Shared by gab=9 and gab=10. Fetches all urut=22 rows for the customer
     * in one query and groups by carton in PHP, instead of one query per
     * carton.
     */
    private function buildCrossMaterialMixed($dt, string $customer): array
    {
        $allRows = DB::table('pack')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('customer', $customer)->where('urut', 22)
            ->orderBy('carton')->orderBy('material')
            ->get();

        $rows = [];
        foreach ($allRows->groupBy('carton') as $materialRowsRaw) {
            if ($materialRowsRaw->isEmpty()) continue;

            $materialBlocks = [];
            $pcspTotal = 0;
            foreach ($materialRowsRaw as $mr) {
                $labelLines = [];
                $pcsLines   = [];
                for ($i = 1; $i <= 40; $i++) {
                    $v = $mr->{"qtyp{$i}"} ?? 0;
                    if ($v > 0) {
                        $labelLines[] = $dt->{"size{$i}"} ?? '';
                        $pcsLines[]   = $v;
                        $pcspTotal   += $v;
                    }
                }
                $materialBlocks[] = [
                    'material'   => $mr->material,
                    'labelLines' => $labelLines,
                    'pcsLines'   => $pcsLines,
                ];
            }

            // Use the first row as the representative carton record for status/keterangan
            $top = $materialRowsRaw->first();
            $cartonInput = $this->buildCartonInput($top, $pcspTotal, $top->pcs);

            $rows[] = [
                'materialBlocks' => $materialBlocks,
                'pcsp'           => $pcspTotal,
                'cartonInput'    => $cartonInput,
            ];
        }

        return $rows;
    }

    /**
     * Builds pre-rendered N.W or G.W table-cell HTML for gab=7.
     * $field is 'nw' or 'gw'.
     *
     * Legacy behaviour: if the highest urut among rows with $field>0 is 21,
     * cells for urut=21 rows render every active size label slash-joined
     * followed by the value; other rows in that same result set render each
     * active size label on its own line followed by the value. If no row
     * ever sits at urut=21, cells are just the plain value.
     *
     * FIX: no longer references an undefined $dt2 — size labels always come
     * from $dt, which is the only PO row in scope for this method.
     */
    private function buildMeasureCells($dt, string $customer, string $field): array
    {
        $maxUrut = DB::table('pack')
            ->where($field, '>', 0)
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('customer', $customer)
            ->groupBy('urut')->orderBy('urut', 'desc')
            ->value('urut');

        if ($maxUrut != 21) {
            $rows = DB::table('pack')
                ->where($field, '>', 0)
                ->where('POno', $dt->POno)->where('OP', $dt->OP)
                ->where('material', $dt->material)->where('customer', $customer)
                ->groupBy($field, 'urut')->orderBy('urut')
                ->get([$field]);

            return $rows->map(fn($r) => (string) $r->{$field})->all();
        }

        // Mixed mode: need qty1..qty40 (pack's own size-qty columns) plus urut/value
        $rows = DB::table('pack')
            ->where($field, '>', 0)
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('customer', $customer)
            ->groupBy($field, 'urut')->orderBy('urut')
            ->selectRaw("urut, {$field}, " . collect(range(1, 40))->map(fn($i) => "qty{$i}")->implode(', '))
            ->get();

        $cells = [];
        foreach ($rows as $r) {
            $labels = [];
            for ($i = 1; $i <= 40; $i++) {
                if (($r->{"qty{$i}"} ?? 0) > 0) {
                    $labels[] = $dt->{"size{$i}"} ?? '';
                }
            }
            if ($r->urut == 21) {
                $html = implode('', array_map(fn($l) => "<b>{$l}</b>/", $labels));
                $html .= "<hr> {$r->{$field}}";
            } else {
                $html = implode('', array_map(fn($l) => "<b>{$l}</b><hr>", $labels));
                $html .= (string) $r->{$field};
            }
            $cells[] = $html;
        }
        return $cells;
    }

    private function calcTotalCtn($dt, $dt2, string $customer)
    {
        $base = $dt2 ? $dt2->ctn : 0;
        switch ((int) $dt->gabung) {
            case 1:
                return DB::table('po')
                    ->where('POno', $dt->POno)->where('OP', $dt->OP)
                    ->where('customer', $customer)->where('gabung', '1')
                    ->groupBy('popk')->orderBy('popk', 'desc')->value('ctn') ?? 0;
            case 2:
            case 9:
            case 10:
                return DB::table('po')
                    ->where('POno', $dt->POno)->where('OP', $dt->OP)
                    ->where('customer', $customer)->where('gabung', $dt->gabung)
                    ->groupBy('material')->sum('ctn');
            case 4:
                return DB::table('po')
                    ->where('POno', $dt->POno)->where('OP', $dt->OP)
                    ->where('material', $dt->material)->where('gabung', '4')
                    ->groupBy('popk')->orderBy('popk', 'desc')->value('ctn') ?? 0;
            case 6:
                return DB::table('pack')
                    ->where('pcsp', '>', 0)->where('POno', $dt->POno)
                    ->where('OP', $dt->OP)->where('material', $dt->material)
                    ->orderBy('packpk', 'desc')->value('carton') ?? 0;
            case 5:
            case 8:
                return DB::table('po')
                    ->where('POno', $dt->POno)->where('OP', $dt->OP)
                    ->where('material', $dt->material)->where('gabung', $dt->gabung)
                    ->groupBy('material')->value('ctn') ?? 0;
            default:
                return $base;
        }
    }

    private function buildDetailPacking($dt, string $customer): array
    {
        $groups = DB::table('pack')
            ->selectRaw('
                count(*) as ctn, pcsp, urut, POno, customer, OP, material,
                qtyp1,qtyp2,qtyp3,qtyp4,qtyp5,qtyp6,qtyp7,qtyp8,qtyp9,qtyp10,
                qtyp11,qtyp12,qtyp13,qtyp14,qtyp15,qtyp16,qtyp17,qtyp18,qtyp19,qtyp20,
                qtyp21,qtyp22,qtyp23,qtyp24,qtyp25,qtyp26,qtyp27,qtyp28,qtyp29,qtyp30,
                qtyp31,qtyp32,qtyp33,qtyp34,qtyp35,qtyp36,qtyp37,qtyp38,qtyp39,qtyp40,
                carton
            ')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('customer', $customer)
            ->groupBy('pcsp', 'urut')->orderBy('urut')->orderBy('packpk')
            ->get();

        $allCartons = DB::table('pack')
            ->select('carton', 'pcs', 'keterangan', 'status', 'pcsp', 'urut')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('customer', $customer)
            ->orderBy('urut')->orderBy('packpk')->get()
            ->groupBy(fn($c) => $c->urut . '|' . $c->pcsp);

        $result = [];
        foreach ($groups as $g) {
            $size = null;
            for ($i = 1; $i <= 40; $i++) {
                if (($g->{"qtyp{$i}"} ?? 0) > 0) $size = $dt->{"size{$i}"} ?? null;
            }

            $cartons = $allCartons->get($g->urut . '|' . $g->pcsp, collect());

            $cartonRows = [];
            foreach ($cartons as $c) {
                $cartonRows[] = $this->buildCartonInput($c, $g->pcsp, $c->pcs);
            }

            $result[] = [
                'size'       => $size,
                'ctn'        => $g->ctn,
                'pcsp'       => $g->pcsp,
                'cartonRows' => $cartonRows,
            ];
        }
        return $result;
    }

    private function calcSizeArrays($orderRow, $shipRow, string $prefix = 'qty'): array
    {
        $order = $ship = $diff = $pct = [];
        for ($i = 1; $i <= 40; $i++) {
            $a = $orderRow->{"qty{$i}"} ?? 0;
            $b = $shipRow->{"qty{$i}"}  ?? 0;
            $order[$i] = $a == 0 ? '' : $a;
            $ship[$i]  = $b == 0 ? '' : $b;
            $d = $b - $a;
            $diff[$i] = $d != 0 ? $d : '';
            $pct[$i]  = !empty($a) ? round($d / $a * 100, 2) : '';
        }
        return [$order, $ship, $diff, $pct];
    }

    private function buildCartonInput($c, $refPcsp, $tqty): array
    {
        $label = !empty($c->keterangan)
            ? "{$c->carton} ({$c->keterangan})"
            : $c->carton;
        $width = !empty($c->keterangan) ? '15%' : '7%';

        $style = "width:{$width};";
        if ($c->status == 4) {
            if ($refPcsp == $tqty)
                $style = "width:{$width}; background-color:#8FBC8F; color:#000;";
            elseif ($tqty < $refPcsp && $tqty > 0)
                $style = "width:{$width}; background-color:#DC143C; color:#000;";
        } elseif ($c->status == 5) {
            $style = "width:{$width}; background-color:#8FBC8F; color:#000;";
        }

        return ['label' => $label, 'style' => $style];
    }

    private function sumFields(string $prefix, int $max): string
    {
        return collect(range(1, $max))
            ->map(fn($i) => "sum({$prefix}{$i}) as {$prefix}{$i}")
            ->implode(', ');
    }

    private function sumQtypAsQty(): string
    {
        return collect(range(1, 40))
            ->map(fn($i) => "sum(qtyp{$i}) as qty{$i}")
            ->implode(', ');
    }

    private function sumQtypFields(): string
    {
        return collect(range(1, 40))
            ->map(fn($i) => "sum(qtyp{$i}) as qty{$i}")
            ->implode(', ');
    }

    private function sumPrefixQtyp(string $table): string
    {
        return collect(range(1, 40))
            ->map(fn($i) => "sum({$table}.qtyp{$i}) as qtyp{$i}")
            ->implode(', ');
    }

    private function resolveConnection(int $mif): string
    {
        return $mif === 1 ? 'mysql_andon' : 'mysql';
    }

    public function printGlobal(Request $request)
    {
        $request->validate([
            'op'    => 'required',
            'po'    => 'nullable',
            'poref' => 'nullable',
            'mif'   => 'nullable',
        ]);

        $po    = $request->input('po');
        $op    = $request->input('op');
        $poref = $request->input('poref');
        $mif   = (int) $request->input('mif', session('pos'));

        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);

        // ============================================================
        // Ambil SEMUA baris po dalam scope PO+OP+poref ini.
        // ============================================================
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

        $popkIds = $poRows->pluck('popk');

        // ============================================================
        // Union semua Size aktif -- kandidat awal (nama size tidak kosong),
        // lalu BUANG size yang Order Qty TOTAL-nya 0/kosong di seluruh
        // scope ini (supaya tidak ada kolom "hantu" tanpa data nyata).
        // ============================================================
        $activeSizesCandidate = [];
        foreach ($poRows as $row) {
            for ($i = 1; $i <= 40; $i++) {
                $sz = $row->{"size{$i}"} ?? null;
                if (!empty($sz) && !isset($activeSizesCandidate[$i])) {
                    $activeSizesCandidate[$i] = $sz;
                }
            }
        }
        ksort($activeSizesCandidate);

        $activeSizes = [];
        foreach ($activeSizesCandidate as $i => $sz) {
            $orderQtyCheck = (int) $db->table('po')->whereIn('popk', $popkIds)->sum("qty{$i}");
            if ($orderQtyCheck > 0) {
                $activeSizes[$i] = $sz;
            }
        }
        ksort($activeSizes);

        // Reverse-map label size (teks) -> index i -- dibutuhkan karena
        // tabel `output` menyimpan size sebagai TEKS ("M","L"), bukan
        // kolom qty1..40 seperti `bj`.
        $sizeLabelToIndex = array_flip($activeSizes);

        // ============================================================
        // BREAKDOWN SIZE & QTY -- per Color/Sec Size DULU, baru diagregat
        // jadi TOTAL. Ship Qty = bj + output (dipetakan via $sizeLabelToIndex).
        // ============================================================
        $popksByCombo = $poRows->groupBy(function ($r) {
            return ($r->material ?? '-') . '||' . ($r->secsz ?? '');
        });

        $orderShipByCombo = [];

        foreach ($popksByCombo as $comboKey => $comboPoRows) {
            $comboPopkIds = $comboPoRows->pluck('popk');
            $repPo        = $comboPoRows->first();

            $bjSums = [];
            foreach ($activeSizes as $i => $sz) {
                $bjSums[$i] = (int) $db->table('bj')->whereIn('popk', $comboPopkIds)->sum("qty{$i}");
            }

            $outputSums = $db->table('output')
                ->whereIn('popk', $comboPopkIds)
                ->select('size')
                ->selectRaw('SUM(jmlpcs) as total')
                ->groupBy('size')
                ->get();

            $outputBySize = [];
            foreach ($outputSums as $osRow) {
                $idx = $sizeLabelToIndex[$osRow->size] ?? null;
                if ($idx !== null) {
                    $outputBySize[$idx] = ($outputBySize[$idx] ?? 0) + (int) $osRow->total;
                }
            }

            $comboRow = [
                'order' => [],
                'ship' => [],
                'diff' => [],
                'pct' => [],
                'order_total' => 0,
                'ship_total' => 0,
                'diff_total' => 0,
                'pct_total' => 0,
            ];

            foreach ($activeSizes as $i => $sz) {
                $orderQty = (int) $db->table('po')->whereIn('popk', $comboPopkIds)->sum("qty{$i}");
                $shipQty  = ($bjSums[$i] ?? 0) + ($outputBySize[$i] ?? 0);

                $comboRow['order'][$i] = $orderQty;
                $comboRow['ship'][$i]  = $shipQty;
                $comboRow['diff'][$i]  = $shipQty - $orderQty;
                $comboRow['pct'][$i]   = $orderQty > 0 ? round(($shipQty / $orderQty) * 100, 2) : 0;

                $comboRow['order_total'] += $orderQty;
                $comboRow['ship_total']  += $shipQty;
            }
            $comboRow['diff_total'] = $comboRow['ship_total'] - $comboRow['order_total'];
            $comboRow['pct_total']  = $comboRow['order_total'] > 0
                ? round(($comboRow['ship_total'] / $comboRow['order_total']) * 100, 2)
                : 0;

            $orderShipByCombo[] = [
                'material' => $repPo->material ?? '-',
                'secsz'    => $repPo->secsz ?? '',
                'data'     => $comboRow,
            ];
        }

        $orderShip = [
            'order' => [],
            'ship' => [],
            'diff' => [],
            'pct' => [],
            'order_total' => 0,
            'ship_total' => 0,
            'diff_total' => 0,
            'pct_total' => 0,
        ];

        foreach ($activeSizes as $i => $sz) {
            $orderShip['order'][$i] = 0;
            $orderShip['ship'][$i]  = 0;

            foreach ($orderShipByCombo as $combo) {
                $orderShip['order'][$i] += $combo['data']['order'][$i] ?? 0;
                $orderShip['ship'][$i]  += $combo['data']['ship'][$i] ?? 0;
            }

            $orderShip['diff'][$i] = $orderShip['ship'][$i] - $orderShip['order'][$i];
            $orderShip['pct'][$i]  = $orderShip['order'][$i] > 0
                ? round(($orderShip['ship'][$i] / $orderShip['order'][$i]) * 100, 2)
                : 0;
        }
        $orderShip['order_total'] = array_sum($orderShip['order']);
        $orderShip['ship_total']  = array_sum($orderShip['ship']);
        $orderShip['diff_total']  = $orderShip['ship_total'] - $orderShip['order_total'];
        $orderShip['pct_total']   = $orderShip['order_total'] > 0
            ? round(($orderShip['ship_total'] / $orderShip['order_total']) * 100, 2)
            : 0;

        // ============================================================
        // N.W / G.W -- 1 nilai per CARTON FISIK, skip kalau carton itu
        // tidak punya data ukuran sama sekali (dua-duanya kosong/0).
        // ============================================================
        $allPacks = $db->table('pack')
            ->whereIn('popk', $popkIds)
            ->orderBy('urut')
            ->orderBy('packpk')
            ->get();

        $groupedByCarton = $allPacks->groupBy('carton');

        $nwCells = [];
        $gwCells = [];
        foreach ($groupedByCarton as $rowsInCarton) {
            $repCartonRow = $rowsInCarton->first();
            $nwVal = $repCartonRow->nw ?? '';
            $gwVal = $repCartonRow->gw ?? '';

            $nwEmpty = ($nwVal === '' || $nwVal === null || (float) $nwVal == 0);
            $gwEmpty = ($gwVal === '' || $gwVal === null || (float) $gwVal == 0);

            if ($nwEmpty && $gwEmpty) {
                continue;
            }

            $nwCells[] = $nwVal;
            $gwCells[] = $gwVal;
        }

        // ============================================================
        // DETAIL PACKING -- carton-centric: group per CARTON FISIK dulu
        // (supaya nomor carton TIDAK pernah diulang lintas Color/Sec
        // Size), lalu pisah Simple (1 combo per carton) vs Mixed (>1
        // combo per carton), dan gabungkan carton yang breakdown-nya
        // IDENTIK (Plan DAN Actual sama) jadi 1 baris dengan CTN & daftar
        // Carton No gabungan.
        // ============================================================
        $rawCartonBlocks = [];

        foreach ($groupedByCarton as $cartonNo => $rowsInCarton) {
            $comboGroups = $rowsInCarton->groupBy(function ($r) {
                return ($r->material ?? '-') . '||' . ($r->secsz ?? '');
            });

            $combos = [];
            foreach ($comboGroups as $comboRows) {
                $repRow    = $comboRows->first();
                $sizeLines = [];
                $totalPlan = 0;
                $totalActual = 0;

                foreach ($activeSizes as $i => $sz) {
                    $planSum   = 0;
                    $actualSum = 0;
                    foreach ($comboRows as $r) {
                        $planSum   += (int) ($r->{"qtyp{$i}"} ?? 0);
                        $actualSum += (int) ($r->{"qty{$i}"} ?? 0);
                    }
                    if ($planSum > 0) {
                        $sizeLines[] = "{$sz}: {$planSum}";
                        $totalPlan  += $planSum;
                    }
                    $totalActual += $actualSum;
                }

                $combos[] = [
                    'material'    => $repRow->material ?? '-',
                    'secsz'       => $repRow->secsz ?? '',
                    'sizeLines'   => $sizeLines,
                    'totalPlan'   => $totalPlan,
                    'totalActual' => $totalActual,
                ];
            }

            usort($combos, function ($a, $b) {
                return [$a['material'], $a['secsz']] <=> [$b['material'], $b['secsz']];
            });

            $rawCartonBlocks[] = [
                'carton'   => $cartonNo,
                'combos'   => $combos,
                'isSimple' => count($combos) === 1,
                'pcs'      => (int) $rowsInCarton->sum('pcs'),
                'pcsp'     => (int) $rowsInCarton->sum('pcsp'),
                'repRow'   => $rowsInCarton->first(),
            ];
        }

        $simpleBlocks = collect($rawCartonBlocks)
            ->filter(fn($b) => $b['isSimple'])
            ->map(function ($b) {
                $c = $b['combos'][0];
                return [
                    'carton'      => $b['carton'],
                    'combos'      => $b['combos'],
                    'cartonInput' => $this->buildCartonInput($b['repRow'], $c['totalPlan'], $c['totalActual']),
                ];
            })
            ->values();

        $mixedBlocks = collect($rawCartonBlocks)
            ->filter(fn($b) => !$b['isSimple'])
            ->map(function ($b) {
                return [
                    'carton'      => $b['carton'],
                    'combos'      => $b['combos'],
                    'cartonInput' => $this->buildCartonInput($b['repRow'], $b['pcsp'], $b['pcs']),
                ];
            })
            ->values();

        // ---- 1) Gabungkan carton SIMPLE yang profilnya identik ----
        $simpleGroups = $simpleBlocks->groupBy(function ($b) {
            $c = $b['combos'][0];

            if (empty($c['sizeLines'])) {
                return $c['material'] . '||' . $c['secsz'] . '||EMPTY||' . $b['carton'];
            }

            return $c['material'] . '||' . $c['secsz'] . '||' . implode(',', $c['sizeLines']) . '||actual:' . $c['totalActual'];
        });

        $detailPackingSimpleRows = [];
        foreach ($simpleGroups as $group) {
            $repBlock = $group->first();
            $c        = $repBlock['combos'][0];

            $detailPackingSimpleRows[] = [
                'material'   => $c['material'],
                'secsz'      => $c['secsz'],
                'sizeLines'  => $c['sizeLines'],
                'ctn'        => $group->count(),
                'pcsp'       => $c['totalPlan'],
                'cartonRows' => $group->map(fn($b) => $b['cartonInput'])->values()->all(),
            ];
        }

        // ---- 2) Gabungkan carton MIXED yang profilnya identik ----
        $mixedGroups = $mixedBlocks->groupBy(function ($block) {
            $sig = collect($block['combos'])->map(function ($c) {
                return $c['material'] . '|' . $c['secsz'] . '|' . implode(',', $c['sizeLines']) . '|actual:' . $c['totalActual'];
            })->implode('||');

            $allEmpty = collect($block['combos'])->every(fn($c) => empty($c['sizeLines']));
            if ($allEmpty) {
                return $sig . '||EMPTY||' . $block['carton'];
            }

            return $sig;
        });

        $detailPackingMixedRows = [];
        foreach ($mixedGroups as $group) {
            $repBlock = $group->first();
            $combos   = $repBlock['combos'];
            $n        = count($combos);

            $cartonInputs = $group->map(fn($b) => $b['cartonInput'])->values()->all();
            $ctnCount     = $group->count();

            $rowspanCarton = $n;
            $idx = 0;
            while ($idx < $n) {
                $j = $idx + 1;
                while ($j < $n && $combos[$j]['material'] === $combos[$idx]['material']) {
                    $j++;
                }
                $colorRowspan = $j - $idx;

                $k = $idx;
                while ($k < $j) {
                    $m = $k + 1;
                    while ($m < $j && $combos[$m]['secsz'] === $combos[$k]['secsz']) {
                        $m++;
                    }
                    $secszRowspan = $m - $k;

                    for ($p = $k; $p < $m; $p++) {
                        $detailPackingMixedRows[] = [
                            'material'        => $combos[$p]['material'],
                            'secsz'           => $combos[$p]['secsz'],
                            'sizeLines'       => $combos[$p]['sizeLines'],
                            'pcsp'            => $combos[$p]['totalPlan'],
                            'showColor'       => $p === $idx,
                            'colorRowspan'    => $colorRowspan,
                            'showSecsz'       => $p === $k,
                            'secszRowspan'    => $secszRowspan,
                            'isFirstOfCarton' => $p === 0,
                            'rowspanCarton'   => $rowspanCarton,
                            'ctn'             => $ctnCount,
                            'cartonInputs'    => $cartonInputs,
                        ];
                    }
                    $k = $m;
                }

                $idx = $j;
            }
        }

        // ============================================================
        // Header laporan -- Customer/Color list, Total CTN, Meas CTN,
        // Last Updated. Versi Global: TIDAK ada konsep "gabung" seperti
        // sistem lama.
        // ============================================================
        $dtp = $db->table('pack')
            ->whereIn('popk', $popkIds)
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu')
            ->first();

        $customersList = $poRows->pluck('customer')->filter()->unique()->values();
        $materialsList = $poRows->pluck('material')->filter()->unique()->values();

        // Total CTN = jumlah CARTON FISIK UNIK (bukan SUM(po.ctn) per popk).
        $totalCtn = $groupedByCarton->count();

        $measList = $allPacks->pluck('meas')->filter()->unique()->values();

        return view('menu.packing.laporan.pdf-global', compact(
            'dt',
            'dt2',
            'activeSizes',
            'orderShip',
            'orderShipByCombo',
            'nwCells',
            'gwCells',
            'dtp',
            'customersList',
            'materialsList',
            'totalCtn',
            'measList',
            'detailPackingSimpleRows',
            'detailPackingMixedRows'
        ));
    }
}
