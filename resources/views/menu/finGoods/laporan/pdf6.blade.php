@php
    // Data pdf6 dari $gabData (hasil buildGab6Data)
    $detailByCustomer = $gabData['detailByCustomer'] ?? [];
    $mixedRows        = $gabData['mixedRows'] ?? [];
@endphp

<div class="ftitle">Detail Finished Goods</div>
<table class="print-table">
    <tr>
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
                <td class="cell-cartons">
                    @foreach ($group['cartonRows'] as $c)
                        <input type="text" class="carton-input" value="{{ $c['label'] }}" style="{{ $c['style'] }}" data-carton="{{ $c['carton'] }}" data-group="{{ $c['group'] }}" readonly>
                    @endforeach
                </td>
            </tr>
        @endforeach
    @endforeach

    {{-- Mixed carton (urut = 21) lintas customer untuk material yang sama --}}
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