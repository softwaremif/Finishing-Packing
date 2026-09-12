<div class="modal fade" id="bundleCartonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
            <div class="modal-header border-0 pb-1">
                <h5 class="fw-bold text-dark mb-0" id="bcmModalTitle" style="font-size:15px;">
                    Gabung Carton Besar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-2">

                {{-- Ringkasan carton terpilih -- SELALU tampil di atas,
                     terkumpul lintas PO/OP (bisa cari & pilih dari PO/OP
                     lain berkali-kali). --}}
                <div id="bcmSelectedSummary" class="border rounded-3 p-2 mb-3 d-none" style="background:#f8fafc;"></div>

                {{-- STEP 1 --}}
                <div id="bcmStep1">
                    <div class="input-group mb-2">
                        <span class="input-group-text search">
                            <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18" alt="Search">
                        </span>
                        <input type="text" class="form-control search" id="bcmSearchPoOp"
                            placeholder="Cari No PO / OP / Customer / Buyer...">
                    </div>
                    <div id="bcmPoOpResults" class="border rounded-3 p-2" style="max-height:300px;overflow-y:auto;font-size:12.5px;">
                        <div class="text-muted text-center py-3">Ketik minimal 2 karakter untuk mencari.</div>
                    </div>
                </div>

                {{-- STEP 2 -- header sticky, sama pola dgn modal Mix Polibag --}}
                <div id="bcmStep2" class="d-none">
                    <div style="position:sticky; top:0; background:#fff; z-index:2; padding-bottom:8px;">
                        <button type="button" class="btn btn-link btn-sm px-0 text-decoration-none mb-2" onclick="bcmBackToStep1()">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke pencarian PO
                        </button>
                        <div class="border rounded-3 p-2 mb-2" style="font-size:12.5px;" id="bcmSelectedPoOpInfo"></div>
                        <div class="text-secondary" style="font-size:12px;">
                            Centang carton yang mau digabung (boleh pilih lebih dari 1):
                        </div>
                    </div>

                    <div id="bcmCartonGrid" class="row g-2 mt-1"></div>

                    <div class="d-flex justify-content-between align-items-center mt-3" id="bcmPagerWrap">
                        <div class="text-secondary" style="font-size:12px;" id="bcmPagerInfo"></div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-secondary" id="bcmBtnPrev" onclick="bcmGoPage(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span style="font-size:12px;" id="bcmPagerLabel"></span>
                            <button class="btn btn-sm btn-outline-secondary" id="bcmBtnNext" onclick="bcmGoPage(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- FORM FINAL -- muncul begitu minimal 1 carton dipilih --}}
                <div id="bcmFinalForm" class="d-none mt-3">
                    <hr>
                    <label class="text-secondary d-block mb-2" style="font-size:12px;">Detail Carton Besar</label>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="text-secondary d-block mb-1" style="font-size:12px;">No Carton Besar</label>
                            <input type="text" id="bcmCartonBesar" class="form-control" placeholder="CTN-BESAR-01">
                        </div>
                        <div class="col-6">
                            <label class="text-secondary d-block mb-1" style="font-size:12px;">Barcode Carton Besar</label>
                            <input type="text" id="bcmNobarBesar" class="form-control" placeholder="Scan Barcode">
                        </div>
                        <div class="col-3">
                            <label class="text-secondary d-block mb-1" style="font-size:12px;">N.W (Kg)</label>
                            <input type="number" step="0.01" min="0" id="bcmNw" class="form-control">
                        </div>
                        <div class="col-3">
                            <label class="text-secondary d-block mb-1" style="font-size:12px;">G.W (Kg)</label>
                            <input type="number" step="0.01" min="0" id="bcmGw" class="form-control">
                        </div>
                        <div class="col-2">
                            <label class="text-secondary d-block mb-1" style="font-size:12px;">Panjang</label>
                            <input type="number" step="0.01" min="0" id="bcmPanjang" class="form-control" placeholder="0">
                        </div>
                        <div class="col-2">
                            <label class="text-secondary d-block mb-1" style="font-size:12px;">Lebar</label>
                            <input type="number" step="0.01" min="0" id="bcmLebar" class="form-control" placeholder="0">
                        </div>
                        <div class="col-2">
                            <label class="text-secondary d-block mb-1" style="font-size:12px;">Tinggi</label>
                            <input type="number" step="0.01" min="0" id="bcmTinggi" class="form-control" placeholder="0">
                        </div>
                        <div class="col-12">
                            <label class="text-secondary d-block mb-1" style="font-size:12px;">Keterangan</label>
                            <textarea id="bcmKeterangan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-dark d-none" id="bcmBtnSubmit" onclick="bcmSubmit()">
                    Gabung Carton Besar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.bcmSelectedCartons = window.bcmSelectedCartons || []; // [{carton, POno, OP, packpks:[...]}]
    window.bcmSelectedPoOp = null;
    window.bcmPage = 1;
    window.bcmTotalCarton = 0;
    window.bcmPerPage = 8;

    function openBundleCartonModal() {
        window.bcmSelectedCartons = [];
        window.bcmEditingBundlepk = null;     // BARU
        $('#bcmSearchPoOp').val('');
        $('#bcmCartonBesar, #bcmNobarBesar, #bcmNw, #bcmGw, #bcmPanjang, #bcmLebar, #bcmTinggi, #bcmKeterangan').val('');
        $('#bcmModalTitle').text('Gabung Carton Besar');   // BARU -- reset judul
        $('#bcmBtnSubmit').text('Gabung Carton Besar');    // BARU -- reset label tombol
        bcmRenderSelectedSummary();
        bcmShowStep1();
        bcmLoadPoOpList('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('bundleCartonModal')).show();
    }

    function editBundleCarton(bundlepk) {
        if (!bundlepk) return;
    
        $.get(R.bundleDetail, { bundlepk: bundlepk }, function (data) {
            const bundle = data.bundle;
            const members = data.members || [];
    
            window.bcmSelectedCartons = members.map(function (m) {
                return { carton: m.carton, POno: m.POno, OP: m.OP, packpks: m.packpks };
            });
            window.bcmEditingBundlepk = bundle.bundlepk;
    
            $('#bcmCartonBesar').val(bundle.bundle_carton || '');
            $('#bcmNobarBesar').val(bundle.bundle_nobar || '');
            $('#bcmNw').val(bundle.nw || '');
            $('#bcmGw').val(bundle.gw || '');
            $('#bcmPanjang').val(bundle.panjang || '');
            $('#bcmLebar').val(bundle.lebar || '');
            $('#bcmTinggi').val(bundle.tinggi || '');
            $('#bcmKeterangan').val(bundle.keterangan || '');
    
            bcmRenderSelectedSummary();
            bcmShowStep1();
            bcmLoadPoOpList('');
    
            $('#bcmModalTitle').text('Edit Carton Besar');
            $('#bcmBtnSubmit').text('Simpan Perubahan');
    
            bootstrap.Modal.getOrCreateInstance(document.getElementById('bundleCartonModal')).show();
        }).fail(function () {
            showToast('error', 'Gagal memuat detail Carton Besar.');
        });
    }

    function bcmShowStep1() {
        $('#bcmStep1').removeClass('d-none');
        $('#bcmStep2').addClass('d-none');
        $('#bcmModalTitle').text('Gabung Carton Besar — Pilih PO/OP');
    }

    function bcmBackToStep1() {
        window.bcmSelectedPoOp = null;
        bcmShowStep1();
    }

    document.addEventListener('DOMContentLoaded', function () {
        let bcmSearchTimer = null;
        $(document).on('input', '#bcmSearchPoOp', function () {
            clearTimeout(bcmSearchTimer);
            const val = $(this).val().trim();
            bcmSearchTimer = setTimeout(function () { bcmLoadPoOpList(val); }, 300);
        });
    });

    function bcmLoadPoOpList(search) {
        $('#bcmPoOpResults').html('<div class="text-muted text-center py-3">Memuat...</div>');
        $.get(R.bundleOpLookup, { search: search, mif: MIF }, function (data) {
            bcmRenderPoOpList(data.rows || []);
        });
    }

    function bcmRenderPoOpList(rows) {
        if (!rows.length) {
            $('#bcmPoOpResults').html('<div class="text-muted text-center py-3">Tidak ada PO/OP yang cocok.</div>');
            return;
        }
        const html = rows.map(function (r) {
            const customerLabel = (r.customerCount > 1) ? `${r.customerCount} customer` : (r.customer ?? '-');
            return `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="cursor:pointer;"
                    onclick='bcmSelectPoOp(${JSON.stringify(r)})'>
                    <div>
                        <strong>${r.POno ?? '-'}</strong> &middot; ${r.OP ?? '-'}
                        <div class="text-muted" style="font-size:11px;" title="${(r.customerList||[]).join(', ')}">${customerLabel}</div>
                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                </div>
            `;
        }).join('');
        $('#bcmPoOpResults').html(html);
    }

    function bcmSelectPoOp(row) {
        window.bcmSelectedPoOp = row;
        window.bcmPage = 1;
        $('#bcmStep1').addClass('d-none');
        $('#bcmStep2').removeClass('d-none');
        $('#bcmModalTitle').text('Pilih Carton yang Mau Digabung');
        const customerLabel = (row.customerCount > 1)
            ? `${row.customerCount} customer (${(row.customerList || []).join(', ')})`
            : (row.customer ?? '-');
        $('#bcmSelectedPoOpInfo').html(`<strong>${row.POno ?? '-'} &middot; ${row.OP ?? '-'}</strong> &middot; ${customerLabel}`);
        bcmLoadCartonPage();
    }

    function bcmLoadCartonPage() {
        $('#bcmCartonGrid').html('<div class="col-12 text-muted text-center py-3">Memuat...</div>');
        const row = window.bcmSelectedPoOp;
        $.get(R.bundleCartonList, {
            po: row.POno, op: row.OP, poref: row.poref,
            page: window.bcmPage, rows: window.bcmPerPage,
            exclude_bundlepk: window.bcmEditingBundlepk || null   // BARU
        }, function (data) {
            window.bcmTotalCarton = data.total || 0;
            bcmRenderCartonCards(data.cartons || []);
            bcmRenderPager();
        });
    }

    function bcmGoPage(delta) {
        const maxPage = Math.max(1, Math.ceil(window.bcmTotalCarton / window.bcmPerPage));
        const next = window.bcmPage + delta;
        if (next < 1 || next > maxPage) return;
        window.bcmPage = next;
        bcmLoadCartonPage();
    }

    function bcmRenderPager() {
        const maxPage = Math.max(1, Math.ceil(window.bcmTotalCarton / window.bcmPerPage));
        $('#bcmPagerInfo').text(window.bcmTotalCarton + ' carton');
        $('#bcmPagerLabel').text('Halaman ' + window.bcmPage + ' / ' + maxPage);
        $('#bcmBtnPrev').prop('disabled', window.bcmPage <= 1);
        $('#bcmBtnNext').prop('disabled', window.bcmPage >= maxPage);
        $('#bcmPagerWrap').toggleClass('d-none', window.bcmTotalCarton === 0);
    }

    function bcmIsCartonSelected(carton, POno, OP) {
        return window.bcmSelectedCartons.some(c => c.carton === carton && c.POno === POno && c.OP === OP);
    }

    // Kartu carton -- detail LENGKAP per size (sama gaya dgn modal Mix
    // Polibag), tapi pemilihannya lewat CHECKBOX (multi-select), bukan
    // tombol "Mix" tunggal -- karena bundle boleh gabung BANYAK carton
    // sekaligus dalam satu aksi.
    function bcmRenderCartonCards(cartons) {
        if (!cartons.length) {
            $('#bcmCartonGrid').html('<div class="col-12 text-muted text-center py-3">Tidak ada carton di PO/OP ini.</div>');
            return;
        }

        const row = window.bcmSelectedPoOp;

        const html = cartons.map(function (ctn) {
            const compositionLabel = ctn.combos.length > 1 ? 'Mixed' : 'Solid';
            const compositionClass = compositionLabel.toLowerCase();

            let sizeRowsHtml = '';
            ctn.combos.forEach(function (combo) {
                Object.keys(combo.activeSizes).forEach(function (i) {
                    const plan = combo.planPerSize[i] || 0;
                    const actual = combo.actualPerSize[i] || 0;
                    if (plan <= 0 && actual <= 0) return;
                    const pct = plan > 0 ? Math.round((actual / plan) * 100) : 0;
                    const color = pct >= 100 ? '#8bc63f' : '#f97316';
                    const secszTag = combo.secsz ? ` (${combo.secsz})` : '';
                    const customerTag = combo.customer ? ` &middot; ${combo.customer}` : '';
                    sizeRowsHtml += `
                        <div class="size-row">
                            <span class="dot"></span>
                            <span class="name">${combo.material ?? '-'}${secszTag} &middot; ${combo.activeSizes[i]}${customerTag}</span>
                            <span class="mini-progress"><span class="bar" style="width:${Math.min(100,pct)}%; background:${color};"></span></span>
                            <span class="frac">${actual}/${plan}</span>
                        </div>
                    `;
                });
            });

            const isSelected = bcmIsCartonSelected(ctn.carton, row.POno, row.OP);
            const cardOutline = isSelected ? 'outline:2px solid #0f172a;' : '';
            const ctnJson = JSON.stringify(ctn).replace(/"/g, '&quot;');

            return `
                <div class="col-12 col-md-6">
                    <div class="packing-card h-100" style="cursor:pointer; ${cardOutline}" onclick="bcmToggleCarton('${ctnJson}')">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input" ${isSelected ? 'checked' : ''}
                                    ${ctn.alreadyBundled ? 'disabled' : ''}
                                    onclick="event.stopPropagation(); bcmToggleCarton('${ctnJson}')">
                                <span class="ctn-code">${ctn.carton ?? '-'}</span>
                                <span class="badge-soft ${compositionClass}">${compositionLabel}</span>
                            </div>
                        </div>
                        <div class="d-flex gap-1 flex-wrap mb-1">
                            ${ctn.alreadyBundled ? '<span class="badge-soft" style="background:#fee2e2;color:#991b1b;">Sudah di-bundle</span>' : ''}
                            ${ctn.hasExportpk ? '<span class="badge-soft" style="background:#fef3c7;color:#92400e;">Sudah Export Plan</span>' : ''}
                        </div>
                        <div class="subline mb-2">
                            <i class="fas fa-barcode me-1"></i>${ctn.nobar ? ctn.nobar : '<span class="text-muted">Belum ada barcode</span>'}
                        </div>
                        <div class="packing-card-sizes">${sizeRowsHtml || '<div class="text-muted" style="font-size:11px;">Belum ada Plan/Actual.</div>'}</div>
                    </div>
                </div>
            `;
        }).join('');

        $('#bcmCartonGrid').html(html);
    }

    function bcmToggleCarton(ctnJsonEscaped) {
        const ctn = JSON.parse(ctnJsonEscaped.replace(/&quot;/g, '"'));
        if (ctn.alreadyBundled) {
            showToast('warning', `Carton ${ctn.carton} sudah tergabung Carton Besar lain.`);
            return;
        }

        const row = window.bcmSelectedPoOp;
        const key = ctn.carton + '|' + row.POno + '|' + row.OP;
        const idx = window.bcmSelectedCartons.findIndex(c => (c.carton + '|' + c.POno + '|' + c.OP) === key);

        if (idx >= 0) {
            window.bcmSelectedCartons.splice(idx, 1);
        } else {
            window.bcmSelectedCartons.push({
                carton: ctn.carton,
                POno: row.POno,
                OP: row.OP,
                packpks: ctn.packpks,
            });
        }

        bcmRenderSelectedSummary();
        bcmLoadCartonPage(); // reload supaya checkbox/outline sinkron
    }

    function bcmRemoveSelectedCarton(idx) {
        window.bcmSelectedCartons.splice(idx, 1);
        bcmRenderSelectedSummary();
        if (window.bcmSelectedPoOp) bcmLoadCartonPage();
    }

    function bcmRenderSelectedSummary() {
        const wrap = $('#bcmSelectedSummary');
        if (!window.bcmSelectedCartons.length) {
            wrap.addClass('d-none').empty();
            $('#bcmFinalForm').addClass('d-none');
            $('#bcmBtnSubmit').addClass('d-none');
            return;
        }

        const itemsHtml = window.bcmSelectedCartons.map(function (c, idx) {
            return `
                <span class="badge-soft mixed" style="margin:2px; display:inline-block;">
                    ${c.carton} (${c.POno} &middot; ${c.OP})
                    <i class="fas fa-times ms-1" style="cursor:pointer;" onclick="bcmRemoveSelectedCarton(${idx})"></i>
                </span>
            `;
        }).join('');

        wrap.removeClass('d-none').html(`
            <div class="fw-bold mb-1" style="font-size:12.5px;">
                <i class="fas fa-box-open me-1"></i>${window.bcmSelectedCartons.length} carton dipilih untuk digabung:
            </div>
            <div>${itemsHtml}</div>
        `);

        $('#bcmFinalForm').removeClass('d-none');
        $('#bcmBtnSubmit').removeClass('d-none');
    }

    function bcmSubmit() {
        const cartonBesar = $('#bcmCartonBesar').val().trim();
        if (!cartonBesar) {
            showToast('warning', 'No Carton Besar wajib diisi.');
            return;
        }
        if (!window.bcmSelectedCartons.length) {
            showToast('warning', 'Pilih minimal 1 carton untuk digabung.');
            return;
        }
    
        const allPackpks = window.bcmSelectedCartons.flatMap(c => c.packpks);
    
        $('#bcmBtnSubmit').prop('disabled', true);
        $.ajax({
            url: R.storeCartonBundle,
            method: 'POST',
            data: {
                mif: MIF,
                bundlepk: window.bcmEditingBundlepk || null,   // BARU
                bundle_carton: cartonBesar,
                bundle_nobar: $('#bcmNobarBesar').val(),
                nw: $('#bcmNw').val(),
                gw: $('#bcmGw').val(),
                panjang: $('#bcmPanjang').val(),
                lebar: $('#bcmLebar').val(),
                tinggi: $('#bcmTinggi').val(),
                keterangan: $('#bcmKeterangan').val(),
                packpks: allPackpks,
            },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('bundleCartonModal')).hide();
                loadPackingCards();
                reloadBreakdownSummary();
                reloadCardsInfoGlobal();
            },
            error: function (xhr) {
                const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                showToast(res.icon, res.title);
            },
            complete: function () {
                $('#bcmBtnSubmit').prop('disabled', false);
            }
        });
    }
</script>