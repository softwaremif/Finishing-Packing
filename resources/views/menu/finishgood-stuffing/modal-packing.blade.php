<div class="modal fade" id="packingModal" tabindex="-1" aria-labelledby="packingModalTitle" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">

            <form id="packingForm" method="POST">
                @csrf

                <input type="hidden" name="popk" value="{{ $popk }}">
                <input type="hidden" name="packpk" value="{{ $hsl->packpk ?? '' }}">
                <input type="hidden" name="cr" value="{{ $cr }}">

                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="packingModalTitle"
                        style="font-size: 15px;">
                        <span class="rounded me-2"
                            style="width: 4px; height: 16px; display: inline-block; background: #1e293b;"></span>
                        Input Packing Carton
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div id="packingAlert" class="alert alert-danger mx-3 mt-1 mb-0 py-2 px-3 d-none" role="alert"
                    style="font-size: 13px;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="packingAlertText"></span>
                </div>

                <div class="modal-body pt-3">
                    <div class="card border shadow-none rounded-3 mb-0">
                        <div class="card-body p-0 table-responsive">
                            <table class="table table-hover align-middle text-center mb-0 table-modern-form">
                                <thead style="font-size: 0.85rem;">
                                    <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                        <th style="min-width: 50px;" class="text-center px-2"></th>
                                        <th style="min-width: 120px;" class="text-start px-3">No. CTN</th>
                                        <th style="min-width: 150px;">No. Barcode</th>
                                        <th width="50" class="bg-light text-dark fw-bold">P/A</th>

                                        @foreach ($activeSizes as $i => $sz)
                                            <th style="min-width: 80px;" class="fw-semibold text-dark">
                                                {{ $sz }}
                                                <input type="hidden" name="size{{ $i }}" value="{{ $sz }}">
                                            </th>
                                        @endforeach

                                        <th style="min-width: 85px;">N.W</th>
                                        <th style="min-width: 85px;">G.W</th>
                                        <th style="min-width: 100px;">Meas <br> CTN</th>
                                        <th style="min-width: 180px;" class="text-start px-3">Keterangan</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {{-- BARIS PLAN (P) --}}
                                    <tr style="border-bottom: 1px dashed #e2e8f0;">
                                        <td rowspan="2" class="text-center border-end" style="background-color: #fafafa;">
                                            <div class="form-check d-inline-block text-start m-0 p-0" style="min-height: auto;">
                                                <input class="form-check-input m-0" type="checkbox" name="check"
                                                    id="autoSplitCheck" value="1"
                                                    {{ empty($hsl->packpk) ? 'checked' : '' }}
                                                    style="cursor: pointer; width: 1.15rem; height: 1.15rem;">
                                            </div>
                                        </td>

                                        <td rowspan="2" class="text-start px-3">
                                            <input type="text" name="nocar"
                                                class="form-control form-control-sm border-secondary-subtle"
                                                value="" placeholder="Masukkan No. CTN" style="border-radius: 5px;">
                                        </td>

                                        <td rowspan="2">
                                            <input type="text" name="nobar"
                                                class="form-control form-control-sm border-secondary-subtle"
                                                value="" placeholder="Scan Barcode" style="border-radius: 5px;">
                                        </td>

                                        <td class="bg-light text-dark fw-bold" style="font-size: 0.85rem;">P</td>

                                        @foreach ($activeSizes as $i => $sz)
                                            <td>
                                                @if (($orderQty[$i] ?? 0) > 0)
                                                    <input type="number" min="0" name="qty{{ $i }}p"
                                                        class="form-control form-control-sm text-center fw-medium border-secondary-subtle plan-input"
                                                        value="" data-index="{{ $i }}"
                                                        style="border-radius: 4px; max-width: 75px; margin: 0 auto;">
                                                @endif
                                            </td>
                                        @endforeach

                                        <td rowspan="2">
                                            <input type="text" name="nw"
                                                class="form-control form-control-sm border-secondary-subtle"
                                                value="" style="border-radius: 5px;">
                                        </td>

                                        <td rowspan="2">
                                            <input type="text" name="gw"
                                                class="form-control form-control-sm border-secondary-subtle"
                                                value="" style="border-radius: 5px;">
                                        </td>

                                        <td rowspan="2">
                                            <input type="text" name="meas"
                                                class="form-control form-control-sm border-secondary-subtle"
                                                value="" style="border-radius: 5px;">
                                        </td>

                                        <td rowspan="2" class="text-start px-3">
                                            <input type="text" name="ket2"
                                                class="form-control form-control-sm border-secondary-subtle"
                                                value="" placeholder="Catatan tambahan..." style="border-radius: 5px;">
                                        </td>
                                    </tr>

                                    {{-- BARIS ACTUAL (A) --}}
                                    <tr id="actualRow">
                                        <td class="bg-light text-dark fw-bold" style="font-size: 0.85rem;">A</td>

                                        @foreach ($activeSizes as $i => $sz)
                                            <td>
                                                @if (($orderQty[$i] ?? 0) > 0)
                                                    <input type="number" min="0" name="qty{{ $i }}"
                                                        class="form-control form-control-sm text-center actual-input"
                                                        data-index="{{ $i }}"
                                                        style="max-width: 75px; margin: auto; background: #fffbeb;">
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="autoSplitInfo" class="alert alert-warning mt-3 mb-0 py-2 px-3" style="font-size: 13px;">
                        <i class="far fa-check-square me-2 text-primary"></i>
                        <strong>Auto Split Carton.</strong><br>
                        Jika hanya <b>1 Size Plan</b> yang diisi, sistem akan otomatis membuat beberapa Carton
                        sesuai jumlah Order Qty pada size tersebut.
                    </div>
                </div>

                <div class="modal-footer border-top-0 pt-0 pb-3 pe-3">
                    <button type="button" class="btn btn-sm btn-light border fw-semibold text-secondary px-3 py-1.5"
                        style="border-radius: 6px; font-size: 13px;" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" id="btnSavePacking"
                        class="btn btn-sm btn-dark fw-semibold px-4 py-1.5 shadow-sm"
                        style="background-color: #1e293b; border-color: #1e293b; border-radius: 6px; font-size: 13px; letter-spacing: 0.3px;"
                        onclick="savePacking()">
                        <i class="fas fa-save me-1.5 small"></i> Save Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .table-modern-form input:focus,
    .table-modern-form select:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        outline: 0;
    }

    .table-modern-form tbody tr:hover {
        background-color: #ffffff !important;
    }

    .series-error {
        font-size: 12px;
    }
</style>

@if ($errors->any())
    <script>
        window.addEventListener('load', function () {
            let modalEl = document.getElementById('packingModal');
            let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            setTimeout(function () {
                let alertEl = document.getElementById('errorAlert');
                if (alertEl) {
                    alertEl.classList.remove('show');
                    alertEl.classList.add('hide');

                    setTimeout(() => {
                        alertEl.remove();
                    }, 300);
                }
            }, 3000);
        });
    </script>
@endif

<script>
    function showPackingAlert(message) {
        $('#packingAlertText').html(message);
        $('#packingAlert')
            .stop(true, true)
            .removeClass('d-none')
            .hide()
            .fadeIn(150);
    }

    function hidePackingAlert() {
        $('#packingAlert')
        .stop(true, true)
        .fadeOut(150, function () {
            $(this).addClass('d-none');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('packingModal');

        if (modalElement) {
            modalElement.addEventListener('hidden.bs.modal', function () {
                hidePackingAlert();

                // FIX: sebelumnya cari '#mainTransferForm' (ID form dari
                // modal LAIN, ke-copy-paste) -- form ID modal ini adalah
                // 'packingForm', jadi reset() sebelumnya TIDAK PERNAH
                // benar-benar jalan.
                const form = document.getElementById('packingForm');
                if (form) form.reset();

                modalElement.querySelectorAll('.is-invalid').forEach(function (input) {
                    input.classList.remove('is-invalid');
                });

                modalElement.querySelectorAll('.invalid-feedback').forEach(function (fb) {
                    fb.innerText = '';
                });

                modalElement.querySelectorAll('td .text-danger').forEach(function (error) {
                    if (error.innerText.trim() !== '*') {
                        error.innerText = '';
                    }
                });

                const globalAlert = document.getElementById('errorAlert');
                if (globalAlert) {
                    globalAlert.remove();
                }
            });
        }

        const inputTanggal = document.querySelector('input[name="tanggal"]');
        if (inputTanggal) {
            inputTanggal.addEventListener('input', function () {
                if (this.value.trim() !== '') clearError(this);
            });
        }

        const selectLine = document.querySelector('select[name="linepk"]');
        if (selectLine) {
            selectLine.addEventListener('change', function () {
                if (this.value.trim() !== '') clearError(this);
            });
        }

        function clearError(element) {
            element.classList.remove('is-invalid');
            const feedback = element.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.innerHTML = '';
            }
        }
    });
</script>