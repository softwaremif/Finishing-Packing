@extends('layout.main')
<style>
    /* Menggunakan variabel warna modern agar konsisten */
    :root {
        --primary-color: #4f46e5; /* Indigo modern */
        --primary-light: rgba(79, 70, 229, 0.1);
        --text-main: #0f172a;
        --text-muted: #64748b;
        --bg-header: #f8fafc;
        --border-color: #e2e8f0;
    }

    a i {
        transition: transform 0.2s ease;
    }

    a:hover i {
        transform: scale(1.2) translateX(-2px);
    }
    
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

@section('content')
@php
$infoItems = [
    [
        'title' => 'PO Number',
        'value' => $dt->POno,
        'icon' => 'fa-hashtag',
        'class' => 'bg-primary-subtle text-primary',
    ],

    [
        'title' => 'OP',
        'value' => $dt->OP,
        'icon' => 'fa-layer-group',
        'class' => 'bg-info-subtle text-info',
    ],

    [
        'title' => 'License PO Ref',
        'value' => $dt->poref ?? '-',
        'icon' => 'fa-certificate',
        'class' => 'bg-light text-dark',
    ],

    [
        'title' => 'Buyer Name',
        'value' => $dt->buyer,
        'icon' => 'fa-user-tie',
        'class' => 'bg-success-subtle text-success',
    ],

    [
        'title' => 'Place',
        'value' => $dt->customer,
        'icon' => 'fa-map-marker-alt',
        'class' => 'bg-danger-subtle text-danger',
    ],

    [
        'title' => 'Season',
        'value' => $dt->season,
        'icon' => 'fa-calendar-alt',
        'class' => 'bg-warning-subtle text-warning',
    ],

    [
        'title' => 'Style Code',
        'value' => $dt->style,
        'icon' => 'fa-tshirt',
        'class' => 'bg-secondary-subtle text-secondary',
    ],

    [
        'title' => 'Color / Material',
        'value' => $dt->material,
        'icon' => 'fa-palette',
        'class' => 'bg-light text-dark',
    ],

    [
        'title' => 'Description',
        'description' => $dt->silhouette ?? '-',
        'icon' => 'fa-align-left',
        'class' => 'bg-light text-dark',
    ],

    // Tambahan item ke-10, ke-11, dst
    // [
    //     'title' => 'Factory',
    //     'value' => $dt->factory ?? '-',
    //     'icon' => 'fa-industry',
    //     'class' => 'bg-dark-subtle text-dark',
    // ],
];
@endphp

<div class="container-fluid py-4 px-4">

    <x-details.header
        title="Input Data Transfer"
        :back-url="route('stuffing.index')"
    />

    <x-details.info-card
        :items="$infoItems"
    />

    <x-details.breakdown
        :sizes="$activeSizes"
        :size-group="$dt->secsz"
        :order-qty="$orderQty"
        :ready-qty="$readyQty"
        :diff-qty="$diffQty"
        :order-total="$dt->qty"
        :ready-total="$summary->pcs ?? 0"
        :balance-total="$totalBalance"
        title="Breakdown Size & Qty"
        :tctnp="$tctnp"
        :tctna="$tctna"
        :balanceCtn="$balanceCtn"
    />
</div>
@endsection
<script>
    
</script>
