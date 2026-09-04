@php
    // Data pdf2 dari $gabData (hasil buildGab2Data)
    $detailByMaterial = $gabData['detailByMaterial'] ?? [];
@endphp

<div class="ftitle">Detail Finished Goods</div>
<table class="print-table">
    <tr>
        <th width="6%">Size</th>
        <th width="6%">CTN</th>
        <th width="6%">PCS</th>
        <th width="82%">CARTON NO</th>
    </tr>

    @foreach ($detailByMaterial as $matBlock)
        <tr>
            <th colspan="3">{{ $matBlock['material'] }}</th>
            <td></td>
        </tr>

        @foreach ($matBlock['groups'] as $group)
            <tr>
                <th>{{ $group['size'] }}</th>
                <td align="center">{{ $group['ctn'] }}</td>
                <td align="center">{{ $group['pcsp'] }}</td>
                <td class="cell-cartons">
                    @foreach ($group['cartonRows'] as $c)
                        <input type="text" class="carton-input" value=" {{ $c['label'] }} " style="{{ $c['style'] }}" readonly>
                    @endforeach
                </td>
            </tr>
        @endforeach
    @endforeach
</table>