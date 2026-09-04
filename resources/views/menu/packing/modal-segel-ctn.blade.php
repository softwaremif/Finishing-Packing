<div class="modal fade" id="bulkSegelCtnModal" tabindex="-1" aria-labelledby="bulkSegelCtnModalTitle" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="bulkSegelCtnModalTitle" style="font-size:16px;">
                    Segel Carton
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <div class="modal-body pb-2">

                <div class="text-muted mb-2" id="bulkSegelInfoText" style="font-size:12.5px; line-height:1.4;">
                    <i class="fas fa-info-circle me-0.5"></i>
                    <strong id="bulkSegelCount" class="text-dark">0</strong> carton berikut akan disegel.
                    Carton yang sudah disegel <strong class="text-dark">tidak dapat diedit lagi</strong>.
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
                                <th class="py-2 text-end">Total Pcs</th>
                            </tr>
                        </thead>
                        <tbody id="bulkSegelList" class="text-dark"></tbody>
                    </table>
                </div>

            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal"
                    style="font-size:13px; border-radius:6px; height:33px;">
                    Cancel
                </button>
                <button type="button" id="btnSubmitSegelCtn" class="btn btn-sm btn-dark px-4 d-inline-flex align-items-center gap-1"
                    style="font-size:13px; border-radius:6px; height:33px; background-color:#1e293b; border-color:#1e293b;"
                    onclick="submitBulkSegelCtn()">
                    <i class="fas fa-lock small"></i> Segel Sekarang
                </button>
            </div>

        </div>
    </div>
</div>