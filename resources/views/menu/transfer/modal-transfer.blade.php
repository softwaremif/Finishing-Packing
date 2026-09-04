<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalTitle" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">

            <form id="mainTransferForm" onsubmit="return false;">
                @csrf
                <div class="modal-header py-3 bg-white border-bottom-0 align-items-start">
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center mb-1" id="transferModalTitle"
                            style="font-size: 15px;">
                            <span class="rounded me-2"
                                style="width: 4px; height: 16px; display: inline-block; background: #1e293b;"></span>
                            Add Transfer
                        </h5>
                        <div class="d-flex flex-wrap align-items-center gap-1" style="font-size:12px; color:#64748b;">
                            <span>PO <strong class="text-dark">{{ $dt->POno ?? '-' }}</strong></span>
                            <span class="text-secondary opacity-50">&middot;</span>
                            <span>OP <strong class="text-dark">{{ $dt->OP ?? '-' }}</strong></span>
                            <span class="text-secondary opacity-50">&middot;</span>
                            <span>{{ $dt->buyer ?? '-' }}</span>
                            <span class="text-secondary opacity-50">&middot;</span>
                            <span>{{ $dt->material ?? '-' }}</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close shadow-none mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-1 pb-3">

                    {{-- Alert error di-render dinamis via JS (showTransferAlert / hideTransferAlert) --}}
                    <div id="transferErrorPlaceholder"></div>

                    <div class="card border-0 bg-light-subtle rounded-3">
                        <div class="card-body p-0">
                            <div class="table-responsive" style="border-radius: 8px;">
                                <table class="table table-sm text-center align-middle mb-0 table-modern-form"
                                    style="min-width:1300px; font-size: 13px;">
                                    <thead class="table-light align-middle"
                                        style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; background-color: #f8fafc;">
                                        <tr>
                                            <th rowspan="2" class="fw-bold text-center align-middle px-3 py-2.5" style="width:170px;">Tanggal <br> Masuk <span class="text-danger">*</span></th>
                                            <th rowspan="2" class="py-2.5 fw-bold" style="width:150px;">Line
                                            <span class="text-danger">*</span></th>
                                            @if ($isStokSisaMode)
                                                <th rowspan="2" class="py-2.5 fw-bold" style="width:110px;">Grade <span class="text-danger">*</span></th>
                                            @endif
                                            <th colspan="{{ count($activeSizes) }}"
                                                class="fw-bold py-2.5 bg-light">
                                                Size
                                                @if (!empty($dt->secsz))
                                                    <span class="text-muted fw-bold text-lowercase">({{ $dt->secsz }})</span>
                                                @endif
                                            </th>
                                        </tr>
                                        <tr>
                                            @foreach ($activeSizes as $size)
                                                <th class="fw-bold bg-white border-bottom-0"
                                                    style="min-width:85px; font-size: 12px;">{{ $size }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="bg-white">
                                            {{-- TANGGAL MASUK --}}
                                            <td class="px-3 py-3 align-top">
                                                <input type="date" name="tanggal"
                                                    class="form-control form-control-sm text-center mx-auto"
                                                    style="max-width:150px; border-color: #cbd5e1; border-radius: 6px; height: 33px;">
                                                <div class="invalid-feedback small text-start ps-1"></div>
                                            </td>

                                            {{-- LINE --}}
                                            <td class="py-3 align-top">
                                                <select name="linepk"
                                                    class="form-select form-select-sm text-center mx-auto"
                                                    style="max-width:140px; border-color: #cbd5e1; border-radius: 6px; height: 33px;">
                                                    <option value="" selected></option>
                                                    @foreach ($lines as $line)
                                                        <option value="{{ $line->linepk }}">
                                                            {{ $line->linenm }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback small text-start ps-2"></div>
                                            </td>

                                            {{-- GRADE — cuma untuk guserpk 35, konsisten dengan header di atas --}}
                                            @if ($isStokSisaMode)
                                                <td class="py-3 align-top">
                                                    <select name="grade"
                                                        class="form-select form-select-sm text-center mx-auto"
                                                        style="max-width:100px; border-color: #cbd5e1; border-radius: 6px; height: 33px;">
                                                        <option value="" selected>-</option>
                                                        <option value="A">A</option>
                                                        <option value="B">B</option>
                                                        <option value="C">C</option>
                                                    </select>
                                                    <div class="invalid-feedback small text-start ps-2"></div>
                                                </td>
                                            @endif

                                            {{-- SIZE INPUT --}}
                                            @foreach ($activeSizes as $key => $size)
                                                <td class="py-3 align-top bg-light-subtle">
                                                    @if (($orderQty[$key] ?? 0) > 0)
                                                        <input type="number" name="qty{{ $key }}"
                                                            class="form-control form-control-sm text-center mx-auto"
                                                            style="width:75px; border-color: #cbd5e1; border-radius: 6px; height: 33px;"
                                                            placeholder="0" min="0" onfocus="this.select();">
                                                        <div class="text-danger" style="font-size: 10px;"></div>
                                                    @else
                                                        <span class="text-muted opacity-25 d-block pt-1">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-top-0 pt-0 pb-3 pe-3">
                    <button type="button" class="btn btn-sm btn-light border fw-semibold text-secondary px-3 py-1.5"
                        style="border-radius:6px; font-size:13px;" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" id="btnSaveTransfer" class="btn btn-sm btn-dark fw-semibold px-4 py-1.5 shadow-sm"
                        style="background-color: #1e293b; border-color: #1e293b; border-radius:6px; font-size:13px; letter-spacing: 0.3px;"
                        onclick="saveTransferAjax()">
                        <i class="fas fa-save me-1.5 small"></i> Save Data
                    </button>
                </div>

                <input type="hidden" name="bjpk" id="bjpk" value="">
                <input type="hidden" name="popk" value="{{ $dt->popk }}">
                <input type="hidden" name="po" value="{{ $dt->POno }}">
                <input type="hidden" name="op" value="{{ $dt->OP }}">
                <input type="hidden" name="mif" value="{{ $mif }}">
                <input type="hidden" name="stok_sisa_mode" value="{{ $isStokSisaMode ? 1 : 0 }}">

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

<script>
    // ============================================================
    // ALERT ERROR (dinamis, tanpa perlu $errors->any() dari server)
    // ============================================================
    function showTransferAlert(message) {
        hideTransferAlert();
        const html = `
            <div id="transferErrorAlert"
                class="alert alert-danger shadow-sm py-2.5 px-3 small d-flex align-items-start mb-3"
                role="alert">
                <i class="fas fa-exclamation-circle me-2 mt-1 fs-6 flex-shrink-0"></i>
                <div class="flex-grow-1">${message}</div>
                <button type="button" class="btn-close shadow-none p-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('#transferErrorPlaceholder').html(html);
    }
    function hideTransferAlert() {
        $('#transferErrorPlaceholder').empty();
    }

    // ============================================================
    // SUBMIT VIA AJAX (Add & Edit Polibag) — TANPA RELOAD HALAMAN
    // ============================================================
    function saveTransferAjax() {
        hideTransferAlert();
        $('#mainTransferForm .is-invalid').removeClass('is-invalid');
        $('#mainTransferForm .invalid-feedback, #mainTransferForm td .text-danger').text('');

        // Lapis ke-3: guard terakhir sebelum submit -- validasi Tanggal Masuk
        // sesuai mode (Add/Edit) sebelum data dikirim ke server.
        const tglInput = document.querySelector('#mainTransferForm input[name="tanggal"]');
        if (tglInput && tglInput.value) {
            let range = null;
            if (currentDateMode === 'add') {
                range = getAddDateRange();
            } else if (currentDateMode === 'edit' && currentEditStoredDate) {
                range = getEditDateRange(currentEditStoredDate);
            }

            if (range) {
                const { min, max } = range;
                if (tglInput.value < min || tglInput.value > max || isSundayDate(tglInput.value)) {
                    showTransferAlert(
                        `Tanggal Masuk harus di antara <b>${min}</b> dan <b>${max}</b>, dan tidak boleh hari Minggu.`
                    );
                    tglInput.classList.add('is-invalid');
                    return;
                }
            }
        }

        $.ajax({
            url: "{{ route('transfer.save') }}",
            method: 'POST',
            data: $('#mainTransferForm').serialize(),
            beforeSend: function () {
                $('#btnSaveTransfer').prop('disabled', true);
            },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('transferModal'))?.hide();

                // Add/Edit sukses -> reload tabel Polibag + Breakdown Size & Qty
                reloadTransferGrid();
                reloadBreakdownSummary();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    const hasFieldErrors = Object.keys(errors).length > 0;

                    if (hasFieldErrors) {
                        // Error validasi PER FIELD (misal tanggal/linepk/grade kosong)
                        Object.keys(errors).forEach(function (field) {
                            const input = $(`#mainTransferForm [name="${field}"]`);
                            input.addClass('is-invalid');
                            const feedback = input.next('.invalid-feedback, .text-danger');
                            if (feedback.length) {
                                feedback.text(errors[field][0]);
                            }
                        });
                        const firstMsg = Object.values(errors).flat().join('<br>');
                        showTransferAlert(firstMsg);
                    } else {
                        // Error 422 TANPA breakdown per field (misal melebihi
                        // Transfer to Finishing) -- server cuma kirim 'title'.
                        const res = xhr.responseJSON || { title: 'Periksa kembali data yang diisi.' };
                        showTransferAlert(res.title);
                    }
                } else {
                    // Error selain 422 (500, network, dll) -- tampilkan via toast
                    // DAN alert, supaya tidak mudah terlewat di dalam modal.
                    const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    showToast(res.icon, res.title);
                    showTransferAlert(res.title);
                }
            },
            complete: function () {
                $('#btnSaveTransfer').prop('disabled', false);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('transferModal');
        if (modalElement) {
            modalElement.addEventListener('hidden.bs.modal', function () {
                const form = document.getElementById('mainTransferForm');
                if (form) form.reset();

                modalElement.querySelectorAll('.is-invalid').forEach(function (input) {
                    input.classList.remove('is-invalid');
                });

                modalElement.querySelectorAll('.invalid-feedback, td .text-danger').forEach(function (fb) {
                    fb.innerText = '';
                });

                hideTransferAlert();
            });
        }

        const inputTanggal = document.querySelector('#mainTransferForm input[name="tanggal"]');
        if (inputTanggal) {
            inputTanggal.addEventListener('input', function () {
                if (this.value.trim() !== '') clearError(this);
            });
        }

        const selectLine = document.querySelector('#mainTransferForm select[name="linepk"]');
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