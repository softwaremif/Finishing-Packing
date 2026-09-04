@php
    // Data pdf8 dari $gabData (hasil buildGab8Data)
    $packingSummary = $gabData['packingSummary'] ?? [];
    $orderRow       = $packingSummary['orderRow'] ?? ['qty' => [], 'total' => 0];
    $customerBlocks = $packingSummary['customerBlocks'] ?? [];
    $summaryGrand   = $packingSummary['grandTotal'] ?? ['qty' => [], 'ctn' => 0, 'totalPcs' => 0];

    // $activeSizes ([index => label]), $cjml dikirim controller.
@endphp

<div class="ftitle">Detail Finished Goods</div>
<table class="print-table">
    <tr>
        <th rowspan="2" width="35%">Carton <br> No.</th>
        <th colspan="{{ $cjml }}" width="35%">Size & Qty</th>
        <th rowspan="2" width="4%">Pcs</th>
        <th rowspan="2" width="4%">CTN</th>
        <th rowspan="2" width="4%">Total <br> Pcs</th>
        <th rowspan="2" width="5%">Carton <br> Dimension</th>
        <th rowspan="2" width="5%">NW</th>
        <th rowspan="2" width="5%">GW</th>
    </tr>
    <tr>
        @foreach($activeSizes as $i => $label)
            <th>{{ $dt2->{"size{$i}"} ?? $label }}</th>
        @endforeach
    </tr>

    {{-- Order quantity keseluruhan untuk material --}}
    <tr>
        <th>Order Qty</th>
        @foreach($activeSizes as $i => $label)<td>{{ $orderRow['qty'][$i] ?? '' }}</td>@endforeach
        <th>{{ $orderRow['total'] }}</th>
        <td colspan="5"></td>
    </tr>

    {{-- Per customer: baris order qty, lalu baris detail per grup carton --}}
    @foreach ($customerBlocks as $block)
        <tr>
            <th>{{ $block['label'] }}</th>
            @foreach($activeSizes as $i => $label)<td>{{ $block['qty'][$i] ?? '' }}</td>@endforeach
            <td align="center">{{ $block['order_total'] }}</td>
            <td colspan="5"></td>
        </tr>

        @foreach ($block['cartonGroups'] as $group)
            <tr>
                <th>
                    @foreach ($group['cartonRows'] as $c)
                        <input type="text" class="carton-input" value="{{ $c['label'] }}" style="{{ $c['style'] }}" data-carton="{{ $c['carton'] }}" data-group="{{ $c['group'] }}" readonly>
                    @endforeach
                </th>
                @foreach($activeSizes as $i => $label)<td>{{ $group['qty'][$i] ?? '' }}</td>@endforeach
                <th>{{ $group['pcsp'] }}</th>
                <th>{{ $group['ctn'] }}</th>
                <th>{{ $group['totalPcs'] }}</th>
                <td>{{ $group['meas'] }}</td>
                <td>{{ $group['nw'] }}</td>
                <td>{{ $group['gw'] }}</td>
            </tr>
        @endforeach
    @endforeach

    <tr>
        <th>Grand Total</th>
        @foreach($activeSizes as $i => $label)<td>{{ $summaryGrand['qty'][$i] ?? '' }}</td>@endforeach
        <td></td>
        <th>{{ $summaryGrand['ctn'] }}</th>
        <th>{{ $summaryGrand['totalPcs'] }}</th>
        <td colspan="5"></td>
    </tr>
</table>