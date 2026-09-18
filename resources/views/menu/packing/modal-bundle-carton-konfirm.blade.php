{{-- Modal konfirmasi "Bubarkan Carton Besar" -- muncul kalau user EDIT
     Bundle lalu uncheck SEMUA carton kecil (packpks kosong). Taruh di
     luar #bundleCartonModal (sejajar, JANGAN di-nested). --}}
<div class="modal fade" id="bcmDissolveConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
            <div class="modal-body text-center pt-4 pb-3">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                        style="width:52px;height:52px;background:#fef3c7;">
                        <i class="fas fa-triangle-exclamation" style="font-size:20px;color:#b45309;"></i>
                    </span>
                </div>
                <h5 class="fw-bold mb-2" style="font-size:15px;">Bubarkan Carton Besar?</h5>
                <div class="text-secondary" style="font-size:12.5px; line-height:1.5;">
                    Semua carton kecil di-uncheck. Menyimpan ini akan <strong>membubarkan</strong>
                    Carton Besar <strong id="bcmDissolveTargetName">-</strong> -- seluruh carton
                    kecil di dalamnya akan kembali menjadi carton biasa.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="bcmBtnConfirmDissolve" onclick="bcmConfirmDissolve()">
                    <i class="fas fa-box-open me-1"></i> Ya, Bubarkan
                </button>
            </div>
        </div>
    </div>
</div>