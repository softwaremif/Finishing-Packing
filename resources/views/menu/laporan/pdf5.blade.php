@php
    // Semua data pdf5 ada di $gabData (hasil buildGab5Data)
    $entityRows    = $gabData['entityRows'] ?? [];
    $grandTotal    = $gabData['grandTotal'] ?? [];
    $ratioRows     = $gabData['ratioRows'] ?? [];
    $tpcsp         = $gabData['tpcsp'] ?? 0;
    $normalDetails = $gabData['normalDetails'] ?? [];
    $mixedRows     = $gabData['mixedRows'] ?? [];

    // Ambil hanya size yang aktif (berdasarkan header PO)
    $activeSizes = [];
    for ($i = 1; $i <= 40; $i++) {
        if (!empty($dt->{"size{$i}"})) {
            $activeSizes[] = $i;
        }
    }

    $cjml    = count($activeSizes);
    $cheader = $cjml + 1;

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
        <th rowspan="2"></th>
        <th rowspan="2">O/S</th>
        <th colspan="{{ $cjml }}">Size & Qty</th>
        <th rowspan="2">Total</th>
    </tr>
    <tr>
        @foreach($activeSizes as $i)
            <th>{{ $dt->{"size{$i}"} ?? '' }}</th>
        @endforeach
    </tr>

    {{-- Breakdown per entitas (secsz atau customer) --}}
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

    {{-- N.W per entitas --}}
    @foreach ($entityRows as $row)
        <tr>
            <th>N.W</th>
            <th>{{ $row['label'] }}</th>
            @foreach($activeSizes as $i)<td>{{ $row['nw'][$i] ?? '' }}</td>@endforeach
            <td></td>
        </tr>
    @endforeach

    {{-- G.W per entitas --}}
    @foreach ($entityRows as $row)
        <tr>
            <th>G.W</th>
            <th>{{ $row['label'] }}</th>
            @foreach($activeSizes as $i)<td>{{ $row['gw'][$i] ?? '' }}</td>@endforeach
            <td></td>
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

{{-- Detail Packing: Size/Ratio per entitas --}}
<div align="center" class="ftitle">Detail Packing</div>
<table align="center" width="100%" border="1" id="hor-minimalist-a" style="font-family:Book Antiqua; font-size:12px;">
    <tr align="center" bgcolor="#CCCCCC">
        <th rowspan="2"></th>
        <th colspan="{{ $cjml }}">Size / Ratio</th>
        <th rowspan="2">Total</th>
    </tr>
    <tr>
        @foreach($activeSizes as $i)
            <th>{{ $dt2->{"size{$i}"} ?? '' }}</th>
        @endforeach
    </tr>

    @foreach ($ratioRows as $row)
        <tr>
            <th>{{ $row['label'] }}</th>
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

{{-- Detail Packing: per size group per entitas (urut < 21) --}}
<table border="1" align="center" width="100%" id="hor-minimalist-a" style="font-family:Book Antiqua; font-size:12px;">
    <tr align="center" bgcolor="#CCCCCC">
        <th width="6%">Size</th>
        <th width="6%">CTN</th>
        <th width="6%">PCS</th>
        <th width="82%">CARTON NO</th>
    </tr>

    @foreach ($normalDetails as $entityBlock)
        <tr>
            <th colspan="3">{{ $entityBlock['label'] }}</th>
            <td></td>
        </tr>

        @foreach ($entityBlock['groups'] as $group)
            <tr>
                <th>{{ $group['size'] }}</th>
                <td align="center">{{ $group['ctn'] }}</td>
                <td align="center">{{ $group['pcsp'] }}</td>
                <td>
                    @foreach ($group['cartonRows'] as $c)
                        <input type="text" value="{{ $c['label'] }}" style="{{ $c['style'] }}" readonly>
                    @endforeach
                </td>
            </tr>
        @endforeach
    @endforeach

    {{-- Mixed carton (urut = 21): carton yang berisi lebih dari 1 entitas/size --}}
    @foreach ($mixedRows as $mixed)
        <tr>
            <th colspan="2">
                @foreach ($mixed['labelLines'] as $line)
                    {{ $line }}<hr>
                @endforeach
            </th>
            <td align="center">
                @foreach ($mixed['pcsLines'] as $pcsLine)
                    {{ $pcsLine }}<hr>
                @endforeach
            </td>
            <td>
                <input type="text" value="{{ $mixed['cartonInput']['label'] }}" style="{{ $mixed['cartonInput']['style'] }}" readonly>
            </td>
        </tr>
    @endforeach
</table>