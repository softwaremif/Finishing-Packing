<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Laporan Bukti Kas Keluar</title>
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
                <td class="no-border text-right">Dicetak: {{ \Carbon\Carbon::now()->format('d M Y H:i:s') }}
                    <!-- <br>Hal : 1 / 1 -->
                </td>
            </tr>
        </table>

        <h3 class="text-center">Laporan Bukti Kas Keluar</h3>

        <table class="no-border" style="margin-bottom: 10px;">
            <tr>
                <td class="no-border" style="width: 65%">
                    Tanggal : <b>{{ \Carbon\Carbon::parse($tglInv)->format('d-m-Y') }}</td>
                <td class="no-border">Diterima dari : <b>{{ $supnm }}</b>
            </tr>
        </table>

        <table>
            <thead>
                <tr class="text-center">
                    <th>No Bukti</th>
                    <th>No Faktur / <br> No. PO</th>
                    <th>Tgl Faktur</th>
                    <th>Keterangan</th>
                    <th>Qty</th>
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
                        $jumlah = str_replace(',', '', $item['jmlhrg']);
                        $grandTotal += (float) $jumlah;
                    @endphp

                    <tr>
                        <td>{{ $item['nobukti'] }}</td>
                        <td>{{ $item['noinv'] }}</td>
                        <!-- <td>{{ $item['tglinv'] }}</td> -->
                        <td>{{ \Carbon\Carbon::parse($item['tglinv'])->format('d M Y') }}</td>
                        <td>{{ $item['brgnm'] }}</td>
                        <td>{{ $item['jmlbeli'] }}</td>
                        <td style="text-align: right;">{{ $item['hrgbeli'] }}/{{ $item['unit'] }}</td>
                        <td style="text-align: right;">{{ $item['jmlhrg'] }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="6" class="text-right bold">Grand Total Rp.</td>
                    <td class="text-right bold">{{ number_format($grandTotal, 0, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>

    </body>
    
</html>
