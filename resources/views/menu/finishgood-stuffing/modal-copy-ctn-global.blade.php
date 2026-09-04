<div class="modal fade" id="bulkCopyModalGlobal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-dark" style="font-size:16px;">Copy Carton (Global)</h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pb-2">
                <p class="mb-1 fw-semibold">
                    Apakah Anda yakin ingin menyalin carton yang dipilih?
                </p>
                <p class="text-muted mb-3" style="font-size:13px;">
                    Carton yang berisi <strong class="text-dark">beberapa Color/Sec Size (Mixed)</strong> akan
                    disalin sebagai <strong class="text-dark">satu kesatuan</strong> per putaran copy -- semua
                    warnanya ikut tersalin bersamaan dengan nomor carton baru yang sama.
                </p>

                <div class="table-responsive border rounded bg-white mb-3 style-modal-scrollbar" style="max-height:220px; border-color:#e2e8f0!important;">
                    <table class="table table-sm table-hover text-center align-middle mb-0" style="font-size:13px;">
                        <thead class="table-light text-secondary sticky-top" style="font-size:11px; text-transform:uppercase;">
                            <tr>
                                <th width="40">No</th>
                                <th>Barcode</th>
                                <th>Carton</th>
                                <th>Color / Sec Size</th>
                                <th class="text-start">Status Actual</th>
                            </tr>
                        </thead>
                        <tbody id="copyListGlobal"></tbody>
                    </table>
                </div>

                <div id="copyWarningGlobal" class="alert alert-warning py-2 px-3 mb-2 d-none" style="font-size:12.5px;">
                    <i class="fas fa-exclamation-triangle me-1"></i> Beberapa Actual Qty tidak akan ikut tersalin
                    karena Polibag kombinasi Color/Sec Size terkait sudah habis.
                </div>

                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4 mb-2">
                    <div class="col-md-7">
                        <label class="form-label-custom">
                            <i class="fas fa-clone me-1"></i>
                            Jumlah Duplikasi per Carton
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="copyAmountGlobal" class="form-control form-control-modern" min="1" value="1" required>
                    </div>
                </div>
                <input type="hidden" id="copyIdsGlobal">
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" style="font-size:13px;border-radius:6px;">
                    Batal
                </button>

                <button type="button" id="btnProcessCopyGlobal" class="btn btn-dark px-4" style="font-size:13px;border-radius:6px;background:#1e293b;border-color:#1e293b;" onclick="processCopyGlobal()">
                    <i class="fas fa-copy me-1"></i>
                    Process Copy
                </button>
            </div>

        </div>
    </div>
</div>

<style>
.form-label-custom{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.5px;
    font-weight:600;
    color:#475569;
    margin-bottom:6px;
}
.form-control-modern{
    border-color:#cbd5e1;
    border-radius:6px;
    font-size:13px;
    height:36px;
}
.form-control-modern:focus{
    border-color:#64748b!important;
    box-shadow:0 0 0 3px rgba(100,116,139,.15)!important;
}
.style-modal-scrollbar::-webkit-scrollbar{
    width:4px;
}
.style-modal-scrollbar::-webkit-scrollbar-thumb{
    background:#cbd5e1;
    border-radius:4px;
}
</style>

<script>
    // ============================================================
    // bulkCopyGlobal() -- dipanggil dari sticky bar, buka modal + isi
    // preview dari semua packpk terpilih (window.selectedPackpksGlobal).
    // ============================================================
    function bulkCopyGlobal() {
        const packpks = window.selectedPackpksGlobal || [];
        if (!packpks.length) {
            showToast('warning', 'Pilih minimal satu carton.');
            return;
        }

        window.bulkCopyGlobalRows = (window.lastPackingRows || []).filter(r => packpks.includes(r.packpk));
        $('#copyIdsGlobal').val(packpks.join(','));
        $('#copyAmountGlobal').val(1);

        renderCopyPreviewGlobal();

        bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkCopyModalGlobal')).show();
    }

    $(document).on('input', '#copyAmountGlobal', function () {
        renderCopyPreviewGlobal();
    });

    function renderCopyPreviewGlobal() {
        const rows = window.bulkCopyGlobalRows || [];
        const copies = Math.max(1, parseInt($('#copyAmountGlobal').val()) || 1);
        const activeIdx = Object.keys(window.activeSizesGlobal || {});
    
        // remaining pool di-key by popk.
        const remaining = {};
        (window.pgCombos || []).forEach(function (c) {
            const key = String(c.popk);
            remaining[key] = {};
            activeIdx.forEach(function (i) {
                remaining[key][i] = Number(c.transQty?.[i] || 0) - Number(c.readyQty?.[i] || 0);
            });
        });
    
        let html = '';
        let adaDitolak = false;
    
        rows.forEach(function (row, index) {
            const popkKey = String(row.popk); // key by popk, bukan comboKey string.
            const punyaActual = activeIdx.some(i => Number(row[`qty${i}`] || 0) > 0);
            let statusParts = [];
    
            if (!punyaActual) {
                statusParts.push('<span class="text-muted">Tidak ada Actual (hanya Plan yang dicopy)</span>');
            } else {
                activeIdx.forEach(function (i) {
                    const actualAsal = Number(row[`qty${i}`] || 0);
                    if (actualAsal <= 0) return;
                    const namaSize = window.activeSizesGlobal[i] || `Size ${i}`;
    
                    let totalTersalin = 0;
                    let sisa = remaining[popkKey]?.[i] ?? 0;
                    let roundPenuh = 0;
                    let roundPartial = 0;
    
                    for (let c = 1; c <= copies; c++) {
                        if (sisa <= 0) break;
                        if (sisa >= actualAsal) {
                            sisa -= actualAsal;
                            totalTersalin += actualAsal;
                            roundPenuh++;
                        } else {
                            totalTersalin += sisa;
                            roundPartial++;
                            sisa = 0;
                        }
                    }
    
                    if (remaining[popkKey]) remaining[popkKey][i] = sisa;
    
                    const totalSeharusnya = actualAsal * copies;
                    if (totalTersalin >= totalSeharusnya) {
                        statusParts.push(`<span class="text-success">✅ ${namaSize}: Actual tersalin penuh ke semua ${copies} copy</span>`);
                    } else if (totalTersalin > 0) {
                        statusParts.push(`<span class="text-warning fw-semibold">⚠ ${namaSize}: Polibag terbatas -- ${roundPenuh} copy penuh${roundPartial ? ` + 1 copy sebagian (sisa ${sisa === 0 ? remaining[popkKey]?.[i] : ''})` : ''}, total tersalin ${totalTersalin}/${totalSeharusnya}</span>`);
                        adaDitolak = true;
                    } else {
                        statusParts.push(`<span class="text-danger">❌ ${namaSize}: Polibag sudah habis, Actual tidak ikut dicopy</span>`);
                        adaDitolak = true;
                    }
                });
            }
    
            // label konsisten dengan Detail Packing/Bulk Actual --
            // tampil "BLACK 1"/"BLACK 2" kalau row ini popk-nya kembaran warna.
            const materialLabel = getComboLabel(row);
            const secszTag = row.secsz ? ` (${row.secsz})` : '';
    
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${row.nobar ?? ''}</td>
                    <td>${row.carton}</td>
                    <td>${materialLabel}${secszTag}</td>
                    <td class="text-start" style="font-size:12px;line-height:1.7;">${statusParts.join('<br>')}</td>
                </tr>
            `;
        });
    
        $('#copyListGlobal').html(html);
        $('#copyWarningGlobal').toggleClass('d-none', !adaDitolak);
    }

    function processCopyGlobal() {
        const ids = $('#copyIdsGlobal').val();
        const copies = $('#copyAmountGlobal').val();

        if (!ids) {
            showToast('warning', 'Pilih minimal satu carton.');
            return;
        }
        if (!copies || copies < 1) {
            showToast('warning', 'Jumlah duplikasi minimal 1.');
            return;
        }

        $.ajax({
            url: "{{ route('packing.copy-selected.global') }}",
            method: 'POST',
            data: {
                packpk: ids,
                copy: copies,
                po: @json($po),
                op: @json($op),
                poref: @json($poref ?? null),
                mif: @json($mif)
            },
            beforeSend: function () {
                $('#btnProcessCopyGlobal').prop('disabled', true);
            },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('bulkCopyModalGlobal')).hide();
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
                $('#btnProcessCopyGlobal').prop('disabled', false);
            }
        });
    }
</script>