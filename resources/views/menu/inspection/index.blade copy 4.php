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
        .po-popup-table th, .po-popup-table td {
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
        .po-popup-table td.cell-left { text-align: left; }
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
    </style>
@endsection
@section('content')
    <div class="page-wrap">
 
        {{-- BARU -- 4 tab, SAMA pola dengan index FG/Stuffing --}}
        <div class="segmented-tabs-row">
            <ul class="nav segmented-tabs" id="inspectionIndexTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tabBtnDaftarPo" data-bs-toggle="tab" data-bs-target="#tabPaneDaftarPo"
                        type="button" role="tab">
                        <i class="fas fa-list me-1"></i> Daftar PO Inspection
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tabBtnCartonInspec" data-bs-toggle="tab" data-bs-target="#tabPaneCartonInspec"
                        type="button" role="tab" onclick="loadCartonInspecTabIfNeeded()">
                        <i class="fas fa-boxes-stacked me-1"></i> Carton Inspec
                        <span id="cartonInspecTabCount" class="badge bg-secondary ms-1 d-none">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tabBtnDokumenInspect" data-bs-toggle="tab" data-bs-target="#tabPaneDokumenInspect"
                        type="button" role="tab" onclick="loadDokumenInspectTabIfNeeded()">
                        <i class="fas fa-clipboard-check me-1"></i> Dokumen Inspect
                    </button>
                </li>
                {{-- <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tabBtnHistoryCarton" data-bs-toggle="tab" data-bs-target="#tabPaneHistoryCarton"
                        type="button" role="tab" onclick="loadHistoryCartonTabIfNeeded()">
                        <i class="fas fa-clock-rotate-left me-1"></i> History Carton Inspect
                    </button>
                </li> --}}
            </ul>
        </div>
 
        <div class="tab-content" id="inspectionIndexTabContent">
 
            {{-- TAB 1 -- ISI LAMA (persis sama). --}}
            <div class="tab-pane fade show active" id="tabPaneDaftarPo" role="tabpanel">
                <x-table-default
                    id="dgInspection"
                    title="Daftar PO Inspection"
                    search search-name="search" search-placeholder="Search..." buyer
                    buyer-name="buyer" buyer-url="{{ route('api.buyer-list') }}" buyer-value-field="buyer"
                    buyer-text-field="buyer_name" buyer-mode="remote" year year-name="year" exfactory exfactory-name="ex_factory"
                    sort-dropdown sort-asc-label="Awal Ex-Factory" sort-desc-label="Akhir Ex-Factory"
                >
                    <table id="dgInspection" class="easyui-datagrid" style="width:100%;height:500px"
                        url="{{ route('inspection.list') }}" method="get" pagination="true" pageSize="50"
                        pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true"
                        checkOnSelect="true" selectOnCheck="true" fitColumns="false" border="false">
 
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
                                <th field="pcs_inspect" width="150" formatter="pcsctn" align="center">Cartons <br> in inspection</th>
                                <th field="inspect_status" width="150" formatter="formatInspectStatus" align="center">Inspection <br> Status</th>
                                <th field="material" width="150">Color</th>
                                <th field="secsz" width="150">Secondary<br>Size</th>
                            </tr>
                        </thead>
                    </table>
                </x-table-default>
            </div>
 
            {{-- TAB 2 -- BARU -- Carton Inspec (global, SEMUA fca=1). --}}
            <div class="tab-pane fade" id="tabPaneCartonInspec" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="input-group" style="width:280px;">
                        <input type="text" class="form-control form-control-sm" id="searchCartonInspec"
                            placeholder="Cari carton / barcode / PO / OP / buyer...">
                    </div>
                </div>
                <div id="cartonInspecLoading" class="text-center text-muted py-5">Memuat...</div>
                <div id="cartonInspecEmpty" class="text-center text-muted py-5 d-none">Tidak ada carton sedang Inspect.</div>
                <div class="table-responsive d-none" id="cartonInspecTableWrap">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light text-secondary" style="font-size:11px;text-transform:uppercase;">
                            <tr>
                                <th>No CTN</th>
                                <th>Barcode</th>
                                <th>Color / Sec Size</th>
                                <th>PO / OP</th>
                                <th>Buyer</th>
                                <th>Masuk Inspect</th>
                                <th width="90" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="cartonInspecBody"></tbody>
                    </table>
                </div>
            </div>
 
            {{-- TAB 3 -- BARU -- Dokumen Inspect (global). --}}
            <div class="tab-pane fade" id="tabPaneDokumenInspect" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="input-group" style="width:280px;">
                        <input type="text" class="form-control form-control-sm" id="searchDokumenInspect"
                            placeholder="Cari carton / PO / OP...">
                    </div>
                </div>
                <div id="dokumenInspectLoading" class="text-center text-muted py-5">Memuat...</div>
                <div id="dokumenInspectEmpty" class="text-center text-muted py-5 d-none">Belum ada dokumen Inspect.</div>
                <div class="row g-3" id="dokumenInspectGrid"></div>
            </div>
 
            {{-- TAB 4 -- BARU -- History Carton Inspect (global). --}}
            {{-- <div class="tab-pane fade" id="tabPaneHistoryCarton" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="input-group" style="width:280px;">
                        <input type="text" class="form-control form-control-sm" id="searchHistoryCarton"
                            placeholder="Cari carton / barcode / PO / OP / buyer...">
                    </div>
                </div>
                <div id="historyCartonLoading" class="text-center text-muted py-5">Memuat...</div>
                <div id="historyCartonEmpty" class="text-center text-muted py-5 d-none">Belum ada riwayat Inspect.</div>
                <div class="table-responsive d-none" id="historyCartonTableWrap">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light text-secondary" style="font-size:11px;text-transform:uppercase;">
                            <tr>
                                <th>No CTN</th>
                                <th>PO / OP</th>
                                <th>Masuk Inspect</th>
                                <th>Kembali</th>
                                <th>Status</th>
                                <th width="90" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="historyCartonBody"></tbody>
                    </table>
                </div>
            </div> --}}
 
        </div>
    </div>
 
    {{-- modal poPopup lama TIDAK berubah --}}
    <div class="modal fade" id="poPopup" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="poPopupTitle">Rincian PO</h5>
                    <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
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
@endsection
 

@section('js_custom')
    <script>
        var popupUrl = "{{ route('finGoods.popup') }}";
        const isSuperUser = @json(session('guserpk') === 34);
        const localNoImg = "{{ asset('public/css/images/no-img.png') }}";

        function pcsctn(value, row, index) {
            var field = this.field;
            var ctnMap = { pcs_stuff: 'ctn_stuff', pcs_inspect: 'ctn_inspect', pcs_ship: 'ctn_ship' };
            var ctnValue = row[ctnMap[field]] || 0;
            var pcs = parseInt(value) || 0;
            var ctn = parseInt(ctnValue) || 0;

            return '<div style="font-size:14px;">' + ctn.toLocaleString() + ' ctn</div>' + '<div style="font-size:11px;color:#888;">' + pcs.toLocaleString() + ' pcs</div>';
        }

        // Kolom Pinjam / Kembali: tampilkan "-" jika kosong (belum pernah dipinjam/dikembalikan)
        function formatDate(value, row, index) {
            if (!value) return '-';
            return value;
        }

        // function formatPinjamKembali(value, row, index) {
        //     var pinjam  = row.pinjam  ? row.pinjam  : '-';
        //     var kembali = row.kembali ? row.kembali : '-';

        //     return '<div style="line-height:1.4;">' +
        //         '<div><span style="color:#94a3b8;">P:</span> ' + pinjam + '</div>' +
        //         '<div><span style="color:#94a3b8;">K:</span> ' + kembali + '</div>' +
        //         '</div>';
        // }

        // Status inspect — fraksi HANYA dari carton yang PERNAH diinspect:
        //   pembagi   = pinjam_count  (carton yang pernah dipinjam/inspect)
        //   pembilang = returned_count = pinjam_count - borrowed_count
        //               (yang pernah dipinjam dan kini sudah kembali)
        // Contoh: total 50 ctn, diinspect 10, belum ada yang kembali -> 0 / 10.
        // 'complete' -> semua yang dipinjam sudah kembali
        // 'partial'  -> masih ada yang dipinjam
        function formatInspectStatus(value, row, index) {
            var inspectedCount = parseInt(row.pinjam_count) || 0;
            var returnedCount  = parseInt(row.returned_count) || 0;

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
        function formatAction(value, row, index) {
            return `
                <a href="javascript:void(0)"
                onclick="openInspectionInput('${row.POno}', '${row.OP}', '${row.poref ?? ''}', ${row.mif ?? 'null'})"
                class="action-btn"
                style="margin-left:6px;"
                title="Buka Input Inspect">
                    <i class="fas fa-edit"></i>
                </a>
            `;
        }

        function openInspectionInput(pono, op, poref, mif) {
            let url = "{{ route('inspection.input.global') }}"
                + "?po=" + encodeURIComponent(pono ?? '')
                + "&op=" + encodeURIComponent(op)
                + "&poref=" + encodeURIComponent(poref ?? '');
            if (mif) url += "&mif=" + mif;
            window.location.href = url;
        }

        /* ============ Modal Bootstrap (kompatibel BS4 & BS5) ============ */

        var popupPono  = null;
        var popupOp    = null;
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
            return '<div>' + pcs.toLocaleString() + ' pcs</div>'
                + '<div class="sub">' + ctn.toLocaleString() + ' ctn</div>';
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

            const year     = parseInt(parts[0], 10);
            const monthIdx = parseInt(parts[1], 10) - 1;
            const dayNum   = parseInt(parts[2], 10);

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
            const mifBadge = isSuperUser
                ? `<span class="badge bg-secondary-subtle text-secondary-emphasis mif-badge">mif ${row.mif}</span>`
                : '';
            return `
                <div class="cell-stack">
                    <div class="cs-main">${value ?? '-'}${mifBadge}</div>
                    <div class="cs-sub">${row.customer ?? '-'}</div>
                </div>
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
        $(document).on('keyup', '#searchCartonInspec', function () {
            clearTimeout(cartonInspecSearchTimer);
            cartonInspecSearchTimer = setTimeout(loadCartonInspecList, 300);
        });

        function loadCartonInspecList() {
            $('#cartonInspecLoading').removeClass('d-none');
            $('#cartonInspecEmpty').addClass('d-none');
            $('#cartonInspecTableWrap').addClass('d-none');

            $.get("{{ route('inspection.globalCartonList') }}", {
                search: $('#searchCartonInspec').val(),
                page: 1,
                rows: 200
            }, function (data) {
                cartonInspecLoaded = true;
                $('#cartonInspecLoading').addClass('d-none');

                const $badge = $('#cartonInspecTabCount');
                if (data.total > 0) $badge.text(data.total).removeClass('d-none');
                else $badge.addClass('d-none');

                if (!data.rows || !data.rows.length) {
                    $('#cartonInspecEmpty').removeClass('d-none');
                    return;
                }

                $('#cartonInspecTableWrap').removeClass('d-none');
                const $body = $('#cartonInspecBody');
                $body.empty();

                data.rows.forEach(function (r) {
                    $body.append(`
                        <tr>
                            <td><strong>${r.carton ?? '-'}</strong></td>
                            <td>${r.nobar ?? '-'}</td>
                            <td>${r.material ?? '-'} ${r.secsz ? '(' + r.secsz + ')' : ''}</td>
                            <td>${r.POno ?? '-'} &middot; ${r.OP ?? '-'}</td>
                            <td>${r.buyer ?? '-'}</td>
                            <td>${formatDate(r.pinjam)}</td>
                            <td class="text-center">
                                <a href="javascript:void(0)" class="action-btn" title="Buka Input Inspect"
                                    onclick="openInspectionInput('${r.POno}', '${r.OP}', '${r.poref ?? ''}', ${r.mif ?? 'null'})">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    `);
                });
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
        $(document).on('keyup', '#searchDokumenInspect', function () {
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
            }, function (data) {
                dokumenInspectLoaded = true;
                $('#dokumenInspectLoading').addClass('d-none');

                if (!data.rows || !data.rows.length) {
                    $('#dokumenInspectEmpty').removeClass('d-none');
                    return;
                }

                const $grid = $('#dokumenInspectGrid');
                data.rows.forEach(function (doc) {
                    const isLulus = doc.hasil === 1;
                    const badgeCls = isLulus ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#991b1b;';
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

        // ============================================================
        // TAB 4 -- History Carton Inspect (global)
        // ============================================================
        let historyCartonLoaded = false;

        function loadHistoryCartonTabIfNeeded() {
            if (historyCartonLoaded) return;
            historyCartonLoaded = true;
            loadHistoryCartonGlobalList();
        }

        let historyCartonSearchTimer = null;
        $(document).on('keyup', '#searchHistoryCarton', function () {
            clearTimeout(historyCartonSearchTimer);
            historyCartonSearchTimer = setTimeout(loadHistoryCartonGlobalList, 300);
        });

        function loadHistoryCartonGlobalList() {
            $('#historyCartonLoading').removeClass('d-none');
            $('#historyCartonEmpty').addClass('d-none');
            $('#historyCartonTableWrap').addClass('d-none');

            $.get("{{ route('inspection.globalHistoryList') }}", {
                search: $('#searchHistoryCarton').val(),
                page: 1,
                rows: 200
            }, function (data) {
                historyCartonLoaded = true;
                $('#historyCartonLoading').addClass('d-none');

                if (!data.rows || !data.rows.length) {
                    $('#historyCartonEmpty').removeClass('d-none');
                    return;
                }

                $('#historyCartonTableWrap').removeClass('d-none');
                const $body = $('#historyCartonBody');
                $body.empty();

                const statusBadge = {
                    inspect: '<span class="badge" style="background:#fef3c7;color:#b45309;">Sedang Inspect</span>',
                    kembali: '<span class="badge" style="background:#dcfce7;color:#15803d;">Sudah Kembali</span>',
                    reject:  '<span class="badge" style="background:#fee2e2;color:#991b1b;">Reject</span>',
                    lainnya: '<span class="badge bg-secondary">-</span>',
                };

                data.rows.forEach(function (r) {
                    $body.append(`
                        <tr>
                            <td><strong>${r.carton ?? '-'}</strong></td>
                            <td>${r.POno ?? '-'} &middot; ${r.OP ?? '-'}</td>
                            <td>${formatDate(r.pinjam)}</td>
                            <td>${formatDate(r.kembali)}</td>
                            <td>${statusBadge[r.status] ?? statusBadge.lainnya}</td>
                            <td class="text-center">
                                <a href="javascript:void(0)" class="action-btn" title="Buka Input Inspect"
                                    onclick="openInspectionInput('${r.POno}', '${r.OP}', '${r.poref ?? ''}', ${r.mif ?? 'null'})">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    `);
                });
            });
        }
    </script>
@endsection