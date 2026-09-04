<div class="modal fade" id="inspectDocumentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px;">
            <div class="modal-header py-3 border-0">
                <h5 class="modal-title fw-bold" style="font-size:15px;">
                    <span class="rounded me-2" style="width:4px;height:16px;display:inline-block;background:#1e293b;"></span>
                    Buat Dokumen Inspect (AQL)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="row g-3">
                    {{-- ===================== KOLOM KIRI -- 1 CARD PENUH ===================== --}}
                    <div class="col-lg-7">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-header bg-white py-3 border-0">
                                <div class="fw-bold" style="font-size:13.5px;">
                                    <i class="fas fa-boxes-stacked me-1 text-primary"></i> Carton Sedang Inspect
                                </div>
                                <div class="text-muted" style="font-size:12px;">
                                    Pilih size/color dari tiap carton yang akan diambil sample-nya.
                                </div>
                            </div>
                            <div class="card-body pt-2" style="max-height:calc(100vh - 220px); overflow-y:auto;">
                                <div id="inspectCartonList"></div>
                                <div id="inspectCartonEmpty" class="text-center text-muted py-5 d-none">
                                    Tidak ada carton yang sedang Inspect untuk OP ini.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ===================== KOLOM KANAN -- 2 CARD BERTUMPUK ===================== --}}
                    <div class="col-lg-5">
                        <div class="d-flex flex-column gap-3" style="position:sticky; top:0;">

                            {{-- CARD ATAS -- Dokumen Inspect (list sample terpilih) --}}
                            <div class="card border-0 shadow-sm rounded-3">
                                <div class="card-header bg-white py-3 border-0">
                                    <div class="fw-bold" style="font-size:13px;">
                                        <i class="fas fa-file-lines me-1 text-primary"></i>
                                        Dokumen Inspect (<span id="inspecCartCount">0</span> baris)
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div id="inspecCartList" style="max-height:320px; overflow-y:auto;">
                                        <div class="text-muted text-center py-4" style="font-size:12.5px;" id="inspecCartEmpty">
                                            Belum ada sample dipilih.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- CARD BAWAH -- Total Pcs Sample s/d Simpan Dokumen --}}
                            <div class="card border-0 shadow-sm rounded-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:12.5px;">
                                        <span class="text-muted">Total Pcs Sample</span>
                                        <strong id="inspecTotalPcs">0</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3" style="font-size:12.5px;">
                                        <span class="text-muted">Jumlah Defect</span>
                                        <strong id="inspecDefectCount" class="text-danger">0</strong>
                                    </div>

                                    <label class="form-label fw-semibold" style="font-size:12px;">
                                        AQL / Batas Reject (jumlah defect maksimal yang masih ditoleransi)
                                    </label>
                                    <input type="number" step="1" min="0"
                                        class="form-control form-control-sm mb-3" id="inspecAql" placeholder="Contoh: 3"
                                        oninput="recomputeHasilDisplay()">

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold d-block" style="font-size:12px;">Hasil Inspect (Otomatis)</label>
                                        <div id="inspecHasilDisplay"
                                            class="alert alert-secondary py-2 px-3 mb-0 text-center fw-bold"
                                            style="font-size:13px;">
                                            Isi AQL &amp; tandai sample untuk melihat hasil
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-dark w-100 fw-semibold" style="border-radius:8px;"
                                        id="btnSubmitInspecDoc" onclick="submitInspecDocument()">
                                        <i class="fas fa-clipboard-check me-1"></i> Simpan Dokumen Inspect
                                    </button>
                                </div>
                            </div>

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

{{-- Modal defect picker TETAP TERPISAH (di luar modal utama), TIDAK
     berubah -- HANYA taruh di luar #inspectDocumentModal, JANGAN
     nested di dalamnya (nested modal Bootstrap tidak didukung dengan
     baik). --}}
<div class="modal fade" id="defectPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px;">
            <div class="modal-header py-3 border-0">
                <h5 class="modal-title fw-bold" style="font-size:15px;">Pilih Tipe Defect</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <div id="defectSubTabs" class="d-flex flex-wrap gap-2 mb-3"></div>
                <div id="defectListWrap" class="d-flex flex-wrap gap-2"></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-dark" onclick="confirmDefectPicker()">
                    <i class="fas fa-check me-1"></i> Simpan Pilihan
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* GANTI/TAMBAH di <style> modal-inspection.blade.php -- redesign
   garment-industry: kiri jadi "tiket carton" (reuse bahasa doc-card),
   kanan jadi "kartu sample QC" dengan stempel bundar PASS/DEFECT. */

    /* ===================== KIRI: Tiket Carton ===================== */
    .inspec-carton-row {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
        overflow: visible;
        margin-bottom: 14px;
        position: relative;
        transition: box-shadow .15s ease, border-color .15s ease;
    }

    .inspec-carton-row:hover {
        box-shadow: 0 4px 10px rgba(15, 23, 42, .1);
        border-color: #cbd5e1;
    }

    .inspec-badge-soft {
        font-size: 10px;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 999px;
        border: 1px solid transparent;
        margin-left: 6px;
        vertical-align: middle;
    }
    .inspec-badge-soft.solid { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }
    .inspec-badge-soft.assorted { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
 
    .inspec-badge-soft.mixed {
        background: linear-gradient(90deg, #f97316, #64748b, #8b5cf6);
        color: #f1f5f9;
        border-color: #fde68a;
    }

    .inspec-carton-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px 10px;
        cursor: pointer;
    }

    .inspec-carton-header:hover {
        background: #f8fafc;
    }

    .inspec-carton-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: #eef2f7;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }

    .inspec-carton-title {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
    }

    .inspec-carton-sub {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 1px;
    }

    /* Perforasi -- SAMA pola doc-card-perforation, kesan "sobekan tiket" */
    .inspec-carton-perf {
        position: relative;
        border-top: 1.5px dashed #d8dee6;
        margin: 0 14px;
    }

    .inspec-carton-perf::before,
    .inspec-carton-perf::after {
        content: '';
        position: absolute;
        top: -7px;
        width: 14px;
        height: 14px;
        background: #f8fafc;
        border-radius: 50%;
        border: 1px solid #e2e8f0;
    }

    .inspec-carton-perf::before {
        left: -21px;
    }

    .inspec-carton-perf::after {
        right: -21px;
    }

    .inspec-size-detail {
        display: none;
        padding: 12px 14px 14px;
    }

    .inspec-size-detail.is-open {
        display: block;
    }

    /* Grid size ala "kantong sample" -- reuse rasa polybag chip */
    .inspec-size-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 10px;
    }

    .inspec-size-pill {
        background: linear-gradient(160deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 8px 8px;
        text-align: center;
        transition: border-color .15s ease;
    }

    .inspec-size-pill.is-empty {
        opacity: .4;
        filter: grayscale(.5);
    }

    .inspec-size-pill .isp-label {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
    }

    .inspec-size-pill .isp-combo {
        font-size: 9.5px;
        color: #94a3b8;
        margin-top: 1px;
        min-height: 12px;
    }

    .inspec-size-pill .isp-remaining {
        font-size: 10px;
        font-weight: 700;
        color: #0369a1;
        background: #e0f2fe;
        border-radius: 999px;
        padding: 2px 8px;
        display: inline-block;
        margin: 6px 0;
    }

    .inspec-size-pill .isp-add-btn {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #1e293b;
        color: #fff;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform .12s ease;
    }

    .inspec-size-pill .isp-add-btn:hover {
        transform: scale(1.1);
    }

    .inspec-size-pill .isp-add-btn:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
    }


    /* ===================== KANAN: Kartu Sample QC ===================== */
    .inspec-cart-row {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 10px;
        position: relative;
        overflow: hidden;
    }

    .inspec-cart-row .icr-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .inspec-cart-row .icr-title {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
    }

    .inspec-cart-row .icr-sub {
        font-size: 11px;
        color: #64748b;
        margin-top: 1px;
    }

    .inspec-cart-row .ic-remove {
        color: #cbd5e1;
        cursor: pointer;
        font-size: 13px;
    }

    .inspec-cart-row .ic-remove:hover {
        color: #dc2626;
    }

    /* Stempel bundar PASS/DEFECT -- REUSE bahasa .ship-stamp yang sudah
   ada di packing-input-global.blade.php (garis border tebal berwarna,
   teks miring, kesan cap tinta QC beneran). */
    .qc-stamp-row {
        display: flex;
        gap: 10px;
        margin-top: 10px;
        justify-content: center;
    }

    .qc-stamp {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 62px;
        height: 62px;
        border-radius: 50%;
        border: 3px solid #cbd5e1;
        background: #fff;
        cursor: pointer;
        transform: rotate(-8deg);
        transition: all .15s ease;
        opacity: .5;
    }

    .qc-stamp:hover {
        opacity: .85;
    }

    .qc-stamp-text {
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #94a3b8;
    }

    .qc-stamp i {
        font-size: 15px;
        color: #94a3b8;
        margin-bottom: 1px;
    }

    .qc-stamp.pass.active {
        opacity: 1;
        border-color: #16a34a;
        box-shadow: 0 2px 8px rgba(22, 163, 74, .25);
    }

    .qc-stamp.pass.active .qc-stamp-text,
    .qc-stamp.pass.active i {
        color: #16a34a;
    }

    .qc-stamp.defect.active {
        opacity: 1;
        border-color: #dc2626;
        box-shadow: 0 2px 8px rgba(220, 38, 38, .25);
    }

    .qc-stamp.defect.active .qc-stamp-text,
    .qc-stamp.defect.active i {
        color: #dc2626;
    }

    .cart-line-defect-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 8px;
        justify-content: center;
    }

    .cart-line-defect-tags .tag {
        background: #fee2e2;
        color: #991b1b;
        font-size: 10px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 999px;
    }

    .cart-line-defect-tags .tag.muted {
        background: #f1f5f9;
        color: #94a3b8;
        font-weight: 500;
    }


    /* ===================== Defect Picker (tab kategori + chip) ===================== */
    .defect-sub-tab {
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        background: #f1f5f9;
        color: #64748b;
        cursor: pointer;
        border: 1px solid #e2e8f0;
        transition: all .15s ease;
    }

    .defect-sub-tab.active {
        background: #1e293b;
        color: #fff;
        border-color: #1e293b;
    }

    .defect-chip {
        padding: 8px 14px;
        border-radius: 10px;
        font-size: 12.5px;
        cursor: pointer;
        background: #fff;
        border: 1.5px solid #e2e8f0;
        color: #334155;
        transition: all .15s ease;
    }

    .defect-chip:hover {
        border-color: #cbd5e1;
    }

    .defect-chip.selected {
        background: #fef2f2;
        border-color: #dc2626;
        color: #991b1b;
        font-weight: 700;
    }

    .defect-chip.selected::before {
        content: '\2713\0020';
    }
</style>
