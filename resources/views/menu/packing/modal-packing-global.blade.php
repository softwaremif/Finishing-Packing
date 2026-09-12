<style>
    .pg-packtype-badge {
        flex: 1;
        text-align: center;
        padding: 8px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        color: #94a3b8;
        background: #fff;
    }
    .pg-packtype-badge.active {
        background: #0f172a;
        color: #fff;
        border-color: #0f172a;
    }
    .pg-breakdown-style select,
    .pg-breakdown-style input {
        border-radius: 6px;
        font-size: 12.5px;
    }
    .pg-breakdown-style .pg-qty-plan,
    .pg-breakdown-style .pg-qty-actual,
    .pg-breakdown-style .pg-crosspo-plan,
    .pg-breakdown-style .pg-crosspo-actual {
        max-width: 65px;
        margin: 0 auto;
        text-align: center;
    }
    .pg-breakdown-style .pg-qty-actual,
    .pg-breakdown-style .pg-crosspo-actual {
        background: #fffbeb;
    }
    .pg-breakdown-style tr[data-role="plan"] {
        border-bottom: 1px dashed #e2e8f0;
    }
    .pg-remove-line {
        color: #cbd5e1;
        cursor: pointer;
        font-size: 16px;
    }
    .pg-remove-line:hover { color: #ef4444; }
</style>
<div class="modal fade" id="packingGlobalModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">

            <div class="modal-header border-0 pb-1">
                <div>
                    <h5 class="fw-bold text-dark mb-0" id="pgModalTitle">New carton</h5>
                    <div class="text-secondary" style="font-size: 13px;">
                        Add color/sec size lines, isi Qty Plan &amp; Actual langsung per size.
                    </div>
                    <div class="text-secondary mt-1" style="font-size: 12px;">
                        <i class="fas fa-circle-info me-1"></i>
                        OP <strong>{{ $op }}</strong>
                        &middot; PO <strong>{{ $po ?: '-' }}</strong>
                        &middot; Buyer: <strong>{{ $dt2->buyer ?? '-' }}</strong>
                        &middot; Customer: <strong>{{ $dt2->customer ?? '-' }}</strong>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div id="pgAlert" class="alert alert-danger mx-3 mt-1 mb-0 py-2 px-3 d-none" style="font-size: 13px;">
                <i class="fas fa-exclamation-triangle me-2"></i><span id="pgAlertText"></span>
            </div>

            <div class="modal-body pt-3">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">Carton no.</label>
                        <input type="text" id="pgCarton" class="form-control" placeholder="CTN-001">
                    </div>
                    <div class="col-6">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">Pack type</label>
                        <div class="d-flex gap-2">
                            <span class="pg-packtype-badge" id="pgTypeSolid">Solid</span>
                            <span class="pg-packtype-badge" id="pgTypeAssorted">Assorted</span>
                            <span class="pg-packtype-badge" id="pgTypeMixed">Mixed</span>
                        </div>
                    </div>
                </div>

                <div class="form-check mb-3" id="pgAutoSplitWrapper">
                    <input class="form-check-input" type="checkbox" id="pgAutoSplit" disabled>
                    <label class="form-check-label text-secondary" style="font-size:12.5px;" for="pgAutoSplit">
                        <strong class="text-dark">Auto Split Carton.</strong>
                        Aktif otomatis kalau cuma <b>1 Color/Sec Size</b> &amp; <b>1 Size</b> yang diisi --
                        sistem akan otomatis membuat beberapa Carton sesuai Order Qty size tersebut.
                    </label>
                </div>

                <label class="text-secondary d-block mb-2" style="font-size: 12px;">Breakdown</label>

                {{-- FIX: setiap combo sekarang punya 2 baris (P/A) seperti
                     modal lama per-popk -- baris Plan & baris Actual, semua
                     size tampil langsung sebagai kolom, tidak perlu pilih
                     size dari dropdown. --}}
                <div class="table-responsive border rounded-3">
                    <table class="table table-sm align-middle mb-0 text-center" id="pgBreakdownTable">
                        <thead style="font-size: 0.8rem; background:#f8fafc;">
                            <tr>
                                <th class="text-start px-2" style="min-width:220px;">Color / Sec Size</th>
                                <th width="36" class="bg-light">P/A</th>
                                @foreach ($activeSizes as $i => $sz)
                                    <th style="min-width:70px;">{{ $sz }}</th>
                                @endforeach
                                <th width="40"></th>
                            </tr>
                        </thead>
                        <tbody id="pgBreakdownLines"></tbody>
                    </table>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <button type="button" class="btn btn-link btn-sm px-0 text-decoration-none" onclick="addBreakdownLine()">
                        <i class="fas fa-plus me-1"></i> Add breakdown line
                    </button>
                    <button type="button" class="btn btn-outline-dark btn-sm" onclick="openCrossPoMixModal()">
                        <i class="fas fa-shuffle me-1"></i> Campur Polibag dari PO Lain
                    </button>
                </div>

                <hr class="my-3">
 
                <div id="pgCrossPoBlocksWrap" class="mt-3"></div>

                <hr class="my-3">

                <div class="row g-3">
                    <div class="col-6">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">No. Barcode</label>
                        <input type="text" id="pgNobar" class="form-control" placeholder="Scan Barcode">
                    </div>
                    <div class="col-3">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">N.W (Kg)</label>
                        <input type="number" step="0.01" min="0" id="pgNw" class="form-control">
                    </div>
                    <div class="col-3">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">(Kg)</label>
                        <input type="number" step="0.01" min="0" id="pgGw" class="form-control">
                    </div>
                    {{-- <div class="col-6">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">Meas CTN</label>
                        <input type="text" id="pgMeas" class="form-control">
                    </div> --}}
                    <div class="col-2">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">Panjang (Cm)</label>
                        <input type="number" step="0.01" min="0" id="pgPanjang" class="form-control" placeholder="0">
                    </div>
                    <div class="col-2">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">Lebar (Cm)</label>
                        <input type="number" step="0.01" min="0" id="pgLebar" class="form-control" placeholder="0">
                    </div>
                    <div class="col-2">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">Tinggi (Cm)</label>
                        <input type="number" step="0.01" min="0" id="pgTinggi" class="form-control" placeholder="0">
                    </div>
                   <div class="col-6">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">
                            Keterangan
                        </label>
                        <textarea id="pgKet2" class="form-control" rows="3" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 d-flex justify-content-between align-items-center">
                <div style="font-size: 14px;" class="d-flex gap-3">
                    <div>
                        <span class="text-secondary">Total Plan</span>
                        <strong id="pgTotal" class="ms-1">0</strong> <span class="text-secondary">pcs</span>
                    </div>
                    <div>
                        <span class="text-secondary">Total Actual</span>
                        <strong id="pgTotalActual" class="ms-1 text-success">0</strong> <span class="text-secondary">pcs</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="btnAddCartonGlobal" class="btn btn-dark" onclick="savePackingGlobalModal()">
                        Add carton
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    let pgLineCounter = 0;
    window.pgIsEditMode = false;
    window.lastPackingRows = window.lastPackingRows || [];
    window.pgOldActualByPackpk = window.pgOldActualByPackpk || {};
    window.pgOldPlanByPackpk = window.pgOldPlanByPackpk || {};
    window.pgCurrentBuyer = @json($dt2->buyer ?? '');

    function pgComboOptions(selected) {
        let html = '<option value="">Pilih Color / Sec Size</option>';
        (window.pgCombos || []).forEach(function (c) {
            let value = `${c.material}||${c.secsz ?? ''}||${c.popk}`;
            let materialLabel = c.duplicateMarker ? `${c.material} ${c.duplicateMarker}` : c.material;
            let label = c.secsz ? `${materialLabel} - ${c.secsz}` : materialLabel;

            // tambahkan Customer ke label combo.
            if (c.customer) {
                label += ` &middot; ${c.customer}`;
            }

            if (c.crossPo) {
                label += ` [${c.crossPoLabel}]`;
                if (c.activeSizes && !pgSizeSchemeMatches(c.activeSizes)) {
                    label += ' ⚠️ skema size beda';
                }
            }
            let sel = (selected === value) ? 'selected' : '';
            html += `<option value="${value}" ${sel}>${label}</option>`;
        });
        return html;
    }

    function pgSizeSchemeMatches(comboActiveSizes) {
        const pageSizes = window.pgSizes || {};
        return Object.keys(comboActiveSizes).every(function (i) {
            return pageSizes[i] === comboActiveSizes[i];
        });
    }

    function pgGetOrderQtyForCombo(comboValue) {
        const parts = String(comboValue).split('||');
        const popk = parts[2];
        const comboData = (window.pgCombos || []).find(function (c) {
            return String(c.popk) === String(popk);
        });
        return comboData?.orderQty || {};
    }

    // ============================================================
    // BARU: bangun HTML kolom size (Plan & Actual) berdasarkan combo
    // yang dipilih -- size dengan Order Qty 0/null TIDAK ditampilkan
    // input sama sekali (ganti jadi "-"). Kalau combo BELUM dipilih
    // (comboValue kosong), tampilkan SEMUA input dulu (baru
    // disembunyikan setelah user pilih combo-nya).
    // ============================================================
    function buildPgSizeCells(comboValue, prefill) {
        prefill = prefill || {};
        const orderQty = pgGetOrderQtyForCombo(comboValue);

        let planCells = '';
        let actualCells = '';

        Object.keys(window.pgSizes || {}).forEach(function (i) {
            const orderQtyForSize = Number(orderQty[i] || 0);
            const comboBelumDipilih = !comboValue;
            const sizeAdaOrder = comboBelumDipilih || orderQtyForSize > 0;

            if (!sizeAdaOrder) {
                // FIX UTAMA: Order 0/null -- JANGAN tampilkan input.
                planCells += `<td class="pg-size-td pg-empty-cell text-muted" data-size="${i}" title="Order Qty size ini 0">-</td>`;
                actualCells += `<td class="pg-size-td pg-empty-cell text-muted" data-size="${i}">-</td>`;
                return;
            }

            const planVal   = (prefill.plan && prefill.plan[i]) ? prefill.plan[i] : '';
            const actualVal = (prefill.actual && prefill.actual[i]) ? prefill.actual[i] : '';
            const hasPlan   = Number(planVal) > 0;

            planCells += `
                <td class="pg-size-td" data-size="${i}">
                    <input type="number" min="0" class="form-control form-control-sm pg-qty-plan"
                        data-size="${i}" placeholder="0" value="${planVal}" oninput="onPgPlanInput(this)">
                </td>
            `;

            actualCells += `
                <td class="pg-size-td pg-actual-cell" data-size="${i}" style="${hasPlan ? '' : 'visibility:hidden;'}">
                    <input type="number" min="0" class="form-control form-control-sm pg-qty-actual"
                        data-size="${i}" placeholder="0" value="${actualVal}" ${hasPlan ? '' : 'disabled'}
                        oninput="onPgQtyInput(this)">
                </td>
            `;
        });

        return { planCells, actualCells };
    }

    // prefill = { combo, plan: {i: val}, actual: {i: val}, packpk }
    function addBreakdownLine(prefill) {
        prefill = prefill || {};
        pgLineCounter++;
        const lineId = pgLineCounter;
        const packpk = prefill.packpk || '';

        const { planCells, actualCells } = buildPgSizeCells(prefill.combo, prefill);

        const actualRowClass = window.pgIsEditMode ? '' : ' d-none';
        const html = `
            <tr class="pg-breakdown-line" data-line="${lineId}" data-role="plan" data-packpk="${packpk}">
                <td class="text-start align-middle">
                    <select class="form-select form-select-sm pg-combo" style="font-size:14px;" onchange="onPgComboChangeGlobal(this)">${pgComboOptions(prefill.combo)}</select>
                </td>
                <td class="bg-light fw-bold" style="font-size:11px;">P</td>
                ${planCells}
                <td class="align-middle">
                    <span class="pg-remove-line" onclick="removeBreakdownLine(this)"><i class="fas fa-minus-circle"></i></span>
                </td>
            </tr>
            <tr class="pg-breakdown-line${actualRowClass}" data-line="${lineId}" data-role="actual">
                <td class="pg-empty-cell"></td>
                <td class="bg-light fw-bold" style="font-size:11px;">A</td>
                ${actualCells}
                <td class="pg-empty-cell"></td>
            </tr>
        `;
        $('#pgBreakdownLines').append(html);
        recalcPackTypeGlobal();
    }

    // ============================================================
    // BARU: dipanggil setiap combo (Color/Sec Size) di 1 baris
    // berubah -- render ULANG kolom size baris itu saja (Plan &
    // Actual), sesuai Order Qty combo yang baru dipilih.
    // ============================================================
    function onPgComboChangeGlobal(selectEl) {
        const $select = $(selectEl);
        const lineId = $select.closest('tr').data('line');
        const comboValue = $select.val();

        const planRow   = $(`.pg-breakdown-line[data-line="${lineId}"][data-role="plan"]`);
        const actualRow = $(`.pg-breakdown-line[data-line="${lineId}"][data-role="actual"]`);

        const { planCells, actualCells } = buildPgSizeCells(comboValue, {});

        // Ganti HANYA <td class="pg-size-td"> -- kolom combo/P-A/tombol
        // hapus di kiri-kanan TIDAK disentuh.
        planRow.find('td.pg-size-td').remove();
        planRow.find('.pg-remove-line').closest('td').before(planCells);

        actualRow.find('td.pg-size-td').remove();
        actualRow.find('.pg-empty-cell:last').before(actualCells);

        recalcPackTypeGlobal();
    }

    function onPgPlanInput(el) {
        const $el = $(el);
        const planRow = $el.closest('tr');
        const lineId = planRow.data('line');
        const size = $el.data('size');
        const planBaru = parseInt($el.val()) || 0;
    
        pgHideAlert();
    
        const packpk = planRow.data('packpk') || null;
        const planLama = packpk
            ? Number(window.pgOldPlanByPackpk?.[packpk]?.[size] || 0)
            : 0;
    
        const comboValue = planRow.find('.pg-combo').val();
        const comboData = (window.pgCombos || []).find(function (c) {
            return String(c.popk) === String(comboValue.split('||')[2]);
        });
    
        // ============================================================
        // Validasi Plan vs Order Qty -- total Plan GABUNGAN semua
        // carton (termasuk baris ini) tidak boleh melebihi Order Qty.
        // ============================================================
        if (comboData && planBaru > 0) {
            const orderQty     = Number(comboData.orderQty?.[size] || 0);
            const planExisting = Number(comboData.planQty?.[size] || 0);
            const total        = (planExisting - planLama) + planBaru;
    
            if (total > orderQty) {
                const sisa = orderQty - (planExisting - planLama);
                pgShowAlert(
                    '<b>Qty Plan tidak dapat disimpan.</b><br>' +
                    'Jumlah Qty Plan yang diinput menyebabkan total Plan melebihi Order Qty.<br>'
                    // '<span class="text-danger fw-bold">Maksimal Qty Plan yang masih dapat diinput untuk size ini adalah ' +
                    // Math.max(0, sisa) + '.</span>'
                );
                $el.val(planLama);
                // Lanjut ke logic visibility Actual cell di bawah, pakai nilai
                // yang SUDAH dikembalikan ($el.val() sekarang = planLama).
            }
        }
    
        const size2 = size; // alias, tidak diubah
        const actualCell = $(`.pg-breakdown-line[data-line="${lineId}"][data-role="actual"] .pg-actual-cell[data-size="${size2}"]`);
        const actualInput = actualCell.find('.pg-qty-actual');
    
        const finalPlanVal = parseInt($el.val()) || 0;
        if (finalPlanVal > 0) {
            actualCell.css('visibility', 'visible');
            actualInput.prop('disabled', false);
        } else {
            actualCell.css('visibility', 'hidden');
            actualInput.prop('disabled', true).val('');
        }
    
        recalcPackTypeGlobal();
    }
    
    function removeBreakdownLine(el) {
        const lineId = $(el).closest('tr').data('line');
        $(`.pg-breakdown-line[data-line="${lineId}"]`).remove();
        recalcPackTypeGlobal();
    }

    // Validasi live: SAMA PERSIS dengan modal lama --
    // Validasi 1: Actual tidak boleh melebihi Plan MILIK SENDIRI (baris ini).
    // Validasi 2: total Actual GABUNGAN (semua carton utk popk+size ini)
    //             tidak boleh melebihi Transfer (Polibag Qty).
    function onPgQtyInput(el) {
        const $el   = $(el);
        const lineId = $el.closest('tr').data('line');
        const size   = $el.data('size');
        const planRow   = $(`.pg-breakdown-line[data-line="${lineId}"][data-role="plan"]`);
        const actualRow = $(`.pg-breakdown-line[data-line="${lineId}"][data-role="actual"]`);

        if ($el.hasClass('pg-qty-actual')) {
            pgHideAlert();

            const planVal    = parseInt(planRow.find(`.pg-qty-plan[data-size="${size}"]`).val()) || 0;
            const actualBaru = parseInt($el.val()) || 0;
            const packpk     = planRow.data('packpk') || null;
            const actualLama = packpk
                ? Number(window.pgOldActualByPackpk?.[packpk]?.[size] || 0)
                : 0;

            if (actualBaru > planVal) {
                pgShowAlert(
                    '<b>Qty Actual tidak dapat disimpan.</b><br>' +
                    `Qty Actual yang diinput (<b>${actualBaru}</b>) melebihi Plan Qty pada carton ini (<b>${planVal}</b>).<br>`
                    // '<span class="text-danger fw-bold">Maksimal Qty Actual untuk size ini adalah ' + planVal + '.</span>'
                );
                $el.val(actualLama);
                recalcPackTypeGlobal();
                return;
            }

            const comboValue = planRow.find('.pg-combo').val();
            const comboData = (window.pgCombos || []).find(function (c) {
                return String(c.popk) === String(comboValue.split('||')[2]);
            });

            if (comboData) {
                const ready    = Number(comboData.readyQty?.[size] || 0);
                const transfer = Number(comboData.transQty?.[size] || 0);
                const total    = (ready - actualLama) + actualBaru;

                if (total > transfer) {
                    const sisa = transfer - (ready - actualLama);
                    pgShowAlert(
                        '<b>Qty Actual tidak dapat disimpan.</b><br>' +
                        'Jumlah Qty Actual yang diinput menyebabkan total Actual Pack Quantity melebihi Qty Polibag.<br>' 
                        // '<span class="text-danger fw-bold">Maksimal Qty yang masih dapat diinput adalah ' +
                        // Math.max(0, sisa) + '.</span>'
                    );
                    $el.val(actualLama);
                    recalcPackTypeGlobal();
                    return;
                }
            }
        }

        recalcPackTypeGlobal();
    }

    function recalcPackTypeGlobal() {
        let total = 0;
        let totalActual = 0;
        let combos = new Set();
        let assortedFlag = false;
        let totalSizeFilledOverall = 0;

        $('.pg-breakdown-line[data-role="plan"]').each(function () {
            const combo = $(this).find('.pg-combo').val();
            const lineId = $(this).data('line');
            let sizesFilledInLine = 0;

            $(this).find('.pg-qty-plan').each(function () {
                const qty = parseInt($(this).val()) || 0;
                if (qty > 0) {
                    total += qty;
                    sizesFilledInLine++;
                    totalSizeFilledOverall++;
                }
            });

            $(`.pg-breakdown-line[data-line="${lineId}"][data-role="actual"] .pg-qty-actual`).each(function () {
                const qtyActual = parseInt($(this).val()) || 0;
                totalActual += qtyActual;
            });

            if (combo && sizesFilledInLine > 0) {
                combos.add(combo);
                if (sizesFilledInLine > 1) assortedFlag = true;
            }
        });

        $('#pgTotal').text(total);
        $('#pgTotalActual').text(totalActual);

        $('.pg-packtype-badge').removeClass('active');
        if (combos.size > 1) {
            $('#pgTypeMixed').addClass('active');
        } else if (combos.size === 1) {
            if (assortedFlag) {
                $('#pgTypeAssorted').addClass('active');
            } else {
                $('#pgTypeSolid').addClass('active');
            }
        }

        const isSingleSizeSingleCombo = (combos.size === 1 && totalSizeFilledOverall === 1);

        if (!window.pgIsEditMode && isSingleSizeSingleCombo) {
            $('#pgAutoSplit').prop('disabled', false).prop('checked', true);
        } else {
            $('#pgAutoSplit').prop('disabled', true).prop('checked', false);
        }
    }

    function openPackingGlobalModal() {
        window.pgIsEditMode = false;
        
        window.pgOldActualByPackpk = {};
        window.pgOldPlanByPackpk = {}; 
        window.pgOriginalPackpks = [];
        $('#pgBreakdownLines').empty();
        $('#pgCarton, #pgNobar, #pgNw, #pgGw, #pgPanjang, #pgLebar, #pgTinggi, #pgKet2').val('');
        $('.pg-packtype-badge').removeClass('active');
        $('#pgTotal').text('0');
        $('#pgAutoSplitWrapper').removeClass('d-none');
        $('#pgModalTitle').text('New carton');
        $('#btnAddCartonGlobal').text('Add carton');
        pgHideAlert();
        pgResetCrossPoBlocks();
        addBreakdownLine();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('packingGlobalModal')).show();
    }

    function editCartonGlobal(packpksCsv) {
        const packpks = packpksCsv.split(',').map(Number);
        let groupRows = (window.lastPackingRows || []).filter(r => packpks.includes(r.packpk));
    
        if (!groupRows.length) {
            showToast('error', 'Data carton tidak ditemukan, coba refresh halaman.');
            return;
        }
    
        window.pgIsEditMode = true;
        window.pgOldActualByPackpk = {};
        window.pgOldPlanByPackpk = {};
        window.pgOriginalPackpks = groupRows.map(r => r.packpk);
    
        $('#pgBreakdownLines').empty();
        $('#pgAutoSplitWrapper').addClass('d-none');
        pgHideAlert();
        pgResetCrossPoBlocks();
    
        const nativeRows  = groupRows.filter(r => r.POno === PO && r.OP === OP);
        const crossPoRows = groupRows.filter(r => !(r.POno === PO && r.OP === OP));
    
        const first = groupRows[0];
        $('#pgCarton').val(first.carton || '');
        $('#pgNobar').val(first.nobar || '');
        $('#pgNw').val(first.nw || '');
        $('#pgGw').val(first.gw || '');
        $('#pgPanjang').val(first.panjang || '');
        $('#pgLebar').val(first.lebar || '');
        $('#pgTinggi').val(first.tinggi || '');
        $('#pgKet2').val(first.keterangan || '');
    
        nativeRows.forEach(function (row) {
            const combo = `${row.material}||${row.secsz ?? ''}||${row.popk}`;
            const plan = {}, actual = {}, oldActual = {}, oldPlan = {};
            Object.keys(window.pgSizes || {}).forEach(function (i) {
                plan[i]      = row[`qtyp${i}`] || '';
                actual[i]    = row[`qty${i}`]  || '';
                oldActual[i] = Number(row[`qty${i}`] || 0);
                oldPlan[i]   = Number(row[`qtyp${i}`] || 0);
            });
            window.pgOldActualByPackpk[row.packpk] = oldActual;
            window.pgOldPlanByPackpk[row.packpk] = oldPlan;
            addBreakdownLine({ combo: combo, plan: plan, actual: actual, packpk: row.packpk });
        });
    
        function finishOpenModal() {
            $('#pgModalTitle').text('Edit carton');
            $('#btnAddCartonGlobal').text('Simpan Perubahan');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('packingGlobalModal')).show();
            recalcPackTypeGlobal();
        }
    
        // ---- Baris dari PO LAIN -- masuk ke pgCrossPoBlocks (BUKAN tabel
        // utama), lengkap dengan activeSizes/orderQty/planQtyAll/readyQtyAll
        // MILIK PO ASALNYA SENDIRI (dari crossPoComboInfo()). ----
        if (crossPoRows.length) {
            const otherPopks = [...new Set(crossPoRows.map(r => r.popk))];
            $.get(R.crossPoComboInfo, { popks: otherPopks.join(',') }, function (data) {
                const infoList = data.rows || [];
    
                crossPoRows.forEach(function (row) {
                    const info = infoList.find(c => String(c.popk) === String(row.popk)) || {};
                    const activeSizes = info.activeSizes || {};
    
                    const planQty = {}, actualQty = {};
                    Object.keys(activeSizes).forEach(function (i) {
                        planQty[i]   = Number(row[`qtyp${i}`] || 0);
                        actualQty[i] = Number(row[`qty${i}`] || 0);
                    });
    
                    window.pgCrossPoBlocks.push({
                        packpk:        row.packpk,
                        popk:          row.popk,
                        POno:          row.POno,
                        OP:            row.OP,
                        customer:      info.customer, 
                        material:      row.material,
                        secsz:         row.secsz,
                        activeSizes:   activeSizes,
                        orderQty:      info.orderQty || {},
                        planQtyAll:    info.planQtyAll || {},
                        readyQtyAll:   info.readyQtyAll || {},
                        transQtyAll:   info.transQtyAll || {},
                        planQty:       planQty,
                        actualQty:     actualQty,
                        // snapshot NILAI AWAL (sebelum admin edit apa pun
                        // di sesi ini) -- dipakai validasi supaya kontribusi carton
                        // INI SENDIRI dikeluarkan dulu dari total sebelum dicek
                        // ulang terhadap Order Qty/Transfer.
                        originalPlanQty:   { ...planQty },
                        originalActualQty: { ...actualQty },
                        sourceCarton: row.carton,
                    });
                });
    
                renderPgCrossPoBlocks();
                finishOpenModal();
            });
        } else {
            finishOpenModal();
        }
    }

    function pgShowAlert(msg) {
        $('#pgAlertText').html(msg);
        $('#pgAlert').removeClass('d-none');
    }
    function pgHideAlert() {
        $('#pgAlert').addClass('d-none');
    }

    function savePackingGlobalModal() {
        pgHideAlert();
        const nocar = $('#pgCarton').val().trim();
        if (!nocar) {
            pgShowAlert('No Carton wajib diisi.');
            return;
        }
        const breakdown = [];
        $('.pg-breakdown-line[data-role="plan"]').each(function () {
            const combo = $(this).find('.pg-combo').val();
            if (!combo) return;
            const [material, secsz, comboPopk] = combo.split('||');
            const lineId  = $(this).data('line');
            const packpk  = $(this).data('packpk') || null;
            const actualRow = $(`.pg-breakdown-line[data-line="${lineId}"][data-role="actual"]`);
            $(this).find('.pg-qty-plan').each(function () {
                const size   = $(this).data('size');
                const plan   = parseInt($(this).val()) || 0;
                const actual = parseInt(actualRow.find(`.pg-qty-actual[data-size="${size}"]`).val()) || 0;
                if (plan > 0 || actual > 0) {
                    breakdown.push({ material, secsz, size, plan, actual, packpk, target_popk: comboPopk || null });
                }
            });
        });
    
        (window.pgCrossPoBlocks || []).forEach(function (block) {
            Object.keys(block.activeSizes).forEach(function (size) {
                const plan   = Number(block.planQty[size] || 0);
                const actual = Number(block.actualQty[size] || 0);
                if (plan <= 0 && actual <= 0) return;
                breakdown.push({
                    material: block.material,
                    secsz: block.secsz,
                    size: size,
                    plan: plan,
                    actual: actual,
                    packpk: block.packpk,       
                    target_popk: block.popk,
                });
            });
        });
    
        if (!breakdown.length) {
            pgShowAlert('Minimal satu Qty (Plan atau Actual) harus diisi.');
            return;
        }
        $.ajax({
            url: "{{ route('packing.store.global') }}",
            method: 'POST',
            data: {
                po: @json($po),
                op: @json($op),
                poref: @json($poref ?? null),
                mif: @json($mif),
                nocar: nocar,
                nobar: $('#pgNobar').val(),
                nw: $('#pgNw').val(),
                gw: $('#pgGw').val(),
                panjang: $('#pgPanjang').val(),
                lebar: $('#pgLebar').val(),
                tinggi: $('#pgTinggi').val(),
                ket2: $('#pgKet2').val(),
                breakdown: breakdown,
                check: $('#pgAutoSplit').is(':checked') ? 1 : 0,
                edit_mode: window.pgIsEditMode ? 1 : 0,
                original_packpks: window.pgIsEditMode ? (window.pgOriginalPackpks || []) : []
            },
            beforeSend: function () { $('#btnAddCartonGlobal').prop('disabled', true); },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('packingGlobalModal')).hide();
                loadPackingCards();
                reloadBreakdownSummary();
                refreshPgCombos();
                reloadCardsInfoGlobal();
            },
            error: function (xhr) {
                let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                pgShowAlert(res.title);
            },
            complete: function () { $('#btnAddCartonGlobal').prop('disabled', false); }
        });
    }
</script>

<script>
    // ============================================================
    // State blok "Mix Polibag Lintas PO" -- TERPISAH dari
    // window.pgCombos/tabel breakdown utama. Tiap blok = 1 combo dari
    // carton PO lain, lengkap dengan activeSizes MILIK SENDIRI (supaya
    // label kolom size SELALU benar, tidak tercampur skema PO yang
    // sedang dibuka).
    // ============================================================
    window.pgCrossPoBlocks = window.pgCrossPoBlocks || [];
 
    function pgResetCrossPoBlocks() {
        window.pgCrossPoBlocks = [];
        $('#pgCrossPoBlocksWrap').empty();
    }
 
    function pgRemoveCrossPoBlock(packpk) {
        window.pgCrossPoBlocks = window.pgCrossPoBlocks.filter(b => b.packpk !== packpk);
        renderPgCrossPoBlocks();
        recalcPackTypeGlobal();
    }
 
    // GANTI TOTAL -- FIX UTAMA: tiap blok render TABEL SENDIRI dengan
    // kolom size MILIK COMBO ITU SENDIRI (block.activeSizes) -- BUKAN
    // window.pgSizes (skema PO yang sedang dibuka). Input SUDAH terisi
    // (pre-filled) dari block.planQty/actualQty yang dibawa dari data
    // carton sumbernya -- admin BOLEH edit kalau perlu, tapi TIDAK WAJIB.
    function renderPgCrossPoBlocks() {
        const wrap = $('#pgCrossPoBlocksWrap');
        wrap.empty();

        if (!window.pgCrossPoBlocks.length) return;

        wrap.append(`
            <label class="text-secondary d-block mb-2" style="font-size: 12px;">
                Polibag Campuran dari PO Lain
            </label>
        `);

        window.pgCrossPoBlocks.forEach(function (block) {
            const sizeHeaderCells = Object.keys(block.activeSizes)
                .map(i => `<th style="min-width:70px;">${block.activeSizes[i]}</th>`).join('');

            const planCells = Object.keys(block.activeSizes).map(function (i) {
                const orderQtyForSize = Number(block.orderQty[i] || 0);
                if (orderQtyForSize <= 0) {
                    return `<td class="pg-empty-cell text-muted" data-size="${i}">-</td>`;
                }
                const val = block.planQty[i] || '';
                return `
                    <td data-size="${i}">
                        <input type="number" min="0" class="form-control form-control-sm pg-crosspo-plan"
                            data-packpk="${block.packpk}" data-size="${i}" value="${val}" placeholder="0"
                            oninput="pgOnCrossPoPlanInput(this)">
                    </td>
                `;
            }).join('');

            const actualCells = Object.keys(block.activeSizes).map(function (i) {
                const orderQtyForSize = Number(block.orderQty[i] || 0);
                if (orderQtyForSize <= 0) {
                    return `<td class="pg-empty-cell text-muted" data-size="${i}">-</td>`;
                }
                const val = block.actualQty[i] || '';
                const hasPlan = Number(block.planQty[i] || 0) > 0;
                return `
                    <td data-size="${i}" style="${hasPlan ? '' : 'visibility:hidden;'}">
                        <input type="number" min="0" class="form-control form-control-sm pg-crosspo-actual"
                            data-packpk="${block.packpk}" data-size="${i}" value="${val}" placeholder="0" ${hasPlan ? '' : 'disabled'}
                            oninput="pgOnCrossPoActualInput(this)">
                    </td>
                `;
            }).join('');

            const secszTag = block.secsz ? ` - ${block.secsz}` : '';
            // BARU -- tampilkan Customer juga di subtitle, kalau ada datanya.
            const customerTag = block.customer ? ` &middot; ${block.customer}` : '';

            wrap.append(`
                <div class="table-responsive border rounded-3 mb-2">
                    <table class="table table-sm align-middle mb-0 text-center pg-breakdown-style">
                        <thead style="font-size: 0.8rem; background:#f8fafc;">
                            <tr>
                                <th class="text-start px-2" style="min-width:220px;">Color / Sec Size</th>
                                <th width="36" class="bg-light">P/A</th>
                                ${sizeHeaderCells}
                                <th width="40"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr data-role="plan">
                                <td class="text-start align-middle">
                                    <div style="font-size:14px;">${block.material ?? '-'}${secszTag} ${customerTag}</div>
                                    <div class="text-muted" style="font-size:10.5px;">
                                        <i class="fas fa-shuffle me-1"></i>${block.POno ?? '-'} &middot; ${block.OP ?? '-'} &middot; Carton ${block.sourceCarton ?? '-'}
                                    </div>
                                </td>
                                <td class="bg-light fw-bold" style="font-size:11px;">P</td>
                                ${planCells}
                                <td class="align-middle">
                                    <span class="pg-remove-line" onclick="pgRemoveCrossPoBlock(${block.packpk})" title="Hapus dari campuran">
                                        <i class="fas fa-minus-circle"></i>
                                    </span>
                                </td>
                            </tr>
                            <tr data-role="actual">
                                <td class="pg-empty-cell"></td>
                                <td class="bg-light fw-bold" style="font-size:11px;">A</td>
                                ${actualCells}
                                <td class="pg-empty-cell"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `);
        });
    }
 
    // Validasi -- SAMA konsepnya dengan onPgPlanInput() di tabel utama,
    // TAPI cap-nya pakai block.orderQty MILIK COMBO INI SENDIRI (dari PO
    // asalnya), BUKAN window.pgCombos milik PO yang sedang dibuka.
    function pgOnCrossPoPlanInput(el) {
        const $el = $(el);
        const packpk = Number($el.data('packpk'));
        const size = $el.data('size');
        const block = window.pgCrossPoBlocks.find(b => b.packpk === packpk);
        if (!block) return;
    
        pgHideAlert();
    
        let planBaru = parseInt($el.val()) || 0;
    
        const orderQty       = Number(block.orderQty[size] || 0);
        const planAllExisting = Number(block.planQtyAll?.[size] || 0);
        const planLama         = Number(block.originalPlanQty?.[size] || 0);
    
        // Total kalau planBaru diterapkan = (total semua carton lain, TIDAK
        // termasuk kontribusi lama carton ini) + nilai baru.
        const totalIfApplied = (planAllExisting - planLama) + planBaru;
    
        if (totalIfApplied > orderQty) {
            const sisa = Math.max(0, orderQty - (planAllExisting - planLama));
            pgShowAlert(
                `<b>Qty Plan tidak dapat disimpan.</b><br>` +
                `Total Plan untuk size ini di PO <b>${block.POno ?? '-'}</b> (carton lain + carton ini) akan melebihi Order Qty (<b>${orderQty}</b>).<br>` +
                `Maksimal yang bisa diisi di carton ini: <b>${sisa}</b>.`
            );
            planBaru = Math.max(0, Math.min(planBaru, sisa));
            $el.val(planBaru || '');
        }
    
        block.planQty[size] = planBaru;
    
        // Toggle visibility Actual cell -- SAMA pola dengan tabel utama.
        const actualCell = $el.closest('table').find(`tbody tr:eq(1) td[data-size="${size}"]`);
        const actualInput = actualCell.find('.pg-crosspo-actual');
        if (planBaru > 0) {
            actualCell.css('visibility', 'visible');
            actualInput.prop('disabled', false);
        } else {
            actualCell.css('visibility', 'hidden');
            actualInput.prop('disabled', true).val('');
            block.actualQty[size] = 0;
        }
    
        recalcPackTypeGlobal();
    }
 
    function pgOnCrossPoActualInput(el) {
        const $el = $(el);
        const packpk = Number($el.data('packpk'));
        const size = $el.data('size');
        const block = window.pgCrossPoBlocks.find(b => b.packpk === packpk);
        if (!block) return;
    
        pgHideAlert();
    
        let actualBaru = parseInt($el.val()) || 0;
        const planVal = Number(block.planQty[size] || 0);
    
        if (actualBaru > planVal) {
            pgShowAlert(`<b>Qty Actual tidak dapat disimpan.</b><br>Qty Actual yang diinput (<b>${actualBaru}</b>) melebihi Plan Qty pada carton ini (<b>${planVal}</b>).`);
            actualBaru = planVal;
            $el.val(actualBaru || '');
        }
    
        const readyAllExisting = Number(block.readyQtyAll?.[size] || 0);
        const actualLama       = Number(block.originalActualQty?.[size] || 0);
        const transfer         = Number(block.transQtyAll?.[size] || 0);
    
        const totalIfApplied = (readyAllExisting - actualLama) + actualBaru;
    
        if (totalIfApplied > transfer) {
            const sisa = Math.max(0, transfer - (readyAllExisting - actualLama));
            pgShowAlert(
                `<b>Qty Actual tidak dapat disimpan.</b><br>` +
                `Total Actual untuk size ini di PO <b>${block.POno ?? '-'}</b> akan melebihi Qty Polibag/Transfer (<b>${transfer}</b>).<br>` +
                `Maksimal yang bisa diisi di carton ini: <b>${sisa}</b>.`
            );
            actualBaru = Math.max(0, Math.min(actualBaru, sisa));
            $el.val(actualBaru || '');
        }
    
        block.actualQty[size] = actualBaru;
        recalcPackTypeGlobal();
    }
 
    // ikutkan total Plan/Actual dari blok cross-PO ke footer
    // "Total Plan"/"Total Actual" modal, supaya angkanya utuh (gabungan
    // breakdown utama + blok cross-PO). Dipanggil dari AKHIR
    // recalcPackTypeGlobal() yang SUDAH ADA -- TAMBAHKAN pemanggilan
    // pgAddCrossPoTotals() di baris PALING BAWAH fungsi itu (sebelum
    // penutup function), BUKAN menggantikan logic yang sudah ada.
    function pgAddCrossPoTotals() {
        let extraPlan = 0, extraActual = 0;
        window.pgCrossPoBlocks.forEach(function (block) {
            Object.values(block.planQty).forEach(v => extraPlan += Number(v) || 0);
            Object.values(block.actualQty).forEach(v => extraActual += Number(v) || 0);
        });
        if (extraPlan > 0 || extraActual > 0) {
            const currentPlan = parseInt($('#pgTotal').text()) || 0;
            const currentActual = parseInt($('#pgTotalActual').text()) || 0;
            $('#pgTotal').text(currentPlan + extraPlan);
            $('#pgTotalActual').text(currentActual + extraActual);
        }
    }
</script>
 