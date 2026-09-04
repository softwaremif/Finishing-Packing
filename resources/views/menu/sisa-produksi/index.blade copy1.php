@extends('layout.main')

@section('css_custom')
    <style>
        /* =========================
           PAGE
        ========================= */
        .page-wrap {
            padding: 16px;
            background: #f9fafb;
        }

        .order-title {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 12px;
        }

        /* =========================
           FILTER BAR
        ========================= */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 12px;
            align-items: center;
        }

        .filter-bar .input-group {
            width: 260px;
        }

        /* =========================
           DATAGRID WRAPPER MODERN
        ========================= */
        .datagrid-wrap {
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #e5e7eb !important;
            background: #fff;
        }

        /* =========================
           HEADER STYLE (CLEAN)
        ========================= */
        .datagrid-header,
        .datagrid-header-inner {
            background: #ffffff !important;
        }

        .datagrid-header .datagrid-cell {
            font-weight: 600;
            font-size: 12px;
            text-align: center !important;
            color: #374151;
        }

        /* header border */
        .datagrid-header-row td {
            border-bottom: 1px solid #e5e7eb !important;
        }

        /* =========================
           ROW STYLE (MODERN FEEL)
        ========================= */
        .datagrid-row {
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s ease;
        }

        .datagrid-row:hover {
            background: #f9fafb !important;
        }

        .datagrid-row-selected {
            background: #dbeafe !important;
        }

        /* =========================
           CELL STYLE
        ========================= */
        .datagrid-cell {
            padding: 6px 8px !important;
            font-size: 12px;
        }

        /* numeric align */
        .datagrid-body td[field="qty"],
        .datagrid-body td[field="transfer"],
        .datagrid-body td[field="balance"] {
            text-align: right !important;
            font-weight: 500;
        }

        /* balance highlight */
        .datagrid-body td[field="balance"] {
            color: #dc2626;
            font-weight: 600;
        }

        /* =========================
           ACTION BUTTON (MODERN)
        ========================= */
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #e0f2fe;
            color: #0369a1; 
            font-size: 13px; 
            transition: 0.2s;
            text-decoration: none; 
        }

        .action-btn:hover {
            background: #bae6fd;
            color: #0c4a6e; 
            transform: scale(1.05);
        }

        .action-btn.action-btn-pdf {
            background: #fee2e2;
            color: #b91c1c;
        }

        .action-btn.action-btn-pdf:hover {
            background: #fecaca;
            color: #7f1d1d;
        }

        /* =========================
           EMPTY STATE
        ========================= */
        .easyui-empty-state {
            /* position: absolute; */
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top:120px;
            background: rgba(255, 255, 255, 0.96);
            z-index: 2;
        }

        .empty-icon img {
            opacity: 0.9;
        }

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

        /* warna per group */
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
    </style>
@endsection


@section('content')
    <div class="page-wrap">

        <div class="order-title">Daftar Data OP</div>

        <!-- FILTER (SINGLE SEARCH + BUYER SELECT) -->
        <div class="filter-bar">

            <!-- GLOBAL SEARCH -->
            <div class="input-group" style="width:260px;">
                <span class="input-group-text search">
                    <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px" height="18px">
                </span>
                <input type="text" class="form-control search" id="globalSearch" placeholder="Search...">
            </div>

            <div class="p-0">
                <input id="filterBuyer" style="width:200px">
            </div>

            <div class="p-0">
                <input id="filterYear" style="width:120px">
            </div>

            <a href="javascript:void(0)" id="btnExportExcel"
                class="btn btn-outline-success btn-sm d-inline-flex align-items-center px-2.5"
                style="border-radius:6px;">
                <i class="fas fa-file-excel me-1"></i> Excel
            </a>

        </div>

        <!-- DATAGRID -->
        <table id="dgOrder" class="easyui-datagrid" style="width:100%;height:600px" url="{{ route('sisa-produksi.list') }}"
            method="get" pagination="true" pageSize="50" pageList="[25,50,100,200,500]" rownumbers="false"
            singleSelect="true" checkOnSelect="true" selectOnCheck="true" fitColumns="false" border="false"
            data-options="
                onLoadSuccess:onLoadTable,
                onBeforeLoad:clearEmptyState,
                onLoadError:onLoadTableError,
                rowStyler:rowStylerOrder
            ">
            <thead>
                <tr class="group-total">
                    <th colspan="14" class="group-info">
                        <div class="group-center">
                            <div class="group-value">Total</div>
                        </div>
                    </th>

                    <th class="group-qty">
                        <div class="group-center">
                            <div class="group-value" id="sumQty">0</div>
                        </div>
                    </th>

                    <th class="group-qty">
                        <div class="group-center">
                            <div class="group-value" id="sumLoading">0</div>
                        </div>
                    </th>

                    <th class="group-qty">
                        <div class="group-center">
                            <div class="group-value" id="sumRQ">0</div>
                        </div>
                    </th>

                    <th class="group-qty">
                        <div class="group-center">
                            <div class="group-value" id="sumTransfer">0</div>
                        </div>
                    </th>

                    <th class="group-qty">
                        <div class="group-center">
                            <div class="group-value" id="sumPacking">0</div>
                        </div>
                    </th>

                    <th class="group-qty">
                        <div class="group-center">
                            <div class="group-value" id="sumBalance">0</div>
                        </div>
                    </th>

                    <th class="group-qty">
                        <div class="group-center">
                            <div class="group-value" id="sumKeluar">0</div>
                        </div>
                    </th>

                    <th class="group-packing">
                        <div class="group-label"></div>
                    </th>

                </tr>
                <tr>
                    <th field="action" width="90" formatter="formatAction" align="center">Aksi</th>
                    <th field="no" width="50" align="center">No</th>
                    <th field="tglin" width="100">Tanggal In</th>
                    <th field="tglout" width="100">Tanggal Out</th>
                    <th field="shipdate1" width="100">Shipdate</th>
                    <th field="POno" width="150">PO No</th>
                    <th field="poref" width="150">License<br>PO Ref</th>
                    <th field="OP" width="150">OP</th>
                    <th field="customer" width="150">Place</th>
                    <th field="season" width="150">Season</th>
                    <th field="buyer" width="150">Buyer</th>
                    <th field="style" width="150">Style</th>
                    <th field="material" width="150">Color</th>
                    <th field="secsz" width="100">Secondary<br>Size</th>

                    <th field="qty" width="150" align="right">Qty</th>
                    <th field="loading" width="150" align="right">Loading <br>(Pcs) </th>
                    <th field="rq" width="150" align="right">R+Q <br>(Pcs) </th>
                    <th field="transfer" width="150" align="right">Transfer<br>(Pcs)</th>
                    <th field="packing" width="150" align="right">Packing<br>(Pcs)</th>
                    <th field="balance" width="150" align="right">Balance<br>(Pcs)</th>
                    <th field="keluar" width="150" align="right">Keluar<br>(Pcs)</th>
                    <th field="silhouette" width="190" align="left">Description</th>

                </tr>
            </thead>
        </table>

    </div>
@endsection
@section('js_custom')
    <script>
        let timer = null;
        let lastQuery = {};
        let restoredState = null;

        /* =========================
           DETEKSI NAVIGASI BACK/FORWARD
        ========================= */
        function isBackForwardNavigation() {
            try {
                const navEntries = performance.getEntriesByType('navigation');
                if (navEntries.length > 0) {
                    return navEntries[0].type === 'back_forward';
                }
                return performance.navigation && performance.navigation.type === 2;
            } catch (e) {
                return false;
            }
        }

        /* INIT */
        $(function() {

            $('#dgOrder').datagrid();

            if (isBackForwardNavigation()) {
                try {
                    restoredState = JSON.parse(sessionStorage.getItem('packingListState') || 'null');
                } catch (e) {
                    restoredState = null;
                }
            }

            bindFilter();

        });

        // ============================================================
        // WARNA BARIS ABU-ABU UNTUK status==2, sama seperti native
        // (native: <tr bgcolor="#CCC"> kalau MAX(bj.status)==2 untuk popk itu)
        // ============================================================
        function rowStylerOrder(index, row) {
            if (Number(row.status) === 2) {
                return 'background-color:#CCCCCC;';
            }
            return '';
        }

        function formatAction(value, row, index) {
            if (!(row.balance > 0)) {
                return '';
            }

            return `
                <div class="d-flex justify-content-center gap-1">
                    <a href="javascript:void(0)"
                    onclick="openTransfer(event, ${row.popk})"
                    class="action-btn"
                    title="Input Transfer">
                        <i class="fas fa-edit"></i>
                    </a>

                    <a href="{{ url('/sisa-produksi') }}/${row.popk}/pdf"
                    target="_blank"
                    class="action-btn action-btn-pdf"
                    title="Cetak PDF">
                        <img src="{{ asset('/public/css/images/pdf.jpg') }}"
                            width="18"
                            height="18">
                    </a>
                </div>
            `;
        }

        function onLoadTableError() {
            console.log('LOAD ERROR');
            showEmptyState('Gagal memuat data', 'Silakan coba lagi');
        }

        /* =========================
           FILTER SYSTEM (SINGLE SEARCH + BUYER)
        ========================= */
        function bindFilter() {

            const initialSearch = restoredState?.search ?? '';
            const initialBuyer  = restoredState?.buyer ?? '';
            const currentYear   = new Date().getFullYear();
            const initialYear   = restoredState?.year ?? currentYear;

            $('#globalSearch').val(initialSearch);

            $('#globalSearch').on('input', function() {
                clearTimeout(timer);
                timer = setTimeout(function() {
                    loadData();
                }, 400);
            });

            $('#filterBuyer').combobox({
                method: 'get',
                url: '{{ route('api.buyer-list') }}',
                valueField: 'buyer',
                textField: 'buyer_name',
                panelHeight: 300,
                editable: true,
                mode: 'local',
                value: initialBuyer,
                onLoadSuccess: function() {
                    if (initialBuyer) {
                        $('#filterBuyer').combobox('setValue', initialBuyer);
                    }
                },
                onSelect: function() {
                    loadData();
                },
                onChange: function() {
                    clearTimeout(timer);
                    timer = setTimeout(function() {
                        loadData();
                    }, 300);
                }
            });

            let years = [];
            for (let y = currentYear; y >= currentYear - 5; y--) {
                years.push({ value: y, text: y });
            }
            years.push({ value: '', text: 'All Year' });

            $('#filterYear').combobox({
                data: years,
                valueField: 'value',
                textField: 'text',
                editable: false,
                panelHeight: 'auto',
                value: initialYear,
                onChange: function(newValue, oldValue) {
                    loadData(1);
                }
            });

            loadDataWith({
                search: initialSearch,
                buyer: initialBuyer,
                year: initialYear,
            }, restoredState?.page || 1);
        }

        function loadData(page = null) {

            let buyer = $('#filterBuyer').combobox('getValue');
            let year = $('#filterYear').combobox('getValue');

            let query = {
                search: $('#globalSearch').val(),
                buyer: buyer,
                year: year
            };

            if (!page && JSON.stringify(query) === JSON.stringify(lastQuery)) {
                return;
            }

            loadDataWith(query, page);
        }

        function loadDataWith(query, page = null) {
            lastQuery = query;

            clearEmptyState();

            $('#dgOrder').datagrid('options').queryParams = query;

            if (page && page > 1) {
                $('#dgOrder').datagrid('getPager').pagination('select', page);
            } else {
                $('#dgOrder').datagrid('load', query);
            }
        }


        function clearEmptyState() {
            $('#dgOrder').datagrid('getPanel')
                .find('.datagrid-view2 .easyui-empty-state')
                .remove();
        }

        function showEmptyState(title, subtitle) {
            let panel = $('#dgOrder').datagrid('getPanel');
            let body = panel.find('.datagrid-view2 .datagrid-body');
            panel.find('.easyui-empty-state').remove();
            body.append(`
                <div class="easyui-empty-state">
                    <div style="text-align:center">
                        <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="180">
                        <div style="margin-top:8px;font-weight:600;">${title}</div>
                        <div style="font-size:12px;color:#9ca3af;">${subtitle}</div>
                    </div>
                </div>
            `);
        }

        function fillSummary(summary) {
            const s = summary || {};
            const fmt = (v) => Number(v || 0).toLocaleString('id-ID');

            $('#sumQty').text(fmt(s.qty));
            $('#sumLoading').text(fmt(s.loading));
            $('#sumRQ').text(fmt(s.rq));
            $('#sumTransfer').text(fmt(s.transfer));
            $('#sumPacking').text(fmt(s.packing));
            $('#sumBalance').text(fmt(s.balance));
            $('#sumKeluar').text(fmt(s.keluar));
        }

        function onLoadTable(data) {
            const rows = data.rows || [];

            const dg = $('#dgOrder');

            dg.datagrid('clearChecked');
            dg.datagrid('clearSelections');

            fillSummary(data.summary);

            if (!rows.length) {
                showEmptyState('No Data Found', 'Try changing filter');
            } else {
                clearEmptyState();
            }
        }

        function openTransfer(e, popk) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            saveListState();

            let url = "{{ url('/sisa-produksi/input') }}/" + popk;
            window.location.href = url;
        }

        function saveListState() {
            let pager = $('#dgOrder').datagrid('getPager');
            let pageNumber = pager.pagination('options').pageNumber;

            let state = {
                search: $('#globalSearch').val(),
                buyer: $('#filterBuyer').combobox('getValue'),
                year: $('#filterYear').combobox('getValue'),
                page: pageNumber
            };

            sessionStorage.setItem('packingListState', JSON.stringify(state));
        }

    </script>
    <script>
        document.getElementById('btnExportExcel')?.addEventListener('click', function () {
            const params = new URLSearchParams({
                search: $('#globalSearch').val() || '',
                buyer: $('#filterBuyer').combobox('getValue') || '',
                year: $('#filterYear').combobox('getValue') || '',
            });
    
            window.open("{{ route('sisa-produksi.export-excel') }}?" + params.toString(), '_blank');
        });
    </script>
@endsection