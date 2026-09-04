{{-- menu/inspection/input-global.blade.php --}}
{{-- Halaman carton yang SEDANG Inspect (fca=1). User bisa PILIH carton
     lalu "Kembalikan ke Stuffing" (TAHAP 1 dari 2 -- lihat modal
     modal-kembalikan-stuffing-global.blade.php). TIDAK ADA Scan Nobar,
     Shipment Plan Session, atau Seal/Unseal -- di luar scope halaman
     ini. --}}

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
    </style>
@endsection

@section('content')

    {{-- ===================== STICKY BAR ===================== --}}
    <div id="stickTopBarInspection" class="sticky-order-bar">
        <div class="sticky-order-inner">
            <div>
                <strong><span id="selectedCountInspection">0</span> carton terpilih</strong>
            </div>
            <div>
                <span class="sticky-action" id="btnKembalikanStuffingGlobal" onclick="confirmKembalikanStuffing()">
                    Kembalikan ke Stuffing
                </span>
                <span class="sticky-action" onclick="closeSelectionInspection()">
                    Close
                </span>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4 px-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-3 mb-4 gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:void(0)" onclick="goBack()"
                    class="btn btn-icon-custom d-inline-flex align-items-center justify-content-center shadow-sm border bg-white text-secondary rounded-circle"
                    style="width: 38px; height: 38px;" title="Kembali ke Daftar Inspection">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold text-dark mb-0" style="font-size: 1.15rem;">
                        Carton Sedang Inspect — Style {{ $dt2->style ?? '-' }}
                    </h4>
                    <span>OP {{ $op }} &middot; Season {{ $dt2->season ?? '-' }} &middot; Buyer:
                        {{ $dt2->buyer ?? '-' }} &middot; PO {{ $po }}</span>
                </div>
            </div>
        </div>

        <div id="headerInfoWrapperGlobal">
            @include('menu.finishgood-stuffing.partials.header_info_global', [
                'dt2' => $dt2, 'poNoList' => $poNoList, 'colorList' => $colorList, 'allPopks' => $allPopks,
            ])
        </div>

        {{-- TIDAK ADA Scan Nobar, Shipment Plan Session, atau tombol
             Seal/Segel apa pun -- halaman ini KHUSUS lihat & kembalikan
             carton yang SUDAH masuk tahap Inspect (fca=1). --}}

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="rounded me-2" style="width:4px;height:16px;display:inline-block;background:#f59e0b;"></span>
                    <strong class="text-dark">Carton Sedang Inspect</strong>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="px-3 py-2 border-bottom bg-white">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="input-group" style="width:260px;">
                                <span class="input-group-text search">
                                    <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18">
                                </span>
                                <input type="text" class="form-control search" id="searchPackingGlobal"
                                    placeholder="Search Barcode / No CTN">
                            </div>
                            <input id="filterColorGlobal" style="width:150px;">
                            <input id="filterSecszGlobal" style="width:150px;">
                            <input id="sortFieldGlobal" style="width:150px;">
                        </div>
                        <div class="btn-group btn-group-sm" role="group" id="viewToggleGlobal">
                            <button type="button" class="btn btn-outline-secondary active" id="viewModeGridBtn"
                                onclick="setViewModeGlobal('grid')"><i class="fas fa-th-large"></i></button>
                            <button type="button" class="btn btn-outline-secondary" id="viewModeListBtn"
                                onclick="setViewModeGlobal('list')"><i class="fas fa-list"></i></button>
                        </div>
                    </div>
                </div>

                <div class="p-3">
                    <div id="packingCardsGrid" class="row g-3"></div>

                    <div id="packingListTableWrapper" class="table-responsive d-none">
                        <table class="table table-sm table-hover align-middle mb-0" id="packingListTable">
                            <thead class="table-light text-secondary" style="font-size:11px; text-transform:uppercase;">
                                <tr>
                                    <th class="text-start py-2">No CTN</th>
                                    <th class="text-start py-2">Barcode</th>
                                    <th class="text-start py-2">Color / Sec Size</th>
                                    <th class="py-2">Progress</th>
                                </tr>
                            </thead>
                            <tbody id="packingListBody"></tbody>
                        </table>
                    </div>

                    <div id="packingCardsEmpty" class="d-none text-center py-5">
                        <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="160">
                        <div class="fw-semibold mt-2">Tidak ada carton sedang Inspect</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-secondary" style="font-size:12.5px;" id="packingCardsInfo"></div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-secondary" id="btnPackingCardsPrev" onclick="packingCardsGoPage(-1)"><i class="fas fa-chevron-left"></i></button>
                            <span style="font-size:12.5px;" id="packingCardsPageLabel"></span>
                            <button class="btn btn-sm btn-outline-secondary" id="btnPackingCardsNext" onclick="packingCardsGoPage(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('menu.inspection.modal-kembalikan-stuffing-global')
@endsection

@section('js_custom')
    <script>
        window.pgCombos = @json($colorSecszCombos ?? []);
        window.activeSizesGlobal = @json($activeSizes);
        window.colorListGlobal   = @json($colorList ?? []);
        window.secszListGlobal   = @json($secszList ?? []);
        window.packingViewModeGlobal = localStorage.getItem('packingViewModeGlobal') || 'grid';
        window.selectedPackpksInspection = [];

        let packingCardsPage = 1, packingCardsRows = 25, packingCardsTotal = 0, packingCardsTotalCarton = 0;

        $(function () {
            initFilterColorGlobal();
            initFilterSecszGlobal();
            initSortFieldGlobalCombobox();
            setViewModeGlobal(window.packingViewModeGlobal);
            loadPackingCards();
            syncStickyBarInspectionPosition();
        });

        function goBack() {
            window.location.href = "{{ route('inspection.index') }}";
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
                data: [
                    { value: '', text: 'Urutan Default' },
                    { value: 'carton_asc', text: 'No Carton (A-Z)' },
                    { value: 'carton_desc', text: 'No Carton (Z-A)' },
                    { value: 'nobar_asc', text: 'Barcode (A-Z)' },
                    { value: 'nobar_desc', text: 'Barcode (Z-A)' },
                ],
                valueField: 'value', textField: 'text', value: '', editable: false, panelHeight: 'auto',
                onChange: function () { reloadPackingGlobal(); }
            });
        }
        function initFilterColorGlobal() {
            let colorData = [{ value: '', text: 'Semua Color' }];
            (window.colorListGlobal || []).forEach(c => colorData.push({ value: c, text: c }));
            $('#filterColorGlobal').combobox({
                data: colorData, valueField: 'value', textField: 'text', value: '', editable: false, panelHeight: 'auto',
                onChange: function () { reloadPackingGlobal(); }
            });
        }
        function initFilterSecszGlobal() {
            let secszData = [{ value: '', text: 'Semua Sec Size' }];
            (window.secszListGlobal || []).forEach(s => secszData.push({ value: s, text: s }));
            $('#filterSecszGlobal').combobox({
                data: secszData, valueField: 'value', textField: 'text', value: '', editable: false, panelHeight: 'auto',
                onChange: function () { reloadPackingGlobal(); }
            });
        }

        let packingGlobalSearchTimer = null;
        $('#searchPackingGlobal').on('keyup', function () {
            clearTimeout(packingGlobalSearchTimer);
            packingGlobalSearchTimer = setTimeout(reloadPackingGlobal, 300);
        });

        // Endpoint BEDA (inspection.list.detail.global) -- backend SUDAH
        // mengunci ke status=inspect, tidak perlu kirim 'status'/'part'.
        function loadPackingCards() {
            $.get("{{ route('inspection.list.detail.global') }}", {
                po: @json($po), op: @json($op), poref: @json($poref ?? null), mif: @json($mif),
                search: $('#searchPackingGlobal').val(),
                color: $('#filterColorGlobal').combobox('getValue'),
                secsz: $('#filterSecszGlobal').combobox('getValue'),
                sort: $('#sortFieldGlobal').combobox('getValue'),
                page: packingCardsPage, rows: packingCardsRows
            }, function (data) {
                packingCardsTotal = data.total || 0;
                packingCardsTotalCarton = data.total_carton || 0;
                window.lastPackingRows = data.rows || [];
                renderPackingCards(data.rows || []);
            });
        }
        function reloadPackingGlobal() { packingCardsPage = 1; loadPackingCards(); }
        function packingCardsGoPage(delta) {
            const maxPage = Math.max(1, Math.ceil(packingCardsTotal / packingCardsRows));
            const next = packingCardsPage + delta;
            if (next < 1 || next > maxPage) return;
            packingCardsPage = next;
            loadPackingCards();
        }

        function renderPackingCards(rows) {
            // Reset selection setiap reload (SAMA seperti Packing versi
            // awal -- halaman ini sederhana, tidak perlu persist lintas
            // halaman/filter).
            window.selectedPackpksInspection = [];
            $('#stickTopBarInspection').hide();
            $('#selectedCountInspection').text('0');

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
                return;
            }
            empty.addClass('d-none');

            const cartonGroups = {};
            const cartonOrder = [];
            rows.forEach(function (row) {
                const key = row.carton ?? '(tanpa carton)';
                if (!cartonGroups[key]) { cartonGroups[key] = []; cartonOrder.push(key); }
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

            const maxPage = Math.max(1, Math.ceil(packingCardsTotal / packingCardsRows));
            $('#packingCardsInfo').text(packingCardsTotalCarton + ' carton');
            $('#packingCardsPageLabel').text('Halaman ' + packingCardsPage + ' / ' + maxPage);
            $('#btnPackingCardsPrev').prop('disabled', packingCardsPage <= 1);
            $('#btnPackingCardsNext').prop('disabled', packingCardsPage >= maxPage);
        }

        function getComboMarkerForPopk(popk) {
            const combo = (window.pgCombos || []).find(c => String(c.popk) === String(popk));
            return combo?.duplicateMarker || null;
        }
        function getComboLabel(row) {
            const marker = getComboMarkerForPopk(row.popk);
            return marker ? `${row.material ?? '-'} ${marker}` : (row.material ?? '-');
        }

        // Disederhanakan -- halaman ini TIDAK PERNAH butuh
        // canSeal/isSealed/hasPart, cuma untuk TAMPILAN.
        function computePackingGroupData(groupRows) {
            const activeIdx = Object.keys(window.activeSizesGlobal || {});
            const first = groupRows[0];
            const uniqueCombos = new Set(groupRows.map(r => `${r.material ?? '-'}||${r.secsz ?? ''}`));
            const compositionLabel = uniqueCombos.size > 1 ? 'Mixed'
                : (groupRows.some(r => activeIdx.filter(i => Number(r[`qtyp${i}`] || 0) > 0).length > 1) ? 'Assorted' : 'Solid');
            const compositionClass = compositionLabel.toLowerCase();

            let totalPlan = 0, totalActual = 0, sizeRows = '';
            groupRows.forEach(function (row) {
                const plannedSizes = activeIdx.filter(i => Number(row[`qtyp${i}`] || 0) > 0);
                const materialLabel = getComboLabel(row);
                plannedSizes.forEach(function (i) {
                    const plan = Number(row[`qtyp${i}`] || 0);
                    const actual = Number(row[`qty${i}`] || 0);
                    totalPlan += plan; totalActual += actual;
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
            const barColor = pct >= 100 ? '#8bc63f' : '#0b89d2';

            let subline;
            if (uniqueCombos.size === 1) {
                const secszLabel = first.secsz ? ` &middot; Sec Size ${first.secsz}` : '';
                subline = `${getComboLabel(first)}${secszLabel}`;
            } else {
                subline = `${uniqueCombos.size} kombinasi Color/Sec Size`;
            }

            // Carton di halaman ini SUDAH PASTI sedang Inspect (backend
            // dikunci ke status=inspect) -- stamp SELALU 'inspect'.
            const shipStampHtml = buildShipStamp('inspect', 'lg');

            return { first, compositionLabel, compositionClass, totalPlan, totalActual, sizeRows, pct, barColor, subline, shipStampHtml };
        }

        function buildShipStamp(stampKey, size) {
            if (stampKey !== 'inspect') return '';
            const sizeClass = (size === 'sm') ? ' ship-stamp-sm' : '';
            return `
                <span class="ship-stamp ship-stamp-inspect${sizeClass}" title="Sedang Inspect">
                    <span class="ship-stamp-text">Inspect</span>
                </span>
            `;
        }

        // BARU -- card & list SEKARANG selectable (data-packpks + onclick),
        // dipakai sticky bar "Kembalikan ke Stuffing".
        function buildPackingCard(groupRows) {
            const d = computePackingGroupData(groupRows);
            const packpksAttr = groupRows.map(r => r.packpk).join(',');
            return `
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="packing-select-item packing-card" data-packpks="${packpksAttr}"
                        onclick="onInspectionItemClick(event, this)">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                            <span class="ctn-code">${d.first.carton ?? '-'}</span>
                            <span class="badge-soft ${d.compositionClass}">${d.compositionLabel}</span>
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
                    </div>
                </div>
            `;
        }

        function buildPackingListRow(groupRows) {
            const d = computePackingGroupData(groupRows);
            const packpksAttr = groupRows.map(r => r.packpk).join(',');
            return `
                <tr class="packing-select-item packing-list-row" data-packpks="${packpksAttr}"
                    onclick="onInspectionItemClick(event, this)">
                    <td class="text-start">
                        <strong>${d.first.carton ?? '-'}</strong>
                        <div><span class="badge-soft ${d.compositionClass}" style="font-size:10px;">${d.compositionLabel}</span></div>
                    </td>
                    <td class="text-start" style="font-size:12.5px; color:#475569;">
                        <div class="d-flex align-items-center gap-2">
                            <span>${d.first.nobar ? d.first.nobar : '<span class="text-muted">-</span>'}</span>
                            ${buildShipStamp('inspect', 'sm')}
                        </div>
                    </td>
                    <td class="text-start" style="font-size:12.5px;">${d.subline}</td>
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
                </tr>
            `;
        }

        // ============================================================
        // BARU -- selection & sticky bar. Sederhana (tidak perlu
        // validasi Segel/Part seperti Packing/FG-Stuffing, karena SEMUA
        // carton di halaman ini sudah pasti homogen: sedang Inspect).
        // ============================================================
        function onInspectionItemClick(e, itemEl) {
            const $item = $(itemEl);
            $item.toggleClass('selected');
            updateSelectionInspection();
        }

        function updateSelectionInspection() {
            const packpks = [];
            $('.packing-select-item.selected').each(function () {
                String($(this).data('packpks') || '').split(',').forEach(function (p) {
                    if (p !== '') packpks.push(Number(p));
                });
            });
            window.selectedPackpksInspection = packpks;

            const uniqueCartonCount = $('.packing-select-item.selected').length;

            $('#selectedCountInspection').text(uniqueCartonCount);
            $('#stickTopBarInspection').toggle(packpks.length > 0);
        }

        function closeSelectionInspection() {
            $('.packing-select-item').removeClass('selected');
            updateSelectionInspection();
        }

        function confirmKembalikanStuffing() {
            const packpks = window.selectedPackpksInspection || [];
            if (!packpks.length) {
                showToast('warning', 'Pilih minimal satu carton.');
                return;
            }
            const cartonCount = $('.packing-select-item.selected').length;
            $('#kembalikanInfoText').html(
                `Anda akan mengembalikan <strong>${cartonCount}</strong> carton ke Stuffing/FinishGood.`
            );
            resetInspectResultChoice(); // BARU
            bootstrap.Modal.getOrCreateInstance(document.getElementById('kembalikanStuffingModal')).show();
        }

        function submitKembalikanStuffing() {
            const packpks = window.selectedPackpksInspection || [];
            if (!packpks.length) return;
        
            // BARU: baca pilihan Hasil Inspect (ok/reject) dari modal.
            const isReject = $('input[name="inspectResultGlobal"]:checked').val() === 'reject';
        
            $('#btnConfirmKembalikanStuffing').prop('disabled', true);
        
            $.ajax({
                url: "{{ route('inspection.bulk-ship-action') }}",
                method: 'POST',
                data: {
                    packpk: packpks.join(','),
                    action: 'request_return',
                    reject: isReject ? 1 : 0, // BARU
                    mif: @json($mif)
                },
                success: function (res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('kembalikanStuffingModal')).hide();
                    closeSelectionInspection();
                    loadPackingCards();
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || { icon: 'error', title: 'Gagal mengembalikan carton.' };
                    showToast(res.icon, res.title);
                },
                complete: function () {
                    $('#btnConfirmKembalikanStuffing').prop('disabled', false);
                }
            });
        }

        function syncStickyBarInspectionPosition() {
            const bar = document.getElementById('stickTopBarInspection');
            if (!bar) return;
            const navbar = document.querySelector('#navbarMain, nav.navbar, header.navbar, .app-navbar');
            let top = 0;
            if (navbar) {
                const rect = navbar.getBoundingClientRect();
                top = Math.max(0, rect.bottom);
            }
            bar.style.top = top + 'px';
        }
        window.addEventListener('scroll', syncStickyBarInspectionPosition, { passive: true });
        window.addEventListener('resize', syncStickyBarInspectionPosition);
    </script>
@endsection