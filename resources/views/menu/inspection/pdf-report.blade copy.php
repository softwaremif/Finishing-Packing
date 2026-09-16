<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Inspeksi {{ $noInspec }}</title>
    <style>
        @page { size: A4; margin: 10mm 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9px; color: #000; margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #000; padding: 3px 6px; vertical-align: middle; }
        table.plain, table.plain td { border: none; padding: 1px 3px; }

        .doc-code {
            border: 1.5px solid #000; padding: 4px 10px; font-weight: 800; font-size: 11px;
            float: right; margin-top: -4px;
        }
        .header-top { overflow: hidden; margin-bottom: 4px; }
        .company-name { font-weight: 800; font-size: 14px; }
        .company-sub { font-size: 8.5px; color: #444; }

        .report-title { text-align: center; font-weight: 800; font-size: 15px; margin: 10px 0 6px; }
        .stage-row { text-align: center; margin-bottom: 10px; font-size: 9.5px; }
        .stage-row .chk { display: inline-block; margin: 0 16px; }
        .box { display: inline-block; width: 11px; height: 11px; border: 1px solid #000; text-align: center;
               line-height: 10px; font-size: 8px; font-weight: 800; margin-right: 4px; vertical-align: middle; }

        .lbl { font-weight: 700; font-size: 8px; text-transform: uppercase; color: #333; width: 38%; }
        .val { font-weight: 700; }

        .section-title {
            font-weight: 800; font-size: 9.5px; text-transform: uppercase; margin: 12px 0 4px;
            /* border-left: 3px solid #000; padding-left: 6px; */
        }

        .mix-badge {
            display: inline-block; font-size: 8px; font-weight: 800; color: #6d28d9;
            background: #ede9fe; border-radius: 8px; padding: 1px 7px; margin-left: 4px;
        }
        .bundle-note { font-size: 7.5px; color: #92400e; font-weight: 600; margin-top: 1px; }
        .mix-note { font-size: 7.5px; color: #6d28d9; font-weight: 600; margin-top: 1px; }

        .defect-grid td { font-size: 8px; padding: 2px 5px; }
        .defect-grid th { font-size: 8px; text-align: center; background: #eee; }

        .result-row { margin: 12px 0; }
        .result-box { display: inline-block; border: 2px solid #000; padding: 4px 16px; font-weight: 800; font-size: 12px; margin-right: 20px; }
        .result-box .box { width: 13px; height: 13px; line-height: 12px; font-size: 10px; }

        .sig-row { margin-top: 34px; overflow: hidden; }
        .sig-col { width: 33.33%; float: left; text-align: center; }
        .sig-line { border-top: 1px solid #000; margin: 42px 14px 4px; padding-top: 3px; font-size: 8.5px; font-weight: 700; }

        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">

    {{-- ===================== HEADER ===================== --}}
    <div class="header-top">
        <div class="doc-code">{{ $noInspec }}</div>
        <div class="company-name">PT. [NAMA PERUSAHAAN]</div>
        <div class="company-sub">Quality Assurance Department</div>
    </div>

    <div class="report-title">INSPECTION REPORT</div>
    <div class="stage-row">
        <span class="chk"><span class="box">&nbsp;</span>PILOT RUN</span>
        <span class="chk"><span class="box">&nbsp;</span>IN LINE</span>
        <span class="chk"><span class="box">&nbsp;</span>PRE FINAL</span>
        <span class="chk"><span class="box">X</span>FINAL</span>
    </div>

    {{-- ===================== INFO GRID 3 KOLOM ===================== --}}
    <table class="plain" style="margin-bottom:6px;">
        <tr>
            <td style="width:33%; vertical-align:top;">
                <table class="plain">
                    <tr><td class="lbl">Manufacturer</td><td class="val">&nbsp;</td></tr>
                    <tr><td class="lbl">Order Qty</td><td class="val">&nbsp; Pcs.</td></tr>
                    <tr><td class="lbl">Shipment Qty</td><td class="val">{{ $totalCarton }} Ctn</td></tr>
                    <tr><td class="lbl">Lot / Balance</td><td class="val">&nbsp;</td></tr>
                    <tr><td class="lbl">Shipment Mode</td><td class="val"><span class="box" style="width:9px;height:9px;">&nbsp;</span> Air &nbsp; <span class="box" style="width:9px;height:9px;">&nbsp;</span> Boat</td></tr>
                </table>
            </td>
            <td style="width:34%; vertical-align:top;">
                <table class="plain">
                    {{-- BARU -- kalau dokumen mencakup >1 PO/OP (Mix Polibag),
                         tampilkan badge + daftar lengkap, BUKAN cuma PO
                         representatif seperti sebelumnya. --}}
                    <tr>
                        <td class="lbl">PO No. / OP No.</td>
                        <td class="val">
                            @if ($allPoOpPairs->count() > 1)
                                <span class="mix-badge">MIX POLIBAG &middot; {{ $allPoOpPairs->count() }} PO</span>
                                <div style="font-size:7.5px; font-weight:400; margin-top:2px;">
                                    @foreach ($allPoOpPairs as $p)
                                        {{ $p['POno'] ?? '-' }} / {{ $p['OP'] ?? '-' }}@if (!$loop->last), @endif
                                    @endforeach
                                </div>
                            @else
                                {{ $poInfo->POno ?? '-' }} / {{ $poInfo->OP ?? '-' }}
                            @endif
                        </td>
                    </tr>
                    <tr><td class="lbl">Style No.</td><td class="val">{{ $poInfo->style ?? '-' }}</td></tr>
                    <tr><td class="lbl">Color</td><td class="val">&nbsp;</td></tr>
                    <tr><td class="lbl">Destination</td><td class="val">{{ $poInfo->customer ?? '-' }}</td></tr>
                    <tr><td class="lbl">Buyer</td><td class="val">{{ $poInfo->buyer ?? '-' }}</td></tr>
                </table>
            </td>
            <td style="width:33%; vertical-align:top;">
                <table class="plain">
                    <tr><td class="lbl">Inspection Date</td><td class="val">{{ \Carbon\Carbon::parse($inspec->tgl)->format('d M Y') }}</td></tr>
                </table>
                <div class="section-title" style="margin-top:6px; font-size:8.5px;">Sampling Plan AQL</div>
                <table>
                    <tr><th>Sample Size</th><th>Accept Level</th><th>Reject Level</th></tr>
                    <tr style="text-align:center;">
                        <td>{{ $inspec->totpcs }}</td>
                        <td>{{ max(0, $inspec->aql - 1) }}</td>
                        <td>{{ $inspec->aql }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===================== ACCESSORIES CHECK LIST (isian manual) ===================== --}}
    <div class="section-title">Accessories Check List <span style="font-weight:400; text-transform:none;">(diisi manual saat inspeksi)</span></div>
    <table class="plain">
        <tr>
            @foreach (['Main Label','Care / Content Label','Factory ID Label','Packaging'] as $item)
                <td style="width:25%;"><span class="box">&nbsp;</span>{{ $item }}</td>
            @endforeach
        </tr>
        <tr>
            @foreach (['Bottons','Lining','Shoulder Pads','Zipper'] as $item)
                <td><span class="box">&nbsp;</span>{{ $item }}</td>
            @endforeach
        </tr>
        <tr>
            @foreach (['Hangtag','Price Pocket','Polybag','Elastic'] as $item)
                <td><span class="box">&nbsp;</span>{{ $item }}</td>
            @endforeach
        </tr>
        <tr>
            @foreach (['Button Size','Interlining','Belts','Threads'] as $item)
                <td><span class="box">&nbsp;</span>{{ $item }}</td>
            @endforeach
        </tr>
    </table>

    {{-- ===================== FAULT CODE / DEFECT SUMMARY ===================== --}}
    <div class="section-title">Fault Code &amp; Defects Found</div>
    @if ($defectSummary->isNotEmpty())
        <table class="defect-grid">
            <tr>
                <th style="width:15%;">Qty</th>
                <th>Kategori / Jenis Defect</th>
            </tr>
            @foreach ($defectSummary as $cat)
                @foreach ($cat['detail'] as $defectnm => $count)
                    <tr>
                        <td style="text-align:center;">{{ $count }}</td>
                        <td>{{ $loop->parent->first ? strtoupper($cat['subnm']) . ' -- ' : '' }}{{ $defectnm }}</td>
                    </tr>
                @endforeach
            @endforeach
        </table>
    @else
        <table class="defect-grid"><tr><td style="text-align:center; color:#666;">Tidak ada defect ditemukan.</td></tr></table>
    @endif

    {{-- ===================== DETAIL PER CARTON (Mix PO & Bundle ditandai) ===================== --}}
    <div class="section-title">Detail per Carton</div>
    <table>
        <thead>
            <tr>
                <th style="width:22%;">Carton #</th>
                <th>Sizes</th>
                <th>Colors</th>
                <th style="width:75px;">Inspect Qty</th>
                <th style="width:75px;">Defect Qty</th>
            </tr>
        </thead>
        <tbody>
            @php
                $byCarton = collect($detailRows)->groupBy('carton');
            @endphp
            @foreach ($byCarton as $cartonNo => $rows)
                @php
                    $rowsColl = collect($rows);
                    $poOpPairs = $rowsColl->map(fn ($r) => ($r['POno'] ?? '-') . ' / ' . ($r['OP'] ?? '-'))->unique()->values();
                    $isMix = $poOpPairs->count() > 1;
                    $bundleName = $rowsColl->pluck('bundle_carton')->filter()->first();
                @endphp
                <tr>
                    <td>
                        <strong>{{ $cartonNo }}</strong>
                        @if ($isMix)
                            <div class="mix-note">Mix PO: {{ $poOpPairs->implode(', ') }}</div>
                        @endif
                        @if ($bundleName)
                            <div class="bundle-note">Bagian dari Carton Besar: {{ $bundleName }}</div>
                        @endif
                    </td>
                    <td>{{ $rowsColl->pluck('size')->unique()->implode(', ') }}</td>
                    <td>{{ $rowsColl->pluck('color')->filter()->unique()->implode(', ') }}</td>
                    <td style="text-align:center;">{{ $rowsColl->sum('qty') }}</td>
                    <td style="text-align:center;">{{ $rowsColl->where('stspass', 0)->sum('qty') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ===================== COMMENTS ===================== --}}
    <div class="section-title">Comments</div>
    <table class="plain">
        <tr>
            <td style="border-bottom:1px solid #000; min-height:24px; padding:4px 3px;">
                {{ $inspec->keterangan ?: '' }}
            </td>
        </tr>
    </table>

    {{-- ===================== INSPECTION SUMMARY ===================== --}}
    <div class="section-title">Inspection Summary</div>
    <table class="plain">
        <tr>
            <td class="lbl" style="width:20%;">General Appearance</td>
            <td style="width:30%;"><span class="box">&nbsp;</span> Acceptable &nbsp; <span class="box">&nbsp;</span> Unacceptable</td>
            <td class="lbl" style="width:20%;">Weight</td>
            <td><span class="box">&nbsp;</span> Acceptable &nbsp; <span class="box">&nbsp;</span> Unacceptable</td>
        </tr>
        <tr>
            <td class="lbl">Accessories</td>
            <td><span class="box">&nbsp;</span> Acceptable &nbsp; <span class="box">&nbsp;</span> Unacceptable</td>
            <td class="lbl">Measurement</td>
            <td><span class="box">&nbsp;</span> Acceptable &nbsp; <span class="box">&nbsp;</span> Unacceptable</td>
        </tr>
        <tr>
            <td class="lbl">Workmanship</td>
            <td><span class="box">&nbsp;</span> Acceptable &nbsp; <span class="box">&nbsp;</span> Unacceptable</td>
            <td class="lbl">Fitting</td>
            <td><span class="box">&nbsp;</span> Acceptable &nbsp; <span class="box">&nbsp;</span> Unacceptable</td>
        </tr>
    </table>

    {{-- ===================== RESULT ===================== --}}
    <div class="result-row">
        <span class="result-box"><span class="box">{{ (int) $inspec->hasil === 1 ? 'X' : '' }}</span> ACCEPT</span>
        <span class="result-box"><span class="box">{{ (int) $inspec->hasil === 0 ? 'X' : '' }}</span> REJECT</span>
    </div>

    {{-- ===================== TANDA TANGAN ===================== --}}
    <div class="section-title" style="border:none; padding-left:0;">Confirmed and Signed By</div>
    <div class="sig-row">
        <div class="sig-col"><div class="sig-line">Quality Control</div></div>
        <div class="sig-col"><div class="sig-line">QA Manager</div></div>
        <div class="sig-col"><div class="sig-line">Production Manager</div></div>
    </div>

</body>
</html>