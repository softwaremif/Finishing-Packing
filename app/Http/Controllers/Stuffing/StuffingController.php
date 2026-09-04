<?php

namespace App\Http\Controllers\Stuffing;

use App\Http\Controllers\Controller;
use App\Services\ShipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StuffingController extends Controller
{
    protected $shipService;
    public function __construct(ShipService $shipService)
    {
        $this->shipService = $shipService;
    }
    
    public function index(Request $request) {
        return view('menu.stuffing.index');
    }

    public function getList(Request $request)
    {
        $result = $this->shipService->getListShip($request);

        return response()->json($result);
    }

    // public function detail($popk, $part, Request $request)
    // {
    //     $dt = DB::table('po')->where('popk', $popk)->first();
    //     if (!$dt) abort(404);

    //     $activeSizes = [];

    //     for ($i = 1; $i <= 40; $i++) {
    //         $size = $dt->{"size$i"} ?? null;

    //         if (!empty($size)) {
    //             $activeSizes[$i] = $size;
    //         }
    //     }

    //     // ================= DETAIL FIXED =================
    //     $details = DB::table('ship')
    //         ->where('popk', $popk)
    //         ->where('part', $part)
    //         ->orderByDesc('tanggal')
    //         ->get();

    //     return view('menu.stuffing.detail', compact(
    //         'dt',
    //         'details'
    //     ));
    // }

    public function detail(int $popk, int $part, Request $request)
    {
        $dt = DB::table('po')->where('popk', $popk)->first();
        if (!$dt) abort(404);

        $activeSizes = [];

        for ($i = 1; $i <= 40; $i++) {
            $size = $dt->{"size$i"} ?? null;

            if (!empty($size)) {
                $activeSizes[$i] = $size;
            }
        }

        $cr = $request->cr;

        $summary = DB::table('ship')
            ->selectRaw(
                "popk, SUM(pcs) as pcs, " .
                collect(range(1, 40))->map(fn($i) => "SUM(qty$i) as qty$i")->implode(', ')
            )
            ->where('popk', $popk)
            ->first();

        // ================= DETAIL FIXED =================
        $details = DB::table('ship')
            ->where('popk', $popk)
            ->where('part', $part)
            ->orderByDesc('tanggal')
            ->get();

        // ================= SIZE ARRAY =================
        $orderQty = [];
        $readyQty = [];
        $diffQty = [];

        for ($i = 1; $i <= 40; $i++) {
            $orderQty[$i] = $dt->{"qty$i"} ?? 0;
            $readyQty[$i] = $summary->{"qty$i"} ?? 0;
            $diffQty[$i] = $readyQty[$i] - $orderQty[$i];
        }

        $dt3 = DB::table('ship')
            ->selectRaw(
                "SUM(pcs) as pcs, SUM(jmlpcs) as ship, SUM(pcsp) as pcsp, " .
                    collect(range(1, 40))->map(fn($i) => "SUM(qty$i) as qty$i")->implode(', ') . ', ' .
                    collect(range(1, 40))->map(fn($i) => "SUM(qtyp$i) as qtyp$i")->implode(', ')
            )
            ->where('popk', $popk)
            ->first();

        $tctnp      = $dt->ctn ?? 0;
        $tctna      = $dt3->ship ?? 0;
        $balanceCtn = $tctna - $tctnp;

        $totalBalance = ($summary->pcs ?? 0) - $dt->qty;

        $customer = $dt->customer;
        return view('menu.stuffing.detail', compact(
            'dt',
            'activeSizes',
            'summary',
            'orderQty',
            'readyQty',
            'diffQty',
            'totalBalance',
            'cr',
            'details',
            'detailShipping',
            'tctnp',
            'tctna',
            'balanceCtn',
        ));
    }
}
