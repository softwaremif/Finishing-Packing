<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Pembelian Cash / Tempo</title>
    <style>
    /* @page {
        margin: 20mm;
    } */

            @page {
            margin: 20px;
            /* Margin semua sisi */
            margin-top: 35px;
            /* Atur margin atas jika berbeda */
            margin-bottom: 25px;
            margin-left: 25px;
            margin-right: 25px;
        }

    body {
        font-family: Arial, sans-serif;
        font-size: 10px;
        text-align: center;
    }



    .footer {
        position: fixed;
        bottom: -10px;
        left: 0;
        right: 0;
        text-align: right;
        font-size: 10px;
    }
    </style>
</head>

<body>

    <!-- <h2>LIST PAYMENT</h2> -->
    <h3>LAPORAN PEMBELIAN MIF 1</h3>
    <!-- <span> Dicetak :  {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i:s') }}</span> -->
   <b>{{ $tanggalLabel }}</b>

    <table border='1';>
        <thead>
            <tr>
                <!-- <th>No</th> -->
                <th>No Invoice</th>
                <th>Tgl Invoice</th>
                <th>Supplier</th>
                <th>Keterangan</th>
                <th>Harga <br>Satuan</th>
                <th>Qty</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalAmount = 0;
                $prevSupplier = null;
            @endphp

            @foreach ($formattedData as $index=>$data)
            <tr>
                <!-- <td>{{ ++$index }}</td> -->
                <td>{{ $data['noinv'] }}</td>
                <td>{{ $data['tanggal'] }}</td>

                @if ($data['supnm'] !== $prevSupplier)
                <td>{{ $data['supnm'] }}</td>
                @php $prevSupplier = $data['supnm']; @endphp
                @else
                <td></td>
                @endif

                <td style="text-align: left;">{{ $data['brgnm'] }}</td>
                <td>Rp. {{ $data['hrgbeli'] }}/{{ $data['unit'] }}</td>
                <td>{{ $data['jmlbeli'] }}</td>
                <!-- <td>{{ $data['jmlhrg'] }} {{ $data['cg'] }}</td> -->
                <td style="text-align: right;">{{ $data['jmlhrg'] }}</td>
                @php
                $jmlbeli = (float) preg_replace('/[^\d.]/', '', $data['jmlbeli']);
                $hrgbeli = (float) preg_replace('/[^\d.]/', '', $data['hrgbeli']);
                $totalAmount += $jmlbeli * $hrgbeli;
                @endphp
            </tr>
            @endforeach

            <tr>
                <td colspan="6" style="text-align: right; font-weight: bold;">GRAND TOTAL</td>
                <td align="center"><strong>{{ number_format($totalAmount, 0, '.', ','), }} </strong></td>
            </tr>
        </tbody>
    </table>
</body>

</html>