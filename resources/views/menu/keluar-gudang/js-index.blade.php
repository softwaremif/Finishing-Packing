<script>
    const guserpk = @json(session('guserpk'));
    const APPROVER_LEVEL1 = [/* TODO: isi guserpk approver level 1 */];
    const APPROVER_LEVEL2 = [/* TODO: isi guserpk approver level 2 */];
    const APPROVER_LEVEL3 = [/* TODO: isi guserpk approver level 3 */];

    // ============================================================
    // FORMATTER INDEX
    // ============================================================
    function formatOutpk(value, row) {
        return row.no_out || `#${value}`;
    }
    function formatDashOut(value) { return value ?? '<span class="text-muted">-</span>'; }
    function formatOutDate(value) {
        if (!value) return '-';
        const d = new Date(value.replace(' ', 'T'));
        if (isNaN(d)) return value;
        const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        return `${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
    }
    function formatOutStatusPill(value) {
        let cls = 'st-pending';
        if (String(value).includes('Ditolak')) cls = 'st-rejected';
        else if (String(value).includes('Selesai')) cls = 'st-done';
        else if (String(value).includes('Menunggu Approve')) cls = 'st-progress';
        return `<span class="status-pill ${cls}">${value}</span>`;
    }
    function formatOutAction(value, row) {
        const canEdit = row.can_edit === true;
        const allApproved = row.stsapv1 === 1 && row.stsapv2 === 1 && row.stsapv3 === 1;
    
        let html = `
            <div class="d-flex justify-content-center gap-1">
                <a href="javascript:void(0)" class="action-btn" title="Lihat Detail" onclick="openDetailOutsisaModal(${row.outpk}, ${row.mif})">
                    <i class="fas fa-eye"></i>
                </a>
        `;
 
        if (canEdit) {
            html += `
                <a href="javascript:void(0)" class="action-btn" style="background:#fef3c7;color:#92400e;" title="Edit"
                    onclick="openEditOutsisaModal(${row.outpk}, ${row.mif})">
                    <i class="fas fa-pen"></i>
                    </a>
                `;
        }
    
        if (!allApproved) {
            html += `
                <a href="javascript:void(0)" class="action-btn" style="background:#ede9fe;color:#6d28d9;" title="Kirim Email Approval"
                    onclick="sendOutsisaEmailAction(${row.outpk}, ${row.mif})">
                    <i class="fas fa-paper-plane"></i>
                </a>
            `;
        }
    
        if (canEdit) {
            html += `
                <a href="javascript:void(0)" class="action-btn action-btn-danger" title="Batalkan" onclick="confirmCancelOutsisa(${row.outpk}, ${row.mif})">
                    <i class="fas fa-ban"></i>
                </a>
            `;
        }
    
        html += `</div>`;
        return html;
    }
    
    // kirim email.
    function sendOutsisaEmailAction(outpk, mif) {
        $.ajax({
            url: "{{ url('/keluarkan-sisa') }}/" + outpk + "/send-email",
            method: 'POST',
            data: { mif: mif },
            success: function (res) { showToast(res.icon, res.title); },
            error: function (xhr) {
                const res = xhr.responseJSON || { icon: 'error', title: 'Gagal mengirim email.' };
                showToast(res.icon, res.title);
            }
        });
    }

    // ============================================================
    // MODAL KONFIRMASI GENERIK (reuse #confirmActionModal dari modul LO)
    // ============================================================
    function openConfirmModal(opts) {
        $('#confirmActionTitle').html(`<i class="fas fa-triangle-exclamation" style="color:${opts.iconColor || '#dc2626'};"></i> ${opts.title || 'Konfirmasi'}`);
        $('#confirmActionMessage').text(opts.message || '');
        $('#confirmActionSubtext').text(opts.subtext || '');
        $('#confirmActionBtnLabel').text(opts.confirmLabel || 'Ya, Lanjutkan');
        $('#confirmActionBtnIcon').attr('class', 'small ' + (opts.confirmIcon || 'fas fa-check'));
        const $btn = $('#btnConfirmAction');
        $btn.attr('class', 'btn btn-sm px-4 d-inline-flex align-items-center gap-1 ' + (opts.confirmBtnClass || 'btn-dark'))
            .css({ 'font-size': '13px', 'border-radius': '6px', 'height': '33px' });
        $btn.off('click').on('click', function () {
            $btn.prop('disabled', true);
            bootstrap.Modal.getInstance(document.getElementById('confirmActionModal'))?.hide();
            if (typeof opts.onConfirm === 'function') opts.onConfirm();
            setTimeout(() => $btn.prop('disabled', false), 500);
        });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).show();
    }

    // ============================================================
    // MODAL: ADD BARANG KELUAR
    // ============================================================
    let outCart = []; // [{bjpk, size, color, secsz, qty, remaining, POno}]
    let outEditOutpk = null; // null = create, angka = edit
    
    function openCreateOutsisaModal() {
        outEditOutpk = null;
        outCart = [];
        $('#outPenerima').val('');
        $('#outKeterangan').val('');
        $('#outItemSearch').val('');
        $('#createOutsisaModalTitle').text('Add Barang Keluar dari Gudang');
        $('#btnSubmitOutsisa').html('<i class="fas fa-truck-ramp-box me-1"></i> Simpan');
        renderOutCart();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('createOutsisaModal')).show();
        loadOutItems();
    }
    
    function openEditOutsisaModal(outpk, mif) {
        outEditOutpk = outpk;
        outCart = [];
        $('#outItemSearch').val('');
        $('#createOutsisaModalTitle').text('Edit Barang Keluar #' + outpk);
        $('#btnSubmitOutsisa').html('<i class="fas fa-save me-1"></i> Simpan Perubahan');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('createOutsisaModal')).show();
    
        $.get("{{ url('/keluarkan-sisa') }}/" + outpk + "?mif=" + mif, function (data) {
            $('#outPenerima').val(data.out.penerima || '');
            $('#outKeterangan').val(data.out.keterangan || '');
            (data.lines || []).forEach(function (line) {
                outCart.push({
                    bjpk: line.bjpk, size: line.size, color: line.color, secsz: line.secsz,
                    qty: line.qty, remaining: line.qty, POno: line.POno,
                });
            });
            renderOutCart();
            loadOutItems();
        });
    }

    let outItemSearchTimer = null;
    $(document).on('keyup', '#outItemSearch', function () {
        clearTimeout(outItemSearchTimer);
        outItemSearchTimer = setTimeout(loadOutItems, 300);
    });

    function loadOutItems() {
        const params = { search: $('#outItemSearch').val() };
        if (outEditOutpk !== null) {
            params.exclude_outpk = outEditOutpk; 
        }
        $.get("{{ route('lo.keluargudang.available-items') }}", params, function (data) {
            renderOutItemGroups(data.groups || []);
        });
    }

    function gradeClass(grade) {
        const g = String(grade || '').trim().toUpperCase();
        if (g === 'A') return 'grade-a';
        if (g === 'B') return 'grade-b';
        if (g === 'C') return 'grade-c';
        return '';
    }

    function toggleOutSizeDetail(bjpk) {
        const $detail = $('#outSizeDetail_' + bjpk);
        const $icon = $('#outExpandIcon_' + bjpk);
        const $btn = $icon.closest('.lo-item-expand-btn');
        const isOpen = $detail.hasClass('is-open');
        $detail.toggleClass('is-open', !isOpen);
        $btn.toggleClass('is-open', !isOpen);
        $icon.toggleClass('fa-chevron-down fa-chevron-up');
    }

    function renderOutItemGroups(groups) {
        const wrap = $('#outItemGroups');
        wrap.empty();
        if (!groups.length) {
            $('#outItemEmpty').removeClass('d-none');
            return;
        }
        $('#outItemEmpty').addClass('d-none');

        groups.forEach(function (g) {
            let itemsHtml = '';
            g.items.forEach(function (item) {
                window['outItemData_' + item.bjpk] = item;

                let sizeRowsHtml = '';
                item.sizes.forEach(function (s, idx) {
                    const inputId = `outQtyInput_${item.bjpk}_${idx}`;
                    sizeRowsHtml += `
                        <div class="out-size-row">
                            <span class="out-size-label">${s.label}</span>
                            <span class="out-size-remaining">sisa <strong>${s.remaining}</strong> pcs</span>
                            <input type="number" class="out-size-qty-input" id="${inputId}" min="1" max="${s.remaining}" value="${s.remaining}">
                            <button type="button" class="out-size-add-btn" title="Tambah ke keranjang"
                                onclick="addOutCartLine(${item.bjpk}, ${idx}, '${inputId}')">
                                <i class="fas fa-plus" style="font-size:10px;"></i>
                            </button>
                        </div>
                    `;
                });

                itemsHtml += `
                    <div class="lo-item-wrap">
                        <div class="lo-item-card" style="cursor:default;">
                            <div class="lo-item-grade-badge ${gradeClass(item.grade)}">${item.grade || '-'}</div>
                            <div class="lo-item-info">
                                <div class="li-main">${item.material ?? '-'} ${item.secsz ? '(' + item.secsz + ')' : ''}</div>
                                <div class="li-sub">${item.POno} &middot; Sec Size <strong>${item.secsz || '-'}</strong></div>
                            </div>
                            <span class="lo-item-expand-btn" title="Lihat detail size"
                                onclick="toggleOutSizeDetail(${item.bjpk})">
                                <i class="fas fa-chevron-down" id="outExpandIcon_${item.bjpk}"></i>
                            </span>
                        </div>
                        <div class="lo-item-size-detail" id="outSizeDetail_${item.bjpk}">
                            ${sizeRowsHtml}
                        </div>
                    </div>
                `;
            });

            wrap.append(`
                <div class="lo-po-group">
                    <div class="lo-po-group-title">
                        <i class="fas fa-file-lines text-muted"></i> ${g.POno} &middot; OP ${g.OP}
                        <span class="buyer-tag">${g.buyer ?? ''}</span>
                    </div>
                    ${itemsHtml}
                </div>
            `);
        });
    }

    function addOutCartLine(bjpk, sizeIdx, inputId) {
        const item = window['outItemData_' + bjpk];
        const sizeInfo = item.sizes[sizeIdx];
        const qtyToAdd = parseFloat($('#' + inputId).val());
    
        if (!qtyToAdd || qtyToAdd <= 0) {
            showToast('warning', 'Qty harus lebih dari 0.');
            return;
        }
    
        // BARU -- FIX UTAMA: hitung qty yang SUDAH ada di keranjang utk
        // bjpk+size yang SAMA, supaya tidak numpuk melebihi sisa asli.
        const alreadyInCart = outCart
            .filter(l => l.bjpk === bjpk && l.size === sizeInfo.label)
            .reduce((sum, l) => sum + l.qty, 0);
    
        const effectiveRemaining = sizeInfo.remaining - alreadyInCart;
    
        if (qtyToAdd > effectiveRemaining) {
            showToast('warning', `Qty melebihi sisa. Sisa sebenarnya (setelah dikurangi yang sudah di keranjang): ${effectiveRemaining} pcs.`);
            return;
        }
    
        // BARU -- kalau baris bjpk+size yang SAMA sudah ada di keranjang,
        // TAMBAHKAN ke qty-nya (bukan push baris baru) -- mencegah duplikat
        // baris untuk sumber bjpk+size yang identik.
        const existingLine = outCart.find(l => l.bjpk === bjpk && l.size === sizeInfo.label);
        if (existingLine) {
            existingLine.qty += qtyToAdd;
        } else {
            outCart.push({
                bjpk: bjpk,
                size: sizeInfo.label,
                color: item.material,
                secsz: item.secsz,
                qty: qtyToAdd,
                remaining: sizeInfo.remaining,
                POno: item.POno,
            });
        }
    
        renderOutCart();
        showToast('success', `${sizeInfo.label} (${qtyToAdd} pcs) ditambahkan ke keranjang.`);
    }

    function removeOutCartLine(index) {
        outCart.splice(index, 1);
        renderOutCart();
    }

    function renderOutCart() {
        $('#outCartCount').text(outCart.length);
        const list = $('#outCartList');
        list.empty();
        if (!outCart.length) {
            list.html('<div class="text-muted text-center py-4" style="font-size:12.5px;">Belum ada barang dipilih.</div>');
            return;
        }
        outCart.forEach(function (line, index) {
            list.append(`
                <div class="out-cart-row">
                    <div>
                        <strong>${line.color ?? '-'}</strong> &middot; ${line.size}<br>
                        <span class="text-muted">${line.POno} &middot; ${line.qty} pcs</span>
                    </div>
                    <i class="fas fa-times lc-remove" onclick="removeOutCartLine(${index})"></i>
                </div>
            `);
        });
    }

    function submitCreateOutsisa() {
        if (!outCart.length) {
            showToast('warning', 'Pilih minimal 1 barang.');
            return;
        }
        $('#btnSubmitOutsisa').prop('disabled', true);
    
        const isEdit = outEditOutpk !== null;
        const url = isEdit ? ("{{ url('/keluarkan-sisa') }}/" + outEditOutpk) : "{{ route('lo.keluargudang.store') }}";
        const method = isEdit ? 'PUT' : 'POST';
    
        $.ajax({
            url: url,
            method: method,
            data: {
                penerima: $('#outPenerima').val(),
                keterangan: $('#outKeterangan').val(),
                lines: outCart.map(l => ({ bjpk: l.bjpk, size: l.size, color: l.color, secsz: l.secsz, qty: l.qty })),
            },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('createOutsisaModal')).hide();
                window.EasyuiDG.reload('dgOutsisa');
            },
            error: function (xhr) {
                const res = xhr.responseJSON || { icon: 'error', title: 'Gagal menyimpan data keluar.' };
                showToast(res.icon, res.title);
            },
            complete: function () { $('#btnSubmitOutsisa').prop('disabled', false); }
        });
    }

    // ============================================================
    // MODAL: DETAIL KELUAR
    // ============================================================
    let currentDetailOutpk = null;
    let currentDetailOutMif = null;

    function openDetailOutsisaModal(outpk, mif) {
        currentDetailOutpk = outpk;
        currentDetailOutMif = mif;
        $.get("{{ url('/keluarkan-sisa') }}/" + outpk + "?mif=" + mif, function (data) {
            renderOutsisaDetail(data.out, data.lines || []);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('detailOutsisaModal')).show();
        });
    }

    function renderOutsisaDetail(out, lines) {
        $('#detailOutNo').text(out.no_out || ('#' + out.outpk));
        $('#detailOutDate').text(formatOutDate(out.tglout));
        $('#detailOutPenerima').text(out.penerima || '-');
        $('#detailOutKeterangan').text(out.keterangan || '-');
    
        const stepLabels = { 1: 'Purchasing', 2: 'HRD', 3: 'HRD2' };
        const steps = [1, 2, 3].map(function (lvl) {
            const val = out['stsapv' + lvl];
            let cls = 'bg-secondary-subtle text-secondary', icon = 'fa-circle';
            if (val === 1) { cls = 'bg-success-subtle text-success'; icon = 'fa-check-circle'; }
            else if (val === 0) { cls = 'bg-danger-subtle text-danger'; icon = 'fa-times-circle'; }
            return `<span class="badge ${cls}" style="font-size:11px;"><i class="fas ${icon} me-1"></i>${stepLabels[lvl]}</span>`;
        }).join('<i class="fas fa-arrow-right text-muted mx-1" style="font-size:10px;"></i>');
        $('#detailOutApprovalSteps').html(steps);
    
        const body = $('#detailOutItemsBody');
        body.empty();
        const canDeleteLine = out.can_edit === true;
        lines.forEach(function (line) {
            body.append(`
                <tr>
                    <td><span class="lo-item-grade-badge ${gradeClass(line.grade)}" style="width:26px;height:26px;font-size:11px;display:inline-flex;">${line.grade}</span></td>
                    <td style="font-size:12.5px;">${line.POno}<br><span class="text-muted">OP ${line.OP}</span></td>
                    <td style="font-size:12.5px;">${line.color ?? '-'} ${line.secsz ? '(' + line.secsz + ')' : ''}</td>
                    <td style="font-size:12.5px;">${line.size}</td>
                    <td class="text-end fw-semibold">${line.qty}</td>
                    <td class="text-center">
                       
                    </td>
                </tr>
            `);
        });
    
        // BARU -- approve INDEPENDEN, SAMA pola modul LO.
        let footer = '';
        let anyButtonShown = false;
        const approverMap = { 1: APPROVER_LEVEL1, 2: APPROVER_LEVEL2, 3: APPROVER_LEVEL3 };
    
        [1, 2, 3].forEach(function (lvl) {
            const val = out['stsapv' + lvl];
            if (val === null && approverMap[lvl].includes(guserpk)) {
                anyButtonShown = true;
                footer += `
                    <div class="d-flex gap-1 mb-1 w-100">
                        <button class="btn btn-outline-danger btn-sm flex-fill" onclick="rejectOutsisa(${out.outpk}, ${lvl})">
                            <i class="fas fa-times me-1"></i>Tolak (${stepLabels[lvl]})
                        </button>
                        <button class="btn btn-dark btn-sm flex-fill" onclick="approveOutsisa(${out.outpk}, ${lvl})">
                            <i class="fas fa-check me-1"></i>Approve (${stepLabels[lvl]})
                        </button>
                    </div>
                `;
            }
        });
    
        if (!anyButtonShown) {
            const allDone = out.stsapv1 !== null && out.stsapv2 !== null && out.stsapv3 !== null;
            footer += allDone
                ? `<span class="text-success fw-semibold" style="font-size:12.5px;"><i class="fas fa-check-circle me-1"></i>Semua approval sudah diproses.</span>`
                : `<span class="text-muted" style="font-size:12px;">Menunggu approver lain.</span>`;
        }
    
        if (out.can_edit) {
            footer += `<button class="btn btn-outline-secondary btn-sm" onclick="confirmCancelOutsisaFromDetail(${out.outpk})"><i class="fas fa-ban me-1"></i>Batalkan</button>`;
        }
    
        $('#detailOutFooter').html(footer);
    }

    function approveOutsisa(outpk, level) {
        $.ajax({
            url: "{{ url('/keluarkan-sisa') }}/" + outpk + "/approve/" + level,
            method: 'POST',
            data: { mif: currentDetailOutMif },
            success: function (res) {
                showToast(res.icon, res.title);
                openDetailOutsisaModal(outpk, currentDetailOutMif);
                window.EasyuiDG.reload('dgOutsisa');
            },
            error: function (xhr) {
                const res = xhr.responseJSON || { icon: 'error', title: 'Gagal approve.' };
                showToast(res.icon, res.title);
            }
        });
    }

    function rejectOutsisa(outpk, level) {
        openConfirmModal({
            title: 'Tolak Approval',
            message: `Tolak data keluar #${outpk} di Level ${level}?`,
            subtext: 'Data tidak bisa dilanjutkan ke level berikutnya setelah ditolak.',
            confirmLabel: 'Ya, Tolak',
            confirmIcon: 'fas fa-times',
            confirmBtnClass: 'btn-danger',
            onConfirm: function () {
                $.ajax({
                    url: "{{ url('/keluarkan-sisa') }}/" + outpk + "/reject/" + level,
                    method: 'POST',
                    data: { mif: currentDetailOutMif },
                    success: function (res) {
                        showToast(res.icon, res.title);
                        openDetailOutsisaModal(outpk, currentDetailOutMif);
                        window.EasyuiDG.reload('dgOutsisa');
                    },
                    error: function (xhr) {
                        const res = xhr.responseJSON || { icon: 'error', title: 'Gagal menolak.' };
                        showToast(res.icon, res.title);
                    }
                });
            }
        });
    }

    function confirmCancelOutsisa(outpk, mif) {
        openConfirmModal({
            title: 'Batalkan Data Keluar',
            message: `Batalkan data keluar #${outpk}?`,
            subtext: 'Semua sisa yang sudah dikeluarkan akan ter-unlock kembali.',
            confirmLabel: 'Ya, Batalkan',
            confirmIcon: 'fas fa-ban',
            confirmBtnClass: 'btn-dark',
            onConfirm: function () {
                $.ajax({
                    url: "{{ url('/keluarkan-sisa') }}/" + outpk,
                    method: 'DELETE',
                    data: { mif: mif },
                    success: function (res) {
                        showToast(res.icon, res.title);
                        window.EasyuiDG.reload('dgOutsisa');
                    },
                    error: function (xhr) {
                        const res = xhr.responseJSON || { icon: 'error', title: 'Gagal membatalkan.' };
                        showToast(res.icon, res.title);
                    }
                });
            }
        });
    }

    function confirmCancelOutsisaFromDetail(outpk) {
        bootstrap.Modal.getInstance(document.getElementById('detailOutsisaModal'))?.hide();
        confirmCancelOutsisa(outpk, currentDetailOutMif);
    }

    function removeOutsisaLine(outpk, outdtpk) {
        openConfirmModal({
            title: 'Hapus Baris',
            message: 'Hapus baris ini dari data keluar?',
            subtext: 'Sisa akan ter-unlock kembali setelah baris ini dihapus.',
            confirmLabel: 'Ya, Hapus',
            confirmIcon: 'fas fa-trash',
            confirmBtnClass: 'btn-dark',
            onConfirm: function () {
                $.ajax({
                    url: "{{ url('/keluarkan-sisa') }}/" + outpk + "/item/" + outdtpk,
                    method: 'DELETE',
                    data: { mif: currentDetailOutMif },
                    success: function (res) {
                        showToast(res.icon, res.title);
                        openDetailOutsisaModal(outpk, currentDetailOutMif);
                        window.EasyuiDG.reload('dgOutsisa');
                    },
                    error: function (xhr) {
                        const res = xhr.responseJSON || { icon: 'error', title: 'Gagal menghapus baris.' };
                        showToast(res.icon, res.title);
                    }
                });
            }
        });
    }
</script>