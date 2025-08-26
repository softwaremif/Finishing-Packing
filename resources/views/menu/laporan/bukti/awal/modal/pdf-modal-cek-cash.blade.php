<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Pembayaran Cash / Tempo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
        }

        .header {
            text-align: center;
        }

        .title {
            font-weight: bold;
            font-size: 14px;
            text-decoration: underline;
        }

        .sub-title {
            font-size: 12px;
            margin-top: 2px;
            font-weight: bold;
        }

        .meta {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-top: 5px;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            border: 1px solid black;
            padding: 5px;
            text-align: center;
            background-color: #f3f3f3;
        }

        .group-label {
            font-weight: bold;
            padding-top: 6px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .subtotal, .grandtotal {
            font-weight: bold;
            padding-top: 5px;
        }

        .underline {
            border-bottom: 1px solid black;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <!-- <div class="header">
        <div>PT. MORICH INDO FASHION</div>
        <div class="title">LAPORAN PEMBAYARAN CASH / TEMPO</div>
        <div class="sub-title"> {{ $tanggalLabel }} </div>
    </div> -->


    <table class="no-border">
        <tr>
            <td class="no-border" style="width: 60%"><strong>PT. MORICH INDO FASHION</strong></td>
            <td class="no-border text-right"><i> Dicetak: {{ \Carbon\Carbon::now()->translatedFormat('l, j F Y H:i:s') }} </i>
                <!-- <br>Hal : 1 / 1 -->
            </td>
        </tr>
    </table>

    <div class="header">
        <div class="title">LAPORAN PEMBAYARAN CASH / TEMPO</div>
        <div class="sub-title"> {{ $tanggalLabel }} </div>
    </div>
    <br>

    <!-- <div class="meta">
        <div></div>
        <div>Dicetak: {{ \Carbon\Carbon::now()->translatedFormat('l, j F Y H:i:s') }} </div>
    </div> -->

    <!-- Tabel Header -->
    <table>
        <thead>
            <tr>
                <th style="width:60px;">NO. INV.</th>
                <th style="width:60px;">TGL. INV.</th>
                <th style="width:60px;">SUPPLIER</th>
                <th style="width:160px;">KETERANGAN</th>
                <th style="width:60px;">HARGA SATUAN</th>
                <th style="width:30px;">QTY</th>
                <th style="width:60px;">JUMLAH (Rp.)</th>
                <th style="width:60px;">TANGGAL BAYAR</th>
                <th style="width:60px;">JML. BAYAR (Rp.)</th>
            </tr>
        </thead>
    </table>

    <!-- Data Table -->
    @if($count_data_Cash > 0)
        <table>
            <tbody>

                <tr>
                    <td colspan="9" class="group-label">CASH</td>
                </tr>

                @foreach ($formattedData_Cash as $index=>$item)
                    <tr>
                        <td style="width:60px;" class="text-left">{{ $item['noinv'] }}</td>
                        <td style="width:60px;" class="text-left">{{ $item['tanggal'] }}</td>
                        <td style="width:60px;" class="text-left">{{ $item['supnm'] }} </td>
                        <td style="width:160px;">{{ $item['brgnm'] }}</td>
                        <td style="width:60px;" class="text-right">{{ $item['hrgbeli'] }} / {{ $item['unit'] }}</td>
                        <td style="width:30px;" class="text-right">{{ $item['jmlbeli'] }}</td>
                        <td style="width:60px;" class="text-right">{{ $item['jmlhrg'] }}</td>
                        <td style="width:60px;" class="text-right">{{ $item['tglbayar'] }}</td>
                        <td style="width:60px;" class="text-right">{{ $item['jmlbayar'] }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="6" class="text-right subtotal">SUB TOTAL CASH Rp.</td>
                    <td class="text-right subtotal">{{ number_format($subTotalCash, 0, '.', ',') }}</td>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                    <td class="text-right subtotal">{{ number_format($grandTotalCash, 0, '.', ',') }}</td>
                </tr>

                <!-- GRAND TOTAL -->
                @if($count_data_Tempo < 1)
                    <tr>
                        <td colspan="6" class="text-right grandtotal">GRAND TOTAL Rp.</td>
                        <td class="text-right underline grandtotal">{{ number_format($SubTotalCashTempoJmlHrg, 0, '.', ',') }} </td>
                        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                        <td class="text-right underline grandtotal">{{ number_format($TotalGrandCashTempo, 0, '.', ',') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
        <br>
    @endif

    @if($count_data_Tempo > 0)
        <table>
            <tbody>
                <tr>
                    <td colspan="9" class="group-label">TEMPO</td>
                </tr>

                @foreach ($formattedData_Tempo as $index=>$dt)
                    <tr>
                        <td style="width:60px;" class="text-left">{{ $dt['noinv'] }}</td>
                        <td style="width:60px;" class="text-left">{{ $dt['tanggal'] }}</td>
                        <td style="width:60px;" class="text-left">{{ $dt['supnm'] }} </td>
                        <td style="width:160px;">{{ $dt['brgnm'] }}</td>
                        <td style="width:60px;" class="text-right">{{ $dt['hrgbeli'] }} / {{ $dt['unit'] }}</td>
                        <td style="width:30px;" class="text-right">{{ $dt['jmlbeli'] }}</td>
                        <td style="width:60px;" class="text-right">{{ $dt['jmlhrg'] }}</td>
                        <td style="width:60px;" class="text-right">{{ $dt['tglbayar'] }}</td>
                        <td style="width:60px;" class="text-right">{{ $dt['jmlbayar'] }} </td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="6" class="text-right subtotal">SUB TOTAL TEMPO Rp.</td>
                    <td class="text-right subtotal">{{ number_format($subTotalTempo, 0, '.', ',') }}</td>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                    <td class="text-right subtotal">{{ $grandTotalTempo }}</td>
                </tr>

                <tr>
                    <td colspan="6" class="text-right grandtotal">GRAND TOTAL Rp.</td>
                    <td class="text-right underline grandtotal">{{ number_format($SubTotalCashTempoJmlHrg, 0, '.', ',') }} </td>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                    <td class="text-right underline grandtotal">{{ number_format($TotalGrandCashTempo, 0, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>