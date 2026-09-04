@php
    // Semua data pdf8 ada di $gabData (hasil buildGab8Data)
    $packingSummary = $gabData['packingSummary'] ?? [];
    $orderRow       = $packingSummary['orderRow'] ?? ['qty' => [], 'total' => 0];
    $customerBlocks = $packingSummary['customerBlocks'] ?? [];
    $summaryGrand   = $packingSummary['grandTotal'] ?? ['qty' => [], 'ctn' => 0, 'totalPcs' => 0];

    $entityRows = $gabData['entityRows'] ?? [];
    $grandTotal = $gabData['grandTotal'] ?? [];

    // Ambil hanya size yang aktif
    $activeSizes = [];
    for ($i = 1; $i <= 40; $i++) {
        if (!empty($dt2->{"size{$i}"} ?? $dt->{"size{$i}"} ?? null)) {
            $activeSizes[] = $i;
        }
    }
    $cjml = count($activeSizes);

    $emptyRow = [];
    foreach ($activeSizes as $i) {
        $emptyRow[$i] = '';
    }
    $gt = $grandTotal ?: [
        'order' => $emptyRow, 'ship' => $emptyRow, 'diff' => $emptyRow, 'pct' => $emptyRow,
        'order_total' => 0, 'ship_total' => 0, 'diff_total' => 0, 'pct_total' => 0,
    ];
@endphp

<div align="center" class="ftitle">Detail Packing</div>
<table align="center" width="100%" border="1" id="hor-minimalist-a" style="font-family:Book Antiqua; font-size:12px;">
    <tr align="center" bgcolor="#CCCCCC">
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
        @foreach($activeSizes as $i)
            <th>{{ $dt2->{"size{$i}"} ?? '' }}</th>
        @endforeach
    </tr>

    {{-- Overall order quantity for the material --}}
    <tr>
        <th>Order Qty</th>
        @foreach($activeSizes as $i)<td>{{ $orderRow['qty'][$i] ?? '' }}</td>@endforeach
        <th>{{ $orderRow['total'] }}</th>
        <td colspan="5"></td>
    </tr>

    {{-- Per customer: order qty row, then each carton group's detail row --}}
    @foreach ($customerBlocks as $block)
        <tr>
            <th>{{ $block['label'] }}</th>
            @foreach($activeSizes as $i)<td>{{ $block['qty'][$i] ?? '' }}</td>@endforeach
            <td align="center">{{ $block['order_total'] }}</td>
            <td colspan="5"></td>
        </tr>

        @foreach ($block['cartonGroups'] as $group)
            <tr>
                <th>
                    @foreach ($group['cartonRows'] as $c)
                        <input type="text" value="{{ $c['label'] }}" style="{{ $c['style'] }}" readonly>
                    @endforeach
                </th>
                @foreach($activeSizes as $i)<td>{{ $group['qty'][$i] ?? '' }}</td>@endforeach
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
        <th bgcolor="#CCCCCC">Grand Total</th>
        @foreach($activeSizes as $i)<td>{{ $summaryGrand['qty'][$i] ?? '' }}</td>@endforeach
        <td></td>
        <th>{{ $summaryGrand['ctn'] }}</th>
        <th>{{ $summaryGrand['totalPcs'] }}</th>
        <td colspan="5"></td>
    </tr>
</table>

<div align="center" class="ftitle">Breakdown Size & Qty</div>
<table align="center" width="100%" border="1" id="hor-minimalist-a" style="font-family:Book Antiqua; font-size:12px;">
    <tr align="center" bgcolor="#CCCCCC">
        <th rowspan="2"></th>
        <th rowspan="2"></th>
        <th colspan="{{ $cjml }}">Size & Qty</th>
        <th rowspan="2">Total</th>
    </tr>
    <tr>
        @foreach($activeSizes as $i)
            <th>{{ $dt2->{"size{$i}"} ?? '' }}</th>
        @endforeach
    </tr>

    {{-- Breakdown per customer --}}
    @foreach ($entityRows as $row)
        <tr>
            <th rowspan="4">{{ $row['label'] }}</th>
            <th>Order</th>
            @foreach($activeSizes as $i)<td>{{ $row['order'][$i] ?? '' }}</td>@endforeach
            <td align="right">{{ $row['order_total'] }}</td>
        </tr>
        <tr>
            <th>Ship</th>
            @foreach($activeSizes as $i)<td>{{ $row['ship'][$i] ?? '' }}</td>@endforeach
            <td align="right">{{ $row['ship_total'] }}</td>
        </tr>
        <tr>
            <th>+/-</th>
            @foreach($activeSizes as $i)<td>{{ $row['diff'][$i] ?? '' }}</td>@endforeach
            <th>{{ $row['diff_total'] }}</th>
        </tr>
        <tr>
            <th>%</th>
            @foreach($activeSizes as $i)
                <td>{{ ($row['pct'][$i] ?? '') !== '' ? number_format($row['pct'][$i], 2, ',', '.') : '' }}</td>
            @endforeach
            <th>{{ number_format($row['pct_total'], 2, ',', '.') }}</th>
        </tr>
    @endforeach

    {{-- Separator --}}
    <tr align="center" bgcolor="#CCCCCC">
        <th colspan="{{ $cjml + 3 }}"></th>
    </tr>

    {{-- Grand Total --}}
    <tr>
        <th rowspan="4">Total</th>
        <th>Order</th>
        @foreach($activeSizes as $i)<td>{{ $gt['order'][$i] ?? '' }}</td>@endforeach
        <th>{{ $gt['order_total'] }}</th>
    </tr>
    <tr>
        <th>Ship</th>
        @foreach($activeSizes as $i)<td>{{ $gt['ship'][$i] ?? '' }}</td>@endforeach
        <th>{{ $gt['ship_total'] }}</th>
    </tr>
    <tr>
        <th>+/-</th>
        @foreach($activeSizes as $i)<td>{{ $gt['diff'][$i] ?? '' }}</td>@endforeach
        <th>{{ $gt['diff_total'] }}</th>
    </tr>
    <tr>
        <th>%</th>
        @foreach($activeSizes as $i)
            <td>{{ ($gt['pct'][$i] ?? '') !== '' ? number_format($gt['pct'][$i], 2, ',', '.') : '' }}</td>
        @endforeach
        <th>{{ number_format($gt['pct_total'], 2, ',', '.') }}</th>
    </tr>
</table>