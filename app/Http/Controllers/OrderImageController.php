<?php

namespace App\Http\Controllers;

use App\Services\OrderImageService;
use Illuminate\Http\Request;

class OrderImageController extends Controller
{
    /** @var OrderImageService */
    protected $orderImageService;

    public function __construct(OrderImageService $orderImageService)
    {
        $this->orderImageService = $orderImageService;
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'ordpks' => 'required|string',
        ]);

        $ordpks = collect(explode(',', $validated['ordpks']))
            ->map(function ($v) { return (int) trim($v); })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($ordpks)) {
            return response()->json([], 422);
        }

        $images = $this->orderImageService->resolve($ordpks);

        return response()->json($images);
    }

    public function show($ordpk)
    {
        $ordpk = (int) $ordpk;
        $images = $this->orderImageService->resolve([$ordpk]);

        return response()->json([
            'ordpk' => $ordpk,
            'image' => isset($images[$ordpk]) ? $images[$ordpk] : asset('public/css/images/no-img.png'),
        ]);
    }
}