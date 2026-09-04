<div class="modal fade" id="bulkActualCtnModal" tabindex="-1" aria-labelledby="bulkActualCtnModalTitle" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="bulkActualCtnModalTitle" style="font-size:16px;">
                    Input Actual Qty Carton
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <div class="modal-body pb-2">

                <div class="mb-2">
                    <label class="form-label-custom">
                        <i class="fas fa-tags text-secondary me-1" style="width:14px;"></i>
                        Pilih Distribusi Size
                    </label>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4 mb-2">
                        <div class="col-md-7">
                            <select id="bulkSizeSelect" class="form-select form-control-modern">
                        <option value="">Semua Size</option>
                        @foreach ($activeSizes as $i => $sz)
                            <option value="{{ $i }}">{{ $sz }}</option>
                        @endforeach
                    </select>
                        </div>
                    </div>
                    
                    <div class="text-muted mt-1" style="font-size:11.5px; line-height:1.4;">
                        <i class="fas fa-info-circle me-0.5"></i>
                        Jika memilih <strong class="text-dark">"Semua Size"</strong>, seluruh Qty Plan akan
                        disalin ke Actual — kecuali jika sisa Transfer tidak mencukupi (lihat status di bawah).
                    </div>
                </div>

                <div class="table-responsive border rounded bg-white mb-2 style-modal-scrollbar"
                    style="max-height: 260px; border-color:#e2e8f0 !important;">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px;">
                        <thead class="table-light text-secondary sticky-top"
                            style="font-size:11px; text-transform:uppercase; letter-spacing:0.3px;">
                            <tr>
                                <th width="40" class="py-2 text-center">No</th>
                                <th class="py-2 text-center">Barcode</th>
                                <th class="py-2 text-center">No CTN</th>
                                <th class="py-2 text-start">Status Alokasi</th>
                            </tr>
                        </thead>
                        <tbody id="bulkActualList" class="text-dark"></tbody>
                    </table>
                </div>

                <div id="bulkActualWarning" class="alert alert-warning py-2 px-3 d-none" style="font-size:12.5px;">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Beberapa carton tidak bisa terisi penuh / tidak bisa terisi sama sekali karena Transfer Qty
                    sudah habis. Carton tersebut akan diisi sebagian atau dilewati.
                </div>

            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal"
                    style="font-size:13px; border-radius:6px; height:33px;">
                    Cancel
                </button>
                <button type="button" id="btnUpdateActual" class="btn btn-sm btn-dark px-4 d-inline-flex align-items-center gap-1"
                    style="font-size:13px; border-radius:6px; height:33px; background-color:#1e293b; border-color:#1e293b;"
                    onclick="submitBulkActualCtn()">
                    <i class="fas fa-check-circle small"></i> Update Actual
                </button>
            </div>

        </div>
    </div>
</div>

<style>
    .gap-1.5 {
        gap: 0.35rem !important;
    }

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
        padding: 5px 12px;
        height: 36px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) inset;
    }

    .form-control-modern:focus {
        border-color: #64748b !important;
        box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.15) !important;
        outline: 0;
    }

    .style-modal-scrollbar::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }

    .style-modal-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
</style>