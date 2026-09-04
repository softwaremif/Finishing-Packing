<?php

namespace App\Services;

use App\Repositories\ShipRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Service: logika bisnis Finished Goods.
 * Semua akses DB didelegasikan ke ShipRepository.
 * Kompatibel PHP 7 (tanpa arrow function & typed property).
 */
class ShipService
{
    /** @var ShipRepository */
    protected $shipRepo;

    public function __construct(ShipRepository $shipRepo)
    {
        $this->shipRepo = $shipRepo;
    }

    /* =====================================================
     |  LIST
     ===================================================== */

    // public function getListShip(Request $request, array $extraParams = []): array
    // {
    //     $page   = (int) ($request->page ?? 1);
    //     $rows   = (int) ($request->rows ?? 50);
    //     $offset = ($page - 1) * $rows;

    //     // Koneksi dinamis (session pos) sudah dipusatkan di ShipRepository::conn()
    //     // dan berlaku untuk SEMUA query repository, bukan hanya list.
    //     $result = $this->shipRepo->getShipList($offset, $rows, array_merge([
    //         'search' => $request->search,
    //         'buyer'  => $request->buyer,
    //         'year'   => $request->year,
    //         'status' => $request->status,
    //         'sort'   => $request->sort,
    //     ], $extraParams));

    //     foreach ($result['data'] as $i => $row) {
    //         $row->no = $offset + $i + 1;
    //     }

    //     return [
    //         'total' => $result['total'],
    //         'rows'  => $result['data'],
    //     ];
    // }
    public function getListShip(Request $request, array $extraParams = []): array
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;

        $result = $this->shipRepo->getShipList($offset, $rows, array_merge([
            'search' => $request->search,
            'buyer'  => $request->buyer,
            'year'   => $request->year,
            'status' => $request->status,
            'sort'   => $request->sort,
            'ex_factory' => $request->ex_factory,
        ], $extraParams));

        // Tambahkan URL gambar order, SAMA seperti di PackingController.
        $this->addOrderImageToRows($result['data']);

        foreach ($result['data'] as $i => $row) {
            $row->no = $offset + $i + 1;
        }

        return [
            'total' => $result['total'],
            'rows'  => $result['data'],
        ];
    }

    /**
     * Rincian satu grup POno+OP per popk+part (isi popup grid
     * finished goods sebelum masuk halaman detail).
     */
    public function getPoOpBreakdown(string $POno, string $OP, string $poref = ''): array
    {
        return $this->shipRepo->getShipBreakdownByPoOp($POno, $OP, $poref)->all();
    }

    /**
     * Laporan GLOBAL (finGoods.printGlobal): mencakup SELURUH baris dalam
     * satu PO+OP -- lintas semua customer/place, material/color, secondary
     * size, dan part. Berbeda dari:
     *   - finGoods.print   : scoped popk+part (satu baris grid sebelum agregasi)
     *   - laporan gab (1-10): scoped satu grup gabung / popk halaman
     * Model data & algoritma grouping mengikuti printGlobal() versi modul
     * packing (tabel pack/bj/output), disesuaikan ke skema ship/po: tabel
     * `ship` di sini SUDAH menyimpan rencana (qtyp{i}) dan aktual (qty{i})
     * dalam satu baris, jadi tidak perlu tabel terpisah seperti bj/output.
     * Mengembalikan null kalau PO+OP tidak ditemukan sama sekali.
     */
    public function getFinishedGoodsPrintGlobal(string $POno, string $OP)
    {
        $core = $this->buildGlobalBreakdown($POno, $OP);
        if ($core === null) {
            return null;
        }

        $core['dtp'] = $this->shipRepo->getLatestShipUpdateGlobal($POno, $OP);

        return $core;
    }

    /**
     * Inti builder laporan GLOBAL (lintas seluruh PO+OP): dipakai DUA
     * tempat --
     *   1) getFinishedGoodsPrintGlobal() -> halaman print-global mandiri
     *      (menambahkan $dtp untuk timestamp "Last updated").
     *   2) buildGabData() case 'global' -> gabData yang dirender di
     *      DALAM halaman detail/print biasa (partial laporan.pdf-global),
     *      supaya ringkasan global bisa dilihat tanpa keluar dari
     *      halaman detail popk+part yang sedang dibuka.
     * Mengembalikan null kalau PO+OP tidak ditemukan sama sekali.
     */
    private function buildGlobalBreakdown(string $POno, string $OP, $currentPopk = null, $currentPart = null)
    {
        $poRows = $this->shipRepo->getGlobalPoRows($POno, $OP);
        if ($poRows->isEmpty()) {
            return null;
        }

        // Representasi header (season/buyer/style/silhouette/shipdate1-2/
        // sap1-2/wh/ket/POno/OP) -- semua PO+OP ini seharusnya seragam
        // untuk field-field itu, jadi baris pertama cukup mewakili.
        $dt2 = $poRows->first();

        // Union semua size aktif (nama size tidak kosong), lalu buang size
        // yang SUM(qty{i}) di SELURUH PO+OP ini = 0 (kolom "hantu" tanpa
        // data nyata).
        $activeSizesCandidate = [];
        foreach ($poRows as $row) {
            for ($i = 1; $i <= 40; $i++) {
                $sz = isset($row->{"size{$i}"}) ? $row->{"size{$i}"} : null;
                if (!empty($sz) && !isset($activeSizesCandidate[$i])) {
                    $activeSizesCandidate[$i] = $sz;
                }
            }
        }
        ksort($activeSizesCandidate);

        $activeSizes = [];
        foreach ($activeSizesCandidate as $i => $sz) {
            $sum = 0;
            foreach ($poRows as $row) {
                $sum += (int) (isset($row->{"qty{$i}"}) ? $row->{"qty{$i}"} : 0);
            }
            if ($sum > 0) {
                $activeSizes[$i] = $sz;
            }
        }
        ksort($activeSizes);

        // Semua baris ship dalam PO+OP ini -- lintas popk/part/customer/
        // material/secsz sekaligus (ship sudah punya kolom POno/OP sendiri,
        // tidak perlu join ke po/popk untuk scoping ini).
        $shipRows = $this->shipRepo->getGlobalShipRows($POno, $OP);

        /* ================= BREAKDOWN PER COMBO (material + secsz) ================= */

        $poByCombo = $poRows->groupBy(function ($r) {
            return (isset($r->material) ? $r->material : '-') . '||' . (isset($r->secsz) ? $r->secsz : '');
        });
        $shipByCombo = $shipRows->groupBy(function ($r) {
            return (isset($r->material) ? $r->material : '-') . '||' . (isset($r->secsz) ? $r->secsz : '');
        });

        $orderShipByCombo = [];
        foreach ($poByCombo as $comboKey => $comboPoRows) {
            $repPo         = $comboPoRows->first();
            $comboShipRows = $shipByCombo->has($comboKey) ? $shipByCombo->get($comboKey) : collect();

            $d = [
                'order' => [], 'ship' => [], 'diff' => [], 'pct' => [],
                'order_total' => 0, 'ship_total' => 0, 'diff_total' => 0, 'pct_total' => 0,
            ];

            foreach ($activeSizes as $i => $sz) {
                $orderQty = 0;
                foreach ($comboPoRows as $r) {
                    $orderQty += (int) (isset($r->{"qty{$i}"}) ? $r->{"qty{$i}"} : 0);
                }

                // "Ship Qty" = SUM(ship.qtyp{i}) -- rencana yang sudah masuk
                // carton, konsisten dengan konvensi dt3/getShipQtypPcspTotals
                // yang dipakai laporan pdf0 (bukan qty aktual yang scan-based).
                $shipQty = 0;
                foreach ($comboShipRows as $r) {
                    $shipQty += (int) (isset($r->{"qtyp{$i}"}) ? $r->{"qtyp{$i}"} : 0);
                }

                $d['order'][$i] = $orderQty;
                $d['ship'][$i]  = $shipQty;
                $d['diff'][$i]  = $shipQty - $orderQty;
                $d['pct'][$i]   = $orderQty > 0 ? round($shipQty / $orderQty * 100, 2) : 0;

                $d['order_total'] += $orderQty;
                $d['ship_total']  += $shipQty;
            }
            $d['diff_total'] = $d['ship_total'] - $d['order_total'];
            $d['pct_total']  = $d['order_total'] > 0
                ? round($d['ship_total'] / $d['order_total'] * 100, 2)
                : 0;

            $orderShipByCombo[] = [
                'material' => isset($repPo->material) ? $repPo->material : '-',
                'secsz'    => isset($repPo->secsz) ? $repPo->secsz : '',
                'data'     => $d,
            ];
        }

        /* ================= TOTAL KESELURUHAN ================= */

        $orderShip = [
            'order' => [], 'ship' => [], 'diff' => [], 'pct' => [],
            'order_total' => 0, 'ship_total' => 0, 'diff_total' => 0, 'pct_total' => 0,
        ];
        foreach ($activeSizes as $i => $sz) {
            $orderShip['order'][$i] = 0;
            $orderShip['ship'][$i]  = 0;
            foreach ($orderShipByCombo as $combo) {
                $orderShip['order'][$i] += isset($combo['data']['order'][$i]) ? $combo['data']['order'][$i] : 0;
                $orderShip['ship'][$i]  += isset($combo['data']['ship'][$i]) ? $combo['data']['ship'][$i] : 0;
            }
            $orderShip['diff'][$i] = $orderShip['ship'][$i] - $orderShip['order'][$i];
            $orderShip['pct'][$i]  = $orderShip['order'][$i] > 0
                ? round($orderShip['ship'][$i] / $orderShip['order'][$i] * 100, 2)
                : 0;
        }
        $orderShip['order_total'] = array_sum($orderShip['order']);
        $orderShip['ship_total']  = array_sum($orderShip['ship']);
        $orderShip['diff_total']  = $orderShip['ship_total'] - $orderShip['order_total'];
        $orderShip['pct_total']   = $orderShip['order_total'] > 0
            ? round($orderShip['ship_total'] / $orderShip['order_total'] * 100, 2)
            : 0;

        /* ================= N.W / G.W: 1 nilai per carton fisik =================
         * CATATAN RISIKO: carton di-group apa adanya (nilai kolom carton),
         * sama seperti list/laporan gab lain di modul ini. Kalau ada nomor
         * carton yang kebetulan sama antar popk/material berbeda dalam
         * PO+OP yang sama, baris itu ikut tergabung sebagai satu carton
         * fisik di laporan ini -- risiko yang sama dengan yang sudah
         * didiskusikan untuk laporan gab gabungan (lihat PENDING di jurnal).
         */
        $groupedByCarton = $shipRows->groupBy('carton');

        $nwCells = [];
        $gwCells = [];
        foreach ($groupedByCarton as $rowsInCarton) {
            $rep = $rowsInCarton->first();
            $nw  = isset($rep->nw) ? $rep->nw : '';
            $gw  = isset($rep->gw) ? $rep->gw : '';

            $nwEmpty = ($nw === '' || $nw === null || (float) $nw == 0);
            $gwEmpty = ($gw === '' || $gw === null || (float) $gw == 0);
            if ($nwEmpty && $gwEmpty) {
                continue;
            }
            $nwCells[] = $nw;
            $gwCells[] = $gw;
        }

        /* ================= DETAIL PACKING: carton-centric =================
         * Group per CARTON FISIK dulu (supaya nomor carton tidak diulang
         * lintas combo material/secsz), lalu pisah Simple (1 combo per
         * carton) vs Mixed (>1 combo per carton), gabungkan carton yang
         * profil breakdown-nya (plan & actual) IDENTIK jadi 1 baris.
         */
        $rawCartonBlocks = [];
        foreach ($groupedByCarton as $cartonNo => $rowsInCarton) {
            $comboGroups = $rowsInCarton->groupBy(function ($r) {
                return (isset($r->material) ? $r->material : '-') . '||' . (isset($r->secsz) ? $r->secsz : '');
            });

            $combos = [];
            foreach ($comboGroups as $comboRows) {
                $rep         = $comboRows->first();
                $sizeLines   = [];
                $totalPlan   = 0;
                $totalActual = 0;

                foreach ($activeSizes as $i => $sz) {
                    $planSum   = 0;
                    $actualSum = 0;
                    foreach ($comboRows as $r) {
                        $planSum   += (int) (isset($r->{"qtyp{$i}"}) ? $r->{"qtyp{$i}"} : 0);
                        $actualSum += (int) (isset($r->{"qty{$i}"}) ? $r->{"qty{$i}"} : 0);
                    }
                    if ($planSum > 0) {
                        $sizeLines[] = "{$sz}: {$planSum}";
                        $totalPlan  += $planSum;
                    }
                    $totalActual += $actualSum;
                }

                $combos[] = [
                    'material'    => isset($rep->material) ? $rep->material : '-',
                    'secsz'       => isset($rep->secsz) ? $rep->secsz : '',
                    'sizeLines'   => $sizeLines,
                    'totalPlan'   => $totalPlan,
                    'totalActual' => $totalActual,
                ];
            }

            usort($combos, function ($a, $b) {
                return [$a['material'], $a['secsz']] <=> [$b['material'], $b['secsz']];
            });

            // Carton fisik ini dianggap "milik halaman ini" (interaktif)
            // kalau ADA SETIDAKNYA SATU baris ship di dalamnya yang popk+
            // part-nya cocok dengan popk+part yang sedang dibuka -- bukan
            // hanya mengecek baris pertama ($rowsInCarton->first()), sebab
            // carton "mixed" bisa memuat lebih dari satu popk sekaligus.
            $isInteractive = true;
            if ($currentPopk !== null) {
                $isInteractive = $rowsInCarton->contains(function ($r) use ($currentPopk, $currentPart) {
                    $rp = isset($r->popk) ? (int) $r->popk : null;
                    $rt = isset($r->part) ? (int) $r->part : null;
                    return $rp === (int) $currentPopk && $rt === (int) $currentPart;
                });
            }

            $rawCartonBlocks[] = [
                'carton'      => $cartonNo,
                'combos'      => $combos,
                'isSimple'    => count($combos) === 1,
                'pcs'         => (int) $rowsInCarton->sum('pcs'),
                'pcsp'        => (int) $rowsInCarton->sum('pcsp'),
                'repRow'      => $rowsInCarton->first(),
                'interactive' => $isInteractive,
            ];
        }

        $simpleBlocks = collect($rawCartonBlocks)
            ->filter(function ($b) {
                return $b['isSimple'];
            })
            ->map(function ($b) {
                $c = $b['combos'][0];
                return [
                    'carton'      => $b['carton'],
                    'combos'      => $b['combos'],
                    'cartonInput' => $this->buildCartonInput(
                        $b['repRow'], $c['totalPlan'], $c['totalActual'], $b['interactive']
                    ),
                ];
            })
            ->values();

        $mixedBlocks = collect($rawCartonBlocks)
            ->filter(function ($b) {
                return !$b['isSimple'];
            })
            ->map(function ($b) {
                return [
                    'carton'      => $b['carton'],
                    'combos'      => $b['combos'],
                    'cartonInput' => $this->buildCartonInput(
                        $b['repRow'], $b['pcsp'], $b['pcs'], $b['interactive']
                    ),
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
                'cartonRows' => $group->map(function ($b) {
                    return $b['cartonInput'];
                })->values()->all(),
            ];
        }

        // ---- 2) Gabungkan carton MIXED yang profilnya identik ----
        $mixedGroups = $mixedBlocks->groupBy(function ($block) {
            $sig = collect($block['combos'])->map(function ($c) {
                return $c['material'] . '|' . $c['secsz'] . '|' . implode(',', $c['sizeLines']) . '|actual:' . $c['totalActual'];
            })->implode('||');

            $allEmpty = collect($block['combos'])->every(function ($c) {
                return empty($c['sizeLines']);
            });
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

            $cartonInputs = $group->map(function ($b) {
                return $b['cartonInput'];
            })->values()->all();
            $ctnCount = $group->count();

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

        /* ================= HEADER ================= */

        $customersList = $poRows->pluck('customer')->filter()->unique()->values();
        $materialsList = $poRows->pluck('material')->filter()->unique()->values();

        // Total CTN = jumlah CARTON FISIK UNIK dalam scope PO+OP ini
        // (bukan SUM(po.ctn) per popk).
        $totalCtn = $groupedByCarton->count();

        $measList = $shipRows->pluck('meas')->filter()->unique()->values();

        return [
            'dt2'                     => $dt2,
            'activeSizes'             => $activeSizes,
            'orderShip'               => $orderShip,
            'orderShipByCombo'        => $orderShipByCombo,
            'nwCells'                 => $nwCells,
            'gwCells'                 => $gwCells,
            'customersList'           => $customersList,
            'materialsList'           => $materialsList,
            'totalCtn'                => $totalCtn,
            'measList'                => $measList,
            'detailPackingSimpleRows' => $detailPackingSimpleRows,
            'detailPackingMixedRows'  => $detailPackingMixedRows,
        ];
    }

    /* =====================================================
     |  DETAIL (halaman detail finished goods)
     |  Mengembalikan null jika po tidak ditemukan.
     ===================================================== */

    public function getFinishedGoodsDetail(int $popk, int $part, Request $request)
    {
        $gab = (string) $request->query('gab', '0');
        $cr  = $request->cr;

        // 1. Timestamp terakhir update
        $dtp = $this->shipRepo->getLatestShipUpdate($popk);

        // 2. Data PO utama
        $dt = $this->shipRepo->findPoByPopk($popk);
        if (!$dt) {
            return null; // controller yang abort(404)
        }

        $customer = $dt->customer;

        $activeSizes = [];
        for ($i = 1; $i <= 40; $i++) {
            $size = isset($dt->{"size{$i}"}) ? $dt->{"size{$i}"} : null;
            if (!empty($size)) {
                $activeSizes[$i] = $size;
            }
        }

        // Agregat breakdown per size dari tabel ship, scoped popk + part:
        //   Planning = SUM(qty{i})                        (total: SUM(pcs))
        //   Ready    = SUM(qty{i}) dengan status >= 6     (total: SUM(pcs))
        //   Inspect  = SUM(qty{i}) dengan fca = 1         (total: SUM(pcs))
        $summary = $this->shipRepo->getBreakdownSummary($popk, $part);
        $details = $this->shipRepo->getShipDetailRows($popk, $part);

        // ================= SIZE ARRAY =================
        $orderQty   = [];
        $readyQty   = [];
        $inspectQty = [];
        $diffQty    = [];

        for ($i = 1; $i <= 40; $i++) {
            $orderQty[$i]   = ($summary && isset($summary->{"ord{$i}"})) ? $summary->{"ord{$i}"} : 0;
            $readyQty[$i]   = ($summary && isset($summary->{"rdy{$i}"})) ? $summary->{"rdy{$i}"} : 0;
            $inspectQty[$i] = ($summary && isset($summary->{"ins{$i}"})) ? $summary->{"ins{$i}"} : 0;
            $diffQty[$i]    = $readyQty[$i] - $orderQty[$i];   // balance = ready - planning (minus = belum ready, inspect tidak dihitung)
        }

        $orderTotal   = ($summary && isset($summary->ord_total)) ? $summary->ord_total : 0;
        $readyTotal   = ($summary && isset($summary->rdy_total)) ? $summary->rdy_total : 0;
        $inspectTotal = ($summary && isset($summary->ins_total)) ? $summary->ins_total : 0;

        // 3. Data PO Grouping (Order Qty per size)
        $dt2 = $this->shipRepo->getPoMif2Summary($popk);

        // Summary CTN (jmlpcs) dari agregat breakdown yang sama:
        //   Plan    = semua baris ship
        //   Aktual  = status >= 6
        //   Inspect = fca = 1
        $tctnp      = ($summary && isset($summary->ctn_plan))    ? $summary->ctn_plan    : 0;
        $tctna      = ($summary && isset($summary->ctn_actual))  ? $summary->ctn_actual  : 0;
        $tctni      = ($summary && isset($summary->ctn_inspect)) ? $summary->ctn_inspect : 0;
        $balanceCtn = $tctna - $tctnp;   // balance CTN = aktual - plan (minus = belum aktual, inspect tidak dihitung)

        $totalBalance = $readyTotal - $orderTotal;   // balance total = ready - planning

        // 4. Hitung kolom size aktif
        $cjml    = count($activeSizes);
        $cheader = $cjml + 1;

        $baseFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'material' => $dt->material,
        ];

        // 5. List Customer (gabung 4/5/6/8)
        $customersList = [];
        if (in_array($dt->gabung, [4, 5, 6, 8])) {
            $customersList = $this->shipRepo->getPoPlainList(
                array_merge($baseFilters, ['gabung' => $dt->gabung]),
                'customer'
            );
        }

        // 6. List Material (gabung 1/2/9/10)
        $materialsList = [];
        if (in_array($dt->gabung, [1, 2, 9, 10])) {
            $materialsList = $this->shipRepo->getPoPlainList([
                'POno'     => $dt->POno,
                'OP'       => $dt->OP,
                'customer' => $customer,
                'gabung'   => $dt->gabung,
            ], 'material');
        }

        // 7. Total CTN
        $totalCtn = $this->calcTotalCtn($dt, $dt2, $customer);

        // 8. MEAS CTN
        $measFilters = array_merge($baseFilters, ['customer' => $customer]);
        $measList    = $this->shipRepo->getMeasList($measFilters);

        // 9. Ship Qty per size (untuk pdf0 - single material)
        $dt3 = $this->shipRepo->getShipQtypPcspTotals($measFilters);

        // 10. N.W list
        $nwList = $this->shipRepo->getMeasureList('nw', $measFilters);

        // 11. G.W list
        $gwList = $this->shipRepo->getMeasureList('gw', $measFilters);

        // 12. Detail Packing per size group (untuk pdf0 -- masih dipakai
        //     print.blade.php / laporan gab lain, TIDAK dipakai lagi oleh
        //     tabel utama di halaman detail).
        $detailPacking  = $this->buildDetailPacking($dt, $customer, $popk, $part);
        $detailShipping = $this->buildDetailShiping($dt, $customer);

        // 12b. Tabel "Detail Finished Goods" di halaman detail SEKARANG
        // menampilkan SEMUA color/secsz dalam PO+OP yang sama (bukan hanya
        // popk+part ini) -- konsisten dengan tampilan print-global.
        // $currentPopk/$currentPart dikirim ke buildGlobalBreakdown supaya
        // tiap badge carton tahu apakah dia milik popk+part halaman ini
        // (interactive: true, bisa di-scan/dipilih untuk bulk action) atau
        // milik popk lain (interactive: false, tampil read-only saja --
        // aksi bulk selalu dieksekusi ke popk+part halaman, jadi carton
        // popk lain TIDAK BOLEH ikut ter-select, kalau tidak salah sasaran).
        $globalBreakdown  = $this->buildGlobalBreakdown($dt->POno, $dt->OP, $popk, $part) ?: [];
        $globalSimpleRows = $globalBreakdown['detailPackingSimpleRows'] ?? [];
        $globalMixedRows  = $globalBreakdown['detailPackingMixedRows'] ?? [];

        // 13. Data spesifik per gab - builder dipanggil sesuai nilai $gab
        $gabData = $this->buildGabData($gab, $dt, $dt2, $dt3, $popk, $part, $customer);

        return [
            'dt'             => $dt,
            'dtp'            => $dtp,
            'dt2'            => $dt2,
            'dt3'            => $dt3,
            'gab'            => $gab,
            'part'           => $part,
            'customer'       => $customer,
            'activeSizes'    => $activeSizes,
            'cjml'           => $cjml,
            'cheader'        => $cheader,
            'summary'        => $summary,
            'orderQty'       => $orderQty,
            'readyQty'       => $readyQty,
            'inspectQty'     => $inspectQty,
            'diffQty'        => $diffQty,
            'orderTotal'     => $orderTotal,
            'readyTotal'     => $readyTotal,
            'inspectTotal'   => $inspectTotal,
            'totalBalance'   => $totalBalance,
            'cr'             => $cr,
            'details'        => $details,
            'detailShipping' => $detailShipping,
            'detailPacking'  => $detailPacking,
            'globalSimpleRows' => $globalSimpleRows,
            'globalMixedRows'   => $globalMixedRows,
            'customersList'  => $customersList,
            'materialsList'  => $materialsList,
            'totalCtn'       => $totalCtn,
            'measList'       => $measList,
            'nwList'         => $nwList,
            'gwList'         => $gwList,
            'tctnp'          => $tctnp,
            'tctna'          => $tctna,
            'tctni'          => $tctni,
            'balanceCtn'     => $balanceCtn,
            'gabData'        => $gabData,
        ];
    }

    /* =====================================================
     |  SCAN NOBAR (scanner / input manual)
     |  Mengubah status baris ship menjadi 6 berdasarkan nobar.
     ===================================================== */

    /**
     * Versi GLOBAL scanNobar: TIDAK dibatasi popk+part, mencari nobar di
     * SELURUH baris ship dalam PO+OP yang sama. Dipakai halaman detail
     * global -- operator bisa scan carton color/customer manapun tanpa
     * perlu pindah halaman per popk.
     */
    public function scanNobarGlobal(string $POno, string $OP, string $nobar): array
    {
        $nobar = trim($nobar);
        if ($nobar === '') {
            return ['success' => false, 'message' => 'Nobar kosong.'];
        }

        $rows = $this->shipRepo->getShipRowsByNobarGlobal($POno, $OP, $nobar);
        if ($rows->isEmpty()) {
            return [
                'success' => false,
                'message' => "Nobar \"{$nobar}\" tidak ditemukan pada PO/OP ini.",
            ];
        }

        $carton = $rows->first()->carton;

        $scannable = $rows->filter(function ($r) {
            return $r->fca === null;
        });
        if ($scannable->isEmpty()) {
            return [
                'success' => false,
                'message' => "Carton {$carton} sudah masuk FCA/inspect, tidak bisa discan.",
                'carton'  => $carton,
            ];
        }

        $updatable = $scannable->filter(function ($r) {
            return $r->status < 6;
        });
        if ($updatable->isEmpty()) {
            $status = $scannable->first()->status;
            return [
                'success' => false,
                'message' => "Carton {$carton} sudah discan (status {$status}).",
                'carton'  => $carton,
            ];
        }

        $updated = $this->shipRepo->updateStatusByNobarGlobal($POno, $OP, $nobar, 6);

        return [
            'success' => true,
            'message' => "Carton {$carton} berhasil discan → status 6.",
            'carton'  => $carton,
            'updated' => $updated,
        ];
    }

    /**
     * Kunci shipment (tombol gembok di grid): semua baris popk+part
     * dinaikkan ke status = 7. Guard server-side: hanya boleh kalau
     * SEMUA carton sudah berstatus >= 6 (kondisi yang sama dengan
     * syarat munculnya tombol di grid) — jadi aman walau endpoint
     * dipanggil manual.
     */
    public function lockShipment(int $popk, int $part): array
    {
        $notReady = $this->shipRepo->countNotReadyRows($popk, $part);
        if ($notReady > 0) {
            return [
                'success' => false,
                'message' => "Tidak bisa dikunci: masih ada {$notReady} carton yang belum ready (status < 6).",
            ];
        }

        $updated = $this->shipRepo->lockShipmentByPopkPart($popk, $part);

        if ($updated < 1) {
            return [
                'success' => false,
                'message' => 'Semua carton sudah berstatus 7 (sudah dikunci sebelumnya).',
            ];
        }

        return [
            'success' => true,
            'message' => "Shipment dikunci: {$updated} baris dinaikkan ke status 7.",
            'updated' => $updated,
        ];
    }

    /**
     * Buka kunci shipment: semua baris popk+part yang berstatus 7
     * dikembalikan ke status = 6. Pembatasan SIAPA yang boleh membuka
     * (guserpk = 34) dicek di controller karena butuh session.
     */
    public function unlockShipment(int $popk, int $part): array
    {
        $updated = $this->shipRepo->unlockShipmentByPopkPart($popk, $part);

        if ($updated < 1) {
            return [
                'success' => false,
                'message' => 'Tidak ada carton berstatus 7 yang bisa dibuka.',
            ];
        }

        return [
            'success' => true,
            'message' => "Kunci dibuka: {$updated} baris dikembalikan ke status 6.",
            'updated' => $updated,
        ];
    }

    /**
     * Aksi massal pada carton terpilih (multi-select di laporan pdf).
     *   'inspect'  -> fca = 1, pinjam = now()   (hanya carton hijau: fca NULL)
     *   'shipment' -> status = 6                (hanya carton hijau: fca NULL, status < 6)
     *   'stuffing' -> kembali = now()           (hanya carton abu-abu: fca = 1)
     */
    public function bulkCartonAction(int $popk, int $part, string $action, array $cartons): array
    {
        $cartons = array_values(array_filter(array_map('trim', $cartons), function ($v) {
            return $v !== '';
        }));
        if (empty($cartons)) {
            return ['success' => false, 'message' => 'Tidak ada carton yang dipilih.'];
        }

        switch ($action) {
            case 'inspect':
                $label   = 'Proses Inspect';
                $updated = $this->shipRepo->markInspectByCartons($popk, $part, $cartons);
                break;
            case 'shipment':
                $label   = 'Proses Shipment';
                $updated = $this->shipRepo->markShipmentByCartons($popk, $part, $cartons);
                break;
            case 'stuffing':
                $label   = 'Kembalikan ke Stuffing';
                $updated = $this->shipRepo->returnToStuffingByCartons($popk, $part, $cartons);
                break;
            default:
                return ['success' => false, 'message' => "Aksi \"{$action}\" tidak dikenal."];
        }

        if ($updated < 1) {
            return [
                'success' => false,
                'message' => "{$label}: tidak ada baris yang memenuhi syarat.",
            ];
        }

        return [
            'success' => true,
            'message' => "{$label}: " . count($cartons) . " carton ({$updated} baris) berhasil diproses.",
            'updated' => $updated,
        ];
    }

    /**
     * Versi GLOBAL bulkCartonAction: scoped POno+OP (bukan popk+part) --
     * dipakai halaman detail global, carton yang dipilih boleh berasal
     * dari color/customer manapun dalam PO+OP yang sama.
     */
    public function bulkCartonActionGlobal(string $POno, string $OP, string $action, array $cartons): array
    {
        $cartons = array_values(array_filter(array_map('trim', $cartons), function ($v) {
            return $v !== '';
        }));
        if (empty($cartons)) {
            return ['success' => false, 'message' => 'Tidak ada carton yang dipilih.'];
        }

        switch ($action) {
            case 'inspect':
                $label   = 'Proses Inspect';
                $updated = $this->shipRepo->markInspectByCartonsGlobal($POno, $OP, $cartons);
                break;
            case 'shipment':
                $label   = 'Proses Shipment';
                $updated = $this->shipRepo->markShipmentByCartonsGlobal($POno, $OP, $cartons);
                break;
            case 'stuffing':
                $label   = 'Kembalikan ke Stuffing';
                $updated = $this->shipRepo->returnToStuffingByCartonsGlobal($POno, $OP, $cartons);
                break;
            default:
                return ['success' => false, 'message' => "Aksi \"{$action}\" tidak dikenal."];
        }

        if ($updated < 1) {
            return [
                'success' => false,
                'message' => "{$label}: tidak ada baris yang memenuhi syarat.",
            ];
        }

        return [
            'success' => true,
            'message' => "{$label}: " . count($cartons) . " carton ({$updated} baris) berhasil diproses.",
            'updated' => $updated,
        ];
    }

    /* =====================================================
     |  Detail shipping (blok lama) - bug return di dalam
     |  loop sudah diperbaiki.
     ===================================================== */

    public function buildDetailShiping($dt, $customer): array
    {
        $build = $this->shipRepo->getDetailShippingData($dt, $customer);

        $result = [];
        foreach ($build['groups'] as $g) {
            $size = null;
            for ($i = 1; $i <= 40; $i++) {
                if ((isset($g->{"qtyp{$i}"}) ? $g->{"qtyp{$i}"} : 0) > 0) {
                    $size = isset($dt->{"size{$i}"}) ? $dt->{"size{$i}"} : null;
                }
            }

            $cartons = $build['allCartons']->get($g->urut . '|' . $g->pcsp, collect());

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

    public function addOrderImageToRows($rows): void
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

    /* =====================================================
     |  GAB DISPATCHER
     ===================================================== */

    private function buildGabData(string $gab, $dt, $dt2, $dt3, $popk, int $part, string $customer): array
    {
        switch ($gab) {
            case '1':      return $this->buildGab1Data($dt, $popk, $part, $customer);
            case '2':      return $this->buildGab2Data($dt, $customer);
            case '3':      return $this->buildGab3Data($dt, $popk, $part, $customer);
            case '4':      return $this->buildGab4Data($dt, $customer);
            case '5':      return $this->buildGab5Data($dt, $customer);
            case '6':      return $this->buildGab6Data($dt, $customer);
            case '7':      return $this->buildGab7Data($dt, $dt2, $dt3, $popk, $part, $customer);
            case '8':      return $this->buildGab8Data($dt, $customer);
            case '9':      return $this->buildGab9Data($dt, $customer);
            case '10':     return $this->buildGab10Data($dt, $customer);
            // 'global': ringkasan lintas SELURUH PO+OP (semua place/color/
            // secsz/part) -- sama isinya dengan halaman print-global mandiri,
            // tapi dirender di DALAM detail/print popk+part yang sedang
            // dibuka (partial laporan.pdf-global), bukan view terpisah.
            // Dipicu lewat query ?gab=global (bukan nilai po.gabung).
            case 'global': return $this->buildGlobalBreakdown($dt->POno, $dt->OP) ?? [];
            default:       return [];
        }
    }

    /* =====================================================
     |  GAB 1
     ===================================================== */

    private function buildGab1Data($dt, $popk, int $part, string $customer): array
    {
        $baseOrderFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'customer' => $customer,
            'gabung'   => '1',
        ];
        $baseShipFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'customer' => $customer,
        ];

        // Breakdown per material
        $materials = $this->shipRepo->getPoGroupedMaterials($baseOrderFilters);

        $materialRows = [];
        foreach ($materials as $mat) {
            $shipAgg = $this->shipRepo->getShipAgg(
                array_merge($baseShipFilters, ['material' => $mat->material])
            );

            $materialRows[] = $this->buildEntityRow($mat, $shipAgg, ['material' => $mat->material]);
        }

        // Grand Total
        $totalOrder = $this->shipRepo->getPoOrderTotals($baseOrderFilters);
        $totalShip  = $this->shipRepo->getShipAgg($baseShipFilters);
        $grandTotal = $this->buildGrandTotalBlock($totalOrder, $totalShip);

        // Detail packing per material (Size/Ratio table)
        $detailMaterials = $this->shipRepo->getDetailMaterialsJoinPo($dt->POno, $dt->OP, $customer, '1');

        $detailMaterialRows = [];
        $tpcsp = 0;
        foreach ($detailMaterials as $row) {
            $vals = [];
            for ($i = 1; $i <= 40; $i++) {
                $v = isset($row->{"qtyp{$i}"}) ? $row->{"qtyp{$i}"} : 0;
                $vals[$i] = $v == 0 ? '' : $v;
            }
            $tpcsp += $row->pcsp;
            $detailMaterialRows[] = [
                'material' => $row->material,
                'qty'      => $vals,
                'pcsp'     => $row->pcsp,
            ];
        }

        // Blok carton bawah: di-scope ke popk+part HALAMAN INI.
        // (Logika lama memakai "popk representatif" — popk dengan pcsp
        // terbesar dalam grup gabungan — sehingga status/warna carton
        // yang tampil bisa milik popk lain, bukan popk yang sedang dibuka.)
        $cartonFilters = ['popk' => $popk, 'part' => $part];

        $cartonList = $this->shipRepo->getShipCartons($cartonFilters, ['urut', 'shippk']);
        $pcsByCarton = $this->shipRepo->getPcsByCarton($cartonFilters);

        $cartonRows = [];
        foreach ($cartonList as $c) {
            $sumPcs = isset($pcsByCarton[$c->carton]) ? $pcsByCarton[$c->carton] : 0;
            $cartonRows[] = $this->buildCartonInput($c, $tpcsp, $sumPcs);
        }

        $ctnCount = $cartonList->pluck('carton')->unique()->count();

        return [
            'materialRows'       => $materialRows,
            'grandTotal'         => $grandTotal,
            'detailMaterialRows' => $detailMaterialRows,
            'tpcsp'              => $tpcsp,
            'ctn'                => $ctnCount,
            'cartonRows'         => $cartonRows,
        ];
    }

    /* =====================================================
     |  GAB 2
     ===================================================== */

    private function buildGab2Data($dt, string $customer): array
    {
        $baseOrderFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'customer' => $customer,
            'gabung'   => '2',
        ];
        $baseShipFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'customer' => $customer,
        ];

        // Daftar material gabung=2
        $materials = $this->shipRepo->getPoRaw(
            $baseOrderFilters,
            'material, qty, ' . $this->shipRepo->sumFields('qty', 40),
            [],
            ['material']
        );

        $materialRows = [];
        $nwByMaterial = [];
        $gwByMaterial = [];

        foreach ($materials as $mat) {
            $shipAgg = $this->shipRepo->getShipAgg(
                array_merge($baseShipFilters, ['material' => $mat->material]),
                'material'
            );

            $materialRows[] = $this->buildEntityRow($mat, $shipAgg, ['material' => $mat->material]);

            // N.W / G.W per material - satu query per material
            list($nwRow, $gwRow) = $this->buildMeasureRowsForMaterial($dt, $customer, $mat->material);
            $nwByMaterial[] = ['material' => $mat->material, 'nw' => $nwRow];
            $gwByMaterial[] = ['material' => $mat->material, 'gw' => $gwRow];
        }

        // Grand Total
        $totalOrder = $this->shipRepo->getPoOrderTotals($baseOrderFilters);
        $totalShip  = $this->shipRepo->getShipAgg($baseShipFilters);
        $grandTotal = $this->buildGrandTotalBlock($totalOrder, $totalShip);

        // Detail packing per material -> per size group
        $detailByMaterial = [];
        foreach ($materials as $mat) {
            $matFilters = array_merge($baseShipFilters, ['material' => $mat->material]);

            $groups = $this->shipRepo->getShipDetailGroups($matFilters, ['urut', 'shippk']);

            // Batch semua carton untuk material ini dalam satu query
            $allCartons = $this->groupCartonsByUrutPcsp(
                $this->shipRepo->getShipCartons($matFilters, ['urut', 'shippk'])
            );

            $sizeGroups = [];
            foreach ($groups as $g) {
                $size = $this->resolveSizeFromQtyp($g, $dt);
                $cartons = $allCartons->get($g->urut . '|' . $g->pcsp, collect());

                $cartonRows = [];
                foreach ($cartons as $c) {
                    $cartonRows[] = $this->buildCartonInput($c, $g->pcsp, $c->pcs);
                }
                $sizeGroups[] = [
                    'size' => $size, 'ctn' => $g->ctn,
                    'pcsp' => $g->pcsp, 'cartonRows' => $cartonRows,
                ];
            }
            $detailByMaterial[] = ['material' => $mat->material, 'groups' => $sizeGroups];
        }

        return [
            'materialRows'     => $materialRows,
            'grandTotal'       => $grandTotal,
            'nwByMaterial'     => $nwByMaterial,
            'gwByMaterial'     => $gwByMaterial,
            'detailByMaterial' => $detailByMaterial,
        ];
    }

    /* =====================================================
     |  GAB 3
     ===================================================== */

    /**
     * gab=3: ratio breakdown untuk SATU popk (bukan gabungan lintas popk),
     * jadi cukup di-scope ke popk + part — tidak perlu filter tambahan
     * POno/OP/customer/material dari tabel po. Filter gabungan itu
     * sebelumnya bisa menghasilkan 0 baris kalau nilai material/customer
     * di ship sedikit berbeda dari po, walau popk+part-nya benar.
     */
    private function buildGab3Data($dt, $popk, int $part, string $customer): array
    {
        $baseFilters = [
            'popk' => $popk,
            'part' => $part,
        ];

        // Ratio table: satu baris per popk (pcsp terbesar)
        $ratioRows = $this->shipRepo->getRatioRowsGroupedByPcsp($baseFilters);

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
                $v = isset($row->{"qtyp{$i}"}) ? $row->{"qtyp{$i}"} : 0;
                $vals[$i] = $v == 0 ? '' : $v;
            }
            $tpcsp += $row->pcsp;
            $ratioData[] = ['label' => $rowLabel, 'qty' => $vals, 'pcsp' => $row->pcsp];
        }

        // Detail carton: per pcsp group, filter by popk + part
        $detailGroups = $this->shipRepo->getCtnGroupsByPcsp($baseFilters);

        // Batch semua carton untuk popk+part ini, digroup per pcsp
        // groupBy via closure (bukan groupBy('pcsp') string) agar aman
        // dari error array_key_exists(): kolom pcsp bisa NULL, atau
        // bertipe float/decimal (tergantung driver PDO) — keduanya
        // ditolak sebagai array key oleh PHP 8+ (harus string/int).
        // Cast eksplisit ke string menghindari semua kasus itu sekaligus.
        $allCartons = $this->shipRepo
            ->getShipCartons($baseFilters, ['urut', 'shippk'])
            ->groupBy(function ($c) {
                return $c->pcsp !== null ? (string) $c->pcsp : '0';
            });

        $pcsByCarton = $this->shipRepo->getPcsByCarton($baseFilters);

        $detailPacking = [];
        foreach ($detailGroups as $g) {
            $pcspKey = $g->pcsp !== null ? (string) $g->pcsp : '0';
            $cartons = $allCartons->get($pcspKey, collect());

            $cartonRows = [];
            foreach ($cartons as $c) {
                $sumPcs = isset($pcsByCarton[$c->carton]) ? $pcsByCarton[$c->carton] : 0;
                $cartonRows[] = $this->buildCartonInput($c, $tpcsp, $sumPcs);
            }

            $detailPacking[] = ['ctn' => $g->ctn, 'pcsp' => $g->pcsp, 'cartonRows' => $cartonRows];
        }

        return [
            'ratioData'     => $ratioData,
            'tpcsp'         => $tpcsp,
            'detailPacking' => $detailPacking,
        ];
    }

    /* =====================================================
     |  GAB 4
     ===================================================== */

    private function buildGab4Data($dt, string $customer): array
    {
        $hasSecsz = !empty($dt->secsz);

        $orderFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'material' => $dt->material,
            'gabung'   => '4',
        ];
        $shipFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'material' => $dt->material,
        ];

        $poList = $hasSecsz
            ? $this->shipRepo->getPoList(array_merge($orderFilters, ['customer' => $customer]), 'secsz')
            : $this->shipRepo->getPoList($orderFilters, 'customer');

        $entityRows = [];
        foreach ($poList as $po) {
            $entityLabel = $hasSecsz ? $po->secsz : $po->customer;

            $shipAgg = $hasSecsz
                ? $this->shipRepo->getShipAgg(
                    array_merge($shipFilters, ['secsz' => $po->secsz, 'customer' => $customer]),
                    'secsz'
                )
                : $this->shipRepo->getShipAgg(
                    array_merge($shipFilters, ['customer' => $po->customer]),
                    'customer'
                );

            $entityRows[] = $this->buildEntityRow($po, $shipAgg, ['label' => $entityLabel]);
        }

        // Grand Total
        $totalOrder = $hasSecsz
            ? $this->shipRepo->getPoOrderTotals(array_merge($orderFilters, ['customer' => $customer]))
            : $this->shipRepo->getPoOrderTotals($orderFilters);
        $totalShip = $hasSecsz
            ? $this->shipRepo->getShipAgg(array_merge($shipFilters, ['customer' => $customer]))
            : $this->shipRepo->getShipAgg($shipFilters);

        $grandTotal = $this->buildGrandTotalBlock($totalOrder, $totalShip);

        // Ratio table (Detail Packing - size/ratio)
        list($ratioRows, $tpcsp) = $this->buildRatioRowsByEntity($dt, $customer, $hasSecsz, $shipFilters);

        // Detail carton per pcsp group (kondisional secsz)
        $ctnGroupFilters = array_merge($shipFilters, ['customer' => $customer]);
        if ($hasSecsz) {
            $ctnGroupFilters['secsz'] = $dt->secsz;
        }
        $ctnGroups = $this->shipRepo->getCtnGroupsByPcsp($ctnGroupFilters);

        $detailPacking = [];
        foreach ($ctnGroups as $g) {
            $pcspTotalFilters = [
                'POno'     => $g->POno,
                'OP'       => $g->OP,
                'material' => $g->material,
                'carton'   => $g->carton,
            ];
            if ($hasSecsz) {
                $pcspTotalFilters['customer'] = $g->customer;
            }
            $pcspTotal = $this->shipRepo->sumShipColumn($pcspTotalFilters, 'pcsp');
            $pcspTotal = $pcspTotal !== null ? $pcspTotal : 0;

            $cartonFilters = [
                'pcsp'     => $g->pcsp,
                'POno'     => $g->POno,
                'OP'       => $g->OP,
                'material' => $g->material,
                'customer' => $g->customer,
            ];
            if ($hasSecsz) {
                $cartonFilters['secsz'] = $g->secsz;
            }
            $cartons = $this->shipRepo->getRows(
                $cartonFilters,
                ['urut', 'shippk'],
                ['carton', 'keterangan', 'status', 'fca']
            );

            // Batch total pcs per carton dalam satu query
            $cartonNames   = $cartons->pluck('carton')->unique()->values()->all();
            $sumPcsFilters = [
                'POno'     => $g->POno,
                'OP'       => $g->OP,
                'material' => $g->material,
            ];
            if ($hasSecsz) {
                $sumPcsFilters['customer'] = $g->customer;
            }
            $pcsByCarton = $this->shipRepo->getPcsByCarton($sumPcsFilters, $cartonNames);

            $cartonRows = [];
            foreach ($cartons as $c) {
                $sumPcs = isset($pcsByCarton[$c->carton]) ? $pcsByCarton[$c->carton] : 0;
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

    /* =====================================================
     |  GAB 5
     ===================================================== */

    private function buildGab5Data($dt, string $customer): array
    {
        $hasSecsz = !empty($dt->secsz);

        $orderFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'material' => $dt->material,
            'gabung'   => '5',
        ];
        $shipFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'material' => $dt->material,
        ];

        $poList = $hasSecsz
            ? $this->shipRepo->getPoList(array_merge($orderFilters, ['customer' => $customer]), 'secsz')
            : $this->shipRepo->getPoList($orderFilters, 'customer');

        $entityRows = [];
        foreach ($poList as $po) {
            $entityLabel = $hasSecsz ? $po->secsz : $po->customer;

            $shipAgg = $hasSecsz
                ? $this->shipRepo->getShipAgg(
                    array_merge($shipFilters, ['secsz' => $po->secsz, 'customer' => $customer]),
                    'secsz'
                )
                : $this->shipRepo->getShipAgg(
                    array_merge($shipFilters, ['customer' => $po->customer]),
                    'customer'
                );

            // N.W / G.W per entity - satu query
            $entityCustomer = $hasSecsz ? $customer : $po->customer;
            $entitySecsz    = $hasSecsz ? $po->secsz : null;
            list($nwRow, $gwRow) = $this->buildMeasureRowsForMaterial(
                $dt, $entityCustomer, $dt->material, $entitySecsz
            );

            $row = $this->buildEntityRow($po, $shipAgg, ['label' => $entityLabel]);
            $row['nw'] = $nwRow;
            $row['gw'] = $gwRow;
            $entityRows[] = $row;
        }

        // Grand Total
        $totalOrder = $hasSecsz
            ? $this->shipRepo->getPoOrderTotals(array_merge($orderFilters, ['customer' => $customer]))
            : $this->shipRepo->getPoOrderTotals($orderFilters);
        $totalShip = $hasSecsz
            ? $this->shipRepo->getShipAgg(array_merge($shipFilters, ['customer' => $customer]))
            : $this->shipRepo->getShipAgg($shipFilters);

        $grandTotal = $this->buildGrandTotalBlock($totalOrder, $totalShip);

        // Ratio table per entity
        list($ratioRows, $tpcsp, $shipGroups) =
            $this->buildRatioRowsByEntity($dt, $customer, $hasSecsz, $shipFilters, true);

        // Detail packing: urut < 21 (normal per entity), urut = 21 (mixed carton)
        $normalDetails = [];
        foreach ($shipGroups as $pg) {
            $entityLabel = $hasSecsz ? $pg->secsz : $pg->customer;

            $groupFilters = [
                'urut'     => ['op' => '<', 'value' => 21],
                'POno'     => $pg->POno,
                'OP'       => $pg->OP,
                'customer' => $pg->customer,
                'material' => $pg->material,
            ];
            if ($hasSecsz) {
                $groupFilters['secsz'] = $pg->secsz;
            }

            $groups = $this->shipRepo->getShipDetailGroups(
                $groupFilters,
                ['urut', 'pcsp desc', 'shippk'],
                false,
                ['secsz']
            );

            // Batch semua carton untuk entitas ini dalam satu query
            $cartonFilters = [
                'POno'     => $pg->POno,
                'OP'       => $pg->OP,
                'customer' => $pg->customer,
                'material' => $pg->material,
            ];
            if ($hasSecsz) {
                $cartonFilters['secsz'] = $pg->secsz;
            }
            $allCartons = $this->groupCartonsByUrutPcsp(
                $this->shipRepo->getShipCartons($cartonFilters, ['urut', 'shippk'])
            );

            $sizeGroups = [];
            foreach ($groups as $g) {
                $size  = $this->resolveSizeFromQtyp($g, $dt);
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
        $mixedFilters = array_merge($shipFilters, ['urut' => 21]);
        if ($hasSecsz) {
            $mixedFilters['customer'] = $customer;
        }
        $mixedCartons = $this->shipRepo->getMixedCartonGroups($mixedFilters, ['urut', 'shippk']);

        // Batch semua sub-baris (per secsz/customer) untuk semua mixed carton sekaligus
        $mixedCartonNames = $mixedCartons->pluck('carton')->unique()->values()->all();
        $subRowFilters    = $shipFilters;
        if ($hasSecsz) {
            $subRowFilters['customer'] = $customer;
        }
        $allSubRows = $this->shipRepo
            ->getMixedSubRowGroups($subRowFilters, $mixedCartonNames, $hasSecsz ? 'secsz' : 'customer')
            ->groupBy('carton');

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
                        if ((isset($sub->{"qtyp{$i}"}) ? $sub->{"qtyp{$i}"} : 0) > 0) {
                            $sizeName = isset($dt->{"size{$i}"}) ? $dt->{"size{$i}"} : '';
                            $labelLines[] = "{$entityLabel} | {$sizeName}";
                            $pcsLines[]   = $sub->{"qtyp{$i}"};
                        }
                    }
                } else {
                    $size = null;
                    $qty  = null;
                    for ($i = 1; $i <= 40; $i++) {
                        if ((isset($sub->{"qtyp{$i}"}) ? $sub->{"qtyp{$i}"} : 0) > 0) {
                            $size = isset($dt->{"size{$i}"}) ? $dt->{"size{$i}"} : null;
                            $qty  = $sub->{"qtyp{$i}"};
                        }
                    }
                    $labelLines[] = "{$entityLabel} | {$size}";
                    $pcsLines[]   = $qty !== null ? $qty : '';
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

    /* =====================================================
     |  GAB 6
     ===================================================== */

    private function buildGab6Data($dt, string $customer): array
    {
        $orderFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'material' => $dt->material,
            'gabung'   => '6',
        ];
        $shipFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'material' => $dt->material,
        ];

        $poList = $this->shipRepo->getPoList($orderFilters, 'customer');

        $entityRows = [];
        foreach ($poList as $po) {
            $shipAgg = $this->shipRepo->getShipAgg(
                array_merge($shipFilters, ['customer' => $po->customer]),
                'customer'
            );

            $entityRows[] = $this->buildEntityRow($po, $shipAgg, ['label' => $po->customer]);
        }

        // Detail packing per customer (urut < 21)
        $custGroups = $this->shipRepo->getPoCustomers($orderFilters);

        $detailByCustomer = [];
        foreach ($custGroups as $cg) {
            $custFilters = array_merge($shipFilters, ['customer' => $cg->customer]);

            $groups = $this->shipRepo->getShipDetailGroups(
                array_merge($custFilters, ['urut' => ['op' => '<', 'value' => 21]]),
                ['urut', 'shippk']
            );

            $allCartons = $this->groupCartonsByUrutPcsp(
                $this->shipRepo->getShipCartons($custFilters, ['urut', 'shippk'])
            );

            $sizeGroups = [];
            foreach ($groups as $g) {
                $size  = $this->resolveSizeFromQtyp($g, $dt);
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

        // Mixed carton (urut = 21) - lintas customer untuk material yang sama
        $mixedCartons = $this->shipRepo->getMixedCartonGroups(
            array_merge($shipFilters, ['urut' => 21]),
            ['urut', 'shippk']
        );

        // Batch semua sub-baris per customer untuk semua mixed carton sekaligus
        $mixedCartonNames = $mixedCartons->pluck('carton')->unique()->values()->all();
        $allSubRows = $this->shipRepo
            ->getMixedSubRowGroups($shipFilters, $mixedCartonNames, 'customer')
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
                    if ((isset($sub->{"qtyp{$i}"}) ? $sub->{"qtyp{$i}"} : 0) > 0) {
                        $size = isset($dt->{"size{$i}"}) ? $dt->{"size{$i}"} : null;
                        $qty  = $sub->{"qtyp{$i}"};
                    }
                }
                $labelLines[] = "{$sub->customer} | {$size}";
                $pcsLines[]   = $qty !== null ? $qty : '';
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

    /* =====================================================
     |  GAB 7
     ===================================================== */

    private function buildGab7Data($dt, $dt2, $dt3, $popk, int $part, string $customer): array
    {
        // 1) Order vs Ship breakdown
        list($order, $ship, $diff, $pct) = $this->calcSizeArrays($dt2, $dt3);
        $orderTotal = ($dt2 && isset($dt2->qty)) ? $dt2->qty : 0;
        $shipTotal  = ($dt3 && isset($dt3->pcs)) ? $dt3->pcs : 0;
        $diffTotal  = $shipTotal - $orderTotal;
        $pctTotal   = !empty($orderTotal) ? round($diffTotal / $orderTotal * 100, 2) : 0;

        $orderShip = [
            'order' => $order, 'ship' => $ship, 'diff' => $diff, 'pct' => $pct,
            'order_total' => $orderTotal, 'ship_total' => $shipTotal,
            'diff_total' => $diffTotal, 'pct_total' => $pctTotal,
        ];

        // 2) N.W / G.W cells
        $nwCells = $this->buildMeasureCells($dt, $customer, 'nw');
        $gwCells = $this->buildMeasureCells($dt, $customer, 'gw');

        // gab=7 = laporan SATU popk; semua query carton di-scope popk + part
        // agar carton part lain (popk sama) tidak ikut tampil / ter-select.
        $pageFilters = ['popk' => $popk, 'part' => $part];

        // 3) Detail packing - normal groups (urut < 21), scoped popk + part
        $groups = $this->shipRepo->getShipDetailGroups(
            array_merge($pageFilters, ['urut' => ['op' => '<', 'value' => 21]]),
            ['shippk', 'urut'],
            false,
            ['part']
        );

        $allCartons = $this->groupCartonsByUrutPcsp(
            $this->shipRepo->getShipCartons($pageFilters, ['shippk', 'urut'])
        );

        $normalGroups = [];
        foreach ($groups as $g) {
            $size  = $this->resolveSizeFromQtyp($g, $dt);
            $pcsp1 = $g->pcsp == 0 ? '' : $g->pcsp;
            $ctn1  = $g->pcsp == 0 ? '' : $g->ctn;

            $cartons = $allCartons->get($g->urut . '|' . $g->pcsp, collect());

            $cartonRows = [];
            foreach ($cartons as $c) {
                $cartonRows[] = $this->buildCartonInput($c, $g->pcsp, $c->pcs);
            }
            $normalGroups[] = ['size' => $size, 'ctn' => $ctn1, 'pcsp' => $pcsp1, 'cartonRows' => $cartonRows];
        }

        // 4) Detail packing - mixed carton (urut = 21), scoped popk + part
        $mixedCartons = $this->shipRepo->getMixedCartonGroups(
            array_merge($pageFilters, ['urut' => 21]),
            ['shippk', 'urut']
        );

        $mixedRows = [];
        foreach ($mixedCartons as $mc) {
            $subRows = $this->shipRepo->getRows(
                array_merge($pageFilters, ['carton' => $mc->carton, 'urut' => 21])
            );

            $labelLines = [];
            $pcsLines   = [];
            foreach ($subRows as $sub) {
                for ($i = 1; $i <= 40; $i++) {
                    $v = isset($sub->{"qtyp{$i}"}) ? $sub->{"qtyp{$i}"} : 0;
                    if ($v > 0) {
                        $labelLines[] = isset($dt->{"size{$i}"}) ? $dt->{"size{$i}"} : '';
                        $pcsLines[]   = $v;
                    }
                }
            }

            $pcsp2 = $this->shipRepo->sumShipColumn(
                array_merge($pageFilters, ['carton' => $mc->carton, 'urut' => 21]),
                'pcsp'
            );

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

    /* =====================================================
     |  GAB 8
     ===================================================== */

    private function buildGab8Data($dt, string $customer): array
    {
        $shipFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'material' => $dt->material,
        ];

        // ---- Part A: packing summary ----------------------------------
        $matTotal = $this->shipRepo->getPoOrderTotals($shipFilters, true);

        $orderRow = [
            'qty'   => [],
            'total' => ($matTotal && isset($matTotal->qty)) ? $matTotal->qty : 0,
        ];
        for ($i = 1; $i <= 40; $i++) {
            $v = ($matTotal && isset($matTotal->{"qty{$i}"})) ? $matTotal->{"qty{$i}"} : 0;
            $orderRow['qty'][$i] = $v == 0 ? '' : $v;
        }

        $custPacks = $this->shipRepo->getShipCustomerGroups($shipFilters);

        // Batch: ambil semua po terkait dalam satu query
        $popks     = $custPacks->pluck('popk')->unique()->values()->all();
        $posByPopk = $this->shipRepo->getPosByPopks($popks);

        $tqtyp  = array_fill(1, 40, 0);
        $tctn2  = 0;
        $tpcsp2 = 0;

        $customerBlocks = [];
        foreach ($custPacks as $cp) {
            $po = $posByPopk->get($cp->popk);
            if (!$po) continue;

            $qtyRow = [];
            for ($i = 1; $i <= 40; $i++) {
                $v = isset($po->{"qty{$i}"}) ? $po->{"qty{$i}"} : 0;
                $qtyRow[$i] = $v == 0 ? '' : $v;
            }

            $custFilters = array_merge($shipFilters, ['customer' => $cp->customer]);

            $groups = $this->shipRepo->getShipDetailGroups(
                $custFilters,
                ['shippk', 'urut'],
                true // dengan meas, nw, gw
            );

            $allCartons = $this->groupCartonsByUrutPcsp(
                $this->shipRepo->getShipCartons($custFilters, ['shippk', 'urut'])
            );

            $cartonGroupRows = [];
            foreach ($groups as $g) {
                $qtyPRow = [];
                for ($i = 1; $i <= 40; $i++) {
                    $v = isset($g->{"qtyp{$i}"}) ? $g->{"qtyp{$i}"} : 0;
                    $qtyPRow[$i] = $v == 0 ? '' : $v;
                    $tqtyp[$i] += $v * $g->ctn;
                }
                $totalPcs = $g->ctn * $g->pcsp;
                $tctn2   += $g->ctn;
                $tpcsp2  += $totalPcs;

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
                'order_total'  => isset($po->qty) ? $po->qty : 0,
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

        // ---- Part B: breakdown Order/Ship/+/-/% per customer + Total ----
        $orderFilters = array_merge($shipFilters, ['gabung' => '8']);
        $poList = $this->shipRepo->getPoList($orderFilters, 'customer');

        $entityRows = [];
        foreach ($poList as $po) {
            $shipAgg = $this->shipRepo->getShipAgg(
                array_merge($shipFilters, ['customer' => $po->customer]),
                'customer'
            );

            $entityRows[] = $this->buildEntityRow($po, $shipAgg, ['label' => $po->customer]);
        }

        $totalOrder = $this->shipRepo->getPoOrderTotals($orderFilters);
        $totalShip  = $this->shipRepo->getShipAgg($shipFilters);
        $grandTotal = $this->buildGrandTotalBlock($totalOrder, $totalShip);

        return [
            'packingSummary' => $packingSummary,
            'entityRows'     => $entityRows,
            'grandTotal'     => $grandTotal,
        ];
    }

    /* =====================================================
     |  GAB 9 & 10
     ===================================================== */

    private function buildGab9Data($dt, string $customer): array
    {
        return $this->buildGab9Or10Data($dt, $customer, '9', true);
    }

    private function buildGab10Data($dt, string $customer): array
    {
        return $this->buildGab9Or10Data($dt, $customer, '10', false);
    }

    private function buildGab9Or10Data($dt, string $customer, string $gabung, bool $byPopk): array
    {
        $orderFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'customer' => $customer,
            'gabung'   => $gabung,
        ];
        $shipFilters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'customer' => $customer,
        ];

        $poList = $this->shipRepo->getPoList($orderFilters, 'material');

        list($materialRows, $nwByMaterial, $gwByMaterial) =
            $this->buildGab9And10MaterialRows($dt, $customer, $poList, $byPopk);

        $totalOrder = $this->shipRepo->getPoOrderTotals($orderFilters);
        $totalShip  = $this->shipRepo->getShipAgg($shipFilters);
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
     * Shared per-material Order/Ship/+/-/% + N.W/G.W builder untuk gab=9/10.
     * $byPopk = true  -> gab=9 (ship terikat popk baris po)
     * $byPopk = false -> gab=10 (ship terikat material+customer)
     */
    private function buildGab9And10MaterialRows($dt, string $customer, $poList, bool $byPopk): array
    {
        $materialRows = [];
        $nwByMaterial = [];
        $gwByMaterial = [];

        foreach ($poList as $po) {
            if ($byPopk) {
                $shipAgg = $this->shipRepo->getShipAgg(['popk' => $po->popk], 'material');
            } else {
                $shipAgg = $this->shipRepo->getShipAgg([
                    'material' => $po->material,
                    'POno'     => $dt->POno,
                    'OP'       => $dt->OP,
                    'customer' => $customer,
                ], 'material');
            }

            $materialRows[] = $this->buildEntityRow($po, $shipAgg, ['material' => $po->material]);

            list($nwRow, $gwRow) = $this->buildMeasureRowsForMaterial($dt, $customer, $po->material);
            $nwByMaterial[] = ['material' => $po->material, 'nw' => $nwRow];
            $gwByMaterial[] = ['material' => $po->material, 'gw' => $gwRow];
        }

        return [$materialRows, $nwByMaterial, $gwByMaterial];
    }

    /**
     * Detail packing per material untuk satu customer: grup size normal
     * (urut < 21) + baris mixed carton (urut = 21). Dipakai gab=9 & gab=10.
     */
    private function buildMaterialDetailPacking($dt, string $customer, array $materials): array
    {
        $detailByMaterial = [];

        foreach ($materials as $material) {
            $matFilters = [
                'POno'     => $dt->POno,
                'OP'       => $dt->OP,
                'material' => $material,
                'customer' => $customer,
            ];

            $groups = $this->shipRepo->getShipDetailGroups(
                array_merge($matFilters, ['urut' => ['op' => '<', 'value' => 21]]),
                ['shippk']
            );

            $allCartons = $this->groupCartonsByUrutPcsp(
                $this->shipRepo->getShipCartons($matFilters, ['shippk'])
            );

            $sizeGroups = [];
            foreach ($groups as $g) {
                $size  = $this->resolveSizeFromQtyp($g, $dt);
                $pcsp1 = $g->pcsp == 0 ? '' : $g->pcsp;
                $ctn1  = $g->pcsp == 0 ? '' : $g->ctn;

                $cartons = $allCartons->get($g->urut . '|' . $g->pcsp, collect());

                $cartonRows = [];
                foreach ($cartons as $c) {
                    $cartonRows[] = $this->buildCartonInput($c, $g->pcsp, $c->pcs);
                }
                $sizeGroups[] = ['size' => $size, 'ctn' => $ctn1, 'pcsp' => $pcsp1, 'cartonRows' => $cartonRows];
            }

            // Mixed carton (urut = 21): satu query per material, group per carton
            // di PHP, ambil baris pertama per carton.
            $mixedRowsRaw = $this->shipRepo->getRows(
                array_merge($matFilters, ['urut' => 21]),
                ['shippk']
            );

            $mixedRows = [];
            foreach ($mixedRowsRaw->groupBy('carton') as $rowsForCarton) {
                $row = $rowsForCarton->first();

                $labelLines = [];
                $pcsLines   = [];
                for ($i = 1; $i <= 40; $i++) {
                    $v = isset($row->{"qtyp{$i}"}) ? $row->{"qtyp{$i}"} : 0;
                    if ($v > 0) {
                        $labelLines[] = isset($dt->{"size{$i}"}) ? $dt->{"size{$i}"} : '';
                        $pcsLines[]   = $v;
                    }
                }

                $cartonInput = $this->buildCartonInput($row, $row->pcsp, $row->pcs);
                $mixedRows[] = [
                    'labelLines'  => $labelLines,
                    'pcsLines'    => $pcsLines,
                    'pcsp'        => $row->pcsp,
                    'cartonInput' => $cartonInput,
                ];
            }

            $detailByMaterial[] = ['material' => $material, 'groups' => $sizeGroups, 'mixedRows' => $mixedRows];
        }

        return $detailByMaterial;
    }

    /**
     * Blok "mixed carton" lintas material (urut = 22): satu carton berisi
     * beberapa material untuk customer yang sama. Dipakai gab=9 & gab=10.
     */
    private function buildCrossMaterialMixed($dt, string $customer): array
    {
        $allRows = $this->shipRepo->getRows([
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'customer' => $customer,
            'urut'     => 22,
        ], ['carton', 'material']);

        $rows = [];
        foreach ($allRows->groupBy('carton') as $materialRowsRaw) {
            if ($materialRowsRaw->isEmpty()) continue;

            $materialBlocks = [];
            $pcspTotal = 0;
            foreach ($materialRowsRaw as $mr) {
                $labelLines = [];
                $pcsLines   = [];
                for ($i = 1; $i <= 40; $i++) {
                    $v = isset($mr->{"qtyp{$i}"}) ? $mr->{"qtyp{$i}"} : 0;
                    if ($v > 0) {
                        $labelLines[] = isset($dt->{"size{$i}"}) ? $dt->{"size{$i}"} : '';
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

    /* =====================================================
     |  MEASURE (N.W / G.W)
     ===================================================== */

    /**
     * N.W/G.W per kolom size untuk satu material dalam satu query.
     * Untuk tiap size aktif i, ambil nw/gw baris ship pertama (by shippk)
     * yang qtyp{i} > 0.
     */
    private function buildMeasureRowsForMaterial($dt, $customer, $material, $secsz = null): array
    {
        $filters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'material' => $material,   // null -> whereNull di repo
            'customer' => $customer,   // null -> whereNull di repo
        ];
        if ($secsz !== null) {
            $filters['secsz'] = $secsz;
        }

        $rows = $this->shipRepo->getMeasureRows($filters);

        $nwRow    = array_fill(1, 40, '');
        $gwRow    = array_fill(1, 40, '');
        $assigned = array_fill(1, 40, false);

        foreach ($rows as $r) {
            for ($i = 1; $i <= 40; $i++) {
                if (!$assigned[$i] && (isset($r->{"qtyp{$i}"}) ? $r->{"qtyp{$i}"} : 0) > 0) {
                    $nwRow[$i]    = $r->nw;
                    $gwRow[$i]    = $r->gw;
                    $assigned[$i] = true;
                }
            }
        }

        return [$nwRow, $gwRow];
    }

    /**
     * Pre-rendered N.W / G.W table-cell HTML untuk gab=7.
     * $field = 'nw' atau 'gw'. Label size selalu dari $dt.
     */
    private function buildMeasureCells($dt, string $customer, string $field): array
    {
        $filters = [
            'POno'     => $dt->POno,
            'OP'       => $dt->OP,
            'material' => $dt->material,
            'customer' => $customer,
        ];

        $maxUrut = $this->shipRepo->getMeasureMaxUrut($field, $filters);

        if ($maxUrut != 21) {
            $rows = $this->shipRepo->getMeasureValues($field, $filters);

            $cells = [];
            foreach ($rows as $r) {
                $cells[] = (string) $r->{$field};
            }
            return $cells;
        }

        // Mixed mode: perlu qty1..qty40 (kolom size-qty milik ship) + urut/value
        $rows = $this->shipRepo->getMeasureMixedRows($field, $filters);

        $cells = [];
        foreach ($rows as $r) {
            $labels = [];
            for ($i = 1; $i <= 40; $i++) {
                if ((isset($r->{"qty{$i}"}) ? $r->{"qty{$i}"} : 0) > 0) {
                    $labels[] = isset($dt->{"size{$i}"}) ? $dt->{"size{$i}"} : '';
                }
            }
            if ($r->urut == 21) {
                $html = '';
                foreach ($labels as $l) {
                    $html .= "<b>{$l}</b>/";
                }
                $html .= "<hr> {$r->{$field}}";
            } else {
                $html = '';
                foreach ($labels as $l) {
                    $html .= "<b>{$l}</b><hr>";
                }
                $html .= (string) $r->{$field};
            }
            $cells[] = $html;
        }
        return $cells;
    }

    /* =====================================================
     |  TOTAL CTN
     ===================================================== */

    private function calcTotalCtn($dt, $dt2, string $customer)
    {
        $base = $dt2 ? $dt2->ctn : 0;

        switch ((int) $dt->gabung) {
            case 1:
                return $this->shipRepo->getPoLatestCtnByPopk([
                    'POno' => $dt->POno, 'OP' => $dt->OP,
                    'customer' => $customer, 'gabung' => '1',
                ]);
            case 2:
            case 9:
            case 10:
                return $this->shipRepo->getPoSumCtnByMaterial([
                    'POno' => $dt->POno, 'OP' => $dt->OP,
                    'customer' => $customer, 'gabung' => $dt->gabung,
                ]);
            case 4:
                return $this->shipRepo->getPoLatestCtnByPopk([
                    'POno' => $dt->POno, 'OP' => $dt->OP,
                    'material' => $dt->material, 'gabung' => '4',
                ]);
            case 6:
                return $this->shipRepo->getShipLatestCarton([
                    'POno' => $dt->POno, 'OP' => $dt->OP,
                    'material' => $dt->material,
                ]);
            case 5:
            case 8:
                return $this->shipRepo->getPoCtnValueByMaterial([
                    'POno' => $dt->POno, 'OP' => $dt->OP,
                    'material' => $dt->material, 'gabung' => $dt->gabung,
                ]);
            default:
                return $base;
        }
    }

    /* =====================================================
     |  Helper umum
     ===================================================== */

    /**
     * Detail Finished Goods (pdf0): grup size + carton.
     * Di-scope HANYA ke popk + part — keduanya sudah cukup unik untuk
     * mengidentifikasi baris ship halaman ini. Filter tambahan
     * POno/OP/material/customer (dari tabel po) sengaja TIDAK dipakai
     * lagi: kalau nilai material/customer di baris ship sedikit berbeda
     * dari po (spasi, huruf besar-kecil, suffix tambahan, dsb), filter
     * gabungan itu bisa menghasilkan 0 baris walau popk+part-nya benar
     * dan datanya sebenarnya ada.
     */
    private function buildDetailPacking($dt, string $customer, int $popk, int $part): array
    {
        $filters = [
            'popk' => $popk,
            'part' => $part,
        ];

        $groups = $this->shipRepo->getShipDetailGroups($filters, ['urut', 'shippk']);

        $allCartons = $this->groupCartonsByUrutPcsp(
            $this->shipRepo->getShipCartons($filters, ['urut', 'shippk'])
        );

        $result = [];
        foreach ($groups as $g) {
            $size = $this->resolveSizeFromQtyp($g, $dt);

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

    /**
     * Ratio table per entity (secsz/customer) untuk gab=4/5.
     * $returnGroups = true juga mengembalikan $shipGroups (dipakai gab=5).
     */
    private function buildRatioRowsByEntity($dt, string $customer, bool $hasSecsz, array $shipFilters, bool $returnGroups = false): array
    {
        $groupFilters = $hasSecsz
            ? array_merge($shipFilters, ['customer' => $customer])
            : $shipFilters;

        $shipGroups = $this->shipRepo->getEntityGroups(
            $groupFilters,
            $hasSecsz ? 'secsz' : 'customer'
        );

        $ratioRows = [];
        $tpcsp     = 0;
        foreach ($shipGroups as $pg) {
            $entityLabel = $hasSecsz ? $pg->secsz : $pg->customer;

            $rowFilters = [
                'POno'     => $pg->POno,
                'OP'       => $pg->OP,
                'material' => $pg->material,
                'customer' => $pg->customer,
            ];
            if ($hasSecsz) {
                $rowFilters['secsz'] = $pg->secsz;
            }
            $shipRow = $this->shipRepo->getTopRowByPcsp($rowFilters);

            if (!$shipRow) continue;

            $vals = [];
            for ($i = 1; $i <= 40; $i++) {
                $v = isset($shipRow->{"qtyp{$i}"}) ? $shipRow->{"qtyp{$i}"} : 0;
                $vals[$i] = $v == 0 ? '' : $v;
            }
            $tpcsp += $shipRow->pcsp;
            $ratioRows[] = ['label' => $entityLabel, 'qty' => $vals, 'pcsp' => $shipRow->pcsp];
        }

        return $returnGroups
            ? [$ratioRows, $tpcsp, $shipGroups]
            : [$ratioRows, $tpcsp];
    }

    /** Baris Order/Ship/+/-/% standar untuk satu entitas (material/customer/secsz). */
    private function buildEntityRow($orderRow, $shipAgg, array $identity): array
    {
        list($order, $ship, $diff, $pct) = $this->calcSizeArrays($orderRow, $shipAgg);

        $orderQty = isset($orderRow->qty) ? $orderRow->qty : 0;
        $pcsTotal = ($shipAgg && isset($shipAgg->pcs)) ? $shipAgg->pcs : 0;
        $pcsDiff  = $pcsTotal - $orderQty;
        $ppcs     = !empty($orderQty) ? round($pcsDiff / $orderQty * 100, 2) : 0;

        return array_merge($identity, [
            'order'       => $order,
            'ship'        => $ship,
            'diff'        => $diff,
            'pct'         => $pct,
            'order_total' => $orderRow->qty,
            'ship_total'  => $pcsTotal,
            'diff_total'  => $pcsDiff,
            'pct_total'   => $ppcs,
        ]);
    }

    private function buildGrandTotalBlock($totalOrder, $totalShip): array
    {
        list($grandOrder, $grandShip, $grandDiff, $grandPct) = $this->calcSizeArrays($totalOrder, $totalShip);

        $orderQty      = ($totalOrder && isset($totalOrder->qty)) ? $totalOrder->qty : 0;
        $grandPcsTotal = ($totalShip && isset($totalShip->pcs)) ? $totalShip->pcs : 0;
        $grandPcsDiff  = $grandPcsTotal - $orderQty;
        $grandPpcs     = !empty($orderQty) ? round($grandPcsDiff / $orderQty * 100, 2) : 0;

        return [
            'order' => $grandOrder, 'ship' => $grandShip,
            'diff' => $grandDiff, 'pct' => $grandPct,
            'order_total' => $orderQty, 'ship_total' => $grandPcsTotal,
            'diff_total' => $grandPcsDiff, 'pct_total' => $grandPpcs,
        ];
    }

    private function calcSizeArrays($orderRow, $shipRow): array
    {
        $order = $ship = $diff = $pct = [];
        for ($i = 1; $i <= 40; $i++) {
            $a = ($orderRow && isset($orderRow->{"qty{$i}"})) ? $orderRow->{"qty{$i}"} : 0;
            $b = ($shipRow && isset($shipRow->{"qty{$i}"})) ? $shipRow->{"qty{$i}"} : 0;

            $order[$i] = $a == 0 ? '' : $a;
            $ship[$i]  = $b == 0 ? '' : $b;

            $d = $b - $a;
            $diff[$i] = $d != 0 ? $d : '';
            $pct[$i]  = !empty($a) ? round($d / $a * 100, 2) : '';
        }
        return [$order, $ship, $diff, $pct];
    }

    /**
     * Aturan warna badge carton:
     *   fca == 1     -> abu-abu (group 'gray')  : menu Kembalikan ke Stuffing
     *   status == 5  -> hijau   (group 'green') : menu Proses Inspect & Proses Shipment
     *   status >= 6  -> biru    (group 'blue')  : tidak bisa dipilih
     *   lainnya      -> tanpa warna (group 'none'), tidak bisa dipilih
     *
     * $interactive (default true): dipakai saat tabel carton menampilkan
     * SEMUA color dalam satu PO+OP (lintas popk) tapi hanya carton milik
     * popk+part halaman yang sedang dibuka yang boleh di-scan/dipilih
     * untuk bulk action (aksi selalu dieksekusi ke popk+part halaman;
     * carton popk lain kalau ikut selectable akan salah sasaran).
     * PENTING: caller yang menghitung nilai ini (buildGlobalBreakdown)
     * WAJIB memeriksa SELURUH baris ship dalam satu carton fisik --
     * bukan cuma satu baris representatif ($c/$repRow) -- karena carton
     * "mixed" bisa berisi lebih dari satu popk sekaligus; kalau hanya
     * baris pertama yang dicek, carton yang sebenarnya juga berisi
     * baris milik popk halaman ini bisa keliru ditandai non-interaktif.
     * Semua pemanggil LAIN (gab1-10, buildDetailPacking, dsb.) tidak
     * mengirim parameter ini sehingga tetap 'interactive' => true seperti
     * perilaku sebelumnya -- perubahan ini backward-compatible.
     */
    private function buildCartonInput($c, $refPcsp, $tqty, bool $interactive = true): array
    {
        $label = !empty($c->keterangan)
            ? "{$c->carton} ({$c->keterangan})"
            : $c->carton;
        $width = !empty($c->keterangan) ? '15%' : '7%';

        $fca    = isset($c->fca) ? $c->fca : 0;
        $status = isset($c->status) ? $c->status : null;

        $style = "width:{$width};";
        $group = 'none';
        if ($fca == 1) {
            $style = "width:{$width}; background-color:#9E9E9E; color:#000;";   // abu-abu
            $group = 'gray';
        } elseif ($status !== null && $status == 5) {
            $style = "width:{$width}; background-color:#8FBC8F; color:#000;";   // hijau
            $group = 'green';
        } elseif ($status >= 6) {
            $style = "width:{$width}; background-color:#87CEEB; color:#000;";   // biru
            $group = 'blue';
        }

        return [
            'label'       => $label,
            'style'       => $style,
            'carton'      => $c->carton,
            'group'       => $group,
            'interactive' => $interactive,
        ];
    }

    /**
     * Label size untuk satu grup carton.
     * Carton solid satu size -> label size itu (mis. "XL-18/20").
     * Carton ratio/mix       -> semua size yang terisi digabung
     *                           (mis. "S-8 / M-10/12 / L-14/16 / XL-18/20").
     * (Sebelumnya hanya mengambil size TERAKHIR yang qtyp-nya > 0, sehingga
     * carton campuran keliru tampil sebagai satu size saja.)
     */
    private function resolveSizeFromQtyp($row, $dt)
    {
        $labels = [];
        for ($i = 1; $i <= 40; $i++) {
            if ((isset($row->{"qtyp{$i}"}) ? $row->{"qtyp{$i}"} : 0) > 0) {
                $label = isset($dt->{"size{$i}"}) ? $dt->{"size{$i}"} : null;
                if ($label !== null && $label !== '') {
                    $labels[] = $label;
                }
            }
        }

        if (empty($labels)) {
            return null;
        }

        return implode(' / ', $labels);
    }

    /** Group koleksi carton berdasar kunci "urut|pcsp". */
    private function groupCartonsByUrutPcsp($cartons)
    {
        return $cartons->groupBy(function ($c) {
            return $c->urut . '|' . $c->pcsp;
        });
    }
}