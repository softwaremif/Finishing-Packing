<div class="modal fade" id="urutkanCtnGlobalModal"
     tabindex="-1"
     aria-labelledby="urutkanCtnGlobalModalTitle"
     aria-hidden="true"
     data-bs-backdrop="static"
     data-bs-keyboard="false">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"
                    id="urutkanCtnGlobalModalTitle"
                    style="font-size:16px;">
                    Urutkan Carton / Barcode
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
            </div>

            <div id="urutAlertGlobal" class="alert alert-danger mx-3 mt-1 mb-0 py-2 px-3 d-none" style="font-size: 13px;">
                <i class="fas fa-exclamation-triangle me-2"></i><span id="urutAlertGlobalText"></span>
            </div>

            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label-custom d-block mb-1">Urutkan Berdasarkan</label>
                    <div class="d-flex gap-2">
                        <span class="pg-packtype-badge active" id="urutModeCartonBtn"
                            onclick="setUrutModeGlobal('carton')" style="cursor:pointer;">
                            <i class="fas fa-box me-1"></i> Nomor Carton
                        </span>
                        <span class="pg-packtype-badge" id="urutModeBarcodeBtn"
                            onclick="setUrutModeGlobal('barcode')" style="cursor:pointer;">
                            <i class="fas fa-barcode me-1"></i> Barcode
                        </span>
                    </div>
                </div>

                {{-- ===================== MODE: CARTON ===================== --}}
                <div id="urutPanelCarton">
                    <div class="mb-2">
                        <label class="form-label-custom">Nomor Carton Awal</label>
                        <input type="text" id="urutCtnAwalGlobal" class="form-control form-control-modern"
                            placeholder="Contoh: CTN-001 / A001 / 000001">
                    </div>

                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" id="urutPairBarcodeGlobal"
                            onchange="toggleUrutPairBarcodeGlobal()">
                        <label class="form-check-label" for="urutPairBarcodeGlobal" style="font-size:12.5px;">
                            Urutkan Carton &amp; Barcode sekaligus (biar keduanya sinkron)
                        </label>
                    </div>

                    <div class="mb-2 d-none" id="urutPairBarcodeInputWrapper">
                        <label class="form-label-custom">Barcode Awal</label>
                        <input type="text" id="urutPairBarcodeAwalGlobal" class="form-control form-control-modern"
                            placeholder="Contoh: BC-005">
                        <div class="text-muted mt-1" style="font-size:11.5px;">
                            Misal Carton mulai <strong class="text-dark">CTN-005</strong> &amp; Barcode mulai
                            <strong class="text-dark">BC-005</strong> -- maka carton CTN-005 pasti dapat
                            barcode BC-005, carton berikutnya dapat barcode berikutnya, dst (selalu berpasangan urut).
                        </div>
                    </div>

                    <div class="rounded bg-light border p-2 text-muted mt-2" style="font-size:12px;" id="urutInfoCartonText">
                        <i class="fas fa-info-circle me-1"></i>
                        Nomor Carton akan diurutkan mulai dari nomor awal yang dimasukkan. <strong class="text-dark">Barcode
                        TIDAK ikut berubah</strong> (kecuali centang opsi di atas).
                    </div>
                </div>

                {{-- ===================== MODE: BARCODE ===================== --}}
                <div id="urutPanelBarcode" class="d-none">
                    <div class="mb-2">
                        <label class="form-label-custom d-block mb-1">Cara Mulai</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="urutBarcodeSubMode" id="urutSubModeManual"
                                value="manual" onchange="setUrutBarcodeSubModeGlobal('manual')">
                            <label class="form-check-label" for="urutSubModeManual" style="font-size:13px;">
                                Manual -- tentukan sendiri carton mana yang jadi patokan awal
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="urutBarcodeSubMode" id="urutSubModeOtomatis"
                                value="otomatis" checked onchange="setUrutBarcodeSubModeGlobal('otomatis')">
                            <label class="form-check-label" for="urutSubModeOtomatis" style="font-size:13px;">
                                Otomatis -- urutkan semua carton dari nomor terkecil ke terbesar
                            </label>
                        </div>
                    </div>

                    <div class="mb-2 d-none" id="urutBarcodeCartonAwalWrapper">
                        <label class="form-label-custom">Nomor Carton Awal (harus sudah ada di sistem)</label>
                        <input type="text" id="urutBarcodeCartonAwalGlobal" class="form-control form-control-modern"
                            placeholder="Contoh: CTN-005">
                    </div>

                    <div class="mb-2">
                        <label class="form-label-custom">Barcode Awal</label>
                        <input type="text" id="urutBarcodeAwalGlobal" class="form-control form-control-modern"
                            placeholder="Contoh: BC-001">
                    </div>

                    <div class="rounded bg-light border p-2 text-muted mt-2" style="font-size:12px;" id="urutInfoBarcodeText">
                        <i class="fas fa-info-circle me-1"></i>
                        <span id="urutInfoBarcodeTextInner">
                            Sistem akan otomatis mengurutkan SEMUA carton dari nomor terkecil ke terbesar, lalu
                            memberi barcode berurutan mulai dari nilai di atas. Nomor Carton <strong class="text-dark">TIDAK</strong> ikut berubah.
                        </span>
                    </div>
                </div>

            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" id="btnSaveUrutCtnGlobal" class="btn btn-sm btn-dark px-4"
                    style="background-color:#1e293b;border-color:#1e293b;" onclick="saveUrutCtnGlobal()">
                    <i class="fas fa-sort-numeric-down me-1"></i>
                    Urutkan
                </button>
            </div>

        </div>
    </div>
</div>

<style>
    #urutkanCtnGlobalModal .pg-packtype-badge {
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
    #urutkanCtnGlobalModal .pg-packtype-badge.active {
        background: #0f172a;
        color: #fff;
        border-color: #0f172a;
    }
    #urutkanCtnGlobalModal .form-label-custom {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
    }
    #urutkanCtnGlobalModal .form-control-modern {
        border-color: #cbd5e1;
        border-radius: 6px;
        font-size: 13.5px;
        height: 36px;
    }
    #urutkanCtnGlobalModal .form-control-modern:focus {
        border-color: #64748b !important;
        box-shadow: 0 0 0 3px rgba(100, 116, 139, .15) !important;
    }
</style>

<script>
    let urutModeGlobal = 'carton'; // 'carton' | 'barcode'
    let urutBarcodeSubModeGlobal = 'otomatis'; // 'manual' | 'otomatis'

    function showUrutAlertGlobal(msg) {
        $('#urutAlertGlobalText').html(msg);
        $('#urutAlertGlobal').removeClass('d-none');
    }
    function hideUrutAlertGlobal() {
        $('#urutAlertGlobal').addClass('d-none');
    }

    function openUrutkanCtnModal() {
        hideUrutAlertGlobal();
        setUrutModeGlobal('carton');
        $('#urutCtnAwalGlobal').val('');
        $('#urutPairBarcodeGlobal').prop('checked', false);
        $('#urutPairBarcodeAwalGlobal').val('');
        $('#urutPairBarcodeInputWrapper').addClass('d-none');

        $('#urutSubModeOtomatis').prop('checked', true);
        setUrutBarcodeSubModeGlobal('otomatis');
        $('#urutBarcodeCartonAwalGlobal').val('');
        $('#urutBarcodeAwalGlobal').val('');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('urutkanCtnGlobalModal')).show();
    }

    function setUrutModeGlobal(mode) {
        urutModeGlobal = mode;
        hideUrutAlertGlobal();

        $('#urutModeCartonBtn, #urutModeBarcodeBtn').removeClass('active');

        if (mode === 'carton') {
            $('#urutModeCartonBtn').addClass('active');
            $('#urutPanelCarton').removeClass('d-none');
            $('#urutPanelBarcode').addClass('d-none');
        } else {
            $('#urutModeBarcodeBtn').addClass('active');
            $('#urutPanelBarcode').removeClass('d-none');
            $('#urutPanelCarton').addClass('d-none');
        }
    }

    function toggleUrutPairBarcodeGlobal() {
        const checked = $('#urutPairBarcodeGlobal').is(':checked');
        $('#urutPairBarcodeInputWrapper').toggleClass('d-none', !checked);

        $('#urutInfoCartonText').html(checked ? `
            <i class="fas fa-info-circle me-1"></i>
            Nomor Carton <strong class="text-dark">dan</strong> Barcode akan diurutkan BERSAMAAN dari nilai
            awal masing-masing -- carton urutan ke-N pasti dapat barcode urutan ke-N juga (selalu berpasangan).
        ` : `
            <i class="fas fa-info-circle me-1"></i>
            Nomor Carton akan diurutkan mulai dari nomor awal yang dimasukkan. <strong class="text-dark">Barcode
            TIDAK ikut berubah</strong>. Carton "Mixed" tetap dapat nomor yang SAMA di semua warnanya.
        `);
    }

    function setUrutBarcodeSubModeGlobal(subMode) {
        urutBarcodeSubModeGlobal = subMode;
        hideUrutAlertGlobal();

        if (subMode === 'manual') {
            $('#urutBarcodeCartonAwalWrapper').removeClass('d-none');
            $('#urutInfoBarcodeTextInner').html(`
                Barcode akan diurutkan MULAI DARI carton yang kamu tentukan (harus sudah ada di sistem) --
                carton-carton SEBELUM itu (berdasarkan urutan nomor carton) TIDAK ikut berubah barcode-nya.
                Nomor Carton <strong class="text-dark">TIDAK</strong> ikut berubah sama sekali.
            `);
        } else {
            $('#urutBarcodeCartonAwalWrapper').addClass('d-none');
            $('#urutInfoBarcodeTextInner').html(`
                Sistem akan otomatis mengurutkan SEMUA carton dari nomor terkecil ke terbesar, lalu memberi
                barcode berurutan mulai dari nilai di atas. Nomor Carton <strong class="text-dark">TIDAK</strong>
                ikut berubah.
            `);
        }
    }

    function saveUrutCtnGlobal() {
        hideUrutAlertGlobal();

        const payload = {
            po: @json($po),
            op: @json($op),
            poref: @json($poref ?? null),
            mif: @json($mif),
            mode: urutModeGlobal
        };

        if (urutModeGlobal === 'carton') {
            const awal = $('#urutCtnAwalGlobal').val().trim();
            if (awal === '') {
                showToast('error', 'Nomor Carton Awal wajib diisi.');
                return;
            }

            const pairBarcode = $('#urutPairBarcodeGlobal').is(':checked');
            const barcodeAwal = $('#urutPairBarcodeAwalGlobal').val().trim();

            if (pairBarcode && barcodeAwal === '') {
                showToast('error', 'Barcode Awal wajib diisi kalau ingin mengurutkan Carton & Barcode sekaligus.');
                return;
            }

            payload.awal = awal;
            payload.pair_barcode = pairBarcode ? 1 : 0;
            payload.barcode_awal = barcodeAwal;

        } else {
            const barcodeAwal = $('#urutBarcodeAwalGlobal').val().trim();
            if (barcodeAwal === '') {
                showToast('error', 'Barcode Awal wajib diisi.');
                return;
            }

            payload.sub_mode = urutBarcodeSubModeGlobal;
            payload.barcode_awal = barcodeAwal;

            if (urutBarcodeSubModeGlobal === 'manual') {
                const cartonAwal = $('#urutBarcodeCartonAwalGlobal').val().trim();
                if (cartonAwal === '') {
                    showToast('error', 'Nomor Carton Awal wajib diisi untuk mode Manual.');
                    return;
                }
                payload.carton_awal = cartonAwal;
            }
        }

        $.ajax({
            url: "{{ route('packing.urut.global') }}",
            method: 'POST',
            data: payload,
            beforeSend: function () {
                $('#btnSaveUrutCtnGlobal').prop('disabled', true);
            },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('urutkanCtnGlobalModal')).hide();
                loadPackingCards();
                reloadBreakdownSummary();
                refreshPgCombos();
                reloadCardsInfoGlobal();
            },
            error: function (xhr) {
                let res = xhr.responseJSON ?? { icon: 'error', title: 'Terjadi kesalahan.' };
                showToast(res.icon, res.title);
                // BARU: tampilkan juga DI DALAM modal -- supaya pesan
                // (misal "Nomor Carton tidak ditemukan") tidak cuma
                // sekilas lewat sebagai toast, tapi tetap terlihat
                // sampai user memperbaiki inputnya.
                showUrutAlertGlobal(res.title);
            },
            complete: function () {
                $('#btnSaveUrutCtnGlobal').prop('disabled', false);
            }
        });
    }
</script>