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

        .datagrid-body td[field="qty"],
        .datagrid-body td[field="loading"],
        .datagrid-body td[field="rq"],
        .datagrid-body td[field="transfer"],
        .datagrid-body td[field="packing"],
        .datagrid-body td[field="balance"],
        .datagrid-body td[field="keluar"] {
            text-align: right !important;
            font-weight: 500;
        }

        .datagrid-body td[field="balance"] {
            font-weight: 500;
        }

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
            border: none;
            outline: none;
            background-clip: padding-box;
            cursor: pointer;
            font-family: inherit;
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

        #sisaDetailModal .modal-dialog {
            max-width: min(1400px, 95vw);
        }

        .datagrid-row-status2 {
            background-color: #CCCCCC !important;
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
            buyer-mode="local"
            year
            year-name="year"
        >
            <x-slot name="filters">
                <a href="javascript:void(0)" id="btnExportExcel"
                    class="btn btn-outline-success btn-sm d-inline-flex align-items-center px-2.5"
                    style="border-radius:6px;">
                    <i class="fas fa-file-excel me-1"></i> Excel
                </a>
            </x-slot>

            <table id="dgOrder" class="easyui-datagrid" style="width:100%;height:600px"
                url="{{ route('sisa-produksi.list') }}" method="get" pagination="true" pageSize="50"
                pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true" fitColumns="false"
                border="false">
                <thead>
                    <tr>
                        <th field="action" width="60" formatter="formatAction" align="center" rowspan="2">Aksi</th>
                        <th field="no" width="50" align="center" rowspan="2">No</th>
                        <th field="POno" width="150" rowspan="2">PO No</th>
                        <th field="OP" width="150" rowspan="2" formatter="formatOP">OP</th>
                        <th field="season" width="120" rowspan="2">Season</th>
                        <th field="buyer" width="150" rowspan="2">Buyer</th>
                        <th field="style" width="150" rowspan="2">Style</th>
                        <th colspan="6">Pcs</th>
                    </tr>
                    <tr>
                        <th field="qty" width="110" align="right" formatter="formatNumber">Qty</th>
                        <th field="finishing" width="120" align="right" formatter="formatNumber">Transfer To<br>Finishing</th>
                        <th field="transfer" width="110" align="right" formatter="formatNumber">Polibag</th>
                        <th field="packing" width="110" align="right" formatter="formatNumber">Packing</th>
                        <th field="balance" width="110" align="right" formatter="formatBalanceCell">Balance</th>
                        <th field="keluar" width="110" align="right" formatter="formatNumber">Keluar</th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>
    @include('menu.sisa-produksi.modal-material-list')
@endsection

@section('js_custom')
    <script>
        // ============================================================
        // SIMPAN / AMBIL STATE (filter index + PO/OP modal yang terbuka)
        // ============================================================
        function formatOP(value, row) {
            const badgeColor = Number(row.mif) === 1 ? '#0369a1' : '#055160';
            return `
                ${value ?? ''}
                <span class="badge" style="background:${badgeColor};color:#fff;font-size:10px;margin-left:4px;">
                    mif ${row.mif}
                </span>
            `;
        }

        function getSavedListState() {
            let raw = sessionStorage.getItem('sisaProduksiListState');
            if (!raw) return null;
            sessionStorage.removeItem('sisaProduksiListState');
            try { return JSON.parse(raw); } catch (e) { return null; }
        }

        function saveListState() {
            let pager = $('#dgOrder').datagrid('getPager');
            let pageNumber = pager.pagination('options').pageNumber;
            let state = {
                search: $('#dgOrder_filterbar [data-dg-filter="search"]').val(),
                buyer: $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('getValue'),
                year: $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('getValue'),
                page: pageNumber,
                modalPo: sisaModalPo,
                modalOp: sisaModalOp,
                modalMif: sisaModalMif // BARU
            };
            sessionStorage.setItem('sisaProduksiListState', JSON.stringify(state));
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
                openSisaDetailModal(null, saved.modalPo, saved.modalOp, saved.modalMif);
            } else if (window.EasyuiDG) {
                window.EasyuiDG.reload('dgOrder', saved?.page || 1);
            }
        });

        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) return;

            if (window.EasyuiDG) {
                window.EasyuiDG.reload('dgOrder');
            }
            if ($('#sisaDetailModal').hasClass('show')) {
                reloadSisaDetailModal();
            }
        });

        document.getElementById('sisaDetailModal').addEventListener('hidden.bs.modal', function () {
            sessionStorage.removeItem('sisaProduksiListState');

            if (!window.EasyuiDG) return;

            if (restoredDgOrderPage) {
                window.EasyuiDG.reload('dgOrder', restoredDgOrderPage);
                restoredDgOrderPage = null;
            } else {
                window.EasyuiDG.reload('dgOrder');
            }
        });

        // ============================================================
        // WARNA BARIS ABU-ABU UNTUK status==2 (dipakai di MODAL)
        // ============================================================
        function rowStylerOrder(index, row) {
            if (Number(row.status) === 2) {
                return 'background-color:#CCCCCC;';
            }
            return '';
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
            let color = v < 0 ? '#dc2626' : (v > 0 ? '#16a34a' : '#94a3b8');
            let text = v > 0 ? ('+' + formatNumber(v)) : formatNumber(v);
            return `<span style="color:${color};font-weight:600;">${text}</span>`;
        }

        // ============================================================
        // KOLOM AKSI INDEX — cuma ikon mata, buka modal rincian PO+OP.
        // ============================================================
        function formatAction(value, row, index) {
            return `
                <a href="javascript:void(0)"
                    onclick='openSisaDetailModal(event, ${JSON.stringify(row.POno)}, ${JSON.stringify(row.OP)}, ${row.mif})'
                    class="action-btn"
                    title="Lihat Detail">
                    <i class="fas fa-eye"></i>
                </a>
            `;
        }

        // ============================================================
        // onLoadSuccess INDEX — summary + empty state
        // ============================================================
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
            rows.forEach((row, index) => { row.no = index + 1; });

            fillSummary(data.summary);

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

        // ============================================================
        // MODAL: buka rincian PO+OP
        // ============================================================
        let sisaModalPo  = null;
        let sisaModalOp  = null;
        let sisaModalMif = null; // BARU
        
        function openSisaDetailModal(e, po, op, mif) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
        
            sisaModalPo  = po;
            sisaModalOp  = op;
            sisaModalMif = mif; // BARU
        
            const poLabel = (po === null || po === undefined || po === '') ? '(PO Kosong)' : po;
            $('#sisaModalPO').text(poLabel);
            $('#sisaModalOP').text(op);
            $('#sisaModalBuyer').text('');
        
            const modalEl = document.getElementById('sisaDetailModal');
            new bootstrap.Modal(modalEl).show();
        
            $(modalEl).one('shown.bs.modal', function () {
                $('#dgSisaDetail').datagrid('resize');
            });
        
            if (!$('#dgSisaDetail').data('datagrid')) {
                $('#dgSisaDetail').datagrid();
            } else {
                $('#dgSisaDetail').datagrid('loadData', { total: 0, rows: [] });
            }
        
            // BARU: kirim mif -- WAJIB, karena detailByPoOp() sekarang
            // 'mif' => 'required'.
            $('#dgSisaDetail').datagrid('load', { po: po ?? '', op: op, mif: mif });
        }
        
        function reloadSisaDetailModal() {
            if (!sisaModalOp) return;
            $('#dgSisaDetail').datagrid('load', { po: sisaModalPo ?? '', op: sisaModalOp, mif: sisaModalMif });
        }

        // ============================================================
        // KOLOM AKSI DI DALAM MODAL — Input Transfer (kalau balance>0) + PDF
        // ============================================================
        function formatDetailAction(value, row, index) {
            if (!(row.balance > 0)) {
                return `
                    <a href="{{ url('/sisa-produksi') }}/${row.popk}/pdf" target="_blank"
                        class="action-btn action-btn-pdf" title="Cetak PDF">
                        <img src="{{ asset('/public/css/images/pdf.jpg') }}" width="18" height="18">
                    </a>
                `;
            }
            return `
                <div class="d-flex justify-content-center gap-1">
                    <a href="javascript:void(0)" onclick="openTransfer(event, ${row.popk}, ${row.mif})"
                        class="action-btn" title="Input Transfer">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="{{ url('/sisa-produksi') }}/${row.popk}/pdf" target="_blank"
                        class="action-btn action-btn-pdf" title="Cetak PDF">
                        <img src="{{ asset('/public/css/images/pdf.jpg') }}" width="18" height="18">
                    </a>
                </div>
            `;
        }

        function openTransfer(e, popk, mif) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            saveListState();
            let url = "{{ url('/sisa-produksi/input') }}/" + popk + "?mif=" + mif;
            window.location.href = url;
        }

        // ============================================================
        // onLoadSuccess MODAL — summary khusus PO+OP ini
        // ============================================================
        function onSisaDetailLoad(data) {
            const rows = data.rows || [];
            const fmt = (v) => Number(v || 0).toLocaleString('id-ID');

            $('#modalSumQty').text(fmt(data.summary?.qty));
            $('#modalSumLoading').text(fmt(data.summary?.loading));
            $('#modalSumRQ').text(fmt(data.summary?.rq));
            $('#modalSumTransfer').text(fmt(data.summary?.transfer));
            $('#modalSumPacking').text(fmt(data.summary?.packing));
            $('#modalSumBalance').text(fmt(data.summary?.balance));
            $('#modalSumKeluar').text(fmt(data.summary?.keluar));
            $('#sisaModalBuyer').text(rows.length ? (rows[0].buyer ?? '-') : '-');

            let panel = $('#dgSisaDetail').datagrid('getPanel');
            let body = panel.find('.datagrid-view2 .datagrid-body');
            panel.find('.easyui-empty-state').remove();
            if (!rows.length) {
                body.append(`
                    <div class="easyui-empty-state">
                        <div style="text-align:center">
                            <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="180">
                            <div style="margin-top:8px;font-weight:600;">No Data Found</div>
                        </div>
                    </div>
                `);
            }
        }

        // ============================================================
        // EXPORT EXCEL
        // ============================================================
        document.getElementById('btnExportExcel')?.addEventListener('click', function () {
            const params = new URLSearchParams({
                search: $('#dgOrder_filterbar [data-dg-filter="search"]').val() || '',
                buyer: $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('getValue') || '',
                year: $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('getValue') || '',
            });
            window.open("{{ route('sisa-produksi.export-excel') }}?" + params.toString(), '_blank');
        });
    </script>
@endsection