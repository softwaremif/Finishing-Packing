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
        .datagrid-body td[field="checked_qty"],
        .datagrid-body td[field="packing_qty"],
        .datagrid-body td[field="balance"],
        .datagrid-body td[field="ctn"] {
            text-align: right !important;
        }

        .datagrid-header-rownumber,
        .datagrid-cell-rownumber {
            display: none;
        }

        .datagrid-td-rownumber {
            background-color: #ffffff !important;
        }

        .datagrid-cell-rownumber {
            text-align: center !important;
            font-weight: 600;
        }

        .datagrid-header .datagrid-cell-group {
            font-weight: bold !important;
            padding-top: 16px;
        }

        .sticky-order-bar {
            display: none;
            position: fixed;
            top: 58px;
            left: 0;
            right: 0;
            z-index: 1030;
            background: #359DD9;
            color: #fff;
        }

        .sticky-order-inner {
            height: 44px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 16px;
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

        .action-btn.action-btn-pdf {
            background: #fee2e2;
            color: #b91c1c;
        }

        .action-btn.action-btn-pdf:hover {
            background: #fecaca;
            color: #7f1d1d;
        }

        .action-btn-disabled {
            background: #f1f5f9 !important;
            color: #94a3b8 !important;
            cursor: not-allowed !important;
            pointer-events: none;
        }

        .datagrid-row-segel-complete {
            background: rgba(25, 183, 21, 0.15) !important;
        }

        .datagrid-row-segel-complete:hover {
            background: rgba(25, 183, 21, 0.25) !important;
        }

        .datagrid-row-segel-complete .datagrid-cell-check {
            pointer-events: none;
            opacity: 0.4;
        }

        .datagrid-row-status4 {
            background: rgba(148, 163, 184, 0.18) !important;
        }

        .datagrid-row-status4:hover {
            background: rgba(148, 163, 184, 0.28) !important;
        }
    </style>
@endsection

@section('content')
    <div class="page-wrap">
        <!-- STICKY SELECTION BAR -->
        <div id="stickTopBar" class="sticky-order-bar">
            <div class="sticky-order-inner">
                <div>
                    <strong><span id="selectedCount">0</span> item terpilih</strong>
                </div>
                <div>
                    <span class="sticky-action d-none" id="btnSegelPacking">Segel Shipment</span>
                    <span class="sticky-action" id="btnInputStatusPacking">Input Status Packing</span>
                    <span class="sticky-action" onclick="closeMenu()">Close</span>
                </div>
            </div>
        </div>

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
                singleSelect="false" checkOnSelect="true" selectOnCheck="true" fitColumns="false" border="false"
                data-options="
                    onCheck:validateOP,
                    onUncheck:updateSelection,
                    onCheckAll:function(rows){
                        validateCheckAll(rows);
                    },
                    onUncheckAll:updateSelection,
                ">
                <thead>
                    <tr class="group-total">
                        <th colspan="16" class="group-info">
                            <div class="group-center">
                                <div class="group-value">Total Summary</div>
                            </div>
                        </th>
                        <th class="group-qty">
                            <div class="group-center">
                                <div class="group-value" id="sumQty">0</div>
                            </div>
                        </th>
                        <th class="group-qty">
                            <div class="group-center">
                                <div class="group-value" id="sumPackingPlan">0</div>
                            </div>
                        </th>
                        <th class="group-qty">
                            <div class="group-center">
                                <div class="group-value" id="sumPackingActual">0</div>
                            </div>
                        </th>
                        <th class="group-qty">
                            <div class="group-center">
                                <div class="group-value" id="sumPackingBalance">0</div>
                            </div>
                        </th>
                        <th class="group-packing">
                            <div class="group-center">
                                <div class="group-value" id="sumCtnPlan">0</div>
                            </div>
                        </th>
                        <th class="group-packing">
                            <div class="group-center">
                                <div class="group-value" id="sumCtnActual">0</div>
                            </div>
                        </th>
                        <th class="group-packing">
                            <div class="group-center">
                                <div class="group-value" id="sumCtnBalance">0</div>
                            </div>
                        </th>
                        <th class="group-packing">
                            <div class="group-label"></div>
                        </th>
                    </tr>
                    <tr>
                        <th field="action" width="70" formatter="formatAction" align="center" rowspan="2">Aksi</th>
                        <th field="segel_status" width="130" align="center" formatter="formatSegelStatus" rowspan="2">
                            Status Shipment</th>
                        <th field="ck" checkbox="true" rowspan="2"></th>
                        <th field="no" width="50" align="center" rowspan="2">No</th>
                        <th field="linenm" width="80" rowspan="2">Line</th>
                        <th colspan="2" align="center">Shipdate</th>
                        <th field="POno" width="150" rowspan="2">PO No</th>
                        <th field="poref" width="150" rowspan="2">License <br> PO Ref</th>
                        <th field="OP" width="150" rowspan="2" formatter="formatPOno">OP</th>
                        <th field="customer" width="150" rowspan="2">Place</th>
                        <th field="season" width="150" rowspan="2">Season</th>
                        <th field="buyer" width="150" rowspan="2">Buyer</th>
                        <th field="style" width="150" rowspan="2">Style</th>
                        <th field="material" width="150" rowspan="2">Color</th>
                        <th field="secsz" width="80" rowspan="2">Secondary<br>Size</th>
                        <th field="qty" width="120" rowspan="2">Qty</th>
                        <th colspan="3" align="center">Packing /Pcs</th>
                        <th colspan="3" align="center">CTN</th>
                        <th field="silhouette" width="190" rowspan="2">Description</th>
                    </tr>
                    <tr>
                        <th field="shipdate1" formatter="formatDate" width="120">Plan</th>
                        <th field="shipdate2" formatter="formatDate" width="120">Actual</th>
                        <th field="packing_qty_plan" width="120" align="right" formatter="formatNumber">Plan</th>
                        <th field="packing_qty" width="120" align="right" formatter="formatNumber">Actual</th>
                        <th field="packing_qty_balance" width="120" align="right"  formatter="formatBalanceCell">Balance</th>
                        <th field="ctn" width="120" align="center">Plan</th>
                        <th field="packing_ctn" width="120" align="center">Actual</th>
                        <th field="ctn_balance" width="120" align="center" formatter="formatBalanceCell">Balance</th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>
    @include('menu.packing.modal-status-packing')
    @include('menu.packing.modal-segel-packing')
@endsection

@section('js_custom')
    <script>
        function formatPOno(value, row) {
            if (!isSuperUser) {
                return value ?? '';
            }
        
            return `
                ${value ?? ''}
                <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:10px; margin-left:4px;">
                    mif ${row.mif}
                </span>
            `;
        }
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
            let saved = getSavedListState();

            if (saved) {
                $('#dgOrder_filterbar [data-dg-filter="search"]').val(saved.search || '');
                $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('setValue', saved.buyer || '');
                $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('setValue', saved.year ?? new Date().getFullYear());
            }

            // Timpa ulang onLoadSuccess: komponen generic sudah pasang
            // versi bawaannya (clearChecked + empty-state polos), di sini
            // kita ganti dengan versi lengkap khusus packing (summary,
            // row styling, sticky bar).
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
        // STATUS SHIPMENT (gabungan Complete/Partial) + tombol Batal
        // ============================================================
        function formatSegelStatus(value, row) {
            if (row.segel_complete == 1) {
                return '<span class="badge bg-success-subtle text-success-emphasis" style="font-size:11px;">Complete</span>';
            }

            const lastPart = getLastSegelPart(row);
            if (lastPart) {
                return '<span class="badge bg-warning-subtle text-warning-emphasis" style="font-size:11px;">' +
                    'Partial Ke-' + lastPart +
                    '</span>';
            }

            return '<span class="text-muted">-</span>';
        }

        function getLastSegelPart(row) {
            if (row.segel_complete == 1) return '10';

            if (row.segel_partial_no) {
                let nums = String(row.segel_partial_no)
                    .split(',')
                    .map(v => parseInt(v.trim(), 10))
                    .filter(v => !isNaN(v))
                    .sort((a, b) => a - b);

                if (nums.length) return String(nums[nums.length - 1]);
            }

            return null;
        }

        const isSuperUser = @json(session('guserpk') === 34);

        function formatAction(value, row, index) {
            let baseUrl = "{{ route('laporan.pdf', ':id') }}".replace(':id', row.popk);
            let pdfUrl = baseUrl + '?gab=' + (row.gabung ?? 0);

            let isComplete = row.segel_complete == 1;
            let lastPart = getLastSegelPart(row);

            let inputBtn = isComplete ? '' : `
                <a href="javascript:void(0)"
                    onclick="openPacking(event, ${row.popk}, ${row.mif})"
                    class="action-btn"
                    title="Input Packing">
                    <i class="fas fa-edit"></i>
                </a>`;

            let cancelBtn = (isSuperUser && lastPart) ? `
                <a href="javascript:void(0)"
                    onclick="confirmCancelSegel(event, ${row.popk}, '${lastPart}')"
                    class="action-btn"
                    style="background:#dcfce7;color:#16a34a;"
                    title="Batalkan Shipment Terakhir">
                    <i class="fas fa-check-circle"></i>
                </a>` : '';

            return `
                <div class="d-flex justify-content-center gap-1">
                    ${inputBtn}
                    ${cancelBtn}
                    <a href="${pdfUrl}"
                        target="_blank"
                        class="action-btn action-btn-pdf"
                        title="Print PDF">
                        <img src="{{ asset('/public/css/images/pdf.jpg') }}" width="18" height="18">
                    </a>
                </div>
            `;
        }

        function confirmCancelSegel(e, popk, part) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            $.messager.confirm(
                'Konfirmasi',
                'Batalkan segel packing terakhir untuk data ini? Carton terkait akan dikembalikan ke status Ready (belum dikirim).',
                function (ok) {
                    if (!ok) return;

                    $.ajax({
                        url: "{{ route('packing.segel.cancel') }}",
                        method: 'POST',
                        data: { popk: popk, part: part },
                        success: function (res) {
                            showToast(res.icon, res.title);
                            window.EasyuiDG.reload('dgOrder');
                        },
                        error: function (xhr) {
                            let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                            showToast(res.icon, res.title);
                        }
                    });
                }
            );
        }

        function openPacking(e, popk, mif) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
        
            saveListState();
        
            let url = "{{ url('/packing/input') }}/" + popk + "?mif=" + mif;
            window.location.href = url;
        }

        // ============================================================
        // SELEKSI CHECKBOX & VALIDASI
        // ============================================================
        function updateSelection() {
            let rows = $('#dgOrder').datagrid('getChecked');
            $('#selectedCount').text(rows.length);
            if (!rows.length) {
                $('#stickTopBar').hide();
                $('#btnSegelPacking').addClass('d-none');
                return;
            }
            $('#stickTopBar').show();
            if (canSegelPacking(rows)) {
                $('#btnSegelPacking').removeClass('d-none');
            } else {
                $('#btnSegelPacking').addClass('d-none');
            }
        }

        function validateOP(index, row) {
            if (row.segel_complete == 1) {
                $.messager.alert('Peringatan', 'Data yang sudah Complete tidak dapat dipilih lagi.', 'warning');
                $('#dgOrder').datagrid('uncheckRow', index);
                updateSelection();
                return;
            }

            let checked = $('#dgOrder').datagrid('getChecked');

            if (checked.length <= 1) {
                updateSelection();
                return;
            }

            let opAwal = checked[0].OP;
            let poAwal = checked[0].POno;

            let bedaOP = checked.some(item => item.OP !== opAwal);
            let bedaPO = checked.some(item => item.POno !== poAwal);

            if (bedaOP || bedaPO) {
                let pesan = '';
                if (bedaOP && bedaPO) {
                    pesan = 'Hanya boleh memilih OP dan PO yang sama.';
                } else if (bedaOP) {
                    pesan = 'Hanya boleh memilih OP yang sama.';
                } else {
                    pesan = 'Hanya boleh memilih PO yang sama.';
                }

                $.messager.alert('Peringatan', pesan, 'warning');
                $('#dgOrder').datagrid('uncheckRow', index);
                updateSelection();
                return;
            }

            let segelCheck = validateSegelStatus(checked);
            if (!segelCheck.valid) {
                $.messager.alert('Peringatan', segelCheck.message, 'warning');
                $('#dgOrder').datagrid('uncheckRow', index);
                updateSelection();
                return;
            }

            updateSelection();
        }

        function validateCheckAll(rows) {
            if (!rows.length) {
                updateSelection();
                return;
            }

            let hasComplete = rows.some(row => row.segel_complete == 1);
            if (hasComplete) {
                $.messager.alert('Peringatan', 'Check All tidak diizinkan karena terdapat data yang sudah Complete.', 'warning');
                $('#dgOrder').datagrid('clearChecked');
                $('#dgOrder').datagrid('clearSelections');
                updateSelection();
                return;
            }

            let opAwal = rows[0].OP;
            let poAwal = rows[0].POno;

            let bedaOP = rows.some(item => item.OP !== opAwal);
            let bedaPO = rows.some(item => item.POno !== poAwal);

            if (bedaOP || bedaPO) {
                let pesan = '';
                if (bedaOP && bedaPO) {
                    pesan = 'Check All tidak diizinkan karena terdapat OP dan PO yang berbeda.';
                } else if (bedaOP) {
                    pesan = 'Check All tidak diizinkan karena terdapat OP yang berbeda.';
                } else {
                    pesan = 'Check All tidak diizinkan karena terdapat PO yang berbeda.';
                }

                $.messager.alert('Peringatan', pesan, 'warning');
                $('#dgOrder').datagrid('clearChecked');
                $('#dgOrder').datagrid('clearSelections');
                updateSelection();
                return;
            }

            let segelCheck = validateSegelStatus(rows);
            if (!segelCheck.valid) {
                $.messager.alert('Peringatan', 'Check All tidak diizinkan karena ' + segelCheck.message.charAt(0)
                    .toLowerCase() + segelCheck.message.slice(1), 'warning');
                $('#dgOrder').datagrid('clearChecked');
                $('#dgOrder').datagrid('clearSelections');
                updateSelection();
                return;
            }

            updateSelection();
        }

        function closeMenu() {
            $('#dgOrder').datagrid('clearChecked');
            $('#dgOrder').datagrid('unselectAll');
            $('#dgOrder').datagrid('clearSelections');
            updateSelection();
        }

        // ============================================================
        // FORMATTER NUMERIK
        // ============================================================
        function formatNumber(value) {
            return Number(value || 0).toLocaleString('id-ID');
        }

        function formatBalanceCell(value) {
            let v = Number(value || 0);
            let cls = v < 0 ? 'color:#dc3545;font-weight:bold' : (v > 0 ? 'color:#198754;font-weight:bold' : 'color:#94a3b8');
            let text = v > 0 ? ('+' + formatNumber(v)) : formatNumber(v);
            return `<span style="${cls}">${text}</span>`;
        }

        // ============================================================
        // onLoadSuccess LENGKAP KHUSUS PACKING (summary, row styling,
        // sticky bar) — inilah yang menimpa versi generic komponen.
        // ============================================================
        function onLoadTable(data) {
            const rows = data.rows || [];
            rows.forEach((row, index) => {
                row.no = index + 1;
            });

            const fmt = (v) => Number(v || 0).toLocaleString('id-ID');
            $('#sumQty').text(fmt(data.summary?.qty));
            $('#sumPackingPlan').text(fmt(data.summary?.packing_qty_plan));
            $('#sumPackingActual').text(fmt(data.summary?.packing_qty));
            $('#sumPackingBalance').text(fmt(data.summary?.packing_qty_balance));
            $('#sumCtnPlan').text(fmt(data.summary?.ctn));
            $('#sumCtnActual').text(fmt(data.summary?.packing_ctn));
            $('#sumCtnBalance').text(fmt(data.summary?.ctn_balance));

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

            $('#dgOrder').datagrid('clearChecked');
            $('#dgOrder').datagrid('unselectAll');
            updateSelection();
            $('#stickTopBar').hide();
            applySegelRowStyle();
        }

        // ============================================================
        // MODAL: Input Status Packing
        // ============================================================
        $('#btnInputStatusPacking').click(function () {
            let rows = $('#dgOrder').datagrid('getChecked');
            if (rows.length === 0) {
                $.messager.alert('Warning', 'Please select data.');
                return;
            }
            $('#cmbGabung').val('');
            $('#infoOP').val(rows[0].OP);

            let html = '';
            rows.forEach(function (row, index) {
                html += `
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td>${row.POno}</td>
                        <td>${row.gabung ?? ''}</td>
                        <td>${row.poref ?? ''}</td>
                        <td>${row.customer}</td>
                        <td>${row.season}</td>
                        <td>${row.buyer}</td>
                        <td>${row.style}</td>
                        <td>${row.material}</td>
                    </tr>
                `;
            });
            $('#statusPackingBody').html(html);
            $('#statusPackingModal').modal('show');
        });

        function saveStatusPacking() {
            let rows = $('#dgOrder').datagrid('getChecked');
            if (rows.length === 0) {
                $.messager.alert('Warning', 'Please select data.');
                return;
            }

            let gabung = $('#cmbGabung').val();
            if (gabung === '') {
                $.messager.alert('Warning', 'Please select Status Packing.');
                return;
            }

            let popk = rows.map(row => row.popk);

            $.ajax({
                url: "{{ route('packing.update-gabung') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    gabung: gabung,
                    popk: popk
                },
                beforeSend: function () {
                    $('#statusPackingModal .btn-dark').prop('disabled', true);
                },
                success: function (res) {
                    showToast(res.icon, res.title);
                    $('#statusPackingModal').modal('hide');
                    window.EasyuiDG.reload('dgOrder');
                },
                error: function (xhr) {
                    let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    showToast(res.icon, res.title);
                },
                complete: function () {
                    $('#statusPackingModal .btn-dark').prop('disabled', false);
                }
            });
        }

        // ============================================================
        // MODAL: Segel Packing (Complete / Partial)
        // ============================================================
        $('#btnSegelPacking').click(function () {
            let rows = $('#dgOrder').datagrid('getChecked');

            if (!rows.length) {
                $.messager.alert('Warning', 'Please select data.');
                return;
            }

            if (!canSegelPacking(rows)) {
                $.messager.alert(
                    'Warning',
                    'Segel Packing hanya dapat dilakukan jika seluruh data yang dipilih sudah memiliki Shipdate Actual dan CTN Actual.',
                    'warning'
                );
                return;
            }

            $('#infoPO').val(rows[0].POno);
            $('#opInfo').val(rows[0].OP);

            window.selectedSegelRows = rows;

            $('#shipmentFull').prop('checked', true);
            $('#partialArea').hide();

            disableUsedPartials(rows);

            $('#segelPackingModal').modal('show');
        });

        function saveSegelPacking() {
            let rows = window.selectedSegelRows || [];

            if (!rows.length) {
                showToast('error', 'Data tidak ditemukan, silakan pilih ulang.');
                return;
            }

            let shipmentType = $('input[name="shipment_type"]:checked').val();
            let partialNo = $('#partialNo').val();
            let popk = rows.map(row => row.popk);

            let payload = {
                popk: popk,
                gabung: rows[0].gabung,
                shipment_type: shipmentType
            };

            if (shipmentType === 'partial') {
                payload.partialNo = partialNo;
            }

            $.ajax({
                url: "{{ route('packing.segel.store') }}",
                method: 'POST',
                data: payload,
                beforeSend: function () {
                    $('#btnSaveSegelPacking').prop('disabled', true);
                },
                success: function (res) {
                    showToast(res.icon, res.title);
                    $('#segelPackingModal').modal('hide');
                    window.EasyuiDG.reload('dgOrder');
                },
                error: function (xhr) {
                    let res = xhr.responseJSON;

                    if (res?.errors) {
                        let firstError = Object.values(res.errors)[0][0];
                        showToast('error', firstError);
                    } else if (res?.title) {
                        showToast(res.icon || 'error', res.title);
                    } else {
                        showToast('error', 'Terjadi kesalahan.');
                    }
                },
                complete: function () {
                    $('#btnSaveSegelPacking').prop('disabled', false);
                }
            });
        }
    </script>
    <script>
        function canSegelPacking(rows) {
            if (!rows.length) return false;

            let shipCtnOk = rows.every(function (row) {
                const shipActual =
                    row.shipdate2 !== null &&
                    row.shipdate2 !== '' &&
                    row.shipdate2 !== '0000-00-00';

                const ctnActual = Number(row.packing_ctn || 0) > 0;

                return shipActual && ctnActual;
            });

            if (!shipCtnOk) return false;

            return validateSegelStatus(rows).valid;
        }

        function getSegelStatusKey(row) {
            if (row.segel_complete == 1) {
                return 'complete';
            }

            if (row.segel_partial_no) {
                let nums = String(row.segel_partial_no)
                    .split(',')
                    .map(v => parseInt(v.trim(), 10))
                    .filter(v => !isNaN(v))
                    .sort((a, b) => a - b);

                return 'partial:' + nums.join(',');
            }

            return 'empty';
        }

        function getSegelStatusLabel(statusKey) {
            if (statusKey === 'empty') return 'Belum Segel';
            if (statusKey === 'complete') return 'Complete';
            if (statusKey.startsWith('partial:')) {
                return 'Partial Ke-' + statusKey.replace('partial:', '');
            }
            return statusKey;
        }

        function validateSegelStatus(rows) {
            if (rows.length <= 1) {
                return { valid: true, message: '' };
            }

            let firstKey = getSegelStatusKey(rows[0]);
            let mismatch = rows.some(row => getSegelStatusKey(row) !== firstKey);

            if (mismatch) {
                let uniqueLabels = [...new Set(rows.map(row => getSegelStatusLabel(getSegelStatusKey(row))))];

                return {
                    valid: false,
                    message: 'Hanya boleh memilih data dengan status Segel yang sama. ' +
                        'Status yang tercampur: ' + uniqueLabels.join(', ') + '.'
                };
            }

            return { valid: true, message: '' };
        }

        function applySegelRowStyle() {
            let rows = $('#dgOrder').datagrid('getRows');
            let panel = $('#dgOrder').datagrid('getPanel');

            rows.forEach(function (row, index) {
                let tr = panel.find('tr[datagrid-row-index="' + index + '"]');
                if (row.segel_complete == 1) {
                    tr.addClass('datagrid-row-segel-complete');
                } else {
                    tr.removeClass('datagrid-row-segel-complete');
                }
            });
        }
    </script>
@endsection