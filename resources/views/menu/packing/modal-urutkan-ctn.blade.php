<div class="modal fade" id="urutkanCtnModal"
     tabindex="-1"
     aria-labelledby="urutkanCtnModalTitle"
     aria-hidden="true"
     data-bs-backdrop="static"
     data-bs-keyboard="false">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"
                    id="urutkanCtnModalTitle"
                    style="font-size:16px;">
                    Urutkan Nomor Carton
                </h5>

                <button type="button"
                        class="btn-close shadow-none"
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="mb-1">
                    <label class="form-label-custom">
                        Nomor CTN Awal
                    </label>

                    <input type="text" id="urutCtnAwal" class="form-control form-control-modern" placeholder="Contoh: CTN001 / A001 / 000001" required>
                </div>

                <div class="form-check">
                    {{-- <input
                        type="checkbox"
                        class="form-check-input"
                        id="format6Digit">

                    <label
                        class="form-check-label"
                        for="format6Digit">

                        Gunakan format nomor carton 6 digit
                    </label> --}}
                </div>

                <div
                    class="rounded bg-light border p-2 text-muted"
                    style="font-size:12px;">

                    <i class="fas fa-info-circle me-1"></i>

                    Nomor Carton akan diurutkan mulai dari nomor awal yang
                    dimasukkan. Barcode akan otomatis mengikuti nomor CTN
                    baru.

                </div>

            </div>

            <div class="modal-footer border-0 pt-0">

                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary px-3"
                    data-bs-dismiss="modal">

                    Cancel

                </button>

                <button
                    type="button"
                    id="btnSaveUrutCtn"
                    class="btn btn-sm btn-dark px-4"
                    onclick="saveUrutCtn()">

                    <i class="fas fa-sort-numeric-down me-1"></i>
                    Urutkan

                </button>

            </div>

        </div>
    </div>
</div>