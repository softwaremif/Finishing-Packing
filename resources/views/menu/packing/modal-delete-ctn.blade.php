<div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="bulkDeleteModalTitle" style="font-size: 16px;">
                    Delete Carton
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pb-2">

                <div class="table-responsive border rounded bg-white mb-3 style-modal-scrollbar" style="max-height: 220px; border-color: #e2e8f0 !important;">
                    <table class="table table-sm table-hover text-center align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light text-secondary sticky-top" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">
                            <tr>
                                <th width="60" class="py-2">No</th>
                                <th class="py-2 font-monospace">Barcode</th>
                                <th class="py-2">No CTN</th>
                            </tr>
                        </thead>
                        <tbody id="bulkDeleteList" class="text-dark"></tbody>
                    </table>
                </div>

                <div class="d-flex align-items-start gap-2 p-2 rounded-2" style="background-color: #fef2f2; border: 1px solid #fee2e2;">
                    <i class="fas fa-exclamation-triangle text-danger mt-0.5" style="font-size: 13px;"></i>
                    <div class="text-danger" style="font-size: 12px; line-height: 1.5;">
                        Carton yang dihapus <strong>tidak dapat dikembalikan</strong>. Nomor carton dan barcode yang tersisa akan diurutkan ulang secara otomatis.
                    </div>
                </div>

            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal" style="font-size: 13px; border-radius: 6px; height: 33px;">
                    Cancel
                </button>
                <button type="button" id="btnConfirmBulkDelete" class="btn btn-sm btn-dark px-4 d-inline-flex align-items-center gap-1" style="font-size: 13px; border-radius: 6px; height: 33px;" onclick="confirmBulkDelete()">
                    <i class="fas fa-trash me-1"></i> Delete Carton
                </button>
            </div>

        </div>
    </div>
</div>

<style>
    .gap-1.5 { gap: 0.35rem !important; }

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
        box-shadow: 0 1px 2px rgba(0,0,0,0.02) inset;
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