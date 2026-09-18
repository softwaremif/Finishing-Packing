<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('/public/css/images/morich.gif') }}" />
    <title>Packing List - {{ $dt2->POno ?? '' }} / {{ $dt2->OP ?? '' }}</title>
    <style type="text/css">
        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 18px 20px;
        }

        /* ===================== LETTERHEAD ===================== */
        .lh-wrap {
            display: table;
            width: 100%;
            border-bottom: 2.5px solid #1e293b;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .lh-left {
            display: table-cell;
            vertical-align: middle;
            width: 70%;
        }

        .lh-right {
            display: table-cell;
            vertical-align: middle;
            width: 30%;
            text-align: right;
        }

        .lh-company {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: .3px;
            color: #0f172a;
        }

        .lh-sub {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 2px;
        }

        .lh-doctitle {
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: .5px;
        }

        .lh-docref {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 3px;
        }

        /* ===================== INFO GRID ===================== */
        .info-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 14px;
        }

        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 14px;
        }

        .info-col:last-child {
            padding-right: 0;
            padding-left: 14px;
            border-left: 1px solid #e2e8f0;
        }

        .info-row {
            display: table;
            width: 100%;
            padding: 3px 0;
        }

        .info-label {
            display: table-cell;
            width: 34%;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: .2px;
            vertical-align: top;
            padding: 2px 0;
        }

        .info-value {
            display: table-cell;
            font-size: 11.5px;
            font-weight: 600;
            color: #0f172a;
            vertical-align: top;
            padding: 2px 0;
        }

        /* ===================== SECTION TITLE ===================== */
        .sec-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            color: #fff;
            background: #1e293b;
            letter-spacing: .4px;
            padding: 5px 10px;
            margin: 14px 0 6px 0;
        }

        /* ===================== TABLES ===================== */
        table.pl-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            font-size: 10.5px;
        }

        table.pl-table th,
        table.pl-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 4px;
            text-align: center;
            vertical-align: middle;
        }

        table.pl-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: .2px;
        }

        table.pl-table .row-label {
            background: #f8fafc;
            font-weight: 700;
            text-align: left;
            padding-left: 8px;
        }

        table.pl-table tr.total-row td,
        table.pl-table tr.total-row th {
            background: #e2e8f0;
            font-weight: 800;
        }

        table.pl-table td.text-left {
            text-align: left;
            padding-left: 8px;
        }

        /* ===================== SHIPPING SUMMARY (standar wajib PL garment) ===================== */
        .ship-summary {
            display: table;
            width: 100%;
            table-layout: fixed;
            border: 1.5px solid #1e293b;
            border-radius: 4px;
            margin: 6px 0 14px 0;
            overflow: hidden;
        }

        .ship-summary .cell {
            display: table-cell;
            text-align: center;
            padding: 10px 6px;
            border-right: 1px solid #cbd5e1;
        }

        .ship-summary .cell:last-child {
            border-right: none;
        }

        .ship-summary .cell-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #64748b;
            font-weight: 700;
        }

        .ship-summary .cell-value {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 3px;
        }

        .ship-summary .cell-unit {
            font-size: 9px;
            color: #94a3b8;
        }

        /* ===================== CARTON STATUS DOT (ganti dari full-bg input box) ===================== */
        .ctn-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 2px 8px;
            margin: 2px;
            font-size: 9.5px;
            font-weight: 600;
            background: #fff;
            color: #334155;
        }

        .ctn-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
            background: #94a3b8;
            flex-shrink: 0;
        }

        .ctn-dot.ok {
            background: #16a34a;
        }

        .ctn-dot.short {
            background: #dc2626;
        }

        .ctn-dot.sealed {
            background: #0369a1;
        }

        /* ===================== SIGNATURE ===================== */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 26px;
            font-size: 10.5px;
            page-break-inside: avoid;
        }

        .signature-table th {
            border: 1px solid #cbd5e1;
            background: #f1f5f9;
            text-align: center;
            padding: 6px;
            font-weight: 700;
        }

        .signature-table td {
            border: 1px solid #cbd5e1;
            text-align: center;
            padding: 6px;
            height: 54px;
            vertical-align: bottom;
        }

        .footnote {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 4px;
        }

        @media print {
            body {
                padding: 0;
                margin: 0;
                color: #000;
            }

            .sec-title {
                page-break-after: avoid;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        .ctn-dot.pending {
            background: #94a3b8;
        }

        /* abu -- belum ada Actual */
        .ctn-dot.partial {
            background: #dc2626;
        }

        /* merah -- Actual masih kurang */
        .ctn-dot.complete {
            background: #16a34a;
        }

        /* hijau -- Actual sudah penuh */
        .ctn-dot.sealed {
            background: #0369a1;
        }

        /* biru -- sudah Segel */
    </style>
</head>

<body>

    {{-- ===================== LETTERHEAD ===================== --}}
    <div class="lh-wrap">
        <div class="lh-left">
            <div class="lh-company">PT. MORICH INDO FASHION</div>
            <div class="lh-sub">Garment Manufacturer &middot; Finishing & Packing </div>
        </div>
        <div class="lh-right">
            <div class="lh-doctitle">Packing List</div>
            <div class="lh-docref">
                No: {{ $dt2->POno ?? '-' }}/{{ $dt2->OP ?? '-' }}<br>
                {{-- Printed: {{ now()->format('d M Y H:i') }} --}}
            </div>
        </div>
    </div>

    {{-- ===================== INFO GRID ===================== --}}
    <div class="info-grid">
        <div class="info-col">
            <div class="info-row">
                <div class="info-label">Buyer</div>
                <div class="info-value">{{ $dt2->buyer ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Customer</div>
                <div class="info-value">
                    @if ($customersList->count() > 1)
                        {{ $customersList->implode(', ') }}
                    @else
                        {{ $customersList->first() ?? ($dt2->customer ?? '-') }}
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Season</div>
                <div class="info-value">{{ $dt2->season ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Style</div>
                <div class="info-value">{{ $dt2->style ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Silhouette</div>
                <div class="info-value">{{ $dt2->silhouette ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Color</div>
                <div class="info-value">
                    @if ($materialsList->count() > 1)
                        {{ $materialsList->implode(', ') }}
                    @else
                        {{ $materialsList->first() ?? ($dt2->material ?? '-') }}
                    @endif
                </div>
            </div>
        </div>
        <div class="info-col">
            <div class="info-row">
                <div class="info-label">PO No.</div>
                <div class="info-value">{{ $dt2->POno ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">OP No.</div>
                <div class="info-value">{{ $dt2->OP ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Ship Date Plan</div>
                <div class="info-value">{{ $dt2->shipdate1 ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Ship Date Actual</div>
                <div class="info-value">{{ $dt2->shipdate2 ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Warehouse</div>
                <div class="info-value">{{ $dt2->wh ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Remark</div>
                <div class="info-value">{{ $dt2->ket ?? '-' }}</div>
            </div>
        </div>
    </div>

    {{-- ===================== SHIPPING SUMMARY -- standar wajib PL garment ===================== --}}
    <div class="sec-title">Shipping Summary</div>
    <div class="ship-summary">
        <div class="cell">
            <div class="cell-label">Total Carton</div>
            <div class="cell-value">{{ number_format($totalCtn) }}</div>
            <div class="cell-unit">Ctn</div>
        </div>
        <div class="cell">
            <div class="cell-label">Total Qty Shipped</div>
            <div class="cell-value">{{ number_format($orderShip['ship_total'] ?? 0) }}</div>
            <div class="cell-unit">Pcs</div>
        </div>
        <div class="cell">
            <div class="cell-label">Total N.W</div>
            <div class="cell-value">{{ number_format($totalNw, 1) }}</div>
            <div class="cell-unit">Kg</div>
        </div>
        <div class="cell">
            <div class="cell-label">Total G.W</div>
            <div class="cell-value">{{ number_format($totalGw, 1) }}</div>
            <div class="cell-unit">Kg</div>
        </div>
        <div class="cell">
            <div class="cell-label">Total Volume</div>
            <div class="cell-value">{{ number_format($totalCbm, 3) }}</div>
            <div class="cell-unit">m&sup3; (CBM)</div>
        </div>
    </div>
    @if ($measCells->isNotEmpty())
        <div class="footnote">
            Carton Measurement (L &times; W &times; H): {{ $measCells->implode('  |  ') }}
        </div>
    @endif

    {{-- ===================== ORDER VS SHIPPED ===================== --}}
    <div class="sec-title">Order vs Shipped Quantity</div>
    <table class="pl-table">
        <thead>
            <tr>
                <th style="width:18%;">Customer / Color / Sec Size</th> {{-- GANTI -- 3 kolom jadi 1 --}}
                <th style="width:9%;">Qty Type</th>
                @foreach ($activeSizes as $i => $sz)
                    <th>{{ $sz }}</th>
                @endforeach
                <th style="width:8%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orderShipByCombo as $combo)
                @php $d = $combo['data']; @endphp
                <tr>
                    <td rowspan="4" class="text-left" style="vertical-align:middle;">
                        <div>{{ $combo['material'] }}@if ($combo['secsz'])
                                &middot; {{ $combo['secsz'] }}
                            @endif
                        </div>
                        <div style="font-weight:700;">{{ $combo['customer'] }}</div>
                    </td>
                    <td class="row-label">Order Qty</td>
                    @foreach ($activeSizes as $i => $sz)
                        <td>{{ $d['order'][$i] ?? '' }}</td>
                    @endforeach
                    <td style="font-weight:700;">{{ $d['order_total'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td class="row-label">Shipped Qty</td>
                    @foreach ($activeSizes as $i => $sz)
                        <td>{{ $d['ship'][$i] ?? '' }}</td>
                    @endforeach
                    <td style="font-weight:700;">{{ $d['ship_total'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td class="row-label">Balance (+/-)</td>
                    @foreach ($activeSizes as $i => $sz)
                        <td>{{ $d['diff'][$i] ?? '' }}</td>
                    @endforeach
                    <td style="font-weight:700;">{{ $d['diff_total'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td class="row-label">Shipped %</td>
                    @foreach ($activeSizes as $i => $sz)
                        <td>{{ ($d['pct'][$i] ?? '') !== '' ? number_format($d['pct'][$i], 1) . '%' : '' }}</td>
                    @endforeach
                    <td style="font-weight:700;">{{ number_format($d['pct_total'] ?? 0, 1) }}%</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td class="text-left">GRAND TOTAL</td> {{-- GANTI colspan="3" -> tanpa colspan (1 kolom) --}}
                <td class="row-label" style="background:#e2e8f0;">Order Qty</td>
                @foreach ($activeSizes as $i => $sz)
                    <td>{{ $orderShip['order'][$i] ?? '' }}</td>
                @endforeach
                <td>{{ $orderShip['order_total'] ?? 0 }}</td>
            </tr>
            <tr class="total-row">
                <td class="text-left"></td>
                <td class="row-label" style="background:#e2e8f0;">Shipped Qty</td>
                @foreach ($activeSizes as $i => $sz)
                    <td>{{ $orderShip['ship'][$i] ?? '' }}</td>
                @endforeach
                <td>{{ $orderShip['ship_total'] ?? 0 }}</td>
            </tr>
            <tr class="total-row">
                <td class="text-left"></td>
                <td class="row-label" style="background:#e2e8f0;">Balance (+/-)</td>
                @foreach ($activeSizes as $i => $sz)
                    <td>{{ $orderShip['diff'][$i] ?? '' }}</td>
                @endforeach
                <td>{{ $orderShip['diff_total'] ?? 0 }}</td>
            </tr>
            <tr class="total-row">
                <td class="text-left"></td>
                <td class="row-label" style="background:#e2e8f0;">Shipped %</td>
                @foreach ($activeSizes as $i => $sz)
                    <td>{{ ($orderShip['pct'][$i] ?? '') !== '' ? number_format($orderShip['pct'][$i], 1) . '%' : '' }}
                    </td>
                @endforeach
                <td>{{ number_format($orderShip['pct_total'] ?? 0, 1) }}%</td>
            </tr>
        </tbody>
    </table>

    {{-- ===================== CARTON BREAKDOWN ===================== --}}
    <div class="sec-title">Carton Breakdown Detail</div>
    <div class="footnote" style="margin-bottom:6px;">
        <span class="ctn-dot pending" style="display:inline-block;"></span> Pending &nbsp;
        <span class="ctn-dot partial" style="display:inline-block;"></span> Partial &nbsp;
        <span class="ctn-dot complete" style="display:inline-block;"></span> Complete &nbsp;
        <span class="ctn-dot sealed" style="display:inline-block;"></span> Sealed
    </div>

    <table class="pl-table">
        <thead>
            <tr>
                <th style="width:16%;">Customer / Color / Sec Size</th> {{-- GANTI -- 3 kolom jadi 1 --}}
                <th style="width:11%;">Size Ratio</th>
                <th style="width:6%;">Ctn</th>
                <th style="width:6%;">Pcs/Ctn</th>
                <th>Carton No.</th>
            </tr>
        </thead>
        <tbody>
            {{-- 1) Carton simple --}}
            @foreach ($detailPackingSimpleRows as $row)
                <tr>
                    <td class="text-left">
                        <div>{{ $row['material'] }}@if ($row['secsz'])
                                &middot; {{ $row['secsz'] }}
                            @endif
                        </div>
                        <div style="font-weight:700;">{{ $row['customer'] }}</div>
                    </td>
                    <td class="text-left">
                        @foreach ($row['sizeLines'] as $line)
                            {{ $line }}<br>
                        @endforeach
                    </td>
                    <td style="font-weight:700;">{{ $row['ctn'] }}</td>
                    <td style="font-weight:700;">{{ $row['pcsp'] }}</td>
                    <td class="text-left">
                        @foreach ($row['cartonRows'] as $c)
                            <span class="ctn-tag"><span
                                    class="ctn-dot {{ $c['statusKey'] }}"></span>{{ $c['label'] }}</span>
                        @endforeach
                    </td>
                </tr>
            @endforeach

            {{-- 2) Carton Mixed antar Customer/Color/Sec Size -- rowspan SEKARANG
             pakai colorRowspan (yang sudah dihitung dari customer+material
             di controller), jadi 1 sel gabungan Customer/Color berlaku utk
             sisa baris turunannya (Sec Size beda tetap baris terpisah). --}}
            @foreach ($detailPackingMixedRows as $row)
                <tr>
                    @if ($row['showColor'])
                        <td rowspan="{{ $row['colorRowspan'] }}" class="text-left" style="vertical-align:middle;">
                            <div>{{ $row['material'] }}</div>
                            <div style="font-weight:700;">{{ $row['customer'] }}</div>
                        </td>
                    @endif
                    <td class="text-left">
                        @if ($row['secsz'])
                            <div style="font-size:9px; color:#64748b;">{{ $row['secsz'] }}</div>
                        @endif
                        @foreach ($row['sizeLines'] as $line)
                            {{ $line }}<br>
                        @endforeach
                    </td>
                    @if ($row['isFirstOfCarton'])
                        <td rowspan="{{ $row['rowspanCarton'] }}" style="vertical-align:middle; font-weight:700;">
                            {{ $row['ctn'] }}</td>
                    @endif
                    <td style="font-weight:700;">{{ $row['pcsp'] }}</td>
                    @if ($row['isFirstOfCarton'])
                        <td rowspan="{{ $row['rowspanCarton'] }}" style="vertical-align:middle;" class="text-left">
                            @foreach ($row['cartonInputs'] as $c)
                                <span class="ctn-tag"><span
                                        class="ctn-dot {{ $c['statusKey'] }}"></span>{{ $c['label'] }}</span>
                            @endforeach
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>


    <div class="footnote">Last data update: {{ $dtp ? $dtp->tanggal . ' ' . $dtp->waktu : '-' }}</div>

    {{-- ===================== SIGNATURE ===================== --}}
    <table class="signature-table">
        <thead>
            <tr>
                <th style="width:20%;">Prepared By</th>
                <th style="width:20%;">Chief Finishing</th>
                <th style="width:20%;">SPV Finishing</th>
                <th style="width:20%;">QA / Accuracy</th>
                <th style="width:20%;">Production Manager</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

</body>

</html>
