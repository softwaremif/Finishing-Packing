@php
    // Data pdf7 dari $gabData (hasil buildGab7Data)
    $normalGroups = $gabData['normalGroups'] ?? [];
    $mixedRows    = $gabData['mixedRows'] ?? [];
@endphp

<div class="ftitle">Detail Finished Goods</div>
<table class="print-table">
    <tr>
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
            <td class="cell-cartons">
                @foreach ($group['cartonRows'] as $c)
                    <input type="text" class="carton-input" value="{{ $c['label'] }}" style="{{ $c['style'] }}" data-carton="{{ $c['carton'] }}" data-group="{{ $c['group'] }}" readonly>
                @endforeach
            </td>
        </tr>
    @endforeach

    {{-- Mixed carton (urut = 21): satu carton berisi beberapa size --}}
    @foreach ($mixedRows as $mixed)
        <tr>
            <th colspan="2">
                @foreach ($mixed['labelLines'] as $idx => $line)
                    {{ $line }} | {{ $mixed['pcsLines'][$idx] ?? '' }}<hr>
                @endforeach
            </th>
            <td align="center">{{ $mixed['pcsp'] }}</td>
            <td class="cell-cartons">
                <input type="text" class="carton-input" value="{{ $mixed['cartonInput']['label'] }}" style="{{ $mixed['cartonInput']['style'] }}" data-carton="{{ $mixed['cartonInput']['carton'] }}" data-group="{{ $mixed['cartonInput']['group'] }}" readonly>
            </td>
        </tr>
    @endforeach
</table>