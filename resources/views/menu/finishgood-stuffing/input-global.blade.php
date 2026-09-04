@extends('layout.main')

@section('css_custom')
    <style>
        .page-wrap {
            padding: 16px;
        }

        .btn-icon-custom:hover {
            background-color: #f8fafc !important;
            color: #1e293b !important;
            transform: translateX(-3px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
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
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }

        .packing-card.selected {
            border-color: #359DD9;
            box-shadow: 0 0 0 2px rgba(53, 157, 217, .25);
            background-color: #f0f9ff;
        }

        .packing-card .icon-btn,
        .packing-card .card-actions .btn {
            cursor: pointer;
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
            z-index: 10;
        }

        .packing-card .packing-card-sizes {
            flex: 1 1 auto;
            min-height: 0;
            /* WAJIB supaya flex child bisa di-scroll, bukan overflow keluar card */
            overflow-y: auto;
            margin-bottom: 8px;
            padding-right: 4px;
        }

        .packing-card .packing-card-sizes::-webkit-scrollbar {
            width: 5px;
        }

        .packing-card .packing-card-sizes::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .packing-card .ctn-code {
            font-weight: 700;
            font-size: 14px;
            color: #0f172a;
        }

        .packing-card .badge-soft {
            font-size: 10.5px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 999px;
            border: 1px solid transparent;
        }

        /* Solid -- 1 combo, 1 size: netral/abu-abu (default, paling sederhana) */
        .packing-card .badge-soft.solid {
            background: #f1f5f9;
            color: #64748b;
            border-color: #e2e8f0;
        }

        /* Assorted -- 1 combo, multi size: biru lembut */
        .packing-card .badge-soft.assorted {
            background: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd;
        }

        /* Mixed -- multi combo (lintas warna): oranye/amber, paling "ramai" */
        .packing-card .badge-soft.mixed {
            background: linear-gradient(90deg, #f97316, #64748b, #8b5cf6);
            color: #f1f5f9;
            border-color: #fde68a;
        }

        .packing-card .badge-status {
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 999px;
        }

        .packing-card .badge-status.planned {
            background: #f1f5f9;
            color: #64748b;
        }

        .packing-card .badge-status.packing {
            background: #0b89d2;
            color: #ffff;
        }

        .packing-card .badge-status.complete {
            background: #8bc63f;
            color: #ffff;
        }

        .packing-card .badge-status.sealed {
            background: #321414;
            color: #e2e8f0;
        }

        .packing-card .icon-btn {
            color: #94a3b8;
            cursor: pointer;
            font-size: 13px;
            padding: 2px 4px;
        }

        .packing-card .icon-btn:hover {
            color: #1e293b;
        }

        .packing-card .progress-main {
            height: 6px;
            background: #eef0f2;
            border-radius: 3px;
            overflow: hidden;
        }

        .packing-card .progress-main .bar {
            display: block;
            height: 100%;
            border-radius: 3px;
        }

        .packing-card .subline {
            font-size: 12.5px;
            color: #334155;
        }

        .packing-card .size-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            padding: 3px 0;
        }

        .packing-card .size-row .mini-progress .bar {
            display: block;
            height: 100%;
            border-radius: 3px;
        }

        .packing-card .size-row .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #0f172a;
            flex-shrink: 0;
        }

        .packing-card .size-row .name {
            flex: 0 0 auto;
            min-width: 110px;
            color: #334155;
        }

        .packing-card .size-row .mini-progress {
            flex: 1;
            height: 5px;
            background: #eef0f2;
            border-radius: 3px;
            overflow: hidden;
        }

        .packing-card .size-row .mini-progress .bar {
            height: 100%;
            border-radius: 3px;
        }

        .packing-card .size-row .frac {
            flex: 0 0 44px;
            text-align: right;
            color: #64748b;
        }

        .packing-card .card-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            flex-shrink: 0;
        }

        .packing-card .card-actions .btn {
            flex: 1;
            font-size: 12px;
            padding: 6px 8px;
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
        }

        .sticky-order-bar {
            display: none;
            position: fixed;
            left: 0;
            right: 0;
            z-index: 1030;
            background: #359DD9;
            color: #fff;
            transition: top 0.15s ease;
        }

        .sticky-order-inner {
            height: 44px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 16px;
        }

        .sticky-count-wrap {
            flex-shrink: 0;
            white-space: nowrap;
            font-size: 13.5px;
        }

        .sticky-actions-wrap {
            flex: 1 1 auto;
            min-width: 0;
            /* WAJIB supaya overflow-x benar-benar bisa scroll */
            display: flex;
            align-items: center;
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .sticky-actions-wrap::-webkit-scrollbar {
            height: 3px;
        }

        .sticky-actions-wrap::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .4);
            border-radius: 3px;
        }

        .sticky-action {
            cursor: pointer;
            margin-left: 10px;
            font-weight: 500;
            opacity: .9;
        }

        .sticky-action:hover {
            opacity: 1;
        }

        #pgBreakdownTable .pg-empty-cell {
            border-top: none;
        }

        #pgBreakdownTable tr[data-role="plan"] td {
            border-bottom: none;
        }

        #pgBreakdownTable tr[data-role="actual"] td {
            border-top: none;
            border-bottom: 1px dashed #e2e8f0;
        }

        #packingCardsGrid.list-mode .packing-card {
            height: auto;
            min-height: 0;
        }

        #packingCardsGrid.list-mode .packing-card-sizes {
            max-height: 160px;
        }

        #viewToggleGlobal .btn.active {
            background-color: #1e293b;
            border-color: #1e293b;
            color: #fff;
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
            transition: all .15s ease;
        }

        .status-chip:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .status-chip.active {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff;
        }

        .status-chip .chip-count {
            background: rgba(0, 0, 0, .08);
            border-radius: 999px;
            padding: 1px 7px;
            font-size: 11px;
        }

        .status-chip.active .chip-count {
            background: rgba(255, 255, 255, .2);
        }

        #packingListTable .packing-list-row {
            cursor: pointer;
            transition: background-color .1s ease;
        }

        #packingListTable .packing-list-row:hover td {
            background-color: #f8fafc;
        }

        #packingListTable .packing-list-row.selected td {
            background-color: #f0f9ff;
            border-color: #bae6fd;
        }

        #packingListTable td {
            vertical-align: middle;
        }

        #packingListTable .icon-btn {
            color: #94a3b8;
            cursor: pointer;
            font-size: 13px;
            padding: 2px 4px;
        }

        #packingListTable .icon-btn:hover {
            color: #1e293b;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #f1f5f9;
            transition: 0.2s;
        }

        .action-btn:hover {
            background: #e2e8f0;
            transform: scale(1.05);
        }

        .action-btn-pdf img {
            display: block;
        }

        /* Tombol Khusus PDF */
        .action-btn.action-btn-pdf {
            background-color: #fee2e2 !important;
            color: #b91c1c !important;
        }

        .action-btn.action-btn-pdf:hover {
            background-color: #fecaca !important;
            color: #7f1d1d !important;
        }

        .card-barcode {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .card-barcode .barcode-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1 1 auto;
            min-width: 0;
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
            transform: rotate(-14deg);
        }

        .packing-card .ship-stamp {
            position: absolute;
            right: 8px;
            bottom: 8px;
            z-index: 20;
            box-shadow: 0 3px 8px rgba(0, 0, 0, .18);
        }

        .ship-stamp-text {
            font-size: 12.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .ship-stamp-date {
            font-size: 8.5px;
            font-weight: 600;
            opacity: .85;
            margin-top: 1px;
            font-family: Arial, sans-serif;
        }

        .ship-stamp-shipped {
            border-color: #2563eb;
            color: #2563eb;
        }

        .ship-stamp-inspect {
            border-color: #f59e0b;
            color: #f59e0b;
        }

        .ship-stamp.ship-stamp-sm {
            width: 42px;
            height: 42px;
            transform: rotate(-10deg);
        }

        .ship-stamp.ship-stamp-sm .ship-stamp-text {
            font-size: 8.5px;
        }

        .ship-stamp.ship-stamp-sm .ship-stamp-date {
            display: none;
        }
        .status-chip.active[data-status="complete"] {
            background: #8bc63f;
            border-color: #8bc63f;
        }
        .status-chip.active[data-status="inspect"] {
            background: #f59e0b;
            border-color: #f59e0b;
        }
        .status-chip.active[data-status="shipped"] {
            background: #2563eb;
            border-color: #2563eb;
        }

        .scan-nobar-wrap {
            display: flex;
            justify-content: center;
        }
        .scan-nobar-card {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .05);
            padding: 18px 20px;
        }
        .scan-nobar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
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
            transition: background .15s ease;
        }
        .scan-nobar-icon {
            font-size: 17px;
            color: #0369a1;
            transition: color .15s ease;
        }
        .scan-nobar-icon-wrap.is-processing { background: #fef3c7; }
        .scan-nobar-icon-wrap.is-processing .scan-nobar-icon {
            color: #d97706;
            animation: scanIconSpin 0.9s linear infinite;
        }
        .scan-nobar-icon-wrap.is-success { background: #dcfce7; }
        .scan-nobar-icon-wrap.is-success .scan-nobar-icon { color: #16a34a; }
        .scan-nobar-icon-wrap.is-error { background: #fee2e2; }
        .scan-nobar-icon-wrap.is-error .scan-nobar-icon { color: #dc2626; }
        @keyframes scanIconSpin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        .scan-nobar-title {
            font-weight: 700;
            font-size: 14.5px;
            color: #0f172a;
        }
        .scan-nobar-subtitle {
            font-size: 11.5px;
            color: #94a3b8;
        }
        .scan-nobar-input-wrap {
            position: relative;
            margin-bottom: 10px;
        }
        .scan-nobar-input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 13px;
            pointer-events: none;
        }
        .scan-nobar-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            padding: 9px 12px 9px 34px;
            color: #0f172a;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .scan-nobar-input:focus {
            outline: none;
            border-color: #0369a1;
            box-shadow: 0 0 0 3px rgba(3, 105, 161, .12);
        }
        .scan-nobar-feedback {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
        }
        .scan-nobar-feedback.is-processing { color: #d97706; }
        .scan-nobar-feedback.is-success    { color: #16a34a; }
        .scan-nobar-feedback.is-error      { color: #dc2626; }

        .shipment-plan-card {
            width: 230px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 16px;
            box-shadow: 0 1px 3px rgba(15,23,42,.05);
        }
        .shipment-plan-card.is-active {
            border-color: #359DD9;
            box-shadow: 0 0 0 2px rgba(53,157,217,.2);
        }
        .shipment-plan-card.is-done {
            border-color: #8bc63f;
            background: #f7fdf1;
        }
        .shipment-plan-title {
            font-weight: 700;
            font-size: 13.5px;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .shipment-plan-progress-bar {
            height: 6px;
            background: #eef0f2;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 6px;
        }
        .shipment-plan-progress-bar .bar {
            display: block;
            height: 100%;
        }
        .shipment-plan-count {
            font-size: 12px;
            color: #475569;
            margin-bottom: 10px;
        }
        .shipment-plan-actions {
            display: flex;
            gap: 6px;
        }
        .shipment-plan-actions .btn {
            flex: 1;
            font-size: 11.5px;
            padding: 5px 8px;
        }

        .shipment-plan-shipdate {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .shipment-plan-shipdate strong {
            color: #0f172a;
        }
        .ship-stamp-returning {
            border-color: #7c3aed;
            color: #7c3aed;
        }
    </style>
@endsection

@section('content')
    <div id="stickTopBarGlobal" class="sticky-order-bar">
        <div class="sticky-order-inner">
            <div>
                <strong><span id="selectedCountGlobal">0</span> carton terpilih</strong>
            </div>
            <div>
                @php
                    $guserpk = Session::get('guserpk');
                @endphp
                @if (in_array($guserpk, [35]))
                    <span class="sticky-action d-none" id="btnProsesInspectGlobal" onclick="bulkProsesInspectGlobal()">
                        Proses Inspect
                    </span>
                    <span class="sticky-action d-none" id="btnProsesShipmentGlobal" onclick="bulkProsesShipmentGlobal()">
                        Proses Shipment
                    </span>
                @endif
                @if (in_array($guserpk, [38]))
                    <span class="sticky-action d-none" id="btnTerimaCartonGlobal" onclick="terimaCartonGlobal()">
                        Terima Carton
                    </span>
                    <span class="sticky-action d-none" id="btnBukaSegelGlobal" onclick="bulkBukaSegelGlobal()">
                        Buka Segel
                    </span>
                    <span class="sticky-action" id="btnBulkSegelCtnGlobal" onclick="bulkSegelCtnGlobal()">
                        Segel CTN
                    </span>
                @endif
                <span class="sticky-action" onclick="closeMenuGlobal()">
                    Close
                </span>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4 px-4">

        <div
            class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-3 mb-4 gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:void(0)" onclick="goBack()"
                    class="btn btn-icon-custom d-inline-flex align-items-center justify-content-center shadow-sm border bg-white text-secondary rounded-circle"
                    style="width: 38px; height: 38px; transition: all 0.2s ease;" title="Kembali ke Daftar Data OP">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold text-dark mb-0" style="font-size: 1.15rem; letter-spacing: -0.3px;">
                        Packing list - Style {{ $dt2->style ?? '-' }}
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

        {{-- ===================== HEADER INFO & FORM ===================== --}}
        <div id="headerInfoWrapperGlobal">
            @include('menu.finishgood-stuffing.partials.header_info_global', [
                'dt2' => $dt2,
                'poNoList' => $poNoList,
                'colorList' => $colorList,
                'allPopks' => $allPopks,
            ])
        </div>

        {{-- ====== CARDS INFO (GLOBAL, LINTAS SEMUA POPK) ======= --}}
        <div id="cardsInfoWrapperGlobal">
            @include('menu.finishgood-stuffing.partials.cards_info_global', [
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

        @if (in_array($guserpk, [35]))
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="fw-bold text-dark" style="font-size:15px;">
                        <span class="rounded me-2" style="width:4px;height:16px;display:inline-block;background:#64748b;"></span>
                        Shipment Plan
                    </div>
                    <div class="text-muted" style="font-size:11.5px;" id="activeSessionBadge"></div>
                </div>
                <div id="shipmentPlanCards" class="d-flex gap-3 flex-wrap"></div>
            </div>
        @endif

        {{-- ====== Breakdown Size & Qty, Summary CTN(GLOBAL, LINTAS SEMUA POPK) ======= --}}
        <div id="breakdownSummaryWrapper">
            @include('menu.finishgood-stuffing.partials.breakdown_summary_global')
        </div>

        @if (in_array($guserpk, [35]))
            <div class="scan-nobar-wrap mb-4">
                <div class="scan-nobar-card">
                    <div class="scan-nobar-header">
                        <div class="scan-nobar-icon-wrap" id="scanIconWrapGlobal">
                            <i class="fas fa-barcode scan-nobar-icon" id="scanIconGlobal"></i>
                        </div>
                        <div>
                            <div class="scan-nobar-title"
                            >Scan Barcode Carton</div>
                            <div class="scan-nobar-subtitle">Untuk proses Shipment</div>
                        </div>
                    </div>

                    <div class="scan-nobar-input-wrap">
                        <i class="fas fa-search scan-nobar-input-icon"></i>
                        <input type="text"
                            id="scanNobarInputGlobal"
                            class="scan-nobar-input"
                            placeholder="Scan barcode / ketik nobar lalu Enter..."
                            autocomplete="off">
                    </div>

                    <div class="scan-nobar-feedback" id="scanNobarFeedbackGlobal">
                        <i class="fas fa-circle-info"></i>
                        <span>Siap menerima scan.</span>
                    </div>
                </div>
            </div>
        @endif

        

        {{-- ===================== DETAIL PACK TABLE (GLOBAL, LINTAS SEMUA POPK) ===================== --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="rounded me-2"
                        style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
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
                            <input id="filterSizeGlobal" style="width:110px;">
                            <input id="filterColorGlobal" style="width:150px;">
                            <input id="filterSecszGlobal" style="width:150px;">
                            <input id="sortFieldGlobal" style="width:150px;">
                            <input id="filterPartGlobal" style="width:150px;">
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="btn-group btn-group-sm" role="group" id="viewToggleGlobal">
                                <button type="button" class="btn btn-outline-secondary active" id="viewModeGridBtn"
                                    onclick="setViewModeGlobal('grid')" title="Tampilan Grid">
                                    <i class="fas fa-th-large"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="viewModeListBtn"
                                    onclick="setViewModeGlobal('list')" title="Tampilan List">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="p-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-3" id="statusFilterChipsGlobal">
                        <span class="status-chip active" data-status="" onclick="setStatusFilterGlobal('')">
                            Semua <span class="chip-count" id="chipCountAll">0</span>
                        </span>
                        <span class="status-chip" data-status="complete" onclick="setStatusFilterGlobal('complete')">
                            Complete <span class="chip-count" id="chipCountComplete">0</span>
                        </span>
                        <span class="status-chip" data-status="sealed" onclick="setStatusFilterGlobal('sealed')">
                            Sealed <span class="chip-count" id="chipCountSealed">0</span>
                        </span>
                        {{-- BARU --}}
                        <span class="status-chip" data-status="inspect" onclick="setStatusFilterGlobal('inspect')">
                            Inspect <span class="chip-count" id="chipCountInspect">0</span>
                        </span>
                        <span class="status-chip" data-status="shipped" onclick="setStatusFilterGlobal('shipped')">
                            Shipped <span class="chip-count" id="chipCountShipped">0</span>
                        </span>
                        <span class="status-chip" data-status="returning" onclick="setStatusFilterGlobal('returning')">
                            Menunggu Diterima <span class="chip-count" id="chipCountReturning">0</span>
                        </span>
                    
                        <div class="ms-auto d-flex align-items-center gap-2">
                            <span class="text-secondary" style="font-size:12px;">Tampilkan</span>
                            <input id="pageSizeGlobal" style="width:90px;">
                        </div>
                    </div>

                    <div id="packingCardsGrid" class="row g-3"></div>

                    <div id="packingListTableWrapper" class="table-responsive d-none">
                        <table class="table table-sm table-hover align-middle mb-0" id="packingListTable">
                            <thead class="table-light text-secondary"
                                style="font-size:11px; text-transform:uppercase; letter-spacing:0.3px;">
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
                                onclick="packingCardsGoPage(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span style="font-size:12.5px;" id="packingCardsPageLabel"></span>
                            <button class="btn btn-sm btn-outline-secondary" id="btnPackingCardsNext"
                                onclick="packingCardsGoPage(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('menu.finishgood-stuffing.modal-segel-ctn-global')
    @include('menu.finishgood-stuffing.modal-shipment-ctn-global')
    @include('menu.finishgood-stuffing.modal-end-session-global')
    @include('menu.finishgood-stuffing.modal-terima-carton-global')
@endsection

@section('js_custom')
    <script>
        window.pgCombos = @json($colorSecszCombos ?? []);
        window.pgSizes = @json($activeSizes);
        window.canManageSegel = @json(in_array(Session::get('guserpk'), [38]));
    </script>

    <script>
        function reloadBreakdownSummary() {
            $.get("{{ route('finish-good-stuffing.breakdownSummaryGlobal') }}", {
                po: @json($po),
                op: @json($op),
                poref: @json($poref ?? null),
                mif: @json($mif)
            }, function(html) {
                $('#breakdownSummaryWrapper').html(html);
                matrixTab = 'planning';
                $('.legend-item').removeClass('active');
                classifyMatrixCells();
            });
        }

        function refreshPgCombos() {
            return $.get("{{ route('finish-good-stuffing.combosGlobal') }}", {
                po: @json($po),
                op: @json($op),
                poref: @json($poref ?? null),
                mif: @json($mif)
            }, function(data) {
                window.pgCombos = data || [];
            });
        }

        function reloadCardsInfoGlobal() {
            $.get("{{ route('finish-good-stuffing.cardsInfoGlobal') }}", {
                po: @json($po),
                op: @json($op),
                poref: @json($poref ?? null),
                mif: @json($mif)
            }, function(html) {
                $('#cardsInfoWrapperGlobal').html(html);
            });
        }

        function reloadHeaderInfoGlobal() {
            return $.get("{{ route('finish-good-stuffing.headerInfoGlobal') }}", {
                po: @json($po),
                op: @json($op),
                poref: @json($poref ?? null),
                mif: @json($mif)
            }, function(html) {
                $('#headerInfoWrapperGlobal').html(html);
            });
        }

        function classifyMatrixCells() {
            document.querySelectorAll('#matrixTable td.matrix-cell').forEach(function(td) {
                const order = Number(td.dataset.order || 0);
                const trans = Number(td.dataset.trans || 0);
                const plan = Number(td.dataset.plan || 0);
                const actual = Number(td.dataset.actual || 0);

                // ---- Coverage: Order vs Polibag (Transfer) ----
                td.classList.remove('cov-exact', 'cov-short', 'cov-over', 'cov-none', 'cov-blank');
                let covColor = '#cbd5e1';
                if (order === 0 && trans === 0) {
                    td.classList.add('cov-blank');
                } else if (trans === 0 && order > 0) {
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

                // ---- Packing: Plan vs Actual ----
                td.classList.remove('pack-full', 'pack-progress', 'pack-empty', 'pack-blank');
                let packColor = '#cbd5e1';
                if (plan === 0) {
                    td.classList.add('pack-blank');
                } else if (actual >= plan) {
                    td.classList.add('pack-full');
                    packColor = '#16a34a';
                } else if (actual > 0) {
                    td.classList.add('pack-progress');
                    packColor = '#2563eb';
                } else {
                    td.classList.add('pack-empty');
                    packColor = '#94a3b8';
                }

                // ============================================================
                // BARU -- Planning: Plan vs Order (SAMA pola dengan Coverage,
                // tapi bandingkan plan terhadap order, bukan trans).
                // ============================================================
                td.classList.remove('plan-exact', 'plan-short', 'plan-over', 'plan-none', 'plan-blank');
                let planColor = '#cbd5e1';
                if (order === 0 && plan === 0) {
                    td.classList.add('plan-blank');
                } else if (plan === 0 && order > 0) {
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

                // ---- isi progress bar warna sesuai kategori di atas ----
                const covPct = order > 0 ? Math.min(100, (trans / order) * 100) : (trans > 0 ? 100 : 0);
                const packPct = plan > 0 ? Math.min(100, (actual / plan) * 100) : 0;
                const planPct = order > 0 ? Math.min(100, (plan / order) * 100) : (plan > 0 ? 100 : 0); // BARU

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

                // BARU
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
                const hue = Math.abs(hash) % 360;
                dot.style.background = `hsl(${hue}, 45%, 45%)`;
            });
        }

        let matrixTab = 'planning';

        // ============================================================
        // GANTI setMatrixTab() -- tambahkan handling untuk tab 'planning'.
        // ============================================================
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

            // FIX: legend-item, bukan lagi .matrix-chip.
            $('.legend-item').removeClass('active');
            $('#matrixTable td.matrix-cell').removeClass('matrix-dim');
        }

        // ============================================================
        // GANTI toggleMatrixChip() -- selector .legend-item, bukan .matrix-chip.
        // ============================================================
        function toggleMatrixChip(el) {
            const $el = $(el);
            const wasActive = $el.hasClass('active');

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
                if (!$(this).hasClass(targetClass)) {
                    $(this).addClass('matrix-dim');
                }
            });
        }

        function onMatrixCellClick(td) {
            const $td = $(td);
            const material = $td.data('material') || '';
            const secsz = $td.data('secsz') || '';
            const size = String($td.data('size'));

            if ($('#filterColorGlobal').data('combobox')) {
                $('#filterColorGlobal').combobox('setValue', material);
            }
            if ($('#filterSecszGlobal').data('combobox')) {
                $('#filterSecszGlobal').combobox('setValue', secsz);
            }
            if ($('#filterSizeGlobal').data('combobox')) {
                $('#filterSizeGlobal').combobox('setValue', size);
            }

            // Scroll ke Detail Packing / Carton biar user langsung lihat hasilnya.
            document.getElementById('packingCardsGrid')?.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    </script>

    <script>
        window.activeSizesGlobal = @json($activeSizes);
        window.colorListGlobal = @json($colorList ?? []);
        window.secszListGlobal = @json($secszList ?? []);
        window.packingViewModeGlobal = localStorage.getItem('packingViewModeGlobal') || 'grid';

        let packingCardsPage = 1;
        let packingCardsRows = 25;
        let packingCardsTotal = 0;
        let packingCardsTotalCarton = 0;
        let statusFilterGlobal = '';

        $(function() {
            initFilterColorGlobal();
            initFilterSizeGlobalCombobox();
            initFilterSecszGlobal();
            initFilterPartGlobal();
            initSortFieldGlobalCombobox();
            initPageSizeGlobalCombobox();
            setViewModeGlobal(window.packingViewModeGlobal);
            loadPackingCards();
            reloadBreakdownSummary();
            reloadCardsInfoGlobal();
        });

        function setViewModeGlobal(mode) {
            window.packingViewModeGlobal = mode;
            localStorage.setItem('packingViewModeGlobal', mode);

            $('#viewModeGridBtn, #viewModeListBtn').removeClass('active');
            $(mode === 'grid' ? '#viewModeGridBtn' : '#viewModeListBtn').addClass('active');

            // Render ulang dari data yang SUDAH ADA (tidak fetch ulang ke server)
            // -- renderPackingCards() sendiri yang menentukan mau tampil sebagai
            // card grid atau tabel list.
            renderPackingCards(window.lastPackingRows || []);
        }

        function initSortFieldGlobalCombobox() {
            const sortData = [{
                    value: '',
                    text: 'Urutan Default'
                },
                {
                    value: 'carton_asc',
                    text: 'No Carton (A-Z)'
                },
                {
                    value: 'carton_desc',
                    text: 'No Carton (Z-A)'
                },
                {
                    value: 'nobar_asc',
                    text: 'Barcode (A-Z)'
                },
                {
                    value: 'nobar_desc',
                    text: 'Barcode (Z-A)'
                },
            ];

            $('#sortFieldGlobal').combobox({
                data: sortData,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    reloadPackingGlobal();
                }
            });
        }

        function initPageSizeGlobalCombobox() {
            $('#pageSizeGlobal').combobox({
                data: [{
                        value: 25,
                        text: '25'
                    },
                    {
                        value: 50,
                        text: '50'
                    },
                    {
                        value: 100,
                        text: '100'
                    },
                    {
                        value: 200,
                        text: '200'
                    },
                ],
                valueField: 'value',
                textField: 'text',
                value: 25,
                editable: false,
                panelHeight: 'auto',
                onChange: function(newValue) {
                    packingCardsRows = parseInt(newValue) || 25;
                    reloadPackingGlobal();
                }
            });
        }

        function setStatusFilterGlobal(status) {
            statusFilterGlobal = status;

            $('#statusFilterChipsGlobal .status-chip').removeClass('active');
            $(`#statusFilterChipsGlobal .status-chip[data-status="${status}"]`).addClass('active');

            reloadPackingGlobal();
        }

        // GANTI loadPackingCards() -- tambahkan 'status' ke payload, dan simpan
        // status_counts dari response ke chip.
        function loadPackingCards() {
            $.get("{{ route('finish-good-stuffing.list.detail.global') }}", {
                po: @json($po),
                op: @json($op),
                poref: @json($poref ?? null),
                mif: @json($mif),
                search: $('#searchPackingGlobal').val(),
                size: $('#filterSizeGlobal').combobox('getValue'),
                color: $('#filterColorGlobal').combobox('getValue'),
                secsz: $('#filterSecszGlobal').combobox('getValue'),
                part: $('#filterPartGlobal').combobox('getValue'),
                sort: $('#sortFieldGlobal').combobox('getValue'),
                status: statusFilterGlobal, // BARU
                page: packingCardsPage,
                rows: packingCardsRows
            }, function(data) {
                packingCardsTotal = data.total || 0;
                packingCardsTotalCarton = data.total_carton || 0;

                //  update angka di tiap chip status.
                const counts = data.status_counts || {};
                $('#chipCountAll').text(counts.all ?? 0);
                $('#chipCountPlanned').text(counts.planned ?? 0);
                $('#chipCountPacking').text(counts.packing ?? 0);
                $('#chipCountComplete').text(counts.complete ?? 0);
                $('#chipCountSealed').text(counts.sealed ?? 0);
                $('#chipCountInspect').text(counts.inspect ?? 0);
                $('#chipCountShipped').text(counts.shipped ?? 0);
                $('#chipCountReturning').text(counts.returning ?? 0);

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

        window.selectedRowsCache = window.selectedRowsCache || {};

        function renderPackingCards(rows) {
            //  JANGAN reset seleksi di sini lagi -- biarkan bertahan
            // lintas pindah halaman/filter/sort.
            // (SEBELUMNYA: window.selectedPackpksGlobal = []; -- DIHAPUS)

            const grid = $('#packingCardsGrid');
            const listWrapper = $('#packingListTableWrapper');
            const listBody = $('#packingListBody');
            const empty = $('#packingCardsEmpty');

            grid.empty();
            listBody.empty();

            if (!rows.length) {
                empty.removeClass('d-none');
                grid.addClass('d-none');
                listWrapper.addClass('d-none');
                $('#packingCardsInfo').text('0 carton');
                $('#packingCardsPageLabel').text('Halaman 1 / 1');
                updateSelectionGlobal(); // tetap refresh sticky bar (bisa saja masih ada seleksi dari halaman lain)
                return;
            }

            empty.addClass('d-none');

            const cartonGroups = {};
            const cartonOrder = [];
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
                cartonOrder.forEach(function(cartonKey) {
                    listBody.append(buildPackingListRow(cartonGroups[cartonKey]));
                });
            } else {
                listWrapper.addClass('d-none');
                grid.removeClass('d-none');
                cartonOrder.forEach(function(cartonKey) {
                    grid.append(buildPackingCard(cartonGroups[cartonKey]));
                });
            }

            //  setelah DOM dibangun ulang, tandai lagi carton yang
            // packpk-nya ADA di seleksi yang tersimpan (window.selectedPackpksGlobal)
            // sebagai 'selected' -- supaya highlight-nya konsisten walau baru
            // pindah halaman/filter.
            const persisted = window.selectedPackpksGlobal || [];
            $('.packing-select-item').each(function() {
                const packpksArr = String($(this).data('packpks') || '')
                    .split(',').map(Number).filter(Boolean);
                const isSelected = packpksArr.some(pk => persisted.includes(pk));
                $(this).toggleClass('selected', isSelected);
            });

            const maxPage = Math.max(1, Math.ceil(packingCardsTotal / packingCardsRows));
            $('#packingCardsInfo').text(packingCardsTotalCarton + ' carton');
            $('#packingCardsPageLabel').text('Halaman ' + packingCardsPage + ' / ' + maxPage);
            $('#btnPackingCardsPrev').prop('disabled', packingCardsPage <= 1);
            $('#btnPackingCardsNext').prop('disabled', packingCardsPage >= maxPage);

            //  refresh sticky bar berdasarkan seleksi gabungan (halaman ini
            // + halaman lain yang masih tersimpan di cache).
            updateSelectionGlobal();
        }

        function getCartonStatusForRow(row) {
            if (Number(row.segel) === 1) return 'sealed';
            if (isRowComplete(row)) return 'complete';
            if (Number(row.pcs) > 0) return 'packing';
            return 'planned';
        }

        // Gabungkan status dari SEMUA popk dalam 1 carton -- prioritas: kalau
        // ADA salah satu sealed, seluruh carton dianggap sealed; kalau SEMUA
        // complete, dianggap complete; kalau ADA yang mulai diisi, dianggap
        // packing; selain itu planned.
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
                const actual = Number(row[`qty${i}`] || 0);
                return actual === plan;
            });
            return hasAnyPlan && semuaSama;
        }

        function getComboMarkerForPopk(popk) {
            const combo = (window.pgCombos || []).find(function(c) {
                return String(c.popk) === String(popk);
            });
            return combo?.duplicateMarker || null;
        }

        function getComboLabel(row) {
            const marker = getComboMarkerForPopk(row.popk);
            return marker ? `${row.material ?? '-'} ${marker}` : (row.material ?? '-');
        }

        function computePackingGroupData(groupRows) {
            const activeIdx = Object.keys(window.activeSizesGlobal || {});
            const first = groupRows[0];
            const uniqueCombos = new Set(groupRows.map(r => `${r.material ?? '-'}||${r.secsz ?? ''}`));
            const compositionLabel = uniqueCombos.size > 1 ?
                'Mixed' :
                (groupRows.some(r => activeIdx.filter(i => Number(r[`qtyp${i}`] || 0) > 0).length > 1) ? 'Assorted' :
                    'Solid');
            const compositionClass = compositionLabel.toLowerCase();
            const status = getGroupStatus(groupRows);
        
            const partValue = groupRows.map(r => r.part).find(p => p !== null && p !== undefined && p !== '' && p !== 0);
            const partBadgeHtml = partValue ?
                `<span class="badge-soft" style="background:#ede9fe;color:#6d28d9;border-color:#ddd6fe;">
                    ${String(partValue) === '10' ? 'Session 10 &middot; Complete' : 'Session ' + partValue}
                </span>` :
                '';
        
            // FIX UTAMA #1: anyInspecting SEKARANG dideklarasikan DI SINI --
            // SEBELUMNYA tidak ada sama sekali, dipakai di canSeal tanpa
            // pernah di-declare (ReferenceError).
            const anyInspecting = groupRows.some(r => r.ship_inspect === true);
            const anyReturning  = groupRows.some(r => r.ship_returning === true);
            const anyReject      = groupRows.some(r => Number(r.reject) === 1);
            const canSeal = status.key === 'complete' && !anyInspecting && !anyReturning && !anyReject;
        
            // FIX UTAMA #2: rejectBadgeHtml SEKARANG dihitung DI SINI (setelah
            // anyReject ada), pakai variabel LOKAL anyReject langsung --
            // SEBELUMNYA pakai 'd.anyReject' padahal 'd' belum eksis sama sekali
            // di titik itu (ReferenceError).
            const rejectBadgeHtml = anyReject
                ? `<span class="badge-soft" style="background:#fee2e2;color:#991b1b;border-color:#fecaca;">
                    <i class="fas fa-times-circle me-1"></i>Reject
                </span>`
                : '';
        
            const shipStampKey = getShipStampForGroup(groupRows);
            const shippedRow = groupRows.find(r => r.ship_shipped === true);
            const shipDate = shippedRow?.ship_date ?? null;
            const shipStampHtml = buildShipStamp(shipStampKey, shipDate, 'lg');
        
            let totalPlan = 0;
            let totalActual = 0;
            let sizeRows = '';
            groupRows.forEach(function(row) {
                const plannedSizes = activeIdx.filter(i => Number(row[`qtyp${i}`] || 0) > 0);
                const materialLabel = getComboLabel(row);
                plannedSizes.forEach(function(i) {
                    const plan = Number(row[`qtyp${i}`] || 0);
                    const actual = Number(row[`qty${i}`] || 0);
                    totalPlan += plan;
                    totalActual += actual;
                    const sizePct = plan > 0 ? Math.round((actual / plan) * 100) : 0;
                    const miniColor = sizePct >= 100 ? '#8bc63f' : '#0b89d2';
                    const secszTag = row.secsz ? ` (${row.secsz})` : '';
                    sizeRows += `
                        <div class="size-row">
                            <span class="dot"></span>
                            <span class="name">${materialLabel}${secszTag} &middot; ${window.activeSizesGlobal[i] ?? i}</span>
                            <span class="mini-progress"><span class="bar" style="width:${Math.min(100, sizePct)}%; background:${miniColor};"></span></span>
                            <span class="frac">${actual}/${plan}</span>
                        </div>
                    `;
                });
            });
        
            const pct = totalPlan > 0 ? Math.round((totalActual / totalPlan) * 100) : 0;
            const barColor = status.key === 'sealed' ? '#8bc63f' : (pct >= 100 ? '#8bc63f' : '#0b89d2');
        
            let subline;
            if (uniqueCombos.size === 1) {
                const materialLabel = getComboLabel(first);
                const secszLabel = first.secsz ? ` &middot; Sec Size ${first.secsz}` : '';
                subline = `${materialLabel}${secszLabel}`;
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
                rejectBadgeHtml, // BARU -- sekarang ikut di-return, dipakai buildPackingCard/List
                shipStampHtml,
                shipStampKey,
                shipDate,
                anyInspecting,
                anyReturning,
                anyReject
            };
        }

        function getShipStampForGroup(groupRows) {
            const anyShipped   = groupRows.some(r => r.ship_shipped === true);
            const anyReturning = groupRows.some(r => r.ship_returning === true); // BARU
            const anyInspect   = groupRows.some(r => r.ship_inspect === true);
            if (anyShipped) return 'shipped';
            if (anyReturning) return 'returning'; // BARU
            if (anyInspect) return 'inspect';
            return null;
        }

        function buildPackingCard(groupRows) {
            const d = computePackingGroupData(groupRows);
            const ribbonHtml = d.allSegel ? '<div class="ribbon-segel">SEGEL</div>' : '';
            const editButtonHtml = (d.isSealed || d.anyReturning) ?
                '' :
                `<i class="fas fa-pen icon-btn" title="Edit" onclick="event.stopPropagation(); editCartonGlobal('${d.packpksAttr}')"></i>`;

            let actionButtonHtml;
 
            if (d.anyReturning) {
                actionButtonHtml = `<button class="btn btn-outline-secondary" disabled title="Carton sedang Menunggu Diterima dari Inspect, tidak dapat disegel/diedit dulu">
                    <i class="fas fa-clock me-1"></i>Menunggu
                </button>`;
            } else if (d.anyReject) {
                // BARU -- carton SUDAH diterima balik dari Inspect, tapi hasilnya
                // Reject -- HARUS di-rework dulu (edit Actual Qty ulang) sebelum
                // bisa disegel lagi.
                actionButtonHtml = `<button class="btn btn-outline-secondary" disabled title="Carton di-reject saat Inspect, perbaiki/rework dulu sebelum bisa disegel">
                    <i class="fas fa-triangle-exclamation me-1"></i>Reject
                </button>`;
            } else if (!window.canManageSegel) {
                if (d.isSealed) {
                    actionButtonHtml = `<button class="btn btn-outline-secondary" disabled title="Anda tidak memiliki akses untuk Segel/Buka Segel">
                        <i class="fas fa-lock me-1"></i>Sealed
                    </button>`;
                } else {
                    actionButtonHtml = `<button class="btn btn-outline-secondary" disabled title="Anda tidak memiliki akses untuk Segel/Buka Segel">
                        <i class="fas fa-ban me-1"></i>Seal
                    </button>`;
                }
            } else if (d.isSealed && d.hasPart) {
                actionButtonHtml = `<button class="btn btn-outline-secondary" disabled title="Sudah masuk proses shipment, tidak dapat dibuka Segel-nya lagi">
                    <i class="fas fa-lock me-1"></i>Sealed
                </button>`;
            } else if (d.isSealed) {
                actionButtonHtml = `<button class="btn btn-outline-secondary"
                    onclick="event.stopPropagation(); unsealCarton('${d.packpksAttr}')">
                    <i class="fas fa-unlock me-1"></i>Unseal
                </button>`;
            } else {
                const sealTitle = d.anyInspecting ? 'title="Sedang proses Inspect, tidak dapat disegel dulu"' : '';
                actionButtonHtml = `<button class="btn ${d.canSeal ? 'btn-dark' : 'btn-outline-secondary'}" ${d.canSeal ? '' : 'disabled'} ${sealTitle}
                    onclick="event.stopPropagation(); sealCarton('${d.packpksAttr}')">Seal</button>`;
            }

            return `
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="packing-select-item packing-card" data-packpks="${d.packpksAttr}" data-sealed="${d.isSealed ? 1 : 0}" data-haspart="${d.hasPart ? 1 : 0}"
                        onclick="onPackingItemClick(event, this)">
                        ${ribbonHtml}
        
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="ctn-code">${d.first.carton ?? '-'}</span>
                            <span class="badge-soft ${d.compositionClass}">${d.compositionLabel}</span>
                            <span class="badge-status ${d.status.key}">${d.status.label}</span>
                            ${d.partBadgeHtml}
                            ${d.rejectBadgeHtml}
                        </div>
        
                        <div class="subline mb-1">${d.subline}</div>
        
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="progress-main flex-grow-1">
                                <span class="bar" style="width:${Math.min(100, d.pct)}%; background:${d.barColor};"></span>
                            </div>
                            <div class="text-nowrap" style="font-size:12.5px;">
                                <strong>${d.totalActual}</strong> / ${d.totalPlan} pcs
                                <span class="text-muted">${d.pct}%</span>
                            </div>
                        </div>
        
                        <div class="packing-card-sizes">${d.sizeRows}</div>
        
                        <div class="card-barcode">
                            <span class="barcode-text">
                                <i class="fas fa-barcode me-1"></i>${d.first.nobar ? d.first.nobar : '<span class="text-muted">Belum ada barcode</span>'}
                            </span>
                            ${d.shipStampHtml}
                        </div>
        
                        <div class="card-actions">
                            ${actionButtonHtml}
                        </div>
                    </div>
                </div>
            `;
        }

        function buildPackingListRow(groupRows) {
            const d = computePackingGroupData(groupRows);
            const editButtonHtml = (d.isSealed || d.anyReturning) ?
                '<span class="text-muted small">-</span>' :
                `<i class="fas fa-pen icon-btn" title="Edit" onclick="event.stopPropagation(); editCartonGlobal('${d.packpksAttr}')"></i>`;

            let actionButtonHtml;
            if (d.anyReturning) {
                actionButtonHtml = `<button class="btn btn-outline-secondary btn-sm" disabled title="Carton sedang Menunggu Diterima dari Inspect, tidak dapat disegel/diedit dulu">
                    <i class="fas fa-clock"></i>
                </button>`;
            } else if (!window.canManageSegel) {
                actionButtonHtml = d.isSealed ?
                `<button class="btn btn-outline-secondary btn-sm" disabled title="Anda tidak memiliki akses untuk Segel/Buka Segel">
                    <i class="fas fa-lock"></i>
                </button>` :
                `<button class="btn btn-outline-secondary btn-sm" disabled title="Anda tidak memiliki akses untuk Segel/Buka Segel">
                    <i class="fas fa-ban"></i>
                </button>`;
            } else if (d.isSealed && d.hasPart) {
                actionButtonHtml = `<button class="btn btn-outline-secondary btn-sm" disabled title="Sudah masuk proses shipment, tidak dapat dibuka Segel-nya lagi">
                    <i class="fas fa-lock"></i>
                </button>`;
            } else if (d.isSealed) {
                actionButtonHtml = `<button class="btn btn-outline-secondary btn-sm"
                    onclick="event.stopPropagation(); unsealCarton('${d.packpksAttr}')">
                    <i class="fas fa-unlock"></i>
                </button>`;
            } else {
                const sealTitle = d.anyInspecting ? 'title="Sedang proses Inspect, tidak dapat disegel dulu"' : '';
                actionButtonHtml = `<button class="btn ${d.canSeal ? 'btn-dark' : 'btn-outline-secondary'} btn-sm" ${d.canSeal ? '' : 'disabled'} ${sealTitle}
                    onclick="event.stopPropagation(); sealCarton('${d.packpksAttr}')">
                    <i class="fas fa-lock"></i>
                </button>`;
            }
                const segelIcon = d.allSegel ? '<i class="fas fa-lock text-danger ms-1" title="Sudah Segel"></i>' : '';

            return `
                <tr class="packing-select-item packing-list-row" data-packpks="${d.packpksAttr}" data-sealed="${d.isSealed ? 1 : 0}" data-haspart="${d.hasPart ? 1 : 0}"
                    onclick="onPackingItemClick(event, this)">
                    <td class="text-start">
                        <strong>${d.first.carton ?? '-'}</strong>${segelIcon}
                        <div>
                            <span class="badge-soft ${d.compositionClass}" style="font-size:10px;">${d.compositionLabel}</span>
                            ${d.partBadgeHtml}
                            ${d.rejectBadgeHtml}
                        </div>
                    </td>
                    <td class="text-start" style="font-size:12.5px; color:#475569;">
                        <div class="d-flex align-items-center gap-2">
                            <span>${d.first.nobar ? d.first.nobar : '<span class="text-muted">-</span>'}</span>
                            ${buildShipStamp(d.shipStampKey, d.shipDate, 'sm')}
                        </div>
                    </td>
                    <td class="text-start" style="font-size:12.5px;">${d.subline}</td>
                    <td class="text-center"><span class="badge-status ${d.status.key}">${d.status.label}</span></td>
                    <td style="min-width:160px;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress-main flex-grow-1">
                                <span class="bar" style="width:${Math.min(100, d.pct)}%; background:${d.barColor};"></span>
                            </div>
                            <div class="text-nowrap" style="font-size:11.5px; min-width:70px;">
                                <strong>${d.totalActual}</strong>/${d.totalPlan} <span class="text-muted">(${d.pct}%)</span>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-2">
                            ${editButtonHtml}
                            ${actionButtonHtml}
                        </div>
                    </td>
                </tr>
            `;
        }

        function buildShipStamp(stampKey, dateStr, size) {
            if (!stampKey) return '';
            const sizeClass = (size === 'sm') ? ' ship-stamp-sm' : '';
            if (stampKey === 'shipped') {
                const tgl = formatStampDate(dateStr);
                return `
                    <span class="ship-stamp ship-stamp-shipped${sizeClass}" title="Sudah Shipped">
                        <span class="ship-stamp-text">Shipped</span>
                        ${tgl ? `<span class="ship-stamp-date">${tgl}</span>` : ''}
                    </span>
                `;
            }
            if (stampKey === 'returning') {
                // BARU -- stamp ungu, SAMA warna dengan chip "Menunggu Diterima".
                return `
                    <span class="ship-stamp ship-stamp-returning${sizeClass}" title="Menunggu Diterima FG/Stuffing">
                        <span class="ship-stamp-text">Menunggu</span>
                    </span>
                `;
            }
            if (stampKey === 'inspect') {
                return `
                    <span class="ship-stamp ship-stamp-inspect${sizeClass}" title="Sedang Inspect">
                        <span class="ship-stamp-text">Inspect</span>
                    </span>
                `;
            }
            return '';
        }

        function onPackingItemClick(e, itemEl) {
            if ($(e.target).closest('.icon-btn, .card-actions, button, a').length) {
                return;
            }

            const $item = $(itemEl);
            const wasSelected = $item.hasClass('selected');
            const packpksArr = String($item.data('packpks') || '').split(',').map(Number).filter(Boolean);

            if (!wasSelected) {
                //  simpan row-row terkait ke cache SEBELUM validasi, supaya
                // updateSelectionGlobal() bisa membacanya.
                packpksArr.forEach(function(pk) {
                    const row = (window.lastPackingRows || []).find(r => r.packpk === pk);
                    if (row) window.selectedRowsCache[pk] = row;
                });
            } else {
                // Dibatalkan -- hapus dari cache juga.
                packpksArr.forEach(function(pk) {
                    delete window.selectedRowsCache[pk];
                });
            }

            $item.toggleClass('selected');
            updateSelectionGlobal();
        }

        // Mark packed -- sekarang menerima STRING packpk dipisah koma (bisa
        // lebih dari 1 kalau carton Mixed), reuse endpoint packing.update-ctn
        // dengan pola yang sama seperti bulkActualCtn (bulk, bukan 1 packpk).
        function markCartonPacked(packpksCsv) {
            $.ajax({
                url: "{{ route('finish-good-stuffing.update-ctn') }}",
                method: 'POST',
                data: {
                    popk: '',
                    size: '',
                    packpk: packpksCsv
                },
                success: function(res) {
                    showToast(res.icon, res.title);
                    loadPackingCards();
                    reloadBreakdownSummary();
                },
                error: function(xhr) {
                    let res = xhr.responseJSON || {
                        icon: 'error',
                        title: 'Terjadi kesalahan.'
                    };
                    showToast(res.icon, res.title);
                }
            });
        }

        // Seal -- juga menerima STRING packpk dipisah koma, reuse
        // packing.update-segel target=1 untuk SEMUA popk dalam carton ini.
        function sealCarton(packpksCsv) {
            const packpks = packpksCsv.split(',').map(Number);
            openSegelModalGlobal(1, packpks);
        }

        function unsealCarton(packpksCsv) {
            const packpks = packpksCsv.split(',').map(Number);
            openSegelModalGlobal(0, packpks);
        }

        function initFilterColorGlobal() {
            let colorData = [{
                value: '',
                text: 'Semua Color'
            }];
            (window.colorListGlobal || []).forEach(function(c) {
                colorData.push({
                    value: c,
                    text: c
                });
            });
            $('#filterColorGlobal').combobox({
                data: colorData,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    reloadPackingGlobal();
                }
            });
        }

        function initFilterSizeGlobalCombobox() {
            let sizeData = [{
                value: '',
                text: 'Semua Size'
            }];
            Object.keys(window.activeSizesGlobal || {}).forEach(function(i) {
                sizeData.push({
                    value: i,
                    text: window.activeSizesGlobal[i]
                });
            });
            $('#filterSizeGlobal').combobox({
                data: sizeData,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    reloadPackingGlobal();
                }
            });
        }

        function initFilterPartGlobal() {
            let partData = [{
                value: '',
                text: 'Semua Session'
            }];
            for (let i = 1; i <= 10; i++) {
                partData.push({
                    value: String(i),
                    text: (i === 10) ? 'Session 10 (Complete)' : `Session ${i}`
                });
            }
            $('#filterPartGlobal').combobox({
                data: partData,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    reloadPackingGlobal();
                }
            });
        }

        function initFilterSecszGlobal() {
            let secszData = [{
                value: '',
                text: 'Semua Sec Size'
            }];
            (window.secszListGlobal || []).forEach(function(s) {
                secszData.push({
                    value: s,
                    text: s
                });
            });
            $('#filterSecszGlobal').combobox({
                data: secszData,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    reloadPackingGlobal();
                }
            });
        }

        let packingGlobalSearchTimer = null;
        $('#searchPackingGlobal').on('keyup', function() {
            clearTimeout(packingGlobalSearchTimer);
            packingGlobalSearchTimer = setTimeout(reloadPackingGlobal, 300);
        });

        // JS Scan Barcode
        (function () {
            const input    = document.getElementById('scanNobarInputGlobal');
            const feedback = document.getElementById('scanNobarFeedbackGlobal');
            if (!input) return;
        
            let busy = false;
        
            function focusScan() {
                try { input.focus({ preventScroll: true }); } catch (e) { input.focus(); }
            }
            focusScan();
        
            // Auto-refocus ke input scan setiap kali user klik di luar elemen
            // interaktif lain -- SAMA UX dengan sistem lama, supaya scanner
            // fisik selalu bisa langsung ketik tanpa perlu klik input dulu.
            let skipRefocus = false;
            document.addEventListener('mousedown', function (e) {
                const t = e.target;
                if (t && t.closest && t.closest('a, button, input, textarea, select, label, [onclick], [role="button"]')) {
                    skipRefocus = true;
                    setTimeout(function () { skipRefocus = false; }, 500);
                }
            }, true);
        
            input.addEventListener('blur', function () {
                setTimeout(function () {
                    if (skipRefocus) return;
                    const ae = document.activeElement;
                    const tag = ae ? ae.tagName : '';
                    if (!['INPUT', 'TEXTAREA', 'SELECT', 'A', 'BUTTON'].includes(tag)) {
                        focusScan();
                    }
                }, 150);
            });
        
            function showScanFeedback(ok, msgHtml) {
                feedback.innerHTML = msgHtml;
                feedback.style.color = ok ? '#15803d' : '#DC143C';
            }
        
            input.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter') return;
                e.preventDefault();
        
                const nobar = input.value.trim();
                input.value = '';
                if (nobar === '' || busy) return;
        
                busy = true;
                showScanFeedback(true, `Memproses ${nobar} ...`);
        
                $.ajax({
                    url: "{{ route('finish-good-stuffing.scan-nobar') }}",
                    method: 'POST',
                    data: {
                        po: @json($po),
                        op: @json($op),
                        poref: @json($poref ?? null),
                        mif: @json($mif),
                        nobar: nobar
                    },
                    success: function (res) {
                        showScanFeedback(true, res.title);
                        showToast(res.icon, res.title);
                        loadPackingCards();
                        reloadBreakdownSummary();
                        if (typeof loadCardsSummaryGlobal === 'function') loadCardsSummaryGlobal();
                    },
                    error: function (xhr) {
                        const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                        showScanFeedback(false, res.title);
                        showToast(res.icon, res.title);
                    },
                    complete: function () {
                        busy = false;
                        focusScan();
                    }
                });
            });
        })();


        const SESSION_KEY = 'stuffingActivePart_{{ $po }}_{{ $op }}';
        window.isSupervisorStuffing = @json(in_array(Session::get('guserpk'), [34])); // sesuaikan guserpk supervisor
        
        function getActiveSessionPart() {
            return localStorage.getItem(SESSION_KEY) || null;
        }
        
        function setActiveSessionPart(part) {
            if (part === null) {
                localStorage.removeItem(SESSION_KEY);
            } else {
                localStorage.setItem(SESSION_KEY, String(part));
            }
            renderActiveSessionBadge();
        }
        
        function renderActiveSessionBadge() {
            const active = getActiveSessionPart();
            $('#activeSessionBadge').html(active
                ? `<i class="fas fa-circle-play text-primary me-1"></i>Sedang stuffing: <strong>Session ${active}</strong>`
                : '');
        }
        
        function loadShipmentPlanCards() {
            $.get("{{ route('finish-good-stuffing.partSummaryGlobal') }}", {
                po: @json($po), op: @json($op), poref: @json($poref ?? null), mif: @json($mif)
            }, function (data) {
                const parts = data.parts || [];
                const wrap = $('#shipmentPlanCards');
                wrap.empty();
        
                if (!parts.length) {
                    wrap.html('<div class="text-muted" style="font-size:12.5px;">Belum ada Session Shipment yang diatur.</div>');
                    return;
                }
        
                const activePart = getActiveSessionPart();
        
                parts.forEach(function (p) {
                    const pct = p.total > 0 ? Math.round((p.shipped / p.total) * 100) : 0;
                    const isDone = p.locked >= p.total && p.total > 0;
                    const isActive = String(activePart) === String(p.part);
                    const barColor = isDone ? '#8bc63f' : '#0b89d2';
                
                    // format tanggal plan shipment (reuse formatStampDate yang
                    // sudah ada, dipakai juga oleh ship stamp Shipped/Inspect).
                    const shipDateLabel = formatStampDate(p.ship_date) || '-';
                
                    let actionsHtml;
                    if (isDone) {
                        actionsHtml = `<button class="btn btn-outline-success btn-sm" disabled><i class="fas fa-check me-1"></i>Selesai</button>`;
                    } else if (isActive) {
                        actionsHtml = `
                            <button class="btn btn-dark btn-sm" onclick="focusSessionPart(${p.part})">Lanjut</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="endStuffingSession(${p.part}, ${p.total}, ${p.shipped})">End</button>
                        `;
                    } else {
                        actionsHtml = `<button class="btn btn-outline-dark btn-sm" onclick="startStuffingSession(${p.part})">Mulai Session</button>`;
                    }
                
                    wrap.append(`
                        <div class="shipment-plan-card ${isActive ? 'is-active' : ''} ${isDone ? 'is-done' : ''}">
                            <div class="shipment-plan-title">Session ${p.part}</div>
                            <div class="shipment-plan-shipdate">
                                <i class="fas fa-calendar-day me-1"></i>Ship Date: <strong>${shipDateLabel}</strong>
                            </div>
                            <div class="shipment-plan-progress-bar">
                                <span class="bar" style="width:${pct}%;background:${barColor};"></span>
                            </div>
                            <div class="shipment-plan-count">${p.shipped} / ${p.total} carton masuk (${pct}%)</div>
                            <div class="shipment-plan-actions">${actionsHtml}</div>
                        </div>
                    `);
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
            // Filter grid Detail Packing/Carton ke Part ini -- SAMA combobox
            // yang sudah ada, tidak perlu endpoint baru.
            if ($('#filterPartGlobal').data('combobox')) {
                $('#filterPartGlobal').combobox('setValue', String(part));
            }
            document.getElementById('packingCardsGrid')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
    </script>

    <script>
        // ============================================================
        // FORMATTER -- WAJIB ada di halaman ini karena dipakai sebagai
        // formatter di columns: dgPackingGlobal. Kalau salah satu dari
        // fungsi ini tidak terdefinisi, seluruh script init datagrid akan
        // GAGAL TOTAL (ReferenceError) dan tabel tidak akan pernah muncul.
        // ============================================================
        function formatAction(value, row, index) {
            let isShipped = row.status == 5;
            let isSegel = Number(row.segel) === 1;

            if (isShipped || isSegel) return '';

            return `
            <a href="javascript:void(0)" onclick="showToast('info','Edit carton (mode Global) belum tersedia.')"
                class="action-btn" title="Edit">
                <i class="fas fa-edit"></i>
            </a>
        `;
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

        function isRowComplete(row) {
            const activeIdx = Object.keys(window.activeSizesGlobal || {});
            let hasAnyPlan = false;

            const semuaSama = activeIdx.every(function(i) {
                const plan = Number(row[`qtyp${i}`] || 0);
                if (plan <= 0) return true;
                hasAnyPlan = true;
                const actual = Number(row[`qty${i}`] || 0);
                return actual === plan;
            });

            return hasAnyPlan && semuaSama;
        }

        function formatSegel(value, row) {
            if (Number(row.segel) === 1) {
                return `<img src="{{ asset('public/css/images/Segel_carton.png') }}" width="28" title="Sudah Disegel">`;
            }
            if (isRowComplete(row)) {
                return `<img src="{{ asset('public/css/images/Complete_carton.png') }}" width="28" title="Actual = Plan (Complete)">`;
            }
            return '<span class="text-muted">-</span>';
        }

        function formatPA(value, row) {
            return `
            <div style="line-height:18px;text-align:center;">
                <div style="font-weight:bold;border-bottom:1px solid #dcdcdc;">P</div>
                <div style="font-weight:bold;">A</div>
            </div>
        `;
        }

        function formatTotal(value, row) {
            return `
            <div style="line-height:18px;text-align:right">
                <div class="text-muted">${row.pcsp || 0}</div>
                <div><b>${row.pcs || 0}</b></div>
            </div>
        `;
        }

        function formatBalance(value, row) {
            let balance = (row.pcs || 0) - (row.pcsp || 0);
            let cls = balance < 0 ? 'color:#dc3545;font-weight:bold' : 'color:#198754;font-weight:bold';
            return `<span style="${cls}">${balance}</span>`;
        }

        @foreach ($activeSizes as $i => $sz)
            function formatSize{{ $i }}(value, row) {
                return `
                <div style="display:flex;flex-direction:column;height:40px;text-align:center;">
                    <div style="flex:1;border-bottom:1px solid #d9d9d9;color:#6c757d;">
                        ${row.qtyp{{ $i }} || ''}
                    </div>
                    <div style="flex:1;font-weight:bold;color:#212529;">
                        ${row.qty{{ $i }} ?? '&nbsp;'}
                    </div>
                </div>
            `;
            }
        @endforeach

        function rowStylerPacking(index, row) {
            if (row.status == 5) return 'datagrid-row-packing-shipped';
            return '';
        }

        // Dipakai tombol back & 2 tombol di toolbar Detail Packing -- juga
        // belum ada di halaman ini sama sekali.
        function goBack() {
            if (document.referrer && document.referrer.indexOf('/packing') !== -1) {
                window.history.back();
            } else {
                window.location.href = "{{ route('finish-good-stuffing.index') }}";
            }
        }

        function updateSelectionGlobal() {
            //  kumpulkan packpk dari DOM (halaman aktif) DAN dari
            // packpk yang sudah tersimpan di window.selectedPackpksGlobal (bisa
            // berasal dari halaman lain) -- union keduanya.
            const domSelectedPackpks = [];
            $('.packing-select-item.selected').each(function() {
                String($(this).data('packpks') || '').split(',').forEach(function(p) {
                    if (p !== '') domSelectedPackpks.push(Number(p));
                });
            });

            // Packpk dari halaman LAIN yang masih tersimpan (tidak ada elemen DOM-nya
            // di halaman aktif, tapi harus tetap dihitung).
            const stillPersistedPackpks = (window.selectedPackpksGlobal || [])
                .filter(pk => window.selectedRowsCache[pk] && !domSelectedPackpks.includes(pk));

            const packpks = [...new Set([...domSelectedPackpks, ...stillPersistedPackpks])];
            window.selectedPackpksGlobal = packpks;

            if (packpks.length === 0) {
                $('#selectedCountGlobal').text('0');
                $('#stickTopBarGlobal').hide();
                return;
            }

            // ambil row DARI CACHE (bukan cuma window.lastPackingRows,
            // yang cuma berisi halaman aktif) -- supaya carton dari halaman lain
            // tetap ikut divalidasi dengan benar.
            const selectedRows = packpks
                .map(pk => window.selectedRowsCache[pk])
                .filter(Boolean);

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

            const uniqueParts = new Set(selectedRows.map(r => String(r.part ?? '')));
            if (uniqueParts.size > 1) {
                abortSelection('Tidak bisa memilih carton dengan Session berbeda secara bersamaan.');
                return;
            }

            const uniqueSegel = new Set(selectedRows.map(r => Number(r.segel) === 1));
            if (uniqueSegel.size > 1) {
                abortSelection('Tidak bisa memilih carton dengan status Segel berbeda secara bersamaan.');
                return;
            }

            // Hitung jumlah CARTON unik (bukan baris/packpk) utk ditampilkan.
            const uniqueCartonCount = new Set(selectedRows.map(r => r.carton)).size;
            $('#selectedCountGlobal').text(uniqueCartonCount);
            $('#stickTopBarGlobal').show();

            const hasSegel = selectedRows.some(r => Number(r.segel) === 1);
            const hasPart = selectedRows.some(r => r.part !== null && r.part !== undefined && r.part !== '' && r.part !==
            0);
            const anyInspecting = selectedRows.some(r => r.ship_inspect === true);
            const allComplete = selectedRows.length > 0 && selectedRows.every(isRowComplete);

            const anyReturning = selectedRows.some(r => r.ship_returning === true); 
            $('#btnBukaSegelGlobal').toggleClass('d-none', !(hasSegel && !hasPart && !anyReturning));
            $('#btnBulkSegelCtnGlobal').toggleClass('d-none', !(allComplete && !hasSegel && !anyInspecting && !anyReturning));

            const eligibleForShipFlow = hasSegel && !anyInspecting && hasPart;
            $('#btnProsesInspectGlobal').toggleClass('d-none', !eligibleForShipFlow);
            $('#btnProsesShipmentGlobal').toggleClass('d-none', !eligibleForShipFlow);

            const allReturning = selectedRows.length > 0 && selectedRows.every(r => r.ship_returning === true);
            $('#btnTerimaCartonGlobal').toggleClass('d-none', !allReturning);

            $('#btnBulkActualCtnGlobal, #btnBulkDeleteActualCtnGlobal, #btnBulkCopyGlobal, #btnBulkDeleteGlobal')
                .toggleClass('d-none', hasSegel);
        }

        // GANTI closeMenuGlobal() -- selector juga diganti:
        function closeMenuGlobal() {
            $('.packing-select-item').removeClass('selected');
            window.selectedPackpksGlobal = [];
            window.selectedRowsCache = {};
            updateSelectionGlobal();
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

        function syncStickyBarGlobalPosition() {
            const bar = document.getElementById('stickTopBarGlobal');
            if (!bar) return;

            const navbar = document.querySelector('#navbarMain, nav.navbar, header.navbar, .app-navbar');

            let top = 0;
            if (navbar) {
                const rect = navbar.getBoundingClientRect();
                // rect.bottom = posisi tepi bawah navbar RELATIF ke viewport saat
                // ini -- kalau navbar sudah scroll keluar/hidden, nilainya <= 0,
                // langsung dijepit ke 0 (nempel paling atas, tidak ada gap).
                top = Math.max(0, rect.bottom);
            }

            bar.style.top = top + 'px';
        }

        // Panggil terus-menerus setiap scroll/resize supaya selalu sinkron,
        // dan sekali di awal load.
        window.addEventListener('scroll', syncStickyBarGlobalPosition, {
            passive: true
        });
        window.addEventListener('resize', syncStickyBarGlobalPosition);
        $(function() {
            loadShipmentPlanCards();
            renderActiveSessionBadge();
            syncStickyBarGlobalPosition();
        });
    </script>
@endsection
