@php
    // Data umum (dt2 = Order Qty per size, dt3 = Ship Qty per size) sudah tersedia dari controller
    // Data spesifik gab=3 ada di $gabData (hasil buildGab3Data)
    $ratioData     = $gabData['ratioData'] ?? [];
    $tpcsp         = $gabData['tpcsp'] ?? 0;
    $detailPacking = $gabData['detailPacking'] ?? [];

    // Ambil hanya size yang aktif (berdasarkan header PO)
    $activeSizes = [];
    for ($i = 1; $i <= 40; $i++) {
        if (!empty($dt->{"size{$i}"})) {
            $activeSizes[] = $i;
        }
    }

    $cjml    = count($activeSizes);
    $cheader = $cjml + 1;

    // Order Qty & Ship Qty per size
    $orderQty = [];
    $shipQty  = [];
    $diffQty  = [];
    $pctQty   = [];
    foreach ($activeSizes as $i) {
        $a = $dt2->{"qty{$i}"} ?? 0;
        $b = $dt3->{"qty{$i}"} ?? 0;
        $orderQty[$i] = $a == 0 ? '' : $a;
        $shipQty[$i]  = $b == 0 ? '' : $b;
        $d = $b - $a;
        $diffQty[$i] = $d != 0 ? $d : '';
        $pctQty[$i]  = !empty($a) ? round($d / $a * 100, 2) : '';
    }
    $orderTotal = $dt2->qty ?? 0;
    $shipTotal  = $dt3->pcs ?? 0;
    $diffTotal  = $shipTotal - $orderTotal;
    $pctTotal   = !empty($orderTotal) ? round($diffTotal / $orderTotal * 100, 2) : 0;
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
        @foreach($activeSizes as $i)<td>{{ $orderQty[$i] }}</td>@endforeach
        <th>{{ $orderTotal }}</th>
    </tr>
    <tr>
        <th>Ship Qty</th>
        @foreach($activeSizes as $i)<td>{{ $shipQty[$i] }}</td>@endforeach
        <th>{{ $shipTotal }}</th>
    </tr>
    <tr>
        <th>+/-</th>
        @foreach($activeSizes as $i)<td>{{ $diffQty[$i] }}</td>@endforeach
        <th>{{ $diffTotal }}</th>
    </tr>
    <tr>
        <th>%</th>
        @foreach($activeSizes as $i)
            <td>{{ $pctQty[$i] !== '' ? number_format($pctQty[$i], 2, ',', '.') : '' }}</td>
        @endforeach
        <th>{{ number_format($pctTotal, 2, ',', '.') }}</th>
    </tr>
</table>

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

    @foreach ($ratioData as $row)
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

<table width="100%" align="center" border="1" id="hor-minimalist-a" style="font-family:Book Antiqua; font-size:12px;">
    <tr align="center" bgcolor="#CCCCCC">
        <th width="6%">CTN</th>
        <th width="6%">PCS</th>
        <th width="88%">CARTON NO</th>
    </tr>

    @foreach ($detailPacking as $group)
        <tr>
            <th>{{ $group['ctn'] }}</th>
            <th>{{ $group['pcsp'] }}</th>
            <td>
                @foreach ($group['cartonRows'] as $c)
                    <input type="text" value="{{ $c['label'] }}" style="{{ $c['style'] }}" readonly>
                @endforeach
            </td>
        </tr>
    @endforeach
</table>