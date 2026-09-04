<div class="modal fade" id="bulkActualCtnGlobalModal" tabindex="-1" aria-labelledby="bulkActualCtnGlobalModalTitle" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="bulkActualCtnGlobalModalTitle" style="font-size:16px;">
                    Input Actual Qty Carton (Global)
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pb-2">

                <div class="mb-2">
                    <label class="form-label-custom">
                        <i class="fas fa-tags text-secondary me-1" style="width:14px;"></i>
                        Pilih Distribusi Size
                    </label>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4 mb-2">
                        <div class="col-md-7">
                            <select id="bulkSizeSelectGlobal" class="form-select form-control-modern">
                                <option value="">Semua Size</option>
                                @foreach ($activeSizes as $i => $sz)
                                    <option value="{{ $i }}">{{ $sz }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="text-muted mt-1" style="font-size:11.5px; line-height:1.4;">
                        <i class="fas fa-info-circle me-0.5"></i>
                        Carton yang dipilih bisa berasal dari <strong class="text-dark">Color/Sec Size berbeda-beda</strong>
                        (carton Mixed) -- sisa Polibag yang dicek TETAP dihitung per kombinasi Color/Sec Size
                        masing-masing, <strong class="text-dark">bukan digabung</strong>. Jika memilih
                        <strong class="text-dark">"Semua Size"</strong>, seluruh Qty Plan akan disalin ke Actual
                        kecuali sisa Polibag combo tersebut tidak mencukupi (lihat status di bawah).
                    </div>
                </div>

                <div class="table-responsive border rounded bg-white mb-2 style-modal-scrollbar"
                    style="max-height: 300px; border-color:#e2e8f0 !important;">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:13px;">
                        <thead class="table-light text-secondary sticky-top"
                            style="font-size:11px; text-transform:uppercase; letter-spacing:0.3px;">
                            <tr>
                                <th width="40" class="py-2 text-center">No</th>
                                <th class="py-2 text-center">Barcode</th>
                                <th class="py-2 text-center">No CTN</th>
                                <th class="py-2 text-center">Color / Sec Size</th>
                                <th class="py-2 text-start">Status Alokasi</th>
                            </tr>
                        </thead>
                        <tbody id="bulkActualGlobalList" class="text-dark"></tbody>
                    </table>
                </div>

                <div id="bulkActualGlobalWarning" class="alert alert-warning py-2 px-3 d-none" style="font-size:12.5px;">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Beberapa carton tidak bisa terisi penuh / tidak bisa terisi sama sekali karena Polibag
                    kombinasi Color/Sec Size terkait sudah habis. Carton tersebut akan diisi sebagian atau dilewati.
                </div>

            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal"
                    style="font-size:13px; border-radius:6px; height:33px;">
                    Cancel
                </button>
                <button type="button" id="btnUpdateActualGlobal" class="btn btn-sm btn-dark px-4 d-inline-flex align-items-center gap-1"
                    style="font-size:13px; border-radius:6px; height:33px; background-color:#1e293b; border-color:#1e293b;"
                    onclick="submitBulkActualCtnGlobal()">
                    <i class="fas fa-check-circle small"></i> Update Actual
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
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) inset;
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
    $(document).on('change', '#bulkSizeSelectGlobal', function () {
        renderBulkActualPreviewGlobal($(this).val());
    });

    // ============================================================

    // ============================================================
    function renderBulkActualPreviewGlobal(size) {
        const packpks = window.selectedPackpksGlobal || [];
        const rows = (window.lastPackingRows || []).filter(r => packpks.includes(r.packpk));
        if (!rows.length) return;
    
        const activeIdx = Object.keys(window.activeSizesGlobal || {});
        const sizeIndexes = (size === '' || size === null) ? activeIdx : [size];
    
        // grup berdasarkan POPK (BUKAN lagi material+secsz) --
        // popk kembaran warna (material+secsz SAMA, popk BEDA) punya pool
        // Transfer/Polibag masing-masing SENDIRI, tidak boleh dianggap
        // berbagi satu pool.
        const popkGroups = {};
        rows.forEach(function (r) {
            const key = String(r.popk);
            if (!popkGroups[key]) popkGroups[key] = [];
            popkGroups[key].push(r);
        });
    
        const remaining = {};
        const isFilled  = {};
    
        Object.keys(popkGroups).forEach(function (popkKey) {
            const popkRows = popkGroups[popkKey];
    
            // cari combo via popk LANGSUNG (window.pgCombos sekarang
            // sudah 1 entry per popk), bukan lagi via string material+secsz.
            const comboData = (window.pgCombos || []).find(function (c) {
                return String(c.popk) === popkKey;
            });
    
            remaining[popkKey] = {};
            isFilled[popkKey] = {};
    
            sizeIndexes.forEach(function (i) {
                const ready    = Number(comboData?.readyQty?.[i] || 0);
                const transfer = Number(comboData?.transQty?.[i] || 0);
    
                isFilled[popkKey][i] = {};
                popkRows.forEach(function (r) {
                    const plan  = Number(r[`qtyp${i}`] || 0);
                    const exist = Number(r[`qty${i}`] || 0);
                    if (plan <= 0) {
                        isFilled[popkKey][i][r.packpk] = null;
                        return;
                    }
                    isFilled[popkKey][i][r.packpk] = (exist >= plan);
                });
    
                remaining[popkKey][i] = transfer - ready;
            });
        });
    
        let html = '';
        let adaMasalah = false;
    
        rows.forEach(function (row, index) {
            const popkKey = String(row.popk);
            const parts = [];
            let adaPlan = false;
    
            sizeIndexes.forEach(function (i) {
                const plan = Number(row[`qtyp${i}`] || 0);
                if (plan <= 0) return;
                adaPlan = true;
    
                const namaSize = window.activeSizesGlobal[i] || `Size ${i}`;
                const exist = Number(row[`qty${i}`] || 0);
    
                if (isFilled[popkKey][i][row.packpk] === true) {
                    parts.push(`<span class="text-primary">✔ ${namaSize}: sudah terisi penuh (${exist})</span>`);
                    return;
                }
    
                const butuh = plan - exist;
                const avail = Math.max(0, remaining[popkKey][i]);
    
                if (avail >= butuh) {
                    const label = exist > 0 ? `OK (+${butuh})` : `OK (${plan})`;
                    parts.push(`<span class="text-success">✅ ${namaSize}: ${label}</span>`);
                    remaining[popkKey][i] -= butuh;
                } else if (avail > 0) {
                    parts.push(`<span class="text-warning fw-semibold">⚠ ${namaSize}: hanya bisa +${avail} pcs (total ${exist + avail})</span>`);
                    remaining[popkKey][i] -= avail;
                    adaMasalah = true;
                } else {
                    parts.push(`<span class="text-danger">❌ ${namaSize}: Polibag sudah habis</span>`);
                    adaMasalah = true;
                }
            });
    
            if (!adaPlan) parts.push('<span class="text-muted">Tidak ada Plan</span>');
    
            // pakai getComboLabel() (helper yang sudah ada dari fix
            // Detail Packing sebelumnya) -- konsisten tampil "BLACK 1",
            // "BLACK 2" kalau row ini popk-nya kembaran warna.
            const materialLabel = getComboLabel(row);
            const secszTag = row.secsz ? ` (${row.secsz})` : '';
    
            html += `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td class="text-center">${row.nobar ?? ''}</td>
                    <td class="text-center"><strong>${row.carton}</strong></td>
                    <td class="text-center">${materialLabel}${secszTag}</td>
                    <td class="text-start" style="font-size:12px; line-height:1.7;">
                        ${parts.join('<br>')}
                    </td>
                </tr>
            `;
        });
    
        $('#bulkActualGlobalList').html(html);
        $('#bulkActualGlobalWarning').toggleClass('d-none', !adaMasalah);
    }

    function submitBulkActualCtnGlobal() {
        const packpks = window.selectedPackpksGlobal || [];
        if (!packpks.length) {
            showToast('warning', 'Pilih minimal satu carton.');
            return;
        }

        $.ajax({
            url: "{{ route('packing.update-ctn.global') }}",
            method: 'POST',
            data: {
                size: $('#bulkSizeSelectGlobal').val(),
                packpk: packpks.join(',')
            },
            beforeSend: function () {
                $('#btnUpdateActualGlobal').prop('disabled', true);
            },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('bulkActualCtnGlobalModal')).hide();
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
                $('#btnUpdateActualGlobal').prop('disabled', false);
            }
        });
    }
</script>