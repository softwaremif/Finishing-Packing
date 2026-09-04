@php
    // Semua data pdf6 ada di $gabData (hasil buildGab6Data)
    $entityRows       = $gabData['entityRows'] ?? [];
    $detailByCustomer = $gabData['detailByCustomer'] ?? [];
    $mixedRows        = $gabData['mixedRows'] ?? [];

    // Ambil hanya size yang aktif (berdasarkan header PO)
    $activeSizes = [];
    for ($i = 1; $i <= 40; $i++) {
        if (!empty($dt->{"size{$i}"})) {
            $activeSizes[] = $i;
        }
    }
    $cjml = count($activeSizes);
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

    {{-- Breakdown per customer — no Grand Total block for gab=6 --}}
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
</table>

<div align="center" class="ftitle">Detail Packing</div>
<table border="1" align="center" width="100%" id="hor-minimalist-a" style="font-family:Book Antiqua; font-size:12px;">
    <tr align="center" bgcolor="#CCCCCC">
        <th width="6%">Size</th>
        <th width="6%">CTN</th>
        <th width="6%">PCS</th>
        <th width="82%">CARTON NO</th>
    </tr>

    {{-- Size groups per customer (urut < 21) --}}
    @foreach ($detailByCustomer as $custBlock)
        <tr>
            <th colspan="3">{{ $custBlock['label'] }}</th>
            <td></td>
        </tr>

        @foreach ($custBlock['groups'] as $group)
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

    {{-- Mixed carton (urut = 21) shared across the whole material --}}
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