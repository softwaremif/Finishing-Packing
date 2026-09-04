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
            ->selectRaw('
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
            'dtp', 'dt', 'dt2', 'dt3', 'gab', 'cheader', 'cjml',
            'customersList', 'materialsList', 'totalCtn', 'measList',
            'nwList', 'gwList', 'detailPacking', 'gabData'
        ));
    }

    private function buildGabData(string $gab, $dt, $dt2, $dt3, $popk, string $customer): array
    {
        switch ($gab) {
            case '1':  return $this->buildGab1Data($dt, $customer);
            case '2':  return $this->buildGab2Data($dt, $customer);
            case '3':  return $this->buildGab3Data($dt, $popk, $customer);
            case '4':  return $this->buildGab4Data($dt, $customer);
            case '5':  return $this->buildGab5Data($dt, $customer);
            case '6':  return $this->buildGab6Data($dt, $customer);
            case '7':  return $this->buildGab7Data($dt, $dt2, $dt3, $popk, $customer);
            case '8':  return $this->buildGab8Data($dt, $customer);
            case '9':  return $this->buildGab9Data($dt, $customer);
            case '10': return $this->buildGab10Data($dt, $customer);
            default:   return [];
        }
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
            'order' => $order, 'ship' => $ship, 'diff' => $diff, 'pct' => $pct,
            'order_total' => $orderTotal, 'ship_total' => $shipTotal,
            'diff_total' => $diffTotal, 'pct_total' => $pctTotal,
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
            ->groupBy(fn ($c) => $c->urut . '|' . $c->pcsp);

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
            ->select('carton', 'pcsp', 'pcs', 'keterangan', 'status',
                'qtyp1','qtyp2','qtyp3','qtyp4','qtyp5','qtyp6','qtyp7','qtyp8','qtyp9','qtyp10',
                'qtyp11','qtyp12','qtyp13','qtyp14','qtyp15','qtyp16','qtyp17','qtyp18','qtyp19','qtyp20',
                'qtyp21','qtyp22','qtyp23','qtyp24','qtyp25','qtyp26','qtyp27','qtyp28','qtyp29','qtyp30',
                'qtyp31','qtyp32','qtyp33','qtyp34','qtyp35','qtyp36','qtyp37','qtyp38','qtyp39','qtyp40')
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

            return $rows->map(fn ($r) => (string) $r->{$field})->all();
        }

        // Mixed mode: need qty1..qty40 (pack's own size-qty columns) plus urut/value
        $rows = DB::table('pack')
            ->where($field, '>', 0)
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('customer', $customer)
            ->groupBy($field, 'urut')->orderBy('urut')
            ->selectRaw("urut, {$field}, " . collect(range(1, 40))->map(fn ($i) => "qty{$i}")->implode(', '))
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
                $html = implode('', array_map(fn ($l) => "<b>{$l}</b>/", $labels));
                $html .= "<hr> {$r->{$field}}";
            } else {
                $html = implode('', array_map(fn ($l) => "<b>{$l}</b><hr>", $labels));
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
            ->groupBy(fn ($c) => $c->urut . '|' . $c->pcsp);

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

    private function sumQtypFields(): string
    {
        return collect(range(1, 40))
            ->map(fn($i) => "sum(qtyp{$i}) as qty{$i}")
            ->implode(', ');
    }
}