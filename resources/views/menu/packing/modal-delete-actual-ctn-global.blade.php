<div class="modal fade" id="bulkDeleteActualCtnGlobalModal" tabindex="-1" aria-labelledby="bulkDeleteActualCtnGlobalModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="bulkDeleteActualCtnGlobalModalTitle" style="font-size: 16px;">
                    Delete Actual Qty Carton (Global)
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
                        <tbody id="bulkDeleteActualGlobalList" class="text-dark"></tbody>
                    </table>
                </div>

                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4 mb-2">
                    <div class="col-md-7">
                        <label class="form-label-custom">
                            <i class="fas fa-tags text-secondary me-1" style="width: 14px;"></i> Pilih Distribusi Size
                        </label>
                        <select id="bulkDeleteActualSizeGlobal" name="size" class="form-select form-control-modern">
                            <option value="">Semua Size</option>
                            @foreach($activeSizes as $i => $sz)
                                <option value="{{ $i }}">{{ $sz }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="text-muted mt-1.5 ps-1" style="font-size: 11.5px; line-height: 1.4;">
                    <i class="fas fa-info-circle me-0.5"></i> Jika memilih <strong class="text-dark">"Semua Size"</strong>,
                    seluruh Qty Actual akan dihapus. Carton yang berasal dari <strong class="text-dark">Color/Sec Size
                    berbeda</strong> (Mixed) akan diproses masing-masing sesuai barisnya sendiri.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal" style="font-size: 13px; border-radius: 6px; height: 33px;">
                    Cancel
                </button>
                <button type="button" id="btnDeleteActualGlobal" class="btn btn-sm btn-dark px-4 d-inline-flex align-items-center gap-1" style="font-size: 13px; border-radius: 6px; height: 33px; background-color: #1e293b; border-color: #1e293b;" onclick="submitDeleteActualCtnGlobal()">
                    <i class="fas fa-trash me-1"></i> Delete Actual
                </button>
            </div>

        </div>
    </div>
</div>

<style>
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

<script>
    // ============================================================
    // bulkDeleteActualCtnGlobal() -- dipanggil dari sticky bar, buka
    // modal + isi daftar preview (barcode/CTN/Color-SecSize) dari
    // semua packpk yang terpilih (window.selectedPackpksGlobal).
    // ============================================================
    function bulkDeleteActualCtnGlobal() {
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

        $('#bulkDeleteActualGlobalList').html(html);
        $('#bulkDeleteActualSizeGlobal').val('');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkDeleteActualCtnGlobalModal')).show();
    }

    function submitDeleteActualCtnGlobal() {
        const packpks = window.selectedPackpksGlobal || [];
        if (!packpks.length) {
            showToast('warning', 'Pilih minimal satu carton.');
            return;
        }
    
        $.ajax({
            url: "{{ route('packing.delete-actual.global') }}",
            method: 'DELETE',
            data: {
                size: $('#bulkDeleteActualSizeGlobal').val(),
                packpk: packpks.join(',')
            },
            beforeSend: function () {
                $('#btnDeleteActualGlobal').prop('disabled', true);
            },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('bulkDeleteActualCtnGlobalModal')).hide();
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
                $('#btnDeleteActualGlobal').prop('disabled', false);
            }
        });
    }
</script>