{{-- menu.transfer.partials.breakdown_summary --}}
@php
    $activeSizes            = $activeSizes ?? [];
    $orderQty               = $orderQty ?? [];
    $manualQty              = $manualQty ?? [];
    $manualTotalPcs         = $manualTotalPcs ?? 0;
    $barcodeQty             = $barcodeQty ?? [];
    $barcodeTotalPcs        = $barcodeTotalPcs ?? 0;
    $diffQty                = $diffQty ?? [];
    $totalBalance           = $totalBalance ?? 0;
    $finishingQty           = $finishingQty ?? [];
    $finishingTotalPcs      = $finishingTotalPcs ?? 0;
    $barcodeLineBreakdown   = $barcodeLineBreakdown ?? [];   // BARU
    $finishingLineBreakdown = $finishingLineBreakdown ?? []; // BARU

    $mapChildren = fn ($breakdown) => collect($breakdown)->map(fn ($l) => [
        'label'  => $l['linenm'],
        'values' => $l['qtyPerSize'],
        'total'  => $l['total'],
    ])->all();

    $columns = collect($activeSizes)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()->all();

    $rows = [
        ['type' => 'primary', 'label' => 'Order Quantity', 'values' => $orderQty, 'total' => $dt->qty ?? 0],
        ['type' => 'normal', 'label' => 'Transfer To Finishing', 'values' => $finishingQty, 'total' => $finishingTotalPcs, 'children' => $mapChildren($finishingLineBreakdown)],
        ['type' => 'normal', 'label' => 'Polibag Quantity', 'badge' => ['text' => 'Manual', 'bg' => 'bg-primary-subtle', 'color' => 'text-primary'], 'values' => $manualQty, 'total' => $manualTotalPcs],
        ['type' => 'normal', 'label' => 'Polibag Quantity', 'badge' => ['text' => 'Barcode', 'bg' => 'bg-secondary-subtle', 'color' => 'text-secondary'], 'values' => $barcodeQty, 'total' => $barcodeTotalPcs, 'children' => $mapChildren($barcodeLineBreakdown)],

        ['type' => 'balance', 'label' => 'Balance (+/-)', 'values' => $diffQty, 'total' => $totalBalance],
    ];
@endphp

<x-details.breakdown-table
    :secsz="$dt->secsz ?? null"
    :columns="$columns"
    :rows="$rows"
/>