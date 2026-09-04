{{-- menu/inspection/modal-kembalikan-stuffing-global.blade.php --}}
{{-- GANTI SELURUH modal-body -- TAMBAH pilihan Hasil Inspect: OK / Reject. --}}

<div class="modal fade" id="kembalikanStuffingModal" tabindex="-1" aria-labelledby="kembalikanStuffingModalTitle"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="kembalikanStuffingModalTitle" style="font-size:16px;">
                    <i class="fas fa-rotate-left" style="color:#f59e0b;"></i>
                    Kembalikan ke FinishGood
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-2">
                <div class="text-muted mb-3" style="font-size:12.5px; line-height:1.5;">
                    <i class="fas fa-info-circle me-0.5"></i>
                    <span id="kembalikanInfoText"></span>
                </div>
 
                {{-- BARU -- FIX UTAMA: tampilan hasil dokumen inspect
                     SESUNGGUHNYA (read-only), BUKAN pilihan manual lagi. --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12.5px; color:#374151;">
                        Hasil Inspect (sesuai Dokumen)
                    </label>
                    <div id="kembalikanHasilDisplay"
                        class="alert py-2 px-3 mb-0 text-center fw-bold"
                        style="font-size:13px;">
                        Memuat data dokumen...
                    </div>
                </div>
 
                <div id="kembalikanWarningBoxOk" class="d-none" style="font-size:12px; background:#fef3c7; border:1px solid #fde68a; border-radius:8px; padding:10px 12px; margin-bottom:8px; color:#92400e;">
                    <i class="fas fa-triangle-exclamation me-1"></i>
                    Carton akan ditandai <strong>"Menunggu Diterima"</strong>. Belum benar-benar
                    kembali ke FinishGood sampai tim FG/Stuffing mengonfirmasi penerimaan. Setelah diterima,
                    carton bisa langsung di-Seal kembali (karena hasil inspect Lulus).
                </div>
                <div id="kembalikanWarningBoxReject" class="d-none" style="font-size:12px; background:#fee2e2; border:1px solid #fecaca; border-radius:8px; padding:10px 12px; margin-bottom:8px; color:#991b1b;">
                    <i class="fas fa-triangle-exclamation me-1"></i>
                    Carton akan ditandai <strong>Reject</strong> dan <strong>"Menunggu Diterima"</strong>.
                    Karena hasil AQL Reject, <strong>SEMUA carton dalam session/part yang sama</strong>
                    akan ikut di-reject &amp; dibuka segelnya saat diterima FG/Stuffing. Perlu diperbaiki/rework
                    dulu sebelum Segel ulang.
                </div>
                <div id="kembalikanWarningBoxMissing" class="d-none" style="font-size:12px; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; margin-bottom:8px; color:#475569;">
                    <i class="fas fa-circle-exclamation me-1"></i>
                    Carton ini belum punya Dokumen Inspect. Buat Dokumen Inspect (AQL) dulu sebelum bisa dikembalikan.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal"
                    style="font-size:13px; border-radius:6px; height:33px;">
                    Batal
                </button>
                <button type="button" id="btnConfirmKembalikanStuffing" class="btn btn-dark btn-sm px-4 d-inline-flex align-items-center gap-1" style="font-size:13px; border-radius:6px; height:33px;" onclick="submitKembalikanStuffing()">
                    <i class="fas fa-rotate-left small"></i>
                    <span>Ya, Kembalikan</span>
                </button>
            </div>
        </div>
    </div>
</div>