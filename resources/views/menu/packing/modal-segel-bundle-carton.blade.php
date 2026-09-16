<div class="modal fade" id="segelBundleModal" tabindex="-1" aria-labelledby="segelBundleModalTitle" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="segelBundleModalTitle" style="font-size:16px;">
                    Segel Carton Besar
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pb-2">
                <div class="text-muted mb-2" id="segelBundleInfoText" style="font-size:12.5px; line-height:1.4;"></div>

                <div class="table-responsive border rounded bg-white mb-2 style-modal-scrollbar"
                    style="max-height:260px; border-color:#e2e8f0 !important;">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px;">
                        <thead class="table-light text-secondary sticky-top"
                            style="font-size:11px; text-transform:uppercase; letter-spacing:0.3px;">
                            <tr>
                                <th width="40" class="py-2 text-center">No</th>
                                <th class="py-2 text-center">No CTN</th>
                                <th class="py-2 text-center">PO/OP</th>
                                <th class="py-2 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="segelBundleList" class="text-dark"></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal"
                    style="font-size:13px; border-radius:6px; height:33px;">
                    Cancel
                </button>
                <button type="button" id="btnSubmitSegelBundle" class="btn btn-sm btn-dark px-4 d-inline-flex align-items-center gap-1"
                    style="font-size:13px; border-radius:6px; height:33px; background-color:#1e293b; border-color:#1e293b;"
                    onclick="submitSegelBundle()">
                    <i class="fas fa-lock small"></i> Segel Sekarang
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    let segelBundleTarget = 1;
    let segelBundleCurrentBundlepk = null;

    // BARU -- dipanggil dari tombol "Seal Bundle"/"Unseal Bundle" di kartu
    // Bundle. Validasi kelengkapan dilakukan DULU di client (SAMA pola
    // dengan modal Segel biasa), backend tetap validasi ulang.
    function openSegelBundleModal(target, bundlepk) {
        const rows = (window.lastPackingRows || []).filter(r => String(r.bundlepk) === String(bundlepk));

        if (!rows.length) {
            showToast('warning', 'Carton dalam bundle ini tidak ditemukan.');
            return;
        }

        if (target === 1) {
            const belumLengkap = rows.filter(r => !isRowComplete(r));
            if (belumLengkap.length > 0) {
                const daftarCarton = [...new Set(belumLengkap.map(r => r.carton))].join(', ');
                showToast('warning', `Carton berikut belum lengkap Actual-nya, tidak bisa disegel: ${daftarCarton}`);
                return;
            }
        }

        segelBundleTarget = target;
        segelBundleCurrentBundlepk = bundlepk;

        const cartonGroups = {};
        const cartonOrder = [];
        rows.forEach(function (row) {
            const key = row.carton + '|' + row.POno + '|' + row.OP;
            if (!cartonGroups[key]) { cartonGroups[key] = []; cartonOrder.push(key); }
            cartonGroups[key].push(row);
        });

        const bundleName = rows.find(r => r.bundle_carton)?.bundle_carton || 'Carton Besar';

        if (target === 1) {
            $('#segelBundleModalTitle').text('Segel Carton Besar');
            $('#segelBundleInfoText').html(`
                <i class="fas fa-info-circle me-0.5"></i>
                Carton Besar <strong>${bundleName}</strong> (${cartonOrder.length} carton kecil) akan disegel sekaligus.
                Carton yang sudah disegel <strong class="text-dark">tidak dapat diedit lagi</strong>.
            `);
            $('#btnSubmitSegelBundle').html('<i class="fas fa-lock small"></i> Segel Sekarang');
        } else {
            $('#segelBundleModalTitle').text('Buka Segel Carton Besar');
            $('#segelBundleInfoText').html(`
                <i class="fas fa-info-circle me-0.5"></i>
                Carton Besar <strong>${bundleName}</strong> (${cartonOrder.length} carton kecil) akan dibuka segelnya sekaligus.
            `);
            $('#btnSubmitSegelBundle').html('<i class="fas fa-unlock small"></i> Buka Segel Sekarang');
        }

        let html = '';
        cartonOrder.forEach(function (key, index) {
            const groupRows = cartonGroups[key];
            const first = groupRows[0];
            const isSealed = Number(first.segel) === 1;
            html += `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td class="text-center"><strong>${first.carton}</strong></td>
                    <td class="text-center">${first.POno ?? '-'} &middot; ${first.OP ?? '-'}</td>
                    <td class="text-center"><span class="badge-status ${isSealed?'sealed':'planned'}">${isSealed?'Sealed':'Belum'}</span></td>
                </tr>
            `;
        });
        $('#segelBundleList').html(html);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('segelBundleModal')).show();
    }

    function submitSegelBundle() {
        if (!segelBundleCurrentBundlepk) return;

        $.ajax({
            url: R.bundleSegel,
            method: 'POST',
            data: {
                bundlepk: segelBundleCurrentBundlepk,
                target: segelBundleTarget,
            },
            beforeSend: function () {
                $('#btnSubmitSegelBundle').prop('disabled', true);
            },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('segelBundleModal')).hide();
                loadPackingCards();
                reloadBreakdownSummary();
                reloadCardsInfoGlobal();
                closeMenuGlobal();
            },
            error: function (xhr) {
                let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                showToast(res.icon, res.title);
            },
            complete: function () {
                $('#btnSubmitSegelBundle').prop('disabled', false);
            }
        });
    }
</script>