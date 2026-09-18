@extends('layout.main')
@section('css_custom')
    <style>
        /* Lebar modal rincian: 90% viewport, maks 1200px */
        #poPopup .modal-dialog.modal-xl {
            max-width: min(1200px, 90vw);
        }

        .po-popup-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .po-popup-table th,
        .po-popup-table td {
            border-bottom: 1px solid #f3f4f6;
            padding: 10px 12px;
            text-align: center;
            vertical-align: middle;
        }

        .po-popup-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
        }

        .po-popup-table td.cell-left {
            text-align: left;
        }

        .po-popup-table .sub {
            font-size: 11px;
            color: #888;
        }

        .cell-stack {
            text-align: left;
            line-height: 1.35;
        }

        .cell-stack .cs-main {
            font-weight: 600;
            font-size: 13px;
            color: #0f172a;
        }

        .cell-stack .cs-sub {
            font-size: 11px;
            color: #64748b;
        }

        .cell-stack .cs-meta {
            font-size: 10.5px;
            color: #94a3b8;
        }

        /* GANTI/TAMBAH di section css_custom, setelah style yang sudah ada */

        .segmented-tabs {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            background: #f1f5f9;
            border-radius: 12px;
            padding: 4px;
            border: none;
            margin-bottom: 0 !important;
        }

        .segmented-tabs .nav-item {
            margin: 0;
        }

        .segmented-tabs .nav-link {
            border: none !important;
            border-radius: 9px !important;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            background: transparent;
            transition: all .15s ease;
            white-space: nowrap;
        }

        .segmented-tabs .nav-link:hover {
            color: #334155;
        }

        .segmented-tabs .nav-link.active {
            background: #fff !important;
            color: #0f172a !important;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .12);
        }

        .segmented-tabs .nav-link .badge {
            font-size: 9.5px;
            vertical-align: 1px;
        }

        /* Wrapper baris tab -- rata kanan seperti contoh, kasih jarak bawah */
        .segmented-tabs-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 16px;
        }

        .packing-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            height: 100%;
            transition: box-shadow .15s ease, border-color .15s ease;
        }

        .packing-card:hover {
            box-shadow: 0 4px 12px rgba(15, 23, 42, .08);
            border-color: #cbd5e1;
        }

        .ctn-code {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }

        .badge-soft {
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 999px;
            border: 1px solid transparent;
            display: inline-flex;
            align-items: center;
        }

        .badge-status {
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 999px;
            display: inline-block;
        }

        .badge-status.inspect {
            background: #fef3c7;
            color: #b45309;
        }

        .subline {
            font-size: 12px;
            color: #64748b;
        }

        .card-barcode {
            font-size: 12px;
            color: #334155;
            background: #f8fafc;
            border-radius: 8px;
            padding: 6px 10px;
            margin-top: 8px;
        }

        .card-barcode .barcode-text i {
            color: #64748b;
        }

        .card-actions {
            margin-top: 10px;
        }

        .dg-daterange-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #374151;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
            user-select: none;
        }

        .dg-daterange-btn:hover {
            background: #f9fafb;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .easyui-dg-wrap .action-btn.action-btn-edit {
            background: #e0f2fe;
            color: #0369a1;
        }

        .easyui-dg-wrap .action-btn.action-btn-edit:hover {
            background: #bae6fd;
            color: #0c4a6e;
        }

        .easyui-dg-wrap .action-btn.action-btn-pdf {
            background: #f8d7da;
            color: #dc3545;
        }

        .easyui-dg-wrap .action-btn.action-btn-pdf:hover {
            background: #ecb9bd;
            color: #ca1124;
        }

        .easyui-dg-wrap .action-btn.action-btn-end {
            background: #dcfce7;
            color: #166534;
        }

        .easyui-dg-wrap .action-btn.action-btn-end:hover {
            background: #bbf7d0;
            color: #14532d;
        }
    </style>

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection
@section('content')
    <div class="page-wrap">

        {{--  4 tab, SAMA pola dengan index FG/Stuffing --}}
        <div class="segmented-tabs-row">
            <ul class="nav segmented-tabs" id="inspectionIndexTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tabBtnDaftarPo" data-bs-toggle="tab"
                        data-bs-target="#tabPaneDaftarPo" type="button" role="tab">
                        <i class="fas fa-list me-1"></i> Daftar PO Inspection
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tabBtnCartonInspec" data-bs-toggle="tab"
                        data-bs-target="#tabPaneCartonInspec" type="button" role="tab"
                        onclick="loadCartonInspecTabIfNeeded()">
                        <i class="fas fa-boxes-stacked me-1"></i> Carton Inspec
                        <span id="cartonInspecTabCount" class="badge bg-secondary ms-1 d-none">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tabBtnDokumenInspect" data-bs-toggle="tab"
                        data-bs-target="#tabPaneDokumenInspect" type="button" role="tab"
                        onclick="loadDokumenInspectTabIfNeeded()">
                        <i class="fas fa-clipboard-check me-1"></i> Dokumen Inspect
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="inspectionIndexTabContent">
            {{-- TAB 1 -- ISI LAMA (persis sama, tidak berubah). --}}
            <div class="tab-pane fade show active" id="tabPaneDaftarPo" role="tabpanel">
                <x-table-default id="dgInspection" title="Daftar PO Inspection" search search-name="search"
                    search-placeholder="Search..." buyer buyer-name="buyer" buyer-url="{{ route('api.buyer-list') }}"
                    buyer-value-field="buyer" buyer-text-field="buyer_name" buyer-mode="remote" year year-name="year"
                    exfactory exfactory-name="ex_factory" sort-dropdown sort-asc-label="Awal Ex-Factory"
                    sort-desc-label="Akhir Ex-Factory">
                    <table id="dgInspection" class="easyui-datagrid" style="width:100%;height:500px"
                        url="{{ route('inspection.list') }}" method="get" pagination="true" pageSize="50"
                        pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true" checkOnSelect="true"
                        selectOnCheck="true" fitColumns="false" border="false">

                        <thead frozen="true">
                            <tr>
                                <th field="action" width="90" formatter="formatAction" align="center">Aksi</th>
                                <th field="OP" width="230" formatter="formatOrderInfo">Order Information</th>
                                <th field="POno" width="220" formatter="formatPOno">PO No</th>
                                <th field="GAC" width="100" align="center" formatter="formatExFactory">Ex Factory</th>
                            </tr>
                        </thead>

                        <thead>
                            <tr>
                                <th field="pcs_inspect" width="150" formatter="pcsctn" align="center">Cartons <br> in
                                    inspection</th>
                                <th field="inspect_status" width="150" formatter="formatInspectStatus" align="center">
                                    Inspection <br> Status</th>
                                <th field="material" width="150">Color</th>
                                <th field="secsz" width="150">Secondary<br>Size</th>
                            </tr>
                        </thead>
                    </table>
                </x-table-default>
            </div>

            {{-- TAB 2 --  Carton Inspec (global, grid kartu ala packing list). --}}
            <div class="tab-pane fade" id="tabPaneCartonInspec" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div
                        class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center">
                            <strong class="text-dark">Carton Sedang Inspect</strong>
                        </div>

                        <div class="input-group" style="width:280px;">
                            <span class="input-group-text search">
                                <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18"
                                    alt="Search">
                            </span>
                            <input type="text" class="form-control search" id="searchCartonInspec"
                                placeholder="Cari carton / barcode / PO / OP / buyer...">
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="cartonInspecLoading" class="text-center text-muted py-5">Memuat...</div>
                        <div id="cartonInspecEmpty" class="text-center text-muted py-5 d-none">
                            <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="160">
                            <div class="fw-semibold mt-2">Tidak ada carton sedang Inspect</div>
                        </div>
                        <div class="row g-3" id="cartonInspecGrid"></div>
                    </div>
                </div>
            </div>

            {{-- TAB 3 -- Dokumen Inspect (global). --}}
            <div class="tab-pane fade" id="tabPaneDokumenInspect" role="tabpanel">
                <x-table-default id="dgInspectDocuments" title="Daftar Dokumen Inspect" search search-name="search"
                    search-placeholder="Cari carton / PO / OP..." buyer buyer-name="buyer"
                    buyer-url="{{ route('api.buyer-list') }}" buyer-value-field="buyer" buyer-text-field="buyer_name"
                    buyer-mode="remote" :sort="false" :year="false">

                    <x-slot name="filters">
                        <div id="dgInspectDocuments_reportrange" class="dg-daterange-btn d-flex align-items-center gap-2">
                            <i class="fas fa-calendar"></i>
                            <span id="dgInspectDocuments_reportrange_label">Semua Tanggal Inspect</span>
                            <i class="fas fa-caret-down ms-auto"></i>
                        </div>

                        {{-- Input tersembunyi -- INI yang dibaca EasyuiDG (data-dg-filter),
                            nilainya di-set lewat callback daterangepicker di JS. --}}
                        <input type="hidden" id="dgInspectDocuments_tgl_from" data-dg-filter="tgl_from"
                            data-dg-chip-label="Dari Tanggal">
                        <input type="hidden" id="dgInspectDocuments_tgl_to" data-dg-filter="tgl_to"
                            data-dg-chip-label="Sampai Tanggal">

                        <div style="margin-left:auto;">
                            <button class="btn btn-dark btn-sm d-flex align-items-center fw-semibold"
                                style="font-size:12px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                                onclick="openGlobalInspectDocumentModal()">
                                <i class="fas fa-clipboard-check me-1"></i> Buat Dokumen Inspect
                            </button>
                        </div>
                    </x-slot>

                    <table id="dgInspectDocuments" class="easyui-datagrid" style="width:100%;height:500px"
                        url="{{ route('inspection.globalDocumentsList') }}" method="get" pagination="true"
                        pageSize="50" pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="false"
                        fitColumns="false" border="false">
                        <thead>
                            <tr>
                                <th field="action" width="150" formatter="formatDocAction" align="center">Aksi</th>
                                <th field="OP" width="230" formatter="formatDocOrderInfo">Order Information</th>
                                <th field="POno" width="180" formatter="formatDocPoOp">PO No</th>
                                <th field="no_inspec" width="220" formatter="formatDocNoAndHasil">No. Dokumen / Hasil
                                </th>
                                <th field="tgl" width="120" formatter="formatDocTanggal" align="center">Tanggal
                                    Inspect</th>
                                <th field="cartons" width="220" formatter="formatDocCartons">Carton Diinspect</th>
                            </tr>
                        </thead>
                    </table>
                </x-table-default>
            </div>

        </div>
    </div>

    {{-- modal poPopup lama TIDAK berubah --}}
    <div class="modal fade" id="poPopup" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="poPopupTitle">Rincian PO</h5>
                    <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal"
                        aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="poPopupBody" style="min-height:160px;">
                        <div style="text-align:center;color:#94a3b8;padding:40px 0;">Memuat...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('menu.inspection.modal-inspection-global')
    @include('menu.inspection.modal-kembalikan-stuffing-global')
    @include('menu.inspection.modal-end-inspect-document')
@endsection


@section('js_custom')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        var popupUrl = "{{ route('finGoods.popup') }}";
        const isSuperUser = @json(session('guserpk') === 34);
        const localNoImg = "{{ asset('public/css/images/no-img.png') }}";

        $(function() {
            $(function() {
                $('#dgInspectDocuments_reportrange').daterangepicker({
                    autoUpdateInput: false,
                    locale: {
                        format: 'YYYY-MM-DD',
                        applyLabel: 'Terapkan',
                        cancelLabel: 'Hapus',
                        customRangeLabel: 'Custom',
                    },
                    ranges: {
                        'Hari Ini': [moment(), moment()],
                        'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
                        '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
                        'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
                        'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment()
                            .subtract(1, 'month').endOf('month')
                        ],
                    }
                });

                $('#dgInspectDocuments_reportrange').on('apply.daterangepicker', function(ev, picker) {
                    $('#dgInspectDocuments_reportrange_label').text(
                        picker.startDate.format('D MMM YYYY') + ' - ' + picker.endDate.format(
                            'D MMM YYYY')
                    );
                    $('#dgInspectDocuments_tgl_from').val(picker.startDate.format('YYYY-MM-DD'))
                        .trigger('change');
                    $('#dgInspectDocuments_tgl_to').val(picker.endDate.format('YYYY-MM-DD'))
                        .trigger('change');
                });

                $('#dgInspectDocuments_reportrange').on('cancel.daterangepicker', function() {
                    $('#dgInspectDocuments_tgl_from').val('').trigger('change');
                    $('#dgInspectDocuments_tgl_to').val('').trigger('change');
                });

                loadCartonInspecList();
            });

            // label SEKARANG disinkronkan LANGSUNG dari nilai
            // input tersembunyi itu sendiri (source of truth), bukan menebak-nebak
            // dari klik tombol lain. Berfungsi utk SEMUA jalur reset -- Cancel di
            // picker, klik chip "x", ATAU klik "Clear All" -- karena SEMUANYA pada
            // akhirnya melewati resetFilterElement() yang sudah di-fix di atas utk
            // men-trigger 'change'.
            $('#dgInspectDocuments_tgl_from, #dgInspectDocuments_tgl_to').on('change', function() {
                var from = $('#dgInspectDocuments_tgl_from').val();
                var to = $('#dgInspectDocuments_tgl_to').val();
                if (!from && !to) {
                    $('#dgInspectDocuments_reportrange_label').text('Semua Tanggal Inspect');
                }
            });

            //  saat user klik "Terapkan": update label yang terlihat, isi
            // 2 input tersembunyi, lalu trigger 'change' -- INI yang bikin
            // EasyuiDG's mekanisme filter generik (yang sudah ada) otomatis
            // menangkap perubahan ini dan reload grid.
            $('#dgInspectDocuments_reportrange').on('apply.daterangepicker', function(ev, picker) {
                $('#dgInspectDocuments_reportrange_label').text(
                    picker.startDate.format('D MMM YYYY') + ' - ' + picker.endDate.format('D MMM YYYY')
                );
                $('#dgInspectDocuments_tgl_from').val(picker.startDate.format('YYYY-MM-DD')).trigger(
                    'change');
                $('#dgInspectDocuments_tgl_to').val(picker.endDate.format('YYYY-MM-DD')).trigger('change');
            });

            //  klik "Hapus" (dipetakan ke tombol Cancel bawaan plugin) --
            // kosongkan kembali.
            $('#dgInspectDocuments_reportrange').on('cancel.daterangepicker', function() {
                resetDgInspectDocumentsDateRange();
            });
        });

        //  helper reset label + input tersembunyi, dipanggil dari cancel
        // picker MAUPUN dari klik chip "x"/"Clear All" (supaya label visual ikut
        // sinkron, karena resetFilterElement() bawaan EasyuiDG cuma set .val()
        // tanpa update tampilan picker-nya).
        function resetDgInspectDocumentsDateRange() {
            $('#dgInspectDocuments_reportrange_label').text('Semua Tanggal Inspect');
            $('#dgInspectDocuments_tgl_from').val('').trigger('change');
            $('#dgInspectDocuments_tgl_to').val('').trigger('change');
        }

        function pcsctn(value, row, index) {
            var field = this.field;
            var ctnMap = {
                pcs_stuff: 'ctn_stuff',
                pcs_inspect: 'ctn_inspect',
                pcs_ship: 'ctn_ship'
            };
            var ctnValue = row[ctnMap[field]] || 0;
            var pcs = parseInt(value) || 0;
            var ctn = parseInt(ctnValue) || 0;

            return '<div style="font-size:14px;">' + ctn.toLocaleString() + ' ctn</div>' +
                '<div style="font-size:11px;color:#888;">' + pcs.toLocaleString() + ' pcs</div>';
        }

        // Kolom Pinjam / Kembali: tampilkan "-" jika kosong (belum pernah dipinjam/dikembalikan)
        function formatDate(value, row, index) {
            if (!value) return '-';
            return value;
        }

        function formatInspectStatus(value, row, index) {
            var inspectedCount = parseInt(row.pinjam_count) || 0;
            var returnedCount = parseInt(row.returned_count) || 0;

            if (value === 'complete') {
                return '<span class="badge" style="background:#dcfce7;color:#15803d;font-weight:600;padding:4px 10px;border-radius:10px;">' +
                    'Complete</span>' +
                    '<div style="font-size:11px;color:#888;">' + returnedCount + ' / ' + inspectedCount + ' carton</div>';
            }

            if (value === 'partial') {
                return '<span class="badge" style="background:#fef3c7;color:#b45309;font-weight:600;padding:4px 10px;border-radius:10px;">' +
                    'Belum Complete</span>' +
                    '<div style="font-size:11px;color:#888;">' + returnedCount + ' / ' + inspectedCount + ' carton</div>';
            }

            return '-';
        }

        function formatShipdate(value, row, index) {
            if (!value || value === '0000-00-00' || value === '0000-00-00 00:00:00') return '-';
            return value;
        }

        // 2 tombol aksi per baris (konsisten dengan Finished Goods):
        //   mata (fa-eye)  -> buka modal rincian per popk+part+secsz
        //   edit (fa-edit) -> langsung ke halaman detail GLOBAL (pono/op)
        function formatDocAction(value, row) {
            const isEnded = !!row.enddate;

            const editButtonHtml = isEnded ?
                '' :
                `<a href="javascript:void(0)" class="action-btn action-btn-edit" title="Edit Dokumen" style="margin-left:6px;" onclick="editInspecDocument(${row.inspecpk})">
                    <i class="fas fa-edit"></i>
                </a>`;

            let endButtonHtml = '';
            if (isEnded) {
                endButtonHtml =
                    `<span class="badge" style="font-size:9.5px;margin-left:6px;color:#8bc63f;background:#dcfce7;" title="Dokumen sudah di-End -- carton di dalamnya bebas didokumentasikan ulang">Selesai</span>`;
            } else if (row.all_returned) {
                endButtonHtml = `<a href="javascript:void(0)" class="action-btn action-btn-end" title="Selesaikan Inspect (semua carton sudah kembali)" style="margin-left:6px;" onclick="endInspecDocument(${row.inspecpk})">
                    <i class="fas fa-flag-checkered"></i>
                </a>`;
            }

            return `
                <a href="javascript:void(0)" class="action-btn action-btn-pdf" title="Cetak PDF" onclick="window.open('{{ url('/inspection/inspect-pdf') }}/${row.inspecpk}', '_blank')">
                    <i class="fas fa-print"></i>
                </a>
                ${editButtonHtml}
                ${endButtonHtml}
            `;
        }

        function openInspectionInput(pono, op, poref, mif) {
            let url = "{{ route('inspection.input.global') }}" +
                "?po=" + encodeURIComponent(pono ?? '') +
                "&op=" + encodeURIComponent(op) +
                "&poref=" + encodeURIComponent(poref ?? '');
            if (mif) url += "&mif=" + mif;
            window.location.href = url;
        }

        /* ============ Modal Bootstrap (kompatibel BS4 & BS5) ============ */

        var popupPono = null;
        var popupOp = null;
        var popupPoref = null;

        function bsModal(el, action) {
            if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
                $(el).modal(action);
            } else if (window.bootstrap && window.bootstrap.Modal) {
                var inst = window.bootstrap.Modal.getOrCreateInstance(el);
                action === 'show' ? inst.show() : inst.hide();
            }
        }

        function esc(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function pcsCtnCell(pcs, ctn) {
            pcs = parseInt(pcs) || 0;
            ctn = parseInt(ctn) || 0;
            return '<div>' + pcs.toLocaleString() + ' pcs</div>' +
                '<div class="sub">' + ctn.toLocaleString() + ' ctn</div>';
        }

        function dateCell(v) {
            if (!v || v === '0000-00-00' || v === '0000-00-00 00:00:00') return '-';
            return esc(v);
        }

        // GLOBAL: navigasi ke halaman detail lewat pono/op saja (tidak
        // ada lagi popk/part/gab di URL).
        function openInspection(pono, op) {
            const url = `{{ route('inspection.detail') }}?pono=${encodeURIComponent(pono)}&op=${encodeURIComponent(op)}`;
            window.location.href = url;
        }


        // Ex Factory -- validasi KETAT, SAMA pola dengan index Packing.
        function formatExFactory(value) {
            if (value === null || value === undefined || value === '') return '';

            const datePart = String(value).split(' ')[0].split('T')[0];
            const parts = datePart.split('-');
            if (parts.length !== 3) return '';

            const year = parseInt(parts[0], 10);
            const monthIdx = parseInt(parts[1], 10) - 1;
            const dayNum = parseInt(parts[2], 10);

            if (
                !Number.isFinite(year) || year <= 0 ||
                !Number.isFinite(monthIdx) || monthIdx < 0 || monthIdx > 11 ||
                !Number.isFinite(dayNum) || dayNum <= 0 || dayNum > 31
            ) {
                return '';
            }

            const bulanSingkat = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return `${dayNum} ${bulanSingkat[monthIdx]} ${year}`;
        }

        // PO No + Place digabung 1 cell. Badge mif ikut di sini (bukan
        // di Order Information) -- SAMA seperti versi lama formatPOno()
        // halaman ini yang menampilkan mif.
        function formatPOno(value, row) {
            const mifBadge = isSuperUser ?
                `<span class="badge bg-secondary-subtle text-secondary-emphasis mif-badge">mif ${row.mif}</span>` :
                '';
            return `
                <div class="cell-stack">
                    <div class="cs-main">${value ?? '-'}${mifBadge}</div>
                    <div class="cs-sub">${row.customer ?? '-'}</div>
                </div>
            `;
        }

        function formatAction(value, row, index) {
            return `
                <a href="javascript:void(0)"
                    onclick="openInspectionInput('${row.POno}', '${row.OP}', '${row.poref ?? ''}', ${row.mif ?? 'null'})"
                    class="action-btn"
                    title="Buka Input Inspect">
                    <i class="fas fa-edit"></i>
                </a>
            `;
        }

        // Order Information -- foto, OP, Buyer, Season, Style, Qty
        // digabung 1 cell. SAMA pola dengan index Packing.
        function formatOrderInfo(value, row) {
            const imgUrl = row.order_image || localNoImg;
            const imgHtml = `
                <img src="${imgUrl}" width="60" height="60"
                    style="object-fit:cover;border-radius:6px;flex-shrink:0;"
                    onerror="this.onerror=null;this.src='${localNoImg}';">
            `;

            return `
                <div class="d-flex align-items-start gap-2">
                    ${imgHtml}
                    <div class="cell-stack">
                        <div class="cs-main">${row.OP ?? '-'}</div>
                        <div class="cs-sub">${row.buyer ?? '-'} &middot; ${row.season ?? '-'}</div>
                        <div class="cs-sub">${row.style ?? '-'}</div>
                        <div class="cs-meta">Qty: <strong style="color:#334155;">${Number(row.qty || 0).toLocaleString()}</strong></div>
                    </div>
                </div>
            `;
        }

        // ============================================================
        // TAB 2 -- Carton Inspec (global)
        // ============================================================
        let cartonInspecLoaded = false;

        function loadCartonInspecTabIfNeeded() {
            if (cartonInspecLoaded) return;
            cartonInspecLoaded = true;
            loadCartonInspecList();
        }

        let cartonInspecSearchTimer = null;
        $(document).on('keyup', '#searchCartonInspec', function() {
            clearTimeout(cartonInspecSearchTimer);
            cartonInspecSearchTimer = setTimeout(loadCartonInspecList, 300);
        });

        function loadCartonInspecList() {
            $('#cartonInspecLoading').removeClass('d-none');
            $('#cartonInspecEmpty').addClass('d-none');
            $('#cartonInspecGrid').empty();

            $.get("{{ route('inspection.globalCartonList') }}", {
                search: $('#searchCartonInspec').val(),
                page: 1,
                rows: 200
            }, function(data) {
                cartonInspecLoaded = true;
                $('#cartonInspecLoading').addClass('d-none');

                const $badge = $('#cartonInspecTabCount');
                if (data.total > 0) $badge.text(data.total).removeClass('d-none');
                else $badge.addClass('d-none');

                if (!data.rows || !data.rows.length) {
                    $('#cartonInspecEmpty').removeClass('d-none');
                    return;
                }

                const $grid = $('#cartonInspecGrid');
                data.rows.forEach(function(c) {
                    $grid.append(buildCartonInspecCard(c));
                });
            });
        }

        //  kartu carton ala packing list, dengan badge Mix PO / Bundle.
        function buildCartonInspecCard(c) {
            const poOpList = c.po_op_list || [];
            const poOpLabel = poOpList.map(p => `${p.POno ?? '-'} &middot; ${p.OP ?? '-'}`).join(', ');

            const mixBadge = c.is_mix ?
                `<span class="badge-soft" style="background:#ede9fe;color:#6d28d9;border-color:#ddd6fe;" title="Mix PO: ${poOpLabel}">
                    <i class="fas fa-shuffle me-1"></i>Mix PO
                </span>` :
                '';

            const bundleBadge = c.bundlepk ?
                `<span class="badge-soft" style="background:#fdf3e7;color:#92400e;border-color:#f3dcb8;" title="Bagian dari Carton Besar: ${c.bundle_carton ?? '-'}">
                    <i class="fas fa-box-open me-1"></i>${c.bundle_carton ?? 'Bundle'}
                </span>` :
                '';

            // BARU -- badge Part/Session, supaya carton dgn nomor SAMA tapi
            // Part BEDA tetap bisa dibedakan visualnya.
            const partVal = (c.part === null || c.part === undefined || c.part === '' || Number(c.part) === 0) ? null : c
                .part;
            const partBadge = partVal ?
                `<span class="badge-soft" style="background:#ede9fe;color:#6d28d9;border-color:#ddd6fe;">
                    ${String(partVal) === '10' ? 'Complete' : 'Session ' + partVal}
                </span>` :
                '';

            const docBadgeHtml = c.has_open_doc ?
                `<div class="mb-2">
                    <span class="badge-soft" style="background:${c.inspec_hasil === 1 ? '#dcfce7' : '#fee2e2'};color:${c.inspec_hasil === 1 ? '#166534' : '#991b1b'};border-color:transparent;">
                        <i class="fas fa-file-lines me-1"></i>${c.no_inspec ?? '-'} &middot; ${c.inspec_hasil === 1 ? 'LULUS' : 'REJECT'}
                    </span>
                </div>` :
                `<div class="mb-2">
                    <span class="badge-soft" style="background:#f1f5f9;color:#64748b;border-color:#e2e8f0;">Belum Ada Dokumen Inspect</span>
                </div>`;

            const packpksCsv = (c.packpks || []).join(',');
            const actionHtml = c.has_open_doc ?
                `<button type="button" class="btn btn-outline-dark btn-sm w-100"
                    onclick="kembalikanCartonFromInspecTab('${c.carton}', '${packpksCsv}', '${(c.no_inspec ?? '').replace(/'/g, "\\'")}', ${c.inspec_hasil ?? 'null'})">
                    <i class="fas fa-arrow-rotate-left me-1"></i> Kembalikan
                </button>` :
                '';

            return `
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="packing-card">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                            <span class="ctn-code">${c.carton ?? '-'}</span>
                            <span class="badge-status inspect">Inspect</span>
                            ${partBadge}
                            ${mixBadge}
                            ${bundleBadge}
                        </div>
                        ${docBadgeHtml}
                        <div class="subline mb-1">${c.material ?? '-'} ${c.secsz ? '(' + c.secsz + ')' : ''} &middot; ${c.buyer ?? '-'}</div>
                        <div class="subline mb-1" title="${poOpLabel}">${c.POno ?? '-'} &middot; ${c.OP ?? '-'}</div>
                        <div class="subline" style="font-size:11px;">
                            <i class="fas fa-calendar-day me-1"></i>Masuk Inspect: ${formatDate(c.pinjam)}
                        </div>
                        <div class="card-barcode">
                            <span class="barcode-text"><i class="fas fa-barcode me-1"></i>${c.nobar ?? '<span class="text-muted">Belum ada barcode</span>'}</span>
                        </div>
                        ${actionHtml ? `<div class="card-actions">${actionHtml}</div>` : ''}
                    </div>
                </div>
            `;
        }

        //  buka modal Kembalikan (REUSE modal & desain yang SAMA dengan
        // halaman input per-PO), scoped ke SATU carton (bukan window.selectedPackpksGlobal
        // karena tab ini tidak punya mekanisme seleksi carton).
        function kembalikanCartonFromInspecTab(cartonNo, packpksCsv, noInspec, inspecHasil) {
            const packpks = packpksCsv.split(',').map(Number).filter(Boolean);
            if (!packpks.length) return;

            window.kembalikanCartonPackpksGlobal = packpks;

            $('#kembalikanInfoText').html(`Anda akan mengembalikan carton <strong>${cartonNo}</strong> ke FinishGood.`);

            const $display = $('#kembalikanHasilDisplay');
            $('#kembalikanWarningBoxOk, #kembalikanWarningBoxReject, #kembalikanWarningBoxMissing').addClass('d-none');

            if (inspecHasil === null || inspecHasil === undefined) {
                // Seharusnya tidak terjadi (tombol cuma muncul kalau has_open_doc
                // true), tapi tetap dijaga sebagai fallback.
                $display.attr('class', 'alert alert-secondary py-2 px-3 mb-0 text-center fw-bold').text(
                    'Belum ada Dokumen Inspect');
                $('#kembalikanWarningBoxMissing').removeClass('d-none');
                $('#btnConfirmKembalikanStuffing').prop('disabled', true);
            } else if (Number(inspecHasil) === 0) {
                $display.attr('class', 'alert alert-danger py-2 px-3 mb-0 text-center fw-bold')
                    .html(`<i class="fas fa-times-circle me-1"></i>REJECT &middot; Dokumen ${noInspec || '-'}`);
                $('#kembalikanWarningBoxReject').removeClass('d-none');
                $('#btnConfirmKembalikanStuffing').prop('disabled', false);
            } else {
                $display.attr('class', 'alert alert-success py-2 px-3 mb-0 text-center fw-bold')
                    .html(`<i class="fas fa-check-circle me-1"></i>LULUS &middot; Dokumen ${noInspec || '-'}`);
                $('#kembalikanWarningBoxOk').removeClass('d-none');
                $('#btnConfirmKembalikanStuffing').prop('disabled', false);
            }

            // GANTI target klik tombol Confirm -- pakai versi submit khusus tab
            // ini (baca window.kembalikanCartonPackpksGlobal), BUKAN
            // submitKembalikanStuffing() bawaan (yang baca window.selectedPackpksGlobal
            // milik halaman input per-PO).
            $('#btnConfirmKembalikanStuffing').off('click').on('click', submitKembalikanCartonInspecTab);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('kembalikanStuffingModal')).show();
        }

        //  proses SAMA PERSIS dengan submitKembalikanStuffing() yang
        // sudah ada (action: request_return via bulkShipAction), cuma sumber
        // packpk-nya dari window.kembalikanCartonPackpksGlobal.
        function submitKembalikanCartonInspecTab() {
            const packpks = window.kembalikanCartonPackpksGlobal || [];
            if (!packpks.length) return;

            $('#btnConfirmKembalikanStuffing').prop('disabled', true);
            $.ajax({
                url: "{{ route('inspection.bulk-ship-action') }}",
                method: 'POST',
                data: {
                    packpk: packpks.join(','),
                    action: 'request_return',
                },
                success: function(res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('kembalikanStuffingModal')).hide();
                    refreshInspectionTabs
                (); // BARU -- Kembalikan bisa bikin all_returned jadi true, Dokumen Inspect perlu ikut update
                },
                error: function(xhr) {
                    const res = xhr.responseJSON || {
                        icon: 'error',
                        title: 'Gagal mengembalikan carton.'
                    };
                    showToast(res.icon, res.title);
                },
                complete: function() {
                    $('#btnConfirmKembalikanStuffing').prop('disabled', false);
                }
            });
        }

        // ============================================================
        // TAB 3 -- Dokumen Inspect (global)
        // ============================================================
        let dokumenInspectLoaded = false;

        function loadDokumenInspectTabIfNeeded() {
            if (dokumenInspectLoaded) return;
            dokumenInspectLoaded = true;
            loadDokumenInspectGlobalList();
        }

        let dokumenInspectSearchTimer = null;
        $(document).on('keyup', '#searchDokumenInspect', function() {
            clearTimeout(dokumenInspectSearchTimer);
            dokumenInspectSearchTimer = setTimeout(loadDokumenInspectGlobalList, 300);
        });

        function loadDokumenInspectGlobalList() {
            $('#dokumenInspectLoading').removeClass('d-none');
            $('#dokumenInspectEmpty').addClass('d-none');
            $('#dokumenInspectGrid').empty();

            $.get("{{ route('inspection.globalDocumentsList') }}", {
                search: $('#searchDokumenInspect').val(),
                page: 1,
                rows: 100
            }, function(data) {
                dokumenInspectLoaded = true;
                $('#dokumenInspectLoading').addClass('d-none');

                if (!data.rows || !data.rows.length) {
                    $('#dokumenInspectEmpty').removeClass('d-none');
                    return;
                }

                const $grid = $('#dokumenInspectGrid');
                data.rows.forEach(function(doc) {
                    const isLulus = doc.hasil === 1;
                    const badgeCls = isLulus ? 'background:#dcfce7;color:#166534;' :
                        'background:#fee2e2;color:#991b1b;';
                    const badgeLabel = isLulus ? 'LULUS' : 'REJECT';
                    const tglLabel = doc.tgl ? doc.tgl.split(' ')[0].split('-').reverse().join('/') : '-';

                    $grid.append(`
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="packing-card" style="height:auto; cursor:default;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="ctn-code">${doc.no_inspec ?? ('Dokumen #' + doc.inspecpk)}</span>
                                    <span class="badge-soft" style="${badgeCls}">${badgeLabel}</span>
                                </div>
                                <div class="subline mb-2">
                                    <i class="fas fa-file-lines me-1"></i>${doc.POno ?? '-'} &middot; ${doc.OP ?? '-'}
                                </div>
                                <div style="font-size:11.5px; color:#475569;">
                                    <i class="fas fa-calendar-day me-1"></i>${tglLabel}
                                    &middot; AQL: <strong>${doc.aql}</strong>
                                    &middot; ${doc.cartons.length} carton, ${doc.totpcs} pcs
                                </div>
                                <div class="card-actions mt-2 d-flex gap-2">
                                    <button class="btn btn-outline-dark btn-sm flex-fill" onclick="window.open('{{ url('/inspection/inspect-pdf') }}/${doc.inspecpk}', '_blank')">
                                        <i class="fas fa-print me-1"></i> Cetak PDF
                                    </button>
                                    <a href="javascript:void(0)" class="btn btn-outline-secondary btn-sm flex-fill"
                                        onclick="openInspectionInput('${doc.POno}', '${doc.OP}', '${doc.poref ?? ''}', ${doc.mif ?? 'null'})">
                                        <i class="fas fa-arrow-up-right-from-square me-1"></i> Buka PO
                                    </a>
                                </div>
                            </div>
                        </div>
                    `);
                });
            });
        }

        function endInspecDocument(inspecpk) {
            window.pendingEndInspecpk = inspecpk;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('endInspecDocumentModal')).show();
        }

        function confirmEndInspecDocument() {
            const inspecpk = window.pendingEndInspecpk;
            if (!inspecpk) return;

            $('#btnConfirmEndInspec').prop('disabled', true);
            $.post(`{{ url('/inspection/inspect-end') }}/${inspecpk}`, {}, function(res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('endInspecDocumentModal')).hide();
                refreshInspectionTabs(); // BARU -- End membebaskan carton, Carton Inspec perlu ikut update
            }).fail(function(xhr) {
                const res = xhr.responseJSON || {
                    icon: 'error',
                    title: 'Gagal mengakhiri dokumen.'
                };
                showToast(res.icon, res.title);
            }).always(function() {
                $('#btnConfirmEndInspec').prop('disabled', false);
                window.pendingEndInspecpk = null;
            });
        }

        function formatDocOrderInfo(value, row) {
            const imgUrl = row.order_image || localNoImg;
            const imgHtml = `
                <img src="${imgUrl}" width="60" height="60"
                    style="object-fit:cover;border-radius:6px;flex-shrink:0;"
                    onerror="this.onerror=null;this.src='${localNoImg}';">
            `;

            return `
                <div class="d-flex align-items-start gap-2">
                    ${imgHtml}
                    <div class="cell-stack">
                        <div class="cs-main">${row.OP ?? '-'}</div>
                        <div class="cs-sub">${row.buyer ?? '-'} &middot; ${row.season ?? '-'}</div>
                        <div class="cs-sub">${row.style ?? '-'}</div>
                        <div class="cs-meta">Qty: <strong style="color:#334155;">${Number(row.qty || 0).toLocaleString()}</strong></div>
                    </div>
                </div>
            `;
        }

        // GANTI formatDocPoOp() -- SEBELUMNYA tampilkan PO+OP -- SEKARANG cukup
        // PO No saja (OP sudah ada di kolom Order Information).
        function formatDocPoOp(value, row) {
            return `
                <div class="cell-stack">
                    <div class="cs-main">${row.POno ?? '-'}</div>
                    <div class="cs-sub">${row.customer ?? '-'}</div>
                </div>
            `;
        }

        function formatDocNoAndHasil(value, row) {
            const isLulus = Number(row.hasil) === 1;
            const bg = isLulus ? '#dcfce7' : '#fee2e2';
            const color = isLulus ? '#8bc63f' : '#991b1b';
            const label = isLulus ? 'LULUS' : 'REJECT';

            return `
                <div class="cell-stack">
                    <div class="cs-main">${row.no_inspec ?? ('Dokumen #' + row.inspecpk)}</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="badge" style="background:${bg};color:${color};font-weight:600;padding:3px 9px;border-radius:10px;font-size:10.5px;">${label}</span>
                        <span class="cs-sub">AQL ${row.aql}</span>
                        <span class="cs-sub">&middot; ${row.totpcs} pcs</span>
                    </div>
                </div>
            `;
        }

        function formatDocTanggal(value) {
            if (!value) return '-';
            const datePart = String(value).split(' ')[0].split('T')[0];
            const parts = datePart.split('-');
            if (parts.length !== 3) return value;
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }

        function formatDocHasil(value) {
            const isLulus = Number(value) === 1;
            const bg = isLulus ? '#dcfce7' : '#fee2e2';
            const color = isLulus ? '#166534' : '#991b1b';
            const label = isLulus ? 'LULUS' : 'REJECT';
            return `<span class="badge" style="background:${bg};color:${color};font-weight:600;padding:4px 10px;border-radius:10px;">${label}</span>`;
        }

        function formatDocCartons(value, row) {
            const cartons = row.cartons || [];
            if (!cartons.length) return '-';
            const shown = cartons.slice(0, 3).join(', ');
            const extra = cartons.length > 3 ? ` +${cartons.length - 3} lainnya` : '';
            return `<span style="font-size:12px;">${shown}${extra}</span>`;
        }


        // ============================================================
        // Modal Buat/Edit Dokumen Inspect (GLOBAL) -- tidak perlu pilih PO/OP,
        // tampilkan SEMUA carton fca=1 lintas PO/OP, tiap carton ditandai asal
        // PO/OP-nya (+ Mix PO/Bundle badge kalau relevan).
        // ============================================================

        function safeIdPart(str) {
            return String(str).replace(/[^a-zA-Z0-9_-]/g, '_');
        }

        let inspecCart = [];
        let inspecRemainingBySizeKey = {};
        window.editingInspecpk = null;

        function openGlobalInspectDocumentModal() {
            window.editingInspecpk = null;
            inspecCart = [];
            inspecRemainingBySizeKey = {};
            $('#inspecAql').val('');
            $('#inspecKeterangan').val(''); // BARU
            renderInspecCart();
            recomputeHasilDisplay();

            bootstrap.Modal.getOrCreateInstance(document.getElementById('inspectDocumentModal')).show();
            loadGlobalInspectAvailableCartons();
        }

        function loadGlobalInspectAvailableCartons() {
            $.get("{{ route('inspection.globalAvailableCartons') }}", {
                exclude_inspecpk: window.editingInspecpk || ''
            }, function(data) {
                renderInspectCartonList(data.rows || []);
                reconcileRemainingWithExistingCart();
            });
        }

        function reconcileRemainingWithExistingCart() {
            inspecCart.forEach(function(line) {
                const sizeKey = `${line.packpk}|${line.size}`;
                if (!(sizeKey in inspecRemainingBySizeKey)) return;

                inspecRemainingBySizeKey[sizeKey] = Math.max(0, inspecRemainingBySizeKey[sizeKey] - (line.qty ||
                1));

                const sizeIdSafe = safeIdPart(line.packpk + '_' + line.size);
                const $label = $('#inspecRemainingLabel_' + sizeIdSafe);
                if ($label.length) {
                    $label.text('sisa ' + inspecRemainingBySizeKey[sizeKey]);
                    if (inspecRemainingBySizeKey[sizeKey] <= 0) {
                        $('#inspecSizeRow_' + sizeIdSafe + ' button').prop('disabled', true);
                    }
                }
            });
        }

        //  render daftar carton, dikelompokkan per NOMOR CARTON FISIK
        // (SAMA seperti versi per-PO), TAPI SEKARANG tiap carton ditandai
        // PO/OP asalnya (dan Mix PO/Bundle) karena lintas PO/OP sekaligus.
        function pgCartonPartKeyInspec(row) {
            const cartonPart = row.carton ?? '(tanpa carton)';
            const partVal = (row.part === null || row.part === undefined || row.part === '' || Number(row.part) === 0) ?
                '' :
                String(row.part);
            return `${cartonPart}||${partVal}`;
        }

        function renderInspectCartonList(rows) {
            const wrap = $('#inspectCartonList');
            wrap.empty();
            if (!rows.length) {
                $('#inspectCartonEmpty').removeClass('d-none');
                return;
            }
            $('#inspectCartonEmpty').addClass('d-none');

            const cartonGroups = {};
            const cartonOrder = [];
            rows.forEach(function(row) {
                window['inspecPackData_' + row.packpk] = row;
                const key = pgCartonPartKeyInspec(row);
                if (!cartonGroups[key]) {
                    cartonGroups[key] = [];
                    cartonOrder.push(key);
                }
                cartonGroups[key].push(row);

                row.sizes.forEach(function(s) {
                    const sizeKey = `${row.packpk}|${s.label}`;
                    if (!(sizeKey in inspecRemainingBySizeKey)) {
                        inspecRemainingBySizeKey[sizeKey] = s.qty;
                    }
                });
            });

            cartonOrder.forEach(function(groupKey) {
                const packRowsInCarton = cartonGroups[groupKey];
                const repRow = packRowsInCarton[0];
                const cartonLabel = repRow.carton ?? '-';

                const uniqueCombos = new Set(packRowsInCarton.map(r => `${r.material ?? '-'}||${r.secsz ?? ''}`));
                const isMixed = uniqueCombos.size > 1;
                const cartonIdSafe = safeIdPart(groupKey);

                const poOpList = [...new Set(packRowsInCarton.map(r =>
                `${r.POno ?? '-'} &middot; ${r.OP ?? '-'}`))];
                const poOpLabel = poOpList.join(', ');

                const anyMix = packRowsInCarton.some(r => r.is_mix);
                const anyBundle = packRowsInCarton.some(r => r.bundlepk);
                const bundleName = packRowsInCarton.find(r => r.bundle_carton)?.bundle_carton;

                const isAlreadyDocumented = packRowsInCarton.some(r => r.already_documented);

                const partVal = (repRow.part === null || repRow.part === undefined || repRow.part === '' || Number(
                        repRow.part) === 0) ?
                    null :
                    repRow.part;
                const partInfoHtml = partVal ?
                    `<span class="inspec-badge-soft" style="background:#ede9fe;color:#6d28d9;border-color:#ddd6fe;">
                ${String(partVal) === '10' ? 'Complete' : 'Session ' + partVal}
            </span>` :
                    '';

                const mixInfoHtml = anyMix ?
                    `<span class="inspec-badge-soft" style="background:#ede9fe;color:#6d28d9;border-color:#ddd6fe;" title="Mix PO: ${poOpLabel}">
                <i class="fas fa-shuffle" style="font-size:9px;"></i> Mix PO
            </span>` :
                    '';
                const bundleInfoHtml = anyBundle ?
                    `<span class="inspec-badge-soft" style="background:#fdf3e7;color:#92400e;border-color:#f3dcb8;" title="Bagian dari Carton Besar: ${bundleName ?? '-'}">
                <i class="fas fa-box-open" style="font-size:9px;"></i> ${bundleName ?? 'Bundle'}
            </span>` :
                    '';
                const documentedInfoHtml = isAlreadyDocumented ?
                    `<span class="inspec-badge-soft" style="background:#fee2e2;color:#991b1b;border-color:#fecaca;" title="Carton ini sudah dimasukkan ke dokumen Inspect lain -- tidak bisa disample ulang di sini">
                <i class="fas fa-ban" style="font-size:9px;"></i> Sudah Terdokumentasi
            </span>` :
                    '';

                let sizePillsHtml = '';
                packRowsInCarton.forEach(function(row) {
                    const materialLabel = row.material ?? '-';
                    const secszTag = row.secsz ? ` (${row.secsz})` : '';
                    row.sizes.forEach(function(s) {
                        const sizeKey = `${row.packpk}|${s.label}`;
                        const remaining = inspecRemainingBySizeKey[sizeKey];
                        const isEmpty = remaining <= 0 || isAlreadyDocumented;
                        const sizeIdSafe = safeIdPart(row.packpk + '_' + s.label);

                        sizePillsHtml += `
                    <div class="inspec-size-pill ${isEmpty ? 'is-empty' : ''}" id="inspecSizeRow_${sizeIdSafe}">
                        <div class="isp-label">${s.label}</div>
                        <div class="isp-combo">${materialLabel}${secszTag}</div>
                        <div class="isp-remaining" id="inspecRemainingLabel_${sizeIdSafe}">sisa ${remaining}</div>
                        <div>
                            <button type="button" class="isp-add-btn" ${isEmpty ? 'disabled' : ''}
                                onclick="addInspecSampleUnit(${row.packpk}, '${String(s.label).replace(/'/g, "\\'")}')">
                                <i class="fas fa-plus" style="font-size:11px;"></i>
                            </button>
                        </div>
                    </div>
                `;
                    });
                });

                const compositionLabel = isMixed ? 'Mixed' : (packRowsInCarton[0].sizes.length > 1 ? 'Assorted' :
                    'Solid');
                const compositionCls = isMixed ? 'mixed' : (packRowsInCarton[0].sizes.length > 1 ? 'assorted' :
                    'solid');

                wrap.append(`
            <div class="inspec-carton-row ${isAlreadyDocumented ? 'is-documented' : ''}">
                <div class="inspec-carton-header" onclick="toggleInspecCartonSize('${groupKey.replace(/'/g, "\\'")}')">
                    <div class="inspec-carton-icon"><i class="fas fa-box-open"></i></div>
                    <div class="flex-grow-1">
                        <div class="inspec-carton-title">
                            Carton ${cartonLabel}
                            <span class="inspec-badge-soft ${compositionCls}">${compositionLabel}</span>
                            ${partInfoHtml}
                            ${mixInfoHtml}
                            ${bundleInfoHtml}
                            ${documentedInfoHtml}
                        </div>
                        <div class="inspec-carton-sub">
                            <i class="fas fa-barcode me-1"></i>${repRow.nobar ?? 'Belum ada barcode'}
                            &middot; ${poOpLabel}
                        </div>
                    </div>
                    <i class="fas fa-chevron-down text-muted" id="inspecExpandIcon_${cartonIdSafe}"></i>
                </div>
                <div class="inspec-carton-perf"></div>
                <div class="inspec-size-detail" id="inspecSizeDetail_${cartonIdSafe}">
                    <div class="inspec-size-grid">${sizePillsHtml}</div>
                </div>
            </div>
        `);
            });
        }

        function toggleInspecCartonSize(cartonNo) {
            const cartonIdSafe = safeIdPart(cartonNo);
            $('#inspecSizeDetail_' + cartonIdSafe).toggleClass('is-open');
            $('#inspecExpandIcon_' + cartonIdSafe).toggleClass('fa-chevron-down fa-chevron-up');
        }

        let inspecDefectSubCache = {
            subs: [],
            defects: []
        };
        let currentDefectPickerLineIndex = null;
        let currentDefectPickerSelected = [];

        function addInspecSampleUnit(packpk, sizeLabel) {
            const sizeKey = `${packpk}|${sizeLabel}`;
            const remaining = inspecRemainingBySizeKey[sizeKey] ?? 0;
            if (remaining <= 0) {
                showToast('warning', 'Sisa untuk size ini sudah habis, tidak bisa ditambah lagi.');
                return;
            }

            const row = window['inspecPackData_' + packpk];
            inspecCart.push({
                packpk: packpk,
                carton: row.carton,
                POno: row.POno, // BARU
                OP: row.OP, // BARU
                size: sizeLabel,
                color: row.material,
                secsz: row.secsz,
                qty: 1,
                stspass: 1,
                defects: [],
            });

            inspecRemainingBySizeKey[sizeKey] = remaining - 1;
            const sizeIdSafe = safeIdPart(packpk + '_' + sizeLabel);
            $('#inspecRemainingLabel_' + sizeIdSafe).text('sisa ' + inspecRemainingBySizeKey[sizeKey]);
            if (inspecRemainingBySizeKey[sizeKey] <= 0) {
                $('#inspecSizeRow_' + sizeIdSafe + ' button').prop('disabled', true);
            }

            renderInspecCart();
            recomputeHasilDisplay();
        }

        function removeInspecCartLine(index) {
            const line = inspecCart[index];
            if (!line) return;
            inspecCart.splice(index, 1);

            const sizeKey = `${line.packpk}|${line.size}`;
            inspecRemainingBySizeKey[sizeKey] = (inspecRemainingBySizeKey[sizeKey] ?? 0) + 1;
            const sizeIdSafe = safeIdPart(line.packpk + '_' + line.size);
            const $label = $('#inspecRemainingLabel_' + sizeIdSafe);
            if ($label.length) {
                $label.text('sisa ' + inspecRemainingBySizeKey[sizeKey]);
                $('#inspecSizeRow_' + sizeIdSafe + ' button').prop('disabled', false);
            }

            renderInspecCart();
            recomputeHasilDisplay();
        }

        function renderInspecCart() {
            $('#inspecCartCount').text(inspecCart.length);
            $('#inspecTotalPcs').text(inspecCart.length);

            const list = $('#inspecCartList');
            list.empty();
            if (!inspecCart.length) {
                list.html(
                    '<div class="text-muted text-center py-4" style="font-size:12.5px;">Belum ada sample dipilih.</div>'
                );
                return;
            }
            inspecCart.forEach(function(line, index) {
                const isPass = line.stspass === 1;
                const isDefect = line.stspass === 0;
                let defectTagsHtml = '';
                if (isDefect) {
                    defectTagsHtml = line.defects.length ?
                        `<div class="cart-line-defect-tags">${line.defects.map(d => `<span class="tag">${d.defectnm}</span>`).join('')}</div>` :
                        `<div class="cart-line-defect-tags"><span class="tag muted">Klik stempel Defect lagi utk pilih tipe</span></div>`;
                }

                list.append(`
                    <div class="inspec-cart-row">
                        <div class="icr-top">
                            <div>
                                <div class="icr-title">Carton ${line.carton} &middot; ${line.size}</div>
                                <div class="icr-sub">${line.color ?? '-'} ${line.secsz ? '(' + line.secsz + ')' : ''} &middot; 1 pcs</div>
                                <div class="icr-sub" style="color:#94a3b8;"><i class="fas fa-file-lines me-1"></i>${line.POno ?? '-'} &middot; ${line.OP ?? '-'}</div>
                            </div>
                            <i class="fas fa-times ic-remove" onclick="removeInspecCartLine(${index})"></i>
                        </div>
                        <div class="qc-stamp-row">
                            <div class="qc-stamp pass ${isPass ? 'active' : ''}" onclick="setCartLineStatus(${index}, 1)">
                                <i class="fas fa-check"></i><span class="qc-stamp-text">Pass</span>
                            </div>
                            <div class="qc-stamp defect ${isDefect ? 'active' : ''}" onclick="setCartLineStatus(${index}, 0)">
                                <i class="fas fa-xmark"></i><span class="qc-stamp-text">Defect</span>
                            </div>
                        </div>
                        ${defectTagsHtml}
                    </div>
                `);
            });
        }

        function setCartLineStatus(index, stspass) {
            const line = inspecCart[index];
            if (!line) return;
            line.stspass = stspass;
            if (stspass === 1) line.defects = [];
            renderInspecCart();
            recomputeHasilDisplay();
            if (stspass === 0) openDefectPicker(index);
        }

        function loadDefectSubDataIfNeeded(callback) {
            if (inspecDefectSubCache.defects.length) {
                callback();
                return;
            }
            $.get("{{ route('inspection.inspect-defect-sub-list') }}", {}, function(data) {
                inspecDefectSubCache = {
                    subs: data.subs || [],
                    defects: data.defects || []
                };
                callback();
            });
        }

        function openDefectPicker(lineIndex) {
            currentDefectPickerLineIndex = lineIndex;
            currentDefectPickerSelected = (inspecCart[lineIndex].defects || []).map(d => d.defectpk);
            loadDefectSubDataIfNeeded(function() {
                renderDefectSubTabs();
                renderDefectList(inspecDefectSubCache.subs[0]?.subpk ?? null);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('defectPickerModal')).show();
            });
        }

        function renderDefectSubTabs() {
            const wrap = $('#defectSubTabs');
            wrap.empty();
            inspecDefectSubCache.subs.forEach(function(sub, idx) {
                wrap.append(
                    `<div class="defect-sub-tab ${idx === 0 ? 'active' : ''}" data-subpk="${sub.subpk}" onclick="switchDefectSubTab(${sub.subpk})">${sub.subnm}</div>`
                );
            });
        }

        function switchDefectSubTab(subpk) {
            $('#defectSubTabs .defect-sub-tab').removeClass('active');
            $(`#defectSubTabs .defect-sub-tab[data-subpk="${subpk}"]`).addClass('active');
            renderDefectList(subpk);
        }

        function renderDefectList(subpk) {
            const wrap = $('#defectListWrap');
            wrap.empty();
            const filtered = inspecDefectSubCache.defects.filter(d => d.subpk === subpk);
            if (!filtered.length) {
                wrap.html('<div class="text-muted" style="font-size:12px;">Tidak ada defect di kategori ini.</div>');
                return;
            }
            filtered.forEach(function(d) {
                const isSelected = currentDefectPickerSelected.includes(d.defectpk);
                wrap.append(
                    `<div class="defect-chip ${isSelected ? 'selected' : ''}" data-defectpk="${d.defectpk}" onclick="toggleDefectChip(${d.defectpk}, this)">${d.defectnm}</div>`
                );
            });
        }

        function toggleDefectChip(defectpk, el) {
            const idx = currentDefectPickerSelected.indexOf(defectpk);
            if (idx >= 0) {
                currentDefectPickerSelected.splice(idx, 1);
                $(el).removeClass('selected');
            } else {
                currentDefectPickerSelected.push(defectpk);
                $(el).addClass('selected');
            }
        }

        function confirmDefectPicker() {
            if (currentDefectPickerLineIndex === null) return;
            if (!currentDefectPickerSelected.length) {
                showToast('warning', 'Pilih minimal 1 tipe defect.');
                return;
            }
            const line = inspecCart[currentDefectPickerLineIndex];
            line.defects = currentDefectPickerSelected.map(pk => {
                const d = inspecDefectSubCache.defects.find(x => x.defectpk === pk);
                return {
                    defectpk: pk,
                    defectnm: d ? d.defectnm : ('#' + pk)
                };
            });
            bootstrap.Modal.getInstance(document.getElementById('defectPickerModal')).hide();
            renderInspecCart();
            recomputeHasilDisplay();
        }

        function recomputeHasilDisplay() {
            const aql = parseFloat($('#inspecAql').val());
            const defectCount = inspecCart.filter(l => l.stspass === 0).length;
            $('#inspecDefectCount').text(defectCount);
            const $display = $('#inspecHasilDisplay');
            if (isNaN(aql)) {
                $display.attr('class', 'alert alert-secondary py-2 px-3 mb-0 text-center fw-bold').text(
                    'Isi AQL untuk melihat hasil');
                return;
            }
            const isReject = defectCount >= aql;
            if (isReject) {
                $display.attr('class', 'alert alert-danger py-2 px-3 mb-0 text-center fw-bold')
                    .html(`<i class="fas fa-times-circle me-1"></i>REJECT (Defect ${defectCount} &ge; AQL ${aql})`);
            } else {
                $display.attr('class', 'alert alert-success py-2 px-3 mb-0 text-center fw-bold')
                    .html(`<i class="fas fa-check-circle me-1"></i>LULUS (Defect ${defectCount} &lt; AQL ${aql})`);
            }
        }

        //  buka modal dalam mode EDIT (dipanggil dari tombol Edit di grid EasyUI).
        function editInspecDocument(inspecpk) {
            $.get(`{{ url('/inspection/inspect-show') }}/${inspecpk}`, function(data) {
                window.editingInspecpk = data.inspecpk;
                inspecCart = data.lines.map(l => ({
                    packpk: l.packpk,
                    carton: l.carton,
                    POno: l.POno, // BARU
                    OP: l.OP, // BARU
                    size: l.size,
                    color: l.color,
                    secsz: l.secsz,
                    qty: l.qty,
                    stspass: l.stspass,
                    defects: (l.defects || []).map(pk => ({
                        defectpk: pk,
                        defectnm: '#' + pk
                    })),
                }));
                inspecRemainingBySizeKey = {};
                $('#inspecAql').val(data.aql);
                $('#inspecKeterangan').val(data.keterangan ?? ''); // BARU
                renderInspecCart();
                recomputeHasilDisplay();

                bootstrap.Modal.getOrCreateInstance(document.getElementById('inspectDocumentModal')).show();
                loadGlobalInspectAvailableCartons();

                loadDefectSubDataIfNeeded(function() {
                    inspecCart.forEach(function(line) {
                        line.defects = line.defects.map(function(d) {
                            const found = inspecDefectSubCache.defects.find(x => x
                                .defectpk === d.defectpk);
                            return found ? {
                                defectpk: found.defectpk,
                                defectnm: found.defectnm
                            } : d;
                        });
                    });
                    renderInspecCart();
                });
            });
        }

        function refreshInspectionTabs() {
            $('#dgInspectDocuments').datagrid('reload');
            loadCartonInspecList();
        }

        function submitInspecDocument() {
            if (!inspecCart.length) {
                showToast('warning', 'Pilih minimal 1 sample.');
                return;
            }
            const aql = $('#inspecAql').val();
            if (aql === '' || Number(aql) < 0) {
                showToast('warning', 'Isi nilai AQL terlebih dulu.');
                return;
            }
            const belumPilihDefect = inspecCart.some(l => l.stspass === 0 && (!l.defects || !l.defects.length));
            if (belumPilihDefect) {
                showToast('warning', 'Ada baris berstatus Defect yang belum dipilih tipe defect-nya.');
                return;
            }

            const isEdit = !!window.editingInspecpk;
            const url = isEdit ?
                `{{ url('/inspection/inspect-update') }}/${window.editingInspecpk}` :
                "{{ route('inspection.inspect-store') }}";

            $('#btnSubmitInspecDoc').prop('disabled', true);
            $.ajax({
                url: url,
                method: isEdit ? 'PUT' : 'POST',
                data: {
                    aql: aql,
                    keterangan: $('#inspecKeterangan').val(), // BARU
                    lines: inspecCart.map(l => ({
                        packpk: l.packpk,
                        size: l.size,
                        color: l.color,
                        secsz: l.secsz,
                        qty: l.qty,
                        stspass: l.stspass,
                        defects: l.defects.map(d => d.defectpk),
                    })),
                },
                success: function(res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('inspectDocumentModal')).hide();
                    window.editingInspecpk = null;
                    refreshInspectionTabs(); // BARU
                },
                error: function(xhr) {
                    const res = xhr.responseJSON || {
                        icon: 'error',
                        title: 'Gagal menyimpan dokumen inspect.'
                    };
                    showToast(res.icon, res.title);
                },
                complete: function() {
                    $('#btnSubmitInspecDoc').prop('disabled', false);
                }
            });
        }

        $('#tabBtnDokumenInspect').on('shown.bs.tab', function() {
            // paksa EasyUI menghitung ulang lebar grid setelah
            // tab-nya BENAR-BENAR terlihat -- ini penyebab data tidak muncul
            // (grid diinisialisasi saat container masih display:none, lebar 0).
            $('#dgInspectDocuments').datagrid('resize').datagrid('reload');
        });

        function filterInspecCartonList() {
            const q = $('#inspecCartonSearch').val().trim().toLowerCase();
            $('.inspec-carton-row').each(function() {
                const cartonNo = $(this).find('.inspec-carton-title').text().toLowerCase();
                const poOpText = $(this).find('.inspec-carton-sub').text().toLowerCase();
                const match = !q || cartonNo.includes(q) || poOpText.includes(q);
                $(this).toggle(match);
            });
        }

        //  buka/tutup SEMUA carton sekaligus (memudahkan scan cepat
        // banyak carton tanpa klik satu-satu).
        function toggleAllInspecCarton(open) {
            $('.inspec-size-detail').toggleClass('is-open', open);
            $('.inspec-carton-header i.fa-chevron-down, .inspec-carton-header i.fa-chevron-up')
                .toggleClass('fa-chevron-down', !open)
                .toggleClass('fa-chevron-up', open);
        }
    </script>
@endsection
