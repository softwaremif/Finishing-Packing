@extends('layout.main')

@section('css_custom')
    <style>
        /* ============================================================
           VARIABEL WARNA
           ============================================================ */
        :root {
            --primary-color: #4f46e5;
            --primary-light: rgba(79, 70, 229, 0.1);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --bg-header: #f8fafc;
            --border-color: #e2e8f0;
        }

        /* ============================================================
           UTILITAS UMUM (ikon, kartu info, tombol back)
           ============================================================ */
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
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-muted);
            padding: 8px 14px;
            transition: all 0.2s ease;
        }

        .btn-back-custom:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: var(--text-main);
        }

        .btn-icon-custom:hover {
            background-color: #f8fafc !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
            color: #1e293b !important;
            transform: translateX(-3px);
        }

        /* ============================================================
           DATAGRID "Detail Data Polibag" (#dgTransfer)
           Desain disamakan dengan tabel di modal (#dgDetailModal):
           header center + background abu muda, TANPA garis kolom/baris.
           ============================================================ */
        #dgTransferWrapper .datagrid-wrap {
            border: none !important;
            border-radius: 0 0 12px 12px;
            overflow: hidden;
        }

        #dgTransferWrapper .datagrid-view,
        #dgTransferWrapper .datagrid-view * {
            box-sizing: border-box !important;
        }

        #dgTransferWrapper .datagrid-cell {
            color: #334155;
            font-size: 13px;
            padding: 10px 12px;
        }

        #dgTransferWrapper .datagrid-header,
        #dgTransferWrapper .datagrid-header-inner {
            background: #f9fafb !important;
        }

        #dgTransferWrapper .datagrid-header .datagrid-cell {
            color: #374151;
            font-weight: 600;
            text-align: center !important;
        }

        #dgTransferWrapper .datagrid-body .datagrid-cell {
            text-align: center;
        }

        #dgTransferWrapper .datagrid-row:hover td {
            background-color: #f1f5f9 !important;
        }

        /* Hilangkan SEMUA garis kolom & baris (vertikal & horizontal) --
           EasyUI menggambar border di banyak elemen sekaligus, jadi
           harus di-nol-kan satu per satu. */
        #dgTransferWrapper .datagrid-view,
        #dgTransferWrapper .datagrid-header,
        #dgTransferWrapper .datagrid-header-inner,
        #dgTransferWrapper .datagrid-header-row,
        #dgTransferWrapper .datagrid-header-row td,
        #dgTransferWrapper .datagrid-body,
        #dgTransferWrapper .datagrid-row,
        #dgTransferWrapper .datagrid-body td,
        #dgTransferWrapper .datagrid-htable td,
        #dgTransferWrapper .datagrid-btable td {
            border: none !important;
        }

        #dgTransferWrapper .datagrid-pager {
            background-color: #ffffff;
            border-top: 1px solid var(--border-color);
            padding: 6px 4px;
        }

        #dgTransferWrapper .l-btn {
            border-radius: 6px !important;
        }

        #dgTransferWrapper .easyui-empty-state {
            background: rgba(255, 255, 255, 0.96);
            display: flex;
            align-items: center;
            justify-content: center;
            inset: 0;
            padding-top: 60px;
            z-index: 2;
        }

        #dgTransferWrapper .empty-icon img {
            opacity: 0.9;
        }

        /* ============================================================
           TOMBOL AKSI DI DALAM DATAGRID
           ============================================================ */
        .dg-action-btn {
            background: transparent;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            padding: 4px 6px;
            transition: background-color .15s ease;
        }

        .dg-action-btn:hover {
            background-color: #f1f5f9;
        }

        .dg-action-btn.dg-edit {
            color: #359DD9;
        }

        .dg-action-btn.dg-delete {
            color: #dc2626;
        }

        .dg-empty-cell {
            color: #94a3b8;
            opacity: .5;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4 px-4">

        {{-- ============================================================
             HEADER HALAMAN
             ============================================================ --}}
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-3 mb-4 gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:void(0)" onclick="goBack()"
                    class="btn btn-icon-custom d-inline-flex align-items-center justify-content-center shadow-sm border bg-white text-secondary rounded-circle"
                    style="width: 38px; height: 38px; transition: all 0.2s ease;"
                    title="Kembali ke Daftar Data OP">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold text-dark mb-0" style="font-size: 1.15rem; letter-spacing: -0.3px;">
                        {{ session('guserpk') == 35 ? 'Input Data Grade Sisa' : 'Input Data Polibag' }}
                    </h4>
                </div>
            </div>
        </div>

        {{-- ============================================================
             HEADER INFO UTAMA PO
             ============================================================ --}}
        <div class="card border-0 shadow-sm mb-4 bg-white" style="border-radius: 12px;">
            <div class="card-body p-4">
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4 mb-4">
                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-layer-group fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size: 10px; letter-spacing: 0.5px;">OP</div>
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
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="letter-spacing: 0.5px; font-size: 10px;">License PO Ref</div>
                                <div class="text-dark fw-semibold text-truncate" style="font-size: 14px;">{{ $dt->poref ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper bg-danger-subtle text-danger rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-map-marker-alt fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size: 10px; letter-spacing: 0.5px;">Place</div>
                                <div class="fw-bold text-dark text-truncate" style="font-size: 14px;" title="{{ $dt->customer }}">{{ $dt->customer }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper bg-warning-subtle text-warning rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-calendar-alt fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size: 10px; letter-spacing: 0.5px;">Season</div>
                                <div class="fw-bold text-dark" style="font-size: 14px;">{{ $dt->season }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-user-tie fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size: 10px; letter-spacing: 0.5px;">Buyer</div>
                                <div class="fw-bold text-dark text-truncate" style="font-size: 14px;" title="{{ $dt->buyer }}">{{ $dt->buyer }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper bg-secondary-subtle text-secondary rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-tshirt fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size: 10px; letter-spacing: 0.5px;">Style Code</div>
                                <div class="fw-bold text-dark text-truncate" style="font-size: 14px;">{{ $dt->style }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper text-dark rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0; background-color: #f1f5f9;">
                                <i class="fas fa-palette fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size: 10px; letter-spacing: 0.5px;">Color / Material</div>
                                <div class="fw-bold text-dark text-truncate" style="font-size: 14px;">{{ $dt->material }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper text-dark rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0; background-color: #f1f5f9;">
                                <i class="fas fa-align-left fs-6"></i>
                            </div>
                            <div>
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size: 10px; letter-spacing: 0.5px;">Description</div>
                                <div class="text-muted fw-normal" style="font-size: 12px; line-height: 1.4; word-break: break-word;">
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
                            <div class="info-icon-wrapper bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-hashtag fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size: 10px; letter-spacing: 0.5px;">PO Number</div>
                                <div class="fw-bold text-dark  text-truncate" style="font-size: 14px;">{{ $dt->POno }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             BREAKDOWN SIZE & QTY (di-reload lewat AJAX setiap ada
             Add/Edit/Delete Polibag)
             ============================================================ --}}
        <div id="breakdownSummaryWrapper">
            @include('menu.transfer.partials.breakdown_summary')
        </div>

        {{-- ============================================================
             DETAIL DATA POLIBAG (EasyUI DataGrid)
             ============================================================ --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="fw-bold text-dark d-flex align-items-center" style="font-size: 14px;">
                    <span class="rounded me-2" style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
                    {{ session('guserpk') == 35 ? 'Detail Data Grade Sisa' : 'Detail Data Polibag' }}
                </div>
            </div>
            <div class="card-body p-0" id="dgTransferWrapper">
                <div class="px-3 py-2 border-bottom bg-white">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <input id="filterLine" style="width:170px;">
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-dark btn-sm d-inline-flex align-items-center px-2.5 py-1.5 fw-semibold"
                                style="font-size: 12px; border-radius: 6px; background-color: #1e293b; border-color: #1e293b;"
                                onclick="openTransferModal()">
                                <i class="fas fa-plus me-1.5 small"></i> {{ session('guserpk') == 35 ? 'Add Grade Sisa' : 'Add Polibag' }}
                            </button>
                        </div>
                    </div>
                </div>
                <table id="dgTransfer" class="easyui-datagrid" style="width:100%;height:520px"
                    url="{{ route('transfer.detail.list', $dt->popk) }}"
                    method="get" pagination="true" pageSize="50" pageList="[25,50,100,200,500]"
                    rownumbers="false" singleSelect="true" fitColumns="false" border="false"
                    data-options="
                        queryParams: {cr: '{{ $cr }}', mif: '{{ $mif }}'},
                        loadMsg: 'Memuat data...',
                        onLoadSuccess: onLoadTable,
                        onBeforeLoad: clearEmptyState,
                        onLoadError: onLoadTableError
                    ">
                    <thead>
                        <tr>
                            <th field="action" rowspan="2" width="70" align="left" formatter="formatAction">Aksi</th>
                            <th field="no" rowspan="2" width="50" align="left" formatter="formatNo">No</th>
                            <th field="tanggal" rowspan="2" width="130" align="left" formatter="formatDate">Tanggal Masuk</th>
                            <th field="linenm" rowspan="2" width="120" align="left">Line</th>
                            <th colspan="{{ count($activeSizes) }}" align="left">
                                <strong>Size</strong>
                                @if (!empty($dt->secsz))
                                    ({{ $dt->secsz }})
                                @endif
                            </th>
                            <th field="pcs" rowspan="2" width="100" align="right" formatter="formatTotal">Total <br>Pcs</th>
                            {{-- @if (session('guserpk') == 35) --}}
                                <th field="grade" rowspan="2" width="100" align="center">Grade</th>
                            {{-- @endif --}}
                        </tr>
                        <tr>
                            @foreach ($activeSizes as $i => $size)
                                <th field="qty{{ $i }}" width="85" align="left" formatter="formatQty">{{ $size }}</th>
                            @endforeach
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </div>
    @include('menu.transfer.modal-transfer')
    @include('menu.transfer.modal-delete-transfer')
@endsection

@section('js_custom')
    <script>
        // ID PO saat ini, dipakai untuk reload breakdown summary via AJAX
        const guserpk = {{ (int) session('guserpk') }};
        const currentPopk = {{ $dt->popk }};

        // ============================================================
        // FILTER LINE
        // ============================================================
        function initFilterLineCombobox() {
            let lineData = [{ value: '', text: 'Semua Line' }];

            @foreach ($lines as $line)
                lineData.push({ value: @json($line->linenm), text: @json($line->linenm) });
            @endforeach

            $('#filterLine').combobox({
                data: lineData,
                valueField: 'value',
                textField: 'text',
                value: '{{ $cr }}',
                editable: false,
                panelHeight: 300,
                panelMaxHeight: 300,
                onChange: function (newValue, oldValue) {
                    reloadTransferGrid(newValue);
                }
            });
        }

        // ============================================================
        // FORMATTERS DATAGRID
        // ============================================================
        function formatNo(value, row, index) {
            try {
                const opts = $('#dgTransfer').datagrid('options');
                return ((opts.pageNumber - 1) * opts.pageSize) + index + 1;
            } catch (e) {
                return index + 1;
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

        function formatQty(value) {
            if (value === null || value === undefined || value === '') {
                return '<span class="dg-empty-cell">-</span>';
            }
            return value;
        }

        function formatTotal(value) {
            return value ?? 0;
        }

        const isGuserpk35 = guserpk === 35;
        function formatAction(value, row, index) {
            const editBtn = `
                <button type="button" class="dg-action-btn dg-edit" title="Ubah" onclick="editRowByIndex(${index})">
                    <i class="fas fa-pen-to-square"></i>
                </button>`;

            const deleteBtn = `
                <button type="button" class="dg-action-btn dg-delete" title="Hapus" onclick="openDeleteModal(${row.bjpk})">
                    <i class="fas fa-trash-alt"></i>
                </button>`;

            const wrap = (html) => `<div class="d-flex justify-content-center gap-1">${html}</div>`;

            const grade = row.grade;
            const hasGrade = grade !== null && grade !== undefined && String(grade).trim() !== '';
            const status = Number(row.status);

            // ============================================================
            // visibilitas Edit/Delete berdasarkan guserpk 35 vs user lain.
            // - guserpk 35   -> HANYA baris yang PUNYA grade boleh diedit/dihapus.
            // - guserpk lain -> HANYA baris yang TIDAK PUNYA grade boleh
            //                   diedit/dihapus (kebalikannya).
            // ============================================================
            const gradeAllowsAction = isGuserpk35 ? hasGrade : !hasGrade;

            let showEdit = true;
            if (hasGrade && status === 2) {
                showEdit = false;
            }
            showEdit = showEdit && gradeAllowsAction;

            const showDelete = hasGrade
                ? gradeAllowsAction
                : (!window.popkAlreadyShipped && gradeAllowsAction);

            let buttons = '';
            if (showEdit) buttons += editBtn;
            if (showDelete) buttons += deleteBtn;

            return wrap(buttons);
        }

        function editRowByIndex(index) {
            const row = $('#dgTransfer').datagrid('getRows')[index];
            if (row) fillEditForm(row);
        }

        // ============================================================
        // EMPTY STATE (Detail Data Polibag)
        // ============================================================
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
            window.popkAlreadyShipped = !!data.has_shipped;

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
        $(function () {
            $('#dgTransfer').datagrid();
            initFilterLineCombobox();
        });

        // ============================================================
        // RELOAD GRID & BREAKDOWN SUMMARY (TANPA RELOAD HALAMAN)
        // Dipanggil setelah Add/Edit (saveTransferAjax) DAN setelah
        // Delete (confirmDeleteTransfer) -- satu titik yang sama,
        // supaya keduanya selalu konsisten.
        // ============================================================
        function reloadTransferGrid(cr) {
            if (!$('#dgTransfer').data('datagrid')) return;

            if (typeof cr !== 'undefined') {
                $('#dgTransfer').datagrid('load', { cr: cr, mif: '{{ $mif }}' });
            } else {
                $('#dgTransfer').datagrid('reload');
            }
        }

        function reloadBreakdownSummary() {
            $.get("{{ url('polibag') }}/" + currentPopk + "/breakdown-summary", function (html) {
                $('#breakdownSummaryWrapper').html(html);
            });
        }

        // ============================================================
        // TANGGAL MASUK -- HELPER & PEMBATASAN RENTANG
        //
        // ATURAN:
        //  - Add  : HANYA hari ini & 1 hari sebelumnya (rentang 2 hari).
        //  - Edit : [tgl tersimpan-1, tgl tersimpan+1], TAPI batas atas
        //           (max) tidak boleh melebihi HARI INI -- jadi kalau
        //           tgl tersimpan = hari ini, rentangnya otomatis jadi
        //           [kemarin, hari ini] juga (sama seperti Add), karena
        //           "besok" (tersimpan+1) dipangkas ke hari ini.
        //
        // 3 lapis proteksi (semua pakai helper getAddDateRange/
        // getEditDateRange yang SAMA, supaya konsisten):
        //  1) atribut min/max native <input type="date">  -> membatasi
        //     kalender popup.
        //  2) event 'change' di bawah                      -> menutup
        //     celah ketik manual di luar rentang.
        //  3) guard di awal saveTransferAjax()              -> jaring
        //     pengaman terakhir sebelum data terkirim.
        // ============================================================
        let currentDateMode = null;       // 'add' | 'edit'
        let currentEditStoredDate = null; // tanggal tersimpan (YYYY-MM-DD), hanya diisi saat Edit

        function todayDateString() {
            const now = new Date();
            const yy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            return `${yy}-${mm}-${dd}`;
        }

        // shiftDateString('2024-05-10 00:00:00', -1) -> '2024-05-09'
        // shiftDateString('2024-05-10', 1)            -> '2024-05-11'
        function shiftDateString(dateStr, days) {
            const datePart = String(dateStr).split(' ')[0];
            const [y, m, d] = datePart.split('-').map(Number);
            const dt = new Date(y, m - 1, d); // local, bukan UTC -- aman timezone
            dt.setDate(dt.getDate() + days);

            const yy = dt.getFullYear();
            const mm = String(dt.getMonth() + 1).padStart(2, '0');
            const dd = String(dt.getDate()).padStart(2, '0');
            return `${yy}-${mm}-${dd}`;
        }

        // BARU: rentang untuk mode ADD -- SELALU [kemarin, hari ini],
        // BUKAN "hari ini dan semua tanggal sebelumnya" (unbounded).
        function getAddDateRange() {
            const today = todayDateString();
            const yesterday = shiftDateString(today, -1);
            return { min: yesterday, max: today };
        }

        // BARU: rentang untuk mode EDIT -- [tersimpan-1, tersimpan+1],
        // TAPI max di-pangkas supaya tidak pernah melebihi hari ini.
        function getEditDateRange(storedDateStr) {
            const stored = String(storedDateStr).split(' ')[0];
            const today = todayDateString();

            const min = shiftDateString(stored, -1);
            let max = shiftDateString(stored, 1);
            if (max > today) max = today; // pangkas -- inilah yang bikin
                                           // "tersimpan = hari ini" otomatis
                                           // jadi [kemarin, hari ini].

            return { min, max };
        }

        function setTanggalRangeForAdd() {
            const input = document.querySelector('#mainTransferForm input[name="tanggal"]');
            if (!input) return;

            const { min, max } = getAddDateRange();
            input.setAttribute('min', min);
            input.setAttribute('max', max);

            currentDateMode = 'add';
            currentEditStoredDate = null;
        }

        function setTanggalRangeForEdit(storedDateStr) {
            const input = document.querySelector('#mainTransferForm input[name="tanggal"]');
            if (!input || !storedDateStr) return;

            const { min, max } = getEditDateRange(storedDateStr);
            input.setAttribute('min', min);
            input.setAttribute('max', max);

            currentDateMode = 'edit';
            currentEditStoredDate = String(storedDateStr).split(' ')[0];
        }

        // Lapis ke-2: validasi aktif saat tanggal berubah (menutup celah ketik manual).
        $(document).on('change', '#mainTransferForm input[name="tanggal"]', function () {
            const input = this;
            const val = input.value;
            if (!val) return;

            if (currentDateMode === 'add') {
                const { min, max } = getAddDateRange();
                if (val > max) {
                    showToast('warning', `Tanggal Masuk maksimal ${max} (hari ini).`);
                    input.value = max;
                } else if (val < min) {
                    showToast('warning', `Tanggal Masuk minimal ${min} (kemarin).`);
                    input.value = min;
                }
            } else if (currentDateMode === 'edit' && currentEditStoredDate) {
                const { min, max } = getEditDateRange(currentEditStoredDate);
                if (val < min) {
                    showToast('warning', `Tanggal Masuk minimal ${min}.`);
                    input.value = min;
                } else if (val > max) {
                    showToast('warning', `Tanggal Masuk maksimal ${max}.`);
                    input.value = max;
                }
            }
        });

        // ============================================================
        // ADD / EDIT POLIBAG VIA AJAX (modal-transfer.blade.php)
        //
        // PENTING: tombol Simpan di modal-transfer.blade.php HARUS
        // dipasang sebagai <button type="button" onclick="saveTransferAjax()">,
        // BUKAN <button type="submit">, supaya tidak native form-submit
        // (reload halaman) dan supaya reload tabel + breakdown di bawah
        // ini benar-benar terpanggil.
        //
        // CATATAN: fungsi ini JUGA didefinisikan di modal-transfer.blade.php.
        // Karena keduanya fungsi global bernama sama, yang paling AKHIR
        // dimuat di DOM yang menang (menimpa definisi sebelumnya). Kalau
        // guard tanggal di bawah ini ternyata tidak berpengaruh, hapus
        // definisi duplikat di modal-transfer.blade.php supaya versi INI
        // yang dipakai.
        // ============================================================
        function saveTransferAjax() {
            hideTransferAlert();
            $('#mainTransferForm .is-invalid').removeClass('is-invalid');
            $('#mainTransferForm .invalid-feedback, #mainTransferForm td .text-danger').text('');

            // Lapis ke-3: guard terakhir sebelum submit.
            const tglInput = document.querySelector('#mainTransferForm input[name="tanggal"]');
            if (tglInput && tglInput.value) {
                if (currentDateMode === 'add') {
                    const { min, max } = getAddDateRange();
                    if (tglInput.value < min || tglInput.value > max) {
                        showTransferAlert(`Tanggal Masuk harus di antara <b>${min}</b> dan <b>${max}</b>.`);
                        tglInput.classList.add('is-invalid');
                        return;
                    }
                }

                if (currentDateMode === 'edit' && currentEditStoredDate) {
                    const { min, max } = getEditDateRange(currentEditStoredDate);
                    if (tglInput.value < min || tglInput.value > max) {
                        showTransferAlert(`Tanggal Masuk harus di antara <b>${min}</b> dan <b>${max}</b>.`);
                        tglInput.classList.add('is-invalid');
                        return;
                    }
                }
            }

            $.ajax({
                url: "{{ route('transfer.save') }}",
                method: 'POST',
                data: $('#mainTransferForm').serialize(),
                beforeSend: function () {
                    $('#btnSaveTransfer').prop('disabled', true);
                },
                success: function (res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('transferModal'))?.hide();

                    // Add/Edit sukses -> reload tabel Polibag + Breakdown Size & Qty
                    reloadTransferGrid();
                    reloadBreakdownSummary();
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        const errors = xhr.responseJSON.errors;

                        Object.keys(errors).forEach(function (field) {
                            const $field = $(`#mainTransferForm [name="${field}"]`);
                            $field.addClass('is-invalid');
                            $field.siblings('.invalid-feedback').text(errors[field][0]);
                        });

                        showTransferAlert(xhr.responseJSON.title || 'Periksa kembali data yang diisi.');
                    } else {
                        const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                        showToast(res.icon, res.title);
                    }
                },
                complete: function () {
                    $('#btnSaveTransfer').prop('disabled', false);
                }
            });
        }

        // ============================================================
        // DELETE POLIBAG VIA AJAX
        // ============================================================
        let deleteId = null;

        function openDeleteModal(id) {
            deleteId = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        function confirmDeleteTransfer() {
            if (!deleteId) return;

            $.ajax({
                url: "{{ route('transfer.delete', '') }}/" + deleteId + "?mif={{ $mif }}",
                method: 'DELETE',
                beforeSend: function () {
                    $('#btnConfirmDeleteTransfer').prop('disabled', true);
                },
                success: function (res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal'))?.hide();

                    // Delete sukses -> reload tabel Polibag + Breakdown Size & Qty
                    reloadTransferGrid();
                    reloadBreakdownSummary();

                    deleteId = null;
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    showToast(res.icon, res.title);
                },
                complete: function () {
                    $('#btnConfirmDeleteTransfer').prop('disabled', false);
                }
            });
        }

        // ============================================================
        // HELPER FORM (datebox fallback, isi form Edit, buka modal Add)
        // ============================================================
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
            $('#mainTransferForm select[name="grade"]').val(row.grade ?? '');

            $('#transferModalTitle').text('Edit Polibag');
            $('#bjpk').val(row.bjpk);
            $('[name=tanggal]').val(row.tanggal);
            $('[name=linepk]').val(row.linepk);

            // Batasi Tanggal Masuk -- rentang [tgl tersimpan-1, tgl tersimpan+1],
            // dipangkas supaya tidak melebihi hari ini.
            setTanggalRangeForEdit(row.tanggal);

            @foreach ($activeSizes as $i => $size)
                $('[name=qty{{ $i }}]').val(row.qty{{ $i }});
            @endforeach

            new bootstrap.Modal(document.getElementById('transferModal')).show();
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

        function openTransferModal() {
            hideTransferAlert();
            $('#transferModalTitle').text('Add Polibag');
            $('#bjpk').val('');

            const form = document.getElementById('mainTransferForm');
            if (form) form.reset();
            $('#mainTransferForm input[name="tanggal"]').val('');
            $('#mainTransferForm select[name="linepk"]').val('');
            $('#mainTransferForm input[type="number"]').val('');
            $('#mainTransferForm .is-invalid').removeClass('is-invalid');
            $('#mainTransferForm .invalid-feedback, #mainTransferForm td .text-danger').text('');

            // Batasi Tanggal Masuk -- hanya hari ini & 1 hari sebelumnya.
            setTanggalRangeForAdd();

            let modalEl = document.getElementById('transferModal');
            let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }

        function goBack() {
            const isStokSisaUser = guserpk === 35;

            const refPath    = isStokSisaUser ? '/stok-sisa' : '/transfer';
            const indexRoute = isStokSisaUser
                ? "{{ route('stok-sisa.index') }}"
                : "{{ route('transfer.index') }}";

            if (document.referrer && document.referrer.indexOf(refPath) !== -1) {
                window.history.back();
            } else {
                window.location.href = indexRoute;
            }
        }
    </script>
@endsection