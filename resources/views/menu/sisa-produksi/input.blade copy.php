@extends('layout.main')

@section('css_custom')
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-light: rgba(79, 70, 229, 0.1);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --bg-header: #f8fafc;
            --border-color: #e2e8f0;
        }

        a i {
            transition: transform 0.2s ease;
        }

        a:hover i {
            transform: scale(1.2) translateX(-2px);
        }

        .info-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }

        .btn-back-custom {
            color: var(--text-muted);
            padding: 8px 14px;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }

        .btn-back-custom:hover {
            color: var(--text-main);
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .btn-icon-custom:hover {
            background-color: #f8fafc !important;
            color: #1e293b !important;
            transform: translateX(-3px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        }

        #dgTransferWrapper .datagrid-wrap {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
            border: none !important;
        }

        #dgTransfer .datagrid-view {
            border: none !important;
        }

        #dgTransfer .datagrid-header,
        #dgTransfer .datagrid-header-inner {
            background-color: var(--bg-header) !important;
            border-bottom: 2px solid #cbd5e1 !important;
        }

        #dgTransfer .datagrid-header .datagrid-cell {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            font-weight: 700;
            text-align: center !important;
        }

        #dgTransfer .datagrid-header-row td {
            border-color: var(--border-color) !important;
        }

        #dgTransfer .datagrid-body td.datagrid-td {
            border-color: var(--border-color) !important;
        }

        #dgTransfer .datagrid-cell {
            font-size: 13px;
            color: #334155;
            padding: 8px 8px !important;
        }

        #dgTransfer .datagrid-row:hover td {
            background-color: #f1f5f9 !important;
        }

        #dgTransfer .datagrid-row-alt {
            background-color: #fcfdfe;
        }

        #dgTransfer .datagrid-td-total .datagrid-cell {
            font-weight: 700;
            color: var(--text-main);
            background-color: #f8fafc;
        }

        #dgTransferWrapper .datagrid-pager {
            background-color: #ffffff;
            border-top: 1px solid var(--border-color);
            padding: 6px 4px;
        }

        #dgTransferWrapper .l-btn {
            border-radius: 6px !important;
        }

        .dg-action-btn {
            border: none;
            background: transparent;
            padding: 4px 6px;
            border-radius: 6px;
            transition: background-color .15s ease;
            cursor: pointer;
        }

        .dg-action-btn:hover {
            background-color: #f1f5f9;
        }

        .dg-action-btn.dg-delete {
            color: #dc2626;
        }

        .dg-action-btn.dg-edit {
            color: #359DD9;
        }

        .dg-action-btn.dg-check {
            color: #16a34a;
        }

        .dg-empty-cell {
            color: #94a3b8;
            opacity: .5;
        }

        #dgTransferWrapper .easyui-empty-state {
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 60px;
            background: rgba(255, 255, 255, 0.96);
            z-index: 2;
        }

        #dgTransferWrapper .empty-icon img {
            opacity: 0.9;
        }

        #dgTransfer .datagrid-row-checked td {
            background-color: #CCCCCC !important;
        }

        #dgTransfer .datagrid-row-checked:hover td {
            background-color: #bfbfbf !important;
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
                    style="width: 38px; height: 38px; transition: all 0.2s ease;" title="Kembali ke Daftar Data OP"> <i
                        class="fas fa-arrow-left"></i> </a>
                <div>
                    <h4 class="fw-bold text-dark mb-0" style="font-size: 1.15rem; letter-spacing: -0.3px;">Input Sisa Produksi</h4>
                </div>
            </div>
        </div>

        {{-- HEADER INFO UTAMA --}}
        <div class="card border-0 shadow-sm mb-4 bg-white" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4 mb-4">
                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-layer-group fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="font-size: 10px; letter-spacing: 0.5px;">OP</div>
                                <div class="fw-bold text-dark" style="font-size: 14px;">{{ $dt->OP }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0; background-color: #f1f5f9;">
                                <i class="fas fa-certificate text-dark fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">License PO Ref</div>
                                <div class="text-dark fw-semibold text-truncate" style="font-size: 14px;">
                                    {{ $dt->poref ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper bg-danger-subtle text-danger rounded-3 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-map-marker-alt fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="font-size: 10px; letter-spacing: 0.5px;">Place</div>
                                <div class="fw-bold text-dark text-truncate" style="font-size: 14px;"
                                    title="{{ $dt->customer }}">{{ $dt->customer }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper bg-warning-subtle text-warning rounded-3 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-calendar-alt fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="font-size: 10px; letter-spacing: 0.5px;">Season</div>
                                <div class="fw-bold text-dark" style="font-size: 14px;">{{ $dt->season }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-user-tie fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="font-size: 10px; letter-spacing: 0.5px;">Buyer</div>
                                <div class="fw-bold text-dark text-truncate" style="font-size: 14px;"
                                    title="{{ $dt->buyer }}">{{ $dt->buyer }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper bg-secondary-subtle text-secondary rounded-3 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-tshirt fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="font-size: 10px; letter-spacing: 0.5px;">Style Code</div>
                                <div class="fw-bold text-dark text-truncate" style="font-size: 14px;">{{ $dt->style }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper text-dark rounded-3 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0; background-color: #f1f5f9;">
                                <i class="fas fa-palette fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="font-size: 10px; letter-spacing: 0.5px;">Color / Material</div>
                                <div class="fw-bold text-dark text-truncate" style="font-size: 14px;">{{ $dt->material }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper text-dark rounded-3 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0; background-color: #f1f5f9;">
                                <i class="fas fa-align-left fs-6"></i>
                            </div>
                            <div>
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="font-size: 10px; letter-spacing: 0.5px;">Description</div>
                                <div class="text-muted fw-normal"
                                    style="font-size: 12px; line-height: 1.4; word-break: break-word;">
                                    {{ $dt->silhouette ?? '-' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4" style="border-color: #f1f5f9; border-width: 2px;">

                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-hashtag fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="font-size: 10px; letter-spacing: 0.5px;">PO Number</div>
                                <div class="fw-bold text-dark  text-truncate" style="font-size: 14px;">
                                    {{ $dt->POno }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="breakdownSummaryWrapper">
            @include('menu.transfer.partials.breakdown_summary')
        </div>

        {{-- DETAIL DATA PACKING (EasyUI DataGrid) --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="fw-bold text-dark d-flex align-items-center" style="font-size: 14px;">
                    <span class="rounded me-2"
                        style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
                    Detail Data Polibag
                </div>
            </div>

            <div class="card-body p-0" id="dgTransferWrapper">
                <table id="dgTransfer" class="easyui-datagrid" style="width:100%;height:520px"
                    url="{{ route('sisa-produksi.detail.list', $dt->popk) }}" method="get" pagination="true"
                    pageSize="50" pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true"
                    fitColumns="false" border="false"
                    data-options="
                        queryParams: {cr: '{{ $cr }}'},
                        loadMsg: 'Memuat data...',
                        onLoadSuccess: onLoadTable,
                        onBeforeLoad: clearEmptyState,
                        onLoadError: onLoadTableError,
                        rowStyler: rowStylerTransfer
                    ">
                    <thead>
                        <tr>
                            <th field="action" rowspan="2" width="80" align="center" formatter="formatAction">
                                Aksi</th>
                            <th field="no" rowspan="2" width="50" align="center" formatter="formatNo">No.
                            </th>
                            <th field="tanggal" formatter="formatTanggalMasuk" rowspan="2" width="105" align="center">Tanggal<br>Masuk</th>
                            <th field="tanggal2" formatter="formatDatePlain" rowspan="2" width="105" align="center">Tanggal<br>Kembali</th>
                            <th field="tglin" formatter="formatDatePlain" rowspan="2" width="105" align="center">Tanggal In</th>
                            <th field="tglout" formatter="formatDatePlain" rowspan="2" width="105" align="center">Tanggal Out</th>
                            <th colspan="{{ count($activeSizes) }}" align="center">
                                <strong>Size</strong>
                                @if (!empty($dt->secsz))
                                    ({{ $dt->secsz }})
                                @endif
                            </th>
                            <th field="pcs" rowspan="2" width="90" align="center" formatter="formatTotal">
                                Total<br>(Pcs)</th>
                            <th field="grade" rowspan="2" width="70" align="center" formatter="formatDash">
                                Grade</th>
                            <th field="pcsk" rowspan="2" width="90" align="center" formatter="formatKeluar">
                                Keluar<br>(Pcs)</th>
                            <th field="keterangan" rowspan="2" width="150" align="center" formatter="formatDash">
                                Keterangan</th>
                        </tr>
                        <tr>
                            @foreach ($activeSizes as $i => $size)
                                <th field="qty{{ $i }}" width="85" align="center" formatter="formatQty">
                                    {{ $size }}</th>
                            @endforeach
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </div>
    @include('menu.sisa-produksi.modal-transfer')
@endsection

@section('js_custom')
    <script>
        // ID PO saat ini, dipakai untuk reload breakdown summary via AJAX
        const currentPopk = {{ $dt->popk }};

        // Flag role super — dipakai untuk menentukan tombol Aksi mana yang tampil
        const isSuperUser = @json($isSuper ?? false);

        function formatNo(value, row, index) {
            try {
                const opts = $('#dgTransfer').datagrid('options');
                return ((opts.pageNumber - 1) * opts.pageSize) + index + 1;
            } catch (e) {
                return index + 1;
            }
        }

        function formatQty(value) {
            if (value === null || value === undefined || value === '') {
                return '<span class="dg-empty-cell">-</span>';
            }
            return value;
        }

        function formatDash(value) {
            if (value === null || value === undefined || value === '') {
                return '<span class="dg-empty-cell">-</span>';
            }
            return value;
        }

        function formatTotal(value) {
            return value ?? 0;
        }

        function formatKeluar(value) {
            return value ?? 0;
        }

        function rowStylerTransfer(index, row) {
            if (Number(row.status) === 2) {
                return 'background-color:#CCCCCC;';
            }
            return '';
        }
        
        // function formatAction(value, row, index) {
        //     const grade = row.grade;
        //     const hasGrade = grade !== null && grade !== undefined && String(grade).trim() !== '';
        //     const status = Number(row.status);
        //     const hasBalance = Number(row.pcs) > Number(row.pcsk || 0);

        //     console.log('ROW:', row);
        //     console.log('Grade:', row.grade);
        //     console.log('hasGrade:', hasGrade);
        //     console.log('status:', row.status);
        //     console.log('hasBalance:', hasBalance);

        //     let buttons = '';

        //     if (hasGrade) {
        //         if (status === 1) {
        //             buttons = checkButtonHtml(row.bjpk);
        //         } else if (status === 2) {
        //             if (isSuperUser) {
        //                 if (hasBalance) {
        //                     buttons += editButtonHtml(index);
        //                 }
        //             } else if (hasBalance) {
        //                 buttons += editButtonHtml(index);
        //             }
        //         } else {
        //             buttons = checkButtonHtml(row.bjpk);
        //         }
        //     }

        //     return `<div class="d-flex justify-content-center gap-1">${buttons}</div>`;
        // }
        function formatAction(value, row, index) {
            const grade = row.grade;
            const hasGrade = grade !== null && grade !== undefined && String(grade).trim() !== '';
            const status = Number(row.status);
            const hasBalance = Number(row.pcs) > Number(row.pcsk || 0);
        
            let buttons = '';
        
            if (hasGrade) {
                if (status === 1) {
                    // Belum complete -> tombol Centang (Tandai Selesai), sama untuk semua role
                    buttons = checkButtonHtml(row.bjpk);
                } else if (status === 2) {
                    if (isSuperUser) {
                        // ============================================================
                        // SUPER USER: sesuai native -- SELALU punya akses Delete di
                        // status=2, apapun kondisi balance-nya. Edit HANYA muncul
                        // kalau masih ada balance (pcs > pcsk).
                        // ============================================================
                        buttons += deleteButtonHtml(row.bjpk);
                        if (hasBalance) {
                            buttons += editButtonHtml(index);
                        }
                    } else if (hasBalance) {
                        // User biasa: cuma Edit, dan hanya kalau masih ada balance
                        buttons += editButtonHtml(index);
                    }
                    // User biasa + tidak ada balance -> tidak ada tombol sama sekali
                } else {
                    // status lain (bukan 1/2) -> fallback ke tombol Centang, sama seperti native
                    buttons = checkButtonHtml(row.bjpk);
                }
            }
        
            return `<div class="d-flex justify-content-center gap-1">${buttons}</div>`;
        }

        function checkButtonHtml(bjpk) {
            return `
                <button type="button" class="dg-action-btn dg-check" title="Tandai Selesai" onclick="completeRow(${bjpk})">
                    <i class="fas fa-check"></i>
                </button>
            `;
        }

        function editButtonHtml(index) {
            return `
                <button type="button" class="dg-action-btn dg-edit" title="Input Actual" onclick="editRowByIndex(${index})">
                    <i class="fas fa-pen-to-square"></i>
                </button>
            `;
        }

        function editRowByIndex(index) {
            const row = $('#dgTransfer').datagrid('getRows')[index];
            if (row) fillEditForm(row);
        }

        function completeRow(bjpk) {
            $.ajax({
                url: "{{ url('sisa-produksi') }}/" + bjpk + "/complete",
                method: 'POST',
                success: function(res) {
                    showToast(res.icon, res.title);
                    reloadTransferGrid();
                    reloadBreakdownSummary();
                },
                error: function(xhr) {
                    const res = xhr.responseJSON || {
                        icon: 'error',
                        title: 'Terjadi kesalahan.'
                    };
                    showToast(res.icon, res.title);
                }
            });
        }

        function formatTanggalMasuk(value, row) {
            if (!value) {
                return '<span class="dg-empty-cell">-</span>';
            }
    
            let datePart = String(value).split(' ')[0];
            let parts = datePart.split('-');
            if (parts.length !== 3) return value;
    
            let [year, month, day] = parts;
            let dateLabel = `${day}/${month}/${year}`;
    
            let sourceTag = '';
            if (row && row.source === 'output') {
                sourceTag = ' <span class="badge bg-secondary-subtle text-secondary" style="font-size:9px;">Barcode</span>';
            }
    
            return `${dateLabel}${sourceTag}`;
        }

        function formatDatePlain(value) {
            if (!value) {
                return '<span class="dg-empty-cell">-</span>';
            }
    
            let datePart = String(value).split(' ')[0];
            let parts = datePart.split('-');
            if (parts.length !== 3) return value;
    
            let [year, month, day] = parts;
            return `${day}/${month}/${year}`;
        }

        function clearEmptyState() {
            $('#dgTransfer').datagrid('getPanel')
                .find('.datagrid-view2 .easyui-empty-state')
                .remove();
        }

        function showEmptyState(title, subtitle) {
            let panel = $('#dgTransfer').datagrid('getPanel');
            let body = panel.find('.datagrid-view2 .datagrid-body');
            panel.find('.easyui-empty-state').remove();
            body.append(`
                <div class="easyui-empty-state">
                    <div class="empty-icon" style="text-align:center">
                        <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="180">
                        <div style="margin-top:8px;font-weight:600;">${title}</div>
                        <div style="font-size:12px;color:#9ca3af;">${subtitle}</div>
                    </div>
                </div>
            `);
        }

        function onLoadTable(data) {
            const rows = data.rows || [];
            if (!rows.length) {
                showEmptyState('No Data Found', 'Try changing filter');
            } else {
                clearEmptyState();
            }
        }

        function onLoadTableError() {
            console.log('LOAD ERROR');
            showEmptyState('Gagal memuat data', 'Silakan coba lagi');
        }

        /* INIT */
        $(function() {
            $('#dgTransfer').datagrid();
        });

        function reloadTransferGrid() {
            if ($('#dgTransfer').data('datagrid')) {
                $('#dgTransfer').datagrid('reload');
            }
        }

        function reloadBreakdownSummary() {
            $.get("{{ route('transfer.breakdown-summary', $dt->popk) }}", {
                mif: '{{ $mif }}'
            }, function(html) {
                $('#breakdownSummaryWrapper').html(html);
            });
        }

        function setDateSafe(name, value) {
            const el = $(`input[name="${name}"]`);
            value = value || '';
            try {
                if (el.hasClass('easyui-datebox') && el.data('datebox')) {
                    el.datebox('setValue', value);
                } else {
                    el.val(value);
                }
            } catch (e) {
                console.warn('Datebox fallback:', name, e);
                el.val(value);
            }
        }

        function fillEditForm(row) {
            hideTransferAlert();
            $('#mainTransferForm .is-invalid').removeClass('is-invalid');
            $('#mainTransferForm .invalid-feedback, #mainTransferForm td .text-danger').text('');
        
            $('#bjpk').val(row.bjpk);
            $('#displayTglin').val(row.tglin ?? '-');
            $('#displayGrade').val(row.grade ?? '-');
            $('[name=tglout]').val(row.tglout ?? '');
            $('[name=keterangan]').val(row.keterangan ?? '');
        
            @foreach ($activeSizes as $i => $size)
                (function () {
                    var val = row.qty{{ $i }};
                    var hasValue = val !== null && val !== undefined && val !== '' && Number(val) > 0;
        
                    var $col = $('#sizeCol{{ $i }}');
                    var $input = $col.find('input[name="qty{{ $i }}"]');
                    var $placeholder = $col.find('.size-empty-placeholder');
        
                    if (hasValue) {
                        $input.removeClass('d-none').val('');
                        $placeholder.addClass('d-none');
                    } else {
                        $input.val('').addClass('d-none');
                        $placeholder.removeClass('d-none');
                    }
                })();
            @endforeach
        
            new bootstrap.Modal(
                document.getElementById('transferModal')
            ).show();
        }

        function myformatter(date) {
            var y = date.getFullYear();
            var m = (date.getMonth() + 1).toString().padStart(2, '0');
            var d = date.getDate().toString().padStart(2, '0');
            return y + '-' + m + '-' + d;
        }

        function myparser(s) {
            if (!s) return new Date();
            var t = s.split('-');
            return new Date(t[0], t[1] - 1, t[2]);
        }

        function goBack() {
            if (document.referrer && document.referrer.indexOf('/sisa-produksi') !== -1) {
                window.history.back();
            } else {
                window.location.href = "{{ route('sisa-produksi.index') }}";
            }
        }
    </script>
@endsection
