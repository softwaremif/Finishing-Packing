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
                            Add Transfer to Finishing
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
                                            <th rowspan="2" class="fw-bold text-center align-middle px-3 py-2.5" style="width:170px;">Tanggal <span class="text-danger">*</span></th>
                                            <th rowspan="2" class="py-2.5 fw-bold" style="width:150px;">Line
                                            <span class="text-danger">*</span></th>
                                            <th colspan="{{ count($sizes) }}"
                                                class="fw-bold py-2.5 bg-light">
                                                Size
                                                @if (!empty($mop->secsz))
                                                    <span class="text-muted fw-bold text-lowercase">({{ $mop->secsz }})</span>
                                                @endif
                                            </th>
                                        </tr>
                                        <tr>
                                            @foreach ($sizes as $s)
                                                <th class="fw-bold bg-white border-bottom-0"
                                                    style="min-width:85px; font-size: 12px;">{{ $s->ukuran }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="bg-white">
                                            {{-- TANGGAL --}}
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

                                            {{-- SIZE INPUT -- statis dari $sizes (mopdt), sama pola dengan
                                                 modal Polibag ($activeSizes). Kolom tanpa Order Qty (0)
                                                 ditampilkan strip, konsisten dengan pola Polibag. --}}
                                            @foreach ($sizes as $s)
                                                <td class="py-3 align-top bg-light-subtle">
                                                    @if (($orderQty[$s->mopdtpk] ?? 0) > 0)
                                                        <input type="number" name="qty_{{ $s->mopdtpk }}"
                                                            class="form-control form-control-sm text-center mx-auto qtyinput"
                                                            data-mopdtpk="{{ $s->mopdtpk }}"
                                                            data-ukuran="{{ $s->ukuran }}"
                                                            style="width:75px; border-color: #cbd5e1; border-radius: 6px; height: 33px;"
                                                            placeholder="0" min="0" onfocus="this.select();"
                                                            oninput="hitungTotalQty()">
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

                    {{-- <div class="mt-3 text-end text-secondary" style="font-size:13px;">
                        Total Qty: <strong id="totQtyDisplay">0</strong>
                    </div> --}}

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

                <input type="hidden" name="tfpbpk" id="tfpbpk" value="">
                <input type="hidden" name="popk" value="{{ $popk }}">

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
    // TOTAL QTY -- dihitung dari semua input .qtyinput yang terisi
    // ============================================================
    function hitungTotalQty() {
        let total = 0;
        $('#mainTransferForm .qtyinput').each(function () {
            const v = parseFloat($(this).val());
            if (!isNaN(v)) total += v;
        });
        $('#totQtyDisplay').text(total);
        return total;
    }

    // ============================================================
    // KUMPULKAN PAYLOAD 'sizes' -- dari input statis qty_{mopdtpk}
    // ============================================================
    function collectSizesPayload() {
        const sizes = [];
        $('#mainTransferForm .qtyinput').each(function () {
            const raw = $(this).val();
            const qty = (raw === '' || raw === null || raw === undefined)
                ? null
                : (parseFloat(raw) || 0);
    
            sizes.push({
                mopdtpk: $(this).data('mopdtpk'),
                ukuran: $(this).data('ukuran'),
                qty: qty
            });
        });
        return sizes;
    }

    // ============================================================
    // BUKA MODAL ADD
    // ============================================================
    function openTransferModal() {
        hideTransferAlert();
        $('#transferModalTitle').text('Add Transfer to Finishing');
        $('#tfpbpk').val('');
        const form = document.getElementById('mainTransferForm');
        if (form) form.reset();
        $('#mainTransferForm input[name="tanggal"]').val('');
        $('#mainTransferForm select[name="linepk"]').val('');
        $('#mainTransferForm .qtyinput').val('');
        $('#mainTransferForm .is-invalid').removeClass('is-invalid');
        $('#mainTransferForm .invalid-feedback, #mainTransferForm td .text-danger').text('');
        hitungTotalQty();
        setTanggalRangeForAdd();
    
        // BARU -- FIX UTAMA: langsung isi tanggal hari ini (bukan dibiarkan
        // kosong) -- todayDateString() sudah ada, tinggal dipakai.
        $('#mainTransferForm input[name="tanggal"]').val(todayDateString());
    
        bootstrap.Modal.getOrCreateInstance(document.getElementById('transferModal')).show();
    }

    function fillEditForm(row) {
        hideTransferAlert();
        $('#mainTransferForm .is-invalid').removeClass('is-invalid');
        $('#mainTransferForm .invalid-feedback, #mainTransferForm td .text-danger').text('');

        $('#transferModalTitle').text('Edit Transfer to Finishing');
        $('#tfpbpk').val(row.tfpbpk);

        const tglOnly = String(row.tanggal).split(' ')[0];
        $('#mainTransferForm input[name="tanggal"]').val(tglOnly);
        $('#mainTransferForm select[name="linepk"]').val(row.linepk);

        $('#mainTransferForm .qtyinput').each(function () {
            const mopdtpk = $(this).data('mopdtpk');
            $(this).val(row['qty_' + mopdtpk] ?? '');
        });
        hitungTotalQty();

        // BARU: WAJIB dipanggil.
        setTanggalRangeForEdit(tglOnly);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('transferModal')).show();
    }

    function saveTransferAjax() {
        hideTransferAlert();
        $('#mainTransferForm .is-invalid').removeClass('is-invalid');
        $('#mainTransferForm .invalid-feedback, #mainTransferForm td .text-danger').text('');

        const tanggal = $('#mainTransferForm input[name="tanggal"]').val();
        const linepk  = $('#mainTransferForm select[name="linepk"]').val();

        if (!tanggal) {
            $('#mainTransferForm input[name="tanggal"]').addClass('is-invalid')
                .siblings('.invalid-feedback').text('Tanggal wajib diisi.');
        }
        if (!linepk) {
            $('#mainTransferForm select[name="linepk"]').addClass('is-invalid')
                .siblings('.invalid-feedback').text('Line wajib dipilih.');
        }
        if (!tanggal || !linepk) {
            showTransferAlert('Tanggal dan Line wajib diisi.');
            return;
        }

        // BARU: guard terakhir sebelum submit (lapis ke-3), tanpa ini user
        // masih bisa lolos kalau min/max attribute di-bypass manual.
        let range = null;
        if (currentDateMode === 'add') {
            range = getAddDateRange();
        } else if (currentDateMode === 'edit' && currentEditStoredDate) {
            range = getEditDateRange(currentEditStoredDate);
        }
        if (range) {
            const { min, max } = range;
            if (tanggal < min || tanggal > max || isSundayDate(tanggal)) {
                showTransferAlert(`Tanggal harus di antara <b>${min}</b> dan <b>${max}</b>, dan tidak boleh hari Minggu.`);
                $('#mainTransferForm input[name="tanggal"]').addClass('is-invalid');
                return;
            }
        }

        $.ajax({
            url: "{{ route('tf_finishing.save') }}",
            method: 'POST',
            data: {
                tfpbpk: $('#tfpbpk').val(),
                popk: {{ $popk }},
                mif: currentMif,
                tanggal: tanggal,
                linepk: linepk,
                sizes: collectSizesPayload()
            },
            beforeSend: function () { $('#btnSaveTransfer').prop('disabled', true); },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('transferModal'))?.hide();
                reloadTransferGrid();
                reloadBreakdownSummary();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    const hasFieldErrors = Object.keys(errors).length > 0;
                    if (hasFieldErrors) {
                        Object.keys(errors).forEach(function (field) {
                            const input = $(`#mainTransferForm [name="${field}"]`);
                            input.addClass('is-invalid');
                            const feedback = input.next('.invalid-feedback, .text-danger');
                            if (feedback.length) feedback.text(errors[field][0]);
                        });
                        showTransferAlert(Object.values(errors).flat().join('<br>'));
                    } else {
                        const res = xhr.responseJSON || { title: 'Periksa kembali data yang diisi.' };
                        showTransferAlert(res.title);
                    }
                } else {
                    const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    showToast(res.icon, res.title);
                    showTransferAlert(res.title);
                }
            },
            complete: function () { $('#btnSaveTransfer').prop('disabled', false); }
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