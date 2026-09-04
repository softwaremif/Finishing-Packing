<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('/public/css/images/morich.gif') }}" />
    <title>Morich Indo Fashion - Print Packing (Global)</title>
    <link href="{{ asset('config/stylist.css') }}" rel="stylesheet" type="text/css" media="screen" />
    <style type="text/css">
        body {
            font-family: "Book Antiqua", Palatino, serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 10px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 5px 6px;
            vertical-align: top;
            border-bottom: 1px dashed #e2e8f0;
        }
        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .print-table th,
        .print-table td {
            border: 1px solid #94a3b8;
            padding: 6px 4px;
            text-align: center;
        }
        .print-table th {
            background-color: #cbd5e1 !important;
            font-weight: bold;
        }
        .ftitle {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 15px 0 8px 0;
            color: #0f172a;
            border-left: 4px solid #475569;
            padding-left: 8px;
            text-align: left;
        }
        .carton-input {
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 2px 4px;
            margin: 2px;
            font-family: inherit;
            font-size: 10px;
            text-align: center;
            display: inline-block;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            font-size: 11px;
            page-break-inside: avoid;
        }
        .signature-table th,
        .signature-table td {
            border: 1px solid #cbd5e1;
            text-align: center;
            padding: 6px;
        }
        @media print {
            body { padding: 0; margin: 0; color: #000; }
            .ftitle { page-break-after: avoid; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .carton-input { border: .5px solid #94a3b8 !important; }
        }
    </style>
</head>
<body>
    {{-- Timestamp Terakhir Diupdate -- dari baris pack TERBARU di
         seluruh scope PO+OP+poref ini ($dtp dikirim dari printGlobal()). --}}
    <table width="100%" style="font-size: 10px; margin-bottom: 10px;">
        <tr>
            <td style="color: #64748b;">
                <i>Last updated: {{ $dtp ? $dtp->tanggal . ' ' . $dtp->waktu : '-' }}</i>
            </td>
        </tr>
    </table>

    {{-- Informasi Detail Utama PO --}}
    <table class="info-table">
        <tr>
            <td width="12%"><strong>Customer</strong></td>
            <td width="18%">:
                {{-- FIX: tidak lagi berdasarkan $dt->gabung (konsep itu
                     tidak ada di versi Global) -- cukup cek apakah scope
                     ini memang mencakup lebih dari 1 customer. --}}
                @if ($customersList->count() > 1)
                    @foreach ($customersList as $c)
                        {{ $c }};<br>
                    @endforeach
                @else
                    {{ $customersList->first() ?? ($dt2->customer ?? '') }}
                @endif
            </td>
            <td width="10%"><strong>Season</strong></td>
            <td width="15%">: {{ $dt2->season ?? '' }}</td>
            <td width="10%"><strong>PO.No</strong></td>
            <td width="15%">: {{ $dt2->POno ?? '' }}</td>
            <td width="10%"><strong>OP#</strong></td>
            <td width="15%">: {{ $dt2->OP ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Buyer</strong></td>
            <td>: {{ $dt2->buyer ?? '' }}</td>
            <td><strong>Style</strong></td>
            <td>: {{ $dt2->style ?? '' }}</td>
            <td><strong>Color</strong></td>
            <td>:
                {{-- FIX: versi Global SELALU bisa mencakup banyak warna,
                     jadi tampilkan daftar kalau lebih dari 1, bukan
                     tergantung flag gabung. --}}
                @if ($materialsList->count() > 1)
                    @foreach ($materialsList as $m)
                        {{ $m }};<br>
                    @endforeach
                @else
                    {{ $materialsList->first() ?? ($dt2->material ?? '') }}
                @endif
            </td>
            <td><strong>Silhouette</strong></td>
            <td>: {{ $dt2->silhouette ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Shipdate Plan</strong></td>
            <td>: {{ $dt2->shipdate1 ?? '' }}</td>
            <td><strong>Shipdate Aktual</strong></td>
            <td>: {{ $dt2->shipdate2 ?? '' }}</td>
            <td><strong>SAP ID</strong></td>
            <td>: {{ $dt2->sap1 ?? '' }}</td>
            <td><strong>SAP No.</strong></td>
            <td>: {{ $dt2->sap2 ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Total CTN</strong></td>
            <td>: <strong>{{ $totalCtn }}</strong></td>
            <td><strong>MEAS CTN</strong></td>
            <td>:
                {{-- FIX: $measList sekarang koleksi STRING biasa (bukan
                     object ->meas) -- dipakai langsung sebagai {{ $x3 }}. --}}
                @foreach ($measList as $x3)
                    {{ $x3 }}; <br>
                @endforeach
            </td>
            <td><strong>Warehouse</strong></td>
            <td>: {{ $dt2->wh ?? '' }}</td>
            <td><strong>Keterangan</strong></td>
            <td>: {{ $dt2->ket ?? '' }}</td>
        </tr>
    </table>

    {{-- Isi Laporan: Breakdown Size & Qty + Detail Packing --}}
    <div class="pdf-content-container">
        @php
            $cjml    = count($activeSizes);
            $cheader = $cjml + 1;
        @endphp

        <div align="center" class="ftitle">Breakdown Size &amp; Qty</div>
        <table align="center" width="100%" border="1" id="hor-minimalist-a"
            style="font-family:Book Antiqua; font-size:12px;">
            <tr align="center" bgcolor="#CCCCCC">
                <th width="10%">Color</th>
                <th width="8%">Sec Size</th>
                <th width="12%">Item</th>
                @foreach ($activeSizes as $i => $sz)
                    <th>{{ $sz }}</th>
                @endforeach
                <th>Total</th>
            </tr>

            {{-- BARU: 1 blok (4 baris: Order/Ship/+-/%) PER Color/Sec Size --
                 kolom Color & Sec Size di-rowspan untuk 4 baris itu. --}}
            @foreach ($orderShipByCombo as $combo)
                @php $d = $combo['data']; @endphp
                <tr>
                    <td align="center" rowspan="4" style="vertical-align:middle;">{{ $combo['material'] }}</td>
                    <td align="center" rowspan="4" style="vertical-align:middle;">{{ $combo['secsz'] ?: '-' }}</td>
                    <th>Order Qty</th>
                    @foreach ($activeSizes as $i => $sz)
                        <td>{{ $d['order'][$i] ?? '' }}</td>
                    @endforeach
                    <th>{{ $d['order_total'] ?? 0 }}</th>
                </tr>
                <tr>
                    <th>Ship Qty</th>
                    @foreach ($activeSizes as $i => $sz)
                        <td>{{ $d['ship'][$i] ?? '' }}</td>
                    @endforeach
                    <th>{{ $d['ship_total'] ?? 0 }}</th>
                </tr>
                <tr>
                    <th>+/-</th>
                    @foreach ($activeSizes as $i => $sz)
                        <td>{{ $d['diff'][$i] ?? '' }}</td>
                    @endforeach
                    <th>{{ $d['diff_total'] ?? 0 }}</th>
                </tr>
                <tr>
                    <th>%</th>
                    @foreach ($activeSizes as $i => $sz)
                        <td>{{ ($d['pct'][$i] ?? '') !== '' ? number_format($d['pct'][$i], 2, ',', '.') : '' }}</td>
                    @endforeach
                    <th>{{ number_format($d['pct_total'] ?? 0, 2, ',', '.') }}</th>
                </tr>
            @endforeach

            {{-- Baris TOTAL keseluruhan (semua Color/Sec Size digabung) --}}
            <tr align="center" bgcolor="#e5e7eb">
                <th colspan="3">TOTAL</th>
                @foreach ($activeSizes as $i => $sz)
                    <th></th>
                @endforeach
                <th></th>
            </tr>
            <tr>
                <td colspan="2" class="text-start" style="text-align:left; padding-left:6px;"></td>
                <th>Order Qty</th>
                @foreach ($activeSizes as $i => $sz)
                    <td>{{ $orderShip['order'][$i] ?? '' }}</td>
                @endforeach
                <th>{{ $orderShip['order_total'] ?? 0 }}</th>
            </tr>
            <tr>
                <td colspan="2"></td>
                <th>Ship Qty</th>
                @foreach ($activeSizes as $i => $sz)
                    <td>{{ $orderShip['ship'][$i] ?? '' }}</td>
                @endforeach
                <th>{{ $orderShip['ship_total'] ?? 0 }}</th>
            </tr>
            <tr>
                <td colspan="2"></td>
                <th>+/-</th>
                @foreach ($activeSizes as $i => $sz)
                    <td>{{ $orderShip['diff'][$i] ?? '' }}</td>
                @endforeach
                <th>{{ $orderShip['diff_total'] ?? 0 }}</th>
            </tr>
            <tr>
                <td colspan="2"></td>
                <th>%</th>
                @foreach ($activeSizes as $i => $sz)
                    <td>{{ ($orderShip['pct'][$i] ?? '') !== '' ? number_format($orderShip['pct'][$i], 2, ',', '.') : '' }}</td>
                @endforeach
                <th>{{ number_format($orderShip['pct_total'] ?? 0, 2, ',', '.') }}</th>
            </tr>

            <tr align="center" bgcolor="#CCCCCC">
                <th colspan="{{ count($activeSizes) + 4 }}"></th>
            </tr>
            <tr>
                <th colspan="3">N.W</th>
                @foreach ($nwCells as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
            <tr>
                <th colspan="3">G.W</th>
                @foreach ($gwCells as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        </table>

        <div align="center" class="ftitle">Detail Packing</div>

        {{-- BARU: 2 bagian --
             1) Carton SIMPLE (1 Color/Sec Size per carton) yang breakdown
                size-nya IDENTIK digabung jadi 1 baris -- CTN = jumlah
                carton tergabung, CARTON NO berisi SEMUA nomornya.
             2) Carton MIXED (lintas Color/Sec Size dalam 1 carton fisik)
                -- TETAP 1 blok per carton (tidak digabung dengan carton
                lain), Color/Sec Size di-rowspan DALAM carton itu saja. --}}
        <table width="100%" align="center" border="1" id="hor-minimalist-a"
            style="font-family:Book Antiqua; font-size:12px;">
            <tr align="center" bgcolor="#CCCCCC">
                <th width="10%">Color</th>
                <th width="8%">Sec Size</th>
                <th width="10%">Size</th>
                <th width="5%">CTN</th>
                <th width="5%">PCS</th>
                <th width="62%">CARTON NO</th>
            </tr>

            {{-- 1) Carton simple, digabung kalau profilnya identik --}}
            @foreach ($detailPackingSimpleRows as $row)
                <tr>
                    <td align="center">{{ $row['material'] }}</td>
                    <td align="center">{{ $row['secsz'] ?: '-' }}</td>
                    <td>
                        @foreach ($row['sizeLines'] as $line)
                            {{ $line }}<br>
                        @endforeach
                    </td>
                    <td align="center">{{ $row['ctn'] }}</td>
                    <td align="center">{{ $row['pcsp'] }}</td>
                    <td>
                        @foreach ($row['cartonRows'] as $c)
                            <input type="text" value="{{ $c['label'] }}" style="{{ $c['style'] }}" readonly>
                        @endforeach
                    </td>
                </tr>
            @endforeach

            {{-- 2) Carton Mixed antar Color/Sec Size -- carton fisik yang
                 breakdown-nya IDENTIK (sama semua Color/Sec Size/Size/Qty)
                 sekarang DIGABUNG juga: CTN = jumlah carton yang cocok,
                 CARTON NO berisi SEMUA nomor carton yang tergabung. --}}
            @foreach ($detailPackingMixedRows as $row)
                <tr>
                    @if ($row['showColor'])
                        <td align="center" rowspan="{{ $row['colorRowspan'] }}" style="vertical-align:middle;">
                            {{ $row['material'] }}
                        </td>
                    @endif
                    @if ($row['showSecsz'])
                        <td align="center" rowspan="{{ $row['secszRowspan'] }}" style="vertical-align:middle;">
                            {{ $row['secsz'] ?: '-' }}
                        </td>
                    @endif
                    <td>
                        @foreach ($row['sizeLines'] as $line)
                            {{ $line }}<br>
                        @endforeach
                    </td>
                    @if ($row['isFirstOfCarton'])
                        <td align="center" rowspan="{{ $row['rowspanCarton'] }}" style="vertical-align:middle;">
                            {{ $row['ctn'] }}
                        </td>
                    @endif
                    <td align="center">{{ $row['pcsp'] }}</td>
                    @if ($row['isFirstOfCarton'])
                        <td rowspan="{{ $row['rowspanCarton'] }}" style="vertical-align:middle;">
                            @foreach ($row['cartonInputs'] as $c)
                                <input type="text" value="{{ $c['label'] }}" style="{{ $c['style'] }}" readonly>
                            @endforeach
                        </td>
                    @endif
                </tr>
            @endforeach
        </table>
    </div>

    {{-- Kolom Tanda Tangan Manajemen --}}
    <table class="signature-table">
        <thead style="background-color: #f1f5f9;">
            <tr>
                <th width="20%">Pembuat</th>
                <th width="20%">Chief Finishing</th>
                <th width="20%">SPV Finishing</th>
                <th width="20%">Akurasi</th>
                <th width="20%">Manager Produksi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><br><br><br><br></td>
                <td><br><br><br><br></td>
                <td><br><br><br><br></td>
                <td><br><br><br><br></td>
                <td><br><br><br><br></td>
            </tr>
        </tbody>
    </table>
</body>
</html>