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

        
        .datagrid-body td[field="qty"],
        .datagrid-body td[field="transfer"],
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
            vertical-align: middle; /* Memastikan posisi sejajar dengan elemen di sekitarnya */
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

        /* Tombol Khusus PDF */
        .action-btn.action-btn-pdf {
            background-color: #fee2e2 !important; /* Diperkuat agar tidak tertimpa warna dasar */
            color: #b91c1c !important;
        }

        .action-btn.action-btn-pdf:hover {
            background-color: #fecaca !important;
            color: #7f1d1d !important;
        }

        /* Menjaga agar ikon/gambar di dalam tombol presisi di tengah */
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
    </style>
@endsection

@section('content')
    <div class="page-wrap">

        <x-table-default
            id="dgOrder"
            title="Daftar Data OP"
            search
            search-name="search"
            search-placeholder="Search..."
            buyer
            buyer-name="buyer"
            buyer-url="{{ route('api.buyer-list') }}"
            buyer-value-field="buyer"
            buyer-text-field="buyer_name"
            buyer-mode="remote"
            year
            year-name="year"
        >
            <table id="dgOrder" class="easyui-datagrid" style="width:100%;height:600px" url="{{ route('packing.list') }}"
                method="get" pagination="true" pageSize="50" pageList="[25,50,100,200,500]" rownumbers="false"
                singleSelect="true" fitColumns="false" border="false">
                <thead>
                    <tr>
                        <th field="action" width="90" formatter="formatAction" align="center" rowspan="2">Aksi</th>
                        <th field="no" width="50" align="center" rowspan="2">No</th>
                        <th field="POno" width="150" rowspan="2">PO No</th>
                        <th field="OP" width="150" rowspan="2" formatter="formatOpWithMif">OP</th>
                        <th field="poref" width="130" rowspan="2">License<br>PO Ref</th>
                        <th field="season" width="120" rowspan="2">Season</th>
                        <th field="buyer" width="150" rowspan="2">Buyer</th>
                        <th field="style" width="150" rowspan="2">Style</th>
                        <th field="qty" width="90" rowspan="2" align="right" formatter="formatNumber">Qty</th>
                        <th width="90" colspan="3" align="right">Packing /Pcs</th>
                        <th width="90" colspan="3" align="right">CTN</th>
                    </tr>
                    <tr>
                        <th field="packing_qty_plan" width="100" align="right" formatter="formatNumber">Plan</th>
                        <th field="packing_qty" width="100" align="right" formatter="formatNumber">Actual</th>
                        <th field="packing_qty_balance" width="100" align="right" formatter="formatBalanceCell">Balance</th>
                        <th field="ctn" width="80" align="right" formatter="formatNumber">Plan</th>
                        <th field="packing_ctn" width="80" align="right" formatter="formatNumber">Actual</th>
                        <th field="ctn_balance" width="80" align="right" formatter="formatBalanceCell">Balance</th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>
@endsection

@section('js_custom')
    <script>
        const isSuperUser = @json(session('guserpk') === 34);

        // ============================================================
        // FILTER STATE (index)
        // ============================================================
        function getSavedListState() {
            let raw = sessionStorage.getItem('packingListState');
            if (!raw) return null;
            sessionStorage.removeItem('packingListState');
            try { return JSON.parse(raw); } catch (e) { return null; }
        }
        
        function savePackingNavState() {
            let pager = $('#dgOrder').datagrid('getPager');
            let pageNumber = pager.pagination('options').pageNumber;
        
            let state = {
                search: $('#dgOrder_filterbar [data-dg-filter="search"]').val(),
                buyer: $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('getValue'),
                year: $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('getValue'),
                page: pageNumber,
            };
        
            sessionStorage.setItem('packingListState', JSON.stringify(state));
        }

        let restoredDgOrderPage = null;
        $(function () {
            let saved = getSavedListState();
        
            if (saved) {
                $('#dgOrder_filterbar [data-dg-filter="search"]').val(saved.search || '');
                $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('setValue', saved.buyer || '');
                $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('setValue', saved.year ?? new Date().getFullYear());
            }
        
            $('#dgOrder').datagrid({ onLoadSuccess: onLoadTable });
        
            if (saved?.modalOp) {
                restoredDgOrderPage = saved.page || 1;
                openPackingDetailModal(null, saved.modalPo, saved.modalOp, saved.modalPoref, saved.modalMif);
            } else if (window.EasyuiDG) {
                window.EasyuiDG.reload('dgOrder', saved?.page || 1);
            }
        });

        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) return;
        
            if (window.EasyuiDG) {
                window.EasyuiDG.reload('dgOrder');
            }
            if ($('#packingDetailModal').hasClass('show')) {
                reloadPackingDetailModal();
            }
        });
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
            let cls = v < 0 ? 'color:#dc3545;font-weight:bold' : (v > 0 ? 'color:#198754;font-weight:bold' : 'color:#94a3b8');
            let text = v > 0 ? ('+' + formatNumber(v)) : formatNumber(v);
            return `<span style="${cls}">${text}</span>`;
        }

        function formatOpWithMif(value, row) {
            if (!isSuperUser) return value ?? '';
            return `
                ${value ?? ''}
                <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:10px; margin-left:4px;">
                    mif ${row.mif}
                </span>
            `;
        }
        // ============================================================
        // KOLOM AKSI INDEX — cuma ikon mata, buka modal rincian PO+OP.
        // ============================================================
        function formatAction(value, row, index) {
            const pdfUrl = "{{ route('laporan.pdf.global') }}"
                + "?po=" + encodeURIComponent(row.POno ?? '')
                + "&op=" + encodeURIComponent(row.OP)
                + "&poref=" + encodeURIComponent(row.poref ?? '')
                + "&mif=" + row.mif;
        
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
            if (e) { e.preventDefault(); e.stopPropagation(); }

            savePackingNavState();

            let url = "{{ route('packing.input.global') }}"
                + "?po=" + encodeURIComponent(po ?? '')
                + "&op=" + encodeURIComponent(op)
                + "&poref=" + encodeURIComponent(poref ?? '')
                + "&mif=" + mif;

            window.location.href = url;
        }

        // ============================================================
        // onLoadSuccess INDEX — summary + empty state
        // ============================================================
        function onLoadTable(data) {
            const rows = data.rows || [];
            rows.forEach((row, index) => { row.no = index + 1; });

            const fmt = (v) => Number(v || 0).toLocaleString('id-ID');

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
    </script>
@endsection