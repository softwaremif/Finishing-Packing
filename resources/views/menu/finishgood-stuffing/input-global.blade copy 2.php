@extends('layout.main')
@section('css_custom')
    <style>
        .page-wrap { padding: 16px; }
        .btn-icon-custom:hover {
            background-color: #f8fafc !important;
            color: #1e293b !important;
            transform: translateX(-3px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        }
        .packing-card {
            cursor: pointer; position: relative; overflow: hidden;
            border: 1px solid #e5e7eb; border-radius: 12px; background: #fff;
            padding: 14px 16px; height: 350px; display: flex; flex-direction: column;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }
        .packing-card.selected {
            border-color: #359DD9; box-shadow: 0 0 0 2px rgba(53, 157, 217, .25); background-color: #f0f9ff;
        }
        .packing-card .icon-btn, .packing-card .card-actions .btn { cursor: pointer; }
        .packing-card .ribbon-segel {
            position: absolute; top: 14px; right: -48px; transform: rotate(45deg);
            background: #dc2626; color: #fff; font-size: 13px; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase; padding: 8px 60px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .25); pointer-events: none; z-index: 10;
        }
        .packing-card .packing-card-sizes { flex: 1 1 auto; min-height: 0; overflow-y: auto; margin-bottom: 8px; padding-right: 4px; }
        .packing-card .packing-card-sizes::-webkit-scrollbar { width: 5px; }
        .packing-card .packing-card-sizes::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .packing-card .ctn-code { font-weight: 700; font-size: 14px; color: #0f172a; }
        .packing-card .badge-soft { font-size: 10.5px; font-weight: 600; padding: 3px 8px; border-radius: 999px; border: 1px solid transparent; }
        .packing-card .badge-soft.solid { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }
        .packing-card .badge-soft.assorted { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
        .packing-card .badge-soft.mixed { background: linear-gradient(90deg, #f97316, #64748b, #8b5cf6); color: #f1f5f9; border-color: #fde68a; }
        .packing-card .badge-status { font-size: 10.5px; font-weight: 700; padding: 3px 8px; border-radius: 999px; }
        .packing-card .badge-status.planned { background: #f1f5f9; color: #64748b; }
        .packing-card .badge-status.packing { background: #0b89d2; color: #ffff; }
        .packing-card .badge-status.complete { background: #8bc63f; color: #ffff; }
        .packing-card .badge-status.sealed { background: #321414; color: #e2e8f0; }
        .packing-card .icon-btn { color: #94a3b8; cursor: pointer; font-size: 13px; padding: 2px 4px; }
        .packing-card .icon-btn:hover { color: #1e293b; }
        .packing-card .progress-main { height: 6px; background: #eef0f2; border-radius: 3px; overflow: hidden; }
        .packing-card .progress-main .bar { display: block; height: 100%; border-radius: 3px; }
        .packing-card .subline { font-size: 12.5px; color: #334155; }
        .packing-card .size-row { display: flex; align-items: center; gap: 8px; font-size: 12.5px; padding: 3px 0; }
        .packing-card .size-row .dot { width: 8px; height: 8px; border-radius: 50%; background: #0f172a; flex-shrink: 0; }
        .packing-card .size-row .name { flex: 0 0 auto; min-width: 110px; color: #334155; }
        .packing-card .size-row .mini-progress { flex: 1; height: 5px; background: #eef0f2; border-radius: 3px; overflow: hidden; }
        .packing-card .size-row .mini-progress .bar { height: 100%; border-radius: 3px; }
        .packing-card .size-row .frac { flex: 0 0 44px; text-align: right; color: #64748b; }
        .packing-card .card-actions { display: flex; gap: 8px; margin-top: auto; flex-shrink: 0; }
        .packing-card .card-actions .btn { flex: 1; font-size: 12px; padding: 6px 8px; }
        .sticky-order-bar { display: none; position: fixed; left: 0; right: 0; z-index: 1030; background: #359DD9; color: #fff; transition: top 0.15s ease; }
        .sticky-order-inner { height: 44px; display: flex; justify-content: space-between; align-items: center; padding: 0 16px; }
        .sticky-action { cursor: pointer; margin-left: 10px; font-weight: 500; opacity: .9; }
        .sticky-action:hover { opacity: 1; }
        #packingCardsGrid.list-mode .packing-card { height: auto; min-height: 0; }
        #packingCardsGrid.list-mode .packing-card-sizes { max-height: 160px; }
        #viewToggleGlobal .btn.active { background-color: #1e293b; border-color: #1e293b; color: #fff; }
        .status-chip { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 999px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all .15s ease; }
        .status-chip:hover { border-color: #cbd5e1; background: #f8fafc; }
        .status-chip.active { background: #0f172a; border-color: #0f172a; color: #fff; }
        .status-chip.active[data-status="complete"] { background: #8bc63f; border-color: #8bc63f; }
        .status-chip.active[data-status="inspect"] { background: #f59e0b; border-color: #f59e0b; }
        .status-chip.active[data-status="shipped"] { background: #2563eb; border-color: #2563eb; }
        .status-chip .chip-count { background: rgba(0, 0, 0, .08); border-radius: 999px; padding: 1px 7px; font-size: 11px; }
        .status-chip.active .chip-count { background: rgba(255, 255, 255, .2); }
        #packingListTable .packing-list-row { cursor: pointer; transition: background-color .1s ease; }
        #packingListTable .packing-list-row:hover td { background-color: #f8fafc; }
        #packingListTable .packing-list-row.selected td { background-color: #f0f9ff; border-color: #bae6fd; }
        #packingListTable td { vertical-align: middle; }
        #packingListTable .icon-btn { color: #94a3b8; cursor: pointer; font-size: 13px; padding: 2px 4px; }
        #packingListTable .icon-btn:hover { color: #1e293b; }
        .action-btn { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9; transition: 0.2s; }
        .action-btn:hover { background: #e2e8f0; transform: scale(1.05); }
        .action-btn.action-btn-pdf { background-color: #fee2e2 !important; color: #b91c1c !important; }
        .action-btn.action-btn-pdf:hover { background-color: #fecaca !important; color: #7f1d1d !important; }
        .card-barcode { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 14px; color: #475569; background: #f8fafc; border: 1px dashed #e2e8f0; border-radius: 6px; padding: 4px 8px; margin-bottom: 8px; flex-shrink: 0; }
        .card-barcode .barcode-text { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1 1 auto; min-width: 0; }
        .ship-stamp { display: inline-flex; flex-direction: column; align-items: center; justify-content: center; width: 78px; height: 78px; border-radius: 50%; border: 3px solid; background: #fff; flex-shrink: 0; text-align: center; line-height: 1.15; font-family: 'Arial Narrow', 'Oswald', 'Segoe UI Condensed', Arial, sans-serif; transform: rotate(-14deg); }
        .packing-card .ship-stamp { position: absolute; right: 8px; bottom: 8px; z-index: 20; box-shadow: 0 3px 8px rgba(0, 0, 0, .18); }
        .ship-stamp-text { font-size: 12.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .8px; }
        .ship-stamp-date { font-size: 8.5px; font-weight: 600; opacity: .85; margin-top: 1px; font-family: Arial, sans-serif; }
        .ship-stamp-shipped { border-color: #2563eb; color: #2563eb; }
        .ship-stamp-inspect { border-color: #f59e0b; color: #f59e0b; }
        .ship-stamp.ship-stamp-sm { width: 42px; height: 42px; transform: rotate(-10deg); }
        .ship-stamp.ship-stamp-sm .ship-stamp-text { font-size: 8.5px; }
        .ship-stamp.ship-stamp-sm .ship-stamp-date { display: none; }
        .scan-nobar-wrap { display: flex; justify-content: center; }
        .scan-nobar-card { width: 100%; max-width: 480px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 3px rgba(15, 23, 42, .05); padding: 18px 20px; }
        .scan-nobar-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .scan-nobar-icon-wrap { flex-shrink: 0; width: 40px; height: 40px; border-radius: 10px; background: #e0f2fe; display: flex; align-items: center; justify-content: center; transition: background .15s ease; }
        .scan-nobar-icon { font-size: 17px; color: #0369a1; transition: color .15s ease; }
        .scan-nobar-icon-wrap.is-processing { background: #fef3c7; }
        .scan-nobar-icon-wrap.is-processing .scan-nobar-icon { color: #d97706; animation: scanIconSpin 0.9s linear infinite; }
        .scan-nobar-icon-wrap.is-success { background: #dcfce7; }
        .scan-nobar-icon-wrap.is-success .scan-nobar-icon { color: #16a34a; }
        .scan-nobar-icon-wrap.is-error { background: #fee2e2; }
        .scan-nobar-icon-wrap.is-error .scan-nobar-icon { color: #dc2626; }
        @keyframes scanIconSpin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .scan-nobar-title { font-weight: 700; font-size: 14.5px; color: #0f172a; }
        .scan-nobar-subtitle { font-size: 11.5px; color: #94a3b8; }
        .scan-nobar-input-wrap { position: relative; margin-bottom: 10px; }
        .scan-nobar-input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none; }
        .scan-nobar-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; padding: 9px 12px 9px 34px; color: #0f172a; transition: border-color .15s ease, box-shadow .15s ease; }
        .scan-nobar-input:focus { outline: none; border-color: #0369a1; box-shadow: 0 0 0 3px rgba(3, 105, 161, .12); }
        .scan-nobar-feedback { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #94a3b8; }
        .scan-nobar-feedback.is-processing { color: #d97706; }
        .scan-nobar-feedback.is-success { color: #16a34a; }
        .scan-nobar-feedback.is-error { color: #dc2626; }
        /* .session-plan-wrap { margin-bottom: 20px; } */
        
        .session-plan-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .session-plan-header-title { font-weight: 700; font-size: 14.5px; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .session-plan-header-title .bar-accent { width: 4px; height: 16px; background: #64748b; border-radius: 2px; display: inline-block; }
        .session-active-badge { font-size: 11.5px; font-weight: 600; color: #0369a1; background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 999px; padding: 4px 12px; display: none; align-items: center; gap: 6px; }
        .session-active-badge.show { display: inline-flex; }
        .session-cards-row { display: flex; gap: 12px; flex-wrap: wrap; }
        .session-card { width: 220px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.05); transition: border-color .15s ease, box-shadow .15s ease; }
        .session-card.is-active { border-color: #359DD9; box-shadow: 0 0 0 2px rgba(53,157,217,.18); }
        .session-card.is-done { border-color: #bbf7d0; background: #f0fdf4; }
        .session-card-top { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .session-card-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; background: #f1f5f9; color: #64748b; }
        .session-card.is-active .session-card-icon { background: #e0f2fe; color: #0369a1; }
        .session-card.is-done .session-card-icon { background: #dcfce7; color: #16a34a; }
        .session-card-title { font-weight: 700; font-size: 13.5px; color: #0f172a; line-height: 1.2; }
        .session-card-status { font-size: 10.5px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
        .session-progress-bar { height: 6px; background: #eef0f2; border-radius: 3px; overflow: hidden; margin-bottom: 6px; }
        .session-progress-bar .bar { display: block; height: 100%; transition: width .2s ease; }
        .session-count { font-size: 12px; color: #475569; margin-bottom: 12px; }
        .session-count strong { color: #0f172a; }
        .session-actions { display: flex; gap: 6px; }
        .session-actions .btn { flex: 1; font-size: 11.5px; padding: 6px 8px; }
        .session-empty-hint { font-size: 12.5px; color: #94a3b8; padding: 12px 0; }
    </style>
@endsection

@section('content')
    <div id="stickTopBarGlobal" class="sticky-order-bar">
        <div class="sticky-order-inner">
            <div><strong><span id="selectedCountGlobal">0</span> carton terpilih</strong></div>
            <div>
                @php $guserpk = Session::get('guserpk'); @endphp
                @if (in_array($guserpk, [35]))
                    <span class="sticky-action d-none" id="btnProsesInspectGlobal" onclick="bulkProsesInspectGlobal()">Proses Inspect</span>
                    <span class="sticky-action d-none" id="btnProsesShipmentGlobal" onclick="bulkProsesShipmentGlobal()">Proses Shipment</span>
                @endif
                @if (in_array($guserpk, [38]))
                    <span class="sticky-action d-none" id="btnBukaSegelGlobal" onclick="bulkBukaSegelGlobal()">Buka Segel</span>
                    <span class="sticky-action" id="btnBulkSegelCtnGlobal" onclick="bulkSegelCtnGlobal()">Segel CTN</span>
                @endif
                <span class="sticky-action" onclick="closeMenuGlobal()">Close</span>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4 px-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-3 mb-4 gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:void(0)" onclick="goBack()" class="btn btn-icon-custom d-inline-flex align-items-center justify-content-center shadow-sm border bg-white text-secondary rounded-circle" style="width: 38px; height: 38px; transition: all 0.2s ease;" title="Kembali ke Daftar Data OP">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold text-dark mb-0" style="font-size: 1.15rem; letter-spacing: -0.3px;">Packing list - Style {{ $dt2->style ?? '-' }}</h4>
                    <span>OP {{ $op }} &middot; Season {{ $dt2->season ?? '-' }} &middot; Buyer: {{ $dt2->buyer ?? '-' }} &middot; PO {{ $po }}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('laporan.pdf.global', ['po' => $po, 'op' => $op, 'poref' => $poref ?? null, 'mif' => $mif]) }}" target="_blank" class="action-btn action-btn-pdf" title="Print PDF">
                    <img src="{{ asset('/public/css/images/pdf.jpg') }}" width="18" height="18">
                </a>
            </div>
        </div>

        <div id="headerInfoWrapperGlobal">
            @include('menu.finishgood-stuffing.partials.header_info_global', ['dt2' => $dt2, 'poNoList' => $poNoList, 'colorList' => $colorList, 'allPopks' => $allPopks])
        </div>

        <div id="cardsInfoWrapperGlobal">
            @include('menu.finishgood-stuffing.partials.cards_info_global', [
                'totalPcs' => array_sum($orderQty ?? []), 'plannedPcs' => array_sum($planQty ?? []),
                'packedPcs' => array_sum($readyQty ?? []), 'shortPcs' => max(0, array_sum($orderQty ?? []) - array_sum($planQty ?? [])),
                'totalColors' => $totalColors ?? 0, 'totalSizes' => count($activeSizes ?? []),
                'totalCarton' => $totalCarton ?? 0, 'sealedCarton' => $sealedCarton ?? 0, 'openCarton' => $openCarton ?? 0,
            ])
        </div>

        <div id="breakdownSummaryWrapper">
            @include('menu.finishgood-stuffing.partials.breakdown_summary_global')
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3 p-md-4">
        <div class="session-plan-header">
            <div class="session-plan-header-title">
                <span class="bar-accent"></span>
                <span id="sessionPlanTitle">Shipment Session</span>
            </div>
            <div class="session-active-badge" id="activeSessionBadge">
                <i class="fas fa-circle-play"></i>
                <span id="activeSessionBadgeText"></span>
            </div>
        </div>
        <div id="shipmentPlanCards" class="session-cards-row"></div>
    </div>
</div>
 
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
            <input type="text" id="scanNobarInputGlobal" class="scan-nobar-input" placeholder="Scan barcode / ketik nobar lalu Enter..." autocomplete="off">
        </div>
        <div class="scan-nobar-feedback" id="scanNobarFeedbackGlobal">
            <i class="fas fa-circle-info"></i><span>Siap menerima scan.</span>
        </div>
    </div>
</div>

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="rounded me-2" style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
                    <strong class="text-dark"> Detail Packing / Carton </strong>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="px-3 py-2 border-bottom bg-white">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="input-group" style="width:260px;">
                                <span class="input-group-text search">
                                    <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18" alt="Search">
                                </span>
                                <input type="text" class="form-control search" id="searchPackingGlobal" placeholder="Search Barcode / No CTN">
                            </div>
                            <input id="filterSizeGlobal" style="width:110px;">
                            <input id="filterColorGlobal" style="width:150px;">
                            <input id="filterSecszGlobal" style="width:150px;">
                            <input id="sortFieldGlobal" style="width:150px;">
                            <input id="filterPartGlobal" style="width:150px;">
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="btn-group btn-group-sm" role="group" id="viewToggleGlobal">
                                <button type="button" class="btn btn-outline-secondary active" id="viewModeGridBtn" onclick="setViewModeGlobal('grid')" title="Tampilan Grid"><i class="fas fa-th-large"></i></button>
                                <button type="button" class="btn btn-outline-secondary" id="viewModeListBtn" onclick="setViewModeGlobal('list')" title="Tampilan List"><i class="fas fa-list"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-3" id="statusFilterChipsGlobal">
                        <span class="status-chip active" data-status="" onclick="setStatusFilterGlobal('')">Semua <span class="chip-count" id="chipCountAll">0</span></span>
                        <span class="status-chip" data-status="complete" onclick="setStatusFilterGlobal('complete')">Complete <span class="chip-count" id="chipCountComplete">0</span></span>
                        <span class="status-chip" data-status="sealed" onclick="setStatusFilterGlobal('sealed')">Sealed <span class="chip-count" id="chipCountSealed">0</span></span>
                        <span class="status-chip" data-status="inspect" onclick="setStatusFilterGlobal('inspect')">Inspect <span class="chip-count" id="chipCountInspect">0</span></span>
                        <span class="status-chip" data-status="shipped" onclick="setStatusFilterGlobal('shipped')">Shipped <span class="chip-count" id="chipCountShipped">0</span></span>
                        <div class="ms-auto d-flex align-items-center gap-2">
                            <span class="text-secondary" style="font-size:12px;">Tampilkan</span>
                            <input id="pageSizeGlobal" style="width:90px;">
                        </div>
                    </div>

                    <div id="packingCardsGrid" class="row g-3"></div>

                    <div id="packingListTableWrapper" class="table-responsive d-none">
                        <table class="table table-sm table-hover align-middle mb-0" id="packingListTable">
                            <thead class="table-light text-secondary" style="font-size:11px; text-transform:uppercase; letter-spacing:0.3px;">
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
                            <button class="btn btn-sm btn-outline-secondary" id="btnPackingCardsPrev" onclick="packingCardsGoPage(-1)"><i class="fas fa-chevron-left"></i></button>
                            <span style="font-size:12.5px;" id="packingCardsPageLabel"></span>
                            <button class="btn btn-sm btn-outline-secondary" id="btnPackingCardsNext" onclick="packingCardsGoPage(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('menu.finishgood-stuffing.modal-segel-ctn-global')
    @include('menu.finishgood-stuffing.modal-shipment-ctn-global')
@endsection

@section('js_custom')
<script>
    window.pgCombos = @json($colorSecszCombos ?? []);
    window.pgSizes = @json($activeSizes);
    window.canManageSegel = @json(in_array(Session::get('guserpk'), [38]));
    window.isSupervisorStuffing = @json(in_array(Session::get('guserpk'), [34]));
    const SESSION_LABEL = 'Session';
</script>

<script>
    function reloadBreakdownSummary() {
        $.get("{{ route('finish-good-stuffing.breakdownSummaryGlobal') }}", { po: @json($po), op: @json($op), poref: @json($poref ?? null), mif: @json($mif) }, function(html) {
            $('#breakdownSummaryWrapper').html(html);
            matrixTab = 'planning';
            $('.legend-item').removeClass('active');
            classifyMatrixCells();
        });
    }
    function refreshPgCombos() {
        return $.get("{{ route('finish-good-stuffing.combosGlobal') }}", { po: @json($po), op: @json($op), poref: @json($poref ?? null), mif: @json($mif) }, function(data) { window.pgCombos = data || []; });
    }
    function reloadCardsInfoGlobal() {
        $.get("{{ route('finish-good-stuffing.cardsInfoGlobal') }}", { po: @json($po), op: @json($op), poref: @json($poref ?? null), mif: @json($mif) }, function(html) { $('#cardsInfoWrapperGlobal').html(html); });
    }
    function reloadHeaderInfoGlobal() {
        return $.get("{{ route('finish-good-stuffing.headerInfoGlobal') }}", { po: @json($po), op: @json($op), poref: @json($poref ?? null), mif: @json($mif) }, function(html) { $('#headerInfoWrapperGlobal').html(html); });
    }
    function classifyMatrixCells() {
        document.querySelectorAll('#matrixTable td.matrix-cell').forEach(function(td) {
            const order = Number(td.dataset.order || 0), trans = Number(td.dataset.trans || 0);
            const plan = Number(td.dataset.plan || 0), actual = Number(td.dataset.actual || 0);

            td.classList.remove('cov-exact', 'cov-short', 'cov-over', 'cov-none', 'cov-blank');
            let covColor = '#cbd5e1';
            if (order === 0 && trans === 0) td.classList.add('cov-blank');
            else if (trans === 0 && order > 0) { td.classList.add('cov-none'); covColor = '#94a3b8'; }
            else if (trans < order) { td.classList.add('cov-short'); covColor = '#dc2626'; }
            else if (trans > order) { td.classList.add('cov-over'); covColor = '#2563eb'; }
            else { td.classList.add('cov-exact'); covColor = '#16a34a'; }

            td.classList.remove('pack-full', 'pack-progress', 'pack-empty', 'pack-blank');
            let packColor = '#cbd5e1';
            if (plan === 0) td.classList.add('pack-blank');
            else if (actual >= plan) { td.classList.add('pack-full'); packColor = '#16a34a'; }
            else if (actual > 0) { td.classList.add('pack-progress'); packColor = '#2563eb'; }
            else { td.classList.add('pack-empty'); packColor = '#94a3b8'; }

            td.classList.remove('plan-exact', 'plan-short', 'plan-over', 'plan-none', 'plan-blank');
            let planColor = '#cbd5e1';
            if (order === 0 && plan === 0) td.classList.add('plan-blank');
            else if (plan === 0 && order > 0) { td.classList.add('plan-none'); planColor = '#94a3b8'; }
            else if (plan < order) { td.classList.add('plan-short'); planColor = '#dc2626'; }
            else if (plan > order) { td.classList.add('plan-over'); planColor = '#2563eb'; }
            else { td.classList.add('plan-exact'); planColor = '#16a34a'; }

            const covPct = order > 0 ? Math.min(100, (trans / order) * 100) : (trans > 0 ? 100 : 0);
            const packPct = plan > 0 ? Math.min(100, (actual / plan) * 100) : 0;
            const planPct = order > 0 ? Math.min(100, (plan / order) * 100) : (plan > 0 ? 100 : 0);

            const covFill = td.querySelector('.cov .matrix-bar-fill');
            if (covFill) { covFill.style.width = covPct + '%'; covFill.style.background = covColor; }
            const packFill = td.querySelector('.pack .matrix-bar-fill');
            if (packFill) { packFill.style.width = packPct + '%'; packFill.style.background = packColor; }
            const planFill = td.querySelector('.planning .matrix-bar-fill');
            if (planFill) { planFill.style.width = planPct + '%'; planFill.style.background = planColor; }
        });
        document.querySelectorAll('.matrix-color-dot').forEach(function(dot) {
            const name = dot.dataset.name || '';
            let hash = 0;
            for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
            dot.style.background = `hsl(${Math.abs(hash) % 360}, 45%, 45%)`;
        });
    }
    let matrixTab = 'planning';
    function setMatrixTab(tab) {
        matrixTab = tab;
        $('.matrix-tab-toggle button').removeClass('active');
        $({ coverage: '#matrixTabCoverage', planning: '#matrixTabPlanning', packing: '#matrixTabPacking' }[tab]).addClass('active');
        $('#matrixLegendCoverage').toggleClass('d-none', tab !== 'coverage');
        $('#matrixLegendPlanning').toggleClass('d-none', tab !== 'planning');
        $('#matrixLegendPacking').toggleClass('d-none', tab !== 'packing');
        $('#matrixTable .cov').toggleClass('d-none', tab !== 'coverage');
        $('#matrixTable .planning').toggleClass('d-none', tab !== 'planning');
        $('#matrixTable .pack').toggleClass('d-none', tab !== 'packing');
        $('#matrixSubtitle').text({
            coverage: 'Polibag vs Order per Color & Sec Size — klik sel untuk filter carton',
            planning: 'Planning vs Order per Color & Sec Size — klik sel untuk filter carton',
            packing: 'Actual vs Plan per Color & Sec Size — klik sel untuk filter carton'
        }[tab]);
        $('.legend-item').removeClass('active');
        $('#matrixTable td.matrix-cell').removeClass('matrix-dim');
    }
    function toggleMatrixChip(el) {
        const $el = $(el), wasActive = $el.hasClass('active');
        $('.legend-item').removeClass('active');
        $('#matrixTable td.matrix-cell').removeClass('matrix-dim');
        if (wasActive) return;
        $el.addClass('active');
        const targetClass = {
            exact: 'cov-exact', short: 'cov-short', over: 'cov-over', none: 'cov-none',
            full: 'pack-full', progress: 'pack-progress', empty: 'pack-empty',
            'plan-exact': 'plan-exact', 'plan-short': 'plan-short', 'plan-over': 'plan-over', 'plan-none': 'plan-none'
        }[$el.data('cat')];
        $('#matrixTable td.matrix-cell').each(function() { if (!$(this).hasClass(targetClass)) $(this).addClass('matrix-dim'); });
    }
    function onMatrixCellClick(td) {
        const $td = $(td);
        if ($('#filterColorGlobal').data('combobox')) $('#filterColorGlobal').combobox('setValue', $td.data('material') || '');
        if ($('#filterSecszGlobal').data('combobox')) $('#filterSecszGlobal').combobox('setValue', $td.data('secsz') || '');
        if ($('#filterSizeGlobal').data('combobox')) $('#filterSizeGlobal').combobox('setValue', String($td.data('size')));
        document.getElementById('packingCardsGrid')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
</script>

<script>
    window.activeSizesGlobal = @json($activeSizes);
    window.colorListGlobal = @json($colorList ?? []);
    window.secszListGlobal = @json($secszList ?? []);
    window.packingViewModeGlobal = localStorage.getItem('packingViewModeGlobal') || 'grid';

    let packingCardsPage = 1, packingCardsRows = 25, packingCardsTotal = 0, packingCardsTotalCarton = 0, statusFilterGlobal = '';
    window.selectedRowsCache = window.selectedRowsCache || {};

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
        loadShipmentPlanCards();
        renderActiveSessionBadge();
        syncStickyBarGlobalPosition();
    });

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
                { value: '', text: 'Urutan Default' }, { value: 'carton_asc', text: 'No Carton (A-Z)' },
                { value: 'carton_desc', text: 'No Carton (Z-A)' }, { value: 'nobar_asc', text: 'Barcode (A-Z)' },
                { value: 'nobar_desc', text: 'Barcode (Z-A)' },
            ],
            valueField: 'value', textField: 'text', value: '', editable: false, panelHeight: 'auto', onChange: reloadPackingGlobal
        });
    }
    function initPageSizeGlobalCombobox() {
        $('#pageSizeGlobal').combobox({
            data: [{ value: 25, text: '25' }, { value: 50, text: '50' }, { value: 100, text: '100' }, { value: 200, text: '200' }],
            valueField: 'value', textField: 'text', value: 25, editable: false, panelHeight: 'auto',
            onChange: function(v) { packingCardsRows = parseInt(v) || 25; reloadPackingGlobal(); }
        });
    }
    function setStatusFilterGlobal(status) {
        statusFilterGlobal = status;
        $('#statusFilterChipsGlobal .status-chip').removeClass('active');
        $(`#statusFilterChipsGlobal .status-chip[data-status="${status}"]`).addClass('active');
        reloadPackingGlobal();
    }
    function loadPackingCards() {
        $.get("{{ route('finish-good-stuffing.list.detail.global') }}", {
            po: @json($po), op: @json($op), poref: @json($poref ?? null), mif: @json($mif),
            search: $('#searchPackingGlobal').val(),
            size: $('#filterSizeGlobal').combobox('getValue'),
            color: $('#filterColorGlobal').combobox('getValue'),
            secsz: $('#filterSecszGlobal').combobox('getValue'),
            part: $('#filterPartGlobal').combobox('getValue'),
            sort: $('#sortFieldGlobal').combobox('getValue'),
            status: statusFilterGlobal, page: packingCardsPage, rows: packingCardsRows
        }, function(data) {
            packingCardsTotal = data.total || 0;
            packingCardsTotalCarton = data.total_carton || 0;
            const counts = data.status_counts || {};
            $('#chipCountAll').text(counts.all ?? 0);
            $('#chipCountComplete').text(counts.complete ?? 0);
            $('#chipCountSealed').text(counts.sealed ?? 0);
            $('#chipCountInspect').text(counts.inspect ?? 0);
            $('#chipCountShipped').text(counts.shipped ?? 0);
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
        const grid = $('#packingCardsGrid'), listWrapper = $('#packingListTableWrapper'), listBody = $('#packingListBody'), empty = $('#packingCardsEmpty');
        grid.empty(); listBody.empty();

        if (!rows.length) {
            empty.removeClass('d-none'); grid.addClass('d-none'); listWrapper.addClass('d-none');
            $('#packingCardsInfo').text('0 carton'); $('#packingCardsPageLabel').text('Halaman 1 / 1');
            updateSelectionGlobal();
            return;
        }
        empty.addClass('d-none');

        const cartonGroups = {}, cartonOrder = [];
        rows.forEach(function(row) {
            const key = row.carton ?? '(tanpa carton)';
            if (!cartonGroups[key]) { cartonGroups[key] = []; cartonOrder.push(key); }
            cartonGroups[key].push(row);
        });

        if (window.packingViewModeGlobal === 'list') {
            grid.addClass('d-none'); listWrapper.removeClass('d-none');
            cartonOrder.forEach(function(k) { listBody.append(buildPackingListRow(cartonGroups[k])); });
        } else {
            listWrapper.addClass('d-none'); grid.removeClass('d-none');
            cartonOrder.forEach(function(k) { grid.append(buildPackingCard(cartonGroups[k])); });
        }

        const persisted = window.selectedPackpksGlobal || [];
        $('.packing-select-item').each(function() {
            const arr = String($(this).data('packpks') || '').split(',').map(Number).filter(Boolean);
            $(this).toggleClass('selected', arr.some(pk => persisted.includes(pk)));
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
        if (statuses.some(s => s === 'sealed')) return { key: 'sealed', label: 'Sealed' };
        if (statuses.every(s => s === 'complete')) return { key: 'complete', label: 'Complete' };
        if (statuses.some(s => s === 'packing' || s === 'complete')) return { key: 'packing', label: 'Packing' };
        return { key: 'planned', label: 'Planned' };
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
        return marker ? `${row.material ?? '-'} ${marker}` : (row.material ?? '-');
    }
    function getShipStampForGroup(groupRows) {
        if (groupRows.some(r => r.ship_shipped === true)) return 'shipped';
        if (groupRows.some(r => r.ship_inspect === true)) return 'inspect';
        return null;
    }
    function formatStampDate(value) {
        if (!value) return '';
        const parts = String(value).split(' ')[0].split('T')[0].split('-');
        if (parts.length !== 3) return '';
        const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        const mi = parseInt(parts[1], 10) - 1;
        if (mi < 0 || mi > 11) return '';
        return `${parseInt(parts[2],10)} ${bulan[mi]} ${parts[0]}`;
    }
    function buildShipStamp(stampKey, dateStr, size) {
        if (!stampKey) return '';
        const sizeClass = (size === 'sm') ? ' ship-stamp-sm' : '';
        if (stampKey === 'shipped') {
            const tgl = formatStampDate(dateStr);
            return `<span class="ship-stamp ship-stamp-shipped${sizeClass}" title="Sudah Shipped"><span class="ship-stamp-text">Shipped</span>${tgl ? `<span class="ship-stamp-date">${tgl}</span>` : ''}</span>`;
        }
        if (stampKey === 'inspect') return `<span class="ship-stamp ship-stamp-inspect${sizeClass}" title="Sedang Inspect"><span class="ship-stamp-text">Inspect</span></span>`;
        return '';
    }

    function computePackingGroupData(groupRows) {
        const activeIdx = Object.keys(window.activeSizesGlobal || {});
        const first = groupRows[0];
        const uniqueCombos = new Set(groupRows.map(r => `${r.material ?? '-'}||${r.secsz ?? ''}`));
        const compositionLabel = uniqueCombos.size > 1 ? 'Mixed' :
            (groupRows.some(r => activeIdx.filter(i => Number(r[`qtyp${i}`] || 0) > 0).length > 1) ? 'Assorted' : 'Solid');
        const status = getGroupStatus(groupRows);

        const partValue = groupRows.map(r => r.part).find(p => p !== null && p !== undefined && p !== '' && p !== 0);
        const partBadgeHtml = partValue ? `<span class="badge-soft" style="background:#ede9fe;color:#6d28d9;border-color:#ddd6fe;">${String(partValue) === '10' ? 'Part 10 &middot; Complete' : 'Part ' + partValue}</span>` : '';

        const shipStampKey = getShipStampForGroup(groupRows);
        const shippedRow = groupRows.find(r => r.ship_shipped === true);
        const shipDate = shippedRow?.ship_date ?? null;
        const shipStampHtml = buildShipStamp(shipStampKey, shipDate, 'lg');

        let totalPlan = 0, totalActual = 0, sizeRows = '';
        groupRows.forEach(function(row) {
            const materialLabel = getComboLabel(row);
            activeIdx.filter(i => Number(row[`qtyp${i}`] || 0) > 0).forEach(function(i) {
                const plan = Number(row[`qtyp${i}`] || 0), actual = Number(row[`qty${i}`] || 0);
                totalPlan += plan; totalActual += actual;
                const sizePct = plan > 0 ? Math.round((actual / plan) * 100) : 0;
                const miniColor = sizePct >= 100 ? '#8bc63f' : '#0b89d2';
                const secszTag = row.secsz ? ` (${row.secsz})` : '';
                sizeRows += `<div class="size-row"><span class="dot"></span><span class="name">${materialLabel}${secszTag} &middot; ${window.activeSizesGlobal[i] ?? i}</span><span class="mini-progress"><span class="bar" style="width:${Math.min(100, sizePct)}%; background:${miniColor};"></span></span><span class="frac">${actual}/${plan}</span></div>`;
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

        const anyInspecting = groupRows.some(r => r.ship_inspect === true);
        const canSeal = status.key === 'complete' && !anyInspecting;
        const isSealed = status.key === 'sealed';
        const packpksAttr = groupRows.map(r => r.packpk).join(',');
        const allSegel = groupRows.every(r => Number(r.segel) === 1);
        const hasPart = groupRows.some(r => r.part !== null && r.part !== undefined && r.part !== '' && r.part !== 0);

        return { first, uniqueCombos, compositionLabel, compositionClass: compositionLabel.toLowerCase(), status,
            totalPlan, totalActual, sizeRows, pct, barColor, subline, canSeal, isSealed, packpksAttr,
            allSegel, hasPart, partBadgeHtml, shipStampHtml, shipStampKey, shipDate, anyInspecting };
    }

    function buildPackingCard(groupRows) {
        const d = computePackingGroupData(groupRows);
        const ribbonHtml = d.allSegel ? '<div class="ribbon-segel">SEGEL</div>' : '';
        const editButtonHtml = (d.isSealed || d.anyInspecting) ? '' :
            `<i class="fas fa-pen icon-btn" title="Edit" onclick="event.stopPropagation(); editCartonGlobal('${d.packpksAttr}')"></i>`;

        let actionButtonHtml;
        if (!window.canManageSegel) {
            actionButtonHtml = d.isSealed
                ? `<button class="btn btn-outline-secondary" disabled title="Anda tidak memiliki akses untuk Segel/Buka Segel"><i class="fas fa-lock me-1"></i>Sealed</button>`
                : `<button class="btn btn-outline-secondary" disabled title="Anda tidak memiliki akses untuk Segel/Buka Segel"><i class="fas fa-ban me-1"></i>Seal</button>`;
        } else if (d.isSealed && d.hasPart) {
            actionButtonHtml = `<button class="btn btn-outline-secondary" disabled title="Sudah masuk proses shipment, tidak dapat dibuka Segel-nya lagi"><i class="fas fa-lock me-1"></i>Sealed</button>`;
        } else if (d.isSealed) {
            actionButtonHtml = `<button class="btn btn-outline-secondary" onclick="event.stopPropagation(); unsealCarton('${d.packpksAttr}')"><i class="fas fa-unlock me-1"></i>Unseal</button>`;
        } else {
            const sealTitle = d.anyInspecting ? 'title="Sedang proses Inspect, tidak dapat disegel dulu"' : '';
            actionButtonHtml = `<button class="btn ${d.canSeal ? 'btn-dark' : 'btn-outline-secondary'}" ${d.canSeal ? '' : 'disabled'} ${sealTitle} onclick="event.stopPropagation(); sealCarton('${d.packpksAttr}')">Seal</button>`;
        }

        return `<div class="col-12 col-md-6 col-xl-4">
            <div class="packing-select-item packing-card" data-packpks="${d.packpksAttr}" data-sealed="${d.isSealed ? 1 : 0}" data-haspart="${d.hasPart ? 1 : 0}" onclick="onPackingItemClick(event, this)">
                ${ribbonHtml}
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="ctn-code">${d.first.carton ?? '-'}</span>
                    <span class="badge-soft ${d.compositionClass}">${d.compositionLabel}</span>
                    <span class="badge-status ${d.status.key}">${d.status.label}</span>
                    ${d.partBadgeHtml}
                </div>
                <div class="subline mb-1">${d.subline}</div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="progress-main flex-grow-1"><span class="bar" style="width:${Math.min(100, d.pct)}%; background:${d.barColor};"></span></div>
                    <div class="text-nowrap" style="font-size:12.5px;"><strong>${d.totalActual}</strong> / ${d.totalPlan} pcs <span class="text-muted">${d.pct}%</span></div>
                </div>
                <div class="packing-card-sizes">${d.sizeRows}</div>
                <div class="card-barcode">
                    <span class="barcode-text"><i class="fas fa-barcode me-1"></i>${d.first.nobar ? d.first.nobar : '<span class="text-muted">Belum ada barcode</span>'}</span>
                    ${d.shipStampHtml}
                </div>
                <div class="card-actions">${actionButtonHtml}</div>
            </div>
        </div>`;
    }

    function buildPackingListRow(groupRows) {
        const d = computePackingGroupData(groupRows);
        const editButtonHtml = (d.isSealed || d.anyInspecting) ? '<span class="text-muted small">-</span>' :
            `<i class="fas fa-pen icon-btn" title="Edit" onclick="event.stopPropagation(); editCartonGlobal('${d.packpksAttr}')"></i>`;

        let actionButtonHtml;
        if (!window.canManageSegel) {
            actionButtonHtml = d.isSealed
                ? `<button class="btn btn-outline-secondary btn-sm" disabled title="Anda tidak memiliki akses untuk Segel/Buka Segel"><i class="fas fa-lock"></i></button>`
                : `<button class="btn btn-outline-secondary btn-sm" disabled title="Anda tidak memiliki akses untuk Segel/Buka Segel"><i class="fas fa-ban"></i></button>`;
        } else if (d.isSealed && d.hasPart) {
            actionButtonHtml = `<button class="btn btn-outline-secondary btn-sm" disabled title="Sudah masuk proses shipment, tidak dapat dibuka Segel-nya lagi"><i class="fas fa-lock"></i></button>`;
        } else if (d.isSealed) {
            actionButtonHtml = `<button class="btn btn-outline-secondary btn-sm" onclick="event.stopPropagation(); unsealCarton('${d.packpksAttr}')"><i class="fas fa-unlock"></i></button>`;
        } else {
            const sealTitle = d.anyInspecting ? 'title="Sedang proses Inspect, tidak dapat disegel dulu"' : '';
            actionButtonHtml = `<button class="btn ${d.canSeal ? 'btn-dark' : 'btn-outline-secondary'} btn-sm" ${d.canSeal ? '' : 'disabled'} ${sealTitle} onclick="event.stopPropagation(); sealCarton('${d.packpksAttr}')"><i class="fas fa-lock"></i></button>`;
        }

        const segelIcon = d.allSegel ? '<i class="fas fa-lock text-danger ms-1" title="Sudah Segel"></i>' : '';

        return `<tr class="packing-select-item packing-list-row" data-packpks="${d.packpksAttr}" data-sealed="${d.isSealed ? 1 : 0}" data-haspart="${d.hasPart ? 1 : 0}" onclick="onPackingItemClick(event, this)">
            <td class="text-start"><strong>${d.first.carton ?? '-'}</strong>${segelIcon}<div><span class="badge-soft ${d.compositionClass}" style="font-size:10px;">${d.compositionLabel}</span>${d.partBadgeHtml}</div></td>
            <td class="text-start" style="font-size:12.5px; color:#475569;"><div class="d-flex align-items-center gap-2"><span>${d.first.nobar ? d.first.nobar : '<span class="text-muted">-</span>'}</span>${buildShipStamp(d.shipStampKey, d.shipDate, 'sm')}</div></td>
            <td class="text-start" style="font-size:12.5px;">${d.subline}</td>
            <td class="text-center"><span class="badge-status ${d.status.key}">${d.status.label}</span></td>
            <td style="min-width:160px;"><div class="d-flex align-items-center gap-2"><div class="progress-main flex-grow-1"><span class="bar" style="width:${Math.min(100, d.pct)}%; background:${d.barColor};"></span></div><div class="text-nowrap" style="font-size:11.5px; min-width:70px;"><strong>${d.totalActual}</strong>/${d.totalPlan} <span class="text-muted">(${d.pct}%)</span></div></div></td>
            <td class="text-center"><div class="d-flex justify-content-center gap-2">${editButtonHtml}${actionButtonHtml}</div></td>
        </tr>`;
    }

    function onPackingItemClick(e, itemEl) {
        if ($(e.target).closest('.icon-btn, .card-actions, button, a').length) return;
        const $item = $(itemEl), wasSelected = $item.hasClass('selected');
        const packpksArr = String($item.data('packpks') || '').split(',').map(Number).filter(Boolean);

        if (!wasSelected) {
            const rowsForThisItem = (window.lastPackingRows || []).filter(r => packpksArr.includes(r.packpk));
            if (rowsForThisItem.some(r => r.ship_shipped === true)) {
                showToast('warning', 'Carton yang sudah Shipment tidak dapat dipilih/diproses lagi.');
                return;
            }
            packpksArr.forEach(function(pk) {
                const row = (window.lastPackingRows || []).find(r => r.packpk === pk);
                if (row) window.selectedRowsCache[pk] = row;
            });
        } else {
            packpksArr.forEach(function(pk) { delete window.selectedRowsCache[pk]; });
        }

        $item.toggleClass('selected');
        updateSelectionGlobal();
    }

    function markCartonPacked(packpksCsv) {
        $.ajax({
            url: "{{ route('finish-good-stuffing.update-ctn') }}", method: 'POST',
            data: { popk: '', size: '', packpk: packpksCsv },
            success: function(res) { showToast(res.icon, res.title); loadPackingCards(); reloadBreakdownSummary(); },
            error: function(xhr) { showToast('error', (xhr.responseJSON || {}).title || 'Terjadi kesalahan.'); }
        });
    }
    function sealCarton(packpksCsv) { openSegelModalGlobal(1, packpksCsv.split(',').map(Number)); }
    function unsealCarton(packpksCsv) { openSegelModalGlobal(0, packpksCsv.split(',').map(Number)); }

    function initFilterColorGlobal() {
        let d = [{ value: '', text: 'Semua Color' }];
        (window.colorListGlobal || []).forEach(c => d.push({ value: c, text: c }));
        $('#filterColorGlobal').combobox({ data: d, valueField: 'value', textField: 'text', value: '', editable: false, panelHeight: 'auto', onChange: reloadPackingGlobal });
    }
    function initFilterSizeGlobalCombobox() {
        let d = [{ value: '', text: 'Semua Size' }];
        Object.keys(window.activeSizesGlobal || {}).forEach(i => d.push({ value: i, text: window.activeSizesGlobal[i] }));
        $('#filterSizeGlobal').combobox({ data: d, valueField: 'value', textField: 'text', value: '', editable: false, panelHeight: 'auto', onChange: reloadPackingGlobal });
    }
    function initFilterPartGlobal() {
        let d = [{ value: '', text: 'Semua Part' }];
        for (let i = 1; i <= 10; i++) d.push({ value: String(i), text: (i === 10) ? 'Part 10 (Complete)' : `Part ${i}` });
        $('#filterPartGlobal').combobox({ data: d, valueField: 'value', textField: 'text', value: '', editable: false, panelHeight: 'auto', onChange: reloadPackingGlobal });
    }
    function initFilterSecszGlobal() {
        let d = [{ value: '', text: 'Semua Sec Size' }];
        (window.secszListGlobal || []).forEach(s => d.push({ value: s, text: s }));
        $('#filterSecszGlobal').combobox({ data: d, valueField: 'value', textField: 'text', value: '', editable: false, panelHeight: 'auto', onChange: reloadPackingGlobal });
    }

    let packingGlobalSearchTimer = null;
    $('#searchPackingGlobal').on('keyup', function() { clearTimeout(packingGlobalSearchTimer); packingGlobalSearchTimer = setTimeout(reloadPackingGlobal, 300); });

    function updateSelectionGlobal() {
        const domSelectedPackpks = [];
        $('.packing-select-item.selected').each(function() {
            String($(this).data('packpks') || '').split(',').forEach(p => { if (p !== '') domSelectedPackpks.push(Number(p)); });
        });
        const stillPersisted = (window.selectedPackpksGlobal || []).filter(pk => window.selectedRowsCache[pk] && !domSelectedPackpks.includes(pk));
        const packpks = [...new Set([...domSelectedPackpks, ...stillPersisted])];
        window.selectedPackpksGlobal = packpks;

        if (packpks.length === 0) { $('#selectedCountGlobal').text('0'); $('#stickTopBarGlobal').hide(); return; }

        const selectedRows = packpks.map(pk => window.selectedRowsCache[pk]).filter(Boolean);

        function abortSelection(message) {
            showToast('warning', message);
            $('.packing-select-item').removeClass('selected');
            window.selectedPackpksGlobal = []; window.selectedRowsCache = {};
            $('#selectedCountGlobal').text('0'); $('#stickTopBarGlobal').hide();
        }

        if (selectedRows.some(r => r.ship_shipped === true)) { abortSelection('Carton yang sudah Shipment tidak dapat dipilih/diproses lagi.'); return; }
        if (new Set(selectedRows.map(r => String(r.part ?? ''))).size > 1) { abortSelection('Tidak bisa memilih carton dengan Part berbeda secara bersamaan.'); return; }
        if (new Set(selectedRows.map(r => Number(r.segel) === 1)).size > 1) { abortSelection('Tidak bisa memilih carton dengan status Segel berbeda secara bersamaan.'); return; }

        $('#selectedCountGlobal').text(new Set(selectedRows.map(r => r.carton)).size);
        $('#stickTopBarGlobal').show();

        const hasSegel = selectedRows.some(r => Number(r.segel) === 1);
        const hasPart = selectedRows.some(r => r.part !== null && r.part !== undefined && r.part !== '' && r.part !== 0);
        const anyInspecting = selectedRows.some(r => r.ship_inspect === true);
        const allComplete = selectedRows.length > 0 && selectedRows.every(isRowComplete);

        $('#btnBukaSegelGlobal').toggleClass('d-none', !(hasSegel && !hasPart));
        $('#btnBulkSegelCtnGlobal').toggleClass('d-none', !(allComplete && !hasSegel && !anyInspecting));

        const eligibleForShipFlow = hasSegel && !anyInspecting && hasPart;
        $('#btnProsesInspectGlobal').toggleClass('d-none', !eligibleForShipFlow);
        $('#btnProsesShipmentGlobal').toggleClass('d-none', !eligibleForShipFlow);
    }

    function closeMenuGlobal() {
        $('.packing-select-item').removeClass('selected');
        window.selectedPackpksGlobal = []; window.selectedRowsCache = {};
        updateSelectionGlobal();
    }
    function bulkSegelCtnGlobal() { openSegelModalGlobal(1); }
    function bulkBukaSegelGlobal() { openSegelModalGlobal(0); }

    function syncStickyBarGlobalPosition() {
        const bar = document.getElementById('stickTopBarGlobal');
        if (!bar) return;
        const navbar = document.querySelector('#navbarMain, nav.navbar, header.navbar, .app-navbar');
        bar.style.top = (navbar ? Math.max(0, navbar.getBoundingClientRect().bottom) : 0) + 'px';
    }
    window.addEventListener('scroll', syncStickyBarGlobalPosition, { passive: true });
    window.addEventListener('resize', syncStickyBarGlobalPosition);
</script>

<script>
    (function () {
        const input = document.getElementById('scanNobarInputGlobal');
        const feedback = document.getElementById('scanNobarFeedbackGlobal');
        const iconWrap = document.getElementById('scanIconWrapGlobal');
        const icon = document.getElementById('scanIconGlobal');
        if (!input) return;

        let busy = false, resetTimer = null;
        function focusScan() { try { input.focus({ preventScroll: true }); } catch (e) { input.focus(); } }
        focusScan();

        let skipRefocus = false;
        document.addEventListener('mousedown', function (e) {
            if (e.target.closest && e.target.closest('a, button, input, textarea, select, label, [onclick], [role="button"]')) {
                skipRefocus = true; setTimeout(() => { skipRefocus = false; }, 500);
            }
        }, true);

        input.addEventListener('blur', function () {
            setTimeout(function () {
                if (skipRefocus) return;
                const tag = document.activeElement ? document.activeElement.tagName : '';
                if (!['INPUT','TEXTAREA','SELECT','A','BUTTON'].includes(tag)) focusScan();
            }, 150);
        });

        function setScanState(state, message, iconClass) {
            iconWrap.classList.remove('is-processing','is-success','is-error');
            feedback.classList.remove('is-processing','is-success','is-error');
            if (state) { iconWrap.classList.add('is-' + state); feedback.classList.add('is-' + state); }
            icon.className = 'fas ' + iconClass + ' scan-nobar-icon';
            feedback.innerHTML = `<i class="fas ${iconClass}"></i><span>${message}</span>`;
        }
        function resetToIdle() { setScanState(null, 'Siap menerima scan.', 'fa-circle-info'); }

        input.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const nobar = input.value.trim();
            input.value = '';
            if (nobar === '' || busy) return;

            const activePart = typeof getActiveSession === 'function' ? getActiveSession() : null;

            if (resetTimer) clearTimeout(resetTimer);
            busy = true;
            setScanState('processing', `Memproses "${nobar}" ...`, 'fa-circle-notch');

            $.ajax({
                url: "{{ route('finish-good-stuffing.scan-nobar') }}", method: 'POST',
                data: { po: @json($po), op: @json($op), poref: @json($poref ?? null), mif: @json($mif), nobar: nobar },
                success: function (res) {
                    if (activePart && res.carton_part && String(res.carton_part) !== String(activePart)) {
                        setScanState('error', `Carton ini milik Part ${res.carton_part}, bukan Session yang sedang aktif.`, 'fa-circle-xmark');
                        showToast('warning', 'Carton ini bukan bagian dari session yang sedang aktif.');
                        resetTimer = setTimeout(resetToIdle, 4500);
                        return;
                    }
                    setScanState('success', res.title, 'fa-circle-check');
                    showToast(res.icon, res.title);
                    loadPackingCards(); reloadBreakdownSummary(); loadShipmentPlanCards();
                    resetTimer = setTimeout(resetToIdle, 3500);
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    setScanState('error', res.title, 'fa-circle-xmark');
                    showToast(res.icon, res.title);
                    resetTimer = setTimeout(resetToIdle, 4500);
                },
                complete: function () { busy = false; focusScan(); }
            });
        });
    })();
</script>

<script>
    function sessionLabel(num) { return `${SESSION_LABEL} ${num}`; }
    const SESSION_STORAGE_KEY = 'stuffingActiveSession_{{ $po }}_{{ $op }}';
    $('#sessionPlanTitle').text(`Shipment ${SESSION_LABEL}`);

    function getActiveSession() { return localStorage.getItem(SESSION_STORAGE_KEY) || null; }
    function setActiveSession(part) {
        if (part === null) localStorage.removeItem(SESSION_STORAGE_KEY);
        else localStorage.setItem(SESSION_STORAGE_KEY, String(part));
        renderActiveSessionBadge();
    }
    function renderActiveSessionBadge() {
        const active = getActiveSession();
        $('#activeSessionBadge').toggleClass('show', !!active);
        if (active) $('#activeSessionBadgeText').text(`Sedang berjalan: ${sessionLabel(active)}`);
    }
    function loadShipmentPlanCards() {
        $.get("{{ route('finish-good-stuffing.partSummaryGlobal') }}", { po: @json($po), op: @json($op), poref: @json($poref ?? null), mif: @json($mif) }, function (data) {
            renderShipmentPlanCards(data.parts || []);
        });
    }
    function renderShipmentPlanCards(parts) {
        const wrap = $('#shipmentPlanCards');
        wrap.empty();
        if (!parts.length) { wrap.html(`<div class="session-empty-hint">Belum ada ${SESSION_LABEL} yang diaturkan tim EXIM.</div>`); return; }
        const active = getActiveSession();
        parts.forEach(p => wrap.append(buildSessionCard(p, String(active) === String(p.part))));
    }
    function buildSessionCard(p, isActive) {
        const pct = p.total > 0 ? Math.round((p.shipped / p.total) * 100) : 0;
        const isDone = p.total > 0 && p.shipped >= p.total;
        const barColor = isDone ? '#16a34a' : '#0b89d2';
        const cardClass = isDone ? 'is-done' : (isActive ? 'is-active' : '');
        const icon = isDone ? 'fa-circle-check' : (isActive ? 'fa-circle-play' : 'fa-box');
        const statusText = isDone ? 'Selesai' : (isActive ? 'Sedang berjalan' : 'Menunggu');

        let actionsHtml;
        if (isDone) actionsHtml = `<button class="btn btn-outline-success btn-sm" disabled><i class="fas fa-check me-1"></i>Selesai</button>`;
        else if (isActive) actionsHtml = `<button class="btn btn-dark btn-sm" onclick="focusSession(${p.part})">Lanjut</button><button class="btn btn-outline-danger btn-sm" onclick="endSession(${p.part}, ${p.total}, ${p.shipped})">End</button>`;
        else actionsHtml = `<button class="btn btn-outline-dark btn-sm w-100" onclick="startSession(${p.part})">Mulai ${SESSION_LABEL}</button>`;

        return `<div class="session-card ${cardClass}">
            <div class="session-card-top"><div class="session-card-icon"><i class="fas ${icon}"></i></div><div><div class="session-card-title">${sessionLabel(p.part)}</div><div class="session-card-status">${statusText}</div></div></div>
            <div class="session-progress-bar"><span class="bar" style="width:${pct}%;background:${barColor};"></span></div>
            <div class="session-count"><strong>${p.shipped}</strong> / ${p.total} carton masuk (${pct}%)</div>
            <div class="session-actions">${actionsHtml}</div>
        </div>`;
    }
    function startSession(part) { setActiveSession(part); focusSession(part); showToast('success', `${sessionLabel(part)} dimulai.`); loadShipmentPlanCards(); }
    function focusSession(part) {
        if ($('#filterPartGlobal').data('combobox')) $('#filterPartGlobal').combobox('setValue', String(part));
        document.getElementById('packingCardsGrid')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function endSession(part, total, shipped) {
        const remaining = total - shipped;
        if (remaining <= 0) { setActiveSession(null); showToast('success', `${sessionLabel(part)} ditutup -- semua carton sudah masuk.`); loadShipmentPlanCards(); return; }
        if (!window.isSupervisorStuffing) { showToast('warning', `Belum bisa ditutup -- masih ada ${remaining} carton yang belum masuk. Hubungi supervisor kalau perlu ditutup paksa.`); return; }
        if (!confirm(`${sessionLabel(part)} masih ada ${remaining} carton BELUM masuk. Tutup paksa sebagai supervisor?`)) return;

        $.get("{{ route('finish-good-stuffing.list.detail.global') }}", { po: @json($po), op: @json($op), poref: @json($poref ?? null), mif: @json($mif), part: part, rows: 9999 }, function (data) {
            const remainingPackpks = (data.rows || []).filter(r => r.ship_shipped !== true).map(r => r.packpk);
            if (!remainingPackpks.length) { setActiveSession(null); loadShipmentPlanCards(); return; }

            $.ajax({
                url: "{{ route('finish-good-stuffing.bulk-ship-action') }}", method: 'POST',
                data: { packpk: remainingPackpks.join(','), action: 'shipment', mif: @json($mif) },
                success: function (res) {
                    showToast(res.icon, `${sessionLabel(part)} ditutup paksa -- ${remainingPackpks.length} carton di-mark masuk otomatis.`);
                    setActiveSession(null); loadPackingCards(); loadShipmentPlanCards();
                },
                error: function (xhr) { showToast('error', (xhr.responseJSON || {}).title || 'Gagal menutup session.'); }
            });
        });
    }
</script>

<script>
    function formatAction(value, row, index) {
        if (row.status == 5 || Number(row.segel) === 1) return '';
        return `<a href="javascript:void(0)" onclick="showToast('info','Edit carton (mode Global) belum tersedia.')" class="action-btn" title="Edit"><i class="fas fa-edit"></i></a>`;
    }
    function formatSegel(value, row) {
        if (Number(row.segel) === 1) return `<img src="{{ asset('public/css/images/Segel_carton.png') }}" width="28" title="Sudah Disegel">`;
        if (isRowComplete(row)) return `<img src="{{ asset('public/css/images/Complete_carton.png') }}" width="28" title="Actual = Plan (Complete)">`;
        return '<span class="text-muted">-</span>';
    }
    function formatPA() { return `<div style="line-height:18px;text-align:center;"><div style="font-weight:bold;border-bottom:1px solid #dcdcdc;">P</div><div style="font-weight:bold;">A</div></div>`; }
    function formatTotal(value, row) { return `<div style="line-height:18px;text-align:right"><div class="text-muted">${row.pcsp || 0}</div><div><b>${row.pcs || 0}</b></div></div>`; }
    function formatBalance(value, row) {
        let balance = (row.pcs || 0) - (row.pcsp || 0);
        let cls = balance < 0 ? 'color:#dc3545;font-weight:bold' : 'color:#198754;font-weight:bold';
        return `<span style="${cls}">${balance}</span>`;
    }
    @foreach ($activeSizes as $i => $sz)
        function formatSize{{ $i }}(value, row) {
            return `<div style="display:flex;flex-direction:column;height:40px;text-align:center;">
                <div style="flex:1;border-bottom:1px solid #d9d9d9;color:#6c757d;">${row.qtyp{{ $i }} || ''}</div>
                <div style="flex:1;font-weight:bold;color:#212529;">${row.qty{{ $i }} ?? '&nbsp;'}</div>
            </div>`;
        }
    @endforeach
    function rowStylerPacking(index, row) { return row.status == 5 ? 'datagrid-row-packing-shipped' : ''; }
    function goBack() {
        if (document.referrer && document.referrer.indexOf('/packing') !== -1) window.history.back();
        else window.location.href = "{{ route('finish-good-stuffing.index') }}";
    }
</script>
@endsection