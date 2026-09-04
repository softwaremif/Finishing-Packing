<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; font-size: 12px; }
        th { background: #CCCCCC; font-weight: bold; text-align: center; }
        td { text-align: center; }
        .ftitle { font-size: 14px; font-weight: bold; margin-bottom: 8px; }
    </style>
</head>
<body>

    <div class="ftitle">
        Daftar Data Sisa Stock Barang Jadi (Produksi)<br>
        Buyer {{ $buyer ?: 'Semua' }} ({{ $year ?: 'Semua Tahun' }})
    </div>

    <table>
        <tr>
            <th colspan="10">Total</th>
            <th>{{ number_format($totals['qty']) }}</th>
            <th>{{ number_format($totals['loading']) }}</th>
            <th>{{ number_format($totals['rq']) }}</th>
            <th>{{ number_format($totals['pcs']) }}</th>
            <th>{{ number_format($totals['packing']) }}</th>
            <th>{{ number_format($totals['balance']) }}</th>
            <th colspan="5"></th>
        </tr>
        <tr>
            <th>No.</th>
            <th>TglInGdg</th>
            <th>TglOutGdg</th>
            <th>Shipdate</th>
            <th>Customer</th>
            <th>PO.No</th>
            <th>OP#</th>
            <th>Buyer</th>
            <th>Style</th>
            <th>Color</th>
            <th>Qty</th>
            <th>Loading<br>(Pcs)</th>
            <th>R+Q<br>(Pcs)</th>
            <th>Transfer<br>(Pcs)</th>
            <th>Packing<br>(Pcs)</th>
            <th>Balance<br>(Pcs)</th>
            <th>Grade<br>(A)</th>
            <th>Grade<br>(C)</th>
            <th>Out<br>(Pcs)</th>
            <th>Silhouette</th>
            <th>Keterangan</th>
        </tr>
        @foreach ($rows as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row['tglin'] }}</td>
                <td>{{ $row['tglout'] }}</td>
                <td>{{ $row['shipdate1'] }}</td>
                <td>{{ $row['customer'] }}</td>
                <td>{{ $row['POno'] }}</td>
                <td>{{ $row['OP'] }}</td>
                <td>{{ $row['buyer'] }}</td>
                <td>{{ $row['style'] }}</td>
                <td>{{ $row['material'] }}</td>
                <td>{{ $row['qty'] }}</td>
                <td>{{ $row['loading'] }}</td>
                <td>{{ $row['rq'] }}</td>
                <td>{{ $row['pcs'] }}</td>
                <td>{{ $row['packing'] }}</td>
                <td>{{ $row['balance'] }}</td>
                <td>{{ $row['grade_a'] }}</td>
                <td>{{ $row['grade_c'] }}</td>
                <td>{{ $row['pcsk'] }}</td>
                <td>{{ $row['silhouette'] }}</td>
                <td style="text-align:left;">{{ $row['keterangan'] }}</td>
            </tr>
        @endforeach
    </table>

</body>
</html>