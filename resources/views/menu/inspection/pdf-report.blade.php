<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Inspeksi {{ $noInspec }}</title>
    <style>
        @page { size: A4; margin: 15mm 12mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111827; margin: 0; }

        .report-header {
            display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 3px solid #1e293b; padding-bottom: 10px; margin-bottom: 14px;
        }
        .report-title { font-size: 17px; font-weight: 800; color: #1e293b; letter-spacing: .3px; }
        .report-subtitle { font-size: 11px; color: #64748b; margin-top: 2px; }
        .report-no { text-align: right; font-size: 12px; }
        .report-no strong { font-size: 14px; color: #1e293b; }

        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px 24px; margin-bottom: 14px; font-size: 11px; }
        .info-grid .label { color: #64748b; font-size: 9.5px; text-transform: uppercase; letter-spacing: .3px; }
        .info-grid .value { font-weight: 700; color: #111827; }

        .stat-row { display: flex; gap: 10px; margin-bottom: 16px; }
        .stat-box {
            flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; text-align: center;
        }
        .stat-box .stat-label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: .3px; }
        .stat-box .stat-value { font-size: 18px; font-weight: 800; color: #111827; margin-top: 2px; }
        .stat-box.pass .stat-value { color: #16a34a; }
        .stat-box.defect .stat-value { color: #dc2626; }

        .result-stamp {
            display: inline-block; border: 3px solid; border-radius: 8px; padding: 10px 28px;
            font-size: 20px; font-weight: 900; letter-spacing: 2px; transform: rotate(-3deg);
        }
        .result-stamp.pass { border-color: #16a34a; color: #16a34a; }
        .result-stamp.reject { border-color: #dc2626; color: #dc2626; }

        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.data-table th {
            background: #1e293b; color: #fff; font-size: 9.5px; text-transform: uppercase;
            padding: 6px 8px; text-align: left; letter-spacing: .3px;
        }
        table.data-table td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10.5px; }
        table.data-table tr:nth-child(even) td { background: #f8fafc; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 9.5px; font-weight: 700; }
        .pill.pass { background: #dcfce7; color: #166534; }
        .pill.defect { background: #fee2e2; color: #991b1b; }

        .section-title {
            font-size: 12.5px; font-weight: 800; color: #1e293b; margin: 18px 0 8px;
            border-left: 4px solid #1e293b; padding-left: 8px;
        }

        .signature-row { display: flex; justify-content: space-between; margin-top: 40px; }
        .signature-box { width: 30%; text-align: center; }
        .signature-box .sig-line { border-top: 1px solid #111827; margin-top: 50px; padding-top: 6px; font-size: 10.5px; font-weight: 700; }
        .signature-box .sig-role { font-size: 9.5px; color: #64748b; }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    {{-- HEADER --}}
    <div class="report-header">
        <div>
            <div class="report-title">LAPORAN INSPEKSI</div>
            <div class="report-subtitle">Quality Inspection Report</div>
        </div>
        <div class="report-no">
            <div>No. Dokumen</div>
            <strong>{{ $noInspec }}</strong>
        </div>
    </div>

    {{-- INFO PO/OP --}}
    <div class="info-grid">
        <div>
            <div class="label">PO No / OP</div>
            <div class="value">{{ $poInfo->POno ?? '-' }} / {{ $poInfo->OP ?? '-' }}</div>
        </div>
        <div>
            <div class="label">Buyer</div>
            <div class="value">{{ $poInfo->buyer ?? '-' }}</div>
        </div>
        <div>
            <div class="label">Style</div>
            <div class="value">{{ $poInfo->style ?? '-' }}</div>
        </div>
        <div>
            <div class="label">Customer / Place</div>
            <div class="value">{{ $poInfo->customer ?? '-' }}</div>
        </div>
        <div>
            <div class="label">Tanggal Inspeksi</div>
            <div class="value">{{ \Carbon\Carbon::parse($inspec->tgl)->format('d M Y') }}</div>
        </div>
        <div>
            <div class="label">Total Carton Diinspeksi</div>
            <div class="value">{{ $totalCarton }} carton</div>
        </div>
    </div>

    {{-- STATISTIK SAMPLE --}}
    <div class="stat-row">
        <div class="stat-box">
            <div class="stat-label">Total Sample</div>
            <div class="stat-value">{{ $inspec->totpcs }}</div>
        </div>
        <div class="stat-box pass">
            <div class="stat-label">Pass</div>
            <div class="stat-value">{{ $totalPass }}</div>
        </div>
        <div class="stat-box defect">
            <div class="stat-label">Defect</div>
            <div class="stat-value">{{ $totalDefect }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">AQL / Batas Reject</div>
            <div class="stat-value">{{ $inspec->aql }}</div>
        </div>
    </div>

    {{-- HASIL --}}
    <div style="text-align:center; margin-bottom: 18px;">
        <span class="result-stamp {{ (int) $inspec->hasil === 1 ? 'pass' : 'reject' }}">
            {{ (int) $inspec->hasil === 1 ? 'LULUS / PASSED' : 'REJECT' }}
        </span>
    </div>

    {{-- DETAIL SAMPLE PER CARTON --}}
    <div class="section-title">Detail Sample per Carton</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="60">No</th>
                <th>Carton</th>
                <th>Color</th>
                <th>Sec Size</th>
                <th>Size</th>
                <th class="text-end">Qty</th>
                <th>Status</th>
                <th>Jenis Defect</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detailRows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['carton'] }}</td>
                    <td>{{ $row['color'] ?? '-' }}</td>
                    <td>{{ $row['secsz'] ?? '-' }}</td>
                    <td>{{ $row['size'] }}</td>
                    <td class="text-end">{{ $row['qty'] }}</td>
                    <td>
                        <span class="pill {{ $row['stspass'] === 1 ? 'pass' : 'defect' }}">
                            {{ $row['stspass'] === 1 ? 'Pass' : 'Defect' }}
                        </span>
                    </td>
                    <td>{{ $row['defects'] ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- REKAP DEFECT PER KATEGORI --}}
    @if($defectSummary->isNotEmpty())
        <div class="section-title">Rekap Defect per Kategori</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Jenis Defect</th>
                    <th class="text-end" width="100">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($defectSummary as $cat)
                    @foreach($cat['detail'] as $defectnm => $count)
                        <tr>
                            <td>{{ $loop->first ? $cat['subnm'] : '' }}</td>
                            <td>{{ $defectnm }}</td>
                            <td class="text-end">{{ $count }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- TANDA TANGAN -- standar sign-off laporan QC garment --}}
    {{-- <div class="signature-row">
        <div class="signature-box">
            <div class="sig-line">Inspector QC</div>
            <div class="sig-role">Nama &amp; Tanda Tangan</div>
        </div>
        <div class="signature-box">
            <div class="sig-line">Supervisor QA</div>
            <div class="sig-role">Nama &amp; Tanda Tangan</div>
        </div>
        <div class="signature-box">
            <div class="sig-line">Buyer QC Representative</div>
            <div class="sig-role">Nama &amp; Tanda Tangan (jika ada)</div>
        </div>
    </div> --}}

</body>
</html>