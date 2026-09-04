{{-- menu/shared/packing-input-global.blade.php --}}
{{-- Dipakai oleh KEDUA halaman: Packing dan FG/Stuffing. Kontroler
     masing-masing mengirim $pageConfig untuk menentukan route & fitur
     mana yang aktif. --}}

@extends('layout.main')

@php
    $cfg = array_merge(
        [
            'mode' => 'packing',
            'pageTitlePrefix' => 'Packing list',
            'guserpkSegel' => [38],
            'guserpkShipmentFlow' => [35],
            'guserpkTerima' => [38],

            'showPlanningChips' => false,
            'showShipmentChips' => false,
            'showSizeFilter' => true,
            'showPartFilter' => false,
            'showAddPacking' => false,
            'showCtnManagement' => false,
            'showScanNobar' => false,
            'showShipmentPlan' => false,
            'showShipmentActions' => false,
            'showEditButton' => true,
            'showSealAction' => true, // BARU -- default tampil (Packing/FG-Stuffing)
            'showKembalikanButton' => false, // BARU -- khusus Inspection
            'showHistoryTab' => false, // BARU -- khusus Inspection
            'showBaseChips' => true,
            'backRouteName' => 'packing.index',

            'routes' => [],
        ],
        $pageConfig ?? [],
    );

    $guserpk = Session::get('guserpk');
@endphp

@section('css_custom')
    <style>
        .page-wrap {
            padding: 16px
        }

        .btn-icon-custom:hover {
            background-color: #f8fafc !important;
            color: #1e293b !important;
            transform: translateX(-3px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1) !important
        }

        .packing-card {
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            padding: 14px 16px;
            height: 350px;
            display: flex;
            flex-direction: column;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease
        }

        .packing-card.selected {
            border-color: #359DD9;
            box-shadow: 0 0 0 2px rgba(53, 157, 217, .25);
            background-color: #f0f9ff
        }

        .packing-card .icon-btn,
        .packing-card .card-actions .btn {
            cursor: pointer
        }

        .packing-card .ribbon-segel {
            position: absolute;
            top: 14px;
            right: -48px;
            transform: rotate(45deg);
            background: #dc2626;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 8px 60px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .25);
            pointer-events: none;
            z-index: 10
        }

        .packing-card .packing-card-sizes {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            margin-bottom: 8px;
            padding-right: 4px
        }

        .packing-card .packing-card-sizes::-webkit-scrollbar {
            width: 5px
        }

        .packing-card .packing-card-sizes::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px
        }

        .packing-card .ctn-code {
            font-weight: 700;
            font-size: 14px;
            color: #0f172a
        }

        .packing-card .badge-soft {
            font-size: 10.5px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 999px;
            border: 1px solid transparent
        }

        .packing-card .badge-soft.solid {
            background: #f1f5f9;
            color: #64748b;
            border-color: #e2e8f0
        }

        .packing-card .badge-soft.assorted {
            background: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd
        }

        .packing-card .badge-soft.mixed {
            background: linear-gradient(90deg, #f97316, #64748b, #8b5cf6);
            color: #f1f5f9;
            border-color: #fde68a
        }

        .packing-card .badge-status {
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 999px
        }

        .packing-card .badge-status.planned {
            background: #f1f5f9;
            color: #64748b
        }

        .packing-card .badge-status.packing {
            background: #0b89d2;
            color: #ffff
        }

        .packing-card .badge-status.complete {
            background: #8bc63f;
            color: #ffff
        }

        .packing-card .badge-status.sealed {
            background: #321414;
            color: #e2e8f0
        }

        .packing-card .icon-btn {
            color: #94a3b8;
            cursor: pointer;
            font-size: 13px;
            padding: 2px 4px
        }

        .packing-card .icon-btn:hover {
            color: #1e293b
        }

        .packing-card .progress-main {
            height: 6px;
            background: #eef0f2;
            border-radius: 3px;
            overflow: hidden
        }

        .packing-card .progress-main .bar {
            display: block;
            height: 100%;
            border-radius: 3px
        }

        .packing-card .subline {
            font-size: 12.5px;
            color: #334155
        }

        .packing-card .size-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            padding: 3px 0
        }

        .packing-card .size-row .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #0f172a;
            flex-shrink: 0
        }

        .packing-card .size-row .name {
            flex: 0 0 auto;
            min-width: 110px;
            color: #334155
        }

        .packing-card .size-row .mini-progress {
            flex: 1;
            height: 5px;
            background: #eef0f2;
            border-radius: 3px;
            overflow: hidden
        }

        .packing-card .size-row .mini-progress .bar {
            display: block;
            height: 100%;
            border-radius: 3px
        }

        .packing-card .size-row .frac {
            flex: 0 0 44px;
            text-align: right;
            color: #64748b
        }

        .packing-card .card-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            flex-shrink: 0
        }

        .packing-card .card-actions .btn {
            flex: 1;
            font-size: 12px;
            padding: 6px 8px
        }

        .packing-card .card-barcode {
            font-size: 14px;
            color: #475569;
            background: #f8fafc;
            border: 1px dashed #e2e8f0;
            border-radius: 6px;
            padding: 4px 8px;
            margin-bottom: 8px;
            flex-shrink: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px
        }

        .card-barcode .barcode-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1 1 auto;
            min-width: 0
        }

        .sticky-order-bar {
            display: none;
            position: fixed;
            left: 0;
            right: 0;
            z-index: 1030;
            background: #359DD9;
            color: #fff;
            transition: top .15s ease
        }

        .sticky-order-inner {
            height: 44px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 16px
        }

        .sticky-action {
            cursor: pointer;
            margin-left: 10px;
            font-weight: 500;
            opacity: .9
        }

        .sticky-action:hover {
            opacity: 1
        }

        #pgBreakdownTable .pg-empty-cell {
            border-top: none
        }

        #pgBreakdownTable tr[data-role="plan"] td {
            border-bottom: none
        }

        #pgBreakdownTable tr[data-role="actual"] td {
            border-top: none;
            border-bottom: 1px dashed #e2e8f0
        }

        #packingCardsGrid.list-mode .packing-card {
            height: auto;
            min-height: 0
        }

        #packingCardsGrid.list-mode .packing-card-sizes {
            max-height: 160px
        }

        #viewToggleGlobal .btn.active {
            background-color: #1e293b;
            border-color: #1e293b;
            color: #fff
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s ease
        }

        .status-chip:hover {
            border-color: #cbd5e1;
            background: #f8fafc
        }

        .status-chip.active {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff
        }

        .status-chip .chip-count {
            background: rgba(0, 0, 0, .08);
            border-radius: 999px;
            padding: 1px 7px;
            font-size: 11px
        }

        .status-chip.active .chip-count {
            background: rgba(255, 255, 255, .2)
        }

        .status-chip.active[data-status="complete"] {
            background: #8bc63f;
            border-color: #8bc63f
        }

        .status-chip.active[data-status="inspect"] {
            background: #f59e0b;
            border-color: #f59e0b
        }

        .status-chip.active[data-status="shipped"] {
            background: #2563eb;
            border-color: #2563eb
        }

        .status-chip.active[data-status="returning"] {
            background: #7c3aed;
            border-color: #7c3aed
        }

        #packingListTable .packing-list-row {
            cursor: pointer;
            transition: background-color .1s ease
        }

        #packingListTable .packing-list-row:hover td {
            background-color: #f8fafc
        }

        #packingListTable .packing-list-row.selected td {
            background-color: #f0f9ff;
            border-color: #bae6fd
        }

        #packingListTable td {
            vertical-align: middle
        }

        #packingListTable .icon-btn {
            color: #94a3b8;
            cursor: pointer;
            font-size: 13px;
            padding: 2px 4px
        }

        #packingListTable .icon-btn:hover {
            color: #1e293b
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #f1f5f9;
            transition: .2s
        }

        .action-btn:hover {
            background: #e2e8f0;
            transform: scale(1.05)
        }

        .action-btn-pdf img {
            display: block
        }

        .action-btn.action-btn-pdf {
            background-color: #fee2e2 !important;
            color: #b91c1c !important
        }

        .action-btn.action-btn-pdf:hover {
            background-color: #fecaca !important;
            color: #7f1d1d !important
        }

        .ship-stamp {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 78px;
            height: 78px;
            border-radius: 50%;
            border: 3px solid;
            background: #fff;
            flex-shrink: 0;
            text-align: center;
            line-height: 1.15;
            font-family: 'Arial Narrow', 'Oswald', 'Segoe UI Condensed', Arial, sans-serif;
            transform: rotate(-14deg)
        }

        .packing-card .ship-stamp {
            position: absolute;
            right: 8px;
            bottom: 8px;
            z-index: 20;
            box-shadow: 0 3px 8px rgba(0, 0, 0, .18)
        }

        .ship-stamp-text {
            font-size: 12.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .8px
        }

        .ship-stamp-date {
            font-size: 8.5px;
            font-weight: 600;
            opacity: .85;
            margin-top: 1px;
            font-family: Arial, sans-serif
        }

        .ship-stamp-shipped {
            border-color: #2563eb;
            color: #2563eb
        }

        .ship-stamp-inspect {
            border-color: #f59e0b;
            color: #f59e0b
        }

        .ship-stamp-returning {
            border-color: #7c3aed;
            color: #7c3aed
        }

        .ship-stamp.ship-stamp-sm {
            width: 42px;
            height: 42px;
            transform: rotate(-10deg)
        }

        .ship-stamp.ship-stamp-sm .ship-stamp-text {
            font-size: 8.5px
        }

        .ship-stamp.ship-stamp-sm .ship-stamp-date {
            display: none
        }

        .scan-nobar-wrap {
            display: flex;
            justify-content: center
        }

        .scan-nobar-card {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .05);
            padding: 18px 20px
        }

        .scan-nobar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px
        }

        .scan-nobar-icon-wrap {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #e0f2fe;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s ease
        }

        .scan-nobar-icon {
            font-size: 17px;
            color: #0369a1;
            transition: color .15s ease
        }

        .scan-nobar-icon-wrap.is-processing {
            background: #fef3c7
        }

        .scan-nobar-icon-wrap.is-processing .scan-nobar-icon {
            color: #d97706;
            animation: scanIconSpin .9s linear infinite
        }

        .scan-nobar-icon-wrap.is-success {
            background: #dcfce7
        }

        .scan-nobar-icon-wrap.is-success .scan-nobar-icon {
            color: #16a34a
        }

        .scan-nobar-icon-wrap.is-error {
            background: #fee2e2
        }

        .scan-nobar-icon-wrap.is-error .scan-nobar-icon {
            color: #dc2626
        }

        @keyframes scanIconSpin {
            from {
                transform: rotate(0)
            }

            to {
                transform: rotate(360deg)
            }
        }

        .scan-nobar-title {
            font-weight: 700;
            font-size: 14.5px;
            color: #0f172a
        }

        .scan-nobar-subtitle {
            font-size: 11.5px;
            color: #94a3b8
        }

        .scan-nobar-input-wrap {
            position: relative;
            margin-bottom: 10px
        }

        .scan-nobar-input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 13px;
            pointer-events: none
        }

        .scan-nobar-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            padding: 9px 12px 9px 34px;
            color: #0f172a;
            transition: border-color .15s ease, box-shadow .15s ease
        }

        .scan-nobar-input:focus {
            outline: none;
            border-color: #0369a1;
            box-shadow: 0 0 0 3px rgba(3, 105, 161, .12)
        }

        .scan-nobar-feedback {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8
        }

        .scan-nobar-feedback.is-processing {
            color: #d97706
        }

        .scan-nobar-feedback.is-success {
            color: #16a34a
        }

        .scan-nobar-feedback.is-error {
            color: #dc2626
        }

        .shipment-plan-card {
            width: 230px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .05)
        }

        .shipment-plan-card.is-active {
            border-color: #359DD9;
            box-shadow: 0 0 0 2px rgba(53, 157, 217, .2)
        }

        .shipment-plan-card.is-done {
            border-color: #8bc63f;
            background: #f7fdf1
        }

        .shipment-plan-title {
            font-weight: 700;
            font-size: 13.5px;
            color: #0f172a;
            margin-bottom: 6px
        }

        .shipment-plan-progress-bar {
            height: 6px;
            background: #eef0f2;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 6px
        }

        .shipment-plan-progress-bar .bar {
            display: block;
            height: 100%
        }

        .shipment-plan-count {
            font-size: 12px;
            color: #475569;
            margin-bottom: 10px
        }

        .shipment-plan-actions {
            display: flex;
            gap: 6px
        }

        .shipment-plan-actions .btn {
            flex: 1;
            font-size: 11.5px;
            padding: 5px 8px
        }

        .shipment-plan-shipdate {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 8px
        }

        .shipment-plan-shipdate strong {
            color: #0f172a
        }
    </style>
@endsection

@section('content')
    <div id="stickTopBarGlobal" class="sticky-order-bar">
        <div class="sticky-order-inner">
            <div><strong><span id="selectedCountGlobal">0</span> carton terpilih</strong></div>
            <div>
                @if ($cfg['showShipmentActions'] && in_array($guserpk, $cfg['guserpkShipmentFlow']))
                    <span class="sticky-action d-none" id="btnProsesShipmentGlobal"
                        onclick="bulkProsesShipmentGlobal()">Proses Shipment</span>
                @endif
                @if ($cfg['showKembalikanButton'])
                    <span class="sticky-action" id="btnKembalikanStuffingGlobal" onclick="confirmKembalikanStuffing()">
                        Kembalikan
                    </span>

                    <span class="sticky-action" id="btnBuatDokumenInspectGlobal" onclick="openInspectDocumentModal()">
                        Buat Dokumen Inspect
                    </span>
                @endif
                @if ($cfg['showShipmentActions'] && in_array($guserpk, $cfg['guserpkTerima']))
                    <span class="sticky-action d-none" id="btnTerimaCartonGlobal" onclick="terimaCartonGlobal()">Terima
                        Carton</span>
                @endif
                @if (in_array($guserpk, $cfg['guserpkSegel']))
                    <span class="sticky-action d-none" id="btnBukaSegelGlobal" onclick="bulkBukaSegelGlobal()">Buka
                        Segel</span>
                    <span class="sticky-action" id="btnBulkSegelCtnGlobal" onclick="bulkSegelCtnGlobal()">Segel CTN</span>
                    <span class="sticky-action d-none" id="btnProsesInspectGlobal"
                        onclick="bulkProsesInspectGlobal()">Proses Inspect</span>
                @endif
                @if ($cfg['showCtnManagement'])
                    <span class="sticky-action" id="btnBulkActualCtnGlobal" onclick="bulkActualCtnGlobal()">Input
                        Actual</span>
                    <span class="sticky-action" id="btnBulkDeleteActualCtnGlobal"
                        onclick="bulkDeleteActualCtnGlobal()">Delete Actual</span>
                    <span class="sticky-action" id="btnBulkCopyGlobal" onclick="bulkCopyGlobal()">Copy CTN</span>
                    <span class="sticky-action" id="btnBulkDeleteGlobal" onclick="bulkDeleteCtnGlobal()">Delete CTN</span>
                @endif
                <span class="sticky-action" onclick="closeMenuGlobal()">Close</span>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4 px-4">
        <div
            class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-3 mb-4 gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:void(0)" onclick="goBack()"
                    class="btn btn-icon-custom d-inline-flex align-items-center justify-content-center shadow-sm border bg-white text-secondary rounded-circle"
                    style="width:38px;height:38px;transition:all .2s ease" title="Kembali ke Daftar Data OP">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold text-dark mb-0" style="font-size:1.15rem;letter-spacing:-.3px">
                        {{ $cfg['pageTitlePrefix'] }} - Style {{ $dt2->style ?? '-' }}
                    </h4>
                    <span>OP {{ $op }} &middot; Season {{ $dt2->season ?? '-' }} &middot; Buyer:
                        {{ $dt2->buyer ?? '-' }} &middot; PO {{ $po }}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('laporan.pdf.global', ['po' => $po, 'op' => $op, 'poref' => $poref ?? null, 'mif' => $mif]) }}"
                    target="_blank" class="action-btn action-btn-pdf" title="Print PDF">
                    <img src="{{ asset('/public/css/images/pdf.jpg') }}" width="18" height="18">
                </a>
            </div>
        </div>

        <div id="headerInfoWrapperGlobal">
            @include($cfg['routes']['headerPartial'], [
                'dt2' => $dt2,
                'poNoList' => $poNoList,
                'colorList' => $colorList,
                'allPopks' => $allPopks,
            ])
        </div>

        <div id="cardsInfoWrapperGlobal">
            @include($cfg['routes']['cardsInfoPartial'], [
                'totalPcs' => array_sum($orderQty ?? []),
                'plannedPcs' => array_sum($planQty ?? []),
                'packedPcs' => array_sum($readyQty ?? []),
                'shortPcs' => max(0, array_sum($orderQty ?? []) - array_sum($planQty ?? [])),
                'totalColors' => $totalColors ?? 0,
                'totalSizes' => count($activeSizes ?? []),
                'totalCarton' => $totalCarton ?? 0,
                'sealedCarton' => $sealedCarton ?? 0,
                'openCarton' => $openCarton ?? 0,
            ])
        </div>

        @if ($cfg['showShipmentPlan'] && in_array($guserpk, $cfg['guserpkShipmentFlow']))
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="fw-bold text-dark" style="font-size:15px;">
                        <span class="rounded me-2"
                            style="width:4px;height:16px;display:inline-block;background:#64748b;"></span>
                        Shipment Plan
                    </div>
                    <div class="text-muted" style="font-size:11.5px;" id="activeSessionBadge"></div>
                </div>
                <div id="shipmentPlanCards" class="d-flex gap-3 flex-wrap"></div>
            </div>
        @endif

        <div id="breakdownSummaryWrapper">
            @include($cfg['routes']['breakdownPartial'])
        </div>

        @if ($cfg['showScanNobar'] && in_array($guserpk, $cfg['guserpkShipmentFlow']))
            <div class="scan-nobar-wrap mb-4">
                <div class="scan-nobar-card">
                    <div class="scan-nobar-header">
                        <div class="scan-nobar-icon-wrap" id="scanIconWrapGlobal">
                            <i class="fas fa-barcode scan-nobar-icon" id="scanIconGlobal"></i>
                        </div>
                        <div>
                            <div class="scan-nobar-title">Scan Barcode Carton</div>
                            <div class="scan-nobar-subtitle">Untuk proses Shipment</div>
                        </div>
                    </div>
                    <div class="scan-nobar-input-wrap">
                        <i class="fas fa-search scan-nobar-input-icon"></i>
                        <input type="text" id="scanNobarInputGlobal" class="scan-nobar-input"
                            placeholder="Scan barcode / ketik nobar lalu Enter..." autocomplete="off">
                    </div>
                    <div class="scan-nobar-feedback" id="scanNobarFeedbackGlobal">
                        <i class="fas fa-circle-info"></i><span>Siap menerima scan.</span>
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="rounded me-2"
                        style="width:4px;height:16px;display:inline-block;background:#64748b;"></span>
                    <strong class="text-dark"> Detail Packing / Carton </strong>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="px-3 py-2 border-bottom bg-white">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="input-group" style="width:260px;">
                                <span class="input-group-text search">
                                    <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18"
                                        alt="Search">
                                </span>
                                <input type="text" class="form-control search" id="searchPackingGlobal"
                                    placeholder="Search Barcode / No CTN">
                            </div>
                            @if ($cfg['showSizeFilter'])
                                <input id="filterSizeGlobal" style="width:110px;">
                            @endif
                            <input id="filterColorGlobal" style="width:150px;">
                            <input id="filterSecszGlobal" style="width:150px;">
                            <input id="sortFieldGlobal" style="width:150px;">
                            @if ($cfg['showPartFilter'])
                                <input id="filterPartGlobal" style="width:150px;">
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="btn-group btn-group-sm" role="group" id="viewToggleGlobal">
                                <button type="button" class="btn btn-outline-secondary active" id="viewModeGridBtn"
                                    onclick="setViewModeGlobal('grid')" title="Tampilan Grid"><i
                                        class="fas fa-th-large"></i></button>
                                <button type="button" class="btn btn-outline-secondary" id="viewModeListBtn"
                                    onclick="setViewModeGlobal('list')" title="Tampilan List"><i
                                        class="fas fa-list"></i></button>
                            </div>
                            @if ($cfg['showAddPacking'])
                                <button class="btn btn-dark btn-sm d-flex align-items-center fw-semibold"
                                    style="font-size:12px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                                    onclick="openUrutkanCtnModal()">
                                    <i class="fas fa-sort-numeric-down me-1"></i> Penomoran CTN
                                </button>
                                <button class="btn btn-dark btn-sm d-flex align-items-center fw-semibold"
                                    style="font-size:12px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                                    onclick="openPackingGlobalModal()">
                                    <i class="fas fa-plus me-1"></i> Add Packing
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($cfg['showHistoryTab'])
                    <div class="px-3 py-2 border-bottom bg-white">
                        <div class="btn-group btn-group-sm" role="group" id="inspectionTabToggle">
                            <button type="button" class="btn btn-outline-dark active" id="tabInspectCurrentBtn"
                                onclick="setInspectionTab('current')">
                                <i class="fas fa-hourglass-half me-1"></i> Sedang Inspect
                            </button>
                            <button type="button" class="btn btn-outline-dark" id="tabInspectHistoryBtn"
                                onclick="setInspectionTab('history')">
                                <i class="fas fa-clock-rotate-left me-1"></i> History Inspect
                            </button>
                        </div>
                    </div>
                @endif

                <div class="p-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-3" id="statusFilterChipsGlobal">
                        @if ($cfg['showBaseChips'])
                            <span class="status-chip active" data-status="" onclick="setStatusFilterGlobal('')">Semua
                                <span class="chip-count" id="chipCountAll">0</span></span>
                        @endif
                        @if ($cfg['showPlanningChips'])
                            <span class="status-chip" data-status="planned"
                                onclick="setStatusFilterGlobal('planned')">Planned <span class="chip-count"
                                    id="chipCountPlanned">0</span></span>
                            <span class="status-chip" data-status="packing"
                                onclick="setStatusFilterGlobal('packing')">Packing <span class="chip-count"
                                    id="chipCountPacking">0</span></span>
                        @endif
                        @if ($cfg['showBaseChips'])
                            <span class="status-chip" data-status="complete"
                                onclick="setStatusFilterGlobal('complete')">Complete <span class="chip-count"
                                    id="chipCountComplete">0</span></span>
                            <span class="status-chip" data-status="sealed"
                                onclick="setStatusFilterGlobal('sealed')">Sealed
                                <span class="chip-count" id="chipCountSealed">0</span></span>
                        @endif
                        @if ($cfg['showShipmentChips'])
                            <span class="status-chip" data-status="inspect"
                                onclick="setStatusFilterGlobal('inspect')">Inspect <span class="chip-count"
                                    id="chipCountInspect">0</span></span>
                            <span class="status-chip" data-status="shipped"
                                onclick="setStatusFilterGlobal('shipped')">Shipped <span class="chip-count"
                                    id="chipCountShipped">0</span></span>
                        @endif
                        @if (in_array($guserpk, $cfg['guserpkSegel']))
                            <span class="status-chip" data-status="returning"
                                onclick="setStatusFilterGlobal('returning')">Carton dari QA <span class="chip-count"
                                    id="chipCountReturning">0</span></span>
                        @endif
                        <div class="ms-auto d-flex align-items-center gap-2">
                            <span class="text-secondary" style="font-size:12px;">Tampilkan</span>
                            <input id="pageSizeGlobal" style="width:90px;">
                        </div>
                    </div>

                    <div id="packingCardsGrid" class="row g-3"></div>
                    <div id="packingListTableWrapper" class="table-responsive d-none">
                        <table class="table table-sm table-hover align-middle mb-0" id="packingListTable">
                            <thead class="table-light text-secondary"
                                style="font-size:11px;text-transform:uppercase;letter-spacing:.3px;">
                                <tr>
                                    <th class="text-start py-2">No CTN</th>
                                    <th class="text-start py-2">Barcode</th>
                                    <th class="text-start py-2">Color / Sec Size</th>
                                    <th class="text-center py-2">Status</th>
                                    <th class="py-2">Progress</th>
                                    <th class="text-center py-2" width="90">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="packingListBody"></tbody>
                        </table>
                    </div>
                    <div id="packingCardsEmpty" class="d-none text-center py-5">
                        <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="160">
                        <div class="fw-semibold mt-2">No Data Found</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-secondary" style="font-size:12.5px;" id="packingCardsInfo"></div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-secondary" id="btnPackingCardsPrev"
                                onclick="packingCardsGoPage(-1)"><i class="fas fa-chevron-left"></i></button>
                            <span style="font-size:12.5px;" id="packingCardsPageLabel"></span>
                            <button class="btn btn-sm btn-outline-secondary" id="btnPackingCardsNext"
                                onclick="packingCardsGoPage(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($cfg['showAddPacking'] || $cfg['showCtnManagement'])
        @include($cfg['routes']['modalEditInfoPacking'])
        @include($cfg['routes']['modalPacking'])
        @include($cfg['routes']['modalActualCtn'])
        @include($cfg['routes']['modalDeleteActualCtn'])
        @include($cfg['routes']['modalDeleteCtn'])
        @include($cfg['routes']['modalCopyCtn'])
        @include($cfg['routes']['modalUrutkanCtn'])
    @endif
    @if ($cfg['showSealAction'])
        @include($cfg['routes']['modalSegelCtn'])
    @endif
    @if ($cfg['showKembalikanButton'])
        @include($cfg['routes']['modalKembalikanStuffing'])
    @endif
    @if ($cfg['showShipmentActions'])
        @include($cfg['routes']['modalShipmentCtn'])
        @include($cfg['routes']['modalEndSession'])
        @include($cfg['routes']['modalTerimaCarton'])
    @endif
@endsection

@section('js_custom')
    <script>
        window.pageCfg = @json($cfg);
        window.pgCombos = @json($colorSecszCombos ?? []);
        window.pgSizes = @json($activeSizes);
        window.canManageSegel = @json(in_array($guserpk, $cfg['guserpkSegel']));
        window.isSupervisorStuffing = @json(in_array($guserpk, [34]));
        window.activeSizesGlobal = @json($activeSizes);
        window.colorListGlobal = @json($colorList ?? []);
        window.secszListGlobal = @json($secszList ?? []);
        window.packingViewModeGlobal = localStorage.getItem('packingViewModeGlobal') || 'grid';
        const R = window.pageCfg.routes;
        const PO = @json($po),
            OP = @json($op),
            POREF = @json($poref ?? null),
            MIF = @json($mif);
        const SESSION_KEY = 'stuffingActivePart_' + PO + '_' + OP;
    </script>

    <script>
        function reloadBreakdownSummary() {
            $.get(R.breakdownSummaryGlobal, {
                po: PO,
                op: OP,
                poref: POREF,
                mif: MIF
            }, function(html) {
                $('#breakdownSummaryWrapper').html(html);
                matrixTab = 'planning';
                $('.legend-item').removeClass('active');
                classifyMatrixCells();
            });
        }

        function refreshPgCombos() {
            return $.get(R.combosGlobal, {
                po: PO,
                op: OP,
                poref: POREF,
                mif: MIF
            }, function(data) {
                window.pgCombos = data || [];
            });
        }

        function reloadCardsInfoGlobal() {
            $.get(R.cardsInfoGlobal, {
                po: PO,
                op: OP,
                poref: POREF,
                mif: MIF
            }, function(html) {
                $('#cardsInfoWrapperGlobal').html(html);
            });
        }

        function reloadHeaderInfoGlobal() {
            return $.get(R.headerInfoGlobal, {
                po: PO,
                op: OP,
                poref: POREF,
                mif: MIF
            }, function(html) {
                $('#headerInfoWrapperGlobal').html(html);
            });
        }

        function classifyMatrixCells() {
            document.querySelectorAll('#matrixTable td.matrix-cell').forEach(function(td) {
                const order = Number(td.dataset.order || 0),
                    trans = Number(td.dataset.trans || 0),
                    plan = Number(td.dataset.plan || 0),
                    actual = Number(td.dataset.actual || 0);
                td.classList.remove('cov-exact', 'cov-short', 'cov-over', 'cov-none', 'cov-blank');
                let covColor = '#cbd5e1';
                if (order === 0 && trans === 0) td.classList.add('cov-blank');
                else if (trans === 0 && order > 0) {
                    td.classList.add('cov-none');
                    covColor = '#94a3b8';
                } else if (trans < order) {
                    td.classList.add('cov-short');
                    covColor = '#dc2626';
                } else if (trans > order) {
                    td.classList.add('cov-over');
                    covColor = '#2563eb';
                } else {
                    td.classList.add('cov-exact');
                    covColor = '#16a34a';
                }
                td.classList.remove('pack-full', 'pack-progress', 'pack-empty', 'pack-blank');
                let packColor = '#cbd5e1';
                if (plan === 0) td.classList.add('pack-blank');
                else if (actual >= plan) {
                    td.classList.add('pack-full');
                    packColor = '#16a34a';
                } else if (actual > 0) {
                    td.classList.add('pack-progress');
                    packColor = '#2563eb';
                } else {
                    td.classList.add('pack-empty');
                    packColor = '#94a3b8';
                }
                td.classList.remove('plan-exact', 'plan-short', 'plan-over', 'plan-none', 'plan-blank');
                let planColor = '#cbd5e1';
                if (order === 0 && plan === 0) td.classList.add('plan-blank');
                else if (plan === 0 && order > 0) {
                    td.classList.add('plan-none');
                    planColor = '#94a3b8';
                } else if (plan < order) {
                    td.classList.add('plan-short');
                    planColor = '#dc2626';
                } else if (plan > order) {
                    td.classList.add('plan-over');
                    planColor = '#2563eb';
                } else {
                    td.classList.add('plan-exact');
                    planColor = '#16a34a';
                }
                const covPct = order > 0 ? Math.min(100, (trans / order) * 100) : (trans > 0 ? 100 : 0),
                    packPct = plan > 0 ? Math.min(100, (actual / plan) * 100) : 0,
                    planPct = order > 0 ? Math.min(100, (plan / order) * 100) : (plan > 0 ? 100 : 0);
                const covFill = td.querySelector('.cov .matrix-bar-fill');
                if (covFill) {
                    covFill.style.width = covPct + '%';
                    covFill.style.background = covColor;
                }
                const packFill = td.querySelector('.pack .matrix-bar-fill');
                if (packFill) {
                    packFill.style.width = packPct + '%';
                    packFill.style.background = packColor;
                }
                const planFill = td.querySelector('.planning .matrix-bar-fill');
                if (planFill) {
                    planFill.style.width = planPct + '%';
                    planFill.style.background = planColor;
                }
            });
            document.querySelectorAll('.matrix-color-dot').forEach(function(dot) {
                const name = dot.dataset.name || '';
                let hash = 0;
                for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
                dot.style.background = `hsl(${Math.abs(hash)%360}, 45%, 45%)`;
            });
        }
        let matrixTab = 'planning';

        function setMatrixTab(tab) {
            matrixTab = tab;
            $('.matrix-tab-toggle button').removeClass('active');
            const btnMap = {
                coverage: '#matrixTabCoverage',
                planning: '#matrixTabPlanning',
                packing: '#matrixTabPacking'
            };
            $(btnMap[tab]).addClass('active');
            $('#matrixLegendCoverage').toggleClass('d-none', tab !== 'coverage');
            $('#matrixLegendPlanning').toggleClass('d-none', tab !== 'planning');
            $('#matrixLegendPacking').toggleClass('d-none', tab !== 'packing');
            $('#matrixTable .cov').toggleClass('d-none', tab !== 'coverage');
            $('#matrixTable .planning').toggleClass('d-none', tab !== 'planning');
            $('#matrixTable .pack').toggleClass('d-none', tab !== 'packing');
            const subtitles = {
                coverage: 'Polibag vs Order per Color & Sec Size — klik sel untuk filter carton',
                planning: 'Planning vs Order per Color & Sec Size — klik sel untuk filter carton',
                packing: 'Actual vs Plan per Color & Sec Size — klik sel untuk filter carton'
            };
            $('#matrixSubtitle').text(subtitles[tab]);
            $('.legend-item').removeClass('active');
            $('#matrixTable td.matrix-cell').removeClass('matrix-dim');
        }

        function toggleMatrixChip(el) {
            const $el = $(el),
                wasActive = $el.hasClass('active');
            $('.legend-item').removeClass('active');
            $('#matrixTable td.matrix-cell').removeClass('matrix-dim');
            if (wasActive) return;
            $el.addClass('active');
            const classMap = {
                exact: 'cov-exact',
                short: 'cov-short',
                over: 'cov-over',
                none: 'cov-none',
                full: 'pack-full',
                progress: 'pack-progress',
                empty: 'pack-empty',
                'plan-exact': 'plan-exact',
                'plan-short': 'plan-short',
                'plan-over': 'plan-over',
                'plan-none': 'plan-none'
            };
            const targetClass = classMap[$el.data('cat')];
            $('#matrixTable td.matrix-cell').each(function() {
                if (!$(this).hasClass(targetClass)) $(this).addClass('matrix-dim');
            });
        }

        function onMatrixCellClick(td) {
            const $td = $(td),
                material = $td.data('material') || '',
                secsz = $td.data('secsz') || '',
                size = String($td.data('size'));
            if ($('#filterColorGlobal').data('combobox')) $('#filterColorGlobal').combobox('setValue', material);
            if ($('#filterSecszGlobal').data('combobox')) $('#filterSecszGlobal').combobox('setValue', secsz);
            if ($('#filterSizeGlobal').length && $('#filterSizeGlobal').data('combobox')) $('#filterSizeGlobal').combobox(
                'setValue', size);
            document.getElementById('packingCardsGrid')?.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    </script>

    <script>
        let packingCardsPage = 1,
            packingCardsRows = 25,
            packingCardsTotal = 0,
            packingCardsTotalCarton = 0,
            statusFilterGlobal = '';
        window.selectedRowsCache = window.selectedRowsCache || {};

        $(function() {
            initFilterColorGlobal();
            if (window.pageCfg.showSizeFilter) initFilterSizeGlobalCombobox();
            initFilterSecszGlobal();
            if (window.pageCfg.showPartFilter) initFilterPartGlobal();
            initSortFieldGlobalCombobox();
            initPageSizeGlobalCombobox();
            setViewModeGlobal(window.packingViewModeGlobal);
            loadPackingCards();
            reloadBreakdownSummary();
            reloadCardsInfoGlobal();
            if (window.pageCfg.showShipmentPlan) {
                loadShipmentPlanCards();
                renderActiveSessionBadge();
            }
            syncStickyBarGlobalPosition();
        });

        function goBack() {
            window.location.href = R.back;
        }

        function setViewModeGlobal(mode) {
            window.packingViewModeGlobal = mode;
            localStorage.setItem('packingViewModeGlobal', mode);
            $('#viewModeGridBtn, #viewModeListBtn').removeClass('active');
            $(mode === 'grid' ? '#viewModeGridBtn' : '#viewModeListBtn').addClass('active');
            renderPackingCards(window.lastPackingRows || []);
        }

        function initSortFieldGlobalCombobox() {
            $('#sortFieldGlobal').combobox({
                data: [{
                    value: '',
                    text: 'Urutan Default'
                }, {
                    value: 'carton_asc',
                    text: 'No Carton (A-Z)'
                }, {
                    value: 'carton_desc',
                    text: 'No Carton (Z-A)'
                }, {
                    value: 'nobar_asc',
                    text: 'Barcode (A-Z)'
                }, {
                    value: 'nobar_desc',
                    text: 'Barcode (Z-A)'
                }],
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    if (window.inspectionActiveTab === 'history') {
                        loadHistoryCards();
                    } else {
                        reloadPackingGlobal();
                    }
                }
            });
        }

        function initPageSizeGlobalCombobox() {
            $('#pageSizeGlobal').combobox({
                data: [{
                    value: 25,
                    text: '25'
                }, {
                    value: 50,
                    text: '50'
                }, {
                    value: 100,
                    text: '100'
                }, {
                    value: 200,
                    text: '200'
                }],
                valueField: 'value',
                textField: 'text',
                value: 25,
                editable: false,
                panelHeight: 'auto',
                onChange: function(v) {
                    packingCardsRows = parseInt(v) || 25;
                    reloadPackingGlobal();
                }
            });
        }

        function initFilterColorGlobal() {
            let data = [{
                value: '',
                text: 'Semua Color'
            }];
            (window.colorListGlobal || []).forEach(c => data.push({
                value: c,
                text: c
            }));
            $('#filterColorGlobal').combobox({
                data,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    if (window.inspectionActiveTab === 'history') {
                        loadHistoryCards();
                    } else {
                        reloadPackingGlobal();
                    }
                }
            });
        }

        function initFilterSizeGlobalCombobox() {
            let data = [{
                value: '',
                text: 'Semua Size'
            }];
            Object.keys(window.activeSizesGlobal || {}).forEach(i => data.push({
                value: i,
                text: window.activeSizesGlobal[i]
            }));
            $('#filterSizeGlobal').combobox({
                data,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    if (window.inspectionActiveTab === 'history') {
                        loadHistoryCards();
                    } else {
                        reloadPackingGlobal();
                    }
                }
            });
        }

        function initFilterPartGlobal() {
            let data = [{
                value: '',
                text: 'Semua Session'
            }];
            for (let i = 1; i <= 10; i++) data.push({
                value: String(i),
                text: (i === 10) ? 'Session 10 (Complete)' : `Session ${i}`
            });
            $('#filterPartGlobal').combobox({
                data,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    if (window.inspectionActiveTab === 'history') {
                        loadHistoryCards();
                    } else {
                        reloadPackingGlobal();
                    }
                }
            });
        }

        function initFilterSecszGlobal() {
            let data = [{
                value: '',
                text: 'Semua Sec Size'
            }];
            (window.secszListGlobal || []).forEach(s => data.push({
                value: s,
                text: s
            }));
            $('#filterSecszGlobal').combobox({
                data,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    if (window.inspectionActiveTab === 'history') {
                        loadHistoryCards();
                    } else {
                        reloadPackingGlobal();
                    }
                }
            });
        }
        let packingGlobalSearchTimer = null;
        $('#searchPackingGlobal').on('keyup', function() {
            clearTimeout(packingGlobalSearchTimer);
            packingGlobalSearchTimer = setTimeout(reloadPackingGlobal, 300);
        });

        function setStatusFilterGlobal(status) {
            statusFilterGlobal = status;
            $('#statusFilterChipsGlobal .status-chip').removeClass('active');
            $(`#statusFilterChipsGlobal .status-chip[data-status="${status}"]`).addClass('active');
            reloadPackingGlobal();
        }

        function loadPackingCards() {
            $.get(R.listDetailGlobal, {
                po: PO,
                op: OP,
                poref: POREF,
                mif: MIF,
                search: $('#searchPackingGlobal').val(),
                size: window.pageCfg.showSizeFilter ? $('#filterSizeGlobal').combobox('getValue') : '',
                color: $('#filterColorGlobal').combobox('getValue'),
                secsz: $('#filterSecszGlobal').combobox('getValue'),
                part: window.pageCfg.showPartFilter ? $('#filterPartGlobal').combobox('getValue') : '',
                sort: $('#sortFieldGlobal').combobox('getValue'),
                status: statusFilterGlobal,
                page: packingCardsPage,
                rows: packingCardsRows
            }, function(data) {
                packingCardsTotal = data.total || 0;
                packingCardsTotalCarton = data.total_carton || 0;
                const counts = data.status_counts || {};
                if (window.pageCfg.showBaseChips) {
                    $('#chipCountAll').text(counts.all ?? 0);
                    $('#chipCountComplete').text(counts.complete ?? 0);
                    $('#chipCountSealed').text(counts.sealed ?? 0);
                }
                if (window.pageCfg.showPlanningChips) {
                    $('#chipCountPlanned').text(counts.planned ?? 0);
                    $('#chipCountPacking').text(counts.packing ?? 0);
                }
                if (window.pageCfg.showShipmentChips) {
                    $('#chipCountInspect').text(counts.inspect ?? 0);
                    $('#chipCountShipped').text(counts.shipped ?? 0);
                    $('#chipCountReturning').text(counts.returning ?? 0);
                }
                window.lastPackingRows = data.rows || [];
                renderPackingCards(data.rows || []);
            });
        }

        function reloadPackingGlobal() {
            packingCardsPage = 1;
            loadPackingCards();
        }

        function packingCardsGoPage(delta) {
            const maxPage = Math.max(1, Math.ceil(packingCardsTotal / packingCardsRows));
            const next = packingCardsPage + delta;
            if (next < 1 || next > maxPage) return;
            packingCardsPage = next;
            loadPackingCards();
        }

        function renderPackingCards(rows) {
            const grid = $('#packingCardsGrid'),
                listWrapper = $('#packingListTableWrapper'),
                listBody = $('#packingListBody'),
                empty = $('#packingCardsEmpty');
            grid.empty();
            listBody.empty();
            if (!rows.length) {
                empty.removeClass('d-none');
                grid.addClass('d-none');
                listWrapper.addClass('d-none');
                $('#packingCardsInfo').text('0 carton');
                $('#packingCardsPageLabel').text('Halaman 1 / 1');
                updateSelectionGlobal();
                return;
            }
            empty.addClass('d-none');
            const cartonGroups = {},
                cartonOrder = [];
            rows.forEach(function(row) {
                const key = row.carton ?? '(tanpa carton)';
                if (!cartonGroups[key]) {
                    cartonGroups[key] = [];
                    cartonOrder.push(key);
                }
                cartonGroups[key].push(row);
            });
            if (window.packingViewModeGlobal === 'list') {
                grid.addClass('d-none');
                listWrapper.removeClass('d-none');
                cartonOrder.forEach(k => listBody.append(buildPackingListRow(cartonGroups[k])));
            } else {
                listWrapper.addClass('d-none');
                grid.removeClass('d-none');
                cartonOrder.forEach(k => grid.append(buildPackingCard(cartonGroups[k])));
            }
            const persisted = window.selectedPackpksGlobal || [];
            $('.packing-select-item').each(function() {
                const packpksArr = String($(this).data('packpks') || '').split(',').map(Number).filter(Boolean);
                $(this).toggleClass('selected', packpksArr.some(pk => persisted.includes(pk)));
            });
            const maxPage = Math.max(1, Math.ceil(packingCardsTotal / packingCardsRows));
            $('#packingCardsInfo').text(packingCardsTotalCarton + ' carton');
            $('#packingCardsPageLabel').text('Halaman ' + packingCardsPage + ' / ' + maxPage);
            $('#btnPackingCardsPrev').prop('disabled', packingCardsPage <= 1);
            $('#btnPackingCardsNext').prop('disabled', packingCardsPage >= maxPage);
            updateSelectionGlobal();
        }

        function getCartonStatusForRow(row) {
            if (Number(row.segel) === 1) return 'sealed';
            if (isRowComplete(row)) return 'complete';
            if (Number(row.pcs) > 0) return 'packing';
            return 'planned';
        }

        function getGroupStatus(groupRows) {
            const statuses = groupRows.map(getCartonStatusForRow);
            if (statuses.some(s => s === 'sealed')) return {
                key: 'sealed',
                label: 'Sealed'
            };
            if (statuses.every(s => s === 'complete')) return {
                key: 'complete',
                label: 'Complete'
            };
            if (statuses.some(s => s === 'packing' || s === 'complete')) return {
                key: 'packing',
                label: 'Packing'
            };
            return {
                key: 'planned',
                label: 'Planned'
            };
        }

        function isRowComplete(row) {
            const activeIdx = Object.keys(window.activeSizesGlobal || {});
            let hasAnyPlan = false;
            const semuaSama = activeIdx.every(function(i) {
                const plan = Number(row[`qtyp${i}`] || 0);
                if (plan <= 0) return true;
                hasAnyPlan = true;
                return Number(row[`qty${i}`] || 0) === plan;
            });
            return hasAnyPlan && semuaSama;
        }

        function getComboMarkerForPopk(popk) {
            const combo = (window.pgCombos || []).find(c => String(c.popk) === String(popk));
            return combo?.duplicateMarker || null;
        }

        function getComboLabel(row) {
            const marker = getComboMarkerForPopk(row.popk);
            return marker ? `${row.material??'-'} ${marker}` : (row.material ?? '-');
        }

        function getShipStampForGroup(groupRows) {
            const anyShipped = groupRows.some(r => r.ship_shipped === true),
                anyReturning = groupRows.some(r => r.ship_returning === true),
                anyInspect = groupRows.some(r => r.ship_inspect === true);
            if (anyShipped) return 'shipped';
            if (anyReturning) return 'returning';
            if (anyInspect) return 'inspect';
            return null;
        }

        function buildShipStamp(stampKey, dateStr, size) {
            if (!stampKey) return '';
            const sizeClass = (size === 'sm') ? ' ship-stamp-sm' : '';
            if (stampKey === 'shipped') {
                const tgl = formatStampDate(dateStr);
                return `<span class="ship-stamp ship-stamp-shipped${sizeClass}" title="Sudah Shipped"><span class="ship-stamp-text">Shipped</span>${tgl?`<span class="ship-stamp-date">${tgl}</span>`:''}</span>`;
            }
            if (stampKey === 'returning') {
                return `<span class="ship-stamp ship-stamp-returning${sizeClass}" title="Menunggu Diterima FG/Stuffing"><span class="ship-stamp-text">Menunggu</span></span>`;
            }
            if (stampKey === 'inspect') {
                return `<span class="ship-stamp ship-stamp-inspect${sizeClass}" title="Sedang Inspect"><span class="ship-stamp-text">Inspect</span></span>`;
            }
            return '';
        }

        function formatStampDate(value) {
            if (!value) return '';
            const datePart = String(value).split(' ')[0].split('T')[0];
            const parts = datePart.split('-');
            if (parts.length !== 3) return '';
            const [y, m, d] = parts;
            const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const mi = parseInt(m, 10) - 1;
            if (mi < 0 || mi > 11) return '';
            return `${parseInt(d,10)} ${bulan[mi]} ${y}`;
        }

        function computePackingGroupData(groupRows) {
            const activeIdx = Object.keys(window.activeSizesGlobal || {});
            const first = groupRows[0];
            const uniqueCombos = new Set(groupRows.map(r => `${r.material??'-'}||${r.secsz??''}`));
            const compositionLabel = uniqueCombos.size > 1 ? 'Mixed' : (groupRows.some(r => activeIdx.filter(i => Number(r[
                `qtyp${i}`] || 0) > 0).length > 1) ? 'Assorted' : 'Solid');
            const compositionClass = compositionLabel.toLowerCase();
            const status = getGroupStatus(groupRows);
            const partValue = groupRows.map(r => r.part).find(p => p !== null && p !== undefined && p !== '' && p !== 0);
            const partBadgeHtml = (partValue) ?
                `<span class="badge-soft" style="background:#ede9fe;color:#6d28d9;border-color:#ddd6fe;">${String(partValue)==='10'?'Session 10 &middot; Complete':'Session '+partValue}</span>` :
                '';
            const anyInspecting = groupRows.some(r => r.ship_inspect === true);
            const anyReturning = groupRows.some(r => r.ship_returning === true);
            const anyReject = groupRows.some(r => Number(r.reject) === 1);
            const canSeal = status.key === 'complete' && !anyInspecting && !anyReturning && !anyReject;
            const rejectBadgeHtml = anyReject ?
                `<span class="badge-soft" style="background:#fee2e2;color:#991b1b;border-color:#fecaca;"><i class="fas fa-times-circle me-1"></i>Reject</span>` :
                '';
            const shipStampKey = getShipStampForGroup(groupRows);
            const shippedRow = groupRows.find(r => r.ship_shipped === true);
            const shipDate = shippedRow?.ship_date ?? null;
            const shipStampHtml = buildShipStamp(shipStampKey, shipDate, 'lg');
            let totalPlan = 0,
                totalActual = 0,
                sizeRows = '';
            groupRows.forEach(function(row) {
                const plannedSizes = activeIdx.filter(i => Number(row[`qtyp${i}`] || 0) > 0);
                const materialLabel = getComboLabel(row);
                plannedSizes.forEach(function(i) {
                    const plan = Number(row[`qtyp${i}`] || 0),
                        actual = Number(row[`qty${i}`] || 0);
                    totalPlan += plan;
                    totalActual += actual;
                    const sizePct = plan > 0 ? Math.round((actual / plan) * 100) : 0;
                    const miniColor = sizePct >= 100 ? '#8bc63f' : '#0b89d2';
                    const secszTag = row.secsz ? ` (${row.secsz})` : '';
                    sizeRows +=
                        `<div class="size-row"><span class="dot"></span><span class="name">${materialLabel}${secszTag} &middot; ${window.activeSizesGlobal[i]??i}</span><span class="mini-progress"><span class="bar" style="width:${Math.min(100,sizePct)}%; background:${miniColor};"></span></span><span class="frac">${actual}/${plan}</span></div>`;
                });
            });
            const pct = totalPlan > 0 ? Math.round((totalActual / totalPlan) * 100) : 0;
            const barColor = status.key === 'sealed' ? '#8bc63f' : (pct >= 100 ? '#8bc63f' : '#0b89d2');
            let subline;
            if (uniqueCombos.size === 1) {
                const secszLabel = first.secsz ? ` &middot; Sec Size ${first.secsz}` : '';
                subline = `${getComboLabel(first)}${secszLabel}`;
            } else {
                subline = `${uniqueCombos.size} kombinasi Color/Sec Size`;
            }
            const isSealed = status.key === 'sealed';
            const packpks = groupRows.map(r => r.packpk);
            const packpksAttr = packpks.join(',');
            const allSegel = groupRows.every(r => Number(r.segel) === 1);
            const hasPart = groupRows.some(r => r.part !== null && r.part !== undefined && r.part !== '' && r.part !== 0);
            return {
                first,
                uniqueCombos,
                compositionLabel,
                compositionClass,
                status,
                totalPlan,
                totalActual,
                sizeRows,
                pct,
                barColor,
                subline,
                canSeal,
                isSealed,
                packpks,
                packpksAttr,
                allSegel,
                hasPart,
                partBadgeHtml,
                rejectBadgeHtml,
                shipStampHtml,
                shipStampKey,
                shipDate,
                anyInspecting,
                anyReturning,
                anyReject
            };
        }

        function buildActionButtonHtml(d) {
            if (!window.pageCfg.showSealAction) return ''; // BARU -- Inspection: kosong total
            if (d.anyReturning)
            return `<button class="btn btn-outline-secondary" disabled title="Carton sedang Menunggu Diterima dari Inspect, tidak dapat disegel/diedit dulu"><i class="fas fa-clock me-1"></i>Menunggu</button>`;
            if (d.anyReject)
            return `<button class="btn btn-outline-secondary" disabled title="Carton di-reject saat Inspect, perbaiki/rework dulu sebelum bisa disegel"><i class="fas fa-triangle-exclamation me-1"></i>Reject</button>`;
            if (!window.canManageSegel) return d.isSealed ?
                `<button class="btn btn-outline-secondary" disabled title="Anda tidak memiliki akses untuk Segel/Buka Segel"><i class="fas fa-lock me-1"></i>Sealed</button>` :
                `<button class="btn btn-outline-secondary" disabled title="Anda tidak memiliki akses untuk Segel/Buka Segel"><i class="fas fa-ban me-1"></i>Seal</button>`;
            if (d.isSealed && d.hasPart)
            return `<button class="btn btn-outline-secondary" disabled title="Sudah masuk proses shipment, tidak dapat dibuka Segel-nya lagi"><i class="fas fa-lock me-1"></i>Sealed</button>`;
            if (d.isSealed)
            return `<button class="btn btn-outline-secondary" onclick="event.stopPropagation(); unsealCarton('${d.packpksAttr}')"><i class="fas fa-unlock me-1"></i>Unseal</button>`;
            const sealTitle = d.anyInspecting ? 'title="Sedang proses Inspect, tidak dapat disegel dulu"' : '';
            return `<button class="btn ${d.canSeal?'btn-dark':'btn-outline-secondary'}" ${d.canSeal?'':'disabled'} ${sealTitle} onclick="event.stopPropagation(); sealCarton('${d.packpksAttr}')">Seal</button>`;
        }

        function buildPackingCard(groupRows) {
            const d = computePackingGroupData(groupRows);
            const ribbonHtml = d.allSegel ? '<div class="ribbon-segel">SEGEL</div>' : '';
            const editButtonHtml = (!window.pageCfg.showEditButton || d.isSealed || d.anyInspecting || d.anyReturning) ?
                '' :
                `<i class="fas fa-pen icon-btn" title="Edit" onclick="event.stopPropagation(); editCartonGlobal('${d.packpksAttr}')"></i>`;
            const actionButtonHtml = buildActionButtonHtml(d);
            return `<div class="col-12 col-md-6 col-xl-4"><div class="packing-select-item packing-card" data-packpks="${d.packpksAttr}" data-sealed="${d.isSealed?1:0}" data-haspart="${d.hasPart?1:0}" onclick="onPackingItemClick(event, this)">
                ${ribbonHtml}
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                    <span class="ctn-code">${d.first.carton??'-'}</span>
                    <span class="badge-soft ${d.compositionClass}">${d.compositionLabel}</span>
                    <span class="badge-status ${d.status.key}">${d.status.label}</span>
                    ${d.partBadgeHtml}
                    ${d.rejectBadgeHtml}
                    ${editButtonHtml}
                </div>
                <div class="subline mb-1">${d.subline}</div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="progress-main flex-grow-1"><span class="bar" style="width:${Math.min(100,d.pct)}%; background:${d.barColor};"></span></div>
                    <div class="text-nowrap" style="font-size:12.5px;"><strong>${d.totalActual}</strong> / ${d.totalPlan} pcs <span class="text-muted">${d.pct}%</span></div>
                </div>
                <div class="packing-card-sizes">${d.sizeRows}</div>
                <div class="card-barcode"><span class="barcode-text"><i class="fas fa-barcode me-1"></i>${d.first.nobar?d.first.nobar:'<span class="text-muted">Belum ada barcode</span>'}</span>${d.shipStampHtml}</div>
                ${actionButtonHtml ? `<div class="card-actions">${actionButtonHtml}</div>` : ''}
            </div></div>`;
        }

        function buildPackingListRow(groupRows) {
            const d = computePackingGroupData(groupRows);
            const editButtonHtml = (!window.pageCfg.showEditButton || d.isSealed || d.anyInspecting || d.anyReturning) ?
                '<span class="text-muted small">-</span>' :
                `<i class="fas fa-pen icon-btn" title="Edit" onclick="event.stopPropagation(); editCartonGlobal('${d.packpksAttr}')"></i>`;
            const actionButtonHtml = buildActionButtonHtml(d).replace('btn ', 'btn btn-sm ');
            const segelIcon = d.allSegel ? '<i class="fas fa-lock text-danger ms-1" title="Sudah Segel"></i>' : '';
            return `<tr class="packing-select-item packing-list-row" data-packpks="${d.packpksAttr}" data-sealed="${d.isSealed?1:0}" data-haspart="${d.hasPart?1:0}" onclick="onPackingItemClick(event, this)">
                <td class="text-start"><strong>${d.first.carton??'-'}</strong>${segelIcon}<div><span class="badge-soft ${d.compositionClass}" style="font-size:10px;">${d.compositionLabel}</span>${d.partBadgeHtml}${d.rejectBadgeHtml}</div></td>
                <td class="text-start" style="font-size:12.5px; color:#475569;"><div class="d-flex align-items-center gap-2"><span>${d.first.nobar?d.first.nobar:'<span class="text-muted">-</span>'}</span>${buildShipStamp(d.shipStampKey,d.shipDate,'sm')}</div></td>
                <td class="text-start" style="font-size:12.5px;">${d.subline}</td>
                <td class="text-center"><span class="badge-status ${d.status.key}">${d.status.label}</span></td>
                <td style="min-width:160px;"><div class="d-flex align-items-center gap-2"><div class="progress-main flex-grow-1"><span class="bar" style="width:${Math.min(100,d.pct)}%; background:${d.barColor};"></span></div><div class="text-nowrap" style="font-size:11.5px; min-width:70px;"><strong>${d.totalActual}</strong>/${d.totalPlan} <span class="text-muted">(${d.pct}%)</span></div></div></td>
                <td class="text-center"><div class="d-flex justify-content-center gap-2">${editButtonHtml}${actionButtonHtml}</div></td>
            </tr>`;
        }

        function onPackingItemClick(e, itemEl) {
            if ($(e.target).closest('.icon-btn, .card-actions, button, a').length) return;
            const $item = $(itemEl),
                wasSelected = $item.hasClass('selected');
            const packpksArr = String($item.data('packpks') || '').split(',').map(Number).filter(Boolean);
            if (!wasSelected) {
                const rowsForThisItem = (window.lastPackingRows || []).filter(r => packpksArr.includes(r.packpk));
                if (rowsForThisItem.some(r => r.ship_shipped === true)) {
                    showToast('warning', 'Carton yang sudah Shipment tidak dapat dipilih/diproses lagi.');
                    return;
                }
                packpksArr.forEach(pk => {
                    const row = (window.lastPackingRows || []).find(r => r.packpk === pk);
                    if (row) window.selectedRowsCache[pk] = row;
                });
            } else {
                packpksArr.forEach(pk => delete window.selectedRowsCache[pk]);
            }
            $item.toggleClass('selected');
            if (!wasSelected) {
                const selectedItems = $('.packing-select-item.selected');
                if (selectedItems.length > 1) {
                    const sealedValues = new Set(selectedItems.map(function() {
                        return $(this).data('sealed');
                    }).get());
                    if (sealedValues.size > 1) {
                        showToast('warning', 'Tidak bisa memilih carton dengan status Segel berbeda secara bersamaan.');
                        $item.removeClass('selected');
                        updateSelectionGlobal();
                        return;
                    }
                }
            }
            updateSelectionGlobal();
        }

        function updateSelectionGlobal() {
            const domSelectedPackpks = [];
            $('.packing-select-item.selected').each(function() {
                String($(this).data('packpks') || '').split(',').forEach(p => {
                    if (p !== '') domSelectedPackpks.push(Number(p));
                });
            });
            const stillPersistedPackpks = (window.selectedPackpksGlobal || []).filter(pk => window.selectedRowsCache[pk] &&
                !domSelectedPackpks.includes(pk));
            const packpks = [...new Set([...domSelectedPackpks, ...stillPersistedPackpks])];
            window.selectedPackpksGlobal = packpks;
            if (packpks.length === 0) {
                $('#selectedCountGlobal').text('0');
                $('#stickTopBarGlobal').hide();
                return;
            }
            const selectedRows = packpks.map(pk => window.selectedRowsCache[pk]).filter(Boolean);

            function abortSelection(message) {
                showToast('warning', message);
                $('.packing-select-item').removeClass('selected');
                window.selectedPackpksGlobal = [];
                window.selectedRowsCache = {};
                $('#selectedCountGlobal').text('0');
                $('#stickTopBarGlobal').hide();
            }
            if (selectedRows.some(r => r.ship_shipped === true)) {
                abortSelection('Carton yang sudah Shipment tidak dapat dipilih/diproses lagi.');
                return;
            }
            if (window.pageCfg.showPartFilter) {
                const normalizePart = (p) => {
                    if (p === null || p === undefined || p === '' || Number(p) === 0) return '';
                    return String(p);
                };
                const uniqueParts = new Set(selectedRows.map(r => normalizePart(r.part)));
                if (uniqueParts.size > 1) {
                    abortSelection('Tidak bisa memilih carton dengan Session berbeda secara bersamaan.');
                    return;
                }
            }
            const uniqueSegel = new Set(selectedRows.map(r => Number(r.segel) === 1));
            if (uniqueSegel.size > 1) {
                abortSelection('Tidak bisa memilih carton dengan status Segel berbeda secara bersamaan.');
                return;
            }
            const uniqueCartonCount = new Set(selectedRows.map(r => r.carton)).size;
            $('#selectedCountGlobal').text(uniqueCartonCount);
            $('#stickTopBarGlobal').show();
            const hasSegel = selectedRows.some(r => Number(r.segel) === 1);
            const hasPart = selectedRows.some(r => r.part !== null && r.part !== undefined && r.part !== '' && r.part !==
                0);
            const anyInspecting = selectedRows.some(r => r.ship_inspect === true);
            const anyReturning = selectedRows.some(r => r.ship_returning === true);
            const anyReject = selectedRows.some(r => Number(r.reject) === 1);
            const allComplete = selectedRows.length > 0 && selectedRows.every(isRowComplete);
            $('#btnBukaSegelGlobal').toggleClass('d-none', !(hasSegel && !hasPart && !anyReturning));
            $('#btnBulkSegelCtnGlobal').toggleClass('d-none', !(allComplete && !hasSegel && !anyInspecting && !
                anyReturning && !anyReject));
            if (window.pageCfg.showShipmentActions) {
                const eligibleForShipFlow = hasSegel && !anyInspecting && hasPart;
                $('#btnProsesInspectGlobal').toggleClass('d-none', !eligibleForShipFlow);
                $('#btnProsesShipmentGlobal').toggleClass('d-none', !eligibleForShipFlow);
                const allReturning = selectedRows.length > 0 && selectedRows.every(r => r.ship_returning === true);
                $('#btnTerimaCartonGlobal').toggleClass('d-none', !allReturning);
            }
            if (window.pageCfg.showCtnManagement) {
                $('#btnBulkActualCtnGlobal, #btnBulkDeleteActualCtnGlobal, #btnBulkCopyGlobal, #btnBulkDeleteGlobal')
                    .toggleClass('d-none', hasSegel);
            }
        }

        function closeMenuGlobal() {
            $('.packing-select-item').removeClass('selected');
            window.selectedPackpksGlobal = [];
            window.selectedRowsCache = {};
            updateSelectionGlobal();
        }

        function sealCarton(csv) {
            openSegelModalGlobal(1, csv.split(',').map(Number));
        }

        function unsealCarton(csv) {
            openSegelModalGlobal(0, csv.split(',').map(Number));
        }

        function bulkSegelCtnGlobal() {
            openSegelModalGlobal(1);
        }

        function bulkBukaSegelGlobal() {
            openSegelModalGlobal(0);
        }

        function bulkActualCtnGlobal() {
            const packpks = window.selectedPackpksGlobal || [];
            if (!packpks.length) {
                showToast('warning', 'Pilih minimal satu carton.');
                return;
            }
            $('#bulkSizeSelectGlobal').val('');
            $('#bulkActualGlobalWarning').addClass('d-none');
            renderBulkActualPreviewGlobal('');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkActualCtnGlobalModal')).show();
        }

        function markCartonPacked(csv) {
            $.ajax({
                url: R.updateCtn,
                method: 'POST',
                data: {
                    popk: '',
                    size: '',
                    packpk: csv
                },
                success: function(res) {
                    showToast(res.icon, res.title);
                    loadPackingCards();
                    reloadBreakdownSummary();
                },
                error: function(xhr) {
                    const res = xhr.responseJSON || {
                        icon: 'error',
                        title: 'Terjadi kesalahan.'
                    };
                    showToast(res.icon, res.title);
                }
            });
        }

        function syncStickyBarGlobalPosition() {
            const bar = document.getElementById('stickTopBarGlobal');
            if (!bar) return;
            const navbar = document.querySelector('#navbarMain, nav.navbar, header.navbar, .app-navbar');
            let top = 0;
            if (navbar) top = Math.max(0, navbar.getBoundingClientRect().bottom);
            bar.style.top = top + 'px';
        }
        window.addEventListener('scroll', syncStickyBarGlobalPosition, {
            passive: true
        });
        window.addEventListener('resize', syncStickyBarGlobalPosition);
    </script>

    @if ($cfg['showScanNobar'])
        <script>
            (function() {
                const input = document.getElementById('scanNobarInputGlobal'),
                    feedback = document.getElementById('scanNobarFeedbackGlobal');
                if (!input) return;
                let busy = false;

                function focusScan() {
                    try {
                        input.focus({
                            preventScroll: true
                        });
                    } catch (e) {
                        input.focus();
                    }
                }
                focusScan();
                let skipRefocus = false;
                document.addEventListener('mousedown', function(e) {
                    const t = e.target;
                    if (t && t.closest && t.closest(
                            'a, button, input, textarea, select, label, [onclick], [role="button"]')) {
                        skipRefocus = true;
                        setTimeout(() => {
                            skipRefocus = false;
                        }, 500);
                    }
                }, true);
                input.addEventListener('blur', function() {
                    setTimeout(function() {
                        if (skipRefocus) return;
                        const tag = document.activeElement ? document.activeElement.tagName : '';
                        if (!['INPUT', 'TEXTAREA', 'SELECT', 'A', 'BUTTON'].includes(tag)) focusScan();
                    }, 150);
                });

                function showScanFeedback(ok, msgHtml) {
                    feedback.innerHTML = msgHtml;
                    feedback.style.color = ok ? '#15803d' : '#DC143C';
                }
                input.addEventListener('keydown', function(e) {
                    if (e.key !== 'Enter') return;
                    e.preventDefault();
                    const nobar = input.value.trim();
                    input.value = '';
                    if (nobar === '' || busy) return;
                    busy = true;
                    showScanFeedback(true, `Memproses ${nobar} ...`);
                    $.ajax({
                        url: R.scanNobar,
                        method: 'POST',
                        data: {
                            po: PO,
                            op: OP,
                            poref: POREF,
                            mif: MIF,
                            nobar
                        },
                        success: function(res) {
                            showScanFeedback(true, res.title);
                            showToast(res.icon, res.title);
                            loadPackingCards();
                            reloadBreakdownSummary();
                        },
                        error: function(xhr) {
                            const res = xhr.responseJSON || {
                                icon: 'error',
                                title: 'Terjadi kesalahan.'
                            };
                            showScanFeedback(false, res.title);
                            showToast(res.icon, res.title);
                        },
                        complete: function() {
                            busy = false;
                            focusScan();
                        }
                    });
                });
            })();
        </script>
    @endif

    @if ($cfg['showShipmentPlan'] || $cfg['showShipmentActions'])
        <script>
            function getActiveSessionPart() {
                return localStorage.getItem(SESSION_KEY) || null;
            }

            function setActiveSessionPart(part) {
                if (part === null) localStorage.removeItem(SESSION_KEY);
                else localStorage.setItem(SESSION_KEY, String(part));
                renderActiveSessionBadge();
            }

            function renderActiveSessionBadge() {
                const active = getActiveSessionPart();
                $('#activeSessionBadge').html(active ?
                    `<i class="fas fa-circle-play text-primary me-1"></i>Sedang stuffing: <strong>Session ${active}</strong>` :
                    '');
            }

            function loadShipmentPlanCards() {
                $.get(R.partSummaryGlobal, {
                    po: PO,
                    op: OP,
                    poref: POREF,
                    mif: MIF
                }, function(data) {
                    const parts = data.parts || [],
                        wrap = $('#shipmentPlanCards');
                    wrap.empty();
                    if (!parts.length) {
                        wrap.html(
                            '<div class="text-muted" style="font-size:12.5px;">Belum ada Session Shipment yang diatur.</div>'
                        );
                        return;
                    }
                    const activePart = getActiveSessionPart();
                    parts.forEach(function(p) {
                        const pct = p.total > 0 ? Math.round((p.shipped / p.total) * 100) : 0,
                            isDone = p.locked >= p.total && p.total > 0,
                            isActive = String(activePart) === String(p.part),
                            barColor = isDone ? '#8bc63f' : '#0b89d2';
                        const shipDateLabel = formatStampDate(p.ship_date) || '-';
                        let actionsHtml;
                        if (isDone) actionsHtml =
                            `<button class="btn btn-outline-success btn-sm" disabled><i class="fas fa-check me-1"></i>Selesai</button>`;
                        else if (isActive) actionsHtml =
                            `<button class="btn btn-dark btn-sm" onclick="focusSessionPart(${p.part})">Lanjut</button><button class="btn btn-outline-danger btn-sm" onclick="endStuffingSession(${p.part}, ${p.total}, ${p.shipped})">End</button>`;
                        else actionsHtml =
                            `<button class="btn btn-outline-dark btn-sm" onclick="startStuffingSession(${p.part})">Mulai Session</button>`;
                        wrap.append(
                            `<div class="shipment-plan-card ${isActive?'is-active':''} ${isDone?'is-done':''}"><div class="shipment-plan-title">Session ${p.part}</div><div class="shipment-plan-shipdate"><i class="fas fa-calendar-day me-1"></i>Ship Date: <strong>${shipDateLabel}</strong></div><div class="shipment-plan-progress-bar"><span class="bar" style="width:${pct}%;background:${barColor};"></span></div><div class="shipment-plan-count">${p.shipped} / ${p.total} carton masuk (${pct}%)</div><div class="shipment-plan-actions">${actionsHtml}</div></div>`
                        );
                    });
                });
            }

            function startStuffingSession(part) {
                setActiveSessionPart(part);
                focusSessionPart(part);
                showToast('success', `Session ${part} dimulai.`);
                loadShipmentPlanCards();
            }

            function focusSessionPart(part) {
                if ($('#filterPartGlobal').data('combobox')) $('#filterPartGlobal').combobox('setValue', String(part));
                document.getElementById('packingCardsGrid')?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }

            function terimaCartonGlobal() {
                const packpks = window.selectedPackpksGlobal || [];
                if (!packpks.length) {
                    showToast('warning', 'Pilih minimal satu carton.');
                    return;
                }
                const selectedRows = packpks.map(pk => window.selectedRowsCache[pk]).filter(Boolean);
                const cartonCount = new Set(selectedRows.map(r => r.carton)).size;
                const rejectCount = new Set(selectedRows.filter(r => Number(r.reject) === 1).map(r => r.carton)).size;
                $('#terimaCartonInfoText').html(
                    `Anda akan menerima <strong>${cartonCount}</strong> carton (${packpks.length} baris) dari Inspect.`);
                $('#terimaCartonRejectWarning').toggleClass('d-none', rejectCount === 0);
                if (rejectCount > 0) $('#terimaCartonRejectCount').text(rejectCount);
                const cartonNames = [...new Set(selectedRows.map(r => r.carton))];
                $('#terimaCartonListText').text(cartonNames.length > 6 ? cartonNames.slice(0, 6).join(', ') +
                    `, +${cartonNames.length-6} lainnya` : cartonNames.join(', '));
                bootstrap.Modal.getOrCreateInstance(document.getElementById('terimaCartonModal')).show();
            }

            function confirmTerimaCartonGlobal() {
                const packpks = window.selectedPackpksGlobal || [];
                if (!packpks.length) return;
                $('#btnConfirmTerimaCarton').prop('disabled', true);
                $.ajax({
                    url: R.bulkShipAction,
                    method: 'POST',
                    data: {
                        packpk: packpks.join(','),
                        action: 'accept_return',
                        mif: MIF
                    },
                    success: function(res) {
                        showToast(res.icon, res.title);
                        bootstrap.Modal.getInstance(document.getElementById('terimaCartonModal')).hide();
                        closeMenuGlobal();
                        loadPackingCards();
                        reloadBreakdownSummary();
                    },
                    error: function(xhr) {
                        const res = xhr.responseJSON || {
                            icon: 'error',
                            title: 'Gagal menerima carton.'
                        };
                        showToast(res.icon, res.title);
                    },
                    complete: function() {
                        $('#btnConfirmTerimaCarton').prop('disabled', false);
                    }
                });
            }

            function bulkProsesInspectGlobal() {
                const packpks = window.selectedPackpksGlobal || [];
                if (!packpks.length) return;
                $.ajax({
                    url: R.bulkShipAction,
                    method: 'POST',
                    data: {
                        packpk: packpks.join(','),
                        action: 'inspect',
                        mif: MIF
                    },
                    success: function(res) {
                        showToast(res.icon, res.title);
                        closeMenuGlobal();
                        loadPackingCards();
                        reloadBreakdownSummary();
                    },
                    error: function(xhr) {
                        const res = xhr.responseJSON || {
                            icon: 'error',
                            title: 'Gagal.'
                        };
                        showToast(res.icon, res.title);
                    }
                });
            }

            function bulkProsesShipmentGlobal() {
                const packpks = window.selectedPackpksGlobal || [];
                if (!packpks.length) return;
                $.ajax({
                    url: R.bulkShipAction,
                    method: 'POST',
                    data: {
                        packpk: packpks.join(','),
                        action: 'shipment',
                        mif: MIF
                    },
                    success: function(res) {
                        showToast(res.icon, res.title);
                        closeMenuGlobal();
                        loadPackingCards();
                        reloadBreakdownSummary();
                    },
                    error: function(xhr) {
                        const res = xhr.responseJSON || {
                            icon: 'error',
                            title: 'Gagal.'
                        };
                        showToast(res.icon, res.title);
                    }
                });
            }
        </script>
    @endif

    @if ($cfg['showHistoryTab'])
        <script>
            window.inspectionActiveTab = 'current';

            function setInspectionTab(tab) {
                window.inspectionActiveTab = tab;
                $('#tabInspectCurrentBtn, #tabInspectHistoryBtn').removeClass('active');
                $(tab === 'current' ? '#tabInspectCurrentBtn' : '#tabInspectHistoryBtn').addClass('active');

                $('#statusFilterChipsGlobal').toggleClass('d-none', tab === 'history');
                closeMenuGlobal();

                if (tab === 'history') {
                    loadHistoryCards();
                } else {
                    loadPackingCards();
                }
            }

            function loadHistoryCards() {
                $.get(R.historyListDetailGlobal, {
                    po: PO,
                    op: OP,
                    poref: POREF,
                    mif: MIF,
                    search: $('#searchPackingGlobal').val(),
                    color: $('#filterColorGlobal').combobox('getValue'),
                    secsz: $('#filterSecszGlobal').combobox('getValue'),
                    sort: $('#sortFieldGlobal').combobox('getValue'),
                    page: 1,
                    rows: 200
                }, function(data) {
                    renderHistoryCards(data.rows || []);
                });
            }

            function formatHistoryDate(value) {
                if (!value) return null;
                return formatStampDate(value);
            }

            function getHistoryBadge(groupRows) {
                const anyShipped = groupRows.some(r => r.ship_shipped === true);
                const anyReturning = groupRows.some(r => r.ship_returning === true);
                const anyInspect = groupRows.some(r => r.ship_inspect === true);
                const anyReject = groupRows.some(r => Number(r.reject) === 1);
                const anyAccepted = groupRows.some(r => r.ship_kembali);

                if (anyShipped) return {
                    key: 'shipped',
                    label: 'Shipped',
                    color: '#2563eb',
                    bg: '#dbeafe'
                };
                if (anyReturning) return {
                    key: 'returning',
                    label: 'Menunggu Diterima',
                    color: '#7c3aed',
                    bg: '#f3e8ff'
                };
                if (anyInspect) return {
                    key: 'inspect',
                    label: 'Sedang Inspect',
                    color: '#f59e0b',
                    bg: '#fef3c7'
                };
                if (anyReject) return {
                    key: 'reject',
                    label: 'Reject (Diterima)',
                    color: '#991b1b',
                    bg: '#fee2e2'
                };
                if (anyAccepted) return {
                    key: 'accepted',
                    label: 'Diterima Kembali',
                    color: '#16a34a',
                    bg: '#dcfce7'
                };
                return {
                    key: 'done',
                    label: 'Selesai',
                    color: '#64748b',
                    bg: '#f1f5f9'
                };
            }

            function buildHistoryCard(groupRows) {
                const first = groupRows[0];
                const badge = getHistoryBadge(groupRows);
                const pinjamRow = groupRows.find(r => r.ship_pinjam);
                const kembaliRow = groupRows.find(r => r.ship_kembali);
                const tglPinjam = pinjamRow ? formatHistoryDate(pinjamRow.ship_pinjam) : null;
                const tglKembali = kembaliRow ? formatHistoryDate(kembaliRow.ship_kembali) : null;
                const uniqueCombos = new Set(groupRows.map(r => `${r.material ?? '-'}||${r.secsz ?? ''}`));
                const subline = uniqueCombos.size === 1 ?
                    `${getComboLabel(first)}${first.secsz ? ' &middot; Sec Size ' + first.secsz : ''}` :
                    `${uniqueCombos.size} kombinasi Color/Sec Size`;

                return `
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="packing-card" style="height:auto; cursor:default;">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <span class="ctn-code">${first.carton ?? '-'}</span>
                                <span class="badge-soft" style="background:${badge.bg};color:${badge.color};border-color:${badge.bg};">${badge.label}</span>
                            </div>
                            <div class="subline mb-2">${subline}</div>
                            <div style="font-size:12px; color:#475569; line-height:1.6;">
                                <div><i class="fas fa-arrow-right-to-bracket me-1" style="color:#f59e0b;"></i> Masuk Inspect: <strong>${tglPinjam ?? '-'}</strong></div>
                                <div><i class="fas fa-arrow-right-from-bracket me-1" style="color:#16a34a;"></i> Diterima Kembali: <strong>${tglKembali ?? 'Belum diterima'}</strong></div>
                            </div>
                            <div class="card-barcode mt-2">
                                <span class="barcode-text"><i class="fas fa-barcode me-1"></i>${first.nobar || '<span class="text-muted">Belum ada barcode</span>'}</span>
                            </div>
                        </div>
                    </div>
                `;
            }

            function buildHistoryListRow(groupRows) {
                const first = groupRows[0];
                const badge = getHistoryBadge(groupRows);
                const pinjamRow = groupRows.find(r => r.ship_pinjam);
                const kembaliRow = groupRows.find(r => r.ship_kembali);
                const tglPinjam = pinjamRow ? formatHistoryDate(pinjamRow.ship_pinjam) : '-';
                const tglKembali = kembaliRow ? formatHistoryDate(kembaliRow.ship_kembali) : 'Belum diterima';
                const uniqueCombos = new Set(groupRows.map(r => `${r.material ?? '-'}||${r.secsz ?? ''}`));
                const subline = uniqueCombos.size === 1 ?
                    `${getComboLabel(first)}${first.secsz ? ' &middot; ' + first.secsz : ''}` :
                    `${uniqueCombos.size} kombinasi`;

                return `
                    <tr>
                        <td class="text-start"><strong>${first.carton ?? '-'}</strong></td>
                        <td class="text-start" style="font-size:12.5px;">${first.nobar || '-'}</td>
                        <td class="text-start" style="font-size:12.5px;">${subline}</td>
                        <td class="text-center"><span class="badge-status" style="background:${badge.bg};color:${badge.color};">${badge.label}</span></td>
                        <td style="font-size:12px;">Masuk: ${tglPinjam}<br>Kembali: ${tglKembali}</td>
                    </tr>
                `;
            }

            function renderHistoryCards(rows) {
                const grid = $('#packingCardsGrid'),
                    listWrapper = $('#packingListTableWrapper'),
                    listBody = $('#packingListBody'),
                    empty = $('#packingCardsEmpty');
                grid.empty();
                listBody.empty();

                if (!rows.length) {
                    empty.removeClass('d-none');
                    grid.addClass('d-none');
                    listWrapper.addClass('d-none');
                    $('#packingCardsInfo').text('0 carton');
                    return;
                }
                empty.addClass('d-none');

                const cartonGroups = {},
                    cartonOrder = [];
                rows.forEach(function(row) {
                    const key = row.carton ?? '(tanpa carton)';
                    if (!cartonGroups[key]) {
                        cartonGroups[key] = [];
                        cartonOrder.push(key);
                    }
                    cartonGroups[key].push(row);
                });

                if (window.packingViewModeGlobal === 'list') {
                    grid.addClass('d-none');
                    listWrapper.removeClass('d-none');
                    cartonOrder.forEach(k => listBody.append(buildHistoryListRow(cartonGroups[k])));
                } else {
                    listWrapper.addClass('d-none');
                    grid.removeClass('d-none');
                    cartonOrder.forEach(k => grid.append(buildHistoryCard(cartonGroups[k])));
                }
                $('#packingCardsInfo').text(cartonOrder.length + ' carton (histori)');
                $('#packingCardsPageLabel').text('');
                $('#btnPackingCardsPrev, #btnPackingCardsNext').prop('disabled', true);
            }
        </script>
    @endif

    @if ($cfg['showKembalikanButton'])
        <script>
            //  function ini SEBELUMNYA cuma ada di blade Inspection
            // yang LAMA (menu.inspection.input-global) -- sekarang kita render
            // lewat blade GABUNGAN, jadi harus didefinisikan DI SINI. Dipakai
            // tombol "Kembalikan ke Stuffing" di sticky bar.
            function confirmKembalikanStuffing() {
                const packpks = window.selectedPackpksGlobal || [];
                if (!packpks.length) {
                    showToast('warning', 'Pilih minimal satu carton.');
                    return;
                }
                const cartonCount = $('.packing-select-item.selected').length;
                $('#kembalikanInfoText').html(
                    `Anda akan mengembalikan <strong>${cartonCount}</strong> carton ke FinishGood.`
                );
                // Reset pilihan OK/Reject ke default setiap modal dibuka --
                // function ini didefinisikan di modal-kembalikan-stuffing-global.
                if (typeof resetInspectResultChoice === 'function') {
                    resetInspectResultChoice();
                }
                bootstrap.Modal.getOrCreateInstance(document.getElementById('kembalikanStuffingModal')).show();
            }

            // Dipanggil oleh tombol "Ya, Kembalikan" di dalam modal
            // (modal-kembalikan-stuffing-global.blade.php).
            function submitKembalikanStuffing() {
                const packpks = window.selectedPackpksGlobal || [];
                if (!packpks.length) return;

                const isReject = $('input[name="inspectResultGlobal"]:checked').val() === 'reject';

                $('#btnConfirmKembalikanStuffing').prop('disabled', true);

                $.ajax({
                    url: R.bulkShipAction,
                    method: 'POST',
                    data: {
                        packpk: packpks.join(','),
                        action: 'request_return',
                        reject: isReject ? 1 : 0,
                        mif: MIF
                    },
                    success: function(res) {
                        showToast(res.icon, res.title);
                        bootstrap.Modal.getInstance(document.getElementById('kembalikanStuffingModal')).hide();
                        closeMenuGlobal();
                        loadPackingCards();
                        reloadBreakdownSummary();
                    },
                    error: function(xhr) {
                        const res = xhr.responseJSON || {
                            icon: 'error',
                            title: 'Gagal mengembalikan carton.'
                        };
                        showToast(res.icon, res.title);
                    },
                    complete: function() {
                        $('#btnConfirmKembalikanStuffing').prop('disabled', false);
                    }
                });
            }
        </script>
    @endif
@endsection
