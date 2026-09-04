@php
    // Semua data pdf7 ada di $gabData (hasil buildGab7Data)
    $orderShip    = $gabData['orderShip'] ?? [];
    $nwCells      = $gabData['nwCells'] ?? [];
    $gwCells      = $gabData['gwCells'] ?? [];
    $normalGroups = $gabData['normalGroups'] ?? [];
    $mixedRows    = $gabData['mixedRows'] ?? [];

    // Ambil hanya size yang aktif
    $activeSizes = [];
    for ($i = 1; $i <= 40; $i++) {
        if (!empty($dt2->{"size{$i}"} ?? $dt->{"size{$i}"} ?? null)) {
            $activeSizes[] = $i;
        }
    }
    $cjml    = count($activeSizes);
    $cheader = $cjml + 1;
@endphp

<div align="center" class="ftitle">Breakdown Size & Qty</div>
<table align="center" width="100%" border="1" id="hor-minimalist-a" style="font-family:Book Antiqua; font-size:12px;">
    <tr align="center" bgcolor="#CCCCCC">
        <th colspan="{{ $cheader }}">Size & Qty</th>
        <th rowspan="2">Total</th>
    </tr>
    <tr>
        <th>Size</th>
        @foreach($activeSizes as $i)
            <th>{{ $dt2->{"size{$i}"} ?? '' }}</th>
        @endforeach
    </tr>
    <tr>
        <th>Order Qty</th>
        @foreach($activeSizes as $i)<td>{{ $orderShip['order'][$i] ?? '' }}</td>@endforeach
        <th>{{ $orderShip['order_total'] ?? 0 }}</th>
    </tr>
    <tr>
        <th>Ship Qty</th>
        @foreach($activeSizes as $i)<td>{{ $orderShip['ship'][$i] ?? '' }}</td>@endforeach
        <th>{{ $orderShip['ship_total'] ?? 0 }}</th>
    </tr>
    <tr>
        <th>+/-</th>
        @foreach($activeSizes as $i)<td>{{ $orderShip['diff'][$i] ?? '' }}</td>@endforeach
        <th>{{ $orderShip['diff_total'] ?? 0 }}</th>
    </tr>
    <tr>
        <th>%</th>
        @foreach($activeSizes as $i)
            <td>{{ ($orderShip['pct'][$i] ?? '') !== '' ? number_format($orderShip['pct'][$i], 2, ',', '.') : '' }}</td>
        @endforeach
        <th>{{ number_format($orderShip['pct_total'] ?? 0, 2, ',', '.') }}</th>
    </tr>

    <tr align="center" bgcolor="#CCCCCC">
        <th colspan="{{ $cjml + 2 }}"></th>
    </tr>

    {{-- N.W / G.W — cells are pre-rendered HTML (may include <b>/<hr> markup
         when a carton mixes multiple sizes), so they're output raw. --}}
    <tr>
        <th>N.W</th>
        @foreach ($nwCells as $cell)
            <td>{!! $cell !!}</td>
        @endforeach
    </tr>
    <tr>
        <th>G.W</th>
        @foreach ($gwCells as $cell)
            <td>{!! $cell !!}</td>
        @endforeach
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

    {{-- Normal size groups (urut < 21) --}}
    @foreach ($normalGroups as $group)
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

    {{-- Mixed carton (urut = 21): one carton holding multiple sizes --}}
    @foreach ($mixedRows as $mixed)
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
</table>