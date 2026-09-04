@php
    // Data pdf1 dari $gabData (hasil buildGab1Data)
    $detailMaterialRows = $gabData['detailMaterialRows'] ?? [];
    $tpcsp              = $gabData['tpcsp'] ?? 0;
    $ctn                = $gabData['ctn'] ?? 0;
    $cartonRows         = $gabData['cartonRows'] ?? [];

    // $activeSizes ([index => label]), $cjml, $cheader dikirim controller.
@endphp

<div class="ftitle">Detail Finished Goods</div>
<table class="print-table">
    <tr>
        <th rowspan="2" width="25%">Material</th>
        <th colspan="{{ $cjml }}" width="69%">Size / Ratio</th>
        <th rowspan="2" width="6%">Total</th>
    </tr>
    <tr>
        @foreach($activeSizes as $i => $label)
            <th>{{ $dt2->{"size{$i}"} ?? $label }}</th>
        @endforeach
    </tr>

    @foreach ($detailMaterialRows as $row)
        <tr>
            <th>{{ $row['material'] }}</th>
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
    <tr>
        <th>{{ $ctn }}</th>
        <th>{{ $tpcsp }}</th>
        <td class="cell-cartons">
            @foreach ($cartonRows as $c)
                <input type="text" class="carton-input" value="{{ $c['label'] }}" style="{{ $c['style'] }}" data-carton="{{ $c['carton'] }}" data-group="{{ $c['group'] }}" readonly>
            @endforeach
        </td>
    </tr>
</table>