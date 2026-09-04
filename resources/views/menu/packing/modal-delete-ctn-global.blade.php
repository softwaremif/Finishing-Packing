<div class="modal fade" id="bulkDeleteModalGlobal" tabindex="-1" aria-labelledby="bulkDeleteModalGlobalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="bulkDeleteModalGlobalTitle" style="font-size: 16px;">
                    Delete Carton (Global)
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pb-2">

                <div class="table-responsive border rounded bg-white mb-3 style-modal-scrollbar" style="max-height: 220px; border-color: #e2e8f0 !important;">
                    <table class="table table-sm table-hover text-center align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light text-secondary sticky-top" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">
                            <tr>
                                <th width="40" class="py-2">No</th>
                                <th class="py-2 font-monospace">Barcode</th>
                                <th class="py-2">No CTN</th>
                                <th class="py-2">Color / Sec Size</th>
                            </tr>
                        </thead>
                        <tbody id="bulkDeleteGlobalList" class="text-dark"></tbody>
                    </table>
                </div>

                <div class="d-flex align-items-start gap-2 p-2 rounded-2" style="background-color: #fef2f2; border: 1px solid #fee2e2;">
                     <i class="fas fa-exclamation-triangle text-danger mt-0.5" style="font-size: 13px;"></i>
                    <div class="text-danger" style="font-size: 12px; line-height: 1.5;">
                         Baris packing yang dihapus <strong>tidak dapat dikembalikan</strong>.
                     </div>
                </div>

            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal" style="font-size: 13px; border-radius: 6px; height: 33px;">
                    Cancel
                </button>
                <button type="button" id="btnConfirmBulkDeleteGlobal" class="btn btn-sm btn-dark px-4 d-inline-flex align-items-center gap-1" style="font-size: 13px; border-radius: 6px; height: 33px; background-color: #1e293b; border-color: #1e293b;" onclick="confirmBulkDeleteGlobal()">
                    <i class="fas fa-trash me-1"></i> Delete Carton
                </button>
            </div>

        </div>
    </div>
</div>

<style>
    .style-modal-scrollbar::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }
    .style-modal-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
</style>

<script>
    // ============================================================
    // GANTI bulkDeleteCtnGlobal() lama (yang langsung confirm() polos
    // tanpa preview) -- sekarang buka modal preview dulu, konsisten
    // dengan Input Actual / Delete Actual versi Global.
    // ============================================================
    function bulkDeleteCtnGlobal() {
        const packpks = window.selectedPackpksGlobal || [];
        if (!packpks.length) {
            showToast('warning', 'Pilih minimal satu carton.');
            return;
        }

        const rows = (window.lastPackingRows || []).filter(r => packpks.includes(r.packpk));

        let html = '';
        rows.forEach(function (row, index) {
            const materialLabel = getComboLabel(row);
            const secszTag = row.secsz ? ` (${row.secsz})` : '';
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${row.nobar ?? ''}</td>
                    <td><strong>${row.carton}</strong></td>
                    <td>${materialLabel}${secszTag}</td>
                </tr>
            `;
        });

        $('#bulkDeleteGlobalList').html(html);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkDeleteModalGlobal')).show();
    }

    function confirmBulkDeleteGlobal() {
        const packpks = window.selectedPackpksGlobal || [];
        if (!packpks.length) {
            showToast('warning', 'Pilih minimal satu carton.');
            return;
        }
    
        $.ajax({
            url: "{{ route('packing.delete-selected.global') }}",
            method: 'DELETE',
            data: {
                packpk: packpks.join(','),
                po: @json($po),
                op: @json($op),
                poref: @json($poref ?? null),
                mif: @json($mif)
            },
            beforeSend: function () {
                $('#btnConfirmBulkDeleteGlobal').prop('disabled', true);
            },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('bulkDeleteModalGlobal')).hide();
                loadPackingCards();
                reloadBreakdownSummary();
                reloadCardsInfoGlobal();
                refreshPgCombos();
                closeMenuGlobal();
            },
            error: function (xhr) {
                let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                showToast(res.icon, res.title);
            },
            complete: function () {
                $('#btnConfirmBulkDeleteGlobal').prop('disabled', false);
            }
        });
    }
</script>