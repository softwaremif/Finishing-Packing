@extends('layout.main')
@section('css_custom')
    <style>
        thead tr.group-total th {
            background: #f8fafc !important;
            border-bottom: 1px solid #e5e7eb !important;
            font-size: 12px;
        }

        .group-label {
            font-size: 11px;
            color: #6b7280;
            font-weight: 500;
        }

        .group-value {
            font-size: 13px;
            font-weight: 700;
        }

        .group-info {
            height: 55px;
            vertical-align: middle;
        }

        .group-center {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            height: 100%;
            padding-bottom: 6px;
        }

        .group-qty {
            background: #ecfeff !important;
        }

        .group-packing {
            background: #fef9c3 !important;
        }

        .datagrid-body td[field="packing_qty"],
        .datagrid-body td[field="packing_qty_plan"],
        .datagrid-body td[field="packing_qty_balance"],
        .datagrid-body td[field="ctn"],
        .datagrid-body td[field="packing_ctn"],
        .datagrid-body td[field="ctn_balance"] {
            text-align: right !important;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background-color: #e0f2fe;
            color: #0369a1;
            font-size: 13px;
            transition: 0.2s;
            text-decoration: none;
            border: none;
            outline: none;
            cursor: pointer;
            font-family: inherit;
            vertical-align: middle;
        }

        .action-btn:hover {
            background-color: #bae6fd;
            color: #0c4a6e;
            transform: scale(1.05);
        }

        .action-btn:focus-visible {
            outline: 2px solid #0369a1;
            outline-offset: 2px;
        }

        .action-btn.action-btn-pdf {
            background-color: #fee2e2 !important;
            color: #b91c1c !important;
        }

        .action-btn.action-btn-pdf:hover {
            background-color: #fecaca !important;
            color: #7f1d1d !important;
        }

        .action-btn img {
            display: block;
            margin: auto;
            max-width: 100%;
            max-height: 100%;
        }

        .datagrid-row-segel-complete {
            background: rgba(25, 183, 21, 0.15) !important;
        }

        .datagrid-row-segel-complete:hover {
            background: rgba(25, 183, 21, 0.25) !important;
        }

        .datagrid-row-status4 {
            background: rgba(148, 163, 184, 0.18) !important;
        }

        .datagrid-row-status4:hover {
            background: rgba(148, 163, 184, 0.28) !important;
        }

        #packingDetailModal .modal-dialog {
            max-width: min(1400px, 95vw);
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

        /* ============================================================
                       SAMA dengan TF Finishing/Polibag -- cell gabungan Order Info
                       & PO No.
                       ============================================================ */
        #dgOrder .datagrid-header .datagrid-cell {
            font-weight: 600;
            color: #374151;
            font-size: 12px;
        }

        #dgOrder .datagrid-body .datagrid-cell {
            font-size: 13px;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        #dgOrder .datagrid-row:hover td {
            background-color: #f8fafc !important;
        }

        .cell-stack {
            text-align: left;
            line-height: 1.35;
        }

        .cell-stack .cs-main {
            font-weight: 600;
            font-size: 13px;
            color: #0f172a;
        }

        .cell-stack .cs-sub {
            font-size: 11px;
            color: #64748b;
        }

        .cell-stack .cs-meta {
            font-size: 10.5px;
            color: #94a3b8;
        }

        .mif-badge {
            font-size: 9px;
            margin-left: 4px;
            vertical-align: 1px;
        }

        .ctn-summary-cell {
            text-align: left;
            line-height: 1.3;
            padding: 2px 0;
        }

        .ctn-summary-total {
            font-weight: 700;
            font-size: 14px;
            color: #0f172a;
        }

        .ctn-summary-label {
            font-weight: 500;
            font-size: 10.5px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .ctn-summary-bar {
            display: flex;
            height: 6px;
            border-radius: 4px;
            overflow: hidden;
            background: #f1f5f9;
            margin: 4px 0;
            width: 100%;
            min-width: 140px;
        }

        .ctn-seg {
            display: inline-block;
            height: 100%;
        }

        .ctn-summary-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            font-size: 10.5px;
            color: #475569;
        }

        .ctn-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            white-space: nowrap;
        }

        .ctn-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        #dgOrder .datagrid-body td[field="POno"],
        #dgOrder .datagrid-header td[field="POno"] {
            overflow: visible !important;
            white-space: normal !important;
        }

        #dgOrder .datagrid-body td[field="POno"] .datagrid-cell,
        #dgOrder .datagrid-header td[field="POno"] .datagrid-cell {
            height: auto !important;
            white-space: normal !important;
            overflow: visible !important;
            word-break: break-word;
            line-height: 1.4;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .cell-stack .cs-main,
        .cell-stack .cs-sub,
        .cell-stack .cs-meta {
            white-space: normal;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .summary-card {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: #fff;
            border: 1px solid #eef1f5;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
            padding: 16px;
            height: 100%;
            transition: box-shadow .15s ease, transform .15s ease;
        }

        .summary-card:hover {
            box-shadow: 0 6px 16px rgba(15, 23, 42, .08);
            transform: translateY(-1px);
        }

        .summary-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
        }

        .summary-card-body {
            min-width: 0;
            flex: 1 1 auto;
        }

        .summary-card-label {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #94a3b8;
            margin-bottom: 2px;
        }

        .summary-card-value {
            font-size: 1.7rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
        }

        .summary-skeleton {
            display: inline-block;
            width: 46px;
            height: 22px;
            border-radius: 6px;
            background: linear-gradient(90deg, #f1f5f9 25%, #e5e9ef 37%, #f1f5f9 63%);
            background-size: 400% 100%;
            animation: summarySkeletonWave 1.4s ease infinite;
        }

        @keyframes summarySkeletonWave {
            0% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0 50%;
            }
        }

        .summary-card-permif {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 6px;
        }

        .summary-card-permif .permif-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #eef1f5;
            border-radius: 999px;
            padding: 2px 8px;
            white-space: nowrap;
        }

        .summary-card-permif .permif-pill strong {
            color: #1e293b;
        }

        .container-card {
            background: #fff;
            border: 1px solid #eef1f5;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
            padding: 16px;
            cursor: pointer;
            transition: box-shadow .15s ease, transform .15s ease;
            height: 100%;
        }

        .container-card:hover {
            box-shadow: 0 6px 16px rgba(15, 23, 42, .08);
            transform: translateY(-1px);
            border-color: #cbd5e1;
        }

        .container-card .cc-title {
            font-weight: 700;
            font-size: 14px;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .container-card .cc-sub {
            font-size: 11.5px;
            color: #64748b;
            margin-top: 4px;
        }

        .container-card .cc-pill-wrap {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .container-card .cc-qty {
            font-size: 12.5px;
            font-weight: 600;
            color: #334155;
        }

        .container-poop-chip {
            display: block;
            width: 100%;
            text-align: left;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all .12s ease;
        }

        .container-poop-chip:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        .container-poop-chip .cpc-main {
            font-weight: 600;
            font-size: 13px;
            color: #0f172a;
        }

        .container-poop-chip .cpc-sub {
            font-size: 11px;
            color: #94a3b8;
        }

        /* GANTI/TAMBAH di section css_custom, setelah style yang sudah ada */

        .segmented-tabs {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            background: #f1f5f9;
            border-radius: 12px;
            padding: 4px;
            border: none;
            margin-bottom: 0 !important;
        }
        .segmented-tabs .nav-item {
            margin: 0;
        }
        .segmented-tabs .nav-link {
            border: none !important;
            border-radius: 9px !important;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            background: transparent;
            transition: all .15s ease;
            white-space: nowrap;
        }
        .segmented-tabs .nav-link:hover {
            color: #334155;
        }
        .segmented-tabs .nav-link.active {
            background: #fff !important;
            color: #0f172a !important;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .12);
        }
        .segmented-tabs .nav-link .badge {
            font-size: 9.5px;
            vertical-align: 1px;
        }

        /* Wrapper baris tab -- rata kanan seperti contoh, kasih jarak bawah */
        .segmented-tabs-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 16px;
        }
    </style>
@endsection
@section('content')
    <div class="page-wrap">
        {{-- BARU -- FIX UTAMA: 2 tab -- "Daftar Data OP" (isi LAMA, tidak
             berubah) dan "Container" (BARU). --}}
        <div class="segmented-tabs-row">
            <ul class="nav segmented-tabs" id="stuffingIndexTabs" role="tablist">
                @if (session('guserpk') == 35)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tabBtnDaftarOp" data-bs-toggle="tab" data-bs-target="#tabPaneDaftarOp"
                            type="button" role="tab">
                            <i class="fas fa-list me-1"></i> Daftar Data OP
                        </button>
                    </li>
                
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tabBtnContainer" data-bs-toggle="tab" data-bs-target="#tabPaneContainer"
                            type="button" role="tab" onclick="loadContainerTabIfNeeded()">
                            <i class="fas fa-truck-fast me-1"></i> Container
                            <span id="containerTabCount" class="badge bg-secondary ms-1 d-none">0</span>
                        </button>
                    </li>
                @endif
            </ul>
        </div>

        <div class="tab-content" id="stuffingIndexTabContent">
            {{-- ============================================================
                 TAB 1 -- ISI LAMA (persis sama, tidak ada perubahan logic).
                 ============================================================ --}}
            <div class="tab-pane fade show active" id="tabPaneDaftarOp" role="tabpanel">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-4">
                        <div class="summary-card">
                            <div class="summary-card-icon"
                                style="background:linear-gradient(135deg,#fef3c7,#fffbeb);color:#f59e0b;">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="summary-card-body">
                                <div class="summary-card-label">Total Carton Inspect</div>
                                <div class="summary-card-value" id="cardTotalInspectGlobal">
                                    <span class="summary-skeleton"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="summary-card">
                            <div class="summary-card-icon"
                                style="background:linear-gradient(135deg,#e2e8f0,#f1f5f9);color:#1e293b;">
                                <i class="fas fa-warehouse"></i>
                            </div>
                            <div class="summary-card-body">
                                <div class="summary-card-label">Ready di Gudang FG</div>
                                <div class="summary-card-value" id="cardTotalReadyGlobal">
                                    <span class="summary-skeleton"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <x-table-default id="dgOrder" title="Daftar Data OP" search search-name="search"
                    search-placeholder="Search..." buyer buyer-name="buyer" buyer-url="{{ route('api.buyer-list') }}"
                    buyer-value-field="buyer" buyer-text-field="buyer_name" buyer-mode="remote" year year-name="year"
                    exfactory exfactory-name="ex_factory" sort-dropdown sort-asc-label="Awal Ex-Factory"
                    sort-desc-label="Akhir Ex-Factory">

                    <table id="dgOrder" style="width:100%;height:600px"
                        url="{{ route('finish-good-stuffing.list') }}" method="get" pagination="true" pageSize="50"
                        pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true" fitColumns="false"
                        border="false">
                        <thead frozen="true">
                            <tr>
                                <th field="action" width="90" formatter="formatAction" align="center">Aksi</th>
                                <th field="OP" width="230" formatter="formatOrderInfo">Order Information</th>
                                <th field="POno" width="150" formatter="formatPOno">PO No</th>
                                <th field="GAC" width="100" align="center" formatter="formatExFactory">Ex Factory
                                </th>
                            </tr>
                        </thead>
                        <thead>
                            <tr>
                                <th field="poref" width="150">License<br>PO Ref</th>
                                <th field="packing_plan_status" width="120" align="center"
                                    formatter="formatPackingPlanStatus">Packing Plan</th>
                                <th field="ctn_summary" width="250" align="left"
                                    formatter="formatCtnPackingSummary">CTN <br> Packing</th>
                                <th field="ctn_shipment_summary" width="250" align="left"
                                    formatter="formatCtnShipmentSummary">CTN <br> Shipment</th>
                            </tr>
                        </thead>
                    </table>
                </x-table-default>
            </div>

            {{-- ============================================================
                 TAB 2 -- BARU -- daftar Container dari EXIM.
                 ============================================================ --}}
            @if (session('guserpk') == 35)
            <div class="tab-pane fade" id="tabPaneContainer" role="tabpanel">
                <div id="containerListEmpty" class="text-center text-muted py-5 d-none">
                    <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="160">
                    <div class="fw-semibold mt-2">Belum ada container</div>
                </div>
                <div id="containerListLoading" class="text-center text-muted py-5">Memuat container...</div>
                <div id="containerListGrid" class="row g-3"></div>
            </div>
            @endif
        </div>
    </div>

    {{-- Modal daftar PO/OP di dalam 1 container -- klik chip PO/OP masuk
         ke halaman input global, SAMA seperti tombol Detail di Tab 1. --}}
    <div class="modal fade" id="containerPoOpModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-1">
                    <h5 class="fw-bold text-dark mb-0" style="font-size:15px;" id="containerPoOpModalTitle">
                        <i class="fas fa-box me-2 text-secondary"></i>Container
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2" id="containerPoOpModalBody"></div>
            </div>
        </div>
    </div>
@endsection
@section('js_custom')
    @php
        $guserpk = Session::get('guserpk');
    @endphp
    <script>
        const isSuperUser = @json(session('guserpk') === 34);
        window.currentSessionMif = @json(session('pos'));
        const localNoImg = "{{ asset('public/css/images/no-img.png') }}";

        // ============================================================
        // FILTER STATE (index)
        // ============================================================
        function getSavedListState() {
            let raw = sessionStorage.getItem('packingListState');
            if (!raw) return null;
            sessionStorage.removeItem('packingListState');
            try {
                return JSON.parse(raw);
            } catch (e) {
                return null;
            }
        }

        function savePackingNavState() {
            let pager = $('#dgOrder').datagrid('getPager');
            let pageNumber = pager.pagination('options').pageNumber;
            let state = {
                search: $('#dgOrder_filterbar [data-dg-filter="search"]').val(),
                buyer: $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('getValue'),
                year: $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('getValue'),
                exFactory: $('#dgOrder_filterbar [data-dg-filter="ex_factory"]').combobox('getValue'),
                page: pageNumber,
            };
            sessionStorage.setItem('packingListState', JSON.stringify(state));
        }

        function loadCardsSummaryGlobal() {
            $.get("{{ route('finish-good-stuffing.cardsSummaryGlobal') }}", function(data) {
                $('#cardTotalShippedGlobal').text(formatNumber(data.total_shipped_carton ?? 0));
                $('#cardTotalInspectGlobal').text(formatNumber(data.total_inspect_carton ?? 0)); // BARU
                $('#cardTotalReadyGlobal').text(formatNumber(data.total_ready_carton ?? 0));
            }).fail(function(xhr) {
                console.error('cardsSummaryGlobal GAGAL:', xhr.status, xhr.responseText);
                $('#cardTotalShippedGlobal, #cardTotalInspectGlobal, #cardTotalReadyGlobal').text('-');
            });
        }

        let lastCardsRefreshAt = 0;
        function loadCardsSummaryGlobalThrottled() {
            const now = Date.now();
            if (now - lastCardsRefreshAt < 800) return;
            lastCardsRefreshAt = now;
            loadCardsSummaryGlobal();
        }
        
        window.canSeeContainerTab = @json(session('guserpk') == 35);
        $(function() {
            loadCardsSummaryGlobalThrottled();

            if (window.canSeeContainerTab) {
                containerTabLoaded = true;
                loadContainerList();
            }

            let saved = getSavedListState();
            if (saved) {
                $('#dgOrder_filterbar [data-dg-filter="search"]').val(saved.search || '');
                $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('setValue', saved.buyer || '');
                $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('setValue', saved.year ?? new Date().getFullYear());
                if (saved.exFactory) {
                    $('#dgOrder_filterbar [data-dg-filter="ex_factory"]').combobox('setValue', saved.exFactory);
                }
            }

            $('#dgOrder').datagrid('options').onLoadSuccess = onLoadTable;

            if (saved?.modalOp) {
                restoredDgOrderPage = saved.page || 1;
                openPackingDetailModal(null, saved.modalPo, saved.modalOp, saved.modalPoref, saved.modalMif);
            } else if (saved?.page) {
                window.EasyuiDG.reload('dgOrder', saved.page);
            }
        });
        
        window.addEventListener('pageshow', function(event) {
            if (!event.persisted) return;
            loadCardsSummaryGlobalThrottled();
            if (window.EasyuiDG) {
                window.EasyuiDG.reload('dgOrder');
            }
            if ($('#packingDetailModal').hasClass('show')) {
                reloadPackingDetailModal();
            }
        });
        
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState !== 'visible') return;
            loadCardsSummaryGlobalThrottled(); // GANTI -- sama fungsi, throttle konsisten
            if (window.EasyuiDG) {
                window.EasyuiDG.reload('dgOrder');
            }
        });

        function formatCtnPackingSummary(value, row) {
            const total = Number(row.ctn_plan_count || 0);
            const sealed = Number(row.ctn_sealed_count || 0);
            const full = Number(row.ctn_full_count || 0);
            const partial = Number(row.ctn_partial_count || 0);
            const notStarted = Math.max(0, total - sealed - full - partial);

            if (total === 0) {
                return `<div class="ctn-summary-cell"><span class="text-muted" style="font-size:12px;">Belum ada carton</span></div>`;
            }

            const pct = (n) => (n / total) * 100;

            // Urutan segmen SENGAJA sealed dulu -- carton yang sudah paling
            // "matang" statusnya tampil di ujung kiri bar (representasi progres).
            const segments = [{
                    label: 'Sealed',
                    value: sealed,
                    color: '#1e293b'
                },
                {
                    label: 'Full',
                    value: full,
                    color: '#8bc63f'
                },
                {
                    label: 'Partial',
                    value: partial,
                    color: '#f97316'
                },
                {
                    label: 'Plan',
                    value: notStarted,
                    color: '#cbd5e1'
                },
            ];

            const barHtml = segments
                .filter(s => s.value > 0)
                .map(s =>
                    `<span class="ctn-seg" style="width:${pct(s.value)}%;background:${s.color};" title="${s.label}: ${s.value}"></span>`
                )
                .join('');

            const legendHtml = segments
                .filter(s => s.value > 0)
                .map(s =>
                    `<span class="ctn-legend-item"><span class="ctn-dot" style="background:${s.color};"></span>${s.value} ${s.label}</span>`
                )
                .join('');

            return `
                <div class="ctn-summary-cell">
                    <div class="ctn-summary-total">${total} <span class="ctn-summary-label">Carton</span></div>
                    <div class="ctn-summary-bar">${barHtml}</div>
                    <div class="ctn-summary-legend">${legendHtml}</div>
                </div>
            `;
        }

        function formatCtnShipmentSummary(value, row) {
            const sealedRaw = Number(row.ctn_sealed_count || 0);
            const shipped = Number(row.ctn_shipped_count || 0);
            const inspecting = Number(row.ctn_inspect_count || 0); // BARU

            // carton yang sedang Inspect segel-nya di-reset jadi
            // null (lihat fix bulkShipAction()), jadi TIDAK ikut terhitung di
            // ctn_sealed_count -- makanya inspecting ditambahkan TERPISAH ke
            // total, bukan diambil dari sealedRaw.
            const readyOnly = Math.max(0, sealedRaw - shipped);
            const total = shipped + inspecting + readyOnly;

            if (total === 0) {
                return `<div class="ctn-summary-cell"><span class="text-muted" style="font-size:12px;">Belum ada carton</span></div>`;
            }

            const pct = (n) => (n / total) * 100;

            // Urutan segmen: Shipped (paling matang) -> Inspect -> Ready (sealed,
            // belum diproses apa pun).
            const segments = [{
                    label: 'Stuffing',
                    value: shipped,
                    color: '#8bc63f'
                },
                {
                    label: 'Inspect',
                    value: inspecting,
                    color: '#f59e0b'
                }, // BARU -- selaras warna .ship-stamp-inspect
                {
                    label: 'Ready',
                    value: readyOnly,
                    color: '#1e293b'
                },
            ];

            const barHtml = segments
                .filter(s => s.value > 0)
                .map(s =>
                    `<span class="ctn-seg" style="width:${pct(s.value)}%;background:${s.color};" title="${s.label}: ${s.value}"></span>`
                )
                .join('');

            const legendHtml = segments
                .filter(s => s.value > 0)
                .map(s =>
                    `<span class="ctn-legend-item"><span class="ctn-dot" style="background:${s.color};"></span>${s.value} ${s.label}</span>`
                )
                .join('');

            return `
                <div class="ctn-summary-cell">
                    <div class="ctn-summary-total">${total} <span class="ctn-summary-label">Carton</span></div>
                    <div class="ctn-summary-bar">${barHtml}</div>
                    <div class="ctn-summary-legend">${legendHtml}</div>
                </div>
            `;
        }

        function formatPackingPlanStatus(value) {
            const map = {
                pending: { label: 'Pending', bg: '#f1f5f9', color: '#475569' },
                partial: { label: 'Partial', bg: '#FFEBDD', color: '#f97316' },
                complete: { label: 'Complete', bg: '#dcfce7', color: '#16a34a' },
            };
            const s = map[value] || map.pending;
            return `
                <span style="display:inline-flex;align-items:center;padding:5px 14px;border-radius:999px;
                    font-size:11.5px;font-weight:700;background:${s.bg};color:${s.color};">
                    ${s.label}
                </span>
            `;
        }
        // ============================================================
        // FORMATTER UMUM
        // ============================================================
        function formatDate(value) {
            if (!value) return '<span class="dg-empty-cell">-</span>';
            let datePart = String(value).split(' ')[0];
            let parts = datePart.split('-');
            if (parts.length !== 3) return value;
            let [year, month, day] = parts;
            return `${day}/${month}/${year}`;
        }

        function formatNumber(value) {
            return Number(value || 0).toLocaleString('id-ID');
        }

        function formatBalanceCell(value) {
            let v = Number(value || 0);
            let cls = v < 0 ? 'color:#dc3545;font-weight:bold' : (v > 0 ? 'color:#198754;font-weight:bold' :
                'color:#94a3b8');
            let text = v > 0 ? ('+' + formatNumber(v)) : formatNumber(v);
            return `<span style="${cls}">${text}</span>`;
        }

        // Ex Factory -- validasi KETAT, kosongkan (bukan "-"/NaN/undefined)
        // kalau GAC tidak valid. SAMA pola dengan TF Finishing/Polibag.
        function formatExFactory(value) {
            if (value === null || value === undefined || value === '') return '';

            const datePart = String(value).split(' ')[0].split('T')[0];
            const parts = datePart.split('-');
            if (parts.length !== 3) return '';

            const year = parseInt(parts[0], 10);
            const monthIdx = parseInt(parts[1], 10) - 1;
            const dayNum = parseInt(parts[2], 10);

            if (
                !Number.isFinite(year) || year <= 0 ||
                !Number.isFinite(monthIdx) || monthIdx < 0 || monthIdx > 11 ||
                !Number.isFinite(dayNum) || dayNum <= 0 || dayNum > 31
            ) {
                return '';
            }

            const bulanSingkat = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return `${dayNum} ${bulanSingkat[monthIdx]} ${year}`;
        }

        // PO No + Place digabung 1 cell.
        function formatPOno(value, row) {
            return `
                <div class="cell-stack">
                    <div class="cs-main">${value ?? '-'}</div>
                    <div class="cs-sub">${row.customer ?? '-'}</div>
                </div>
            `;
        }

        // Order Information -- foto, OP, Buyer, Season, Style, Qty
        // digabung 1 cell. SAMA pola dengan TF Finishing/Polibag.
        function formatOrderInfo(value, row) {
            const mifBadge = isSuperUser ?
                `<span class="badge bg-secondary-subtle text-secondary-emphasis mif-badge">mif ${row.mif}</span>` :
                '';

            const imgUrl = row.order_image || localNoImg;
            const imgHtml = `
                <img src="${imgUrl}" width="60" height="60"
                    style="object-fit:cover;border-radius:6px;flex-shrink:0;"
                    onerror="this.onerror=null;this.src='${localNoImg}';">
            `;

            return `
                <div class="d-flex align-items-start gap-2">
                    ${imgHtml}
                    <div class="cell-stack">
                        <div class="cs-main">${row.OP ?? '-'}${mifBadge}</div>
                        <div class="cs-sub">${row.buyer ?? '-'} &middot; ${row.season ?? '-'}</div>
                        <div class="cs-sub">${row.style ?? '-'}</div>
                        <div class="cs-meta">Qty: <strong style="color:#334155;">${Number(row.qty || 0).toLocaleString()}</strong></div>
                    </div>
                </div>
            `;
        }

        // ============================================================
        // KOLOM AKSI INDEX
        // ============================================================
        function formatAction(value, row, index) {
            const pdfUrl = "{{ route('laporan.pdf.global') }}" +
                "?po=" + encodeURIComponent(row.POno ?? '') +
                "&op=" + encodeURIComponent(row.OP) +
                "&poref=" + encodeURIComponent(row.poref ?? '') +
                "&mif=" + row.mif;

            return `
                <div class="d-inline-flex align-items-center gap-1.5">
                    <a href="javascript:void(0)"
                        onclick='openPackingGlobal(event, ${JSON.stringify(row.POno)}, ${JSON.stringify(row.OP)}, ${JSON.stringify(row.poref)}, ${row.mif})'
                        class="action-btn"
                        title="Input Packing Global">
                        <i class="fas fa-edit"></i>
                    </a>

                    <a href="${pdfUrl}" target="_blank" class="action-btn action-btn-pdf" title="Print PDF">
                        <img src="{{ asset('/public/css/images/pdf.jpg') }}" width="18" height="18" style="display: block; object-fit: contain;">
                    </a>
                </div>
            `;
        }

        function openPackingGlobal(e, po, op, poref, mif) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            savePackingNavState();
            let url = "{{ route('finish-good-stuffing.input.global') }}" +
                "?po=" + encodeURIComponent(po ?? '') +
                "&op=" + encodeURIComponent(op) +
                "&poref=" + encodeURIComponent(poref ?? '') +
                "&mif=" + mif;
            window.location.href = url;
        }

        // ============================================================
        // onLoadSuccess INDEX — summary + empty state
        // ============================================================
        function onLoadTable(data) {
            const rows = data.rows || [];
            rows.forEach((row, index) => {
                row.no = index + 1;
            });
            let panel = $('#dgOrder').datagrid('getPanel');
            let body = panel.find('.datagrid-view2 .datagrid-body');
            panel.find('.easyui-empty-state').remove();
            if (!rows.length) {
                body.append(`
                    <div class="easyui-empty-state">
                        <div style="text-align:center">
                            <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="180">
                            <div style="margin-top:8px;font-weight:600;">No Data Found</div>
                            <div style="font-size:12px;color:#9ca3af;">Try changing filter</div>
                        </div>
                    </div>
                `);
            }
        }

        // TAMBAHKAN function-function ini di section js_custom.

        let containerTabLoaded = false;
        let containersCache = [];

        // Dipanggil saat tab "Container" pertama kali diklik -- lazy load,
        // TIDAK fetch ulang kalau sudah pernah dimuat (klik tab lagi cukup
        // tampilkan cache).
        function loadContainerTabIfNeeded() {
            if (containerTabLoaded) return;
            containerTabLoaded = true;
            loadContainerList();
        }

        function loadContainerList() {
            $('#containerListLoading').removeClass('d-none');
            $('#containerListEmpty').addClass('d-none');
            $('#containerListGrid').empty();

            $.get("{{ route('finish-good-stuffing.listContainersGlobal') }}", function(data) {
                containersCache = data.containers || [];
                $('#containerListLoading').addClass('d-none');

                // BARU -- FIX UTAMA: update badge jumlah container di tombol tab.
                updateContainerTabBadge(containersCache.length);

                if (data.error) {
                    $('#containerListGrid').html(
                        `<div class="col-12 text-center text-danger py-4"><i class="fas fa-triangle-exclamation me-1"></i>${data.error}</div>`
                    );
                    return;
                }
                if (!containersCache.length) {
                    $('#containerListEmpty').removeClass('d-none');
                    return;
                }
                renderContainerCards(containersCache);
            }).fail(function() {
                $('#containerListLoading').addClass('d-none');
                $('#containerListGrid').html(
                    `<div class="col-12 text-center text-danger py-4"><i class="fas fa-triangle-exclamation me-1"></i>Gagal memuat daftar container.</div>`
                );
            });
        }

        function updateContainerTabBadge(count) {
            const $badge = $('#containerTabCount');
            if (count > 0) {
                $badge.text(count).removeClass('d-none');
            } else {
                $badge.addClass('d-none');
            }
        }

        function renderContainerCards(containers) {
            const $grid = $('#containerListGrid');
            $grid.empty();

            containers.forEach(function(c, idx) {
                const contEnded = !!c.segel;
                const contStarted = !!c.start_ship;
                let statusBadge;
                if (contEnded) {
                    statusBadge = `<span class="badge" style="background:#8bc63f;font-size:9.5px;">Selesai</span>`;
                } else if (contStarted) {
                    statusBadge = `<span class="badge" style="background:#f97316;font-size:9.5px;">Berjalan</span>`;
                } else {
                    statusBadge = `<span class="badge bg-secondary" style="font-size:9.5px;">Belum Mulai</span>`;
                }
                const exdateLabel = formatDate(c.exdate) !== '<span class="dg-empty-cell">-</span>' ? formatDate(c
                    .exdate) : '-';

                $grid.append(`
            <div class="col-12 col-md-6 col-xl-4">
                <div class="container-card" onclick="openContainerPoOpModal(${idx})">
                    <div class="cc-title">
                        <i class="fas fa-truck-fast me-1"></i>
                        <span>${c.contno ?? '-'}</span>
                        ${statusBadge}
                    </div>
                    <div class="cc-sub">${c.typenm ?? c.type ?? '-'} &middot; PEB ${c.pebno ?? '-'}</div>
                    <div class="cc-sub">${c.buyer ?? '-'} &middot; Ex Factory ${exdateLabel}</div>
                    <div class="cc-pill-wrap">
                        <span class="cc-qty"><i class="fas fa-boxes-stacked me-1"></i>${c.qty_ctn} carton</span>
                        <span class="text-muted" style="font-size:11px;">${c.po_op_list.length} PO/OP <i class="fas fa-chevron-right ms-1"></i></span>
                    </div>
                </div>
            </div>
        `);
            });
        }

        // BARU -- klik container -> modal daftar PO/OP di dalamnya, klik salah
        // satu PO/OP -> masuk halaman input global (SAMA function dgn tombol
        // Detail di Tab 1: openPackingGlobal()).
        function openContainerPoOpModal(idx) {
            const c = containersCache[idx];
            if (!c) return;

            $('#containerPoOpModalTitle').html(
                `<i class="fas fa-box me-2 text-secondary"></i>${c.contno ?? '-'} &middot; PEB ${c.pebno ?? '-'}`);

            const poOpList = c.po_op_list || [];
            const bodyHtml = poOpList.length ?
                poOpList.map(function(p) {
                    const mifBadge = p.mif ?
                        `<span class="badge bg-secondary-subtle text-secondary-emphasis mif-badge">mif ${p.mif}</span>` :
                        '';

                    // BARU -- FIX UTAMA: kalau factory PO/OP ini BEDA dengan session
                    // mif yang sedang login, chip dikunci (tidak bisa diklik) --
                    // mencegah user mif 1 masuk ke data factory 2, dst.
                    const factoryMismatch = p.factory &&
                        window.currentSessionMif &&
                        String(p.factory).trim() !== String(window.currentSessionMif).trim();

                    if (factoryMismatch) {
                        return `
                    <div class="container-poop-chip" style="cursor:not-allowed; opacity:.5; background:#f1f5f9;"
                        title="Factory ${p.factory} tidak sesuai dengan sesi login Anda (mif ${window.currentSessionMif})">
                        <div class="cpc-main">
                            <i class="fas fa-lock me-1" style="font-size:11px;"></i>
                            ${p.POno ?? '-'} &middot; ${p.OP ?? '-'} ${mifBadge}
                        </div>
                        <div class="cpc-sub">${p.factory ? 'Factory ' + p.factory + ' (terkunci)' : ''}</div>
                    </div>
                `;
                    }

                    return `
                <div class="container-poop-chip" onclick='openPackingGlobal(null, ${JSON.stringify(p.POno)}, ${JSON.stringify(p.OP)}, ${JSON.stringify(p.poref)}, ${p.mif ?? 'null'})'>
                    <div class="cpc-main">${p.POno ?? '-'} &middot; ${p.OP ?? '-'} ${mifBadge}</div>
                    <div class="cpc-sub">${p.factory ? 'Factory ' + p.factory : ''}</div>
                </div>
            `;
                }).join('') :
                `<div class="text-center text-muted py-4">Tidak ada data PO/OP untuk container ini.</div>`;

            $('#containerPoOpModalBody').html(bodyHtml);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('containerPoOpModal')).show();
        }
    </script>
@endsection
