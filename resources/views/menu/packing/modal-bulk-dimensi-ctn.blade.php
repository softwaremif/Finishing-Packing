<div class="modal fade" id="bulkDimensiCtnModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
            <div class="modal-header border-0 pb-1">
                <h5 class="fw-bold text-dark mb-0" style="font-size:15px;">
                    <i class="fas fa-ruler-combined me-2 text-secondary"></i>Edit Ukuran &amp; Berat Carton (Massal)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="text-secondary mb-3" style="font-size:12.5px;">
                    Berlaku untuk <strong id="bulkDimensiCartonCount">0</strong> carton yang dipilih.
                </div>

                {{-- ============================================================
                     UKURAN (Panjang/Lebar/Tinggi)
                     ============================================================ --}}
                <div id="bulkDimensiExistingWrap" class="mb-3 d-none">
                    <label class="text-secondary d-block mb-2" style="font-size:12px;">
                        Ukuran yang sudah pernah dipakai:
                    </label>
                    <div id="bulkDimensiExistingList" class="d-flex flex-wrap gap-2"></div>
                </div>

                <label class="text-secondary d-block mb-2" style="font-size:12px;">
                    Ukuran (Cm) - kosongkan kalau tidak ingin diubah:
                </label>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <label class="text-secondary d-block mb-1" style="font-size:11px;">Panjang</label>
                        <input type="number" step="0.01" min="0" id="bulkDimPanjang" class="form-control form-control-sm text-center">
                    </div>
                    <div class="col-4">
                        <label class="text-secondary d-block mb-1" style="font-size:11px;">Lebar</label>
                        <input type="number" step="0.01" min="0" id="bulkDimLebar" class="form-control form-control-sm text-center">
                    </div>
                    <div class="col-4">
                        <label class="text-secondary d-block mb-1" style="font-size:11px;">Tinggi</label>
                        <input type="number" step="0.01" min="0" id="bulkDimTinggi" class="form-control form-control-sm text-center">
                    </div>
                </div>

                <hr class="my-3">

                {{-- ============================================================
                     BARU -- BERAT (NW/GW)
                     ============================================================ --}}
                <div id="bulkWeightExistingWrap" class="mb-3 d-none">
                    <label class="text-secondary d-block mb-2" style="font-size:12px;">
                        Berat yang sudah pernah dipakai:
                    </label>
                    <div id="bulkWeightExistingList" class="d-flex flex-wrap gap-2"></div>
                </div>

                <label class="text-secondary d-block mb-2" style="font-size:12px;">
                    Berat (Kg) - kosongkan kalau tidak ingin diubah:
                </label>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="text-secondary d-block mb-1" style="font-size:11px;">N.W (Net Weight)</label>
                        <input type="number" step="0.01" min="0" id="bulkDimNw" class="form-control form-control-sm text-center">
                    </div>
                    <div class="col-6">
                        <label class="text-secondary d-block mb-1" style="font-size:11px;">G.W (Gross Weight)</label>
                        <input type="number" step="0.01" min="0" id="bulkDimGw" class="form-control form-control-sm text-center">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btnSaveBulkDimensi" class="btn btn-sm btn-dark px-4" onclick="saveBulkDimensiCtn()">
                    Terapkan
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .bulk-dim-chip {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
        background: #f8fafc;
        cursor: pointer;
        transition: all .12s ease;
    }
    .bulk-dim-chip:hover {
        border-color: #94a3b8;
        background: #f1f5f9;
    }
    .bulk-dim-chip.active {
        background: #1e293b;
        border-color: #1e293b;
        color: #fff;
    }
</style>

<script>
    // BARU -- cache data combo/weight (di-refresh tiap openBulkDimensiModal),
    // dipakai oleh syncDimChipSelection()/syncWeightChipSelection() untuk
    // live-matching setiap field berubah.
    let bulkDimCombosCache = [];
    let bulkWeightCombosCache = [];

    function openBulkDimensiModal() {
        const packpks = window.selectedPackpksGlobal || [];
        if (!packpks.length) {
            showToast('warning', 'Pilih minimal satu carton.');
            return;
        }
        const selectedRows = packpks.map(pk => window.selectedRowsCache[pk]).filter(Boolean);
        const cartonCount = new Set(selectedRows.map(r => r.carton)).size;
        $('#bulkDimensiCartonCount').text(cartonCount);
        $('#bulkDimPanjang, #bulkDimLebar, #bulkDimTinggi, #bulkDimNw, #bulkDimGw').val('');
        $('#bulkDimensiExistingList, #bulkWeightExistingList').empty();
        $('#bulkDimensiExistingWrap, #bulkWeightExistingWrap').addClass('d-none');
        bulkDimCombosCache = [];
        bulkWeightCombosCache = [];

        // ============================================================
        // BARU -- FIX UTAMA: pasang listener 'input' DI SINI (dalam
        // function, baru jalan saat modal dibuka lewat klik user -- jQuery
        // SUDAH PASTI ter-load di titik ini), BUKAN lagi di top-level
        // script (yang dieksekusi SAAT FILE DI-PARSE, SEBELUM jQuery
        // selesai dimuat -- itu penyebab "$ is not defined").
        //
        // .off() dulu + event NAMESPACE ('.bulkDim'/'.bulkWeight') supaya
        // tidak numpuk listener dobel kalau modal ini dibuka berkali-kali.
        // ============================================================
        $(document).off('input.bulkDim').on('input.bulkDim', '#bulkDimPanjang, #bulkDimLebar, #bulkDimTinggi', function () {
            syncDimChipSelection();
        });
        $(document).off('input.bulkWeight').on('input.bulkWeight', '#bulkDimNw, #bulkDimGw', function () {
            syncWeightChipSelection();
        });

        $.get(R.distinctDimensiCtn, { po: PO, op: OP, poref: POREF, mif: MIF }, function (data) {
            bulkDimCombosCache = data.combos || [];
            bulkWeightCombosCache = data.weights || [];

            if (bulkDimCombosCache.length) {
                $('#bulkDimensiExistingWrap').removeClass('d-none');
                const $list = $('#bulkDimensiExistingList');
                bulkDimCombosCache.forEach(function (c, idx) {
                    const label = `${c.panjang} &times; ${c.lebar} &times; ${c.tinggi}`;
                    const $chip = $(`<span class="bulk-dim-chip" data-idx="${idx}">${label}</span>`);
                    $chip.on('click', function () {
                        const isActive = $(this).hasClass('active');
                        if (isActive) {
                            $('#bulkDimPanjang, #bulkDimLebar, #bulkDimTinggi').val('');
                        } else {
                            $('#bulkDimPanjang').val(c.panjang);
                            $('#bulkDimLebar').val(c.lebar);
                            $('#bulkDimTinggi').val(c.tinggi);
                        }
                        syncDimChipSelection();
                    });
                    $list.append($chip);
                });
            }

            if (bulkWeightCombosCache.length) {
                $('#bulkWeightExistingWrap').removeClass('d-none');
                const $wList = $('#bulkWeightExistingList');
                bulkWeightCombosCache.forEach(function (w, idx) {
                    const label = `NW ${w.nw} / GW ${w.gw}`;
                    const $chip = $(`<span class="bulk-dim-chip" data-idx="${idx}">${label}</span>`);
                    $chip.on('click', function () {
                        const isActive = $(this).hasClass('active');
                        if (isActive) {
                            $('#bulkDimNw, #bulkDimGw').val('');
                        } else {
                            $('#bulkDimNw').val(w.nw);
                            $('#bulkDimGw').val(w.gw);
                        }
                        syncWeightChipSelection();
                    });
                    $wList.append($chip);
                });
            }
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkDimensiCtnModal')).show();
    }

    // ============================================================
    // BARU -- FIX UTAMA: live-matching. Dipanggil tiap field P/L/T
    // berubah (termasuk dikosongkan) -- cari combo yang PERSIS SAMA
    // (perbandingan numerik, bukan string, supaya "60" == "60.00"),
    // highlight chip-nya kalau ketemu, lepas SEMUA highlight kalau
    // tidak ketemu / field kosong.
    // ============================================================
    function syncDimChipSelection() {
        const panjang = $('#bulkDimPanjang').val();
        const lebar = $('#bulkDimLebar').val();
        const tinggi = $('#bulkDimTinggi').val();

        $('#bulkDimensiExistingList .bulk-dim-chip').removeClass('active');

        if (panjang === '' || lebar === '' || tinggi === '') return; // kosong -> tidak match apa pun

        const matchIdx = bulkDimCombosCache.findIndex(c =>
            parseFloat(c.panjang) === parseFloat(panjang) &&
            parseFloat(c.lebar) === parseFloat(lebar) &&
            parseFloat(c.tinggi) === parseFloat(tinggi)
        );
        if (matchIdx !== -1) {
            $(`#bulkDimensiExistingList .bulk-dim-chip[data-idx="${matchIdx}"]`).addClass('active');
        }
    }

    function syncWeightChipSelection() {
        const nw = $('#bulkDimNw').val();
        const gw = $('#bulkDimGw').val();

        $('#bulkWeightExistingList .bulk-dim-chip').removeClass('active');

        if (nw === '' || gw === '') return;

        const matchIdx = bulkWeightCombosCache.findIndex(w =>
            parseFloat(w.nw) === parseFloat(nw) &&
            parseFloat(w.gw) === parseFloat(gw)
        );
        if (matchIdx !== -1) {
            $(`#bulkWeightExistingList .bulk-dim-chip[data-idx="${matchIdx}"]`).addClass('active');
        }
    }

    function saveBulkDimensiCtn() {
        const packpks = window.selectedPackpksGlobal || [];
        if (!packpks.length) return;
        const panjang = $('#bulkDimPanjang').val();
        const lebar = $('#bulkDimLebar').val();
        const tinggi = $('#bulkDimTinggi').val();
        const nw = $('#bulkDimNw').val();
        const gw = $('#bulkDimGw').val();
        const hasDimensi = panjang !== '' || lebar !== '' || tinggi !== '';
        const hasWeight = nw !== '' || gw !== '';
        if (!hasDimensi && !hasWeight) {
            showToast('warning', 'Isi minimal salah satu: Ukuran (P/L/T) atau Berat (NW/GW).');
            return;
        }
        if (hasDimensi && (panjang === '' || lebar === '' || tinggi === '')) {
            showToast('warning', 'Kalau mengisi Ukuran, Panjang/Lebar/Tinggi harus diisi semua.');
            return;
        }
        if (hasWeight && (nw === '' || gw === '')) {
            showToast('warning', 'Kalau mengisi Berat, N.W dan G.W harus diisi berdua.');
            return;
        }
        $.ajax({
            url: R.bulkUpdateDimensiCtn,
            method: 'POST',
            data: {
                packpk: packpks.join(','),
                panjang: hasDimensi ? panjang : null,
                lebar: hasDimensi ? lebar : null,
                tinggi: hasDimensi ? tinggi : null,
                nw: hasWeight ? nw : null,
                gw: hasWeight ? gw : null,
                mif: MIF,
            },
            beforeSend: function () { $('#btnSaveBulkDimensi').prop('disabled', true); },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('bulkDimensiCtnModal')).hide();
                closeMenuGlobal();
                loadPackingCards();
            },
            error: function (xhr) {
                const res = xhr.responseJSON || { icon: 'error', title: 'Gagal menerapkan data.' };
                showToast(res.icon, res.title);
            },
            complete: function () { $('#btnSaveBulkDimensi').prop('disabled', false); }
        });
    }
</script>