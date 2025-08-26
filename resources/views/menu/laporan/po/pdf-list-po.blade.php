<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>List Laporan Purchase Order</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 5px; vertical-align: top; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .no-border { border: none; }
        .signature-box { height: 60px; }
    </style>
</head>
<body>

    <table class="no-border">
        <tr>
            <td class="no-border" style="width: 60%"><strong>PT. MORICH INDO FASHION</strong></td>
            <td class="no-border text-right">Dicetak: {{ \Carbon\Carbon::now()->translatedFormat('l, j F Y H:i:s') }}
                <!-- <br>Hal : 1 / 1 -->
            </td>
        </tr>
    </table>

        <h3 class="text-center">Laporan Purchase Order <br> Bulan {{ $bulanNama }} Tahun {{ $filterYear }}</h3>
    <!-- <div class="header">
        <div class="title">LAPORAN PEMBELIAN GABUNGAN</div>
    </div>
    <br>

    <table class="no-border" style="margin-bottom: 10px;">
        <tr>
            <td class="no-border" style="width: 80%"></td>
        </tr>
    </table> -->

    <table>
        <thead>
            <tr>
                <th style="width: 5%">NO.</th>
                <th style="width: 20%" class="text-left">Tgl Po</th>
                <th style="width: 15%" class="text-left">NO Bukti</th>
                <th style="width: 55%" class="text-left">Nama Barang</th>
                <th style="width: 15%" class="text-left">Satuan</th>
                <th style="width: 10%" class="text-left">Qty</th>
                <th style="width: 15%" class="text-left">Harga Satuan</th>
                <th style="width: 15%" class="text-left">Total Harga</th>
                <th style="width: 15%" class="text-left">user</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($formattedData as $row)
                <tr>
                    <td class="text-center"> {{ $row['index'] }}</td>
                    <td class="text-left"> {{ $row['tanggal'] }}</td>
                    <td class="text-left"> {{ $row['nobukti'] }}</td>
                    <td class="text-left"> {{ $row['brgnm'] }}</td>
                    <td class="text-left"> {{ $row['unit'] }}</td>
                    <td class="text-left"> {{ $row['jmlbeli'] }}</td>
                    <td class="text-left"> {{ $row['hrgbeli'] }}</td>
                    <td class="text-left"> {{ $row['totalbeli'] }}</td>
                    <td class="text-left"> {{ $row['user'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="7" class="text-right subtotal">GRAND TOTAL </td>
                <td colspan="2" class="text-left underline subtotal">{{ number_format($subTotal, 0, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>