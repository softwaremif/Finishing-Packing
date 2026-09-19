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

        a i { transition: transform 0.2s ease; }
        a:hover i { transform: scale(1.2) translateX(-2px); }

        .info-card {
            background: #ffffff; border: 1px solid var(--border-color); border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .btn-icon-custom:hover {
            background-color: #f8fafc !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,.1) !important;
            color: #1e293b !important; transform: translateX(-3px);
        }

        #dgTransferWrapper .datagrid-wrap { border: none !important; border-radius: 0 0 12px 12px; overflow: hidden; }
        #dgTransferWrapper .datagrid-view, #dgTransferWrapper .datagrid-view * { box-sizing: border-box !important; }
        #dgTransferWrapper .datagrid-cell { color: #334155; font-size: 13px; padding: 10px 12px; }
        #dgTransferWrapper .datagrid-header, #dgTransferWrapper .datagrid-header-inner { background: #f9fafb !important; }
        #dgTransferWrapper .datagrid-header .datagrid-cell { color: #374151; font-weight: 600; text-align: center !important; }
        #dgTransferWrapper .datagrid-body .datagrid-cell { text-align: center; }
        #dgTransferWrapper .datagrid-row:hover td { background-color: #f1f5f9 !important; }
        #dgTransferWrapper .datagrid-view, #dgTransferWrapper .datagrid-header, #dgTransferWrapper .datagrid-header-inner,
        #dgTransferWrapper .datagrid-header-row, #dgTransferWrapper .datagrid-header-row td, #dgTransferWrapper .datagrid-body,
        #dgTransferWrapper .datagrid-row, #dgTransferWrapper .datagrid-body td, #dgTransferWrapper .datagrid-htable td,
        #dgTransferWrapper .datagrid-btable td { border: none !important; }
        #dgTransferWrapper .datagrid-pager { background-color: #ffffff; border-top: 1px solid var(--border-color); padding: 6px 4px; }
        #dgTransferWrapper .l-btn { border-radius: 6px !important; }

        .dg-action-btn { background: transparent; border: none; border-radius: 6px; cursor: pointer; padding: 4px 6px; transition: background-color .15s ease; }
        .dg-action-btn:hover { background-color: #f1f5f9; }
        .dg-action-btn.dg-edit { color: #359DD9; }
        .dg-action-btn.dg-delete { color: #dc2626; }
        .dg-empty-cell { color: #94a3b8; opacity: .0; }
        .badge-grade { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px; background: #ede9fe; color: #6d28d9; }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-4 px-4">

        {{-- HEADER HALAMAN -- SELALU "Input Data Grade Sisa" --}}
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-3 mb-4 gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:void(0)" onclick="goBack()"
                    class="btn btn-icon-custom d-inline-flex align-items-center justify-content-center shadow-sm border bg-white text-secondary rounded-circle"
                    style="width: 38px; height: 38px; transition: all 0.2s ease;" title="Kembali ke Daftar Data OP">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold text-dark mb-0" style="font-size: 1.15rem; letter-spacing: -0.3px;">
                        Input Data Grade Sisa
                    </h4>
                </div>
            </div>
        </div>

        @php
            $infoSections = [
                [
                    ['icon' => 'fas fa-layer-group', 'iconBg' => 'bg-info-subtle', 'iconColor' => 'text-info', 'label' => 'OP', 'value' => $dt->OP ?? '-'],
                    ['icon' => 'fas fa-certificate', 'iconColor' => 'text-dark', 'label' => 'License PO Ref', 'value' => $dt->poref ?? '-'],
                    ['icon' => 'fas fa-map-marker-alt', 'iconBg' => 'bg-danger-subtle', 'iconColor' => 'text-danger', 'label' => 'Place', 'value' => $dt->customer ?? '-'],
                    ['icon' => 'fas fa-calendar-alt', 'iconBg' => 'bg-warning-subtle', 'iconColor' => 'text-warning', 'label' => 'Season', 'value' => $dt->season ?? '-'],
                    ['icon' => 'fas fa-user-tie', 'iconBg' => 'bg-success-subtle', 'iconColor' => 'text-success', 'label' => 'Buyer', 'value' => $dt->buyer ?? '-'],
                    ['icon' => 'fas fa-tshirt', 'iconBg' => 'bg-secondary-subtle', 'iconColor' => 'text-secondary', 'label' => 'Style Code', 'value' => $dt->style ?? '-'],
                    ['icon' => 'fas fa-palette', 'iconColor' => 'text-dark', 'label' => 'Color / Material', 'value' => $dt->material ?? '-'],
                    ['icon' => 'fas fa-align-left', 'iconColor' => 'text-dark', 'label' => 'Description', 'value' => $dt->silhouette ?? '-', 'type' => 'description'],
                ],
                [
                    ['icon' => 'fas fa-hashtag', 'iconBg' => 'bg-primary-subtle', 'iconColor' => 'text-primary', 'label' => 'PO Number', 'value' => $dt->POno ?? '-'],
                ],
            ];
        @endphp

        <x-details.info-card :sections="$infoSections" />

        <div id="breakdownSummaryWrapper">
            @include('menu.stok-sisa.partials.breakdown_summary')
        </div>

        {{-- DETAIL DATA GRADE SISA (EasyUI-Bootstrap DataGrid) -- KOLOM
             LINE DIHAPUS (bjgrade.linepk tidak dipakai). --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="fw-bold text-dark d-flex align-items-center" style="font-size: 14px;">
                    <span class="rounded me-2" style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
                    Detail Data Grade Sisa
                </div>
            </div>
            <div class="card-body p-0">
                <x-table-bootstrap
                    id="dgTransfer"
                    url="{{ route('stok-sisa.detail.list', $dt->popk) }}"
                    :page-size="50"
                    sort-dropdown
                    sort-name="sort"
                    sort-default="desc"
                    sort-asc-label="Awal tanggal"
                    sort-desc-label="Akhir tanggal"
                    on-load-success="onLoadPolibagTable"
                >
                    <x-slot name="filters">
                        <div class="p-0">
                            <button class="btn btn-dark btn-sm d-inline-flex align-items-center px-2.5 py-1.5 fw-semibold ms-auto"
                                style="font-size: 12px; border-radius: 6px; background-color: #1e293b; border-color: #1e293b;"
                                onclick="openTransferModal()">
                                <i class="fas fa-plus me-1.5 small"></i>
                                Add Grade Sisa
                            </button>
                        </div>
                    </x-slot>

                    {{-- GANTI -- HAPUS kolom Line, "Aksi/Tanggal/Total Pcs/Grade"
                         rowspan=2, "Size" jadi header grup. --}}
                    <tr>
                        <th data-field="" rowspan="2" style="width:70px;" data-align="center" data-formatter="formatAction">Aksi</th>
                        <th data-field="tanggal" rowspan="2" style="width:130px;" data-align="left" data-formatter="formatDate">Tanggal</th>
                        <th data-field="grade" rowspan="2" style="width:100px;" data-align="center" data-formatter="formatGradeBadge">Grade</th>
                        <th colspan="{{ count($activeSizes) }}" class="text-center">
                            Size
                            @if (!empty($dt->secsz))
                                <span class="text-muted fw-bold text-lowercase">({{ $dt->secsz }})</span>
                            @endif
                        </th>
                        <th data-field="pcs" rowspan="2" style="width:100px;" data-align="right" data-formatter="formatTotal">Total Pcs</th>
                    </tr>
                    <tr>
                        @foreach ($activeSizes as $i => $size)
                            <th data-field="qty{{ $i }}" style="min-width:85px;" data-align="center" data-formatter="formatQty">{{ $size }}</th>
                        @endforeach
                    </tr>
                </x-table-bootstrap>
            </div>
        </div>

    </div>
    @include('menu.stok-sisa.modal-transfer')
    @include('menu.stok-sisa.modal-delete-transfer')
@endsection

@section('js_custom')
    <script>
        const currentPopk = {{ $dt->popk }};
        const activeSizesGrade = @json($activeSizes);
        const sisaQtyGrade = @json($sisaQty);

        function formatDate(value) {
            if (!value) return '<span class="dg-empty-cell">-</span>';
            const [y, m, d] = String(value).split(' ')[0].split('-');
            return `${d}/${m}/${y}`;
        }

        function formatGradeBadge(value) {
            if (!value) return '<span class="dg-empty-cell">-</span>';
            return `<span class="badge-grade">${value}</span>`;
        }

        function formatQty(value) {
            return (value === null || value === undefined || value === '') ? '<span class="dg-empty-cell">-</span>' : value;
        }

        function formatTotal(value) {
            return `<div style="text-align:right;">${value ?? 0}</div>`;
        }

        // GANTI TOTAL formatAction() -- SEBELUMNYA logic hasGrade/
        // gradeAllowsAction/isStokSisaMode/dateEditable yang rumit --
        // MENJADI cukup 1 pengecekan: row.status === 0 (belum dikirim ke
        // LO) = boleh Edit/Hapus, selain itu disabled (read-only).
        function formatAction(value, row, index) {
            const isEditable = Number(row.status) === 0;

            const editBtn = isEditable
                ? `<button type="button" class="dg-action-btn dg-edit" title="Ubah" onclick="editRowByIndex(${index})"><i class="fas fa-pen-to-square"></i></button>`
                : `<button type="button" class="dg-action-btn dg-edit" style="opacity:.35;cursor:not-allowed;" title="Sudah dikirim ke LO, tidak bisa diubah" disabled><i class="fas fa-pen-to-square"></i></button>`;

            const deleteBtn = isEditable
                ? `<button type="button" class="dg-action-btn dg-delete" title="Hapus" onclick="openDeleteModal(${row.bjpk})"><i class="fas fa-trash-alt"></i></button>`
                : `<button type="button" class="dg-action-btn dg-delete" style="opacity:.35;cursor:not-allowed;" title="Sudah dikirim ke LO, tidak bisa dihapus" disabled><i class="fas fa-trash-alt"></i></button>`;

            return `<div class="d-flex justify-content-center gap-1">${editBtn}${deleteBtn}</div>`;
        }

        function onLoadPolibagTable(data) {
            // tidak ada lagi has_shipped khusus -- eligibility murni dari status
            // per baris (lihat formatAction()).
        }

        function editRowByIndex(index) {
            const row = window.BsTable.getRow('dgTransfer', index);
            if (row) fillEditForm(row);
        }

        $(function () { reloadBreakdownSummary(); });

        function reloadTransferGrid() { window.BsTable.reload('dgTransfer'); }

        function reloadBreakdownSummary() {
            $.get("{{ route('stok-sisa.breakdown-summary', $dt->popk) }}", { mif: '{{ $mif }}' }, function (html) {
                $('#breakdownSummaryWrapper').html(html);
            });
        }

        // ============================================================
        // DELETE GRADE SISA VIA AJAX
        // ============================================================
        let deleteId = null;

        function openDeleteModal(id) {
            deleteId = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        function confirmDeleteTransfer() {
            if (!deleteId) return;

            $.ajax({
                url: "{{ route('stok-sisa.delete', '') }}/" + deleteId,
                method: 'DELETE',
                beforeSend: function () { $('#btnConfirmDeleteTransfer').prop('disabled', true); },
                success: function (res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal'))?.hide();
                    reloadTransferGrid();
                    reloadBreakdownSummary();
                    deleteId = null;
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    showToast(res.icon, res.title);
                },
                complete: function () { $('#btnConfirmDeleteTransfer').prop('disabled', false); }
            });
        }

        // ============================================================
        // HELPER FORM (isi form Edit, buka modal Add)
        // ============================================================
        function fillEditForm(row) {
            hideTransferAlert();
            $('#mainTransferForm .is-invalid').removeClass('is-invalid');
            $('#mainTransferForm .invalid-feedback, #mainTransferForm td .text-danger').text('');

            $('#transferModalTitle').text('Edit Grade Sisa');
            $('#bjpk').val(row.bjpk);
            $('#mainTransferForm select[name="grade"]').val(row.grade ?? '');

            const tglOnly = String(row.tanggal).split(' ')[0];
            $('[name=tanggal]').val(tglOnly);

            @foreach ($activeSizes as $i => $size)
                $('[name=qty{{ $i }}]').val(row.qty{{ $i }});
            @endforeach

            new bootstrap.Modal(document.getElementById('transferModal')).show();
        }

        function openTransferModal() {
            hideTransferAlert();
            $('#transferModalTitle').text('Add Grade Sisa');
            $('#bjpk').val('');
            const form = document.getElementById('mainTransferForm');
            if (form) form.reset();
            $('#mainTransferForm input[name="tanggal"]').val('');
            $('#mainTransferForm select[name="grade"]').val('');
            $('#mainTransferForm input[type="number"]').val('');
            $('#mainTransferForm .is-invalid').removeClass('is-invalid');
            $('#mainTransferForm .invalid-feedback, #mainTransferForm td .text-danger').text('');

            $('#mainTransferForm input[name="tanggal"]').val(new Date().toISOString().slice(0, 10));

            bootstrap.Modal.getOrCreateInstance(document.getElementById('transferModal')).show();
        }

        function goBack() {
            const refPath = '/stok-sisa/input';
            const indexRoute = "{{ route('stok-sisa.index') }}";
            if (document.referrer && document.referrer.indexOf(refPath) !== -1) {
                window.history.back();
            } else {
                window.location.href = indexRoute;
            }
        }
    </script>
@endsection