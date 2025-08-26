<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order</title>
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

    

        .no-border {
            border: none;
        }


        .container {
            width: 100%;
            /* border-collapse: collapse; */
        }

        .left-column {
            width: 84%;
            vertical-align: top;
        }

        .right-column {
            width: 16%;
            vertical-align: top;
        }

        td {
            padding: 5px;
        }

        .judul-surat {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }

        .separator {
            border-bottom: 0.5px solid black;
            width: 200px;
            /* Lebar garis, bisa disesuaikan */
            margin: 3px auto;
            /* Otomatis rata tengah */
        }

        .subjudul {
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <!-- Header Section -->
    <table class="header">
        <div><img src="{!! asset('public/css/images/morich.jpg') !!}" width="500" height="35" style="margin-top:3%;margin-left:3%;"></div>
        <div style="margin-top:-5%;margin-left:-89%;"><img src="{!! asset('public/css/images/morich.png') !!}" width="75" height="75"></div>
        <div style="margin-top:-7%;margin-left:1%;text-align:center;">
            <p style="font-size:13px;">JL. RAYA KARANGJATI KM. 25 DESA GEMBONGAN - BERGAS, SEMARANG - INDONESIA<br>
                PHONE : 0298 - 523523 (6 LINES) FAX. 0298 - 522095<br>
                www.morichindofashion.com</p>
        </div>
        <hr style="border: none;border-top: 2px solid black;margin-top:2%;">
        </hr>
    </table>
    <br>

    <table class="container">
        <tr>
            <td colspan="2" class="judul-surat">
               PURCHASE ORDER
                <div class="separator"></div>
            </td>
        </tr>

        <tr>
            <td colspan="2" class="subjudul">
                <b>NO. {{ str_pad($dt_po->nobukti, 6, '0', STR_PAD_LEFT) }}</b>
            </td>
        </tr>
        <br>

        <tr>
            <td class="left-column">
                <table>
                    <tr>
                        <td>Kepada Yth : <br> <strong>{{ strtoupper($dt_po->supnm) }} </strong></td>
                    </tr>
                </table>
                &nbsp; <i>Up.</i>
            </td>

            <td class="right-column">
                <table>
                    <tr>
                        <strong>{{ $dt_po->tanggal}} </strong>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <p>Harap Saudara kirim barang-barang sbb:</p>
        </tr>
    </table>

    <table border="1">
        <tr>
            <th align="left">&nbsp; No</th>
            <th align="left">&nbsp; Nama Barang/Spesifikasi</th>
            <th align="center">Qty</th>
            <th align="center">Harga Satuan</th>
            <th align="center">Jumlah</th>
        </tr>

        <tbody>

            @php
                $totalPcs = 0;
                $totalAmount = 0;
            @endphp

            @foreach($dt_podt as $index=>$dt)
            <tr>
                <td align="left">&nbsp; {{ ++$index }}</td>
                <td align="left">&nbsp; {{ ucwords($dt->brgnm) }}</td>
                <td align="center">{{ $dt->jmlbeli }}</td>
                <td align="center">{{ $dt_po->cursign}} {{ number_format($dt->hrgbeli, 2, '.', ','),}}/{{ $dt->unit }}</td>
                <td align="center">{{ $dt_po->cursign}} {{ number_format($dt->jmlbeli * $dt->hrgbeli, 2, '.', ',') }}</td>
                <?php
                    $totalPcs += $dt->jmlbeli;
                    $totalAmount += $dt->jmlbeli * $dt->hrgbeli;
                ?>
            </tr>

            @endforeach
        </tbody>
        <tfoot style="font-size: 12px;">
            <tr>
                <td colspan="4" align="center"><strong> Total </strong></td>
                <td align="center"><strong>{{ $dt_po->cursign}} {{ number_format($totalAmount, 2, '.', ','), }} </strong></td>
            </tr>
        </tfoot>
    </table>
    @if($dt_po->ket != null)
    <p><i>Up. {{ $dt_po->ket }}</i></p>
    @endif
    <br>
    <br>


    <table class="no-border" style="font-size: 12px;">
        <tr>
            <td align="right">Hormat kami</td>
        </tr>

        <br>
        <br>

        <tr>
            @if($dt_po->ttdnm == null)
            <td align="right">({{ ucfirst($dt_po->login) }})</td>
            @else
            <td align="right">({{ ucfirst($dt_po->ttdnm) }})</td>
            @endif
        </tr>
    </table>

</body>

</html>