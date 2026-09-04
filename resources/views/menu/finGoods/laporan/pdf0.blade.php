@php
    // pdf0 (gab = 0 / single material)
    // $detailPacking = hasil ShipService::buildDetailPacking()
@endphp

<div class="ftitle">Detail Finished Goods</div>
<table class="print-table">
    <tr>
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
            <td class="cell-cartons">
                @foreach ($group['cartonRows'] as $c)
                    <input type="text" class="carton-input" value=" {{ $c['label'] }} " style="{{ $c['style'] }}" data-carton="{{ $c['carton'] }}" data-group="{{ $c['group'] }}" readonly>
                @endforeach
            </td>
        </tr>
    @endforeach
</table>