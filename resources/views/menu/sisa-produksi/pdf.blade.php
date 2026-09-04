<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    {{-- <title>Morich Indo Fashion|Data Stock - OP {{ $dt->OP }}</title> --}}
    <title>Morich Indo Fashion</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            font-size: 13px;
            color: #000;
            background: #f3f4f6;
            margin: 0;
            padding: 24px;
        }

        .sheet {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            padding: 24px 32px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.15);
        }

        .toolbar {
            max-width: 1000px;
            margin: 0 auto 12px;
            display: flex;
            justify-content: flex-end;
        }

        .btn-print {
            background: #1e293b;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
        }

        .btn-print:hover {
            background: #334155;
        }

        .ftitle {
            font-size: 15px;
            font-weight: bold;
            color: #444;
            padding: 5px 0;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            text-align: center;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        table.plain, table.plain td, table.plain th {
            border: none;
        }

        table.report th,
        table.report td {
            border: 1px solid #000;
            padding: 4px 7px;
            text-align: center;
            font-size: 13px;
        }

        table.report th {
            background: #eee;
        }

        table.info td {
            border: none;
            padding: 2px 4px;
            font-size: 13px;
            vertical-align: top;
        }

        .dashed-divider {
            border: none;
            border-top: 1px dashed #ccc;
            margin: 16px 0;
        }

        .layout-col-left {
            width: 30%;
            vertical-align: top;
        }

        .layout-col-right {
            width: 70%;
            vertical-align: top;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                box-shadow: none;
                max-width: 100%;
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <button type="button" class="btn-print" onclick="window.print()">
            Cetak / Simpan sebagai PDF
        </button>
    </div>

    <div class="sheet">

        {{-- ============================================================
             SECTION 1: DATA STOCK (per grade, breakdown per material)
        ============================================================ --}}
        @foreach ($stockByGrade as $stock)
            <div class="ftitle">DATA STOCK</div>

            <table class="info">
                <tr>
                    <td width="10%">Season</td>
                    <td width="15%">: {{ $dt->season }}</td>
                    <td width="10%">OP#</td>
                    <td width="15%">: {{ $dt->OP }}</td>
                </tr>
                <tr>
                    <td>Buyer</td>
                    <td>: {{ $dt->buyer }}</td>
                    <td>Style</td>
                    <td>: {{ $dt->style }}</td>
                    <td>Silhouette</td>
                    <td>: {{ $dt->silhouette }}</td>
                </tr>
                <tr>
                    <td>Grade</td>
                    <td>: {{ $stock['grade'] }}</td>
                </tr>
            </table>
            <br>

            <table class="report">
                <thead>
                    <tr>
                        <th rowspan="2">Color</th>
                        <th colspan="{{ count($activeSizes) }}">Size & Qty</th>
                        <th rowspan="2">Total</th>
                        <th rowspan="2">Grade</th>
                    </tr>
                    <tr>
                        @foreach ($activeSizes as $size)
                            <th>{{ $size }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stock['byMaterial'] as $row)
                        <tr>
                            <th>{{ $row->material }}</th>
                            @foreach ($activeSizes as $i => $size)
                                <td>{{ ($row->{"qty$i"} ?? 0) ?: '' }}</td>
                            @endforeach
                            <th>{{ $row->pcs }}</th>
                            <th>{{ $stock['grade'] }}</th>
                        </tr>
                    @endforeach
                    <tr>
                        <th style="background:#ddd;">Total</th>
                        @foreach ($activeSizes as $i => $size)
                            <th>{{ ($stock['total']->{"qty$i"} ?? 0) ?: '' }}</th>
                        @endforeach
                        <th>{{ $stock['total']->pcs ?? 0 }}</th>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <div class="ftitle">JUMLAH : {{ $stock['total']->pcs ?? 0 }} PCS</div>
            <hr class="dashed-divider">
        @endforeach

        {{-- ============================================================
             SECTION 2: LAPORAN STOCK
        ============================================================ --}}
        <div class="ftitle">LAPORAN STOCK</div>

        <table class="plain">
            <tr>
                <td class="layout-col-left">
                    <table class="info">
                        <tr><td width="45%">Season</td><td>: {{ $dt->season }}</td></tr>
                        <tr><td>OP#</td><td>: {{ $dt->OP }}</td></tr>
                        <tr><td>Buyer</td><td>: {{ $dt->buyer }}</td></tr>
                        <tr><td>Style</td><td>: {{ $dt->style }}</td></tr>
                        <tr><td>Silhouette</td><td>: {{ $dt->silhouette }}</td></tr>
                        <tr><td>Qty Order</td><td>: <b>{{ $qtyOrder }}</b></td></tr>
                        <tr><td>Qty Cutt</td><td>: </td></tr>
                        <tr><td>Qty Sewing</td><td>: <b>{{ $qtySewing }}</b></td></tr>
                        <tr><td>Qty Export</td><td>: <b>{{ $qtyExport }}</b></td></tr>
                        <tr><td>Qty Sisa</td><td>: <b>{{ $qtySisa }}</b></td></tr>
                        <tr><td>Masuk Gudang</td><td>: <b>{{ $qtySisa }}</b></td></tr>
                    </table>
                </td>

                <td class="layout-col-right">
                    <table class="report">
                        <thead>
                            <tr>
                                <th rowspan="2">Color</th>
                                <th colspan="{{ count($activeSizes) }}">Size & Qty</th>
                                <th rowspan="2">Total</th>
                            </tr>
                            <tr>
                                @foreach ($activeSizes as $size)
                                    <th>{{ $size }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stockPerMaterial as $row)
                                <tr>
                                    <th>{{ $row['material'] }}</th>
                                    @foreach ($activeSizes as $i => $size)
                                        <td>{{ ($row['qty'][$i] ?? 0) ?: '' }}</td>
                                    @endforeach
                                    <th>{{ $row['pcs'] }}</th>
                                </tr>
                            @endforeach
                            <tr>
                                <th style="background:#ddd;">Total</th>
                                @foreach ($activeSizes as $i => $size)
                                    <th>{{ ($runningTotal[$i] ?? 0) ?: '' }}</th>
                                @endforeach
                                <th>{{ $runningPcs }}</th>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <br><br>

        {{-- Kolom tanda tangan --}}
        <table class="report" style="margin-top: 40px;">
            <tr>
                <td style="height: 80px;"></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <th width="33%">Sewing</th>
                <th width="33%">Finishing</th>
                <th width="34%">Gudang</th>
            </tr>
        </table>

    </div>

</body>
</html>