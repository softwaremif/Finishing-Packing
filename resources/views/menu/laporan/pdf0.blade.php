@php
    $sizes = [];

    for ($i = 1; $i <= 40; $i++) {

        $size = $dt2->{"size{$i}"} ?? '';

        if (!empty($size)) {

            $order = $dt2->{"qty{$i}"} ?? 0;
            $ship  = $dt3->{"qty{$i}"} ?? 0;

            $sizes[] = [
                'size'  => $size,
                'order' => $order,
                'ship'  => $ship,
                'diff'  => $ship - $order,
                'pct'   => $order > 0 ? (($ship - $order) / $order) * 100 : 0,
            ];
        }
    }

    $cheader = count($sizes) + 1;

    $pcsTotal = $dt3->pcs ?? 0;
    $pcsDiff  = $pcsTotal - ($dt2->qty ?? 0);
    $ppcs     = ($dt2->qty ?? 0) > 0 ? ($pcsDiff / $dt2->qty) * 100 : 0;
@endphp

<div align="center" class="ftitle">Breakdown Size & Qty</div>

<table align="center" width="100%" border="1" id="hor-minimalist-a"
       style="font-family:Book Antiqua;font-size:12px;">

    <tr align="center" bgcolor="#CCCCCC">
        <th colspan="{{ $cheader }}">Size & Qty</th>
        <th rowspan="2">Total</th>
    </tr>

    <tr>
        <th>Size</th>
        @foreach($sizes as $s)
            <th>{{ $s['size'] }}</th>
        @endforeach
    </tr>

    <tr>
        <th>Order Qty</th>
        @foreach($sizes as $s)
            <td>{{ $s['order'] ?: '' }}</td>
        @endforeach
        <th>{{ $dt2->qty ?? 0 }}</th>
    </tr>

    <tr>
        <th>Ship Qty</th>
        @foreach($sizes as $s)
            <td>{{ $s['ship'] ?: '' }}</td>
        @endforeach
        <th>{{ $pcsTotal }}</th>
    </tr>

    <tr>
        <th>+/-</th>
        @foreach($sizes as $s)
            <td>{{ $s['diff'] ?: '' }}</td>
        @endforeach
        <th>{{ $pcsDiff }}</th>
    </tr>

    <tr>
        <th>%</th>
        @foreach($sizes as $s)
            <td>{{ $s['pct'] != 0 ? number_format($s['pct'],2,',','.') : '' }}</td>
        @endforeach
        <th>{{ number_format($ppcs,2,',','.') }}</th>
    </tr>

    <tr align="center" bgcolor="#CCCCCC">
        <th colspan="{{ count($sizes)+2 }}"></th>
    </tr>

    <tr>
        <th>N.W</th>
        @foreach($nwList as $row)
            <td>{{ $row->nw }}</td>
        @endforeach
    </tr>

    <tr>
        <th>G.W</th>
        @foreach($gwList as $row)
            <td>{{ $row->gw }}</td>
        @endforeach
    </tr>

</table>

<div align="center" class="ftitle">Detail Packings</div>
<table border="1" align="center" width="100%" id="hor-minimalist-a" style="font-family:Book Antiqua; font-size:12px;">
    <tr align="center" bgcolor="#CCCCCC">
        <th width="6%">Size</th>
        <th width="6%">CTN</th>
        <th width="6%">PCS</th>
        <th width="82%">CARTON NO</th>
    </tr>
    @foreach ($detailPacking as $group)
        <tr>
            <th>{{ $group['size'] }}</th>
            <td align="center">{{ $group['ctn'] }}</td>
            <td align="center">{{ $group['pcsp'] }}</td>
            <td>
                @foreach ($group['cartonRows'] as $c)
                    <input type="text" value=" {{ $c['label'] }} " style="{{ $c['style'] }}" readonly>
                @endforeach
            </td>
        </tr>
    @endforeach
</table>