<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Pembayaran Cash Giro</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            text-align: center;
        }

        table {
            /* width: 100%; */
            /* border-collapse: collapse; */
            /* border: 1px; */
            /* margin-top: 10px; */
        }

    </style>
</head>

<body>
    
    <h2>LIST PAYMENT</h2>
    <h3>PT. MORICH INDO FASHION</h3>

    <table border="1";>
        <thead>
            <tr>
                <th>No Invoice</th>
                <th>Tgl Invoice</th>
                <th>Supplier</th>
                <th>Keterangan</th>
                <th>Harga <br>Satuan</th>
                <th>Qty</th>
                <th>Jumlah</th>
                <!-- <th>Tanggal Bayar</th> -->
                <!-- <th>Jml. Bayar</th> -->
            </tr>
        </thead>
        <tbody>

            @foreach ($formattedData as $data)
            <tr>
                <td>{{ $data['noinv'] }}</td>
                <td>{{ $data['tanggal'] }}</td>
                <td>{{ $data['supnm'] }}</td>
                <td style="text-align: left;">{{ $data['brgnm'] }}</td>
                <td>Rp. {{ $data['hrgbeli'] }}/{{ $data['unit'] }}</td>
                <td>{{ $data['jmlbeli'] }}</td>
                <td>{{ $data['jmlhrg'] }} {{ $data['cg'] }}</td>
                <!-- <td>{{ $data['tglbayar'] }}</td> -->
                <!-- <td>{{ $data['jmlbayar'] }}</td> -->
            </tr>
            @endforeach

            <tr>
                <td colspan="9" style="text-align: center; font-weight: bold;">GRAND TOTAL</td>
            </tr>
        </tbody>
    </table>

</body>

</html>