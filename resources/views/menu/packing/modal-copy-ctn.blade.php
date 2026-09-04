<div class="modal fade" id="bulkCopyModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-dark" style="font-size:16px;">Copy Carton</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pb-2">
                <p class="mb-1 fw-semibold">
                    Apakah Anda yakin ingin menyalin data carton yang dipilih?
                </p>
                <p class="text-muted mb-3" style="font-size:13px;">
                    Sistem akan menduplikasi item-item di bawah ini ke dalam record baru.
                </p>
                <div class="table-responsive border rounded bg-white mb-3 style-modal-scrollbar" style="max-height:150px; border-color:#e2e8f0!important;">
                    <table class="table table-sm table-hover text-center align-middle mb-0" style="font-size:13px;">
                        <thead class="table-light text-secondary" style="font-size:11px; text-transform:uppercase;">
                            <tr>
                                <th width="40">No</th>
                                <th>Barcode</th>
                                <th>Carton</th>
                                <th class="text-start">Status Actual</th>
                            </tr>
                        </thead>
                        <tbody id="copyList"></tbody>
                    </table>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4 mb-2">
                    <div class="col-md-7">
                        <label class="form-label-custom">
                            <i class="fas fa-clone me-1"></i>
                            Jumlah Duplikasi per Carton
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="copyAmount" class="form-control form-control-modern" name="copy" min="1" value="1" required>
                    </div>
                </div>
                <input type="hidden" id="copyIds">
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" style="font-size:13px;border-radius:6px;">
                    Batal
                </button>

                <button type="button" id="btnProcessCopy" class="btn btn-dark px-4" style="font-size:13px;border-radius:6px;background:#1e293b;border-color:#1e293b;" onclick="processCopy()">
                    <i class="fas fa-copy me-1"></i>
                    Process Copy
                </button>
            </div>

        </div>
    </div>
</div>

<style>
.form-label-custom{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.5px;
    font-weight:600;
    color:#475569;
    margin-bottom:6px;
}

.form-control-modern{
    border-color:#cbd5e1;
    border-radius:6px;
    font-size:13px;
    height:36px;
}

.form-control-modern:focus{
    border-color:#64748b!important;
    box-shadow:0 0 0 3px rgba(100,116,139,.15)!important;
}

.style-modal-scrollbar::-webkit-scrollbar{
    width:4px;
}

.style-modal-scrollbar::-webkit-scrollbar-thumb{
    background:#cbd5e1;
    border-radius:4px;
}
</style>