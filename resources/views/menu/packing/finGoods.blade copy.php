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
        .action-btn:focus-visible {
            outline: 2px solid #0369a1;
            outline-offset: 2px;
        }

        .action-btn.action-btn-pdf {
            background: #fee2e2;
            color: #b91c1c;
        }

        .action-btn.action-btn-pdf:hover {
            background: #fecaca;
            color: #7f1d1d;
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
            <table id="dgOrder" class="easyui-datagrid" style="width:100%;height:600px" url="{{ route('packing.list') }}?fin=1"
                method="get" pagination="true" pageSize="50" pageList="[25,50,100,200,500]" rownumbers="false"
                singleSelect="true" fitColumns="false" border="false">
                <thead>
                    <tr>
                        <th field="action" width="90" formatter="formatAction" align="center" rowspan="2">Aksi</th>
                        <th field="no" width="50" align="center" rowspan="2">No</th>
                        <th field="POno" width="150" rowspan="2">PO No</th>
                        <th field="OP" width="150" rowspan="2" formatter="formatPOno">OP</th>
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
                        <th field="ctn" width="80" align="center">Plan</th>
                        <th field="packing_ctn" width="80" align="center">Actual</th>
                        <th field="ctn_balance" width="80" align="center" formatter="formatBalanceCell">Balance</th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>
    @include('menu.packing.modal-material-list', [
        'check' => true,
        'status' => true
    ])
    @include('menu.packing.modal-status-packing')
    @include('menu.packing.modal-segel-packing')
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
                // PENTING: ikut simpan PO/OP/mif modal yang sedang terbuka.
                modalPo:  packingModalPo,
                modalOp:  packingModalOp,
                modalMif: packingModalMif
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
                openPackingDetailModal(null, saved.modalPo, saved.modalOp, saved.modalMif);
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
        
        document.getElementById('packingDetailModal').addEventListener('hidden.bs.modal', function () {
            sessionStorage.removeItem('packingListState');
 
            if (!window.EasyuiDG) return;
        
            if (restoredDgOrderPage) {
                window.EasyuiDG.reload('dgOrder', restoredDgOrderPage);
                restoredDgOrderPage = null;
            } else {
                window.EasyuiDG.reload('dgOrder');
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

        function formatPOno(value, row) {
            if (!isSuperUser) return value ?? '';
            return `
                ${value ?? ''}
                <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:10px; margin-left:4px;">
                    mif ${row.mif}
                </span>
            `;
        }

        function formatSegelStatus(value, row) {
            if (row.segel_complete == 1) {
                return '<span class="badge bg-success-subtle text-success-emphasis" style="font-size:11px;">Complete</span>';
            }
            const lastPart = getLastSegelPart(row);
            if (lastPart) {
                return '<span class="badge bg-warning-subtle text-warning-emphasis" style="font-size:11px;">Partial Ke-' + lastPart + '</span>';
            }
            return '<span class="text-muted">-</span>';
        }

        function getLastSegelPart(row) {
            if (row.segel_complete == 1) return '10';
            if (row.segel_partial_no) {
                let nums = String(row.segel_partial_no).split(',')
                    .map(v => parseInt(v.trim(), 10)).filter(v => !isNaN(v)).sort((a, b) => a - b);
                if (nums.length) return String(nums[nums.length - 1]);
            }
            return null;
        }

        // ============================================================
        // KOLOM AKSI INDEX — cuma ikon mata, buka modal rincian PO+OP.
        // ============================================================
        // function formatAction(value, row, index) {
        //     return `
        //         <a href="javascript:void(0)"
        //             onclick='openPackingDetailModal(event, ${JSON.stringify(row.POno)}, ${JSON.stringify(row.OP)}, ${JSON.stringify(row.poref)}, ${row.mif})'
        //             class="action-btn"
        //             title="Lihat Detail">
        //             <i class="fas fa-eye"></i>
        //         </a>
        //     `;
        // }

        function formatAction(value, row, index) {
            const pdfUrl = "{{ route('laporan.pdf.global') }}"
                + "?po=" + encodeURIComponent(row.POno ?? '')
                + "&op=" + encodeURIComponent(row.OP)
                + "&poref=" + encodeURIComponent(row.poref ?? '')
                + "&mif=" + row.mif;
        
            return `
                <div class="d-inline-flex align-items-center gap-1.5">
                    <a href="javascript:void(0)"
                        onclick='openPackingDetailModal(event, ${JSON.stringify(row.POno)}, ${JSON.stringify(row.OP)}, ${JSON.stringify(row.poref)}, ${row.mif})'
                        class="action-btn"
                        title="Lihat Detail">
                        <i class="fas fa-eye"></i>
                    </a>
                    
                    <a href="${pdfUrl}" target="_blank" class="action-btn action-btn-pdf" title="Print PDF">
                        <img src="{{ asset('/public/css/images/pdf.jpg') }}" width="18" height="18" style="display: block; object-fit: contain;">
                    </a>
                </div>
            `;
        }

        // ============================================================
        // onLoadSuccess INDEX — summary + empty state
        // ============================================================
        function onLoadTable(data) {
            const rows = data.rows || [];
            rows.forEach((row, index) => { row.no = index + 1; });

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
        }

        // ============================================================
        // MODAL: buka rincian PO+OP
        // ============================================================
        let packingModalPo  = null;
        let packingModalOp  = null;
        let packingModalPoref = null;
        let packingModalMif = null;

        function openPackingDetailModal(e, po, op, poref, mif) {
            if (e) { e.preventDefault(); e.stopPropagation(); }

            packingModalPo  = po;
            packingModalOp  = op;
            packingModalPoref = poref;
            packingModalMif = mif;

            $('#packingModalPO').text(po);
            $('#packingModalOP').text(op);
            $('#packingModalBuyer').text('');

            const modalEl = document.getElementById('packingDetailModal');
            new bootstrap.Modal(modalEl).show();

            $(modalEl).one('shown.bs.modal', function () {
                $('#dgPackingDetail').datagrid('resize');
            });

            if (!$('#dgPackingDetail').data('datagrid')) {
                $('#dgPackingDetail').datagrid();
            } else {
                $('#dgPackingDetail').datagrid('loadData', { total: 0, rows: [] });
            }

            $('#dgPackingDetail').datagrid('load', { po: po, op: op, poref: poref, mif: mif });
        }

        function reloadPackingDetailModal() {
            if (!packingModalOp) return;
            $('#dgPackingDetail').datagrid('load', { po: packingModalPo, op: packingModalOp, poref: packingModalPoref, mif: packingModalMif });
        }

        // ============================================================
        // KOLOM AKSI DI DALAM MODAL — Input Packing, Batalkan, PDF
        // ============================================================
        function formatPackingDetailAction(value, row, index) {
            let baseUrl = "{{ route('laporan.pdf', ':id') }}".replace(':id', row.popk);
            let pdfUrl = baseUrl + '?gab=' + (row.gabung ?? 0);

            // let isComplete = row.segel_complete == 1;
            let lastPart = getLastSegelPart(row);

            // let inputBtn = isComplete ? '' : `
            //     <a href="javascript:void(0)"
            //         onclick="openPacking(event, ${row.popk}, ${row.mif})"
            //         class="action-btn"
            //         title="Input Packing">
            //         <i class="fas fa-edit"></i>
            //     </a>`;

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
                ${cancelBtn}
                <a href="${pdfUrl}" target="_blank" class="action-btn action-btn-pdf" title="Print PDF">
                    <img src="{{ asset('/public/css/images/pdf.jpg') }}" width="18" height="18">
                </a>
            </div>
            `;
            
        }

        function confirmCancelSegel(e, popk, part) {
            if (e) { e.preventDefault(); e.stopPropagation(); }

            $.messager.confirm(
                'Konfirmasi',
                'Batalkan proses kirim ke stuffing terakhir untuk data ini? Carton terkait akan dikembalikan ke status Ready (belum dikirim).',
                function (ok) {
                    if (!ok) return;
                    $.ajax({
                        url: "{{ route('packing.segel.cancel') }}",
                        method: 'POST',
                        data: { popk: popk, part: part },
                        success: function (res) {
                            showToast(res.icon, res.title);
                            reloadPackingDetailModal();
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
            if (e) { e.preventDefault(); e.stopPropagation(); }
        
            savePackingNavState();
        
            let url = "{{ url('/packing/input') }}/" + popk + "?mif=" + mif;
            window.location.href = url;
        }

        // ============================================================
        // onLoadSuccess MODAL — summary khusus PO+OP ini + row styling
        // ============================================================
        function onPackingDetailLoad(data) {
            const rows = data.rows || [];

            const fmt = (v) => Number(v || 0).toLocaleString('id-ID');
            $('#modalSumQty').text(fmt(data.summary?.qty));
            $('#modalSumPackingPlan').text(fmt(data.summary?.packing_qty_plan));
            $('#modalSumPackingActual').text(fmt(data.summary?.packing_qty));
            $('#modalSumPackingBalance').text(fmt(data.summary?.packing_qty_balance));
            $('#modalSumCtnPlan').text(fmt(data.summary?.ctn));
            $('#modalSumCtnActual').text(fmt(data.summary?.packing_ctn));
            $('#modalSumCtnBalance').text(fmt(data.summary?.ctn_balance));
            $('#packingModalBuyer').text(rows.length ? (rows[0].buyer ?? '-') : '-');

            $('#dgPackingDetail').datagrid('clearChecked');
            $('#dgPackingDetail').datagrid('unselectAll');
            updatePackingDetailSelection();
            applyPackingDetailRowStyle();

            let panel = $('#dgPackingDetail').datagrid('getPanel');
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

        function applyPackingDetailRowStyle() {
            let rows = $('#dgPackingDetail').datagrid('getRows');
            let panel = $('#dgPackingDetail').datagrid('getPanel');
            rows.forEach(function (row, index) {
                let tr = panel.find('tr[datagrid-row-index="' + index + '"]');
                if (row.segel_complete == 1) {
                    tr.addClass('datagrid-row-segel-complete');
                } else {
                    tr.removeClass('datagrid-row-segel-complete');
                }
            });
        }

        // ============================================================
        // SELEKSI CHECKBOX DI DALAM MODAL
        // ============================================================
        function updatePackingDetailSelection() {
            let rows = $('#dgPackingDetail').datagrid('getChecked');
            
            $('#packingModalSelectedCount').text(rows.length);
        
            if (!rows.length) {
                $('#packingModalStickyBar').addClass('d-none');
                $('#btnModalSegelPacking').addClass('d-none');
                return;
            }
        
            $('#packingModalStickyBar').removeClass('d-none');
        
            if (canSegelPacking(rows)) {
                $('#btnModalSegelPacking').removeClass('d-none');
            } else {
                $('#btnModalSegelPacking').addClass('d-none');
            }
        }
        
        function closePackingModalSelection() {
            $('#dgPackingDetail').datagrid('clearChecked');
            $('#dgPackingDetail').datagrid('unselectAll');
            $('#dgPackingDetail').datagrid('clearSelections');
            updatePackingDetailSelection();
        }

        function validatePackingDetailCheck(index, row) {
            if (row.segel_complete == 1) {
                $.messager.alert('Peringatan', 'Data yang sudah Complete tidak dapat dipilih lagi.', 'warning');
                $('#dgPackingDetail').datagrid('uncheckRow', index);
                updatePackingDetailSelection();
                return;
            }

            let checked = $('#dgPackingDetail').datagrid('getChecked');
            if (checked.length > 1) {
                let segelCheck = validateSegelStatus(checked);
                if (!segelCheck.valid) {
                    $.messager.alert('Peringatan', segelCheck.message, 'warning');
                    $('#dgPackingDetail').datagrid('uncheckRow', index);
                    updatePackingDetailSelection();
                    return;
                }
            }

            updatePackingDetailSelection();
        }

        function validatePackingDetailCheckAll(rows) {
            if (!rows.length) {
                updatePackingDetailSelection();
                return;
            }

            let hasComplete = rows.some(row => row.segel_complete == 1);
            if (hasComplete) {
                $.messager.alert('Peringatan', 'Check All tidak diizinkan karena terdapat data yang sudah Complete.', 'warning');
                $('#dgPackingDetail').datagrid('clearChecked');
                $('#dgPackingDetail').datagrid('clearSelections');
                updatePackingDetailSelection();
                return;
            }

            let segelCheck = validateSegelStatus(rows);
            if (!segelCheck.valid) {
                $.messager.alert('Peringatan', segelCheck.message, 'warning');
                $('#dgPackingDetail').datagrid('clearChecked');
                $('#dgPackingDetail').datagrid('clearSelections');
                updatePackingDetailSelection();
                return;
            }

            updatePackingDetailSelection();
        }

        function getSegelStatusKey(row) {
            if (row.segel_complete == 1) return 'complete';
            if (row.segel_partial_no) {
                let nums = String(row.segel_partial_no).split(',')
                    .map(v => parseInt(v.trim(), 10)).filter(v => !isNaN(v)).sort((a, b) => a - b);
                return 'partial:' + nums.join(',');
            }
            return 'empty';
        }

        function getSegelStatusLabel(statusKey) {
            if (statusKey === 'empty') return 'Belum Segel';
            if (statusKey === 'complete') return 'Complete';
            if (statusKey.startsWith('partial:')) return 'Partial Ke-' + statusKey.replace('partial:', '');
            return statusKey;
        }

        function validateSegelStatus(rows) {
            if (rows.length <= 1) return { valid: true, message: '' };

            let firstKey = getSegelStatusKey(rows[0]);
            let mismatch = rows.some(row => getSegelStatusKey(row) !== firstKey);

            if (mismatch) {
                let uniqueLabels = [...new Set(rows.map(row => getSegelStatusLabel(getSegelStatusKey(row))))];
                return {
                    valid: false,
                    message: 'Hanya boleh memilih data dengan status Segel yang sama. Status yang tercampur: ' + uniqueLabels.join(', ') + '.'
                };
            }
            return { valid: true, message: '' };
        }

        function canSegelPacking(rows) {
            if (!rows.length) return false;

            // let shipCtnOk = rows.every(function (row) {
            //     const shipActual = row.shipdate2 !== null && row.shipdate2 !== '' && row.shipdate2 !== '0000-00-00';
            //     const ctnActual = Number(row.packing_ctn || 0) > 0;
            //     return shipActual && ctnActual;
            // });

            // if (!shipCtnOk) return false;
            return validateSegelStatus(rows).valid;
        }

        // ============================================================
        // MODAL: Input Status Packing (target grid modal)
        // ============================================================
        $('#btnModalInputStatusPacking').click(function () {
            let rows = $('#dgPackingDetail').datagrid('getChecked');
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
            let rows = $('#dgPackingDetail').datagrid('getChecked');
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
                data: { _token: "{{ csrf_token() }}", gabung: gabung, popk: popk },
                beforeSend: function () { $('#statusPackingModal .btn-dark').prop('disabled', true); },
                success: function (res) {
                    showToast(res.icon, res.title);
                    $('#statusPackingModal').modal('hide');
                    reloadPackingDetailModal();
                },
                error: function (xhr) {
                    let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    showToast(res.icon, res.title);
                },
                complete: function () { $('#statusPackingModal .btn-dark').prop('disabled', false); }
            });
        }

        // ============================================================
        // MODAL: Segel Packing (target grid modal)
        // ============================================================
        $('#btnModalSegelPacking').click(function () {
            let rows = $('#dgPackingDetail').datagrid('getChecked');

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
            $('#shipmentDate').val(rows[0].shipdate2);

            window.selectedSegelRows = rows;

            $('#shipmentFull').prop('checked', true);
            $('#partialArea').hide();

            disableUsedPartials(rows);

            $('#segelPackingModal').modal('show');
        });

        $('#segelPackingModal').on('show.bs.modal', function () {
            if (!$('#shipmentDate').val()) {
                $('#shipmentDate').val(new Date().toISOString().slice(0, 10));
            }
        });

        function saveSegelPacking() {
            let rows = window.selectedSegelRows || [];

            if (!rows.length) {
                showToast('error', 'Data tidak ditemukan, silakan pilih ulang.');
                return;
            }

            let actualShipment = $('#shipmentDate').val();
            if (!actualShipment) {
                showToast('error', 'Actual Shipment wajib diisi.');
                return;
            }

            let shipmentType = $('input[name="shipment_type"]:checked').val();
            let partialNo = $('#partialNo').val();
            let popk = rows.map(row => row.popk);

            let payload = {
                popk: popk,
                gabung: rows[0].gabung,
                shipment_type: shipmentType,
                actual_shipment: actualShipment,
                mif: rows[0].mif
            };
            if (shipmentType === 'partial') payload.partialNo = partialNo;

            $.ajax({
                url: "{{ route('packing.segel.store') }}",
                method: 'POST',
                data: payload,
                beforeSend: function () { $('#btnSaveSegelPacking').prop('disabled', true); },
                success: function (res) {
                    showToast(res.icon, res.title);
                    $('#segelPackingModal').modal('hide');
                    reloadPackingDetailModal();
                },
                error: function (xhr) {
                    let res = xhr.responseJSON;
                    if (res?.errors) {
                        showToast('error', Object.values(res.errors)[0][0]);
                    } else if (res?.title) {
                        showToast(res.icon || 'error', res.title);
                    } else {
                        showToast('error', 'Terjadi kesalahan.');
                    }
                },
                complete: function () { $('#btnSaveSegelPacking').prop('disabled', false); }
            });
        }
    </script>
@endsection