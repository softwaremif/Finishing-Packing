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
    </style>
@endsection

@section('content')
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
                        <!-- Filter -->
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="input-group" style="width:260px;">
                                <span class="input-group-text search">
                                    <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18"
                                        alt="Search">
                                </span>
                                <input type="text" class="form-control search" id="searchPackingGlobal"
                                    placeholder="Search Barcode / No CTN">
                            </div>
                            <input id="filterSizeGlobal" style="width:170px;">
                            <input id="filterColorGlobal" style="width:170px;">
                            <input id="filterSecszGlobal" style="width:170px;">
                        </div>
                        <!-- Button -->
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-dark btn-sm d-flex align-items-center fw-semibold"
                                style="font-size:12px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                                onclick="openUrutkanCtnModal()">
                                <i class="fas fa-sort-numeric-down me-1"></i>
                                Urutkan CTN
                            </button>
                            <button class="btn btn-dark btn-sm d-flex align-items-center fw-semibold"
                                style="font-size:12px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                                onclick="openPackingModal()">
                                <i class="fas fa-plus me-1"></i>
                                Add Packing
                            </button>
                        </div>
                    </div>
                </div>
                <table id="dgPackingGlobal" style="width:100%;height:650px"></table>
            </div>
        </div>



    </div>
@endsection

@section('js_custom')
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
        window.colorListGlobal   = @json($colorList ?? []);
        window.secszListGlobal   = @json($secszList ?? []);

        $(function() {
            $('#dgPackingGlobal').datagrid({
                url: "{{ route('packing.list.detail.global') }}",
                queryParams: {
                    po: @json($po),
                    op: @json($op),
                    poref: @json($poref ?? null),
                    mif: @json($mif)
                },
                method: 'get',
                pagination: true,
                pageSize: 50,
                pageList: [25, 50, 100, 200, 500],
                singleSelect: false,
                checkOnSelect: true,
                selectOnCheck: true,
                fitColumns: false,
                rownumbers: false,
                border: false,
                columns: [[
                    { field: 'action', title: 'Aksi', width: 50, align: 'center', rowspan: 2, formatter: formatAction },
                    { field: 'segel', title: 'Status', width: 80, align: 'center', rowspan: 2, formatter: formatSegel },
                    { field: 'ck', checkbox: true, rowspan: 2 },
                    { field: 'material', title: 'Color', width: 90, rowspan: 2 },
                    { field: 'secsz', title: 'Sec Size', width: 90, rowspan: 2 },
                    { field: 'nobar', title: 'No Barcode', width: 130, rowspan: 2 },
                    { field: 'carton', title: 'No CTN', width: 100, rowspan: 2 },
                    { field: '_pa', title: 'P/A', width: 50, align: 'center', rowspan: 2, formatter: formatPA },
                    { title: 'Size', align: 'center', colspan: {{ count($activeSizes) }} },
                    { field: 'total', title: 'Total<br>(Pcs)', width: 80, align: 'center', rowspan: 2, formatter: formatTotal },
                    { field: 'balance', title: 'Balance<br>(Pcs)', width: 80, align: 'center', rowspan: 2, formatter: formatBalance },
                    { field: 'nw', title: 'N.W', width: 70, rowspan: 2 },
                    { field: 'gw', title: 'G.W', width: 70, rowspan: 2 },
                    { field: 'meas', title: 'Meas CTN', width: 100, rowspan: 2 },
                    { field: 'keterangan', title: 'Keterangan', width: 200, rowspan: 2 }
                ], [
                    @foreach ($activeSizes as $i => $sz)
                        { field: 'qty{{ $i }}', title: @json($sz), align: 'center', width: 70, formatter: window['formatSize{{ $i }}'] },
                    @endforeach
                ]],
                onLoadSuccess: onLoadPackingGlobal,
                rowStyler: rowStylerPacking
            });

            initFilterColorGlobal();
            initFilterSizeGlobalCombobox();
            initFilterSecszGlobal();
        });

        function reloadPackingGlobal() {
            $('#dgPackingGlobal').datagrid('load', {
                po: @json($po),
                op: @json($op),
                poref: @json($poref ?? null),
                mif: @json($mif),
                search: $('#searchPackingGlobal').val(),
                size: $('#filterSizeGlobal').combobox('getValue'),
                color: $('#filterColorGlobal').combobox('getValue'),
                secsz: $('#filterSecszGlobal').combobox('getValue')
            });
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
            let secszData = [{ value: '', text: 'Semua Sec Size' }];
            (window.secszListGlobal || []).forEach(function (s) {
                secszData.push({ value: s, text: s });
            });
    
            $('#filterSecszGlobal').combobox({
                data: secszData,
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

        let packingGlobalSearchTimer = null;
        $('#searchPackingGlobal').on('keyup', function() {
            clearTimeout(packingGlobalSearchTimer);
            packingGlobalSearchTimer = setTimeout(reloadPackingGlobal, 300);
        });

        function onLoadPackingGlobal(data) {
            const rows = data.rows || [];
            let panel = $('#dgPackingGlobal').datagrid('getPanel');
            let body  = panel.find('.datagrid-view2 .datagrid-body');
            panel.find('.easyui-empty-state').remove();
        
            if (!rows.length) {
                body.append(`
                    <div class="easyui-empty-state">
                        <div style="text-align:center;padding:40px">
                            <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="180">
                            <div style="margin-top:8px;font-weight:600;">No Data Found</div>
                        </div>
                    </div>
                `);
                return;
            }
        
            mergeGlobalGroupCells(rows);
        }

        function mergeGlobalGroupCells(rows) {
            mergeRunsByField(rows, 'secsz');
            mergeRunsByField(rows, 'material');
        }
        
        function mergeRunsByField(rows, field) {
            let i = 0;
            while (i < rows.length) {
                let j = i + 1;
                while (j < rows.length && rows[j][field] === rows[i][field]) {
                    j++;
                }
        
                const runLength = j - i;
                if (runLength > 1) {
                    $('#dgPackingGlobal').datagrid('mergeCells', {
                        index: i,
                        field: field,
                        rows: runLength
                    });
                }
        
                i = j;
            }
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

        function openUrutkanCtnModal() {
            showToast('info', 'Fitur Urutkan CTN untuk mode Global belum tersedia.');
        }

        function openPackingModal() {
            showToast('info', 'Fitur Add Packing untuk mode Global belum tersedia.');
        }
    </script>
@endsection
