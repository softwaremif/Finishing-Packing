<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * VERSI 2 — perubahan struktural untuk performa:
 *
 * 1. Base query TIDAK LAGI dari `bj` (level baris) melainkan dari `po`
 *    (level popk). Dulu MySQL harus scan semua baris bj lalu GROUP BY
 *    25+ kolom — itu bagian paling berat. Sekarang GROUP_CONCAT linenm
 *    dipindah ke subquery sendiri, sehingga semua join jadi 1:1 per popk
 *    dan GROUP BY besar di query utama HILANG total.
 *
 * 2. Semua agregat bj digabung 1 subquery, semua agregat pack per-popk
 *    digabung 1 subquery (sama seperti versi sebelumnya).
 *
 * 3. WAJIB: pasang index dari migration `add_packing_indexes` di KEDUA
 *    database (mysql & mysql_andon). Tanpa index, subquery pack/bj tetap
 *    full table scan dan perubahan struktur ini tidak banyak menolong.
 */
class PackingRepository
{
    public function resolveConnection($mif): string
    {
        return ((int) $mif) === 1 ? 'mysql_andon' : 'mysql';
    }

    public function fetchAll(string $connection, int $mif, array $filters = []): Collection
    {
        $db = DB::connection($connection);

        // --- Agregat bj per popk (transfer + checked dalam 1 scan) --------
        $bjAgg = $db->table('bj')
            ->selectRaw("
                popk,
                SUM(CASE WHEN check2 = 0 THEN pcs ELSE 0 END) AS transfer,
                SUM(CASE WHEN check2 = 1 THEN pcs ELSE 0 END) AS checked_qty
            ")
            ->groupBy('popk');

        // --- Agregat pack per popk (qty, plan, ctn, status dalam 1 scan) --
        $packAgg = $db->table('pack')
            ->selectRaw("
                popk,
                SUM(pcs)  AS packing_qty,
                SUM(pcsp) AS packing_qty_plan,
                SUM(CASE WHEN status >= 4 THEN jmlpcs ELSE 0 END) AS packing_ctn,
                MAX(status) AS status
            ")
            ->groupBy('popk');

        $segel = $db->table('pack')
            ->selectRaw("
                popk,
                MAX(CASE WHEN part = '10' THEN 1 ELSE 0 END) AS segel_complete,
                GROUP_CONCAT(
                    DISTINCT CASE WHEN part <> '10' THEN part END
                    ORDER BY CAST(part AS UNSIGNED)
                    SEPARATOR ', '
                ) AS segel_partial_no
            ")
            ->where('status', 5)
            ->groupBy('popk');

        // --- GROUP_CONCAT line DIPINDAH ke subquery sendiri ---------------
        // Inilah yang membuat query utama bebas GROUP BY.
        $lines = $db->table('bj')
            ->join('line', 'line.linepk', '=', 'bj.linepk')
            ->selectRaw("
                bj.popk,
                GROUP_CONCAT(
                    DISTINCT TRIM(SUBSTRING(line.linenm, 6, 3))
                    ORDER BY line.linenm
                    SEPARATOR ';'
                ) AS linenm
            ")
            ->where('bj.check2', 0)
            ->groupBy('bj.popk');

        $ctnGabung1 = $db->table('pack')
            ->selectRaw('POno, OP, customer, COUNT(*) AS ctn')
            ->where('status', '>=', 4)
            ->groupBy('POno', 'OP', 'customer');

        $ctnGabung4 = $db->table('pack')
            ->selectRaw('POno, OP, material, COUNT(*) AS ctn')
            ->where('status', '>=', 4)
            ->groupBy('POno', 'OP', 'material');

        $ctnGabung6 = $db->table('pack')
            ->selectRaw('POno, OP, material, MAX(carton) AS carton')
            ->where('status', '>=', 4)
            ->groupBy('POno', 'OP', 'material');

        // ------------------------------------------------------------------
        // QUERY UTAMA — base dari `po`, semua join 1:1, TANPA GROUP BY
        // ------------------------------------------------------------------
        $query = $db->table('po')
            // inner join: hanya popk yang punya transfer (dulu: base dari bj
            // where check2=0 + kondisi trf.transfer > 0)
            ->joinSub($bjAgg, 'bja', fn ($j) => $j->on('po.popk', '=', 'bja.popk'))
            ->leftJoinSub($packAgg, 'pka', fn ($j) => $j->on('po.popk', '=', 'pka.popk'))
            ->leftJoinSub($segel,   'sgl', fn ($j) => $j->on('po.popk', '=', 'sgl.popk'))
            ->leftJoinSub($lines,   'ln',  fn ($j) => $j->on('po.popk', '=', 'ln.popk'))
            ->leftJoinSub($ctnGabung1, 'g1', function ($j) {
                $j->on('po.POno', '=', 'g1.POno')
                    ->on('po.OP', '=', 'g1.OP')
                    ->on('po.customer', '=', 'g1.customer');
            })
            ->leftJoinSub($ctnGabung4, 'g4', function ($j) {
                $j->on('po.POno', '=', 'g4.POno')
                    ->on('po.OP', '=', 'g4.OP')
                    ->on('po.material', '=', 'g4.material');
            })
            ->leftJoinSub($ctnGabung6, 'g6', function ($j) {
                $j->on('po.POno', '=', 'g6.POno')
                    ->on('po.OP', '=', 'g6.OP')
                    ->on('po.material', '=', 'g6.material');
            })
            ->where('po.qty', '>', 0)
            ->where('po.OP', '<>', '')
            ->where('po.mif', $mif)
            ->where('bja.transfer', '>', 0);

        // Filter PO + OP (dipakai modal detail)
        $po = $filters['po'] ?? null;
        $op = $filters['op'] ?? null;

        if ($op !== null) {
            $query->where('po.OP', $op);

            if ($po !== null && $po !== '') {
                $query->where('po.POno', $po);
            } else {
                $query->where(function ($q) {
                    $q->whereNull('po.POno')->orWhere('po.POno', '');
                });
            }
        } elseif ($po !== null) {
            $query->where('po.POno', $po);
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
                WHEN po.gabung = 1 THEN COALESCE(g1.ctn, 0)
                WHEN po.gabung = 4 THEN COALESCE(g4.ctn, 0)
                WHEN po.gabung = 6 THEN COALESCE(g6.carton, 0)
                ELSE po.ctn
            END AS ctn,

            ln.linenm AS linenm,

            COALESCE(bja.transfer, 0)    AS transfer,
            COALESCE(bja.checked_qty, 0) AS checked_qty,

            COALESCE(pka.packing_qty_plan, 0) AS packing_qty_plan,
            COALESCE(pka.packing_qty, 0)      AS packing_qty,
            (COALESCE(pka.packing_qty, 0) - COALESCE(pka.packing_qty_plan, 0)) AS packing_qty_balance,

            COALESCE(pka.packing_ctn, 0) AS packing_ctn,

            (
                COALESCE(pka.packing_ctn, 0)
                - CASE
                    WHEN po.gabung = 1 THEN COALESCE(g1.ctn, 0)
                    WHEN po.gabung = 4 THEN COALESCE(g4.ctn, 0)
                    WHEN po.gabung = 6 THEN COALESCE(g6.carton, 0)
                    ELSE po.ctn
                END
            ) AS ctn_balance,

            COALESCE(pka.status, 0)         AS status,
            COALESCE(sgl.segel_complete, 0) AS segel_complete,
            sgl.segel_partial_no            AS segel_partial_no,

            (
                COALESCE(bja.transfer, 0)
                - COALESCE(bja.checked_qty, 0)
                - COALESCE(pka.packing_qty, 0)
            ) AS balance
        ");

        // fin: dulu HAVING packing_qty > 0, sekarang cukup WHERE
        if (!empty($filters['fin'])) {
            $query->whereRaw('COALESCE(pka.packing_qty, 0) > 0');
        }

        $this->applyListFilters($query, $filters);

        return $query->orderByDesc('po.OP')->get();
    }

    private function applyListFilters($query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
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

        if (!empty($filters['buyer'])) {
            $query->where('po.buyer', $filters['buyer']);
        }

        // Dulu whereYear di baris bj (base query). Sekarang base dari po,
        // jadi diganti EXISTS: popk tetap muncul kalau punya minimal satu
        // baris transfer (check2=0) di tahun tsb — hasil baris sama.
        if (!empty($filters['year'])) {
            $year = (int) $filters['year'];

            $query->whereExists(function ($q) use ($year) {
                $q->selectRaw(1)
                    ->from('bj')
                    ->whereColumn('bj.popk', 'po.popk')
                    ->where('bj.check2', 0)
                    ->whereYear('bj.tanggal', $year);
            });
        }
    }
}