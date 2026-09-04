<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('/public/css/images/morich.gif') }}"/>  
    <title>Morich Indo Fashion - Print Packing</title>
    <link href="{{ asset('config/stylist.css') }}" rel="stylesheet" type="text/css" media="screen" />
    <style type="text/css">
        /* Base Styling */
        body { 
            font-family: "Book Antiqua", Palatino, serif; 
            font-size: 11px; 
            color: #1e293b; 
            line-height: 1.4;
            margin: 0;
            padding: 10px;
        }
        
        /* Master Info Table */
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

        /* Data Tables styling */
        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .print-table th, .print-table td {
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

        /* Carton Badge Input Restyling */
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

        /* Signature Table */
        .signature-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 30px; 
            font-size: 11px;
            page-break-inside: avoid;
        }
        .signature-table th, .signature-table td { 
            border: 1px solid #cbd5e1; 
            text-align: center; 
            padding: 6px; 
        }
        
        /* Optimasi Khusus Cetak / Print PDF */
        @media print {
            body { padding: 0; margin: 0; color: #000; }
            .ftitle { page-break-after: avoid; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            
            /* Memaksa background color agar tetap keluar di printer */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            /* Menghilangkan box border input bawaan agar menyatu dengan kertas */
            .carton-input {
                border: .5px solid #94a3b8 !important;
            }
        }
    </style>
</head>
<body>

    {{-- Timestamp Terakhir Diupdate --}}
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
                @if(in_array($dt->gabung, [4, 5, 6, 8]))
                    @foreach($customersList as $x)
                        {{ $x->customer }};<br>
                    @endforeach
                @else
                    {{ $dt2->customer ?? '' }}
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
                @if(in_array($dt->gabung, [1, 2, 9, 10]))
                    @foreach($materialsList as $x)
                        {{ $x->material }};<br>
                    @endforeach
                @else
                    {{ $dt2->material ?? '' }}
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
                @foreach($measList as $x3)
                    {{ $x3->meas }}; <br>
                @endforeach
            </td>
            <td><strong>Warehouse</strong></td>
            <td>: {{ $dt2->wh ?? '' }}</td>
            <td><strong>Keterangan</strong></td>
            <td>: {{ $dt2->ket ?? '' }}</td>
        </tr>
    </table>

    {{-- Sub-View PDF Berdasarkan Parameter GAB --}}
    <div class="pdf-content-container">
        @if(in_array($gab, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]))
            @include('menu.laporan.pdf' . $gab)
        @else
            @include('menu.laporan.pdf0')
        @endif
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