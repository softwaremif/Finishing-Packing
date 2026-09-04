{{-- menu/finishgood-stuffing/modal-terima-carton-global.blade.php --}}
{{-- Modal konfirmasi TAHAP 2 dari alur 2-tahap "Kembalikan ke Stuffing":
     Inspection user sudah "Kembalikan ke Stuffing" (fca=2) -- modal ini
     dipakai FG/Stuffing user untuk konfirmasi PENERIMAAN carton
     tersebut. Eksekusi AJAX-nya (confirmTerimaCartonGlobal) ada di
     script utama halaman input-global. --}}

<div class="modal fade" id="terimaCartonModal" tabindex="-1" aria-labelledby="terimaCartonModalTitle"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="terimaCartonModalTitle" style="font-size:16px;">
                    <i class="fas fa-box-open" style="color:#7c3aed;"></i>
                    Terima Carton dari Inspect
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-2">
                <div class="text-muted mb-2" style="font-size:12.5px; line-height:1.5;">
                    <i class="fas fa-info-circle me-0.5"></i>
                    <span id="terimaCartonInfoText"></span>
                </div>

                {{-- <div style="font-size:12px; background:#f3e8ff; border:1px solid #e9d5ff; border-radius:8px; padding:10px 12px; margin-bottom:8px; color:#6b21a8;">
                    <i class="fas fa-circle-check me-1"></i>
                    Carton akan ditandai <strong>diterima</strong> -- tanggal "Kembali" akan dicatat sekarang,
                    dan carton bisa diproses kembali di Stuffing/FinishGood.
                </div> --}}

                {{-- Warning KHUSUS -- muncul HANYA kalau ada carton berstatus
                     Reject di antara yang dipilih. --}}
                <div id="terimaCartonRejectWarning" class="d-none" style="font-size:12px; background:#fee2e2; border:1px solid #fecaca; border-radius:8px; padding:10px 12px; margin-bottom:8px; color:#991b1b;">
                    <i class="fas fa-triangle-exclamation me-1"></i>
                    <span id="terimaCartonRejectCount"></span> dari carton yang dipilih berstatus
                    <strong>Reject</strong>. Setelah diterima, carton itu TETAP TERKUNCI dari Seal
                    sampai di-rework (edit Actual Qty) dulu.
                </div>

                <div style="font-size:12.5px; color:#475569;">
                    <i class="fas fa-list-check me-0.5" style="color:#94a3b8;"></i>
                    <span id="terimaCartonListText"></span>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal"
                    style="font-size:13px; border-radius:6px; height:33px;">
                    Batal
                </button>
                <button type="button" id="btnConfirmTerimaCarton" class="btn btn-dark btn-sm px-4 d-inline-flex align-items-center gap-1"
                    style="font-size:13px; border-radius:6px; height:33px; color:#fff;"
                    onclick="confirmTerimaCartonGlobal()">
                    <i class="fas fa-box-open small"></i>
                    <span>Ya, Terima Carton</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function terimaCartonGlobal() {
            const packpks = window.selectedPackpksGlobal || [];
            if (!packpks.length) {
                showToast('warning', 'Pilih minimal satu carton.');
                return;
            }
        
            const selectedRows = packpks
                .map(pk => window.selectedRowsCache[pk])
                .filter(Boolean);
        
            const cartonCount = new Set(selectedRows.map(r => r.carton)).size;
            const rejectCount = new Set(
                selectedRows.filter(r => Number(r.reject) === 1).map(r => r.carton)
            ).size;
        
            $('#terimaCartonInfoText').html(
                `Anda akan menerima <strong>${cartonCount}</strong> carton (${packpks.length} baris) dari Inspect.`
            );
        
            if (rejectCount > 0) {
                $('#terimaCartonRejectCount').text(rejectCount);
                $('#terimaCartonRejectWarning').removeClass('d-none');
            } else {
                $('#terimaCartonRejectWarning').addClass('d-none');
            }
        
            const cartonNames = [...new Set(selectedRows.map(r => r.carton))];
            $('#terimaCartonListText').text(
                cartonNames.length > 6
                    ? cartonNames.slice(0, 6).join(', ') + `, +${cartonNames.length - 6} lainnya`
                    : cartonNames.join(', ')
            );
        
            bootstrap.Modal.getOrCreateInstance(document.getElementById('terimaCartonModal')).show();
        }
        
        // BARU -- eksekusi sebenarnya, dipanggil dari tombol konfirmasi di modal.
        function confirmTerimaCartonGlobal() {
            const packpks = window.selectedPackpksGlobal || [];
            if (!packpks.length) return;
        
            $('#btnConfirmTerimaCarton').prop('disabled', true);
        
            $.ajax({
                url: "{{ route('finish-good-stuffing.bulk-ship-action') }}",
                method: 'POST',
                data: { packpk: packpks.join(','), action: 'accept_return', mif: @json($mif) },
                success: function (res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('terimaCartonModal')).hide();
                    closeMenuGlobal();
                    loadPackingCards();
                    reloadBreakdownSummary();
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || { icon: 'error', title: 'Gagal menerima carton.' };
                    showToast(res.icon, res.title);
                },
                complete: function () {
                    $('#btnConfirmTerimaCarton').prop('disabled', false);
                }
            });
        }
</script>