@php
    // Data pdf5 dari $gabData (hasil buildGab5Data)
    $ratioRows     = $gabData['ratioRows'] ?? [];
    $tpcsp         = $gabData['tpcsp'] ?? 0;
    $normalDetails = $gabData['normalDetails'] ?? [];
    $mixedRows     = $gabData['mixedRows'] ?? [];

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

    @foreach ($ratioRows as $row)
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

{{-- Per size group per entitas (urut < 21) + mixed carton (urut = 21) --}}
<table class="print-table">
    <tr>
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
                <td class="cell-cartons">
                    @foreach ($group['cartonRows'] as $c)
                        <input type="text" class="carton-input" value="{{ $c['label'] }}" style="{{ $c['style'] }}" data-carton="{{ $c['carton'] }}" data-group="{{ $c['group'] }}" readonly>
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
            <td class="cell-cartons">
                <input type="text" class="carton-input" value="{{ $mixed['cartonInput']['label'] }}" style="{{ $mixed['cartonInput']['style'] }}" data-carton="{{ $mixed['cartonInput']['carton'] }}" data-group="{{ $mixed['cartonInput']['group'] }}" readonly>
            </td>
        </tr>
    @endforeach
</table>