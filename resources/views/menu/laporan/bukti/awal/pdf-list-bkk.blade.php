<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>List Laporan Bukti Kas Keluar</title>
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

    <h3 class="text-center">BUKTI KAS KELUAR</h3>

    <table class="no-border" style="margin-bottom: 10px;">
        <tr>
            <td class="no-border" style="width: 80%"></td>
            <!-- <td class="no-border"></td> -->
            <td class="no-border">No. : 
                <br>Tgl. : {{ \Carbon\Carbon::parse($SelectDate)->format('d-m-Y') }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr class="text-center">
                <th style="width: 5%">NO.</th>
                <th style="width: 65%">KETERANGAN</th>
                <th style="width: 15%">NO. REKENING</th>
                <th style="width: 15%">NILAI UANG (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($formattedData as $row)
                <tr>
                    <td class="text-center">{{ $row['index'] }}</td>
                    <td>{{ $row['supnm'] }}</td>
                    <td></td>
                    <td class="text-right">{{ $row['hrgbeli'] }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3" class="text-right bold">Total Rp.</td>
                <td class="text-right bold">{{ number_format($totalAll, 0, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>

    <p>Terbilang : <strong> {{ Str::upper(FormatRupiahHelper::terbilang($totalAll)) }} RUPIAH</strong></p>

    <table class="no-border text-center" style="margin-top: 40px;">
        <tr>
            <td class="no-border">Menyetujui :</td>
            <td class="no-border">Yang menerima :</td>
            <td class="no-border">Yang membayar :</td>
        </tr>
        <br>
        <br>
        <br>
        <tr class="signature-box">
            <td class="no-border">( ............................... )</td>
            <td class="no-border">( ............................... )</td>
            <td class="no-border">( ............................... )</td>
        </tr>
    </table>

</body>
</html>
