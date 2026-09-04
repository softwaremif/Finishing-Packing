@php
    // Semua data pdf9 ada di $gabData (hasil buildGab9Data)
    $materialRows       = $gabData['materialRows'] ?? [];
    $nwByMaterial       = $gabData['nwByMaterial'] ?? [];
    $gwByMaterial       = $gabData['gwByMaterial'] ?? [];
    $grandTotal         = $gabData['grandTotal'] ?? [];
    $detailByMaterial   = $gabData['detailByMaterial'] ?? [];
    $crossMaterialMixed = $gabData['crossMaterialMixed'] ?? [];

    // Ambil hanya size yang aktif
    $activeSizes = [];
    for ($i = 1; $i <= 40; $i++) {
        if (!empty($dt->{"size{$i}"})) {
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

<div align="center" class="ftitle">Breakdown Size & Qty</div>
<table align="center" width="100%" border="1" id="hor-minimalist-a" style="font-family:Book Antiqua; font-size:12px;">
    <tr align="center" bgcolor="#CCCCCC">
        <th rowspan="2" width="25%"></th>
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

    {{-- N.W per material --}}
    @foreach ($nwByMaterial as $row)
        <tr>
            <th>N.W</th>
            <th>{{ $row['material'] }}</th>
            @foreach($activeSizes as $i)<td>{{ $row['nw'][$i] ?? '' }}</td>@endforeach
            <td></td>
        </tr>
    @endforeach

    {{-- G.W per material --}}
    @foreach ($gwByMaterial as $row)
        <tr>
            <th>G.W</th>
            <th>{{ $row['material'] }}</th>
            @foreach($activeSizes as $i)<td>{{ $row['gw'][$i] ?? '' }}</td>@endforeach
            <td></td>
        </tr>
    @endforeach

    {{-- Grand Total --}}
    <tr align="center" bgcolor="#CCCCCC">
        <th colspan="{{ $cjml + 3 }}"></th>
    </tr>
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
<table width="100%" align="center" border="1" id="hor-minimalist-a" style="font-family:Book Antiqua; font-size:12px;">
    <tr align="center" bgcolor="#CCCCCC">
        <th width="5%">Size</th>
        <th width="5%">CTN</th>
        <th width="5%">PCS</th>
        <th width="80%">CARTON NO</th>
    </tr>

    @foreach ($detailByMaterial as $matBlock)
        <tr>
            <th colspan="3">{{ $matBlock['material'] }}</th>
            <td></td>
        </tr>

        {{-- Normal size groups (urut < 21) --}}
        @foreach ($matBlock['groups'] as $group)
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

        {{-- Mixed carton within this material (urut = 21) --}}
        @foreach ($matBlock['mixedRows'] as $mixed)
            <tr>
                <th colspan="2">
                    @foreach ($mixed['labelLines'] as $idx => $line)
                        {{ $line }} | {{ $mixed['pcsLines'][$idx] ?? '' }}<hr>
                    @endforeach
                </th>
                <td align="center">{{ $mixed['pcsp'] }}</td>
                <td>
                    <input type="text" value="{{ $mixed['cartonInput']['label'] }}" style="{{ $mixed['cartonInput']['style'] }}" readonly>
                </td>
            </tr>
        @endforeach
    @endforeach

    {{-- Cross-material mixed carton (urut = 22): one carton spanning multiple materials --}}
    @foreach ($crossMaterialMixed as $mixed)
        <tr>
            <th colspan="2">
                @foreach ($mixed['materialBlocks'] as $block)
                    <strong>{{ $block['material'] }}</strong><br>
                    @foreach ($block['labelLines'] as $idx => $line)
                        {{ $line }} | {{ $block['pcsLines'][$idx] ?? '' }}<hr>
                    @endforeach
                @endforeach
            </th>
            <td align="center">{{ $mixed['pcsp'] }}</td>
            <td>
                <input type="text" value="{{ $mixed['cartonInput']['label'] }}" style="{{ $mixed['cartonInput']['style'] }}" readonly>
            </td>
        </tr>
    @endforeach
</table>