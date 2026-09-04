<div class="modal fade" id="packingGlobalModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">

            <div class="modal-header border-0 pb-1">
                <div>
                    <h5 class="fw-bold text-dark mb-0">New carton</h5>
                    <div class="text-secondary" style="font-size: 13px;">
                        Add color/size breakdown lines. Quantities update the coverage matrix live.
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

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="text-secondary mb-0" style="font-size: 12px;">Breakdown</label>
                    <span class="text-secondary" style="font-size: 12px;">Pieces</span>
                </div>
                <div id="pgBreakdownLines"></div>
                <button type="button" class="btn btn-link btn-sm px-0 text-decoration-none" onclick="addBreakdownLine()">
                    <i class="fas fa-plus me-1"></i> Add breakdown line
                </button>

                <hr class="my-3">

                <div class="row g-3">
                    <div class="col-6">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">No. Barcode</label>
                        <input type="text" id="pgNobar" class="form-control" placeholder="Scan Barcode">
                    </div>
                    <div class="col-3">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">N.W</label>
                        <input type="text" id="pgNw" class="form-control">
                    </div>
                    <div class="col-3">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">G.W</label>
                        <input type="text" id="pgGw" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">Meas CTN</label>
                        <input type="text" id="pgMeas" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="text-secondary d-block mb-1" style="font-size: 12px;">Keterangan</label>
                        <input type="text" id="pgKet2" class="form-control" placeholder="Catatan tambahan...">
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 d-flex justify-content-between align-items-center">
                <div style="font-size: 14px;">
                    <span class="text-secondary">Carton total</span>
                    <strong id="pgTotal" class="ms-1">0</strong> <span class="text-secondary">pcs</span>
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
    .pg-breakdown-line select,
    .pg-breakdown-line input {
        border-radius: 8px;
    }
    .pg-remove-line {
        color: #cbd5e1;
        cursor: pointer;
        font-size: 16px;
        padding: 0 4px;
    }
    .pg-remove-line:hover { color: #ef4444; }
</style>

<script>
    // window.pgCombos & window.pgSizes disediakan dari controller
    // (lihat catatan di bawah), dipakai untuk mengisi dropdown.
    let pgLineCounter = 0;

    function pgComboOptions() {
        let html = '<option value="">Pilih Color / Sec Size</option>';
        (window.pgCombos || []).forEach(function (c) {
            let label = c.secsz ? `${c.material} - ${c.secsz}` : c.material;
            html += `<option value="${c.material}||${c.secsz ?? ''}">${label}</option>`;
        });
        return html;
    }

    function pgSizeOptions() {
        let html = '<option value="">Size</option>';
        Object.keys(window.pgSizes || {}).forEach(function (i) {
            html += `<option value="${i}">${window.pgSizes[i]}</option>`;
        });
        return html;
    }

    function addBreakdownLine() {
        pgLineCounter++;
        const html = `
            <div class="d-flex gap-2 align-items-center mb-2 pg-breakdown-line" data-line="${pgLineCounter}">
                <select class="form-select pg-combo" style="flex:2;" onchange="recalcPackTypeGlobal()">${pgComboOptions()}</select>
                <select class="form-select pg-size" style="flex:1;" onchange="recalcPackTypeGlobal()">${pgSizeOptions()}</select>
                <input type="number" min="0" class="form-control pg-qty" style="width:90px;" placeholder="0" oninput="recalcPackTypeGlobal()">
                <span class="pg-remove-line" onclick="removeBreakdownLine(this)"><i class="fas fa-minus-circle"></i></span>
            </div>
        `;
        $('#pgBreakdownLines').append(html);
        recalcPackTypeGlobal();
    }

    function removeBreakdownLine(el) {
        $(el).closest('.pg-breakdown-line').remove();
        recalcPackTypeGlobal();
    }

    function recalcPackTypeGlobal() {
        let total = 0;
        let combos = new Set();

        $('.pg-breakdown-line').each(function () {
            const combo = $(this).find('.pg-combo').val();
            const qty   = parseInt($(this).find('.pg-qty').val()) || 0;
            if (combo && qty > 0) {
                combos.add(combo);
                total += qty;
            }
        });

        $('#pgTotal').text(total);

        $('.pg-packtype-badge').removeClass('active');
        if (combos.size > 1) {
            $('#pgTypeMixed').addClass('active');
        } else if (combos.size === 1) {
            const lineCountForCombo = $('.pg-breakdown-line').filter(function () {
                const combo = $(this).find('.pg-combo').val();
                const qty   = parseInt($(this).find('.pg-qty').val()) || 0;
                return combo && qty > 0;
            }).length;
            if (lineCountForCombo > 1) {
                $('#pgTypeAssorted').addClass('active');
            } else {
                $('#pgTypeSolid').addClass('active');
            }
        }
    }

    function openPackingGlobalModal() {
        $('#pgBreakdownLines').empty();
        $('#pgCarton, #pgNobar, #pgNw, #pgGw, #pgMeas, #pgKet2').val('');
        $('.pg-packtype-badge').removeClass('active');
        $('#pgTotal').text('0');
        pgHideAlert();
        addBreakdownLine();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('packingGlobalModal')).show();
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
        $('.pg-breakdown-line').each(function () {
            const combo = $(this).find('.pg-combo').val();
            const size  = $(this).find('.pg-size').val();
            const qty   = parseInt($(this).find('.pg-qty').val()) || 0;

            if (combo && size && qty > 0) {
                const [material, secsz] = combo.split('||');
                breakdown.push({ material: material, secsz: secsz, size: size, qty: qty });
            }
        });

        if (!breakdown.length) {
            pgShowAlert('Minimal satu baris breakdown (Color/Sec Size + Size + Qty) harus diisi.');
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
                meas: $('#pgMeas').val(),
                ket2: $('#pgKet2').val(),
                breakdown: breakdown
            },
            beforeSend: function () {
                $('#btnAddCartonGlobal').prop('disabled', true);
            },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('packingGlobalModal')).hide();
                loadPackingCards();
                reloadBreakdownSummary();
            },
            error: function (xhr) {
                let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                pgShowAlert(res.title);
            },
            complete: function () {
                $('#btnAddCartonGlobal').prop('disabled', false);
            }
        });
    }
</script>