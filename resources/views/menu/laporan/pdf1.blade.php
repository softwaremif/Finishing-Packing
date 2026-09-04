@php
    // Semua data pdf1 ada di $gabData
    $materialRows       = $gabData['materialRows'] ?? [];
    $grandTotal         = $gabData['grandTotal'] ?? [];
    $detailMaterialRows = $gabData['detailMaterialRows'] ?? [];
    $tpcsp              = $gabData['tpcsp'] ?? 0;
    $ctn                = $gabData['ctn'] ?? 0;
    $cartonRows         = $gabData['cartonRows'] ?? [];

    // Ambil hanya size yang aktif
    $activeSizes = [];

    for ($i = 1; $i <= 40; $i++) {
        if (!empty($dt2->{"size{$i}"})) {
            $activeSizes[] = $i;
        }
    }

    $cjml = count($activeSizes);
    $cheader = $cjml + 1;

    // Array kosong untuk grand total
    $emptyRow = [];
    foreach ($activeSizes as $i) {
        $emptyRow[$i] = '';
    }

    $gt = $grandTotal ?: [
        'order'       => $emptyRow,
        'ship'        => $emptyRow,
        'diff'        => $emptyRow,
        'pct'         => $emptyRow,
        'order_total' => 0,
        'ship_total'  => 0,
        'diff_total'  => 0,
        'pct_total'   => 0,
    ];
@endphp

<div align="center" class="ftitle">Breakdown Size & Qty</div>
<table align="center" width="100%" border="1" id="hor-minimalist-a" style="font-family:Book Antiqua; font-size:12px;">
    <tr align="center" bgcolor="#CCCCCC">
        <th rowspan="2" width="25%">Material</th>
        <th rowspan="2" width="6%">O/S</th>
        <th colspan="{{ $cjml }}" width="63%">Size & Qty</th>
        <th rowspan="2" width="6%">Total</th>
    </tr>
    <tr>
        @foreach($activeSizes as $i)
            <th>{{ $dt->{"size{$i}"} ?? '' }}</th>
        @endforeach
    </tr>

    {{-- Breakdown per material --}}
    @foreach ($materialRows as $row)
        <tr>
            <th rowspan="4">{{ $row['material'] }}</th>
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

<div align="center" class="ftitle">Detail Packing</div>
<table align="center" width="100%" border="1" id="hor-minimalist-a" style="font-family:Book Antiqua; font-size:12px;">
    <tr align="center" bgcolor="#CCCCCC">
        <th rowspan="2" width="25%">Material</th>
        <th colspan="{{ $cjml }}" width="69%">Size / Ratio</th>
        <th rowspan="2" width="6%">Total</th>
    </tr>
    <tr>
        @foreach($activeSizes as $i)
            <th>{{ $dt2->{"size{$i}"} ?? '' }}</th>
        @endforeach
    </tr>

    @foreach ($detailMaterialRows as $row)
        <tr>
            <th>{{ $row['material'] }}</th>
            @foreach($activeSizes as $i)<td>{{ $row['qty'][$i] ?? '' }}</td>@endforeach
            <th align="right">{{ $row['pcsp'] }}</th>
        </tr>
    @endforeach

    <tr align="center" bgcolor="#CCCCCC">
        <th colspan="{{ $cheader }}">Total</th>
        <th>{{ $tpcsp }}</th>
    </tr>
</table>
<br>

<table width="100%" align="center" border="1" id="hor-minimalist-a" style="font-family:Book Antiqua; font-size:12px;">
    <tr align="center" bgcolor="#CCCCCC">
        <th width="6%">CTN</th>
        <th width="6%">PCS</th>
        <th width="88%">CARTON NO</th>
    </tr>
    <tr>
        <th>{{ $ctn }}</th>
        <th>{{ $tpcsp }}</th>
        <td>
            @foreach ($cartonRows as $c)
                <input type="text" value="{{ $c['label'] }}" style="{{ $c['style'] }}" readonly>
            @endforeach
        </td>
    </tr>
</table>