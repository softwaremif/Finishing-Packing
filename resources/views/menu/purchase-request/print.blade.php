<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Request</title>
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
            width: 50%;
            vertical-align: top;
        }

        .right-column {
            width: 35%;
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
               PURCHASE REQUEST
                <div class="separator"></div>
            </td>
        </tr>

        <tr>
            <td colspan="2" class="subjudul">
                NO. PR {{ str_pad($dt_pr->nopr, 6, '0', STR_PAD_LEFT) }}, {{ $dt_pr->tanggal }}
            </td>
        </tr>
        <br>
        <br>
        <br>

        <tr>
            <td class="left-column">
                <table>
                    <tr>
                        <td>To <strong>: {{ strtoupper($dt_pr->depnm) }} </strong></td>
                    </tr>
                </table>
            </td>
            <!-- <td class="left-column">
                <table>
                    <tr>
                        <td>Pengirim <strong>: {{ strtoupper($dt_pr->login) }} </strong></td>
                    </tr>
                </table>
            </td>

            <td class="right-column">
                <table>
                    <tr>
                        <td>Penerima <strong>: {{ $dt_pr->depnm}}
                        </strong></td>
                    </tr>
                </table>
            </td> -->
        </tr>
    </table>

    <table border="1">
        <tr>
            <th align="left">&nbsp; No</th>
            <th align="left">&nbsp; Nama Barang/Spesifikasi</th>
            <th align="center">Qty</th>
            <th align="center">Satuan</th>
        </tr>

        <tbody>

            @php
            $totalPcs = 0;
            @endphp

            @foreach($dt_prdt as $index=>$dt)
            <tr>
                <td align="left">&nbsp; {{ ++$index }}</td>
                <td align="left">&nbsp; {{ ucwords($dt->brgnm) }}</td>
                <td align="center">{{ $dt->unit }}</td>
                <td align="center">{{ $dt->jmlbeli }}</td>
                <?php
                    $totalPcs += $dt->jmlbeli;
                ?>
            </tr>
            @endforeach
        </tbody>
        <tfoot style="font-size: 12px;">
            <tr>
                <td colspan="3" align="center"><strong> Total </strong></td>
                <td align="center"><strong> {{ number_format($totalPcs, 0, ',', ','), }} </strong></td>
            </tr>
        </tfoot>
    </table>

    <br>
    <br>


    <table class="no-border" style="font-size: 12px;">
        <tr>
            <td align="center">Disetujui oleh</td>
            <td align="center">Yang minta</td>
        </tr>

        <br>
        <br>

        <tr>
            
            <!-- <td align="center">({{ $dt_pr->depnm}})</td> -->
            <td align="center">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</td>
            <td align="center">({{ strtoupper($dt_pr->login) }})</td>
        </tr>
    </table>

</body>

</html>