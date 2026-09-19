<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            {{-- HEADER --}}
            <div class="modal-header border-0">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            {{-- BODY --}}
            <div class="modal-body">
                <p class="mb-1 fw-semibold">
                    Apakah Anda yakin ingin menghapus data ini?
                </p>
                <small class="text-muted">
                    Data yang sudah dihapus tidak bisa dikembalikan.
                </small>
            </div>
            {{-- FOOTER --}}
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" id="btnConfirmDeleteTransfer" class="btn btn-dark" onclick="confirmDeleteTransfer()">
                    <i class="fas fa-trash-alt me-1"></i>
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>