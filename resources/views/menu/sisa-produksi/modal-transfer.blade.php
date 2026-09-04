<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalTitle" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">

            <form id="mainTransferForm" onsubmit="return false;">
                @csrf

                <div class="modal-header py-3 bg-white border-bottom-0">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="transferModalTitle"
                        style="font-size: 15px;">
                        <span class="rounded me-2"
                            style="width: 4px; height: 16px; display: inline-block; background: #1e293b;"></span>
                        Detail Data Packing
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body pt-1 pb-3">

                    {{-- Alert error di-render dinamis via JS (showTransferAlert / hideTransferAlert) --}}
                    <div id="transferErrorPlaceholder"></div>

                    <div class="card border-0 bg-light-subtle rounded-3">
                        <div class="card-body p-0">
                            <div class="table-responsive" style="border-radius: 8px;">
                                <table class="table table-sm text-center align-middle mb-0 table-modern-form"
                                    style="min-width:1200px; font-size: 13px;">
                                    <thead class="table-light align-middle"
                                        style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; background-color: #f8fafc;">
                                        <tr>
                                            <th rowspan="2" class="fw-bold text-center align-middle px-3 py-2.5" style="width:150px;">Tanggal In</th>
                                            <th rowspan="2" class="fw-bold text-center align-middle px-3 py-2.5" style="width:150px;">Tanggal Out</th>
                                            <th colspan="{{ count($activeSizes) }}"
                                                class="fw-bold py-2.5 bg-light">
                                                Size
                                                @if (!empty($dt->secsz))
                                                    <span class="text-muted fw-bold text-lowercase">({{ $dt->secsz }})</span>
                                                @endif
                                            </th>
                                            <th rowspan="2" class="py-2.5 fw-bold" style="width:90px;">Grade</th>
                                            <th rowspan="2" class="py-2.5 fw-bold" style="width:220px;">Keterangan</th>
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
                                            {{-- TANGGAL IN (readonly, informasi saja — sudah terisi saat "Tandai Selesai") --}}
                                            <td class="px-3 py-3 align-top">
                                                <input type="text" id="displayTglin"
                                                    class="form-control form-control-sm text-center mx-auto"
                                                    style="max-width:140px; border-color: #cbd5e1; border-radius: 6px; height: 33px; background-color:#f1f5f9;"
                                                    readonly>
                                            </td>

                                            {{-- TANGGAL OUT --}}
                                            <td class="px-3 py-3 align-top">
                                                <input type="date" name="tglout"
                                                    class="form-control form-control-sm text-center mx-auto"
                                                    style="max-width:150px; border-color: #cbd5e1; border-radius: 6px; height: 33px;">
                                                <div class="invalid-feedback small text-start ps-1"></div>
                                            </td>

                                            @foreach ($activeSizes as $key => $size)
                                                <td class="py-3 align-top bg-light-subtle" id="sizeCol{{ $key }}">
                                                    <input type="number" name="qty{{ $key }}"
                                                        class="form-control form-control-sm text-center mx-auto d-none"
                                                        style="width:75px; border-color: #cbd5e1; border-radius: 6px; height: 33px;"
                                                        placeholder="0" min="0" onfocus="this.select();">
                                                    <div class="text-danger" style="font-size: 10px;"></div>
                                                    <span class="text-muted opacity-25 d-block pt-1 size-empty-placeholder">-</span>
                                                </td>
                                            @endforeach

                                            {{-- GRADE (readonly) --}}
                                            <td class="px-2 py-3 align-top">
                                                <input type="text" id="displayGrade"
                                                    class="form-control form-control-sm text-center mx-auto"
                                                    style="max-width:70px; border-color: #cbd5e1; border-radius: 6px; height: 33px; background-color:#f1f5f9;"
                                                    readonly>
                                            </td>

                                            {{-- KETERANGAN --}}
                                            <td class="px-2 py-3 align-top">
                                                <textarea name="keterangan" rows="2"
                                                    class="form-control form-control-sm"
                                                    style="min-width:200px; border-color: #cbd5e1; border-radius: 6px;"></textarea>
                                            </td>
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
                        <i class="fas fa-save me-1.5 small"></i> Simpan
                    </button>
                </div>

                <input type="hidden" name="bjpk" id="bjpk" value="">
                <input type="hidden" name="popk" value="{{ $dt->popk }}">

            </form>
        </div>
    </div>
</div>

<style>
    .table-modern-form input:focus,
    .table-modern-form select:focus,
    .table-modern-form textarea:focus {
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

    
    function saveTransferAjax() {
        hideTransferAlert();
        $('#mainTransferForm .is-invalid').removeClass('is-invalid');
        $('#mainTransferForm .invalid-feedback, #mainTransferForm td .text-danger').text('');

        const bjpk = $('#bjpk').val();
        if (!bjpk) {
            showTransferAlert('Data tidak valid (bjpk kosong).');
            return;
        }

        $.ajax({
            url: "{{ url('sisa-produksi') }}/" + bjpk + "/update-actual",
            method: 'POST',
            data: $('#mainTransferForm').serialize(),
            beforeSend: function () {
                $('#btnSaveTransfer').prop('disabled', true);
            },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('transferModal'))?.hide();

                // reload datagrid & breakdown summary, TANPA reload halaman
                reloadTransferGrid();
                reloadBreakdownSummary();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};

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
                    const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
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

                modalElement.querySelectorAll('[id^="sizeCol"] input[type="number"]').forEach(function (inp) {
                    inp.value = '';
                    inp.classList.add('d-none');
                });
                modalElement.querySelectorAll('[id^="sizeCol"] .size-empty-placeholder').forEach(function (ph) {
                    ph.classList.remove('d-none');
                });

                hideTransferAlert();
            });
        }
    });
</script>