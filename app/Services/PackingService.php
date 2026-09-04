<?php

namespace App\Services;

use App\Repositories\PackingRepository;
use Illuminate\Support\Collection;

/**
 * Layer BUSINESS LOGIC — memutuskan koneksi mana yang dipakai,
 * agregasi per PO+OP, sorting, paging, dan summary.
 * Controller tinggal melempar input dan me-return JSON.
 */
class PackingService
{
    private const SUPER_USER_PK = 34;
    protected $repo;

    public function __construct(PackingRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Data untuk grid utama (paginated + summary).
     *
     * @param array $input  page, rows, sort, search, buyer, year, fin
     * @param array $ctx    ['guserpk' => session('guserpk'), 'pos' => session('pos')]
     */
    public function getList(array $input, array $ctx): array
    {
        $page   = max(1, (int) ($input['page'] ?? 1));
        $rows   = max(1, (int) ($input['rows'] ?? 10));
        $offset = ($page - 1) * $rows;

        $sortDir = ($input['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $filters = $this->extractFilters($input);

        $combined = $this->fetchForContext($ctx, $filters);

        $aggregated = $this->aggregateByPoOp($combined);

        $aggregated = $sortDir === 'asc'
            ? $aggregated->sortBy('OP')->values()
            : $aggregated->sortByDesc('OP')->values();

        $total = $aggregated->count();
        $data  = $aggregated->slice($offset, $rows)->values();

        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
        }

        return [
            'total'   => $total,
            'rows'    => $data,
            'summary' => $this->buildSummary($data),
        ];
    }

    /**
     * Data untuk modal detail per PO+OP (tanpa agregasi, level popk).
     */
    public function detailByPoOp(?string $po, string $op, $mif, array $input): array
    {
        $connection = $this->repo->resolveConnection($mif);

        $filters       = $this->extractFilters($input);
        $filters['po'] = $po;
        $filters['op'] = $op;

        $rowsData = $this->repo->fetchAll($connection, (int) $mif, $filters)->values();

        foreach ($rowsData as $i => $row) {
            $row->no = $i + 1;
        }

        return [
            'total'   => $rowsData->count(),
            'rows'    => $rowsData,
            'summary' => $this->buildSummary($rowsData),
        ];
    }

    // ======================================================================
    // Internal
    // ======================================================================

    /**
     * Superuser (guserpk 34) -> gabung 2 koneksi. Selain itu ikut session pos.
     */
    private function fetchForContext(array $ctx, array $filters): Collection
    {
        $isSuper = ((int) ($ctx['guserpk'] ?? 0)) === self::SUPER_USER_PK;

        if ($isSuper) {
            return $this->repo->fetchAll('mysql_andon', 1, $filters)
                ->concat($this->repo->fetchAll('mysql', 2, $filters));
        }

        $mif        = ((int) ($ctx['pos'] ?? 0)) === 1 ? 1 : 2;
        $connection = $this->repo->resolveConnection($mif);

        return $this->repo->fetchAll($connection, $mif, $filters);
    }

    private function extractFilters(array $input): array
    {
        return [
            'search' => $input['search'] ?? null,
            'buyer'  => $input['buyer'] ?? null,
            'year'   => $input['year'] ?? null,
            'fin'    => $input['fin'] ?? null,
        ];
    }

    /**
     * Agregasi per (POno + OP).
     *
     * OPTIMASI vs versi lama: dulu tiap grup memanggil ->sum() 7x +
     * ->max() 2x + sortByDesc()->first() = ±10 iterasi penuh per grup.
     * Sekarang cukup SATU pass per grup (O(n) total, bukan O(n x 10)).
     */
    private function aggregateByPoOp(Collection $collection): Collection
    {
        return $collection
            ->groupBy(fn ($row) => $row->POno . '|' . $row->OP)
            ->map(function (Collection $group) {
                $rep = null;

                $sum = [
                    'qty' => 0, 'transfer' => 0, 'checked_qty' => 0,
                    'packing_qty_plan' => 0, 'packing_qty' => 0,
                    'ctn' => 0, 'packing_ctn' => 0,
                ];
                $maxSegel  = 0;
                $maxStatus = 0;

                foreach ($group as $row) {
                    // representatif = popk terbesar (sama seperti versi lama)
                    if ($rep === null || $row->popk > $rep->popk) {
                        $rep = $row;
                    }

                    foreach ($sum as $key => $_) {
                        $sum[$key] += (int) $row->{$key};
                    }

                    $maxSegel  = max($maxSegel, (int) $row->segel_complete);
                    $maxStatus = max($maxStatus, (int) $row->status);
                }

                $rep = clone $rep;

                foreach ($sum as $key => $val) {
                    $rep->{$key} = $val;
                }

                $rep->packing_qty_balance = $rep->packing_qty - $rep->packing_qty_plan;
                $rep->ctn_balance         = $rep->packing_ctn - $rep->ctn;
                $rep->balance             = $rep->transfer - $rep->checked_qty - $rep->packing_qty;

                // Kalau salah satu baris dalam grup sudah Complete/Segel,
                // grup dianggap Complete/ada segel (untuk badge status).
                $rep->segel_complete = $maxSegel;
                $rep->status         = $maxStatus;

                return $rep;
            })
            ->values();
    }

    private function buildSummary(Collection $data): array
    {
        $summary = [
            'qty'                 => 0,
            'packing_qty_plan'    => 0,
            'packing_qty'         => 0,
            'packing_qty_balance' => 0,
            'ctn'                 => 0,
            'packing_ctn'         => 0,
            'ctn_balance'         => 0,
        ];

        // satu pass, bukan 7x ->sum()
        foreach ($data as $row) {
            foreach ($summary as $key => $_) {
                $summary[$key] += (int) ($row->{$key} ?? 0);
            }
        }

        return $summary;
    }
}