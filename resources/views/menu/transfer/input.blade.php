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
            opacity: .0;
        }

        /* =========================================================
        DC BREAKDOWN BY LINE
        ========================================================= */

        .dc-line-row {
            background: #fafbfc;
            border-top: 0 !important;
        }

        .dc-line-row td {
            height: 32px;
            padding-top: 5px !important;
            padding-bottom: 5px !important;
            border-bottom: 0 !important;
        }

        /* label line */
        .dc-line-label {
            position: relative;
            display: flex;
            align-items: center;
            min-height: 22px;
            padding-left: 22px;
        }

        /* garis vertikal breakdown */
        .dc-line-connector {
            position: absolute;
            left: 8px;
            top: -5px;
            bottom: -5px;
            width: 1px;
            background: #cbd5e1;
        }

        /* garis horizontal menuju nama line */
        .dc-line-connector::after {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            width: 10px;
            height: 1px;
            background: #cbd5e1;
        }

        /* icon ↳ */
        .dc-line-icon {
            position: relative;
            z-index: 1;
            margin-right: 7px;
            font-size: 13px;
            line-height: 1;
            color: #94a3b8;
        }

        /* nama line */
        .dc-line-text {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
        }

        /* badge DC */
        .dc-line-badge {
            margin-left: 7px;
            padding: 2px 5px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background: #f1f5f9;
            color: #94a3b8;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: .4px;
            line-height: 1;
        }

        /* qty */
        .dc-line-qty {
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
        }

        /* total */
        .dc-line-total {
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            border-left: 1px solid #e2e8f0 !important;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4 px-4">

        {{-- ============================================================
             HEADER HALAMAN
             ============================================================ --}}
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
                        {{-- {{ session('guserpk') == 35 ? 'Input Data Grade Sisa' : 'Input Data Polibag' }} --}}
                        {{ $isStokSisaMode ? 'Input Data Grade Sisa' : 'Input Data Polibag' }}
                    </h4>
                </div>
            </div>
        </div>

        @php
            $infoSections = [
                [ // Section 1
                    ['icon' => 'fas fa-layer-group', 'iconBg' => 'bg-info-subtle', 'iconColor' => 'text-info', 'label' => 'OP', 'value' => $dt->OP ?? '-'],
                    ['icon' => 'fas fa-certificate', 'iconColor' => 'text-dark', 'label' => 'License PO Ref', 'value' => $dt->poref ?? '-'],
                    ['icon' => 'fas fa-map-marker-alt', 'iconBg' => 'bg-danger-subtle', 'iconColor' => 'text-danger', 'label' => 'Place', 'value' => $dt->customer ?? '-'],
                    ['icon' => 'fas fa-calendar-alt', 'iconBg' => 'bg-warning-subtle', 'iconColor' => 'text-warning', 'label' => 'Season', 'value' => $dt->season ?? '-'],
                    ['icon' => 'fas fa-user-tie', 'iconBg' => 'bg-success-subtle', 'iconColor' => 'text-success', 'label' => 'Buyer', 'value' => $dt->buyer ?? '-'],
                    ['icon' => 'fas fa-tshirt', 'iconBg' => 'bg-secondary-subtle', 'iconColor' => 'text-secondary', 'label' => 'Style Code', 'value' => $dt->style ?? '-'],
                    ['icon' => 'fas fa-palette', 'iconColor' => 'text-dark', 'label' => 'Color / Material', 'value' => $dt->material ?? '-'],
                    ['icon' => 'fas fa-align-left', 'iconColor' => 'text-dark', 'label' => 'Description', 'value' => $dt->silhouette ?? '-', 'type' => 'description'],
                ],
                [ // Section 2
                    ['icon' => 'fas fa-hashtag', 'iconBg' => 'bg-primary-subtle', 'iconColor' => 'text-primary', 'label' => 'PO Number', 'value' => $dt->POno ?? '-'],
                ],
            ];
        @endphp

        <x-details.info-card :sections="$infoSections" />

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
                    {{ $isStokSisaMode ? 'Detail Data Grade Sisa' : 'Detail Data Polibag' }}
                </div>
            </div>
            <div class="card-body p-0">
                <x-table-bootstrap
                    id="dgTransfer"
                    url="{{ route('transfer.detail.list', $dt->popk) }}"
                    :page-size="50"
                    sort-dropdown
                    sort-name="sort"
                    sort-default="desc"
                    sort-asc-label="Awal tanggal"
                    sort-desc-label="Akhir tanggal"
                    on-load-success="onLoadPolibagTable"
                >
                    <x-slot name="filters">
                        {{-- Filter Line -- LOCAL options dari $lines. --}}
                        <div class="p-0">
                            <input data-dg-filter="cr" data-dg-filter-type="select"
                                data-dg-chip-label="Line"
                                data-dg-options='[
                                    {"value":"","text":"Semua Line"}
                                    @foreach ($filterLines as $line)
                                        ,{"value":{{ $line->linepk }},"text":{{ json_encode($line->linenm) }}}
                                    @endforeach
                                ]'
                                data-dg-default="{{ $cr }}" style="width:170px">
                        </div>

                        <input type="hidden" data-dg-filter="mif" data-dg-filter-type="search"
                            data-dg-chip-hide="true" value="{{ $mif }}">
                        <div class="p-0">
                            <button class="btn btn-dark btn-sm d-inline-flex align-items-center px-2.5 py-1.5 fw-semibold ms-auto"
                                style="font-size: 12px; border-radius: 6px; background-color: #1e293b; border-color: #1e293b;"
                                onclick="openTransferModal()">
                                <i class="fas fa-plus me-1.5 small"></i>
                                {{ $isStokSisaMode ? 'Add Grade Sisa' : 'Add Polibag' }}
                            </button>
                        </div>
                    </x-slot>
        
                    {{-- BARIS 1 -- Aksi/Tanggal/Line/Total Pcs/Grade rowspan=2,
                        "Size" jadi header grup. --}}
                    <tr>
                        <th data-field="" rowspan="2" style="width:70px;" data-align="center" data-formatter="formatAction">Aksi</th>
                        <th data-field="tanggal" rowspan="2" style="width:130px;" data-align="left" data-formatter="formatDate">Tanggal Masuk</th>
                        <th data-field="linenm" rowspan="2" style="width:120px;" data-align="left" data-formatter="formatLine">Line</th>
                        <th colspan="{{ count($activeSizes) }}" class="text-center">
                            Size
                            @if (!empty($dt->secsz))
                                <span class="text-muted fw-bold text-lowercase">({{ $dt->secsz }})</span>
                            @endif
                        </th>
                        <th data-field="pcs" rowspan="2" style="width:100px;" data-align="right" data-formatter="formatTotal">Total Pcs</th>
                        <th data-field="grade" rowspan="2" style="width:100px;" data-align="center">Grade</th>
                    </tr>
                    {{-- BARIS 2 -- size individual saja. --}}
                    <tr>
                        @foreach ($activeSizes as $i => $size)
                            <th data-field="qty{{ $i }}" style="min-width:85px;" data-align="center" data-formatter="formatQty">{{ $size }}</th>
                        @endforeach
                    </tr>
                </x-table-bootstrap>
            </div>
        </div>

    </div>
    @include('menu.transfer.modal-transfer')
    @include('menu.transfer.modal-delete-transfer')
@endsection

@section('js_custom')
    <script>
        // ID PO saat ini, dipakai untuk reload breakdown summary via AJAX
        const isStokSisaMode = @json($isStokSisaMode);
        const currentPopk = {{ $dt->popk }};

        function formatLine(value) {
            if (!value) return '<span class="dg-empty-cell">-</span>';
            const stripped = String(value).replace(/^line\s*/i, '').trim();
            const label = stripped || value;
            return `<div style="text-align:center;">${label}</div>`;
        }

        function formatDate(value, row) {
            if (!value) return '<span class="dg-empty-cell">-</span>';

            let datePart = String(value).split(' ')[0];
            let parts = datePart.split('-');
            if (parts.length !== 3) return value;

            let [year, month, day] = parts;
            let dateLabel = `${day}/${month}/${year}`;

            // FIX: hapus kasus 'merged' -- bj dan output TIDAK PERNAH digabung
            // lagi jadi 1 baris, jadi source cuma 'bj' atau 'output' saja.
            let sourceTag = '';
            if (row.source === 'output') {
                sourceTag = ' <span class="badge bg-secondary-subtle text-secondary" style="font-size:9px;">Barcode</span>';
            }

            let jamHtml = '';
            if (row && row.waktu) {
                const timePart = String(row.waktu).split(' ')[1] || '';
                const jam = timePart ? timePart.substring(0, 5) : '';
                if (jam) jamHtml = `<div style="font-size:10.5px;color:#94a3b8;">${jam}</div>`;
            }

            return `${dateLabel}${sourceTag}${jamHtml}`;
        }

        function formatQty(value) {
            if (value === null || value === undefined || value === '') {
                return '<span class="dg-empty-cell">-</span>';
            }
            return value;
        }

        function formatTotal(value) {
            return `<div style="text-align:right;">${value ?? 0}</div>`;
        }

        function isRowDateEditable(row) {
            if (!row.tanggal) return false;

            const tglOnly = String(row.tanggal).split(' ')[0];
            const today = todayDateString();
            const yesterday = shiftDateString(today, -1);

            return tglOnly === today || tglOnly === yesterday;
        }

        function formatAction(value, row, index) {

            if (!row.bjpk) {
                return '<span class="dg-empty-cell" title="Data dari Output, tidak dapat diubah di sini">—</span>';
            }

            const dateOnly = String(row.tanggal).split(' ')[0];
            const dateEditable = isDateEditable(dateOnly);

            const editBtn = `
                <button type="button" class="dg-action-btn dg-edit" title="Ubah" onclick="editRowByIndex(${index})">
                    <i class="fas fa-pen-to-square"></i>
                </button>`;

            // BARU: versi disabled -- tetap tampil (memberi konteks kenapa tidak
            // bisa diedit) tapi tidak bisa diklik.
            const editBtnDisabled = `
                <button type="button" class="dg-action-btn dg-edit" style="opacity:.35; cursor:not-allowed;"
                    title="Tanggal Masuk di luar rentang yang boleh diubah (hanya hari ini / hari kerja sebelumnya)" disabled>
                    <i class="fas fa-pen-to-square"></i>
                </button>`;

            const deleteBtn = `
                <button type="button" class="dg-action-btn dg-delete" title="Hapus" onclick="openDeleteModal(${row.bjpk})">
                    <i class="fas fa-trash-alt"></i>
                </button>`;

            const deleteBtnDisabled = `
                <button type="button" class="dg-action-btn dg-delete" style="opacity:.35; cursor:not-allowed;"
                    title="Tanggal Masuk di luar rentang yang boleh dihapus (hanya hari ini / hari kerja sebelumnya)" disabled>
                    <i class="fas fa-trash-alt"></i>
                </button>`;

            const wrap = (html) => `<div class="d-flex justify-content-center gap-1">${html}</div>`;

            const grade = row.grade;
            const hasGrade = grade !== null && grade !== undefined && String(grade).trim() !== '';
            const status = Number(row.status);

            const gradeAllowsAction = isStokSisaMode ? hasGrade : !hasGrade;

            let showEdit = true;
            if (hasGrade && status === 2) {
                showEdit = false;
            }
            showEdit = showEdit && gradeAllowsAction;

            const showDelete = hasGrade ?
                gradeAllowsAction :
                (!window.popkAlreadyShipped && gradeAllowsAction);

            let buttons = '';
            if (showEdit && dateEditable) buttons += editBtn;
            if (showDelete && dateEditable) buttons += deleteBtn;

            return wrap(buttons);
        }

        function onLoadPolibagTable(data) {
            window.popkAlreadyShipped = !!data.has_shipped;
        }
        
        function editRowByIndex(index) {
            const row = window.BsTable.getRow('dgTransfer', index);
            if (row) fillEditForm(row);
        }

        /* INIT */
        $(function () {
            reloadBreakdownSummary();
        });

        function reloadTransferGrid(cr) {
            window.BsTable.reload('dgTransfer');
        }

        function reloadBreakdownSummary() {
            $.get("{{ route('transfer.breakdown-summary', $dt->popk) }}", {
                mif: '{{ $mif }}'
            }, function(html) {
                $('#breakdownSummaryWrapper').html(html);
            });
        }


        let currentDateMode = null; // 'add' | 'edit'
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

        function dateStringDay(dateStr) {
            const [y, m, d] = String(dateStr).split(' ')[0].split('-').map(Number);
            return new Date(y, m - 1, d).getDay(); // 0 = Minggu
        }

        function isSundayDate(dateStr) {
            return dateStringDay(dateStr) === 0;
        }

        // Mundur 1 hari dari dateStr -- kalau hasilnya jatuh Minggu, mundur
        // SATU HARI LAGI (ke Sabtu). Contoh: Senin -> Minggu (skip) -> Sabtu.
        function getPreviousWorkDay(dateStr) {
            let d = shiftDateString(dateStr, -1);
            if (isSundayDate(d)) d = shiftDateString(d, -1);
            return d;
        }

        // Maju 1 hari dari dateStr -- kalau hasilnya jatuh Minggu, maju SATU
        // HARI LAGI (ke Senin). Contoh: Sabtu -> Minggu (skip) -> Senin.
        function getNextWorkDay(dateStr) {
            let d = shiftDateString(dateStr, 1);
            if (isSundayDate(d)) d = shiftDateString(d, 1);
            return d;
        }

        // BARU: rentang untuk mode ADD -- SELALU [kemarin, hari ini],
        // BUKAN "hari ini dan semua tanggal sebelumnya" (unbounded).
        function getAddDateRange() {
            const today = todayDateString();
            const min = getPreviousWorkDay(today);
            return {
                min,
                max: today
            };
        }

        // BARU: rentang untuk mode EDIT -- [tersimpan-1, tersimpan+1],
        // TAPI max di-pangkas supaya tidak pernah melebihi hari ini.
        function getEditDateRange(storedDateStr) {
            return getAddDateRange();
        }

        function isDateEditable(dateStr) {
            if (!dateStr) return false;
            const dateOnly = String(dateStr).split(' ')[0];
            const {
                min,
                max
            } = getAddDateRange();
            return dateOnly === min || dateOnly === max;
        }

        function setTanggalRangeForAdd() {
            const input = document.querySelector('#mainTransferForm input[name="tanggal"]');
            if (!input) return;

            const {
                min,
                max
            } = getAddDateRange();
            input.setAttribute('min', min);
            input.setAttribute('max', max);

            currentDateMode = 'add';
            currentEditStoredDate = null;
        }

        function setTanggalRangeForEdit(storedDateStr) {
            const input = document.querySelector('#mainTransferForm input[name="tanggal"]');
            if (!input || !storedDateStr) return;

            const {
                min,
                max
            } = getEditDateRange(storedDateStr);
            input.setAttribute('min', min);
            input.setAttribute('max', max);

            currentDateMode = 'edit';
            currentEditStoredDate = String(storedDateStr).split(' ')[0];
        }

        // Lapis ke-2: validasi aktif saat tanggal berubah (menutup celah ketik manual).
        $(document).on('change', '#mainTransferForm input[name="tanggal"]', function() {
            const input = this;
            const val = input.value;
            if (!val) return;

            let range = null;
            if (currentDateMode === 'add') {
                range = getAddDateRange();
            } else if (currentDateMode === 'edit' && currentEditStoredDate) {
                range = getEditDateRange(currentEditStoredDate);
            }
            if (!range) return;

            const {
                min,
                max
            } = range;

            if (val > max) {
                showToast('warning', `Tanggal Masuk maksimal ${max}.`);
                input.value = max;
                return;
            }
            if (val < min) {
                showToast('warning', `Tanggal Masuk minimal ${min}.`);
                input.value = min;
                return;
            }

            // BARU: native date input bisa saja diketik manual jadi hari
            // Minggu meski masih di dalam rentang min/max -- min/max sendiri
            // dijamin tidak pernah Minggu, jadi aman dikembalikan ke min.
            if (isSundayDate(val)) {
                showToast('warning', 'Hari Minggu tidak dapat dipilih untuk Tanggal Masuk.');
                input.value = min;
            }
        });

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
                beforeSend: function() {
                    $('#btnConfirmDeleteTransfer').prop('disabled', true);
                },
                success: function(res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal'))?.hide();

                    // Delete sukses -> reload tabel Polibag + Breakdown Size & Qty
                    reloadTransferGrid();
                    reloadBreakdownSummary();

                    deleteId = null;
                },
                error: function(xhr) {
                    const res = xhr.responseJSON || {
                        icon: 'error',
                        title: 'Terjadi kesalahan.'
                    };
                    showToast(res.icon, res.title);
                },
                complete: function() {
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

            // FIX: <input type="date"> HANYA menerima "YYYY-MM-DD" -- kalau
            // row.tanggal masih ada jam-nya ("... 00:00:00"), browser menolak
            // set value dan input jadi KOSONG. Wajib di-strip dulu.
            const tglOnly = String(row.tanggal).split(' ')[0];
            $('[name=tanggal]').val(tglOnly);
            $('[name=linepk]').val(row.linepk);

            // Batasi Tanggal Masuk -- rentang [hari kerja sebelum, hari kerja
            // sesudah tersimpan], dipangkas supaya tidak melebihi hari ini,
            // dan tidak pernah jatuh di hari Minggu.
            setTanggalRangeForEdit(tglOnly);

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
        
            $('#mainTransferForm input[name="tanggal"]').val(todayDateString());
        
            let modalEl = document.getElementById('transferModal');
            let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }

        function goBack() {
            const refPath = isStokSisaMode ? '/stok-sisa/input' : '/polibag/input';
            const indexRoute = isStokSisaMode ?
                "{{ route('stok-sisa.index') }}" :
                "{{ route('transfer.index') }}";
            if (document.referrer && document.referrer.indexOf(refPath) !== -1) {
                window.history.back();
            } else {
                window.location.href = indexRoute;
            }
        }
    </script>
@endsection
