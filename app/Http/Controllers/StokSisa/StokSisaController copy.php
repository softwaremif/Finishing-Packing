<?php

namespace App\Http\Controllers\StokSisa;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StokSisaController extends Controller
{
    public function index()
    {
        return view('menu.stok-sisa.index');
    }

    public function getList(Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;

        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        $combined = $this->fetchAll($request);

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

        // Total summary dihitung dari data MENTAH ($combined, sebelum
        // di-groupBy) -- hasilnya SAMA dengan sum-of-sums per grup, tapi
        // tidak perlu hitung ulang dari $aggregated.
        $summary = [
            'qty'      => $combined->sum('qty'),
            'loading'  => $combined->sum('loading'),
            'rq'       => $combined->sum('rq'),
            'transfer' => $combined->sum('transfer'),
            'packing'  => $combined->sum('packing'),
            'balance'  => $combined->sum('balance'),
            'keluar'   => $combined->sum('keluar'),
        ];

        return response()->json([
            'total'   => $total,
            'rows'    => $data,
            'summary' => $summary,
        ]);
    }

    /**
     * Detail SEMUA baris (per color/material, TIDAK digabung) untuk satu
     * PO+OP -- dipakai modal rincian di index.
     */
    public function detailByPoOp(Request $request)
    {
        $validated = $request->validate([
            'po' => 'nullable',
            'op' => 'required',
        ]);

        $po = $validated['po'] ?? null;
        $op = $validated['op'];

        $rows = $this->fetchAll($request, $po, $op)->values();

        foreach ($rows as $i => $row) {
            $row->no = $i + 1;
        }

        $summary = [
            'qty'      => $rows->sum('qty'),
            'loading'  => $rows->sum('loading'),
            'rq'       => $rows->sum('rq'),
            'transfer' => $rows->sum('transfer'),
            'packing'  => $rows->sum('packing'),
            'balance'  => $rows->sum('balance'),
            'keluar'   => $rows->sum('keluar'),
        ];

        return response()->json([
            'total'   => $rows->count(),
            'rows'    => $rows,
            'summary' => $summary,
        ]);
    }

    private function aggregateByPoOp($collection)
    {
        return $collection
            ->groupBy(fn ($row) => $row->POno . '|' . $row->OP)
            ->map(function ($group) {
                $representative = clone $group->sortByDesc('popk')->first();

                $representative->qty      = (float) $group->sum('qty');
                $representative->loading  = (float) $group->sum('loading');
                $representative->rq       = (float) $group->sum('rq');
                $representative->transfer = (float) $group->sum('transfer');
                $representative->packing  = (float) $group->sum('packing');
                $representative->keluar   = (float) $group->sum('keluar');
                $representative->balance  = $representative->transfer - $representative->packing;

                $representative->status = (int) $group->max('status');

                return $representative;
            })
            ->values();
    }

    private function fetchAll(Request $request, ?string $po = null, ?string $op = null)
    {
        $search = $request->search;
        $buyer  = $request->buyer;
        $year   = $request->year;

        $qualifyingPacking = DB::table('pack')
            ->select('popk')
            ->groupBy('popk')
            ->havingRaw('SUM(pcs) > 0');

        $bj = DB::table('bj')
            ->selectRaw("
                popk,
                SUM(pcs)  AS transfer_pcs,
                SUM(pcsk) AS keluar_pcs,
                MAX(tglin)  AS tglin,
                MAX(tglout) AS tglout,
                MAX(status) AS status
            ")
            ->groupBy('popk');

        $pack = DB::table('pack')
            ->selectRaw('popk, SUM(pcs) AS packing_pcs')
            ->groupBy('popk');

        $query = DB::table('po')
            // INNER JOIN (bukan leftJoin) -- PO WAJIB ada di kedua gate
            // sekaligus (grade DAN packing) supaya lolos tampil.
            ->joinSub($qualifyingPacking, 'qpk', function ($join) {
                $join->on('po.popk', '=', 'qpk.popk');
            })
            ->leftJoinSub($bj, 'bj', function ($join) {
                $join->on('po.popk', '=', 'bj.popk');
            })
            ->leftJoinSub($pack, 'pack', function ($join) {
                $join->on('po.popk', '=', 'pack.popk');
            })
            ->selectRaw("
                po.popk,
                po.moppk,
                po.POno,
                po.poref,
                po.OP,
                po.customer,
                po.season,
                po.style,
                po.material,
                po.buyer,
                po.qty,
                po.secsz,
                po.silhouette,
                po.shipdate1,
                po.mif,

                bj.tglin,
                bj.tglout,
                bj.status,
                COALESCE(bj.transfer_pcs, 0) AS transfer,
                COALESCE(bj.keluar_pcs, 0)   AS keluar,
                COALESCE(pack.packing_pcs,0) AS packing,

                (COALESCE(bj.transfer_pcs,0) - COALESCE(pack.packing_pcs,0)) AS balance
            ");

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

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po.POno', 'like', "%{$search}%")
                    ->orWhere('po.OP', 'like', "%{$search}%")
                    ->orWhere('po.poref', 'like', "%{$search}%")
                    ->orWhere('po.customer', 'like', "%{$search}%")
                    ->orWhere('po.season', 'like', "%{$search}%")
                    ->orWhere('po.secsz', 'like', "%{$search}%")
                    ->orWhere('po.silhouette', 'like', "%{$search}%")
                    ->orWhere('po.style', 'like', "%{$search}%");
            });
        }

        if ($buyer) {
            $query->where('po.buyer', 'like', "%{$buyer}%");
        }

        if ($year) {
            $query->whereRaw('YEAR(po.shipdate1) >= ?', [(int) $year]);
        }

        $rows = $query
            ->orderByDesc('po.shipdate1')
            ->orderByDesc('po.OP')
            ->orderByDesc('po.POno')
            ->orderByDesc('po.material')
            ->get();

        $moppks = $rows->pluck('moppk')->filter()->unique()->values();

        $loadingMap = collect();
        $rMap = collect();
        $qMap = collect();

        if ($moppks->isNotEmpty()) {
            $loadingMap = DB::connection('mysql_sop')->table('sop')
                ->select('moppk')->selectRaw('SUM(tot) as qty')
                ->whereIn('moppk', $moppks)
                ->groupBy('moppk')
                ->pluck('qty', 'moppk');

            $rMap = DB::connection('mysql_sop')->table('r')
                ->select('moppk')->selectRaw('SUM(tot) as qty')
                ->whereIn('moppk', $moppks)
                ->groupBy('moppk')
                ->pluck('qty', 'moppk');

            $qMap = DB::connection('mysql_sop')->table('q')
                ->select('moppk')->selectRaw('SUM(tot) as qty')
                ->whereIn('moppk', $moppks)
                ->groupBy('moppk')
                ->pluck('qty', 'moppk');
        }

        foreach ($rows as $row) {
            $row->loading = (float) ($loadingMap[$row->moppk] ?? 0);
            $row->rq      = (float) ($rMap[$row->moppk] ?? 0) + (float) ($qMap[$row->moppk] ?? 0);
        }

        return $rows;
    }
}