<div class="modal fade" id="segelPackingModal" tabindex="-1" aria-labelledby="segelPackingModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">

            {{-- HEADER --}}
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="segelPackingModalTitle" style="font-size: 16px;">
                    Shipment CTN
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- BODY --}}
            <div class="modal-body px-4 pt-3 pb-4">

                {{-- Info PO & OP --}}
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <label class="form-label-custom">
                            <i class="fas fa-file-alt me-1 text-muted"></i> PO
                        </label>
                        <input type="text" id="infoPO" class="form-control form-control-modern bg-light border-0 fw-semibold" readonly>
                    </div>

                    <div class="col-6">
                        <label class="form-label-custom">
                            <i class="fas fa-layer-group me-1 text-muted"></i> OP
                        </label>
                        <input type="text" id="opInfo" class="form-control form-control-modern bg-light border-0 fw-semibold" readonly>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label-custom" for="shipmentDate">Actual Shipment <span class="text-danger">*</span></label>               
                    <input type="date" class="form-control form-control-modern" id="shipmentDate" name="shipmentDate">
                </div>
                
                <div class="mb-2">
                    <label class="form-label-custom">Jenis Shipment</label>
                </div>

                {{-- Container Radio Button --}}
                <div class="d-flex flex-column gap-2">

                    <label class="shipment-card-option p-3 border rounded d-flex align-items-center justify-content-between m-0" for="shipmentFull">
                        <div class="d-flex align-items-center gap-3">
                            <input class="form-check-input m-0 shadow-none" type="radio" name="shipment_type" id="shipmentFull" value="full" checked>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark mb-0 small">Complete Sipment</span>
                                <small class="text-muted" style="font-size: 11px;">Sipment seluruh kuantitas carton sekaligus.</small>
                            </div>
                        </div>
                    </label>

                    <label class="shipment-card-option p-3 border rounded d-flex flex-column m-0" for="shipmentPartial">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <div class="d-flex align-items-center gap-3">
                                <input class="form-check-input m-0 shadow-none" type="radio" name="shipment_type" id="shipmentPartial" value="partial">
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark mb-0 small">Partial Sipment</span>
                                    <small class="text-muted" style="font-size: 11px;">Sipment carton bertahap.</small>
                                </div>
                            </div>
                        </div>

                        {{-- Area Pilihan Dropdown Partial --}}
                        <div id="partialArea" class="mt-3 pt-3 border-top" style="display:none">
                            <div class="row align-items-center">
                                <div class="col-5">
                                    <label class="form-label-custom m-0 text-dark">Partial Ke- :</label>
                                </div>
                                <div class="col-7">
                                    <select class="form-select form-control-modern py-1" id="partialNo" name="partialNo">
                                        @for($i=1; $i<=10; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                    <small class="text-muted d-block mt-1" id="partialNoHint" style="font-size: 11px;"></small>
                                </div>
                            </div>
                        </div>
                    </label>

                </div>

            </div>

            {{-- FOOTER --}}
            <div class="modal-footer border-0 pt-0 pb-4 px-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-3 d-inline-flex align-items-center justify-content-center" data-bs-dismiss="modal" style="font-size: 13px; border-radius: 6px; height: 36px; min-width: 85px;">
                    Cancel
                </button>
                <button type="button" id="btnSaveSegelPacking" class="btn btn-dark px-4 d-inline-flex align-items-center justify-content-center gap-2 shadow-none" onclick="saveSegelPacking()" style="font-size: 13px; border-radius: 6px; height: 36px; background-color: #0f172a; border-color: #0f172a;">
                    <i class="fas fa-save small"></i> Save
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
        color: #64748b;
        margin-bottom: 6px;
        display: block;
    }

    .form-control-modern {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 13.5px;
        color: #1e293b;
        padding: 5px 12px;
        height: 36px;
        transition: all 0.2s ease;
    }

    .form-control-modern:focus {
        border-color: #475569 !important;
        box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.12) !important;
        outline: 0;
    }

    .shipment-card-option {
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        background-color: #fff;
    }

    .shipment-card-option:hover {
        background-color: #f8fafc;
        border-color: #94a3b8 !important;
    }

    .shipment-card-option:has(input[type="radio"]:checked) {
        border-color: #0f172a !important;
        background-color: #f8fafc;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
    }

    .form-check-input:checked {
        background-color: #0f172a;
        border-color: #0f172a;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const radioFull = document.getElementById('shipmentFull');
        const radioPartial = document.getElementById('shipmentPartial');
        const partialArea = document.getElementById('partialArea');
        const partialSelect = document.getElementById('partialNo');

        function togglePartialArea() {
            if (radioPartial.checked) {
                partialArea.style.display = 'block';
            } else {
                partialArea.style.display = 'none';
            }
        }

        radioFull.addEventListener('change', togglePartialArea);
        radioPartial.addEventListener('change', togglePartialArea);

        window.disableUsedPartials = function (rows) {
            const usedPartials = new Set();

            rows.forEach(function (row) {
                const raw = row.segel_partial_no;
                if (!raw) return;

                String(raw).split(',').forEach(function (p) {
                    const n = parseInt(p.trim(), 10);
                    if (!isNaN(n)) usedPartials.add(n);
                });
            });

            let firstAvailable = null;

            $(partialSelect).find('option').each(function () {
                const val = parseInt($(this).val(), 10);
                const isUsed = usedPartials.has(val);

                $(this).prop('disabled', isUsed);

                if (!isUsed && firstAvailable === null) {
                    firstAvailable = val;
                }
            });

            if (usedPartials.size >= 10) {
                $(radioPartial).prop('disabled', true);
                $(radioPartial).closest('.shipment-card-option').addClass('opacity-50')
                    .attr('title', 'Semua partial (1-10) sudah digunakan untuk PO ini.');

                radioFull.checked = true;
                togglePartialArea();
            } else {
                $(radioPartial).prop('disabled', false);
                $(radioPartial).closest('.shipment-card-option').removeClass('opacity-50')
                    .removeAttr('title');

                if (firstAvailable !== null) {
                    $(partialSelect).val(firstAvailable);
                }
            }
        };
    });
</script>