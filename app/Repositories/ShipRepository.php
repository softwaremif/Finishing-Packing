<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

/**
 * Repository: seluruh akses data tabel `po` dan `ship`.
 *
 * Catatan:
 * - Semua query yang dulu memakai tabel `pack` sudah diganti ke tabel `ship`
 *   (termasuk primary key `packpk` -> `shippk`).
 * - Kompatibel PHP 7 (tanpa arrow function & typed property).
 */
class ShipRepository
{
    /* =====================================================
     |  Koneksi database dinamis
     ===================================================== */

    private function conn()
    {
        $connection = session('pos') == 2
            ? 'mysql'
            : 'mysql_andon';

        return DB::connection($connection);
    }

    /* =====================================================
     |  Helper fragmen SQL
     ===================================================== */

    public function sumFields($prefix, $max = 40)
    {
        $parts = [];
        for ($i = 1; $i <= $max; $i++) {
            $parts[] = "sum({$prefix}{$i}) as {$prefix}{$i}";
        }
        return implode(', ', $parts);
    }

    public function sumQtypAsQty()
    {
        $parts = [];
        for ($i = 1; $i <= 40; $i++) {
            $parts[] = "sum(qtyp{$i}) as qty{$i}";
        }
        return implode(', ', $parts);
    }

    public function sumPrefixQtyp($table)
    {
        $parts = [];
        for ($i = 1; $i <= 40; $i++) {
            $parts[] = "sum({$table}.qtyp{$i}) as qtyp{$i}";
        }
        return implode(', ', $parts);
    }

    public function qtypColumns()
    {
        $parts = [];
        for ($i = 1; $i <= 40; $i++) {
            $parts[] = "qtyp{$i}";
        }
        return implode(',', $parts);
    }

    public function qtyColumns()
    {
        $parts = [];
        for ($i = 1; $i <= 40; $i++) {
            $parts[] = "qty{$i}";
        }
        return implode(',', $parts);
    }

    private function applyFilters($query, array $filters)
    {
        foreach ($filters as $col => $val) {
            if ($val === null) {
                $query->whereNull($col);
            } elseif (is_array($val)) {
                if (array_key_exists('in', $val)) {
                    $query->whereIn($col, $val['in']);
                } else {
                    $query->where($col, $val['op'], $val['value']);
                }
            } else {
                $query->where($col, $val);
            }
        }
        return $query;
    }

    private function applyOrderBys($query, array $orderBys)
    {
        foreach ($orderBys as $order) {
            $parts = explode(' ', trim($order));
            $dir   = isset($parts[1]) ? $parts[1] : 'asc';
            $query->orderBy($parts[0], $dir);
        }
        return $query;
    }

    /* =====================================================
     |  LIST (grid finished goods / stuffing)
     ===================================================== */

    /**
     * GROUPING POno + OP + poref (License PO Ref):
     * - $ship di-derive dulu lewat join ke po (via popk) supaya setiap
     *   baris ship punya nilai poref (kolom itu asli milik po, ship tidak
     *   punya poref sendiri) -- baru diagregasi per POno+OP+poref.
     * - $po diagregasi dengan kunci yang SAMA (POno+OP+poref); kolom
     *   multi-nilai (customer/place, material/color, secsz) digabung
     *   GROUP_CONCAT karena satu poref bisa mencakup banyak place/color.
     * - Join outer memakai TRIM(...) di kedua sisi supaya kebal spasi,
     *   dan COALESCE(...,'') untuk poref supaya PO tanpa poref (NULL)
     *   tetap match (NULL = NULL selalu false di SQL).
     */
    // public function getShipList($offset, $rows, array $params)
    // {
    //     $db = $this->conn();

    //     $ship = $db->table('ship')
    //         ->join('po as po_ref', 'po_ref.popk', '=', 'ship.popk')
    //         ->selectRaw("
    //             TRIM(ship.POno) AS POno,
    //             TRIM(ship.OP) AS OP,
    //             COALESCE(TRIM(po_ref.poref), '') AS poref,

    //             MAX(ship.popk) AS popk,
    //             MIN(ship.part) AS part,

    //             SUM(CASE WHEN ship.status <= 7 THEN ship.pcs ELSE 0 END) AS pcs_stuff,
    //             SUM(CASE WHEN ship.fca = 1 THEN ship.pcs ELSE 0 END) AS pcs_inspect,
    //             SUM(CASE WHEN ship.status = 7 THEN ship.pcs ELSE 0 END) AS pcs_ship,

    //             SUM(CASE WHEN ship.status <= 7 THEN ship.jmlpcs ELSE 0 END) AS ctn_stuff,
    //             SUM(CASE WHEN ship.fca = 1 THEN ship.jmlpcs ELSE 0 END) AS ctn_inspect,
    //             SUM(CASE WHEN ship.status = 7 THEN ship.jmlpcs ELSE 0 END) AS ctn_ship,

    //             MAX(ship.pinjam) AS pinjam,
    //             MAX(ship.kembali) AS kembali,

    //             MIN(ship.status) AS min_status,

    //             COUNT(*) AS total_carton,

    //             SUM(
    //                 CASE
    //                     WHEN ship.pinjam IS NOT NULL
    //                     AND ship.pinjam <> ''
    //                     AND ship.pinjam <> '0000-00-00 00:00:00'
    //                     THEN 1
    //                     ELSE 0
    //                 END
    //             ) AS pinjam_count,

    //             SUM(CASE WHEN ship.fca = 1 THEN 1 ELSE 0 END) AS borrowed_count
    //         ")
    //         ->groupByRaw("TRIM(ship.POno), TRIM(ship.OP), COALESCE(TRIM(po_ref.poref), '')");

    //     $poShipCols = [];
    //     for ($i = 1; $i <= 10; $i++) {
    //         $poShipCols[] = "MAX(ship{$i}) AS ship{$i}";
    //     }

    //     $po = $db->table('po')
    //         ->selectRaw("
    //             TRIM(POno) AS POno,
    //             TRIM(OP) AS OP,
    //             COALESCE(TRIM(poref), '') AS poref,
    //             MAX(popk) AS popk,
    //             MAX(buyer) AS buyer,
    //             MAX(season) AS season,
    //             MAX(style) AS style,
    //             MAX(silhouette) AS silhouette,
    //             MAX(mif) AS mif,
    //             MAX(gabung) AS gabung,
    //             MAX(GAC) AS GAC,
    //             MAX(qty) AS qty,
    //             MAX(ordpk) AS ordpk,
    //             GROUP_CONCAT(DISTINCT customer SEPARATOR ', ') AS customer,
    //             GROUP_CONCAT(DISTINCT material SEPARATOR ', ') AS material,
    //             GROUP_CONCAT(DISTINCT secsz SEPARATOR ', ') AS secsz,
    //             " . implode(', ', $poShipCols) . "
    //         ")
    //         ->groupByRaw("TRIM(POno), TRIM(OP), COALESCE(TRIM(poref), '')");

    //     $shipdateCase = 'CASE ship.part';
    //     for ($i = 1; $i <= 10; $i++) {
    //         $shipdateCase .= " WHEN {$i} THEN po.ship{$i}";
    //     }
    //     $shipdateCase .= ' ELSE NULL END AS shipdate';

    //     $query = $db->query()
    //         ->fromSub($po, 'po')
    //         ->joinSub($ship, 'ship', function ($join) {
    //             $join->on('po.POno', '=', 'ship.POno')
    //                 ->on('po.OP', '=', 'ship.OP')
    //                 ->on('po.poref', '=', 'ship.poref');
    //         })
    //         ->selectRaw("
    //             ship.popk,
    //             ship.part,

    //             po.POno,
    //             po.OP,
    //             po.customer,
    //             po.material,
    //             po.secsz,
    //             po.poref,

    //             po.buyer,
    //             po.season,
    //             po.style,
    //             po.silhouette,
    //             po.mif,
    //             po.gabung,
    //             po.GAC,
    //             po.qty,
    //             po.ordpk,

    //             {$shipdateCase},

    //             ship.pcs_stuff,
    //             ship.pcs_inspect,
    //             ship.pcs_ship,

    //             ship.ctn_stuff,
    //             ship.ctn_inspect,
    //             ship.ctn_ship,

    //             ship.pinjam,
    //             ship.kembali,

    //             ship.min_status,

    //             ship.total_carton,
    //             ship.pinjam_count,
    //             ship.borrowed_count,

    //             (ship.pinjam_count - ship.borrowed_count) AS returned_count,

    //             CASE
    //                 WHEN ship.pinjam_count > 0
    //                     AND ship.borrowed_count = 0
    //                     THEN 'complete'

    //                 WHEN ship.pinjam_count > 0
    //                     THEN 'partial'

    //                 ELSE ''
    //             END AS inspect_status,

    //             (
    //                 ship.pcs_stuff
    //                 - ship.pcs_inspect
    //                 - ship.pcs_ship
    //             ) AS balance_pcs,

    //             (
    //                 ship.ctn_stuff
    //                 - ship.ctn_inspect
    //                 - ship.ctn_ship
    //             ) AS balance_ctn
    //         ");

    //     if (!empty($params['search'])) {
    //         $search = trim($params['search']);

    //         $query->where(function ($q) use ($search) {
    //             $q->where('po.POno', 'like', "%{$search}%")
    //                 ->orWhere('po.OP', 'like', "%{$search}%")
    //                 ->orWhere('po.customer', 'like', "%{$search}%")
    //                 ->orWhere('po.season', 'like', "%{$search}%")
    //                 ->orWhere('po.style', 'like', "%{$search}%");
    //         });
    //     }

    //     if (!empty($params['buyer'])) {
    //         $query->where('po.buyer', $params['buyer']);
    //     }

    //     if (!empty($params['year'])) {
    //         $year      = (int) $params['year'];
    //         $shortYear = $year - 2000;

    //         $query->whereRaw(
    //             'LEFT(TRIM(po.OP), 2) = ?',
    //             [sprintf('%02d', $shortYear)]
    //         );
    //     }

    //     if (!empty($params['only_pinjam'])) {
    //         $query->where('ship.pinjam_count', '>', 0);
    //     }

    //     if (!empty($params['status'])) {
    //         switch ($params['status']) {
    //             case 'finished':
    //                 $query->where('ship.min_status', '<', 7);
    //                 break;
    //             case 'inspect':
    //                 $query->where('ship.borrowed_count', '>', 0);
    //                 break;
    //             case 'shipment':
    //                 $query->where('ship.min_status', '>=', 7);
    //                 break;
    //         }
    //     }

    //     // BARU: filter Ex Factory (po.GAC) berdasarkan preset rentang tanggal,
    //     // SAMA PERSIS logic dengan PackingController.
    //     if (!empty($params['ex_factory'])) {
    //         $range = $this->resolveExFactoryRange($params['ex_factory']);
    //         if ($range) {
    //             $query->whereBetween('po.GAC', [
    //                 $range['start']->format('Y-m-d 00:00:00'),
    //                 $range['end']->format('Y-m-d 23:59:59'),
    //             ]);
    //         }
    //     }

    //     // $total = $db->query()
    //     //     ->fromSub(clone $query, 'x')
    //     //     ->count();

    //     // $sortDir = (isset($params['sort']) && strtolower((string) $params['sort']) === 'asc')
    //     //     ? 'asc'
    //     //     : 'desc';

    //     // $data = $query
    //     //     // ->orderBy('ship.popk', $sortDir)
    //     //     // ->orderBy('po.OP')
    //     //     ->offset($offset)
    //     //     ->limit($rows)
    //     //     ->get();

    //     // // BARU: normalisasi GAC jadi timestamp lalu sort di collection,
    //     // // SAMA PERSIS pola dengan PackingController::getList().
    //     // foreach ($data as $r) {
    //     //     $r->gac_sort_ts = $this->normalizeGacForSort($r->GAC);
    //     // }

    //     // $data = $sortDir === 'asc'
    //     //     ? $data->sortBy('gac_sort_ts')->values()
    //     //     : $data->sortByDesc('gac_sort_ts')->values();

    //     // return [
    //     //     'total' => $total,
    //     //     'data'  => $data,
    //     // ];
    //     $total = $db->query()
    //         ->fromSub(clone $query, 'x')
    //         ->count();

    //     $sortDir = (isset($params['sort']) && strtolower((string) $params['sort']) === 'asc')
    //         ? 'asc'
    //         : 'desc';

    //     // Ambil SEMUA row (tanpa offset/limit dulu) supaya sort GAC valid
    //     // untuk seluruh dataset, SAMA pola dengan PackingController::getList().
    //     $allData = $query->get();

    //     foreach ($allData as $r) {
    //         $r->gac_sort_ts = $this->normalizeGacForSort($r->GAC);
    //     }

    //     $sorted = $sortDir === 'asc'
    //         ? $allData->sortBy('gac_sort_ts')->values()
    //         : $allData->sortByDesc('gac_sort_ts')->values();

    //     // BARU potong per halaman SETELAH full-sort.
    //     $data = $sorted->slice($offset, $rows)->values();

    //     return [
    //         'total' => $total,
    //         'data'  => $data,
    //     ];
    // }
    public function getShipList($offset, $rows, array $params)
    {
        $db = $this->conn();

        // ============================================================
        // BARU -- FIX UTAMA: level agregasi TAMBAHAN, per NOMOR CARTON
        // FISIK (bukan per baris ship/popk). Satu nomor carton bisa
        // muncul di BEBERAPA baris ship (mixed carton, beda popk) --
        // level ini menggabungkannya jadi SATU baris per carton, supaya
        // level berikutnya (per PO+OP) menghitung carton yang BENAR,
        // bukan carton yang keitung dobel/tripel gara-gara jumlah baris.
        // ============================================================
        $cartonLevel = $db->table('ship')
            ->join('po as po_ref', 'po_ref.popk', '=', 'ship.popk')
            ->selectRaw("
                TRIM(ship.POno) AS POno,
                TRIM(ship.OP) AS OP,
                COALESCE(TRIM(po_ref.poref), '') AS poref,
                ship.carton,
                MAX(ship.popk) AS popk,
                MIN(ship.part) AS part,
                SUM(ship.pcs) AS pcs,
                MAX(ship.jmlpcs) AS jmlpcs,
                MAX(ship.fca) AS fca,
                MIN(ship.status) AS status,
                MAX(ship.pinjam) AS pinjam,
                MAX(ship.kembali) AS kembali
            ")
            ->groupByRaw("TRIM(ship.POno), TRIM(ship.OP), COALESCE(TRIM(po_ref.poref), ''), ship.carton");

        // ============================================================
        // Level PO+OP -- SEKARANG diagregasi dari $cartonLevel (1 baris =
        // 1 carton fisik), BUKAN lagi langsung dari baris ship.
        // ============================================================
        $ship = $db->query()
            ->fromSub($cartonLevel, 'c')
            ->selectRaw("
                c.POno,
                c.OP,
                c.poref,
                MAX(c.popk) AS popk,
                MIN(c.part) AS part,
                SUM(CASE WHEN c.status <= 7 THEN c.pcs ELSE 0 END) AS pcs_stuff,
                SUM(CASE WHEN c.fca = 1 THEN c.pcs ELSE 0 END) AS pcs_inspect,
                SUM(CASE WHEN c.status = 7 THEN c.pcs ELSE 0 END) AS pcs_ship,
                SUM(CASE WHEN c.status <= 7 THEN c.jmlpcs ELSE 0 END) AS ctn_stuff,
                SUM(CASE WHEN c.fca = 1 THEN c.jmlpcs ELSE 0 END) AS ctn_inspect,
                SUM(CASE WHEN c.status = 7 THEN c.jmlpcs ELSE 0 END) AS ctn_ship,
                MAX(c.pinjam) AS pinjam,
                MAX(c.kembali) AS kembali,
                MIN(c.status) AS min_status,
                COUNT(*) AS total_carton,
                SUM(
                    CASE
                        WHEN c.pinjam IS NOT NULL
                        AND c.pinjam <> ''
                        AND c.pinjam <> '0000-00-00 00:00:00'
                        THEN c.jmlpcs
                        ELSE 0
                    END
                ) AS pinjam_count,
                SUM(CASE WHEN c.fca = 1 THEN c.jmlpcs ELSE 0 END) AS borrowed_count
            ")
            ->groupByRaw("c.POno, c.OP, c.poref");

        $poShipCols = [];
        for ($i = 1; $i <= 10; $i++) {
            $poShipCols[] = "MAX(ship{$i}) AS ship{$i}";
        }
        $po = $db->table('po')
            ->selectRaw("
                TRIM(POno) AS POno,
                TRIM(OP) AS OP,
                COALESCE(TRIM(poref), '') AS poref,
                MAX(popk) AS popk,
                MAX(buyer) AS buyer,
                MAX(season) AS season,
                MAX(style) AS style,
                MAX(silhouette) AS silhouette,
                MAX(mif) AS mif,
                MAX(gabung) AS gabung,
                MAX(GAC) AS GAC,
                MAX(qty) AS qty,
                MAX(ordpk) AS ordpk,
                GROUP_CONCAT(DISTINCT customer SEPARATOR ', ') AS customer,
                GROUP_CONCAT(DISTINCT material SEPARATOR ', ') AS material,
                GROUP_CONCAT(DISTINCT secsz SEPARATOR ', ') AS secsz,
                " . implode(', ', $poShipCols) . "
            ")
            ->groupByRaw("TRIM(POno), TRIM(OP), COALESCE(TRIM(poref), '')");

        $shipdateCase = 'CASE ship.part';
        for ($i = 1; $i <= 10; $i++) {
            $shipdateCase .= " WHEN {$i} THEN po.ship{$i}";
        }
        $shipdateCase .= ' ELSE NULL END AS shipdate';

        $query = $db->query()
            ->fromSub($po, 'po')
            ->joinSub($ship, 'ship', function ($join) {
                $join->on('po.POno', '=', 'ship.POno')
                    ->on('po.OP', '=', 'ship.OP')
                    ->on('po.poref', '=', 'ship.poref');
            })
            ->selectRaw("
                ship.popk,
                ship.part,
                po.POno,
                po.OP,
                po.customer,
                po.material,
                po.secsz,
                po.poref,
                po.buyer,
                po.season,
                po.style,
                po.silhouette,
                po.mif,
                po.gabung,
                po.GAC,
                po.qty,
                po.ordpk,
                {$shipdateCase},
                ship.pcs_stuff,
                ship.pcs_inspect,
                ship.pcs_ship,
                ship.ctn_stuff,
                ship.ctn_inspect,
                ship.ctn_ship,
                ship.pinjam,
                ship.kembali,
                ship.min_status,
                ship.total_carton,
                ship.pinjam_count,
                ship.borrowed_count,
                (ship.pinjam_count - ship.borrowed_count) AS returned_count,
                CASE
                    WHEN ship.pinjam_count > 0
                        AND ship.borrowed_count = 0
                        THEN 'complete'
                    WHEN ship.pinjam_count > 0
                        THEN 'partial'
                    ELSE ''
                END AS inspect_status,
                (
                    ship.pcs_stuff
                    - ship.pcs_inspect
                    - ship.pcs_ship
                ) AS balance_pcs,
                (
                    ship.ctn_stuff
                    - ship.ctn_inspect
                    - ship.ctn_ship
                ) AS balance_ctn
            ");

        if (!empty($params['search'])) {
            $search = trim($params['search']);
            $query->where(function ($q) use ($search) {
                $q->where('po.POno', 'like', "%{$search}%")
                    ->orWhere('po.OP', 'like', "%{$search}%")
                    ->orWhere('po.customer', 'like', "%{$search}%")
                    ->orWhere('po.season', 'like', "%{$search}%")
                    ->orWhere('po.style', 'like', "%{$search}%");
            });
        }
        if (!empty($params['buyer'])) {
            $query->where('po.buyer', $params['buyer']);
        }
        if (!empty($params['year'])) {
            $year      = (int) $params['year'];
            $shortYear = $year - 2000;
            $query->whereRaw(
                'LEFT(TRIM(po.OP), 2) = ?',
                [sprintf('%02d', $shortYear)]
            );
        }
        if (!empty($params['only_pinjam'])) {
            $query->where('ship.pinjam_count', '>', 0);
        }
        if (!empty($params['status'])) {
            switch ($params['status']) {
                case 'finished':
                    $query->where('ship.min_status', '<', 7);
                    break;
                case 'inspect':
                    $query->where('ship.borrowed_count', '>', 0);
                    break;
                case 'shipment':
                    $query->where('ship.min_status', '>=', 7);
                    break;
            }
        }
        if (!empty($params['ex_factory'])) {
            $range = $this->resolveExFactoryRange($params['ex_factory']);
            if ($range) {
                $query->whereBetween('po.GAC', [
                    $range['start']->format('Y-m-d 00:00:00'),
                    $range['end']->format('Y-m-d 23:59:59'),
                ]);
            }
        }

        $total = $db->query()
            ->fromSub(clone $query, 'x')
            ->count();

        $sortDir = (isset($params['sort']) && strtolower((string) $params['sort']) === 'asc')
            ? 'asc'
            : 'desc';

        $allData = $query->get();
        foreach ($allData as $r) {
            $r->gac_sort_ts = $this->normalizeGacForSort($r->GAC);
        }
        $sorted = $sortDir === 'asc'
            ? $allData->sortBy('gac_sort_ts')->values()
            : $allData->sortByDesc('gac_sort_ts')->values();

        $data = $sorted->slice($offset, $rows)->values();

        return [
            'total' => $total,
            'data'  => $data,
        ];
    }

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
     * Breakdown SATU grup POno+OP(+poref opsional) menjadi baris per
     * popk+part+secsz -- dipakai popup di grid: klik "Lihat Rincian" pada
     * baris agregat menampilkan rincian OP/PO/color/secsz per popk+part
     * lengkap dengan angka Finished Goods/Inspect/Shipment/Balance +
     * Ship Date, lalu dari popup itu user masuk ke halaman detail popk+part.
     */
    public function getShipBreakdownByPoOp($POno, $OP, $poref = null)
    {
        $db = $this->conn();

        $ship = $db->table('ship')
            ->selectRaw("
                popk,
                part,
                secsz,

                SUM(CASE WHEN status <= 7 THEN pcs ELSE 0 END) AS pcs_stuff,
                SUM(CASE WHEN fca = 1 THEN pcs ELSE 0 END) AS pcs_inspect,
                SUM(CASE WHEN status = 7 THEN pcs ELSE 0 END) AS pcs_ship,

                SUM(CASE WHEN status <= 7 THEN jmlpcs ELSE 0 END) AS ctn_stuff,
                SUM(CASE WHEN fca = 1 THEN jmlpcs ELSE 0 END) AS ctn_inspect,
                SUM(CASE WHEN status = 7 THEN jmlpcs ELSE 0 END) AS ctn_ship,

                MAX(pinjam) AS pinjam,
                MAX(kembali) AS kembali,

                MIN(status) AS min_status
            ")
            ->whereRaw('TRIM(POno) = ?', [trim((string) $POno)])
            ->whereRaw('TRIM(OP) = ?', [trim((string) $OP)])
            ->groupBy('popk', 'part', 'secsz');

        $shipdateCase = 'CASE ship.part';
        for ($i = 1; $i <= 10; $i++) {
            $shipdateCase .= " WHEN {$i} THEN po.ship{$i}";
        }
        $shipdateCase .= ' ELSE NULL END AS shipdate';

        $po = $db->table('po')
            ->joinSub($ship, 'ship', function ($join) {
                $join->on('po.popk', '=', 'ship.popk');
            })
            ->selectRaw("
                ship.popk,
                ship.part,
                ship.secsz,

                po.POno,
                po.OP,
                po.poref,
                po.customer,
                po.material,
                po.gabung,

                {$shipdateCase},

                ship.pcs_stuff,
                ship.pcs_inspect,
                ship.pcs_ship,

                ship.ctn_stuff,
                ship.ctn_inspect,
                ship.ctn_ship,

                ship.pinjam,
                ship.kembali,

                ship.min_status,

                (ship.pcs_stuff - ship.pcs_inspect - ship.pcs_ship) AS balance_pcs,
                (ship.ctn_stuff - ship.ctn_inspect - ship.ctn_ship) AS balance_ctn
            ")
            ->orderBy('ship.popk')
            ->orderBy('ship.part')
            ->orderBy('ship.secsz');

        // Baris grid sudah per POno+OP+poref -> popup di-scope ke poref
        // yang sama (kalau dikirim). '' dianggap "PO tanpa poref".
        if ($poref !== null) {
            $poref = trim((string) $poref);
            $po->whereRaw('COALESCE(TRIM(po.poref), ?) = ?', [$poref, $poref]);
        }

        return $po->get();
    }

    /**
     * Semua baris po dalam SATU PO+OP -- lintas SELURUH customer/place,
     * material/color, secondary size, dan popk. Dipakai laporan GLOBAL
     * (finGoods.printGlobal & halaman detail): laporan/tampilan gabungan
     * penuh satu PO+OP, berbeda dari laporan biasa (scoped popk+part)
     * atau laporan gab (scoped satu grup gabung/popk halaman).
     */
    public function getGlobalPoRows($POno, $OP)
    {
        return $this->conn()->table('po')
            ->whereRaw('TRIM(POno) = ?', [trim((string) $POno)])
            ->whereRaw('TRIM(OP) = ?', [trim((string) $OP)])
            ->get();
    }

    /**
     * Semua baris ship dalam SATU PO+OP -- lintas seluruh customer/
     * material/secsz/part/popk. Kolom POno/OP sudah tersimpan langsung
     * di tabel ship (tidak perlu join popk) sehingga scoping ini murni
     * berdasar PO+OP, sama seperti getShipList/getShipBreakdownByPoOp.
     */
    public function getGlobalShipRows($POno, $OP)
    {
        return $this->conn()->table('ship')
            ->whereRaw('TRIM(POno) = ?', [trim((string) $POno)])
            ->whereRaw('TRIM(OP) = ?', [trim((string) $OP)])
            ->orderBy('urut')
            ->orderBy('shippk')
            ->get();
    }

    /** Baris ship terakhir (timestamp update terakhir) untuk SATU PO+OP (laporan global). */
    public function getLatestShipUpdateGlobal($POno, $OP)
    {
        return $this->conn()->table('ship')
            ->whereRaw('TRIM(POno) = ?', [trim((string) $POno)])
            ->whereRaw('TRIM(OP) = ?', [trim((string) $OP)])
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu', 'desc')
            ->first();
    }

    /* =====================================================
     |  PO
     ===================================================== */

    public function findPoByPopk($popk)
    {
        return $this->conn()->table('po')->where('popk', $popk)->first();
    }

    public function getPoMif2Summary($popk)
    {
        return $this->conn()->table('po')
            ->selectRaw('
                ket, wh, sap1, sap2, gabung, shipdate1, shipdate2, ctn, customer, season, POno, OP,
                buyer, style, material, silhouette,
                size1,size2,size3,size4,size5,size6,size7,size8,size9,size10,
                size11,size12,size13,size14,size15,size16,size17,size18,size19,size20,
                size21,size22,size23,size24,size25,size26,size27,size28,size29,size30,
                size31,size32,size33,size34,size35,size36,size37,size38,size39,size40,
                sum(qty) as qty, ' . $this->sumFields('qty', 40)
            )
            ->where('mif', '2')
            ->where('popk', $popk)
            ->groupBy('popk')
            ->first();
    }

    public function getPoList(array $filters, $orderBy)
    {
        return $this->applyFilters($this->conn()->table('po'), $filters)
            ->selectRaw('*, ' . $this->sumFields('qty', 40))
            ->orderBy($orderBy)
            ->get();
    }

    public function getPoGroupedMaterials(array $filters)
    {
        return $this->applyFilters($this->conn()->table('po'), $filters)
            ->selectRaw('material, qty, ' . $this->sumFields('qty', 40))
            ->groupBy('material')
            ->orderBy('qty', 'desc')
            ->get();
    }

    public function getPoOrderTotals(array $filters, $withCtn = false)
    {
        $select = 'sum(qty) as qty, '
            . ($withCtn ? 'sum(ctn) as ctn, ' : '')
            . $this->sumFields('qty', 40);

        return $this->applyFilters($this->conn()->table('po'), $filters)
            ->selectRaw($select)
            ->first();
    }

    public function getPoCustomers(array $filters)
    {
        return $this->applyFilters($this->conn()->table('po'), $filters)
            ->groupBy('customer')
            ->orderBy('customer')
            ->select('customer')
            ->get();
    }

    public function getPosByPopks(array $popks)
    {
        return $this->conn()->table('po')->whereIn('popk', $popks)->get()->keyBy('popk');
    }

    public function getPoLatestCtnByPopk(array $filters)
    {
        $ctn = $this->applyFilters($this->conn()->table('po'), $filters)
            ->groupBy('popk')
            ->orderBy('popk', 'desc')
            ->value('ctn');

        return $ctn !== null ? $ctn : 0;
    }

    public function getPoSumCtnByMaterial(array $filters)
    {
        return $this->applyFilters($this->conn()->table('po'), $filters)
            ->groupBy('material')
            ->sum('ctn');
    }

    public function getPoCtnValueByMaterial(array $filters)
    {
        $ctn = $this->applyFilters($this->conn()->table('po'), $filters)
            ->groupBy('material')
            ->value('ctn');

        return $ctn !== null ? $ctn : 0;
    }

    public function getShipLatestCarton(array $filters)
    {
        $carton = $this->applyFilters($this->conn()->table('ship'), $filters)
            ->where('pcsp', '>', 0)
            ->orderBy('shippk', 'desc')
            ->value('carton');

        return $carton !== null ? $carton : 0;
    }

    /* =====================================================
     |  SHIP - ringkasan per popk (halaman detail)
     ===================================================== */

    public function getBreakdownSummary($popk, $part)
    {
        $parts = [];
        for ($i = 1; $i <= 40; $i++) {
            $parts[] = "SUM(qty{$i}) as ord{$i}";
            $parts[] = "SUM(CASE WHEN status >= 6 THEN qty{$i} ELSE 0 END) as rdy{$i}";
            $parts[] = "SUM(CASE WHEN fca = 1 THEN qty{$i} ELSE 0 END) as ins{$i}";
        }

        return $this->conn()->table('ship')
            ->selectRaw(
                'popk, '
                . 'SUM(pcs) as ord_total, '
                . 'SUM(CASE WHEN status >= 6 THEN pcs ELSE 0 END) as rdy_total, '
                . 'SUM(CASE WHEN fca = 1 THEN pcs ELSE 0 END) as ins_total, '
                . 'SUM(jmlpcs) as ctn_plan, '
                . 'SUM(CASE WHEN status >= 6 THEN jmlpcs ELSE 0 END) as ctn_actual, '
                . 'SUM(CASE WHEN fca = 1 THEN jmlpcs ELSE 0 END) as ctn_inspect, '
                . implode(', ', $parts)
            )
            ->where('popk', $popk)
            ->where('part', $part)
            ->first();
    }

    public function getShipSizeSummary($popk)
    {
        return $this->conn()->table('ship')
            ->selectRaw(
                'popk, SUM(pcs) as pcs, ' . $this->sumQtypAsQtyLegacy()
            )
            ->where('popk', $popk)
            ->first();
    }

    private function sumQtypAsQtyLegacy()
    {
        $parts = [];
        for ($i = 1; $i <= 40; $i++) {
            $parts[] = "SUM(qty{$i}) as qty{$i}";
        }
        return implode(', ', $parts);
    }

    public function getShipDetailRows($popk, $part)
    {
        return $this->conn()->table('ship')
            ->where('popk', $popk)
            ->where('part', $part)
            ->orderByDesc('tanggal')
            ->get();
    }

    public function getShipQtypSummary($popk)
    {
        $sumQty = [];
        $sumQtyp = [];
        for ($i = 1; $i <= 40; $i++) {
            $sumQty[]  = "SUM(qty{$i}) as qty{$i}";
            $sumQtyp[] = "SUM(qtyp{$i}) as qtyp{$i}";
        }

        return $this->conn()->table('ship')
            ->selectRaw(
                'SUM(pcs) as pcs, SUM(jmlpcs) as ship, SUM(pcsp) as pcsp, '
                . implode(', ', $sumQty) . ', ' . implode(', ', $sumQtyp)
            )
            ->where('popk', $popk)
            ->first();
    }

    /* =====================================================
     |  SHIP - data packing (dulu tabel `pack`)
     ===================================================== */

    public function getShipAgg(array $filters, $groupBy = null)
    {
        $query = $this->applyFilters($this->conn()->table('ship'), $filters)
            ->selectRaw('sum(pcs) as pcs, ' . $this->sumQtypAsQty());

        if ($groupBy !== null) {
            $query->groupBy($groupBy);
        }

        return $query->first();
    }

    public function getShipDetailGroups(array $filters, array $orderBys, $withMeasures = false, array $extraCols = [])
    {
        $extra = count($extraCols) ? implode(', ', $extraCols) . ', ' : '';
        $meas  = $withMeasures ? 'meas, nw, gw, ' : '';

        $query = $this->applyFilters($this->conn()->table('ship'), $filters)
            ->selectRaw(
                $meas . 'count(*) as ctn, pcsp, urut, ' . $extra
                . 'POno, customer, OP, material, '
                . $this->qtypColumns() . ', carton'
            )
            ->groupBy('pcsp', 'urut');

        return $this->applyOrderBys($query, $orderBys)->get();
    }

    public function getShipCartons(array $filters, array $orderBys)
    {
        $query = $this->applyFilters($this->conn()->table('ship'), $filters)
            ->select('carton', 'pcs', 'keterangan', 'status', 'fca', 'pcsp', 'urut');

        return $this->applyOrderBys($query, $orderBys)->get();
    }

    public function getMixedCartonGroups(array $filters, array $orderBys)
    {
        $query = $this->applyFilters($this->conn()->table('ship'), $filters)
            ->selectRaw(
                'count(*) as ctn, status, fca, keterangan, carton, pcsp, pcs, urut, '
                . 'POno, customer, secsz, OP, material, ' . $this->qtypColumns()
            )
            ->groupBy('carton');

        return $this->applyOrderBys($query, $orderBys)->get();
    }

    public function getMixedSubRowGroups(array $filters, array $cartons, $secondGroupCol)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)
            ->whereIn('carton', $cartons)
            ->selectRaw(
                'carton, count(*) as no, keterangan, pcsp, pcs, urut, '
                . 'POno, customer, secsz, OP, material, ' . $this->qtypColumns()
            )
            ->groupBy('carton', $secondGroupCol)
            ->orderBy($secondGroupCol)
            ->get();
    }

    public function getRows(array $filters, array $orderBys = [], array $columns = ['*'])
    {
        $query = $this->applyFilters($this->conn()->table('ship'), $filters)
            ->select($columns);

        return $this->applyOrderBys($query, $orderBys)->get();
    }

    /* --- Aksi massal carton (multi-select laporan) --- */

    public function markInspectByCartons($popk, $part, array $cartons)
    {
        return $this->conn()->table('ship')
            ->where('popk', $popk)
            ->where('part', $part)
            ->whereIn('carton', $cartons)
            ->whereNull('fca')
            ->update([
                'fca'    => 1,
                'pinjam' => now(),
            ]);
    }

    public function markShipmentByCartons($popk, $part, array $cartons)
    {
        return $this->conn()->table('ship')
            ->where('popk', $popk)
            ->where('part', $part)
            ->whereIn('carton', $cartons)
            ->whereNull('fca')
            ->where('status', '<', 6)
            ->update(['status' => 6]);
    }

    public function returnToStuffingByCartons($popk, $part, array $cartons)
    {
        $updated = $this->conn()->table('ship')
            ->where('popk', $popk)
            ->where('part', $part)
            ->whereIn('carton', $cartons)
            ->where('fca', 1)
            ->update(['fca' => null]);

        $this->conn()->table('ship')
            ->where('popk', $popk)
            ->where('part', $part)
            ->whereIn('carton', $cartons)
            ->where(function ($q) {
                $q->whereNull('kembali')
                    ->orWhere('kembali', '')
                    ->orWhere('kembali', '0000-00-00 00:00:00');
            })
            ->update(['kembali' => now()]);

        return $updated;
    }

    /* --- Versi GLOBAL (scoped POno+OP, bukan popk+part) ---
     * Dipakai halaman detail global: scan & bulk action tidak lagi
     * dibatasi satu popk+part, tapi berlaku ke seluruh baris ship
     * dalam PO+OP yang sama (lintas semua color/customer/secsz/part). */

    public function markInspectByCartonsGlobal($POno, $OP, array $cartons)
    {
        return $this->conn()->table('ship')
            ->whereRaw('TRIM(POno) = ?', [trim((string) $POno)])
            ->whereRaw('TRIM(OP) = ?', [trim((string) $OP)])
            ->whereIn('carton', $cartons)
            ->whereNull('fca')
            ->update([
                'fca'    => 1,
                'pinjam' => now(),
            ]);
    }

    public function markShipmentByCartonsGlobal($POno, $OP, array $cartons)
    {
        return $this->conn()->table('ship')
            ->whereRaw('TRIM(POno) = ?', [trim((string) $POno)])
            ->whereRaw('TRIM(OP) = ?', [trim((string) $OP)])
            ->whereIn('carton', $cartons)
            ->whereNull('fca')
            ->where('status', '<', 6)
            ->update(['status' => 6]);
    }

    public function returnToStuffingByCartonsGlobal($POno, $OP, array $cartons)
    {
        $updated = $this->conn()->table('ship')
            ->whereRaw('TRIM(POno) = ?', [trim((string) $POno)])
            ->whereRaw('TRIM(OP) = ?', [trim((string) $OP)])
            ->whereIn('carton', $cartons)
            ->where('fca', 1)
            ->update(['fca' => null]);

        $this->conn()->table('ship')
            ->whereRaw('TRIM(POno) = ?', [trim((string) $POno)])
            ->whereRaw('TRIM(OP) = ?', [trim((string) $OP)])
            ->whereIn('carton', $cartons)
            ->where(function ($q) {
                $q->whereNull('kembali')
                    ->orWhere('kembali', '')
                    ->orWhere('kembali', '0000-00-00 00:00:00');
            })
            ->update(['kembali' => now()]);

        return $updated;
    }

    /** Baris ship untuk satu nobar (hasil scan barcode), scoped PO+OP (global). */
    public function getShipRowsByNobarGlobal($POno, $OP, $nobar)
    {
        return $this->conn()->table('ship')
            ->select('shippk', 'popk', 'part', 'carton', 'material', 'secsz', 'status', 'fca', 'pcs', 'nobar')
            ->whereRaw('TRIM(POno) = ?', [trim((string) $POno)])
            ->whereRaw('TRIM(OP) = ?', [trim((string) $OP)])
            ->where('nobar', $nobar)
            ->get();
    }

    /**
     * Update status baris ship berdasarkan nobar (scan), scoped PO+OP
     * (global) -- TIDAK dibatasi popk+part, jadi operator bisa scan
     * carton color/customer manapun dalam satu PO+OP yang sama tanpa
     * perlu pindah halaman per color.
     */
    public function updateStatusByNobarGlobal($POno, $OP, $nobar, $status)
    {
        return $this->conn()->table('ship')
            ->whereRaw('TRIM(POno) = ?', [trim((string) $POno)])
            ->whereRaw('TRIM(OP) = ?', [trim((string) $OP)])
            ->where('nobar', $nobar)
            ->whereNull('fca')
            ->where('status', '<', $status)
            ->update(['status' => $status]);
    }

    /* --- Lock shipment (tombol gembok di grid) --- */

    public function countNotReadyRows($popk, $part)
    {
        return $this->conn()->table('ship')
            ->where('popk', $popk)
            ->where('part', $part)
            ->where('status', '<', 6)
            ->count();
    }

    public function lockShipmentByPopkPart($popk, $part)
    {
        return $this->conn()->table('ship')
            ->where('popk', $popk)
            ->where('part', $part)
            ->where('status', '<', 7)
            ->update(['status' => 7]);
    }

    public function unlockShipmentByPopkPart($popk, $part)
    {
        return $this->conn()->table('ship')
            ->where('popk', $popk)
            ->where('part', $part)
            ->where('status', 7)
            ->update(['status' => 6]);
    }

    public function getShipRowsByNobar($popk, $part, $nobar)
    {
        return $this->conn()->table('ship')
            ->select('shippk', 'carton', 'status', 'fca', 'pcs', 'nobar')
            ->where('popk', $popk)
            ->where('part', $part)
            ->where('nobar', $nobar)
            ->get();
    }

    public function updateStatusByNobar($popk, $part, $nobar, $status)
    {
        return $this->conn()->table('ship')
            ->where('popk', $popk)
            ->where('part', $part)
            ->where('nobar', $nobar)
            ->whereNull('fca')
            ->where('status', '<', $status)
            ->update(['status' => $status]);
    }

    public function getLatestShipUpdate($popk)
    {
        return $this->conn()->table('ship')
            ->where('popk', $popk)
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu', 'desc')
            ->first();
    }

    public function getMeasList(array $filters)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)
            ->select('meas')
            ->where('meas', '<>', '')
            ->groupBy('meas')
            ->orderBy('meas')
            ->get();
    }

    public function getMeasureList($field, array $filters)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)
            ->select($field, 'urut')
            ->where($field, '>', 0)
            ->groupBy($field, 'urut')
            ->orderBy('urut')
            ->get();
    }

    public function getShipQtypPcspTotals(array $filters)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)
            ->selectRaw($this->sumQtypAsQty() . ', sum(pcsp) as pcs')
            ->first();
    }

    public function getPoPlainList(array $filters, $orderBy)
    {
        return $this->applyFilters($this->conn()->table('po'), $filters)
            ->orderBy($orderBy)
            ->get();
    }

    public function getPcsByCarton(array $filters, array $cartons = null)
    {
        $query = $this->applyFilters($this->conn()->table('ship'), $filters);

        if ($cartons !== null) {
            $query->whereIn('carton', $cartons);
        }

        return $query
            ->groupBy('carton')
            ->selectRaw('carton, sum(pcs) as pcs')
            ->pluck('pcs', 'carton');
    }

    public function getPoRaw(array $filters, $selectRaw, array $groupBys = [], array $orderBys = [])
    {
        $query = $this->applyFilters($this->conn()->table('po'), $filters)
            ->selectRaw($selectRaw);

        foreach ($groupBys as $g) {
            $query->groupBy($g);
        }

        return $this->applyOrderBys($query, $orderBys)->get();
    }

    public function sumShipColumn(array $filters, $column)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)->sum($column);
    }

    public function getTopRowByPcsp(array $filters)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)
            ->orderBy('pcsp', 'desc')
            ->first();
    }

    public function getEntityGroups(array $filters, $groupBy)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)
            ->groupBy($groupBy)
            ->orderBy($groupBy)
            ->selectRaw('secsz, customer, POno, OP, material')
            ->get();
    }

    public function getRatioRowsGroupedByPcsp(array $filters)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)
            ->selectRaw('*, ' . $this->sumPrefixQtyp('ship'))
            ->groupBy('pcsp')
            ->orderBy('pcsp', 'desc')
            ->limit(1)
            ->get();
    }

    public function getCtnGroupsByPcsp(array $filters)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)
            ->selectRaw('pcsp, count(*) as ctn, POno, OP, material, customer, secsz, carton')
            ->groupBy('pcsp')
            ->orderBy('pcsp', 'desc')
            ->get();
    }

    public function getShipCustomerGroups(array $filters)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)
            ->groupBy('customer')
            ->orderBy('shippk')
            ->select('customer', 'popk')
            ->get();
    }

    public function getMeasureRows(array $filters)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)
            ->orderBy('shippk')
            ->selectRaw('nw, gw, ' . $this->qtypColumns())
            ->get();
    }

    public function getMeasureMaxUrut($field, array $filters)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)
            ->where($field, '>', 0)
            ->groupBy('urut')
            ->orderBy('urut', 'desc')
            ->value('urut');
    }

    public function getMeasureValues($field, array $filters)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)
            ->where($field, '>', 0)
            ->groupBy($field, 'urut')
            ->orderBy('urut')
            ->get([$field]);
    }

    public function getMeasureMixedRows($field, array $filters)
    {
        return $this->applyFilters($this->conn()->table('ship'), $filters)
            ->where($field, '>', 0)
            ->groupBy($field, 'urut')
            ->orderBy('urut')
            ->selectRaw("urut, {$field}, " . $this->qtyColumns())
            ->get();
    }

    public function getDetailMaterialsJoinPo($POno, $OP, $customer, $gabung)
    {
        return $this->conn()->table('ship')
            ->leftJoin('po', 'po.popk', '=', 'ship.popk')
            ->where('ship.POno', $POno)->where('ship.OP', $OP)
            ->where('ship.customer', $customer)->where('po.gabung', $gabung)
            ->selectRaw($this->sumPrefixQtyp('ship') . ', ship.material, sum(ship.pcsp) as pcsp')
            ->groupBy('ship.material')
            ->orderBy('pcsp', 'desc')
            ->get();
    }

    public function getCartonHeadJoinPo($POno, $OP, $customer, $gabung)
    {
        return $this->conn()->table('ship')
            ->leftJoin('po', 'po.popk', '=', 'ship.popk')
            ->where('ship.POno', $POno)->where('ship.OP', $OP)
            ->where('ship.customer', $customer)->where('po.gabung', $gabung)
            ->selectRaw('count(*) as ctn, ship.POno, ship.OP, ship.material, ship.customer')
            ->groupBy('ship.popk')
            ->orderBy('ship.pcsp', 'desc')
            ->limit(1)
            ->first();
    }

    public function getCartonListJoinPo($POno, $OP, $material, $customer, $gabung)
    {
        return $this->conn()->table('ship')
            ->leftJoin('po', 'po.popk', '=', 'ship.popk')
            ->where('ship.POno', $POno)->where('ship.OP', $OP)
            ->where('ship.material', $material)->where('ship.customer', $customer)
            ->where('po.gabung', $gabung)
            ->select('ship.carton', 'ship.pcs', 'ship.keterangan', 'ship.status', 'ship.fca')
            ->orderBy('ship.urut')->orderBy('ship.shippk')
            ->get();
    }

    public function getPcsByCartonJoinPo(array $cartons, $POno, $OP, $customer, $gabung)
    {
        return $this->conn()->table('ship')
            ->leftJoin('po', 'po.popk', '=', 'ship.popk')
            ->whereIn('ship.carton', $cartons)
            ->where('ship.POno', $POno)->where('ship.OP', $OP)
            ->where('ship.customer', $customer)->where('po.gabung', $gabung)
            ->groupBy('ship.carton')
            ->selectRaw('ship.carton, sum(ship.pcs) as pcs')
            ->pluck('pcs', 'carton');
    }

    /* =====================================================
     |  Detail shipping (blok lama, tetap tabel ship)
     ===================================================== */

    public function getDetailShippingData($dt, $customer)
    {
        $groups = $this->conn()->table('ship')
            ->selectRaw(
                'count(*) as ctn, pcsp, urut, POno, customer, OP, material, '
                . $this->qtypColumns() . ', carton'
            )
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('customer', $customer)
            ->groupBy('pcsp', 'urut')->orderBy('urut')->orderBy('shippk')
            ->get();

        $allCartons = $this->conn()->table('ship')
            ->select('carton', 'pcs', 'keterangan', 'status', 'fca', 'pcsp', 'urut')
            ->where('POno', $dt->POno)->where('OP', $dt->OP)
            ->where('material', $dt->material)->where('customer', $customer)
            ->orderBy('urut')->orderBy('shippk')
            ->get()
            ->groupBy(function ($c) {
                return $c->urut . '|' . $c->pcsp;
            });

        return ['groups' => $groups, 'allCartons' => $allCartons];
    }
}