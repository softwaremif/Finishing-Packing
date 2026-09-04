@extends('layout.main')
@section('css_custom')
    <style>
        :root {
            --primary-color: #4f46e5;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --bg-header: #f8fafc;
            --border-color: #e2e8f0;
        }

        .info-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }

        .btn-icon-custom:hover {
            background-color: #f8fafc !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1) !important;
            color: #1e293b !important;
            transform: translateX(-3px);
        }

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
        <div
            class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-3 mb-4 gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:void(0)" onclick="goBack()"
                    class="btn btn-icon-custom d-inline-flex align-items-center justify-content-center shadow-sm border bg-white text-secondary rounded-circle"
                    style="width:38px;height:38px;" title="Kembali ke Daftar Data OP">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold text-dark mb-0" style="font-size:1.15rem;">Input Data Transfer to Finishing</h4>
                </div>
            </div>
        </div>

        {{-- ============================================================
            HEADER INFO UTAMA --.
            ============================================================ --}}
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

        {{-- BREAKDOWN SIZE & QTY -- di-reload via AJAX --}}
        <div id="breakdownSummaryWrapper">
            @include('menu.transfer-finishing.partials.breakdown_summary')
        </div>

        {{-- DETAIL DATA TRANSFER TO FINISHING --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="fw-bold text-dark d-flex align-items-center" style="font-size:14px;">
                    <span class="rounded me-2" style="width:4px;height:16px;display:inline-block;background:#64748b;"></span>
                    Detail Data Transfer to Finishing
                </div>
            </div>
            <div class="card-body p-0">
                <x-table-bootstrap
                    id="dgTransfer"
                    url="{{ route('tf_finishing.detail.list', $popk) }}"
                    :page-size="50"
                    sort-dropdown
                    sort-name="sort"
                    sort-default="desc"
                    sort-asc-label="Awal tanggal"
                    sort-desc-label="Akhir tanggal"
                >
                    <x-slot name="filters">
                        <div class="p-0">
                            <input data-dg-filter="cr" data-dg-filter-type="select"
                                data-dg-chip-label="Line"
                                data-dg-options='[
                                    {"value":"","text":"Semua Line"}
                                    @foreach ($filterLines as $line)
                                        ,{"value":{{ $line->linepk }},"text":{{ json_encode($line->linenm) }}}
                                    @endforeach
                                ]'
                                data-dg-default="" style="width:170px">
                        </div>
                        <div class="p-0">
                            <button class="btn btn-dark btn-sm d-inline-flex align-items-center px-2.5 py-1.5 fw-semibold ms-auto"
                                style="font-size:12px;border-radius:6px;background-color:#1e293b;border-color:#1e293b;"
                                onclick="openTransferModal()">
                                <i class="fas fa-plus me-1.5 small"></i> Add Transfer to Finishing
                            </button>
                        </div>
                    </x-slot>
                    <tr>
                        <th data-field="" rowspan="2" style="width:75px;" data-align="center" data-formatter="formatAction">Aksi</th>
                        <th data-field="tanggal" rowspan="2" style="width:150px;" data-align="left" data-formatter="formatDate">Tanggal</th>
                        <th data-field="linenm" rowspan="2" style="width:150px;" data-align="center" data-formatter="formatLine">Line</th>
                        <th colspan="{{ count($sizes) }}" class="text-center">
                            Size
                            @if (!empty($mop->secsz))
                                <span class="text-muted fw-bold text-lowercase">({{ $mop->secsz }})</span>
                            @endif
                        </th>
                        <th data-field="pcs" rowspan="2" style="width:90px;" data-align="right" data-formatter="formatTotal">Total Pcs</th>
                    </tr>
                    <tr>
                        @foreach ($sizes as $s)
                            <th data-field="qty_{{ $s->mopdtpk }}" style="min-width:100px;" data-align="center" data-formatter="formatQty">{{ $s->ukuran }}</th>
                        @endforeach
                    </tr>
                </x-table-bootstrap>
            </div>
        </div>
    </div>
    @include('menu.transfer-finishing.modal-transfer')
    @include('menu.transfer-finishing.modal-delete-transfer')
@endsection
@section('js_custom')
    <script>
        const currentPopk = {{ $popk }};
        
        window.tfSizes = @json($sizes->map(fn($s) => ['mopdtpk' => $s->mopdtpk, 'ukuran' => $s->ukuran])->values());

        function formatLine(value) {
            if (!value) return '<span class="dg-empty-cell">-</span>';
            const stripped = String(value).replace(/^line\s*/i, '').trim();
            const label = stripped || value;
            return `<div style="text-align:center;">${label}</div>`;
        }


        function formatDate(value, row) {
            if (!value) return '<span class="dg-empty-cell">-</span>';
            let parts = String(value).split(' ')[0].split('-');
            if (parts.length !== 3) return value;

            let dateLabel = `${parts[2]}/${parts[1]}/${parts[0]}`;

            // BARU: badge Barcode untuk baris yang bukan manual (source='barcode')
            let sourceTag = '';
            if (row.source === 'barcode') {
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

       function formatAction(value, row, index) {
            if (!row.tfpbpk) {
                return '<span class="dg-empty-cell" title="Data dari Barcode, tidak dapat diubah/dihapus di sini">—</span>';
            }
        
            const dateOnly = String(row.tanggal).split(' ')[0];
            const dateEditable = isDateEditable(dateOnly);
    
            if (!dateEditable) {
                return '<span class="dg-empty-cell" title="Tanggal di luar rentang yang boleh diubah/dihapus">-</span>';
            }
        
            const editBtn = `
                <button type="button" class="dg-action-btn dg-edit" title="Ubah" onclick="editRowByIndex(${index})">
                    <i class="fas fa-pen-to-square"></i>
                </button>`;
        
            const deleteBtn = `
                <button type="button" class="dg-action-btn dg-delete" title="Hapus" onclick="openDeleteModal(${row.tfpbpk})">
                    <i class="fas fa-trash-alt"></i>
                </button>`;
        
            return `
                <div class="d-flex justify-content-center gap-1">
                    ${editBtn}
                    ${deleteBtn}
                </div>
            `;
        }

        function formatQty(value) {
            return (value === null || value === undefined || value === '') ? '<span class="dg-empty-cell">-</span>' : value;
        }

        function formatTotal(value) {
            return `<div style="text-align:right;">${value ?? 0}</div>`;
        }

        function editRowByIndex(index) {
            const row = window.BsTable.getRow('dgTransfer', index);
            if (row) fillEditForm(row);
        }

        function reloadTransferGrid(cr) {
            window.BsTable.reload('dgTransfer');
        }

        function reloadBreakdownSummary() {
            $.get("{{ route('tf_finishing.breakdown-summary', $popk) }}", function(html) {
                $('#breakdownSummaryWrapper').html(html);
            });
        }

        let currentDateMode = null; // 'add' | 'edit'
        let currentEditStoredDate = null;

        function todayDateString() {
            const now = new Date();
            const yy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            return `${yy}-${mm}-${dd}`;
        }

        function shiftDateString(dateStr, days) {
            const datePart = String(dateStr).split(' ')[0];
            const [y, m, d] = datePart.split('-').map(Number);
            const dt = new Date(y, m - 1, d);
            dt.setDate(dt.getDate() + days);
            const yy = dt.getFullYear();
            const mm = String(dt.getMonth() + 1).padStart(2, '0');
            const dd = String(dt.getDate()).padStart(2, '0');
            return `${yy}-${mm}-${dd}`;
        }

        function dateStringDay(dateStr) {
            const [y, m, d] = String(dateStr).split(' ')[0].split('-').map(Number);
            return new Date(y, m - 1, d).getDay();
        }

        function isSundayDate(dateStr) {
            return dateStringDay(dateStr) === 0;
        }

        function getPreviousWorkDay(dateStr) {
            let d = shiftDateString(dateStr, -1);
            if (isSundayDate(d)) d = shiftDateString(d, -1);
            return d;
        }

        function getAddDateRange() {
            const today = todayDateString();
            const min = getPreviousWorkDay(today);
            return {
                min,
                max: today
            };
        }

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
                showToast('warning', `Tanggal maksimal ${max}.`);
                input.value = max;
                return;
            }
            if (val < min) {
                showToast('warning', `Tanggal minimal ${min}.`);
                input.value = min;
                return;
            }
            if (isSundayDate(val)) {
                showToast('warning', 'Hari Minggu tidak dapat dipilih.');
                input.value = min;
            }
        });

        let deleteId = null;

        function openDeleteModal(id) {
            deleteId = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        function confirmDeleteTransfer() {
            if (!deleteId) return;
            $.ajax({
                url: "{{ route('tf_finishing.delete', '') }}/" + deleteId,
                method: 'DELETE',
                beforeSend: function() {
                    $('#btnConfirmDeleteTransfer').prop('disabled', true);
                },
                success: function(res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal'))?.hide();
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

        function goBack() {
            if (document.referrer && document.referrer.indexOf('/tf-finishing') !== -1) {
                window.history.back();
            } else {
                window.location.href = "{{ route('tf_finishing.index') }}";
            }
        }
    </script>
@endsection
