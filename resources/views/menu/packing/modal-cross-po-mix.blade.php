<div class="modal fade" id="crossPoMixModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
            <div class="modal-header border-0 pb-1">
                <h5 class="fw-bold text-dark mb-0" id="cpmModalTitle" style="font-size:15px;">
                    Campur Polibag dari PO Lain
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-2">
                {{-- STEP 1: cari & pilih PO/OP --}}
                <div id="cpmStep1">
                    <div class="text-secondary mb-2" style="font-size:12px;">
                        Menampilkan PO/OP dengan Buyer yang sama: <strong id="cpmBuyerLabel"></strong>
                    </div>
                    <div class="input-group mb-2">
                        <span class="input-group-text search">
                            <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18" alt="Search">
                        </span>
                        <input type="text" class="form-control search" id="cpmSearchPoOp"
                            placeholder="Cari No PO / OP / Customer / Material...">
                    </div>
                    <div id="cpmPoOpResults" class="border rounded-3 p-2" style="max-height:340px;overflow-y:auto;font-size:12.5px;">
                        <div class="text-muted text-center py-3">Memuat...</div>
                    </div>
                </div>

                {{-- STEP 2: pilih carton -- GANTI TOTAL: header (tombol
                     kembali + info PO/OP + teks instruksi) SEKARANG sticky
                     supaya tetap kelihatan saat scroll ke bawah, dan HANYA
                     modal (modal-dialog-scrollable) yang scroll -- konten di
                     dalamnya TIDAK punya scroll sendiri lagi (paginasi
                     dipakai supaya konten per halaman selalu pendek). --}}
                <div id="cpmStep2" class="d-none">
                    <div style="position:sticky; top:0; background:#fff; z-index:2; padding-bottom:8px;">
                        <button type="button" class="btn btn-link btn-sm px-0 text-decoration-none mb-2" onclick="cpmBackToStep1()">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke pencarian PO
                        </button>
                        <div class="border rounded-3 p-2 mb-2" style="font-size:12.5px;" id="cpmSelectedPoOpInfo"></div>
                        <div class="text-secondary" style="font-size:12px;">
                            Carton yang sudah di-plan di PO/OP ini -- pilih carton mana yang mau ikut di-mix:
                        </div>
                    </div>

                    <div id="cpmCartonGrid" class="row g-2 mt-1"></div>

                    <div class="d-flex justify-content-between align-items-center mt-3" id="cpmPagerWrap">
                        <div class="text-secondary" style="font-size:12px;" id="cpmPagerInfo"></div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-secondary" id="cpmBtnPrev" onclick="cpmGoPage(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span style="font-size:12px;" id="cpmPagerLabel"></span>
                            <button class="btn btn-sm btn-outline-secondary" id="cpmBtnNext" onclick="cpmGoPage(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.cpmSelectedPoOp = null;
    window.cpmPage = 1;
    window.cpmTotalCarton = 0;
    window.cpmPerPage = 8;

    function openCrossPoMixModal() {
        $('#cpmBuyerLabel').text(window.pgCurrentBuyer || '-');
        $('#cpmSearchPoOp').val('');
        cpmShowStep1();
        cpmLoadPoOpList('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('crossPoMixModal')).show();
    }

    function cpmShowStep1() {
        $('#cpmStep1').removeClass('d-none');
        $('#cpmStep2').addClass('d-none');
        $('#cpmModalTitle').text('Campur Polibag dari PO Lain — Pilih PO/OP');
    }

    function cpmBackToStep1() {
        window.cpmSelectedPoOp = null;
        cpmShowStep1();
    }

    document.addEventListener('DOMContentLoaded', function () {
        let cpmSearchTimer = null;
        $(document).on('input', '#cpmSearchPoOp', function () {
            clearTimeout(cpmSearchTimer);
            const val = $(this).val().trim();
            cpmSearchTimer = setTimeout(function () { cpmLoadPoOpList(val); }, 300);
        });
    });

    function cpmLoadPoOpList(search) {
        $.get(R.crossPoOpLookup, {
            search: search,
            buyer: window.pgCurrentBuyer || '',
            exclude_po: PO,
            exclude_op: OP
        }, function (data) {
            cpmRenderPoOpList(data.rows || []);
        });
    }

    function cpmRenderPoOpList(rows) {
        if (!rows.length) {
            $('#cpmPoOpResults').html('<div class="text-muted text-center py-3">Tidak ada PO/OP lain dengan Buyer yang sama.</div>');
            return;
        }
        const html = rows.map(function (r) {
            // BARU -- kalau customer lebih dari 1 macam di PO/OP ini,
            // tampilkan "X customer" (tooltip berisi daftar lengkap). Kalau
            // cuma 1, tampilkan nama customer-nya langsung.
            const customerLabel = (r.customerCount > 1)
                ? `${r.customerCount} customer`
                : (r.customer ?? '-');
            const customerTooltip = (r.customerList || []).join(', ');

            return `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="cursor:pointer;"
                    onclick='cpmSelectPoOp(${JSON.stringify(r)})'>
                    <div>
                        <strong>${r.POno ?? '-'}</strong> &middot; ${r.OP ?? '-'}
                        <div class="text-muted" style="font-size:11px;" title="${customerTooltip}">${customerLabel}</div>
                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                </div>
            `;
        }).join('');
        $('#cpmPoOpResults').html(html);
    }

    function cpmSelectPoOp(row) {
        window.cpmSelectedPoOp = row;
        window.cpmPage = 1;
        $('#cpmStep1').addClass('d-none');
        $('#cpmStep2').removeClass('d-none');
        $('#cpmModalTitle').text('Pilih Carton yang Mau Di-mix');

        // BARU -- info box juga pakai label customer yang SAMA (konsisten
        // dengan Step 1).
        const customerLabel = (row.customerCount > 1)
            ? `${row.customerCount} customer (${(row.customerList || []).join(', ')})`
            : (row.customer ?? '-');
        $('#cpmSelectedPoOpInfo').html(`<strong>${row.POno ?? '-'} &middot; ${row.OP ?? '-'}</strong> &middot; ${customerLabel}`);

        cpmLoadCartonPage();
    }

    function cpmLoadCartonPage() {
        $('#cpmCartonGrid').html('<div class="col-12 text-muted text-center py-3">Memuat...</div>');
        const row = window.cpmSelectedPoOp;
        $.get(R.crossPoCartonList, {
            po: row.POno, op: row.OP, poref: row.poref,
            page: window.cpmPage, rows: window.cpmPerPage
        }, function (data) {
            window.cpmTotalCarton = data.total || 0;
            cpmRenderCartonCards(data.cartons || []);
            cpmRenderPager();
        });
    }

    function cpmGoPage(delta) {
        const maxPage = Math.max(1, Math.ceil(window.cpmTotalCarton / window.cpmPerPage));
        const next = window.cpmPage + delta;
        if (next < 1 || next > maxPage) return;
        window.cpmPage = next;
        cpmLoadCartonPage();
    }

    function cpmRenderPager() {
        const maxPage = Math.max(1, Math.ceil(window.cpmTotalCarton / window.cpmPerPage));
        $('#cpmPagerInfo').text(window.cpmTotalCarton + ' carton');
        $('#cpmPagerLabel').text('Halaman ' + window.cpmPage + ' / ' + maxPage);
        $('#cpmBtnPrev').prop('disabled', window.cpmPage <= 1);
        $('#cpmBtnNext').prop('disabled', window.cpmPage >= maxPage);
        $('#cpmPagerWrap').toggleClass('d-none', window.cpmTotalCarton === 0);
    }

    // GANTI TOTAL -- FIX UTAMA: kartu SEKARANG menampilkan detail LENGKAP
    // per size (warna + size + qty actual/plan, mini progress bar) --
    // SAMA gaya dengan kartu carton di halaman utama Packing (.size-row/
    // .dot/.mini-progress/.frac). Tombol "Mix" SEKARANG per CARTON (ambil
    // SEMUA combo di dalamnya APA ADANYA, bukan per combo lagi).
    function cpmRenderCartonCards(cartons) {
        if (!cartons.length) {
            $('#cpmCartonGrid').html('<div class="col-12 text-muted text-center py-3">Tidak ada carton di PO/OP ini.</div>');
            return;
        }

        const isPackpkAdded = (packpk) => (window.pgCrossPoBlocks || []).some(b => b.packpk === packpk);

        const html = cartons.map(function (ctn) {
            const compositionLabel = ctn.combos.length > 1 ? 'Mixed' : 'Solid';
            const compositionClass = compositionLabel.toLowerCase();

            const allAdded = ctn.combos.every(c => isPackpkAdded(c.packpk));
            const someAdded = !allAdded && ctn.combos.some(c => isPackpkAdded(c.packpk));

            let sizeRowsHtml = '';
            ctn.combos.forEach(function (combo) {
                Object.keys(combo.activeSizes).forEach(function (i) {
                    const plan = combo.planPerSize[i] || 0;
                    const actual = combo.actualPerSize[i] || 0;
                    if (plan <= 0 && actual <= 0) return;
                    const pct = plan > 0 ? Math.round((actual / plan) * 100) : 0;
                    const color = pct >= 100 ? '#8bc63f' : '#f97316';
                    const secszTag = combo.secsz ? ` (${combo.secsz})` : '';
                    // BARU -- tambahkan Customer di akhir label.
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

            let btnLabel = '<i class="fas fa-plus"></i> Mix';
            let btnClass = 'btn-outline-dark';
            let btnDisabled = '';
            if (allAdded) {
                btnLabel = '<i class="fas fa-check"></i> Ditambahkan';
                btnClass = 'btn-outline-secondary';
                btnDisabled = 'disabled';
            } else if (someAdded) {
                btnLabel = '<i class="fas fa-check-double"></i> Sebagian';
                btnClass = 'btn-outline-warning';
            }

            return `
                <div class="col-12 col-md-6">
                    <div class="packing-card h-100" style="cursor:default;">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="ctn-code">${ctn.carton ?? '-'}</span>
                                <span class="badge-soft ${compositionClass}">${compositionLabel}</span>
                            </div>
                            <button type="button" class="btn btn-sm ${btnClass}" ${btnDisabled}
                                onclick='cpmPickCarton(${JSON.stringify(ctn)})'>
                                ${btnLabel}
                            </button>
                        </div>
                        <div class="subline mb-2">
                            <i class="fas fa-barcode me-1"></i>${ctn.nobar ? ctn.nobar : '<span class="text-muted">Belum ada barcode</span>'}
                        </div>
                        <div class="packing-card-sizes">${sizeRowsHtml || '<div class="text-muted" style="font-size:11px;">Belum ada Plan/Actual.</div>'}</div>
                    </div>
                </div>
            `;
        }).join('');

        $('#cpmCartonGrid').html(html);
    }

    // GANTI TOTAL -- FIX UTAMA: ambil SEMUA combo di dalam carton ini APA
    // ADANYA (plan & actual SUDAH terisi dari data yang sudah ada, admin
    // TIDAK PERLU ketik ulang) -- dorong ke window.pgCrossPoBlocks
    // (blok terpisah, BUKAN ke window.pgCombos/tabel breakdown utama,
    // supaya kolom size-nya tetap benar milik PO asalnya sendiri).
    function cpmPickCarton(ctn) {
        let addedCount = 0;
        ctn.combos.forEach(function (combo) {
            const already = (window.pgCrossPoBlocks || []).some(b => b.packpk === combo.packpk);
            if (already) return;
    
            window.pgCrossPoBlocks.push({
                packpk: combo.packpk,
                popk: combo.popk,
                POno: combo.POno,
                OP: combo.OP,
                customer: combo.customer,
                material: combo.material,
                secsz: combo.secsz,
                activeSizes: combo.activeSizes,
                orderQty: combo.orderQty,
                planQtyAll: combo.planQtyAll,     
                readyQtyAll: combo.readyQtyAll,  
                transQtyAll: combo.transQtyAll,   
                planQty: { ...combo.planPerSize },
                actualQty: { ...combo.actualPerSize },
                // snapshot nilai AWAL carton ini (sebelum diedit di sesi
                // ini) -- dipakai validasi (lihat pgOnCrossPoPlanInput/
                // pgOnCrossPoActualInput).
                originalPlanQty: { ...combo.planPerSize },
                originalActualQty: { ...combo.actualPerSize },
                sourceCarton: ctn.carton,
            });
            addedCount++;
        });
    
        renderPgCrossPoBlocks();
        recalcPackTypeGlobal();
    
        if (addedCount > 0) {
            showToast('success', `Carton ${ctn.carton} (${addedCount} combo) ditambahkan.`);
            cpmLoadCartonPage();
        } else {
            showToast('warning', 'Semua combo di carton ini sudah ditambahkan sebelumnya.');
        }
    }
</script>