<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bukti Kas Keluar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .header,
        .footer {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }
        
        .separator {
            border-bottom: 0.5px solid black;
            width: 280px;
            /* Lebar garis, bisa disesuaikan */
            margin: 3px auto;
            /* Otomatis rata tengah */
        }

                .left-column {
            width: 65%;
            vertical-align: top;
        }

        .right-column {
            width: 35%;
            vertical-align: top;
        }

                .container {
            width: 100%;
            /* border-collapse: collapse; */
        }

                .judul-surat {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <!-- <h2>Laporan Bukti Kas Keluar</h2> -->
    <!-- <h3>PT. MORICH INDO FASHION</h3> -->

     <table class="container">
        <tr>
            <td colspan="2" class="judul-surat">
                Laporan Bukti Kas Keluar
                <div class="separator"></div>
            </td>
        </tr>
        <br>
        <tr>
            <td class="left-column">
                <table>
                    <tr>
                        <td>Tanggal :<strong> {{ $tglInv }} </strong></td>
                    </tr>
                </table>
            </td>

            <td class="right-column">
                <table>
                    <tr>
                        <td>Diterima dari : <strong>{{ $supnm }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table border="1";>
        <thead>
            <tr>
                <!-- <th>No</th> -->
                <th>No Bukti</th>
                <th>No Faktur / <br> No. PO</th>
                <th>Tgl Faktur</th>
                <th>Keterangan</th>
                <th>Qty</th>
                <!-- <th>Satuan</th> -->
                <th>Harga Satuan</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandTotal = 0;
            @endphp

            @foreach ($formattedData as $index=>$item)

            @php
                // Hilangkan koma pada jmlbayar agar bisa dijumlahkan sebagai angka
                $jumlah = str_replace(',', '', $item['jmlbayar']);
                $grandTotal += (float) $jumlah;
            @endphp

            <tr>
                <!-- <td>{{ ++$index }}</td> -->
                <td>{{ $item['nobukti'] }}</td>
                <td>{{ $item['noinv'] }}</td>
                <!-- <td>{{ $item['tglinv'] }}</td> -->
                 <td>{{ \Carbon\Carbon::parse($item['tglinv'])->format('d M Y') }}</td>
                <td>{{ $item['brgnm'] }}</td>
                <td>{{ $item['jmlbeli'] }}</td>
                <!-- <td></td> -->
                <td style="text-align: right;">{{ $item['hrgbeli'] }}/{{ $item['unit'] }}</td>
                <td style="text-align: right;">{{ $item['jmlbayar'] }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="6" style="text-align: right; font-weight: bold;">Grand Total </td>
                <td colspan="1" style="text-align: right; font-weight: bold;">Rp. {{ number_format($grandTotal, 0, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>