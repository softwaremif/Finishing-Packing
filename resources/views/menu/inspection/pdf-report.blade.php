<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Inspeksi {{ $noInspec }}</title>
    <style>
        @page {
            size: A4;
            margin: 12mm 11mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5px;
            color: #1e293b;
            margin: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        table.plain,
        table.plain td {
            border: none;
            padding: 0;
        }

        /* ===================== HEADER BANNER ===================== */
        .banner {
            /* background: #1e293b; color: #fff; border-radius: 8px; */
            padding: 12px 16px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .banner .company-name {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: .2px;
        }

        .banner .company-sub {
            font-size: 8.5px;
            color: #000000;
            margin-top: 1px;
        }

        .banner .doc-box {
            float: right;
            text-align: right;
            background: rgba(255, 255, 255, .08);
            border-radius: 6px;
            padding: 6px 12px;
        }

        .banner .doc-box .doc-label {
            font-size: 7.5px;
            color: #494e54;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .banner .doc-box .doc-no {
            font-size: 12.5px;
            font-weight: 800;
        }

        .report-title-row {
            text-align: center;
            margin: 10px 0 8px;
        }

        .report-title {
            font-size: 14px;
            font-weight: 800;
            color: #1e293b;
        }

        .stage-row {
            margin-top: 4px;
            font-size: 9px;
        }

        .stage-row .chk {
            display: inline-block;
            margin: 0 12px;
        }

        .box {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1.3px solid #1e293b;
            text-align: center;
            line-height: 9px;
            font-size: 7.5px;
            font-weight: 800;
            margin-right: 3px;
            vertical-align: middle;
            border-radius: 2px;
        }

        /* ===================== INFO CARDS ===================== */
        .info-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 9px 12px;
            margin-bottom: 8px;
        }

        .info-card-title {
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: .4px;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #f1f5f9;
        }

        .lbl {
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #494e54;
            width: 42%;
            padding: 2px 0;
        }

        .val {
            font-size: 9.5px;
            font-weight: 700;
            color: #1e293b;
            padding: 2px 0;
        }

        .mix-badge {
            display: inline-block;
            font-size: 7.5px;
            font-weight: 800;
            color: #6d28d9;
            background: #ede9fe;
            border-radius: 8px;
            padding: 1px 7px;
            margin-left: 2px;
        }

        .bundle-note {
            font-size: 7px;
            color: #92400e;
            font-weight: 600;
            margin-top: 1px;
        }

        .mix-note {
            font-size: 7px;
            color: #6d28d9;
            font-weight: 600;
            margin-top: 1px;
        }

        /* ===================== SECTION TITLES ===================== */
        .section-title {
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            color: #1e293b;
            margin: 12px 0 5px;
            padding-left: 7px;
            letter-spacing: .3px;
        }

        .section-hint {
            font-weight: 400;
            text-transform: none;
            color: #494e54;
            font-size: 7.5px;
        }

        /* ===================== TABLES ===================== */
        table.grid-table {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }

        table.grid-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: .3px;
            padding: 5px 6px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        table.grid-table td {
            padding: 5px 6px;
            font-size: 8.5px;
            border-bottom: 1px solid #f1f5f9;
        }

        table.grid-table tr:last-child td {
            border-bottom: none;
        }

        table.grid-table tr:nth-child(even) td {
            background: #fafbfc;
        }

        .pill {
            display: inline-block;
            padding: 1.5px 7px;
            border-radius: 999px;
            font-size: 7.5px;
            font-weight: 800;
        }

        .pill.pass {
            background: #dcfce7;
            color: #166534;
        }

        .pill.defect {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ===================== RESULT STAMP ===================== */
        .result-row {
            text-align: center;
            margin: 14px 0;
        }

        .result-stamp {
            display: inline-block;
            border: 2.5px solid;
            border-radius: 8px;
            padding: 7px 26px;
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 1.5px;
        }

        .result-stamp.pass {
            border-color: #16a34a;
            color: #16a34a;
            background: #f0fdf4;
        }

        .result-stamp.reject {
            border-color: #dc2626;
            color: #dc2626;
            background: #fef2f2;
        }

        /* ===================== SIGNATURE ===================== */
        .sig-row {
            margin-top: 30px;
            overflow: hidden;
        }

        .sig-col {
            width: 33.33%;
            float: left;
            text-align: center;
        }

        .sig-line {
            border-top: 1px solid #1e293b;
            margin: 38px 14px 4px;
            padding-top: 4px;
            font-size: 8.5px;
            font-weight: 700;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    {{-- ===================== BANNER HEADER ===================== --}}
    <div class="banner">
        <div class="doc-box">
            <div class="doc-label">No. Dokumen</div>
            <div class="doc-no">{{ $noInspec }}</div>
        </div>
        <div class="company-name">PT. MORICH INDO FASHION</div>
        <div class="company-sub">Quality Assurance Department</div>
    </div>

    <div class="report-title-row">
        <div class="report-title">INSPECTION REPORT</div>
        <div class="stage-row">
            <span class="chk"><span class="box">&nbsp;</span>PRE FINAL</span>
            <span class="chk"><span class="box">X</span>FINAL</span>
        </div>
    </div>

    {{-- ===================== INFO CARDS 3 KOLOM ===================== --}}
    <table class="plain">
        <tr>
            <td style="width:33%; padding-right:6px; vertical-align:top;">
                <div class="info-card">
                    <div class="info-card-title">Order Information</div>
                    <table class="plain">
                        <tr>
                            <td class="lbl">Order Qty</td>
                            <td class="val">{{ $poInfo->qty ? number_format($poInfo->qty) . ' Pcs' : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Shipment Qty</td>
                            <td class="val">{{ $totalCarton }} Ctn</td>
                        </tr>
                        <tr>
                            <td class="lbl">Lot / Balance</td>
                            <td class="val">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="lbl">Shipment Mode</td>
                            <td class="val"><span class="box" style="width:8px;height:8px;">&nbsp;</span> Air
                                &nbsp; <span class="box" style="width:8px;height:8px;">&nbsp;</span> Boat</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width:34%; padding-right:6px; vertical-align:top;">
                <div class="info-card">
                    <div class="info-card-title">PO / Style</div>
                    <table class="plain">
                        <tr>
                            <td class="lbl">PO No. / OP No.</td>
                            <td class="val">
                                @if ($allPoOpPairs->count() > 1)
                                    {{-- <span class="mix-badge">MIX POLIBAG &middot; {{ $allPoOpPairs->count() }} PO</span> --}}
                                    <div style="font-size:7px; font-weight:400; margin-top:2px;">
                                        @foreach ($allPoOpPairs as $p)
                                            {{ $p['POno'] ?? '-' }} / {{ $p['OP'] ?? '-' }}@if (!$loop->last)
                                                ,
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    {{ $poInfo->POno ?? '-' }} / {{ $poInfo->OP ?? '-' }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl">Style No.</td>
                            <td class="val">{{ $poInfo->style ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Destination</td>
                            <td class="val">{{ $poInfo->customer ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Buyer</td>
                            <td class="val">{{ $poInfo->buyer ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width:33%; vertical-align:top;">
                <div class="info-card">
                    <div class="info-card-title">Jadwal Inspect</div>
                    <table class="plain">
                        <tr>
                            <td class="lbl">Tgl Inspeksi</td>
                            <td class="val">{{ \Carbon\Carbon::parse($inspec->tgl)->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Tgl Selesai</td>
                            <td class="val">
                                {{ $inspec->enddate ? \Carbon\Carbon::parse($inspec->enddate)->format('d M Y') : '-' }}
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="info-card" style="margin-top:6px;">
                    <div class="info-card-title">Sampling Plan AQL</div>
                    <table class="grid-table">
                        <tr>
                            <th>Sample</th>
                            <th>Accept Level</th>
                            <th>Reject Level</th>
                        </tr>
                        <tr style="text-align:center;">
                            <td>{{ $inspec->totpcs }}</td>
                            <td>{{ max(0, $inspec->aql - 1) }}</td>
                            <td>{{ $inspec->aql }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ===================== FAULT CODE / DEFECT SUMMARY ===================== --}}
    <div class="section-title">Fault Code &amp; Defects Found</div>
    @if ($defectSummary->isNotEmpty())
        <table class="grid-table">
            <tr>
                <th style="width:15%;">Qty</th>
                <th>Kategori / Jenis Defect</th>
            </tr>
            @foreach ($defectSummary as $cat)
                @foreach ($cat['detail'] as $defectnm => $count)
                    <tr>
                        <td style="text-align:center; font-weight:700;">{{ $count }}</td>
                        <td>{{ $loop->parent->first ? strtoupper($cat['subnm']) . ' -- ' : '' }}{{ $defectnm }}
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </table>
    @else
        <table class="grid-table">
            <tr>
                <td style="text-align:center; color:#494e54; padding:10px;">Tidak ada defect ditemukan.</td>
            </tr>
        </table>
    @endif

    {{-- ===================== DETAIL PER CARTON ===================== --}}
    <div class="section-title">Detail per Carton</div>
    <table class="grid-table">
        <thead>
            <tr>
                <th style="width:20%;">Carton #</th>
                <th>Sizes</th>
                <th>Colors</th>
                <th style="width:60px;">Inspect</th>
                <th style="width:60px;">Defect</th>
                <th style="width:75px;">Tgl Kembali</th>
            </tr>
        </thead>
        <tbody>
            @php
                $byCartonPart = collect($detailRows)->groupBy(function ($r) {
                    $partKey =
                        ($r['part'] ?? null) === null ||
                        $r['part'] === '' ||
                        (is_numeric($r['part'] ?? null) && (float) $r['part'] === 0.0)
                            ? ''
                            : (string) $r['part'];
                    return $r['carton'] . '|' . $partKey;
                });
            @endphp
            @foreach ($byCartonPart as $groupKey => $rows)
                @php
                    $rowsColl = collect($rows);
                    $repRow = $rowsColl->first();
                    $cartonNo = $repRow['carton']; // BARU -- label ASLI, bukan groupKey
                    $partVal = $repRow['part'] ?? null;
                    $partLabel =
                        $partVal !== null && $partVal !== '' && !(is_numeric($partVal) && (float) $partVal === 0.0)
                            ? ((string) $partVal === '10'
                                ? 'Complete'
                                : 'Session ' . $partVal)
                            : null;

                    $poOpPairs = $rowsColl
                        ->map(fn($r) => ($r['POno'] ?? '-') . ' / ' . ($r['OP'] ?? '-'))
                        ->unique()
                        ->values();
                    $isMix = $poOpPairs->count() > 1;
                    $bundleName = $rowsColl->pluck('bundle_carton')->filter()->first();
                    $kembaliVal = $rowsColl->pluck('kembali')->filter()->first();
                @endphp
                <tr>
                    <td>
                        <strong>{{ $cartonNo }}</strong>
                        @if ($partLabel)
                            <span style="font-size:8px; color:#6d28d9; font-weight:600;">&middot;
                                {{ $partLabel }}</span>
                        @endif
                        @if ($isMix)
                            <div class="mix-note">Mix PO: {{ $poOpPairs->implode(', ') }}</div>
                        @endif
                        @if ($bundleName)
                            <div class="bundle-note">Bundle: {{ $bundleName }}</div>
                        @endif
                    </td>
                    <td>{{ $rowsColl->pluck('size')->unique()->implode(', ') }}</td>
                    <td>{{ $rowsColl->pluck('color')->filter()->unique()->implode(', ') }}</td>
                    <td style="text-align:center; font-weight:700;">{{ $rowsColl->sum('qty') }}</td>
                    <td style="text-align:center; font-weight:700; color:#dc2626;">
                        {{ $rowsColl->where('stspass', 0)->sum('qty') }}</td>
                    <td style="text-align:center;">
                        {{ $kembaliVal ? \Carbon\Carbon::parse($kembaliVal)->format('d/m/y') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ===================== COMMENTS ===================== --}}
    <div class="section-title">Comments</div>
    <div class="info-card" style="min-height:26px;">{{ $inspec->keterangan ?: '' }}</div>

    {{-- ===================== RESULT ===================== --}}
    <div class="result-row">
        <span class="result-stamp {{ (int) $inspec->hasil === 1 ? 'pass' : 'reject' }}">
            {{ (int) $inspec->hasil === 1 ? 'ACCEPT / LULUS' : 'REJECT' }}
        </span>
    </div>

</body>

</html>
