{{-- TAMBAHKAN sebelum @endsection di section content, sejajar dengan
     modal-modal lain yang sudah di-include. --}}

<div class="modal fade" id="endInspecDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px;">
            <div class="modal-header py-3 border-0">
                <h5 class="modal-title fw-bold" style="font-size:15px;">
                    <i class="fas fa-flag-checkered me-2 text-success"></i>Selesaikan Dokumen Inspect
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <p style="font-size:13px; color:#334155; margin-bottom:8px;">
                    Semua carton pada dokumen ini sudah kembali ke FinishGood. Yakin ingin menandai dokumen ini
                    sebagai <strong>selesai (End)</strong>?
                </p>
                <div class="alert alert-warning py-2 px-3 mb-0" style="font-size:12px;">
                    <i class="fas fa-triangle-exclamation me-1"></i>
                    Setelah di-End, dokumen ini <strong>tidak bisa diedit lagi</strong>, dan carton-carton di
                    dalamnya akan <strong>bebas didokumentasikan ulang</strong> di dokumen baru kalau nanti masuk
                    Inspect lagi. Tindakan ini tidak bisa dibatalkan.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btnConfirmEndInspec" onclick="confirmEndInspecDocument()">
                    <i class="fas fa-check me-1"></i> Ya, Selesaikan
                </button>
            </div>
        </div>
    </div>
</div>