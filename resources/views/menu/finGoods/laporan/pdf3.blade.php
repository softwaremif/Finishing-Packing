@php
    // Data pdf3 dari $gabData (hasil buildGab3Data)
    $ratioData     = $gabData['ratioData'] ?? [];
    $tpcsp         = $gabData['tpcsp'] ?? 0;
    $detailPacking = $gabData['detailPacking'] ?? [];

    // $activeSizes ([index => label]), $cjml, $cheader dikirim controller.
@endphp

<div class="ftitle">Detail Finished Goods</div>
<table class="print-table">
    <tr>
        <th rowspan="2"></th>
        <th colspan="{{ $cjml }}">Size / Ratio</th>
        <th rowspan="2">Total</th>
    </tr>
    <tr>
        @foreach($activeSizes as $i => $label)
            <th>{{ $dt2->{"size{$i}"} ?? $label }}</th>
        @endforeach
    </tr>

    @foreach ($ratioData as $row)
        <tr>
            <th>{{ $row['label'] }}</th>
            @foreach($activeSizes as $i => $label)<td>{{ $row['qty'][$i] ?? '' }}</td>@endforeach
            <th class="cell-total">{{ $row['pcsp'] }}</th>
        </tr>
    @endforeach

    <tr>
        <th colspan="{{ $cheader }}">Total</th>
        <th>{{ $tpcsp }}</th>
    </tr>
</table>
<br>

<table class="print-table">
    <tr>
        <th width="6%">CTN</th>
        <th width="6%">PCS</th>
        <th width="88%">CARTON NO</th>
    </tr>

    @foreach ($detailPacking as $group)
        <tr>
            <th>{{ $group['ctn'] }}</th>
            <th>{{ $group['pcsp'] }}</th>
            <td class="cell-cartons">
                @foreach ($group['cartonRows'] as $c)
                    <input type="text" class="carton-input" value="{{ $c['label'] }}" style="{{ $c['style'] }}" data-carton="{{ $c['carton'] }}" data-group="{{ $c['group'] }}" readonly>
                @endforeach
            </td>
        </tr>
    @endforeach
</table>