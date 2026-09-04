{{-- konfirmasi Proses Inspect / Proses Shipment --}}
<div class="modal fade" id="bulkShipActionModalGlobal" tabindex="-1" aria-labelledby="bulkShipActionModalGlobalTitle"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="bulkShipActionModalGlobalTitle" style="font-size:16px;">
                    Proses
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-2">
                <div class="text-muted mb-2" id="bulkShipActionInfoText" style="font-size:12.5px; line-height:1.4;">
                    <i class="fas fa-info-circle me-0.5"></i>
                    <strong id="bulkShipActionCount" class="text-dark">0</strong> carton berikut akan diproses.
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
                        <tbody id="bulkShipActionList" class="text-dark"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal"
                    style="font-size:13px; border-radius:6px; height:33px;">
                    Cancel
                </button>
                <button type="button" id="btnSubmitShipActionGlobal" class="btn btn-sm btn-dark px-4 d-inline-flex align-items-center gap-1"
                    style="font-size:13px; border-radius:6px; height:33px; background-color:#1e293b; border-color:#1e293b;"
                    onclick="submitShipActionGlobal()">
                    <i class="fas fa-check-circle small"></i>
                    <span id="btnSubmitShipActionGlobalText">Proses Sekarang</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let shipActionGlobalTarget = null;   // 'inspect' | 'shipment'
    let shipActionGlobalPackpks = [];

    // ============================================================
    // GANTI runShipBulkActionGlobal() -- SEBELUMNYA pakai confirm()
    // browser polos. SEKARANG buka modal preview dulu (SAMA pola
    // dengan openSegelModalGlobal()), submit sebenarnya terjadi di
    // submitShipActionGlobal() setelah user klik konfirmasi di modal.
    // ============================================================
    function runShipBulkActionGlobal(action, label) {
        const packpks = window.selectedPackpksGlobal || [];
        if (!packpks.length) {
            showToast('warning', 'Pilih minimal satu carton.');
            return;
        }

        shipActionGlobalTarget  = action;
        shipActionGlobalPackpks = packpks;

        const selectedRows = (window.lastPackingRows || []).filter(r => packpks.includes(r.packpk));

        // Group per carton fisik -- SAMA pola dengan modal Segel.
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

        // Warna & teks tombol disesuaikan per aksi.
        const isInspect = action === 'inspect';
        const accentColor = isInspect ? '#f59e0b' : '#2563eb';
        const iconClass   = isInspect ? 'fa-search' : 'fa-shipping-fast';

        $('#bulkShipActionModalGlobalTitle').text(label + ' (Global)');
        $('#bulkShipActionInfoText').html(`
            <i class="fas ${iconClass} me-0.5" style="color:${accentColor};"></i>
            <strong id="bulkShipActionCount" class="text-dark">${cartonOrder.length}</strong> carton berikut akan diproses ${label}.
        `);
        $('#btnSubmitShipActionGlobalText').text(label + ' Sekarang');
        $('#btnSubmitShipActionGlobal').css({ 'background-color': accentColor, 'border-color': accentColor });

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
        $('#bulkShipActionList').html(html);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkShipActionModalGlobal')).show();
    }

    // ============================================================
    // Submit sebenarnya -- dipanggil dari tombol konfirmasi di modal.
    // ============================================================
    function submitShipActionGlobal() {
        const packpks = shipActionGlobalPackpks || [];
        const action  = shipActionGlobalTarget;

        if (!packpks.length || !action) {
            showToast('warning', 'Pilih minimal satu carton.');
            return;
        }

        $.ajax({
            url: "{{ route('finish-good-stuffing.bulk-ship-action') }}",
            method: 'POST',
            data: {
                packpk: packpks.join(','),
                action: action,
                mif: @json($mif)
            },
            beforeSend: function () {
                $('#btnSubmitShipActionGlobal').prop('disabled', true);
            },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('bulkShipActionModalGlobal')).hide();
                loadPackingCards();
                reloadBreakdownSummary();
                closeMenuGlobal();
            },
            error: function (xhr) {
                let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                showToast(res.icon, res.title);
            },
            complete: function () {
                $('#btnSubmitShipActionGlobal').prop('disabled', false);
            }
        });
    }

    // GANTI 2 function pemanggil -- SEBELUMNYA langsung panggil
    // runShipBulkActionGlobal() yang dulu isinya confirm()+ajax. Sekarang
    // TETAP panggil fungsi yang sama (namanya dipertahankan), tapi
    // isinya sudah diganti jadi "buka modal" seperti di atas.
    function bulkProsesInspectGlobal() {
        runShipBulkActionGlobal('inspect', 'Proses Inspect');
    }

    function bulkProsesShipmentGlobal() {
        runShipBulkActionGlobal('shipment', 'Proses Shipment');
    }
</script>