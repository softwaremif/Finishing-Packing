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