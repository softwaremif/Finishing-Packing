@extends('layout.main')
@section('css_custom')
    <style>
        .info-icon-wrapper {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
        }
        .sample-photo {
            width: 240px;
            height: 250px;
            /* object-fit:cover; */
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 10px;
            background: #f8fafc;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            transition: 0.2s;
            cursor: pointer;
            border: none;
        }
        .action-btn.btn-add { background: #dcfce7; color: #15803d; }
        .action-btn.btn-add:hover { background: #bbf7d0; }
        .action-btn.btn-edit { background: #e0f2fe; color: #0369a1; }
        .action-btn.btn-edit:hover { background: #bae6fd; }
        .action-btn.btn-del { background: #fee2e2; color: #b91c1c; }
        .action-btn.btn-del:hover { background: #fecaca; }
        .nav-tabs .nav-link {
            color: #64748b;
            font-weight: 500;
            border: none;
            padding: 10px 18px;
        }
        .nav-tabs .nav-link.active {
            font-weight: 600;
            border-bottom: 2px solid #1e293b;
            color: #1e293b;
            background: transparent;
        }
        #sizeTable thead th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }
        #sizeTable td {
            font-size: 13.5px;
            vertical-align: middle;
        }
        #sizeTable tbody tr:hover {
            background-color: #f8fafc;
        }
        .qty-sisa-value {
            font-weight: 700;
            font-size: 14px;
        }

        .btn-icon-custom:hover {
            background-color: #f8fafc !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1) !important;
            color: #1e293b !important;
            transform: translateX(-3px);
        }
    </style>
    <style>
        .sisa-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(15,23,42,.06);
            overflow: hidden;
            position: relative;
            transition: box-shadow .15s ease, border-color .15s ease;
        }
        .sisa-card:hover { box-shadow: 0 4px 10px rgba(15,23,42,.1); border-color: #cbd5e1; }
        .sisa-card-ribbon {
            position: absolute; top: 10px; right: -34px; transform: rotate(40deg);
            width: 130px; text-align: center; font-size: 10.5px; font-weight: 800;
            letter-spacing: 1px; text-transform: uppercase; padding: 3px 0; color: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,.15); z-index: 2;
        }
        .sisa-card-ribbon.st-added { background: #16a34a; }
        .sisa-card-ribbon.st-pending { background: #94a3b8; }
        .sisa-card-header { padding: 12px 14px 10px; }
        .sisa-card-size { font-size: 16px; font-weight: 800; color: #0f172a; }
        .sisa-card-cw { font-size: 11.5px; color: #64748b; margin-top: 1px; }
        .sisa-card-perforation {
            position: relative; border-top: 1.5px dashed #d8dee6; margin: 0 14px;
        }
        .sisa-card-perforation::before, .sisa-card-perforation::after {
            content: ''; position: absolute; top: -7px; width: 14px; height: 14px;
            background: #f8fafc; border-radius: 50%; border: 1px solid #e2e8f0;
        }
        .sisa-card-perforation::before { left: -21px; }
        .sisa-card-perforation::after { right: -21px; }
        .sisa-card-body { padding: 12px 14px; }
        .sisa-card-qty-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px; }
        .sisa-card-qty-label { font-size: 10.5px; color: #64748b; font-weight: 600; text-transform: uppercase; }
        .sisa-card-qty-value { font-size: 18px; font-weight: 800; color: #0f172a; }
        .sisa-card-qty-value.is-sisa { color: #2563eb; }
        .sisa-card-footer {
            padding: 10px 14px 14px; background: #fafbfc; border-top: 1px solid #f1f5f9;
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
        }
    </style>

    <style>
        .doc-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
            transition: box-shadow .15s ease, border-color .15s ease;
        }
        .doc-card:hover { box-shadow: 0 4px 10px rgba(15, 23, 42, .1); border-color: #cbd5e1; }
        .doc-card-grade-ribbon {
            position: absolute; top: 10px; right: -34px; transform: rotate(40deg);
            width: 130px; text-align: center; font-size: 11px; font-weight: 800;
            letter-spacing: 1px; text-transform: uppercase; padding: 3px 0; color: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,.15); z-index: 2;
        }
        .doc-card-grade-ribbon.grade-a { background: #16a34a; }
        .doc-card-grade-ribbon.grade-b { background: #2563eb; }
        .doc-card-grade-ribbon.grade-c { background: #d97706; }
        .doc-card-grade-ribbon.grade-none { background: #64748b; }
        .doc-card-header { padding: 12px 14px 10px; display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .doc-card-bjpk { font-size: 10.5px; color: #64748b; font-weight: 600; letter-spacing: .3px; }
        .doc-card-date { font-size: 13.5px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px; }
        .doc-card-line { font-size: 11.5px; color: #64748b; margin-top: 1px; }
        .doc-card-perforation { position: relative; border-top: 1.5px dashed #d8dee6; margin: 0 14px; }
        .doc-card-perforation::before, .doc-card-perforation::after {
            content: ''; position: absolute; top: -7px; width: 14px; height: 14px;
            background: #f8fafc; border-radius: 50%; border: 1px solid #e2e8f0;
        }
        .doc-card-perforation::before { left: -21px; }
        .doc-card-perforation::after { right: -21px; }
        .doc-card-body { padding: 12px 14px; flex: 1 1 auto; }
        .doc-size-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(48px, 1fr)); gap: 6px; margin-bottom: 10px; }
        .doc-size-chip { background: #f8fafc; border: 1px solid #eef1f5; border-radius: 7px; padding: 5px 4px; text-align: center; }
        .doc-size-chip .sc-label { font-size: 9.5px; color: #64748b; font-weight: 700; text-transform: uppercase; }
        .doc-size-chip .sc-value { font-size: 14px; font-weight: 800; color: #0f172a; }
        .doc-card-total-row { display: flex; align-items: baseline; justify-content: space-between; padding-top: 8px; border-top: 1px solid #f1f5f9; }
        .doc-card-total-label { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
        .doc-card-total-value { font-size: 20px; font-weight: 800; color: #0f172a; }
        .doc-card-footer { padding: 10px 14px 14px; background: #fafbfc; border-top: 1px solid #f1f5f9; }
        .doc-status-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px; }
        .doc-status-pill.st-menunggu { background: #fef3c7; color: #92400e; }
        .doc-status-pill.st-sebagian { background: #dbeafe; color: #1e40af; }
        .doc-status-pill.st-selesai  { background: #dcfce7; color: #166534; }
        .doc-status-pill.st-diterima { background: #dcfce7; color: #166534; }
        .doc-status-pill.st-ditolak  { background: #fee2e2; color: #991b1b; }
        .doc-btn-confirm { width: 100%; background: #16a34a; border-color: #16a34a; color: #fff; font-weight: 700; font-size: 13px; border-radius: 8px; padding: 9px 0; transition: background .15s ease; }
        .doc-btn-confirm:hover { background: #15803d; }
        .doc-btn-outline { width: 100%; background: #fff; border: 1px solid #cbd5e1; color: #0f172a; font-weight: 600; font-size: 12.5px; border-radius: 8px; padding: 7px 0; }
        .doc-btn-outline:hover { background: #f1f5f9; }
        .doc-empty-state { text-align: center; padding: 60px 20px; }
        .doc-empty-state img { opacity: .85; }
    </style>
    <style>
        /* Baris size di modal Keluarkan -- reuse gaya sederhana, tambah
        highlight kalau sudah masuk keranjang. */
        .keluar-size-row {
            display: flex; align-items: center; gap: 10px;
            border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px;
            margin-bottom: 8px; background: #fff; transition: border-color .15s ease, background .15s ease;
        }
        .keluar-size-row.in-cart { border-color: #359DD9; background: #f0f9ff; }
        .keluar-size-info { flex: 1 1 auto; min-width: 0; }
        .keluar-size-info .ks-main { font-size: 13px; font-weight: 700; color: #0f172a; }
        .keluar-size-info .ks-sub { font-size: 11px; color: #64748b; }
        .keluar-size-info .ks-incart { font-size: 10.5px; color: #359DD9; font-weight: 700; }
        .keluar-cart-row {
            display: flex; align-items: flex-start; justify-content: space-between;
            font-size: 12px; padding: 8px 0; border-bottom: 1px dashed #e2e8f0;
        }
        .keluar-cart-row .kc-remove { color: #dc2626; cursor: pointer; flex-shrink: 0; margin-left: 8px; }
    </style>
@endsection
@section('content')
    <div class="container-fluid py-4 px-4">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('sisa-sample.index') }}"
                class="btn btn-icon-custom d-inline-flex align-items-center justify-content-center shadow-sm border bg-white text-secondary rounded-circle"
                style="width:38px;height:38px;">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h4 class="fw-bold mb-0" style="font-size:1.15rem;">Detail Sisa Garment</h4>
                <span class="text-secondary" style="font-size:13px;">SR# {{ $status->srno ?? '-' }}</span>
            </div>
        </div>

        {{-- ===================== CARD INFO -- ikon per field, sesuai tema ===================== --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-9">
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
                            <div class="col">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="info-icon-wrapper bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-hashtag fs-6"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size:10px; letter-spacing:.5px;">SR#</div>
                                        <div class="fw-bold text-dark" style="font-size:14px;">{{ $status->srno ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="info-icon-wrapper bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-user-tie fs-6"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size:10px; letter-spacing:.5px;">Buyer</div>
                                        <div class="fw-bold text-dark text-truncate" style="font-size:14px;" title="{{ $status->buyernm ?? '-' }}">{{ $status->buyernm ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="info-icon-wrapper bg-warning-subtle text-warning rounded-3 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-calendar-alt fs-6"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size:10px; letter-spacing:.5px;">Season</div>
                                        <div class="fw-bold text-dark" style="font-size:14px;">{{ $status->season ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="info-icon-wrapper bg-secondary-subtle text-secondary rounded-3 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-tshirt fs-6"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size:10px; letter-spacing:.5px;">Style</div>
                                        <div class="fw-bold text-dark text-truncate" style="font-size:14px;">{{ $status->style ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="info-icon-wrapper bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-vial fs-6"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size:10px; letter-spacing:.5px;">Sample Status</div>
                                        <div class="fw-bold text-dark" style="font-size:14px;">{{ $status->samplenm ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="info-icon-wrapper rounded-3 d-flex align-items-center justify-content-center" style="background:#f1f5f9;">
                                        <i class="fas fa-list text-dark fs-6"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size:10px; letter-spacing:.5px;">Option</div>
                                        <div class="fw-bold text-dark" style="font-size:14px;">{{ $status->opsi ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="info-icon-wrapper bg-danger-subtle text-danger rounded-3 d-flex align-items-center justify-content-center">
                                        <i class="fas fa-tint fs-6"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size:10px; letter-spacing:.5px;">Washing</div>
                                        <div class="fw-bold text-dark" style="font-size:14px;">{{ (int) $status->washing === 1 ? 'Yes' : 'No' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="info-icon-wrapper rounded-3 d-flex align-items-center justify-content-center" style="background:#f1f5f9;">
                                        <i class="fas fa-align-left text-dark fs-6"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="text-secondary text-uppercase fw-semibold mb-0.5" style="font-size:10px; letter-spacing:.5px;">Description</div>
                                        <div class="text-muted" style="font-size:12.5px; line-height:1.4;">{{ $status->description ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-center justify-content-center justify-content-md-end">
                        <img src="{{ route('sisa-sample.foto', $statuspk) }}"
                            class="sample-photo"
                            onerror="this.src='{{ asset('public/css/images/no-img.png') }}'"
                            alt="Foto Sample">
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================== TAB ===================== --}}
        <ul class="nav nav-tabs mb-3" id="detailTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabSisaMasuk" type="button">
                    <i class="fas fa-box-open me-1 text-success"></i> Barang Diterima
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSisaKeluar" type="button">
                    <i class="fas fa-truck-ramp-box me-1 text-primary"></i> Barang Keluar
                </button>
            </li>
        </ul>

        <div class="tab-content">
            {{-- ===== TAB SISA MASUK -- disederhanakan, edit via modal ===== --}}

            <div class="tab-pane fade show active" id="tabSisaMasuk">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 border-0">
                        <strong>Sisa Sample per Size</strong>
                        <div class="text-muted" style="font-size:12px;">
                            Klik kartu untuk menambahkan ke Gudang LO atau mengubah Qty Sisa.
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div id="sizeCardsGrid" class="row g-3">
                            <div class="col-12 text-center text-muted py-4">Memuat data...</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== TAB SISA KELUAR GUDANG -- placeholder, menyusul ===== --}}
            <div class="tab-pane fade" id="tabSisaKeluar">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
                        <div>
                            <strong>Sisa Keluar Gudang</strong>
                            <div class="text-muted" style="font-size:12px;">Riwayat pengeluaran sisa sample dari gudang.</div>
                        </div>
                        <button type="button" class="btn btn-dark btn-sm d-inline-flex align-items-center px-3 fw-semibold"
                            style="border-radius:6px;" onclick="openKeluarCreateModal()">
                            <i class="fas fa-plus me-1"></i> Keluarkan Barang
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <div id="keluarCardsGrid" class="row g-3">
                            <div class="col-12 text-center text-muted py-4">Memuat data...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== MODAL: Add / Edit Qty Sisa ===================== --}}
    <div class="modal fade" id="qtyModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:12px;">
                <div class="modal-header border-0 pb-1">
                    <h6 class="modal-title fw-bold" id="qtyModalTitle">Tambahkan ke Gudang LO</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <div class="text-secondary mb-3" id="qtyModalSizeLabel" style="font-size:13px;"></div>
                    <label class="form-label fw-semibold text-secondary" style="font-size:11px; text-transform:uppercase; letter-spacing:.4px;">
                        Qty Sisa
                    </label>
                    <input type="number" id="qtyModalInput" class="form-control form-control-lg text-center" min="0"
                        placeholder="0" oninput="validateQtyModalInput()" onkeydown="if(event.key==='Enter'){submitQtyModal();}">
                    <div class="text-muted text-center mt-1" style="font-size:11.5px;" id="qtyModalMaxHint"></div>
                    <div class="invalid-feedback d-block text-center" id="qtyModalError"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-dark" id="qtyModalSubmitBtn" onclick="submitQtyModal()">
                        <i class="fas fa-check me-1"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Keluarkan Barang -- pilih size (dari yang sudah masuk),
     input qty (dibatasi sisa), penerima, keterangan. --}}
    <div class="modal fade" id="keluarCreateModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius:12px;">
                <div class="modal-header py-3 border-0">
                    <h5 class="modal-title fw-bold" style="font-size:15px;">
                        <span class="rounded me-2" style="width:4px;height:16px;display:inline-block;background:#1e293b;"></span>
                        Keluarkan Sisa Sample
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="row g-3">
                        {{-- KIRI: browse size yang sudah masuk --}}
                        <div class="col-lg-8">
                            <div id="keluarSizeList" style="max-height:480px; overflow-y:auto;"></div>
                            <div id="keluarSizeEmpty" class="text-center text-muted py-5 d-none">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                Tidak ada sisa yang bisa dikeluarkan (semua sudah keluar, atau belum ada size yang ditambahkan).
                            </div>
                        </div>
    
                        {{-- KANAN: keranjang (sticky) --}}
                        <div class="col-lg-4">
                            <div class="border rounded-3 p-3 bg-light-subtle" style="position:sticky; top:0;">
                                <div class="fw-bold mb-2" style="font-size:13px;">
                                    <i class="fas fa-cart-shopping me-1 text-primary"></i>
                                    Keranjang (<span id="keluarCartCount">0</span> size)
                                </div>
                                <div id="keluarCartList" style="max-height:280px; overflow-y:auto;">
                                    <div class="text-muted text-center py-4" style="font-size:12.5px;" id="keluarCartEmpty">
                                        Belum ada size dipilih.
                                    </div>
                                </div>
                                <hr>
                                <label class="form-label fw-semibold" style="font-size:12px;">Penerima</label>
                                <input type="text" class="form-control form-control-sm mb-2" id="keluarPenerima" placeholder="Nama penerima...">
                                <label class="form-label fw-semibold" style="font-size:12px;">Keterangan (opsional)</label>
                                <textarea class="form-control form-control-sm mb-3" id="keluarKeterangan" rows="2" placeholder="Catatan..."></textarea>
                                <button type="button" class="btn btn-dark w-100 fw-semibold" style="border-radius:8px;"
                                    id="btnSubmitKeluar" onclick="submitKeluarCreate()">
                                    <i class="fas fa-truck-ramp-box me-1"></i> Keluarkan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </div>
    </div>
    
    {{-- MODAL: Detail outsisa -- approval steps + baris item. --}}
    <div class="modal fade" id="keluarDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius:12px;">
                <div class="modal-header py-3 border-0">
                    <h5 class="modal-title fw-bold" style="font-size:15px;">Detail Keluar <span id="keluarDetailNo" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <div style="font-size:12.5px;color:#64748b;">Tanggal Keluar: <strong id="keluarDetailDate"></strong></div>
                        <div style="font-size:12.5px;color:#64748b;">Penerima: <span id="keluarDetailPenerima">-</span></div>
                        <div style="font-size:12.5px;color:#64748b;">Keterangan: <span id="keluarDetailKeterangan">-</span></div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-3" id="keluarDetailSteps"></div>
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light" style="font-size:11px;text-transform:uppercase;">
                            <tr><th>Size</th><th>CW#</th><th class="text-end">Qty</th><th width="40"></th></tr>
                        </thead>
                        <tbody id="keluarDetailBody"></tbody>
                    </table>
                </div>
                <div class="modal-footer border-0 pt-0" id="keluarDetailFooter"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="confirmActionTitle" style="font-size:16px;">
                        <i class="fas fa-triangle-exclamation" id="confirmActionIcon" style="color:#dc2626;"></i>
                        Konfirmasi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-2">
                    <p class="mb-1 fw-semibold" id="confirmActionMessage" style="font-size:14px;"></p>
                    <small class="text-muted" id="confirmActionSubtext"></small>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal"
                        style="font-size:13px; border-radius:6px; height:33px;">
                        Batal
                    </button>
                    <button type="button" id="btnConfirmAction" class="btn btn-sm btn-dark px-4 d-inline-flex align-items-center gap-1"
                        style="font-size:13px; border-radius:6px; height:33px;">
                        <i class="fas fa-check small" id="confirmActionBtnIcon"></i>
                        <span id="confirmActionBtnLabel">Ya, Lanjutkan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js_custom')
    <script>
        const srpk     = {{ $srpk }};
        const statuspk = {{ $statuspk }};
        const isSuper  = @json($isSuper ?? false);
        const sisaSampleBaseUrl = "{{ url('/sisa-sample') }}";

        function loadSizes() {
            $.get("{{ route('sisa-sample.sizes', [$srpk, $statuspk]) }}", function (data) {
                renderSizeCards(data.rows || []);
            });
        }

        // ============================================================
        // FIX UTAMA -- ganti renderSizeTable() (tabel polos) jadi
        // renderSizeCards() (card grid ala Terima Sisa Gudang / doc-card).
        // ============================================================
        function renderSizeCards(rows) {
            const grid = $('#sizeCardsGrid');
            grid.empty();
        
            if (!rows.length) {
                grid.html('<div class="col-12 text-center text-muted py-4">Tidak ada data size.</div>');
                return;
            }
        
            rows.forEach(function (row) {
                const already = Number(row.masuk) === 1;
                const sizeLabel = row.size ?? '-';
                const qtyOrder = Number(row.qty ?? 0);
                const qtysVal = Number(row.qtys ?? 0);
                // Reuse warna doc-card-grade-ribbon: hijau (grade-a) = Sudah,
                // abu (grade-none) = Belum -- tidak perlu class CSS baru.
                const ribbonCls = already ? 'grade-a' : 'grade-none';
                const ribbonLabel = already ? 'Sudah' : 'Belum';
        
                let actionHtml = '';
                if (already) {
                    actionHtml = `<button type="button" class="doc-btn-outline" onclick="openQtyModal(${row.sizepk}, '${sizeLabel}', ${qtysVal}, ${qtyOrder}, true)">
                                    <i class="fas fa-pen me-1"></i> Ubah Qty Sisa
                                </button>`;
                    if (isSuper) {
                        actionHtml += `<button type="button" class="btn btn-sm btn-outline-danger w-100 mt-2" onclick="deleteSize(${row.sizepk})">
                                        <i class="fas fa-trash me-1"></i> Hapus
                                    </button>`;
                    }
                } else {
                    actionHtml = `<button type="button" class="doc-btn-confirm" onclick="openQtyModal(${row.sizepk}, '${sizeLabel}', 0, ${qtyOrder}, false)">
                                    <i class="fas fa-plus me-1"></i> Tambahkan ke Gudang LO
                                </button>`;
                }
        
                grid.append(`
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="doc-card">
                            <div class="doc-card-grade-ribbon ${ribbonCls}">${ribbonLabel}</div>
                            <div class="doc-card-header">
                                <div>
                                    <div class="doc-card-date">${sizeLabel}</div>
                                    <div class="doc-card-line">CW# ${row.cw ?? '-'}</div>
                                </div>
                            </div>
                            <div class="doc-card-perforation"></div>
                            <div class="doc-card-body">
                                <div class="doc-card-total-row" style="border-top:none; padding-top:0;">
                                    <span class="doc-card-total-label">Qty Order</span>
                                    <span class="doc-card-total-value" style="font-size:16px;">${qtyOrder}</span>
                                </div>
                                <div class="doc-card-total-row">
                                    <span class="doc-card-total-label">Qty Sisa</span>
                                    <span class="doc-card-total-value" style="color:#2563eb;">${already ? qtysVal : '-'}</span>
                                </div>
                            </div>
                            <div class="doc-card-footer">${actionHtml}</div>
                        </div>
                    </div>
                `);
            });
        }

        // ============================================================
        // MODAL Add/Edit Qty Sisa -- FIX UTAMA: SEKARANG simpan &
        // tampilkan batas maksimal (Qty Order), validasi client-side
        // SEBELUM submit (server juga validasi ulang, ini cuma UX cepat).
        // ============================================================
        let qtyModalSizepk = null;
        let qtyModalIsEdit = false;
        let qtyModalMaxQty = 0;

        function openQtyModal(sizepk, sizeName, currentQty, maxQty, isEdit) {
            qtyModalSizepk = sizepk;
            qtyModalIsEdit = isEdit;
            qtyModalMaxQty = Number(maxQty) || 0;

            $('#qtyModalTitle').text(isEdit ? 'Ubah Qty Sisa' : 'Tambahkan ke Gudang LO');
            $('#qtyModalSizeLabel').html(`Size: <strong>${sizeName}</strong>`);
            $('#qtyModalInput').val(currentQty || 0).attr('max', qtyModalMaxQty);
            $('#qtyModalMaxHint').text(`Maks: ${qtyModalMaxQty} (Qty Order)`);
            $('#qtyModalError').text('');
            $('#qtyModalInput').removeClass('is-invalid');

            const modalEl = document.getElementById('qtyModal');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            $(modalEl).one('shown.bs.modal', function () {
                $('#qtyModalInput').trigger('select');
            });
        }

        // BARU -- validasi live saat mengetik, dipanggil juga sebelum submit.
        function validateQtyModalInput() {
            const val = parseFloat($('#qtyModalInput').val());
            const $input = $('#qtyModalInput');
            const $error = $('#qtyModalError');

            if (isNaN(val) || val < 0) {
                $input.addClass('is-invalid');
                $error.text('Qty Sisa wajib diisi dengan angka valid.');
                return false;
            }
            if (val > qtyModalMaxQty) {
                $input.addClass('is-invalid');
                $error.text(`Qty Sisa tidak boleh melebihi Qty Order (${qtyModalMaxQty}).`);
                return false;
            }
            $input.removeClass('is-invalid');
            $error.text('');
            return true;
        }

        function submitQtyModal() {
            if (!validateQtyModalInput()) {
                showToast('warning', 'Periksa kembali Qty Sisa yang diinput.');
                return;
            }

            const qtys = $('#qtyModalInput').val();
            const url = qtyModalIsEdit
                ? `${sisaSampleBaseUrl}/size/${qtyModalSizepk}/update-qty`
                : `${sisaSampleBaseUrl}/size/${qtyModalSizepk}/add`;

            $('#qtyModalSubmitBtn').prop('disabled', true);

            $.ajax({
                url: url,
                method: 'POST',
                data: { qtys: qtys },
                success: function (res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('qtyModal')).hide();
                    loadSizes();
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    showToast(res.icon, res.title);
                },
                complete: function () {
                    $('#qtyModalSubmitBtn').prop('disabled', false);
                }
            });
        }

        function deleteSize(sizepk) {
            openConfirmModal({
                title: 'Hapus Size',
                message: 'Hapus size ini dari Gudang LO?',
                subtext: 'Data akan hilang dan size akan kembali bisa ditambahkan ulang.',
                confirmLabel: 'Ya, Hapus',
                confirmIcon: 'fas fa-trash',
                confirmBtnClass: 'btn-dark',
                onConfirm: function () {
                    $.ajax({
                        url: `${sisaSampleBaseUrl}/size/${sizepk}`,
                        method: 'DELETE',
                        success: function (res) {
                            showToast(res.icon, res.title);
                            loadSizes();
                        },
                        error: function (xhr) {
                            const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                            showToast(res.icon, res.title);
                        }
                    });
                }
            });
        }

        $(function () {
            loadSizes();
            loadKeluarList();
        });
    </script>
    {{-- TAMBAHKAN script ini di dalam @section('js_custom') detail.blade.php,
     dan panggil loadKeluarList() juga di $(function(){...}) yang sudah ada
     (sejajar loadSizes()). --}}

    <script>
        // ============================================================
        // ROLE APPROVER -- ISI SESUAI GUSERPK di sistem kamu.
        // ============================================================
        const keluarGuserpk = @json(session('guserpk'));
        const KELUAR_APPROVER_LEVEL1 = [/* TODO */];
        const KELUAR_APPROVER_LEVEL2 = [/* TODO */];
        const KELUAR_APPROVER_LEVEL3 = [/* TODO */];

        function loadKeluarList() {
            $.get(`${sisaSampleBaseUrl}/keluar/list/${statuspk}`, function (data) {
                renderKeluarCards(data.rows || []);
            });
        }

        function keluarStatusPillClass(label) {
            if (label.includes('Ditolak')) return 'bg-danger-subtle text-danger';
            if (label.includes('Selesai')) return 'bg-success-subtle text-success';
            return 'bg-warning-subtle text-warning-emphasis';
        }

        function renderKeluarCards(rows) {
            const grid = $('#keluarCardsGrid');
            grid.empty();
        
            if (!rows.length) {
                grid.html('<div class="col-12 text-center text-muted py-4">Belum ada riwayat barang keluar.</div>');
                return;
            }
        
            rows.forEach(function (out) {
                const label = out.status_label || '';
                let ribbonCls = 'grade-b', pillCls = 'st-menunggu';
                if (label.includes('Ditolak')) { ribbonCls = 'grade-c'; pillCls = 'st-ditolak'; }
                else if (label.includes('Selesai')) { ribbonCls = 'grade-a'; pillCls = 'st-selesai'; }
        
                const tglLabel = out.tglout ? out.tglout.split(' ')[0].split('-').reverse().join('/') : '-';
                const sizesHtml = buildSizeGridFromArray(out.sizes);
                const allApproved = out.stsapv1 === 1 && out.staapv2 === 1 && out.stsapv3 === 1;
        
                // BARU -- baris tombol aksi cepat di kartu.
                let quickActions = '';
                if (out.can_edit) {
                    quickActions += `<button type="button" class="doc-btn-outline" style="width:auto;padding:5px 10px;font-size:11px;" onclick="event.stopPropagation(); openEditKeluarModal(${out.outpk})"><i class="fas fa-pen me-1"></i>Edit</button>`;
                }
                if (!allApproved) {
                    quickActions += `<button type="button" class="doc-btn-outline" style="width:auto;padding:5px 10px;font-size:11px;margin-left:6px;" onclick="event.stopPropagation(); sendKeluarEmailAction(${out.outpk})"><i class="fas fa-paper-plane me-1"></i>Email</button>`;
                }
        
                grid.append(`
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="doc-card" style="cursor:pointer;" onclick="openKeluarDetail(${out.outpk})">
                            <div class="doc-card-grade-ribbon ${ribbonCls}">${out.no_out ? out.no_out.split('/')[0] : '#'+out.outpk}</div>
                            <div class="doc-card-header">
                                <div>
                                    <div class="doc-card-date"><i class="fas fa-calendar-day text-muted" style="font-size:11px;"></i> ${tglLabel}</div>
                                    <div class="doc-card-line">${out.no_out || ('#'+out.outpk)} &middot; ${out.jumlah_item} baris</div>
                                </div>
                            </div>
                            <div class="doc-card-perforation"></div>
                            <div class="doc-card-body">
                                <div class="doc-size-grid">${sizesHtml}</div>
                                <div class="doc-card-total-row">
                                    <span class="doc-card-total-label">Total Pcs</span>
                                    <span class="doc-card-total-value">${out.pcs ?? 0}</span>
                                </div>
                            </div>
                            <div class="doc-card-footer">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="doc-status-pill ${pillCls}">${label}</span>
                                </div>
                                ${out.penerima ? `<div style="font-size:11px;color:#64748b;">Penerima: <strong>${out.penerima}</strong></div>` : ''}
                                ${out.keterangan ? `<div style="font-size:11px;color:#64748b;margin-top:2px;"><i class="fas fa-note-sticky me-1"></i>${out.keterangan}</div>` : ''}
                                ${quickActions ? `<div class="mt-2" onclick="event.stopPropagation();">${quickActions}</div>` : ''}
                            </div>
                        </div>
                    </div>
                `);
            });
        }
        
        // kirim email approval.
        function sendKeluarEmailAction(outpk) {
            $.ajax({
                url: `${sisaSampleBaseUrl}/keluar/${outpk}/send-email`,
                method: 'POST',
                success: function (res) { showToast(res.icon, res.title); },
                error: function (xhr) {
                    const res = xhr.responseJSON || { icon: 'error', title: 'Gagal mengirim email.' };
                    showToast(res.icon, res.title);
                }
            });
        }

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
        // MODAL: KELUARKAN BARANG
        // ============================================================
        let keluarAvailableSizesCache = []; // cache hasil fetch, dipakai render ulang
        let keluarCart = {}; // { sizepk: {sizepk, size, cw, qty, remaining} }
        let keluarEditOutpk = null; // null = create, angka = edit
 
        function openKeluarCreateModal() {
            keluarEditOutpk = null;
            keluarCart = {};
            $('#keluarPenerima').val('');
            $('#keluarKeterangan').val('');
            $('#btnSubmitKeluar').html('<i class="fas fa-truck-ramp-box me-1"></i> Keluarkan');
            renderKeluarCart();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('keluarCreateModal')).show();
            $.get(`${sisaSampleBaseUrl}/keluar/available-sizes/${statuspk}`, function (data) {
                keluarAvailableSizesCache = data.rows || [];
                renderKeluarSizeList();
            });
        }
        
        // buka modal yang SAMA dalam mode edit.
        function openEditKeluarModal(outpk) {
            keluarEditOutpk = outpk;
            keluarCart = {};
            $('#btnSubmitKeluar').html('<i class="fas fa-save me-1"></i> Simpan Perubahan');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('keluarCreateModal')).show();
        
            $.get(`${sisaSampleBaseUrl}/keluar/${outpk}`, function (data) {
                $('#keluarPenerima').val(data.out.penerima || '');
                $('#keluarKeterangan').val(data.out.keterangan || '');
                (data.lines || []).forEach(function (line) {
                    keluarCart[line.sizepk] = { sizepk: line.sizepk, size: line.size, cw: line.cw, qty: line.qty, remaining: line.qty };
                });
                renderKeluarCart();
        
                // exclude_outpk supaya sisa yang tampil di browse list SUDAH
                // dikembalikan penuh (tidak dipotong ganda oleh baris milik
                // data ini sendiri) -- SAMA fix dengan modul Keluarkan Sisa Produksi.
                $.get(`${sisaSampleBaseUrl}/keluar/available-sizes/${statuspk}`, { exclude_outpk: outpk }, function (res) {
                    keluarAvailableSizesCache = res.rows || [];
                    renderKeluarSizeList();
                });
            });
        }

        function renderKeluarSizeList() {
            const wrap = $('#keluarSizeList');
            wrap.empty();
        
            if (!keluarAvailableSizesCache.length) {
                $('#keluarSizeEmpty').removeClass('d-none');
                return;
            }
            $('#keluarSizeEmpty').addClass('d-none');
        
            keluarAvailableSizesCache.forEach(function (s) {
                const inCart = keluarCart[s.sizepk];
                const rowCls = inCart ? 'in-cart' : '';
                const defaultVal = inCart ? '' : s.remaining;
        
                wrap.append(`
                    <div class="keluar-size-row ${rowCls}" id="keluarSizeRow_${s.sizepk}">
                        <div class="keluar-size-info">
                            <div class="ks-main">${s.size} <span class="text-muted" style="font-weight:400;">&middot; CW# ${s.cw ?? '-'}</span></div>
                            <div class="ks-sub">Sisa: ${s.remaining} pcs</div>
                            ${inCart ? `<div class="ks-incart"><i class="fas fa-check-circle me-1"></i>Di keranjang: ${inCart.qty} pcs</div>` : ''}
                        </div>
                        <input type="number" class="form-control form-control-sm" style="width:80px;"
                            id="keluarQtyInput_${s.sizepk}" min="1" max="${s.remaining}"
                            placeholder="${inCart ? 'Tambah lagi' : ''}" value="${defaultVal}">
                        <button type="button" class="btn btn-sm btn-dark" onclick="addKeluarCartLine(${s.sizepk}, '${s.size}', '${s.cw ?? ''}', ${s.remaining})">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                `);
            });
        }

        function addKeluarCartLine(sizepk, size, cw, remaining) {
            const qtyToAdd = parseFloat($('#keluarQtyInput_' + sizepk).val());
            if (!qtyToAdd || qtyToAdd <= 0) {
                showToast('warning', 'Qty harus lebih dari 0.');
                return;
            }
        
            const alreadyInCart = keluarCart[sizepk] ? keluarCart[sizepk].qty : 0;
            const effectiveRemaining = remaining - alreadyInCart;
        
            if (qtyToAdd > effectiveRemaining) {
                showToast('warning', `Qty melebihi sisa (sisa efektif: ${effectiveRemaining} pcs).`);
                return;
            }
        
            if (keluarCart[sizepk]) {
                keluarCart[sizepk].qty += qtyToAdd;
            } else {
                keluarCart[sizepk] = { sizepk, size, cw, qty: qtyToAdd, remaining };
            }
        
            renderKeluarSizeList();
            renderKeluarCart();
            showToast('success', `${size} (${qtyToAdd} pcs) ditambahkan ke keranjang.`);
        }

        function submitKeluarCreate() {
            const lines = Object.values(keluarCart).map(l => ({ sizepk: l.sizepk, qty: l.qty }));
            if (!lines.length) {
                showToast('warning', 'Pilih minimal 1 size ke keranjang.');
                return;
            }
            $('#btnSubmitKeluar').prop('disabled', true);
        
            const isEdit = keluarEditOutpk !== null;
            const url = isEdit ? `${sisaSampleBaseUrl}/keluar/${keluarEditOutpk}` : "{{ route('sisa-sample.keluar.store') }}";
            const method = isEdit ? 'PUT' : 'POST';
        
            $.ajax({
                url: url,
                method: method,
                data: { statuspk: statuspk, penerima: $('#keluarPenerima').val(), keterangan: $('#keluarKeterangan').val(), lines: lines },
                success: function (res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('keluarCreateModal')).hide();
                    loadKeluarList();
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || { icon: 'error', title: 'Gagal menyimpan.' };
                    showToast(res.icon, res.title);
                },
                complete: function () { $('#btnSubmitKeluar').prop('disabled', false); }
            });
        }
 

        function renderKeluarCart() {
            const sizepks = Object.keys(keluarCart);
            $('#keluarCartCount').text(sizepks.length);
            const list = $('#keluarCartList');
            list.empty();
        
            if (!sizepks.length) {
                list.html('<div class="text-muted text-center py-4" style="font-size:12.5px;" id="keluarCartEmpty">Belum ada size dipilih.</div>');
                return;
            }
        
            sizepks.forEach(function (sizepk) {
                const item = keluarCart[sizepk];
                list.append(`
                    <div class="keluar-cart-row">
                        <div>
                            <strong>${item.size}</strong> ${item.cw ? '&middot; CW# ' + item.cw : ''}<br>
                            <span class="text-muted">${item.qty} pcs</span>
                        </div>
                        <i class="fas fa-times kc-remove" onclick="removeKeluarCartLine(${sizepk})"></i>
                    </div>
                `);
            });
        }
        
        // BARU -- hapus 1 size dari keranjang, kembalikan ke browse list.
        function removeKeluarCartLine(sizepk) {
            delete keluarCart[sizepk];
            renderKeluarSizeList();
            renderKeluarCart();
        }

        // ============================================================
        // MODAL: DETAIL KELUAR
        // ============================================================
        let currentKeluarOutpk = null;

        function openKeluarDetail(outpk) {
            currentKeluarOutpk = outpk;
            $.get(`${sisaSampleBaseUrl}/keluar/${outpk}`, function (data) {
                renderKeluarDetail(data.out, data.lines || []);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('keluarDetailModal')).show();
            });
        }

        function renderKeluarDetail(out, lines) {
            $('#keluarDetailNo').text(out.no_out || ('#' + out.outpk));
            $('#keluarDetailDate').text(out.tglout ? out.tglout.split(' ')[0].split('-').reverse().join('/') : '-');
            $('#keluarDetailPenerima').text(out.penerima || '-');
            $('#keluarDetailKeterangan').text(out.keterangan || '-');
        
            const stepLabels = { 1: 'Purchasing', 2: 'HRD', 3: 'HRD2' };
            const steps = [
                { lvl: 1, val: out.stsapv1 },
                { lvl: 2, val: out.staapv2 },
                { lvl: 3, val: out.stsapv3 },
            ].map(function (s) {
                let cls = 'bg-secondary-subtle text-secondary', icon = 'fa-circle';
                if (s.val === 1) { cls = 'bg-success-subtle text-success'; icon = 'fa-check-circle'; }
                else if (s.val === 0) { cls = 'bg-danger-subtle text-danger'; icon = 'fa-times-circle'; }
                return `<span class="badge ${cls}" style="font-size:11px;"><i class="fas ${icon} me-1"></i>${stepLabels[s.lvl]}</span>`;
            }).join('<i class="fas fa-arrow-right text-muted mx-1" style="font-size:10px;"></i>');
            $('#keluarDetailSteps').html(steps);
        
            const body = $('#keluarDetailBody');
            body.empty();
            const canDelete = out.can_edit === true;
            lines.forEach(function (l) {
                body.append(`
                    <tr>
                        <td>${l.size}</td>
                        <td>${l.cw ?? '-'}</td>
                        <td class="text-end fw-semibold">${l.qty}</td>
                        <td class="text-center">${canDelete ? `<i class="fas fa-trash text-danger" style="cursor:pointer;" onclick="removeKeluarLine(${out.outpk}, ${l.outdtpk})"></i>` : ''}</td>
                    </tr>
                `);
            });
        
            // BARU -- approve INDEPENDEN.
            let footer = '';
            let anyButtonShown = false;
            const approverMap = { 1: KELUAR_APPROVER_LEVEL1, 2: KELUAR_APPROVER_LEVEL2, 3: KELUAR_APPROVER_LEVEL3 };
            const vals = { 1: out.stsapv1, 2: out.staapv2, 3: out.stsapv3 };
        
            [1, 2, 3].forEach(function (lvl) {
                if (vals[lvl] === null && approverMap[lvl].includes(keluarGuserpk)) {
                    anyButtonShown = true;
                    footer += `
                        <div class="d-flex gap-1 mb-1 w-100">
                            <button class="btn btn-outline-danger btn-sm flex-fill" onclick="rejectKeluar(${out.outpk}, ${lvl})">
                                <i class="fas fa-times me-1"></i>Tolak (${stepLabels[lvl]})
                            </button>
                            <button class="btn btn-dark btn-sm flex-fill" onclick="approveKeluar(${out.outpk}, ${lvl})">
                                <i class="fas fa-check me-1"></i>Approve (${stepLabels[lvl]})
                            </button>
                        </div>
                    `;
                }
            });
        
            if (!anyButtonShown) {
                const allDone = vals[1] !== null && vals[2] !== null && vals[3] !== null;
                footer += allDone
                    ? `<span class="text-success fw-semibold" style="font-size:12.5px;"><i class="fas fa-check-circle me-1"></i>Semua level sudah diproses.</span>`
                    : `<span class="text-muted" style="font-size:12px;">Menunggu approver lain.</span>`;
            }
        
            if (out.can_edit) {
                footer += `<button class="btn btn-outline-secondary btn-sm" onclick="cancelKeluarFromDetail(${out.outpk})"><i class="fas fa-ban me-1"></i>Batalkan</button>`;
            }
        
            $('#keluarDetailFooter').html(footer);
        }

        function approveKeluar(outpk, level) {
            $.ajax({
                url: `${sisaSampleBaseUrl}/keluar/${outpk}/approve/${level}`, method: 'POST',
                success: function (res) { showToast(res.icon, res.title); openKeluarDetail(outpk); loadKeluarList(); },
                error: function (xhr) { const res = xhr.responseJSON || { icon: 'error', title: 'Gagal approve.' }; showToast(res.icon, res.title); }
            });
        }

        function rejectKeluar(outpk, level) {
            openConfirmModal({
                title: 'Tolak Approval',
                message: `Tolak data keluar #${outpk} di Level ${level}?`,
                subtext: 'Data tidak bisa dilanjutkan ke level berikutnya setelah ditolak.',
                confirmLabel: 'Ya, Tolak',
                confirmIcon: 'fas fa-times',
                confirmBtnClass: 'btn-danger',
                onConfirm: function () {
                    $.ajax({
                        url: `${sisaSampleBaseUrl}/keluar/${outpk}/reject/${level}`,
                        method: 'POST',
                        success: function (res) { showToast(res.icon, res.title); openKeluarDetail(outpk); loadKeluarList(); },
                        error: function (xhr) { const res = xhr.responseJSON || { icon: 'error', title: 'Gagal menolak.' }; showToast(res.icon, res.title); }
                    });
                }
            });
        }

        function cancelKeluarFromDetail(outpk) {
            openConfirmModal({
                title: 'Batalkan Data Keluar',
                message: `Batalkan data keluar #${outpk}?`,
                subtext: 'Semua sisa yang sudah dikeluarkan akan ter-unlock kembali.',
                confirmLabel: 'Ya, Batalkan',
                confirmIcon: 'fas fa-ban',
                confirmBtnClass: 'btn-dark',
                onConfirm: function () {
                    $.ajax({
                        url: `${sisaSampleBaseUrl}/keluar/${outpk}`,
                        method: 'DELETE',
                        success: function (res) {
                            showToast(res.icon, res.title);
                            bootstrap.Modal.getInstance(document.getElementById('keluarDetailModal'))?.hide();
                            loadKeluarList();
                        },
                        error: function (xhr) { const res = xhr.responseJSON || { icon: 'error', title: 'Gagal membatalkan.' }; showToast(res.icon, res.title); }
                    });
                }
            });
        }

        function removeKeluarLine(outpk, outdtpk) {
            openConfirmModal({
                title: 'Hapus Baris',
                message: 'Hapus baris size ini dari data keluar?',
                subtext: 'Sisa akan ter-unlock dan bisa dikeluarkan lagi setelah baris ini dihapus.',
                confirmLabel: 'Ya, Hapus',
                confirmIcon: 'fas fa-trash',
                confirmBtnClass: 'btn-dark',
                onConfirm: function () {
                    $.ajax({
                        url: `${sisaSampleBaseUrl}/keluar/${outpk}/item/${outdtpk}`,
                        method: 'DELETE',
                        success: function (res) { showToast(res.icon, res.title); openKeluarDetail(outpk); loadKeluarList(); },
                        error: function (xhr) { const res = xhr.responseJSON || { icon: 'error', title: 'Gagal menghapus.' }; showToast(res.icon, res.title); }
                    });
                }
            });
        }
 

        function openConfirmModal(opts) {
            $('#confirmActionTitle').html(`<i class="fas fa-triangle-exclamation" style="color:${opts.iconColor || '#dc2626'};"></i> ${opts.title || 'Konfirmasi'}`);
            $('#confirmActionMessage').text(opts.message || '');
            $('#confirmActionSubtext').text(opts.subtext || '');
            $('#confirmActionBtnLabel').text(opts.confirmLabel || 'Ya, Lanjutkan');
            $('#confirmActionBtnIcon').attr('class', 'small ' + (opts.confirmIcon || 'fas fa-check'));
        
            const $btn = $('#btnConfirmAction');
            $btn.attr('class', 'btn btn-sm px-4 d-inline-flex align-items-center gap-1 ' + (opts.confirmBtnClass || 'btn-dark'))
                .css({ 'font-size': '13px', 'border-radius': '6px', 'height': '33px' });
        
            $btn.off('click').on('click', function () {
                $btn.prop('disabled', true);
                bootstrap.Modal.getInstance(document.getElementById('confirmActionModal'))?.hide();
                if (typeof opts.onConfirm === 'function') opts.onConfirm();
                setTimeout(() => $btn.prop('disabled', false), 500);
            });
        
            bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).show();
        }
    </script>
@endsection