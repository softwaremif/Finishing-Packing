<div class="modal fade" id="bulkSegelCtnGlobalModal" tabindex="-1" aria-labelledby="bulkSegelCtnGlobalModalTitle" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="bulkSegelCtnGlobalModalTitle" style="font-size:16px;">
                    Segel Carton (Global)
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <div class="modal-body pb-2">

                <div class="text-muted mb-2" id="bulkSegelGlobalInfoText" style="font-size:12.5px; line-height:1.4;">
                    <i class="fas fa-info-circle me-0.5"></i>
                    <strong id="bulkSegelGlobalCount" class="text-dark">0</strong> carton berikut akan disegel.
                    Carton yang sudah disegel <strong class="text-dark">tidak dapat diedit lagi</strong>.
                </div>

                <div class="table-responsive border rounded bg-white mb-2 style-modal-scrollbar"
                    style="max-height: 260px; border-color:#e2e8f0 !important;">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px;">
                        <thead class="table-light text-secondary sticky-top"
                            style="font-size:11px; text-transform:uppercase; letter-spacing:0.3px;">
                            <tr>
                                <th width="40" class="py-2 text-center">No</th>
                                <th class="py-2 text-center">Barcode</th>
                                <th class="py-2 text-center">No CTN</th>
                                <th class="py-2 text-center">Color / Sec Size</th>
                                <th class="py-2 text-end">Total Pcs</th>
                            </tr>
                        </thead>
                        <tbody id="bulkSegelGlobalList" class="text-dark"></tbody>
                    </table>
                </div>

            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal"
                    style="font-size:13px; border-radius:6px; height:33px;">
                    Cancel
                </button>
                <button type="button" id="btnSubmitSegelCtnGlobal" class="btn btn-sm btn-dark px-4 d-inline-flex align-items-center gap-1"
                    style="font-size:13px; border-radius:6px; height:33px; background-color:#1e293b; border-color:#1e293b;"
                    onclick="submitBulkSegelCtnGlobal()">
                    <i class="fas fa-lock small"></i> Segel Sekarang
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
    let bulkSegelGlobalTarget = 1; 
    let segelGlobalTargetPackpks = []; 
 
    function openSegelModalGlobal(target, packpksOverride) {
        const packpks = packpksOverride && packpksOverride.length
            ? packpksOverride
            : (window.selectedPackpksGlobal || []);
    
        if (!packpks.length) {
            showToast('warning', 'Pilih minimal satu carton.');
            return;
        }
    
        const selectedRows = (window.lastPackingRows || []).filter(r => packpks.includes(r.packpk));
    
        if (target === 1) {
            const belumLengkap = selectedRows.filter(r => !isRowComplete(r));
            if (belumLengkap.length > 0) {
                const daftarCarton = [...new Set(belumLengkap.map(r => r.carton))].join(', ');
                showToast('warning', `Carton berikut belum lengkap Actual-nya, tidak bisa disegel: ${daftarCarton}`);
                return;
            }
        }
    
        bulkSegelGlobalTarget   = target;
        segelGlobalTargetPackpks = packpks; // dipakai submitBulkSegelCtnGlobal()
    
        const cartonGroups = {};
        const cartonOrder = [];
        selectedRows.forEach(function (row) {
            const key = row.carton ?? '(tanpa carton)';
            if (!cartonGroups[key]) {
                cartonGroups[key] = [];
                cartonOrder.push(key);
            }
            cartonGroups[key].push(row);
        });
    
        if (target === 1) {
            $('#bulkSegelCtnGlobalModalTitle').text('Segel Carton (Global)');
            $('#bulkSegelGlobalInfoText').html(`
                <i class="fas fa-info-circle me-0.5"></i>
                <strong id="bulkSegelGlobalCount" class="text-dark">${cartonOrder.length}</strong> carton berikut akan disegel.
                Carton yang sudah disegel dan di Shipment <strong class="text-dark">tidak dapat diedit lagi</strong>.
            `);
            $('#btnSubmitSegelCtnGlobal').html('<i class="fas fa-lock small"></i> Segel Sekarang');
        } else {
            $('#bulkSegelCtnGlobalModalTitle').text('Buka Segel Carton (Global)');
            $('#bulkSegelGlobalInfoText').html(`
                <i class="fas fa-info-circle me-0.5"></i>
                <strong id="bulkSegelGlobalCount" class="text-dark">${cartonOrder.length}</strong> carton berikut akan dibuka segelnya
                dan bisa diedit kembali.
            `);
            $('#btnSubmitSegelCtnGlobal').html('<i class="fas fa-unlock small"></i> Buka Segel Sekarang');
        }
    
        let html = '';
        cartonOrder.forEach(function (cartonKey, index) {
            const groupRows = cartonGroups[cartonKey];
            const first = groupRows[0];
    
            const combos = [...new Set(groupRows.map(function (r) {
                const materialLabel = getComboLabel(r);
                const secszTag = r.secsz ? ` (${r.secsz})` : '';
                return `${materialLabel}${secszTag}`;
            }))].join(', ');
    
            const totalPcs = groupRows.reduce((sum, r) => sum + Number(r.pcs ?? 0), 0);
    
            html += `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td class="text-center">${first.nobar ?? ''}</td>
                    <td class="text-center"><strong>${first.carton}</strong></td>
                    <td class="text-center">${combos}</td>
                    <td class="text-end">${totalPcs}</td>
                </tr>
            `;
        });
        $('#bulkSegelGlobalList').html(html);
    
        bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkSegelCtnGlobalModal')).show();
    }
    
    function submitBulkSegelCtnGlobal() {
        const packpks = segelGlobalTargetPackpks || [];
        if (!packpks.length) {
            showToast('warning', 'Pilih minimal satu carton.');
            return;
        }
    
        $.ajax({
            url: "{{ route('packing.update-segel.global') }}",
            method: 'POST',
            data: {
                packpk: packpks.join(','),
                target: bulkSegelGlobalTarget,
                mif: @json($mif)
            },
            beforeSend: function () {
                $('#btnSubmitSegelCtnGlobal').prop('disabled', true);
            },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('bulkSegelCtnGlobalModal')).hide();
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
                $('#btnSubmitSegelCtnGlobal').prop('disabled', false);
            }
        });
    }
</script>