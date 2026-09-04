{{-- menu.transfer-finishing.partials.breakdown_summary --}}
@php
    $sizes           = $sizes ?? collect();
    $orderQty        = $orderQty ?? [];
    $orderTotalPcs   = $orderTotalPcs ?? 0;
    $rqQty           = $rqQty ?? [];
    $rqTotalPcs      = $rqTotalPcs ?? 0;
    $barcodeQty      = $barcodeQty ?? [];
    $barcodeTotalPcs = $barcodeTotalPcs ?? 0;
    $manualQty       = $manualQty ?? [];
    $manualTotalPcs  = $manualTotalPcs ?? 0;
    $lineBreakdown   = $lineBreakdown ?? [];
    $rqLineBreakdown = $rqLineBreakdown ?? [];
    $balanceTotal    = ($manualTotalPcs + $barcodeTotalPcs) - $orderTotalPcs;

    $mapChildren = fn ($breakdown) => collect($breakdown)->map(fn ($l) => [
        'label'  => $l['linenm'],
        'values' => $l['qtyPerSize'],
        'total'  => $l['total'],
    ])->all();

    $balanceValues = [];
    foreach ($sizes as $s) {
        $balanceValues[$s->mopdtpk] = (($manualQty[$s->mopdtpk] ?? 0) + ($barcodeQty[$s->mopdtpk] ?? 0)) - ($orderQty[$s->mopdtpk] ?? 0);
    }

    $columns = $sizes->map(fn ($s) => ['key' => $s->mopdtpk, 'label' => $s->ukuran])->values()->all();

    $rows = [
        ['type' => 'primary', 'label' => 'Order Quantity', 'values' => $orderQty, 'total' => $orderTotalPcs],
        ['type' => 'normal', 'label' => 'R+Q', 'values' => $rqQty, 'total' => $rqTotalPcs, 'children' => $mapChildren($rqLineBreakdown)],
        ['type' => 'normal', 'label' => 'Transfer To Finishing', 'badge' => ['text' => 'Barcode', 'bg' => 'bg-secondary-subtle', 'color' => 'text-secondary'], 'values' => $barcodeQty, 'total' => $barcodeTotalPcs, 'children' => $mapChildren($lineBreakdown)],
        ['type' => 'normal', 'label' => 'Transfer To Finishing', 'badge' => ['text' => 'Manual', 'bg' => 'bg-primary-subtle', 'color' => 'text-primary'], 'values' => $manualQty, 'total' => $manualTotalPcs],
        ['type' => 'balance', 'label' => 'Balance (+/-)', 'values' => $balanceValues, 'total' => $balanceTotal],
    ];
@endphp

<x-details.breakdown-table
    :secsz="$mop->secsz ?? null"
    :columns="$columns"
    :rows="$rows"
/>