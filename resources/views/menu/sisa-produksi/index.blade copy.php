@extends('layout.main')

@section('css_custom')
    <style>
        /* ============================================================
           Style SPESIFIK halaman sisa-produksi — tidak ditangani generic
           oleh komponen table-default.
           ============================================================ */
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
            color: #dc2626;
            font-weight: 600;
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
                pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true" checkOnSelect="true"
                selectOnCheck="true" fitColumns="false" border="false"
                data-options="
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
                        <th field="tglin" formatter="formatDate" width="100">Tanggal In</th>
                        <th field="tglout" formatter="formatDate" width="100">Tanggal Out</th>
                        <th field="shipdate1" formatter="formatDate" width="100">Shipdate</th>
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
        </x-table-default>
    </div>
@endsection

@section('js_custom')
    <script>
        // ============================================================
        // DETEKSI NAVIGASI BACK/FORWARD (sama seperti sebelumnya)
        // ============================================================
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

        // ============================================================
        // RESTORE STATE — komponen table-default tidak punya mekanisme
        // ini bawaan, ditangani manual di level halaman.
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

        function saveListState() {
            let pager = $('#dgOrder').datagrid('getPager');
            let pageNumber = pager.pagination('options').pageNumber;

            let state = {
                search: $('#dgOrder_filterbar [data-dg-filter="search"]').val(),
                buyer: $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('getValue'),
                year: $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('getValue'),
                page: pageNumber
            };

            sessionStorage.setItem('packingListState', JSON.stringify(state));
        }

        $(function () {
            let saved = isBackForwardNavigation() ? getSavedListState() : null;

            if (saved) {
                $('#dgOrder_filterbar [data-dg-filter="search"]').val(saved.search || '');
                $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('setValue', saved.buyer || '');
                $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('setValue', saved.year ?? new Date().getFullYear());
            }

            // Timpa ulang onLoadSuccess: komponen generic sudah pasang
            // versi bawaannya (cuma empty-state polos), di sini ditambah
            // fillSummary() yang khusus halaman ini.
            $('#dgOrder').datagrid({
                onLoadSuccess: onLoadTable
            });

            if (window.EasyuiDG) {
                window.EasyuiDG.reload('dgOrder', saved?.page || 1);
            }
        });

        window.addEventListener('pageshow', function (event) {
            if (event.persisted && window.EasyuiDG) {
                window.EasyuiDG.reload('dgOrder');
            }
        });

        // ============================================================
        // WARNA BARIS ABU-ABU UNTUK status==2 (sama seperti native)
        // ============================================================
        function rowStylerOrder(index, row) {
            if (Number(row.status) === 2) {
                return 'background-color:#CCCCCC;';
            }
            return '';
        }

        // ============================================================
        // KOLOM AKSI — hanya tampil kalau ada balance
        // ============================================================
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
                        <img src="{{ asset('/public/css/images/pdf.jpg') }}" width="18" height="18">
                    </a>
                </div>
            `;
        }

        function formatDate(value) {
            if (!value) {
                return '<span class="dg-empty-cell">-</span>';
            }
        
            let datePart = String(value).split(' ')[0];
            let parts = datePart.split('-');
        
            if (parts.length !== 3) {
                return value; 
            }
        
            let [year, month, day] = parts;
            return `${day}/${month}/${year}`;
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

        // ============================================================
        // RINGKASAN (Total row) + EMPTY STATE — versi lengkap khusus
        // halaman ini, menimpa versi generic dari komponen.
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

            $('#dgOrder').datagrid('clearChecked');
            $('#dgOrder').datagrid('clearSelections');

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
    </script>
    <script>
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