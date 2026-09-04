<div class="modal fade" id="createOutsisaModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px;">
            <div class="modal-header py-3 border-0">
                <h5 class="modal-title fw-bold" id="createOutsisaModalTitle" style="font-size:15px;">
                    <span class="rounded me-2" style="width:4px;height:16px;display:inline-block;background:#1e293b;"></span>
                    Add Barang Keluar dari Gudang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="row g-3">
                    {{-- KIRI: browse item yang sudah diterima gudang --}}
                    <div class="col-lg-8">
                        <div class="input-group mb-3">
                            <span class="input-group-text search">
                                <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18">
                            </span>
                            <input type="text" class="form-control search" id="outItemSearch" placeholder="Cari PO No / OP / Buyer / Color...">
                        </div>
                        <div id="outItemGroups" style="max-height:520px; overflow-y:auto;"></div>
                        <div id="outItemEmpty" class="text-center text-muted py-5 d-none">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            Tidak ada barang tersedia (belum ada yang diterima gudang, atau semua sudah keluar).
                        </div>
                    </div>

                    {{-- KANAN: keranjang keluar --}}
                    <div class="col-lg-4">
                        <div class="border rounded-3 p-3 bg-light-subtle" style="position:sticky; top:0;">
                            <div class="fw-bold mb-2" style="font-size:13px;">
                                <i class="fas fa-dolly me-1 text-primary"></i>
                                Keranjang Keluar (<span id="outCartCount">0</span> baris)
                            </div>
                            <div id="outCartList" style="max-height:280px; overflow-y:auto;">
                                <div class="text-muted text-center py-4" style="font-size:12.5px;" id="outCartEmpty">
                                    Belum ada barang dipilih.
                                </div>
                            </div>
                            <hr>
                            <label class="form-label fw-semibold" style="font-size:12px;">Penerima</label>
                            <input type="text" class="form-control form-control-sm mb-2" id="outPenerima" placeholder="Nama penerima...">
                            <label class="form-label fw-semibold" style="font-size:12px;">Keterangan (opsional)</label>
                            <textarea class="form-control form-control-sm mb-3" id="outKeterangan" rows="2"
                                placeholder="Catatan untuk barang keluar ini..."></textarea>
                            <button type="button" class="btn btn-dark w-100 fw-semibold" style="border-radius:8px;"
                                id="btnSubmitOutsisa" onclick="submitCreateOutsisa()">
                                <i class="fas fa-truck-ramp-box me-1"></i> Simpan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>