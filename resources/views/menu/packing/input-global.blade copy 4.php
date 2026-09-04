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
            min-height: 0; /* WAJIB supaya flex child bisa di-scroll, bukan overflow keluar card */
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
            background: #dbeafe;
            color: #1d4ed8;
        }

        .packing-card .badge-status.complete {
            background: #dcfce7;
            color: #15803d;
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
            min-width: 0; /* WAJIB supaya overflow-x benar-benar bisa scroll */
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
    </style>
@endsection

@section('content')
    <div id="stickTopBarGlobal" class="sticky-order-bar">
        <div class="sticky-order-inner">
            <div>
                <strong><span id="selectedCountGlobal">0</span> carton terpilih</strong>
            </div>
            <div>
                <span class="sticky-action d-none" id="btnBukaSegelGlobal" onclick="bulkBukaSegelGlobal()">
                    Buka Segel
                </span>
                <span class="sticky-action" id="btnBulkSegelCtnGlobal" onclick="bulkSegelCtnGlobal()">
                    Segel CTN
                </span>
                <span class="sticky-action" id="btnBulkActualCtnGlobal" onclick="bulkActualCtnGlobal()">
                    Input Actual
                </span>
                <span class="sticky-action" id="btnBulkDeleteActualCtnGlobal" onclick="bulkDeleteActualCtnGlobal()">
                    Delete Actual
                </span>
                <span class="sticky-action" id="btnBulkCopyGlobal" onclick="bulkCopyGlobal()">
                    Copy CTN
                </span>
                <span class="sticky-action" id="btnBulkDeleteGlobal" onclick="bulkDeleteCtnGlobal()">
                    Delete CTN
                </span>
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
        </div>

        @php
            $totalPcs = array_sum($orderQty ?? []);
            $plannedPcs = array_sum($planQty ?? []);
            $packedPcs = array_sum($readyQty ?? []);
            $shortPcs = max(0, $totalPcs - $plannedPcs);

            $plannedPct = $totalPcs > 0 ? round(($plannedPcs / $totalPcs) * 100) : 0;
            $packedPct = $totalPcs > 0 ? round(($packedPcs / $totalPcs) * 100) : 0;

            $totalColors = $totalColors ?? 0;
            $totalSizes = count($activeSizes ?? []);

            $totalCarton = $totalCarton ?? 0;
            $sealedCarton = $sealedCarton ?? 0;
            $openCarton = $openCarton ?? 0;
        @endphp

        <div class="row g-3 mb-4">

            <div class="col-6 col-md-3 col-lg-2">
                <div class="bg-white border rounded-3 p-3 h-100" style="border-color:#e5e7eb;">
                    <div class="text-secondary mb-1" style="font-size:12px;">Order breakdown</div>
                    <div class="fw-bold text-dark" style="font-size:1.6rem;">{{ number_format($totalPcs) }}</div>
                    <div class="text-secondary" style="font-size:11.5px;">
                        pcs across {{ $totalColors }} colors &middot; {{ $totalSizes }} sizes
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <div class="bg-white border rounded-3 p-3 h-100" style="border-color:#e5e7eb;">
                    <div class="text-secondary mb-1" style="font-size:12px;">Planned into cartons</div>
                    <div class="mb-1">
                        <span class="fw-bold text-dark" style="font-size:1.6rem;">{{ number_format($plannedPcs) }}</span>
                        <span class="text-secondary" style="font-size:12px;">/ {{ number_format($totalPcs) }}</span>
                    </div>
                    <div class="progress mb-1" style="height:5px; background-color:#e5e7eb;">
                        <div class="progress-bar" role="progressbar"
                            style="width:{{ min(100, $plannedPct) }}%; background-color:#3b82f6;"></div>
                    </div>
                    <div class="text-secondary" style="font-size:11.5px;">{{ $plannedPct }}% of order covered</div>
                </div>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <div class="bg-white border rounded-3 p-3 h-100" style="border-color:#e5e7eb;">
                    <div class="text-secondary mb-1" style="font-size:12px;">Packed &amp; verified</div>
                    <div class="mb-1">
                        <span class="fw-bold text-dark" style="font-size:1.6rem;">{{ number_format($packedPcs) }}</span>
                        <span class="text-secondary" style="font-size:12px;">/ {{ number_format($totalPcs) }}</span>
                    </div>
                    <div class="progress mb-1" style="height:5px; background-color:#e5e7eb;">
                        <div class="progress-bar" role="progressbar"
                            style="width:{{ min(100, $packedPct) }}%; background-color:#22c55e;"></div>
                    </div>
                    <div class="text-secondary" style="font-size:11.5px;">{{ $packedPct }}% of order packed</div>
                </div>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <div class="bg-white border rounded-3 p-3 h-100" style="border-color:#e5e7eb;">
                    <div class="text-secondary mb-1" style="font-size:12px;">Cartons</div>
                    <div class="fw-bold text-dark" style="font-size:1.6rem;">{{ number_format($totalCarton) }}</div>
                    <div class="text-secondary" style="font-size:11.5px;">
                        {{ $sealedCarton }} sealed &middot; {{ $openCarton }} open
                    </div>
                </div>
            </div>

            @if ($shortPcs > 0)
                <div class="col-12 col-lg-4">
                    <div class="rounded-3 p-3 h-100 d-flex align-items-start gap-3 text-white"
                        style="background-color:#f97316;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:34px; height:34px; background-color:rgba(255,255,255,.25);">
                            <i class="fas fa-exclamation-triangle" style="font-size:14px;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:0.95rem;">
                                {{ number_format($shortPcs) }} pcs short of order
                            </div>
                            <div style="font-size:12.5px; opacity:.95;">
                                Add cartons to cover the remaining breakdown
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <div id="breakdownSummaryWrapper">
            @include('menu.packing.partials.breakdown_summary_global')
        </div>

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
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-dark btn-sm d-flex align-items-center fw-semibold"
                                style="font-size:12px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                                onclick="openUrutkanCtnModal()">
                                <i class="fas fa-sort-numeric-down me-1"></i> Urutkan CTN
                            </button>
                            <button class="btn btn-dark btn-sm d-flex align-items-center fw-semibold"
                                style="font-size:12px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                                onclick="openPackingGlobalModal()">
                                <i class="fas fa-plus me-1"></i> Add Packing
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-3">
                    <div id="packingCardsGrid" class="row g-3"></div>

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

    @include('menu.packing.modal-packing-global')
    @include('menu.packing.modal-actual-ctn-global')
    @include('menu.packing.modal-delete-actual-ctn-global')
    @include('menu.packing.modal-delete-ctn-global')
    @include('menu.packing.modal-copy-ctn-global')
    @include('menu.packing.modal-segel-ctn-global')
    @include('menu.packing.modal-urutkan-ctn-global')
@endsection

@section('js_custom')
    <script>
        window.pgCombos = @json($colorSecszCombos ?? []);
        window.pgSizes = @json($activeSizes);
    </script>
    <script>
        function reloadBreakdownSummary() {
            $.get("{{ route('packing.breakdownSummaryGlobal') }}", {
                po: @json($po),
                op: @json($op),
                poref: @json($poref ?? null),
                mif: @json($mif)
            }, function(html) {
                $('#breakdownSummaryWrapper').html(html);
            });
        }
    </script>

    <script>
        window.activeSizesGlobal = @json($activeSizes);
        window.colorListGlobal = @json($colorList ?? []);
        window.secszListGlobal = @json($secszList ?? []);

        let packingCardsPage = 1;
        let packingCardsRows = 25;
        let packingCardsTotal = 0;
        let packingCardsTotalCarton = 0;

        $(function() {
            initFilterColorGlobal();
            initFilterSizeGlobalCombobox();
            initFilterSecszGlobal();
            initSortFieldGlobalCombobox();
            loadPackingCards();
        });

        function initSortFieldGlobalCombobox() {
            const sortData = [
                { value: '',            text: 'Urutan Default' },
                { value: 'carton_asc',  text: 'No Carton (A-Z)' },
                { value: 'carton_desc', text: 'No Carton (Z-A)' },
                { value: 'nobar_asc',   text: 'Barcode (A-Z)' },
                { value: 'nobar_desc',  text: 'Barcode (Z-A)' },
            ];
        
            $('#sortFieldGlobal').combobox({
                data: sortData,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function () {
                    reloadPackingGlobal();
                }
            });
        }
 
        function loadPackingCards() {
            $.get("{{ route('packing.list.detail.global') }}", {
                po: @json($po),
                op: @json($op),
                poref: @json($poref ?? null),
                mif: @json($mif),
                search: $('#searchPackingGlobal').val(),
                size: $('#filterSizeGlobal').combobox('getValue'),
                color: $('#filterColorGlobal').combobox('getValue'),
                secsz: $('#filterSecszGlobal').combobox('getValue'),
                sort: $('#sortFieldGlobal').combobox('getValue'), // BARU
                page: packingCardsPage,
                rows: packingCardsRows
            }, function (data) {
                packingCardsTotal       = data.total || 0;
                packingCardsTotalCarton = data.total_carton || 0;
        
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
            window.selectedPackpksGlobal = [];
            $('#stickTopBarGlobal').hide();
            $('#selectedCountGlobal').text('0');
        
            const grid = $('#packingCardsGrid');
            const empty = $('#packingCardsEmpty');
            grid.empty();

            if (!rows.length) {
                empty.removeClass('d-none');
                $('#packingCardsInfo').text('0 carton');
                $('#packingCardsPageLabel').text('Halaman 1 / 1');
                return;
            }

            empty.addClass('d-none');

            // ============================================================
            // BARU: kelompokkan baris (yang masing-masing 1 popk) berdasarkan
            // NOMOR CARTON -- carton "Mixed" (banyak popk, carton sama) jadi
            // SATU grup, ditampilkan sebagai SATU card, bukan card terpisah
            // per popk seperti sebelumnya.
            //
            // CATATAN: pengelompokan ini cuma berlaku dalam BATAS HALAMAN yang
            // sedang tampil (pagination server-side per-baris, bukan per-
            // carton) -- kalau 1 carton "Mixed" kebetulan terpotong di batas
            // halaman, sebagian anggotanya bisa ke-render di card terpisah di
            // halaman berikutnya. Umumnya jarang terjadi kalau pageSize cukup
            // besar, tapi tetap perlu diketahui.
            // ============================================================
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

            cartonOrder.forEach(function(cartonKey) {
                grid.append(buildPackingCard(cartonGroups[cartonKey]));
            });

            const maxPage = Math.max(1, Math.ceil(packingCardsTotal / packingCardsRows));
            $('#packingCardsInfo').text(packingCardsTotalCarton + ' carton');
            $('#packingCardsPageLabel').text('Halaman ' + packingCardsPage + ' / ' + maxPage);
            $('#btnPackingCardsPrev').prop('disabled', packingCardsPage <= 1);
            $('#btnPackingCardsNext').prop('disabled', packingCardsPage >= maxPage);
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

        // $groupRows = array baris (1 baris = 1 popk) yang berbagi nomor
        // carton yang sama.
        function buildPackingCard(groupRows) {
            const activeIdx = Object.keys(window.activeSizesGlobal || {});
            const first = groupRows[0];

            const uniqueCombos = new Set(groupRows.map(r => `${r.material ?? '-'}||${r.secsz ?? ''}`));
            const compositionLabel = uniqueCombos.size > 1 ?
                'Mixed' :
                (groupRows.some(r => activeIdx.filter(i => Number(r[`qtyp${i}`] || 0) > 0).length > 1) ? 'Assorted' :
                    'Solid');

            const compositionClass = compositionLabel.toLowerCase();

            const status = getGroupStatus(groupRows);

            let totalPlan = 0;
            let totalActual = 0;
            let sizeRows = '';

            groupRows.forEach(function(row) {
                const plannedSizes = activeIdx.filter(i => Number(row[`qtyp${i}`] || 0) > 0);

                plannedSizes.forEach(function(i) {
                    const plan = Number(row[`qtyp${i}`] || 0);
                    const actual = Number(row[`qty${i}`] || 0);
                    totalPlan += plan;
                    totalActual += actual;

                    const sizePct = plan > 0 ? Math.round((actual / plan) * 100) : 0;
                    const miniColor = sizePct >= 100 ? '#22c55e' : '#3b82f6';
                    const secszTag = row.secsz ? ` (${row.secsz})` : '';

                    sizeRows += `
                    <div class="size-row">
                        <span class="dot"></span>
                        <span class="name">${row.material ?? '-'}${secszTag} &middot; ${window.activeSizesGlobal[i] ?? i}</span>
                        <span class="mini-progress"><span class="bar" style="width:${Math.min(100, sizePct)}%; background:${miniColor};"></span></span>
                        <span class="frac">${actual}/${plan}</span>
                    </div>
                `;
                });
            });

            const pct = totalPlan > 0 ? Math.round((totalActual / totalPlan) * 100) : 0;
            const barColor = status.key === 'sealed' ? '#64748b' : (pct >= 100 ? '#22c55e' : '#3b82f6');

            // Subline: kalau cuma 1 combo, tampilkan "Color · Sec Size" seperti
            // sebelumnya. Kalau lebih dari 1 (Mixed), tampilkan ringkas jumlah
            // combo-nya saja (detail lengkap sudah ada di baris per-size).
            let subline;
            if (uniqueCombos.size === 1) {
                const secszLabel = first.secsz ? ` &middot; Sec Size ${first.secsz}` : '';
                subline = `${first.material ?? '-'}${secszLabel}`;
            } else {
                subline = `${uniqueCombos.size} kombinasi Color/Sec Size`;
            }

            const canScan = status.key === 'planned' || status.key === 'packing';
            const canMark = status.key === 'planned' || status.key === 'packing';
            const canSeal = status.key === 'complete';
            const isSealed = status.key === 'sealed'; // BARU
            
            const packpks = groupRows.map(r => r.packpk);
            const packpksAttr = packpks.join(',');
            
            const allSegel = groupRows.every(r => Number(r.segel) === 1);
            const ribbonHtml = allSegel ? '<div class="ribbon-segel">SEGEL</div>' : '';
            const editButtonHtml = isSealed ? '' : `<i class="fas fa-pen icon-btn" title="Edit" onclick="event.stopPropagation(); editCartonGlobal('${packpksAttr}')"></i>`;
            
            const actionButtonHtml = isSealed
                ? `<button class="btn btn-outline-secondary"
                    onclick="event.stopPropagation(); unsealCarton('${packpksAttr}')">
                    <i class="fas fa-unlock me-1"></i>Unseal
                </button>`
                : `<button class="btn ${canSeal ? 'btn-dark' : 'btn-outline-secondary'}" ${canSeal ? '' : 'disabled'}
                    onclick="event.stopPropagation(); sealCarton('${packpksAttr}')">Seal</button>`;
            
            return `
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="packing-card" data-packpks="${packpksAttr}" data-sealed="${isSealed ? 1 : 0}"
                        onclick="onPackingCardClick(event, this)">
                        ${ribbonHtml}
            
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="ctn-code">${first.carton ?? '-'}</span>
                                <span class="badge-soft ${compositionClass}">${compositionLabel}</span>
                                <span class="badge-status ${status.key}">${status.label}</span>
                            </div>
                            <div class="d-flex gap-2">
                                ${editButtonHtml}
                            </div>
                        </div>
            
                        <div class="subline mb-1">${subline}</div>
            
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="progress-main flex-grow-1">
                                <span class="bar" style="width:${Math.min(100, pct)}%; background:${barColor};"></span>
                            </div>
                            <div class="text-nowrap" style="font-size:12.5px;">
                                <strong>${totalActual}</strong> / ${totalPlan} pcs
                                <span class="text-muted">${pct}%</span>
                            </div>
                        </div>
            
                        <div class="packing-card-sizes">${sizeRows}</div>
            
                        <div class="card-barcode">
                            <i class="fas fa-barcode me-1"></i>${first.nobar ? first.nobar : '<span class="text-muted">Belum ada barcode</span>'}
                        </div>
            
                        <div class="card-actions">
                            ${actionButtonHtml}
                        </div>
                    </div>
                </div>
            `;
        }

        function onPackingCardClick(e, cardEl) {
            if ($(e.target).closest('.icon-btn, .card-actions, button, a').length) {
                return;
            }

            const $card = $(cardEl);
            const wasSelected = $card.hasClass('selected');

            $card.toggleClass('selected');

            if (!wasSelected) {
                const selectedCards = $('.packing-card.selected');

                if (selectedCards.length > 1) {
                    const sealedValues = new Set(
                        selectedCards.map(function () { return $(this).data('sealed'); }).get()
                    );

                    if (sealedValues.size > 1) {
                        showToast('warning', 'Tidak bisa memilih carton dengan status Segel berbeda secara bersamaan. Pilih carton yang statusnya SAMA saja (semua sudah Segel, atau semua belum Segel).');
                        $card.removeClass('selected');
                        updateSelectionGlobal();
                        return;
                    }
                }
            }

            updateSelectionGlobal();
        }



        // Mark packed -- sekarang menerima STRING packpk dipisah koma (bisa
        // lebih dari 1 kalau carton Mixed), reuse endpoint packing.update-ctn
        // dengan pola yang sama seperti bulkActualCtn (bulk, bukan 1 packpk).
        function markCartonPacked(packpksCsv) {
            $.ajax({
                url: "{{ route('packing.update-ctn') }}",
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
                window.location.href = "{{ route('packing.index') }}";
            }
        }

        function updateSelectionGlobal() {
            const selectedCards = $('.packing-card.selected');
            const packpks = [];
        
            selectedCards.each(function () {
                String($(this).data('packpks') || '').split(',').forEach(function (p) {
                    if (p !== '') packpks.push(Number(p));
                });
            });
        
            window.selectedPackpksGlobal = packpks;
            $('#selectedCountGlobal').text(selectedCards.length);
        
            if (selectedCards.length === 0) {
                $('#stickTopBarGlobal').hide();
                return;
            }
            $('#stickTopBarGlobal').show();
        
            const selectedRows = (window.lastPackingRows || []).filter(r => packpks.includes(r.packpk));
        
            const hasSegel = selectedRows.some(r => Number(r.segel) === 1);
            if (hasSegel) {
                $('#btnBukaSegelGlobal').removeClass('d-none');
                $('#btnBulkSegelCtnGlobal, #btnBulkActualCtnGlobal, #btnBulkDeleteActualCtnGlobal, #btnBulkCopyGlobal, #btnBulkDeleteGlobal').addClass('d-none');
                return;
            }
        
            $('#btnBukaSegelGlobal').addClass('d-none');
            $('#btnBulkActualCtnGlobal, #btnBulkDeleteActualCtnGlobal, #btnBulkCopyGlobal, #btnBulkDeleteGlobal').removeClass('d-none');
        
            const allComplete = selectedRows.length > 0 && selectedRows.every(isRowComplete);
            $('#btnBulkSegelCtnGlobal').toggleClass('d-none', !allComplete);
        }
        
        function closeMenuGlobal() {
            $('.packing-card').removeClass('selected');
            updateSelectionGlobal();
        }

        function bulkSegelCtnGlobal() { openSegelModalGlobal(1); }
        function bulkBukaSegelGlobal() { openSegelModalGlobal(0); }

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
        window.addEventListener('scroll', syncStickyBarGlobalPosition, { passive: true });
        window.addEventListener('resize', syncStickyBarGlobalPosition);
        $(function () {
            syncStickyBarGlobalPosition();
        });
    </script>
@endsection
