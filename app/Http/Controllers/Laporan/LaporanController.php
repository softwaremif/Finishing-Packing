<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    /**
     * Laporan Packing List (Global) -- SATU-SATUNYA laporan yang aktif di
     * sistem ini. Scope: 1 PO + OP + poref tertentu, mif sebagai atribut
     * bisnis (BUKAN pemilih koneksi -- koneksi SELALU 'mysql', database
     * sudah global sejak konsolidasi Packing/FG-Stuffing).
     */
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

        // FIX UTAMA -- koneksi SELALU 'mysql'. Tidak ada lagi pemilihan
        // koneksi berdasarkan mif (dulu mysql_andon vs mysql) -- 'mif'
        // sekarang murni atribut data bisnis (po.mif), bukan pemilih host.
        $db = DB::connection('mysql');

        // ============================================================
        // Ambil SEMUA baris po dalam scope PO+OP+poref ini.
        // ============================================================
        $poRows = $db->table('po')
            ->where('OP', $op)
            ->where('mif', $mif)
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

        $sumQtyExpr = collect(range(1, 40))->map(fn ($i) => "SUM(qty{$i}) as qty{$i}")->implode(', ');
        $orderSumsForActiveCheck = $db->table('po')->whereIn('popk', $popkIds)->selectRaw($sumQtyExpr)->first();

        $activeSizes = [];
        foreach ($activeSizesCandidate as $i => $sz) {
            if ((int) ($orderSumsForActiveCheck->{"qty{$i}"} ?? 0) > 0) {
                $activeSizes[$i] = $sz;
            }
        }
        ksort($activeSizes);

        // ============================================================
        // BREAKDOWN SIZE & QTY -- per Color/Sec Size DULU, baru diagregat
        // jadi TOTAL. FIX UTAMA -- Ship Qty SEKARANG dari pack.qty{i}
        // (Actual), HANYA baris yang status-nya SUDAH 6 atau 7 (sudah
        // benar-benar Shipment/Locked) -- BUKAN lagi dari bj/output (itu
        // data Polibag/Transfer, tahap SEBELUM carton di-packing). Tetap
        // 100% scoped ke popk PO ini saja ($comboPopkIds), TIDAK ada
        // logic OP-global di sini.
        // ============================================================
        $popksByCombo = $poRows->groupBy(function ($r) {
            return ($r->customer ?? '-') . '||' . ($r->material ?? '-') . '||' . ($r->secsz ?? '');
        });
        
        $orderShipByCombo = [];
        
        foreach ($popksByCombo as $comboKey => $comboPoRows) {
            $comboPopkIds = $comboPoRows->pluck('popk');
            $repPo        = $comboPoRows->first();
        
            $shipSumsForCombo = $db->table('pack')
                ->whereIn('popk', $comboPopkIds)
                ->whereIn('status', [6, 7])
                ->selectRaw($sumQtyExpr)
                ->first();
        
            $orderSumsForCombo = $db->table('po')->whereIn('popk', $comboPopkIds)->selectRaw($sumQtyExpr)->first();
        
            $comboRow = [
                'order' => [], 'ship' => [], 'diff' => [], 'pct' => [],
                'order_total' => 0, 'ship_total' => 0, 'diff_total' => 0, 'pct_total' => 0,
            ];
        
            foreach ($activeSizes as $i => $sz) {
                $orderQty = (int) ($orderSumsForCombo->{"qty{$i}"} ?? 0);
                $shipQty  = (int) ($shipSumsForCombo->{"qty{$i}"} ?? 0);
        
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
                'customer' => $repPo->customer ?? '-', // BARU
                'material' => $repPo->material ?? '-',
                'secsz'    => $repPo->secsz ?? '',
                'data'     => $comboRow,
            ];
        }

        $orderShip = [
            'order' => [], 'ship' => [], 'diff' => [], 'pct' => [],
            'order_total' => 0, 'ship_total' => 0, 'diff_total' => 0, 'pct_total' => 0,
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
        // Ambil semua baris pack dalam scope ini + lookup Bundle.
        // ============================================================
        $allPacks = $db->table('pack')
            ->whereIn('popk', $popkIds)
            ->orderBy('urut')
            ->orderBy('packpk')
            ->get();

        $bundlepksInvolved = $allPacks->pluck('bundlepk')->filter()->unique()->values();
        $bundleNameMap = [];
        if ($bundlepksInvolved->isNotEmpty()) {
            $bundleNameMap = $db->table('carton_bundle')
                ->whereIn('bundlepk', $bundlepksInvolved)
                ->pluck('bundle_carton', 'bundlepk');
        }

        // FIX UTAMA -- grouping SEKARANG carton+part, BUKAN carton saja --
        // carton dengan nomor SAMA tapi Part/Session BEDA (fisik berbeda)
        // tidak lagi tercampur jadi satu.
        $groupedByCartonPart = $allPacks->groupBy(function ($r) {
            return $r->carton . '|' . $this->normalizePartKey($r->part);
        });

        // ============================================================
        // N.W / G.W / DIMENSI CARTON (Panjang x Lebar x Tinggi) -- 1 nilai
        // per CARTON FISIK (carton+part). Dimensi dari pack.panjang/
        // pack.lebar/pack.tinggi (3 kolom numerik), BUKAN lagi dari field
        // 'meas' tunggal (legacy, sudah tidak dipakai di skema saat ini).
        // ============================================================
        $nwCells   = [];
        $gwCells   = [];
        $measCells = [];

        foreach ($groupedByCartonPart as $rowsInCarton) {
            $rep = $rowsInCarton->first();

            $nwVal = $rep->nw ?? '';
            $gwVal = $rep->gw ?? '';
            $nwEmpty = ($nwVal === '' || $nwVal === null || (float) $nwVal == 0);
            $gwEmpty = ($gwVal === '' || $gwVal === null || (float) $gwVal == 0);
            if (!$nwEmpty || !$gwEmpty) {
                $nwCells[] = $nwVal;
                $gwCells[] = $gwVal;
            }

            $p = $rep->panjang ?? null;
            $l = $rep->lebar ?? null;
            $t = $rep->tinggi ?? null;
            $pEmpty = ($p === null || $p === '' || (float) $p == 0);
            $lEmpty = ($l === null || $l === '' || (float) $l == 0);
            $tEmpty = ($t === null || $t === '' || (float) $t == 0);
            if (!($pEmpty && $lEmpty && $tEmpty)) {
                $measCells[] = sprintf(
                    '%s x %s x %s cm',
                    $pEmpty ? '-' : $p,
                    $lEmpty ? '-' : $l,
                    $tEmpty ? '-' : $t
                );
            }
        }
        $measCells = collect($measCells)->unique()->values();

        // ============================================================
        // DETAIL PACKING -- carton-centric: group per CARTON+PART dulu,
        // pisah Simple (1 combo per carton) vs Mixed (>1 combo per carton),
        // gabungkan carton yang breakdown-nya IDENTIK jadi 1 baris. Tiap
        // blok carton diberi anotasi Bundle/Mix PO kalau relevan.
        // ============================================================
        $rawCartonBlocks = [];

        foreach ($groupedByCartonPart as $rowsInCarton) {
            $comboGroups = $rowsInCarton->groupBy(function ($r) {
                return ($r->customer ?? '-') . '||' . ($r->material ?? '-') . '||' . ($r->secsz ?? '');
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
                    'customer'    => $repRow->customer ?? '-', // BARU
                    'material'    => $repRow->material ?? '-',
                    'secsz'       => $repRow->secsz ?? '',
                    'sizeLines'   => $sizeLines,
                    'totalPlan'   => $totalPlan,
                    'totalActual' => $totalActual,
                ];
            }
        
            usort($combos, function ($a, $b) {
                return [$a['customer'], $a['material'], $a['secsz']] <=> [$b['customer'], $b['material'], $b['secsz']]; // GANTI -- ikutkan customer
            });

            $repRow = $rowsInCarton->first();
            $repRow->bundle_carton = $repRow->bundlepk ? ($bundleNameMap[$repRow->bundlepk] ?? null) : null;
            $repRow->is_mix = !empty($repRow->mixno);

            $rawCartonBlocks[] = [
                'carton'   => $repRow->carton,
                'combos'   => $combos,
                'isSimple' => count($combos) === 1,
                'pcs'      => (int) $rowsInCarton->sum('pcs'),
                'pcsp'     => (int) $rowsInCarton->sum('pcsp'),
                'repRow'   => $repRow,
            ];
        }

        $simpleBlocks = collect($rawCartonBlocks)
            ->filter(fn ($b) => $b['isSimple'])
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
            ->filter(fn ($b) => !$b['isSimple'])
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
                return $c['customer'] . '||' . $c['material'] . '||' . $c['secsz'] . '||EMPTY||' . $b['carton'];
            }
        
            return $c['customer'] . '||' . $c['material'] . '||' . $c['secsz'] . '||' . implode(',', $c['sizeLines']) . '||actual:' . $c['totalActual'];
        });
        
        $detailPackingSimpleRows = [];
        foreach ($simpleGroups as $group) {
            $repBlock = $group->first();
            $c        = $repBlock['combos'][0];
        
            $detailPackingSimpleRows[] = [
                'customer'   => $c['customer'], // BARU
                'material'   => $c['material'],
                'secsz'      => $c['secsz'],
                'sizeLines'  => $c['sizeLines'],
                'ctn'        => $group->count(),
                'pcsp'       => $c['totalPlan'],
                'cartonRows' => $group->map(fn ($b) => $b['cartonInput'])->values()->all(),
            ];
        }

        // ---- 2) Gabungkan carton MIXED yang profilnya identik ----
        $mixedGroups = $mixedBlocks->groupBy(function ($block) {
            $sig = collect($block['combos'])->map(function ($c) {
                return $c['customer'] . '|' . $c['material'] . '|' . $c['secsz'] . '|' . implode(',', $c['sizeLines']) . '|actual:' . $c['totalActual'];
            })->implode('||');
        
            $allEmpty = collect($block['combos'])->every(fn ($c) => empty($c['sizeLines']));
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
        
            $cartonInputs = $group->map(fn ($b) => $b['cartonInput'])->values()->all();
            $ctnCount     = $group->count();
        
            $rowspanCarton = $n;
            $idx = 0;
            while ($idx < $n) {
                $j = $idx + 1;
                // GANTI -- rowspan Color SEKARANG juga mempertimbangkan customer
                // (biar Color yang sama tapi customer beda TETAP baris terpisah).
                while ($j < $n && $combos[$j]['customer'] === $combos[$idx]['customer'] && $combos[$j]['material'] === $combos[$idx]['material']) {
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
                            'customer'        => $combos[$p]['customer'], // BARU
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
        // Header laporan -- Customer/Color list, Total CTN, Last Updated.
        // ============================================================
        $dtp = $db->table('pack')
            ->whereIn('popk', $popkIds)
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu')
            ->first();

        $customersList = $poRows->pluck('customer')->filter()->unique()->values();
        $materialsList = $poRows->pluck('material')->filter()->unique()->values();

        // Total CTN -- Bundle-aware (carton besar dihitung SEBAGAI 1,
        // bukan per carton kecil di dalamnya), dan carton nomor sama+part
        // beda tetap terhitung sebagai unit terpisah.
        $countingUnits = $groupedByCartonPart->groupBy(function ($group) {
            $rep = $group->first();
            return $rep->bundlepk
                ? ('bundle_' . $rep->bundlepk)
                : ('single_' . $rep->carton . '|' . $this->normalizePartKey($rep->part));
        });
        $totalCtn = $countingUnits->count();

        $bundleInfoMap = [];
        if ($bundlepksInvolved->isNotEmpty()) {
            $bundleRowsFull = $db->table('carton_bundle')->whereIn('bundlepk', $bundlepksInvolved)->get();
            foreach ($bundleRowsFull as $br) {
                $bundleInfoMap[$br->bundlepk] = $br;
            }
        }
        // $bundleNameMap TETAP dipakai di tempat lain (label anotasi) -- turunkan dari $bundleInfoMap:
        $bundleNameMap = collect($bundleInfoMap)->map(fn ($b) => $b->bundle_carton);
        
        // BARU -- Total N.W / G.W (Kg) & Total CBM (m3), standar wajib tampil di
        // Packing List industri garment.
        $totalNw  = 0.0;
        $totalGw  = 0.0;
        $totalCbm = 0.0;
        
        foreach ($countingUnits as $unit) {
    $rep = $unit->first()->first(); // GANTI -- double ->first() (grup -> baris)
 
    if ($rep->bundlepk && isset($bundleInfoMap[$rep->bundlepk])) {
        $b = $bundleInfoMap[$rep->bundlepk];
        $nw = (float) ($b->nw ?? 0);
        $gw = (float) ($b->gw ?? 0);
        $p  = (float) ($b->panjang ?? 0);
        $l  = (float) ($b->lebar ?? 0);
        $t  = (float) ($b->tinggi ?? 0);
    } else {
        $nw = (float) ($rep->nw ?? 0);
        $gw = (float) ($rep->gw ?? 0);
        $p  = (float) ($rep->panjang ?? 0);
        $l  = (float) ($rep->lebar ?? 0);
        $t  = (float) ($rep->tinggi ?? 0);
    }
 
    $totalNw += $nw;
    $totalGw += $gw;
    if ($p > 0 && $l > 0 && $t > 0) {
        $totalCbm += ($p * $l * $t) / 1000000;
    }
}
$totalCbm = round($totalCbm, 3);

        return view('menu.packing.laporan.pdf-global', compact(
            'dt',
            'dt2',
            'activeSizes',
            'orderShip',
            'orderShipByCombo',
            'nwCells',
            'gwCells',
            'measCells',
            'dtp',
            'customersList',
            'materialsList',
            'totalCtn',
            'totalNw',   // BARU
            'totalGw',   // BARU
            'totalCbm',  // BARU
            'detailPackingSimpleRows',
            'detailPackingMixedRows'
        ));
    }

    /**
     * Bangun label + style 1 kotak carton di kolom "CARTON NO" -- warna
     * hijau kalau Actual sudah PENUH (status 4 & pas) atau sudah Segel
     * (status 5), merah kalau Actual masih KURANG dari Plan tapi > 0.
     * Label diberi anotasi Bundle/Mix PO kalau properti itu di-set
     * (lihat printGlobal() -- $repRow->bundle_carton / $repRow->is_mix).
     */
    private function buildCartonInput($c, $refPcsp, $tqty): array
    {
        $label = !empty($c->keterangan)
            ? "{$c->carton} ({$c->keterangan})"
            : $c->carton;
    
        if (!empty($c->bundle_carton ?? null)) {
            $label .= " [Bundle: {$c->bundle_carton}]";
        }
        if (!empty($c->is_mix ?? false)) {
            $label .= ' [Mix PO]';
        }
    
        $width = !empty($c->keterangan) ? '15%' : '7%';
    
        if ((int) ($c->segel ?? 0) === 1) {
            $statusKey = 'sealed';
        } elseif ($tqty <= 0) {
            $statusKey = 'pending';
        } elseif ($tqty < $refPcsp) {
            $statusKey = 'partial';
        } else {
            $statusKey = 'complete';
        }
    
        // bg + warna teks per status -- 'partial'/'complete' pertahankan
        // kontras ASLI (teks hitam di atas hijau/merah muda), 'sealed' pakai
        // teks putih (background biru lebih gelap).
        $styleByStatus = [
            'pending'  => "width:{$width};",
            'partial'  => "width:{$width}; background-color:#DC143C; color:#000;",
            'complete' => "width:{$width}; background-color:#8FBC8F; color:#000;",
            'sealed'   => "width:{$width}; background-color:#0369A1; color:#fff;",
        ];
    
        $style = $styleByStatus[$statusKey];
    
        return ['label' => $label, 'style' => $style, 'statusKey' => $statusKey]; // BARU -- statusKey
    }

    /**
     * Normalisasi nilai Part/Session jadi string yang bisa dibandingkan --
     * null/''/0 semuanya dianggap "tanpa session" (key kosong).
     */
    private function normalizePartKey($p): string
    {
        if ($p === null || $p === '') return '';
        if (is_numeric($p) && (float) $p === 0.0) return '';
        return (string) $p;
    }
}