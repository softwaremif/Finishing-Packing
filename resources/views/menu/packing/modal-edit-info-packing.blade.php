<div class="modal fade" id="editPackingModal" tabindex="-1" aria-labelledby="editPackingModalTitle" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            
            <form id="editPackingForm">
                @csrf
                <input type="hidden" name="popk" id="edit_popk" value="{{ $popk }}">
                <input type="hidden" name="cr" id="edit_cr" value="{{ $cr }}">

                <div class="modal-header py-3 bg-white border-bottom border-light">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="editPackingModalTitle" style="font-size: 15px;">
                        <span class="rounded me-2" style="width: 4px; height: 16px; display: inline-block; background: #475569;"></span>
                        Perbarui Informasi PO
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body py-3 py-sm-4 px-3 px-sm-4" style="background-color: #f8fafc;">
                    
                    <div class="p-3 mb-3 bg-white rounded-3 border d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2" style="border-color: #e2e8f0 !important;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-2 d-flex align-items-center justify-content-center text-secondary" style="width: 32px; height: 32px; background-color: #f1f5f9;">
                                <i class="fas fa-layer-group" style="font-size: 13px;"></i>
                            </div>
                            <div>
                                <span class="text-muted text-uppercase fw-semibold d-block mb-0.5" style="font-size: 9px; letter-spacing: 0.5px;">Order Production (OP)</span>
                                <span class="text-dark fw-bold " style="font-size: 14px;">{{ $dt2->OP ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="text-start text-sm-end d-flex flex-row-reverse flex-sm-row align-items-center gap-2 justify-content-end">
                            <div>
                                <span class="text-muted text-uppercase fw-semibold d-block mb-0.5" style="font-size: 9px; letter-spacing: 0.5px;">Buyer / Style</span>
                                <span class="text-secondary small fw-medium">{{ $dt2->buyer ?? '-' }} / {{ $dt2->style ?? '-' }}</span>
                            </div>
                            <div class="rounded-2 d-flex align-items-center justify-content-center text-secondary" style="width: 32px; height: 32px; background-color: #f1f5f9;">
                                <i class="fas fa-tshirt" style="font-size: 13px;"></i>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label-custom">
                                <i class="fas fa-hashtag text-secondary me-1" style="width: 14px;"></i> PO Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="pono" id="edit_pono" class="form-control form-control-modern " 
                                value="{{ $dt2->POno ?? '' }}" required placeholder="Masukkan nomor PO...">
                        </div>

                        <div class="col-12 col-sm-6">
                            <label class="form-label-custom">
                                <i class="fas fa-warehouse text-secondary me-1" style="width: 14px;"></i> Warehouse
                            </label>
                            <input type="text" name="wh" id="edit_wh" class="form-control form-control-modern" 
                                value="{{ $dt2->wh ?? '' }}" placeholder="Nama gudang penyimpanan...">
                        </div>

                        <div class="col-12 col-sm-6">
                            <label class="form-label-custom">
                                <i class="far fa-calendar-alt text-secondary me-1" style="width: 14px;"></i> Shipdate Plan
                            </label>
                            <input type="date" name="ship1" id="edit_ship1" class="form-control form-control-modern" 
                                value="{{ $dt2->shipdate1 ?? '' }}">
                        </div>

                        {{-- <div class="col-12 col-sm-6">
                            <label class="form-label-custom">
                                <i class="fas fa-calendar-check text-secondary me-1" style="width: 14px;"></i> Shipdate Actual
                            </label>
                            <input type="date" name="ship2" id="edit_ship2" class="form-control form-control-modern" 
                                value="{{ $dt2->shipdate2 ?? '' }}">
                        </div> --}}

                        <div class="col-12 col-sm-6">
                            <label class="form-label-custom">
                                <i class="fas fa-id-card text-secondary me-1" style="width: 14px;"></i> SAP ID
                            </label>
                            <input type="text" name="sap1" id="edit_sap1" class="form-control form-control-modern " 
                                value="{{ $dt2->sap1 ?? '' }}" placeholder="Contoh: 80012932">
                        </div>

                        <div class="col-12 col-sm-6">
                            <label class="form-label-custom">
                                <i class="fas fa-file-signature text-secondary me-1" style="width: 14px;"></i> SAP No
                            </label>
                            <input type="text" name="sap2" id="edit_sap2" class="form-control form-control-modern " 
                                value="{{ $dt2->sap2 ?? '' }}" placeholder="Contoh: 10002931">
                        </div>

                        <div class="col-12">
                            <label class="form-label-custom">
                                <i class="fas fa-comment-alt text-secondary me-1" style="width: 14px;"></i> Keterangan Catatan
                            </label>
                            <textarea name="ket" id="edit_ket" class="form-control form-control-modern" rows="3" 
                                placeholder="Tambahkan catatan internal logistik jika ada...">{{ $dt2->ket ?? '' }}</textarea>
                        </div>
                    </div>

                </div>

                <div class="modal-footer bg-white border-top border-light py-2.5 pe-3">
                    <button type="button" class="btn btn-sm btn-light border fw-semibold text-secondary px-3 py-1.5"
                        style="border-radius:6px; font-size:13px;" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm btn-dark fw-semibold px-4 py-1.5 shadow-sm"
                        style="background-color: #1e293b; border-color: #1e293b; border-radius:6px; font-size:13px; letter-spacing: 0.3px;">
                        <i class="fas fa-save me-1.5 small"></i> Save Data
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<style>
    .gap-2.5 { gap: 0.65rem !important; }

    .form-label-custom {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
        display: block;
    }

    .form-control-modern {
        border-color: #cbd5e1;
        border-radius: 6px;
        font-size: 13.5px;
        color: #1e293b;
        padding: 6px 12px;
        height: 36px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02) inset;
    }
    textarea.form-control-modern {
        height: auto !important;
    }

    .form-control-modern:focus {
        border-color: #64748b !important;
        box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.15) !important;
        outline: 0;
    }
</style>