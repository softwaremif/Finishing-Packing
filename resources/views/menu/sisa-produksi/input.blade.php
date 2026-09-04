@extends('layout.main')

@section('css_custom')
    <style>
        :root {
            --primary-color: #4f46e5;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }
        a i { transition: transform 0.2s ease; }
        a:hover i { transform: scale(1.2) translateX(-2px); }
        .btn-icon-custom:hover {
            background-color: #f8fafc !important;
            color: #1e293b !important;
            transform: translateX(-3px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        }

        /* ============================================================
           GRADE TICKET CARD -- gaya "dokumen produksi" ERP garment:
           header ber-ribbon Grade, garis putus-putus (perforasi) di
           antara header & body, size ditampilkan sebagai chip matrix,
           footer berisi status/progress + aksi.
           ============================================================ */
        .doc-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
            transition: box-shadow .15s ease, border-color .15s ease;
        }
        .doc-card:hover {
            box-shadow: 0 4px 10px rgba(15, 23, 42, .1);
            border-color: #cbd5e1;
        }
        .doc-card-grade-ribbon {
            position: absolute;
            top: 10px;
            right: -34px;
            transform: rotate(40deg);
            width: 130px;
            text-align: center;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 3px 0;
            color: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,.15);
            z-index: 2;
        }
        .doc-card-grade-ribbon.grade-a { background: #16a34a; }
        .doc-card-grade-ribbon.grade-b { background: #2563eb; }
        .doc-card-grade-ribbon.grade-c { background: #d97706; }
        .doc-card-grade-ribbon.grade-none { background: #64748b; }

        .doc-card-header {
            padding: 12px 14px 10px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }
        .doc-card-bjpk {
            font-size: 10.5px;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: .3px;
        }
        .doc-card-date {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .doc-card-line {
            font-size: 11.5px;
            color: var(--text-muted);
            margin-top: 1px;
        }
        .doc-card-source-badge {
            font-size: 9px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #64748b;
            text-transform: uppercase;
        }

        /* Perforasi -- garis putus-putus pemisah header/body, kesan
           "robekan tiket". */
        .doc-card-perforation {
            position: relative;
            border-top: 1.5px dashed #d8dee6;
            margin: 0 14px;
        }
        .doc-card-perforation::before,
        .doc-card-perforation::after {
            content: '';
            position: absolute;
            top: -7px;
            width: 14px;
            height: 14px;
            background: #f8fafc;
            border-radius: 50%;
            border: 1px solid var(--border-color);
        }
        .doc-card-perforation::before { left: -21px; }
        .doc-card-perforation::after { right: -21px; }

        .doc-card-body {
            padding: 12px 14px;
            flex: 1 1 auto;
        }
        .doc-size-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(48px, 1fr));
            gap: 6px;
            margin-bottom: 10px;
        }
        .doc-size-chip {
            background: #f8fafc;
            border: 1px solid #eef1f5;
            border-radius: 7px;
            padding: 5px 4px;
            text-align: center;
        }
        .doc-size-chip .sc-label {
            font-size: 9.5px;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
        }
        .doc-size-chip .sc-value {
            font-size: 14px;
            font-weight: 800;
            color: var(--text-main);
        }
        .doc-card-total-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            padding-top: 8px;
            border-top: 1px solid #f1f5f9;
        }
        .doc-card-total-label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .3px;
        }
        .doc-card-total-value {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-main);
        }
        .doc-card-total-value small {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .doc-card-footer {
            padding: 10px 14px 14px;
            background: #fafbfc;
            border-top: 1px solid #f1f5f9;
        }
        .doc-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
        }
        .doc-status-pill.st-menunggu { background: #fef3c7; color: #92400e; }
        .doc-status-pill.st-sebagian { background: #dbeafe; color: #1e40af; }
        .doc-status-pill.st-selesai  { background: #dcfce7; color: #166534; }
        .doc-status-pill.st-diterima { background: #dcfce7; color: #166534; }

        .doc-progress-bar {
            height: 6px;
            background: #eef0f2;
            border-radius: 3px;
            overflow: hidden;
            margin: 8px 0 6px;
        }
        .doc-progress-bar .bar {
            display: block;
            height: 100%;
            background: #2563eb;
        }
        .doc-progress-bar .bar.is-done { background: #16a34a; }

        .doc-btn-confirm {
            width: 100%;
            background: #16a34a;
            border-color: #16a34a;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            border-radius: 8px;
            padding: 9px 0;
            transition: background .15s ease;
        }
        .doc-btn-confirm:hover { background: #15803d; }

        .doc-btn-outline {
            width: 100%;
            background: #fff;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            font-weight: 600;
            font-size: 12.5px;
            border-radius: 8px;
            padding: 7px 0;
        }
        .doc-btn-outline:hover { background: #f1f5f9; }

        .doc-empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .doc-empty-state img { opacity: .85; }

        .doc-cards-count {
            font-size: 12.5px;
            color: var(--text-muted);
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
            <div class="card-header bg-white py-3 border-0">
                <div class="fw-bold text-dark d-flex align-items-center mb-3" style="font-size: 14px;">
                    <span class="rounded me-2" style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
                    Detail Data Sisa Produksi
                </div>
                <ul class="nav nav-tabs" id="sisaTabs" role="tablist" style="border-bottom: 2px solid #e2e8f0;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-pending-btn" onclick="switchSisaTab('pending')" type="button">
                            <i class="fas fa-hourglass-half me-1 text-warning"></i> Menunggu Konfirmasi
                            <span class="badge bg-warning-subtle text-warning-emphasis ms-1" id="countPending">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-received-btn" onclick="switchSisaTab('received')" type="button">
                            <i class="fas fa-box-open me-1 text-success"></i> Barang Diterima
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-keluar-btn" onclick="switchSisaTab('keluar')" type="button">
                            <i class="fas fa-truck-ramp-box me-1 text-primary"></i> Barang Keluar
                        </button>
                    </li>
                </ul>
            </div>
        
            <div class="card-body p-3" id="sisaCardsWrapper">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="doc-cards-count" id="sisaCardsCount">0 dokumen</div>
                </div>
        
                <div id="sisaCardsGrid" class="row g-3"></div>
        
                <div id="sisaCardsEmpty" class="doc-empty-state d-none">
                    <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="160">
                    <div class="fw-semibold mt-2">Tidak ada data</div>
                    <div class="text-muted" style="font-size:12.5px;">Belum ada dokumen pada tab ini</div>
                </div>
            </div>
        </div>

    </div>
    @include('menu.sisa-produksi.modal-transfer')
@endsection

@section('js_custom')
    <script>
        // ============================================================
        // STATE DASAR
        // ============================================================
        const currentPopk = {{ $dt->popk }};
        const isSuperUser = @json($isSuper ?? false);
        window.activeSizesGlobal = @json($activeSizes);
        let sisaActiveTab = 'pending';
        const sisaState = {
            pending:  { data: [] },
            received: { data: [] },
            keluar:   { data: [] },
        };
        $(function () {
            loadSisaTab('pending');
        });

        // ============================================================
        // TAB SWITCH
        // ============================================================
        function switchSisaTab(tab) {
            sisaActiveTab = tab;
            $('#sisaTabs .nav-link').removeClass('active');
            $(`#tab-${tab}-btn`).addClass('active');
            if (sisaState[tab].data.length || sisaState[tab].loaded) {
                renderSisaCards(tab);
            } else {
                loadSisaTab(tab);
            }
        }

        // ============================================================
        // LOAD DATA
        // ============================================================
        function loadSisaTab(tab) {
            $('#sisaCardsGrid').html('<div class="text-center text-muted py-5 w-100">Memuat data...</div>');
            $('#sisaCardsEmpty').addClass('d-none');
            $.get("{{ route('sisa-produksi.detail.list', $dt->popk) }}", {
                cr: '{{ $cr }}',
                tab: tab,
                mif: '{{ $mif }}',
                rows: 200
            }, function (data) {
                sisaState[tab].data = data.rows || [];
                sisaState[tab].loaded = true;
                if (tab === 'pending') {
                    $('#countPending').text(sisaState[tab].data.length);
                }
                renderSisaCards(tab);
            }).fail(function () {
                $('#sisaCardsGrid').empty();
                $('#sisaCardsEmpty').removeClass('d-none');
                $('#sisaCardsCount').text('Gagal memuat data');
            });
        }

        function reloadSisaTab(tab) {
            sisaState[tab].loaded = false;
            if (sisaActiveTab === tab) {
                loadSisaTab(tab);
            } else {
                $.get("{{ route('sisa-produksi.detail.list', $dt->popk) }}", {
                    cr: '{{ $cr }}', tab: tab, mif: '{{ $mif }}', rows: 200
                }, function (data) {
                    sisaState[tab].data = data.rows || [];
                    sisaState[tab].loaded = true;
                    if (tab === 'pending') $('#countPending').text(sisaState[tab].data.length);
                });
            }
        }

        function reloadTransferGrid() {
            reloadSisaTab('pending');
            reloadSisaTab('received');
            reloadSisaTab('keluar');
        }

        function reloadBreakdownSummary() {
            $.get("{{ route('transfer.breakdown-summary', $dt->popk) }}", { mif: '{{ $mif }}' }, function (html) {
                $('#breakdownSummaryWrapper').html(html);
            });
        }

        // ============================================================
        // HELPER FORMAT
        // ============================================================
        function formatDateLabel(value) {
            if (!value) return null;
            const datePart = String(value).split(' ')[0];
            const parts = datePart.split('-');
            if (parts.length !== 3) return value;
            const [y, m, d] = parts;
            return `${d}/${m}/${y}`;
        }

        function gradeClass(grade) {
            const g = String(grade || '').trim().toUpperCase();
            if (g === 'A') return 'grade-a';
            if (g === 'B') return 'grade-b';
            if (g === 'C') return 'grade-c';
            return 'grade-none';
        }

        function buildSizeGrid(row) {
            const idx = Object.keys(window.activeSizesGlobal || {});
            let html = '';
            idx.forEach(function (i) {
                const val = row[`qty${i}`];
                if (val === null || val === undefined || val === '' || Number(val) <= 0) return;
                html += `
                    <div class="doc-size-chip">
                        <div class="sc-label">${window.activeSizesGlobal[i]}</div>
                        <div class="sc-value">${val}</div>
                    </div>
                `;
            });
            return html || '<div class="text-muted" style="font-size:12px;">Tidak ada breakdown size</div>';
        }

        // BARU -- size grid dari ARRAY {label, qty} (dipakai buildKeluarCard(),
        // BUKAN dari kolom qty1..40 seperti buildSizeGrid()).
        function buildSizeGridFromArray(sizes) {
            if (!sizes || !sizes.length) {
                return '<div class="text-muted" style="font-size:12px;">Tidak ada breakdown size</div>';
            }
            return sizes.map(function (s) {
                return `
                    <div class="doc-size-chip">
                        <div class="sc-label">${s.label}</div>
                        <div class="sc-value">${s.qty}</div>
                    </div>
                `;
            }).join('');
        }

        // ============================================================
        // BANGUN KARTU
        // ============================================================
        function buildDocCard(row, footerHtml, docNumber) {
            const sourceBadge = row.source === 'output'
                ? '<span class="doc-card-source-badge">Barcode</span>'
                : '';
            return `
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="doc-card">
                        <div class="doc-card-grade-ribbon ${gradeClass(row.grade)}">${row.grade || '-'}</div>
                        <div class="doc-card-header">
                            <div>
                                <div class="doc-card-bjpk">DOC #${docNumber ?? '-'}</div>
                                <div class="doc-card-date"><i class="fas fa-calendar-day text-muted" style="font-size:11px;"></i> ${formatDateLabel(row.tanggal) || '-'}</div>
                                <div class="doc-card-line">${row.linenm ? row.linenm.replace(/^line\s*/i, '') : '-'} ${sourceBadge}</div>
                            </div>
                        </div>
                        <div class="doc-card-perforation"></div>
                        <div class="doc-card-body">
                            <div class="doc-size-grid">${buildSizeGrid(row)}</div>
                            <div class="doc-card-total-row">
                                <span class="doc-card-total-label">Total Pcs</span>
                                <span class="doc-card-total-value">${row.pcs ?? 0}</span>
                            </div>
                        </div>
                        <div class="doc-card-footer">${footerHtml}</div>
                    </div>
                </div>
            `;
        }

        // ============================================================
        // FIX UTAMA -- tombol Konfirmasi Terima HANYA muncul kalau:
        // 1. row.lo_lopk ADA (bjpk memang tercatat di lodt/lo), DAN
        // 2. row.lo_approved === true (stsapv1=1 DAN stsapv2=1 DAN
        //    stsapv3=1 di LO tersebut).
        // Backend (detailList()) SUDAH mengirim 'lo_lopk' & 'lo_approved'
        // per baris -- kalau field ini TIDAK ADA di response, berarti
        // backend belum di-update, BUKAN salah di JS ini.
        // ============================================================
        function buildPendingCard(row) {
            const hasGrade = row.grade !== null && row.grade !== undefined && String(row.grade).trim() !== '';
            let footer;

            if (!hasGrade) {
                footer = `<span class="text-muted" style="font-size:12px;">Menunggu grade diinput</span>`;
            } else if (!row.lo_lopk) {
                footer = `<span class="text-muted" style="font-size:12px;"><i class="fas fa-circle-info me-1"></i>Belum masuk LO Kirim Sisa</span>`;
            } else if (row.lo_approved !== true) {
                footer = `<span class="text-muted" style="font-size:12px;"><i class="fas fa-hourglass-half me-1"></i>Menunggu LO #${row.lo_lopk} disetujui (3 level)</span>`;
            } else {
                footer = `<button type="button" class="doc-btn-confirm" onclick="completeRow(${row.bjpk})">
                               <i class="fas fa-check-circle me-1"></i> Konfirmasi Terima
                           </button>`;
            }

            return buildDocCard(row, footer, row.lo_lopk);
        }

        function buildReceivedCard(row) {
            const tglDiterima = formatDateLabel(row.tglin);
            const footer = `
                <span class="doc-status-pill st-diterima"><i class="fas fa-check"></i> Diterima ${tglDiterima ? '&middot; ' + tglDiterima : ''}</span>
            `;
            return buildDocCard(row, footer, row.lo_lopk);
        }

        // ============================================================
        // FIX UTAMA -- Tab Keluar SEPENUHNYA dari outsisa/outsisadt.
        // TIDAK ADA aksi apa pun (keluar cuma lewat menu Keluarkan
        // Sisa). Card yang BELUM fully-approved (stsapv1/2/3 belum
        // semua 1) DIFILTER di renderSisaCards() SEBELUM sampai sini --
        // fungsi ini asumsikan row yang masuk SUDAH pasti approved.
        // ============================================================
        function buildKeluarCard(row) {
            const tglKeluar = formatDateLabel(row.tglout);
            const sizesHtml = buildSizeGridFromArray(row.sizes);

            const footer = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="doc-status-pill st-selesai"><i class="fas fa-check"></i> ${row.status_label || 'Selesai (Approved)'}</span>
                </div>
                ${row.penerima ? `<div style="font-size:11px;color:#64748b;">Penerima: <strong>${row.penerima}</strong></div>` : ''}
                ${tglKeluar ? `<div style="font-size:11px;color:#94a3b8;">Tanggal Keluar: ${tglKeluar}</div>` : ''}
                ${row.keterangan ? `<div style="font-size:11px;color:#64748b;margin-top:4px;"><i class="fas fa-note-sticky me-1"></i>${row.keterangan}</div>` : ''}
            `;

            return `
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="doc-card">
                        <div class="doc-card-grade-ribbon ${gradeClass(row.grade)}">${row.grade || '-'}</div>
                        <div class="doc-card-header">
                            <div>
                                <div class="doc-card-bjpk">DOC #${row.outpk ?? '-'}</div>
                                <div class="doc-card-date"><i class="fas fa-calendar-day text-muted" style="font-size:11px;"></i> ${tglKeluar || '-'}</div>
                            </div>
                        </div>
                        <div class="doc-card-perforation"></div>
                        <div class="doc-card-body">
                            <div class="doc-size-grid">${sizesHtml}</div>
                            <div class="doc-card-total-row">
                                <span class="doc-card-total-label">Total Pcs</span>
                                <span class="doc-card-total-value">${row.pcs ?? 0}</span>
                            </div>
                        </div>
                        <div class="doc-card-footer">${footer}</div>
                    </div>
                </div>
            `;
        }

        // ============================================================
        // RENDER -- FIX UTAMA: tab 'keluar' DIFILTER DULU, HANYA row
        // yang stsapv1===1 DAN stsapv2===1 DAN stsapv3===1 yang boleh
        // lolos ke render (dan ikut dihitung count-nya).
        // ============================================================
        function renderSisaCards(tab) {
            let rows = sisaState[tab].data;

            if (tab === 'keluar') {
                rows = rows.filter(r => r.stsapv1 === 1 && r.stsapv2 === 1 && r.stsapv3 === 1);
            }

            const grid = $('#sisaCardsGrid'), empty = $('#sisaCardsEmpty');
            grid.empty();
            if (!rows.length) {
                empty.removeClass('d-none');
                $('#sisaCardsCount').text('0 dokumen');
                return;
            }
            empty.addClass('d-none');
            $('#sisaCardsCount').text(rows.length + ' dokumen');
            rows.forEach(function (row) {
                if (tab === 'pending') grid.append(buildPendingCard(row));
                else if (tab === 'received') grid.append(buildReceivedCard(row));
                else if (tab === 'keluar') grid.append(buildKeluarCard(row));
            });
        }

        // ============================================================
        // AKSI
        // ============================================================
        function completeRow(bjpk) {
            $.ajax({
                url: "{{ url('sisa-produksi') }}/" + bjpk + "/complete",
                method: 'POST',
                data: { mif: '{{ $mif }}' },
                success: function (res) {
                    showToast(res.icon, res.title);
                    reloadTransferGrid();
                    reloadBreakdownSummary();
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    showToast(res.icon, res.title);
                }
            });
        }

        // CATATAN: editKeluarByIndex() dan penggunaan fillEditForm() KHUSUS
        // utk tombol "Input Actual Keluar" SUDAH DIHAPUS -- tab Keluar
        // sekarang murni informasi dari outsisa, tidak ada aksi edit lagi.
        // fillEditForm()/setDateSafe()/myformatter()/myparser() di bawah
        // TETAP dipertahankan HANYA JIKA masih dipakai fitur lain di
        // halaman ini (modal-transfer) -- kalau TIDAK ada lagi yang
        // memanggilnya, boleh dihapus semua beserta

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
                el.val(value);
            }
        }

        function myformatter(date) {
            const y = date.getFullYear();
            const m = (date.getMonth() + 1).toString().padStart(2, '0');
            const d = date.getDate().toString().padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function myparser(s) {
            if (!s) return new Date();
            const t = s.split('-');
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
