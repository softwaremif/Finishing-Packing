<script>
    // ============================================================
    // FORMATTER INDEX
    // ============================================================
    function formatLopk(value) {
        return `#${value}`;
    }

    function formatDashLo(value) {
        return value ?? '<span class="text-muted">-</span>';
    }

    function formatLoDate(value) {
        if (!value) return '-';
        const d = new Date(value.replace(' ', 'T'));
        if (isNaN(d)) return value;
        const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return `${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
    }

    function formatStatusPill(value) {
        let cls = 'st-pending';
        if (String(value).includes('Ditolak')) cls = 'st-rejected';
        else if (String(value).includes('Selesai')) cls = 'st-done';
        else if (String(value).includes('Menunggu Approve')) cls = 'st-progress';
        return `<span class="status-pill ${cls}">${value}</span>`;
    }

    function formatLoAction(value, row) {
        const canCancel = row.stsapv1 !== 1;
        return `
                <div class="d-flex justify-content-center gap-1">
                    <a href="javascript:void(0)" class="action-btn" title="Lihat Detail" onclick="openDetailLoModal(${row.lopk})">
                        <i class="fas fa-eye"></i>
                    </a>
                    ${canCancel ? `
                        <a href="javascript:void(0)" class="action-btn action-btn-danger" title="Batalkan LO" onclick="confirmCancelLo(${row.lopk})">
                            <i class="fas fa-ban"></i>
                        </a>
                    ` : ''}
                </div>
            `;
    }

    // ============================================================
    // ROLE APPROVER -- ISI SESUAI GUSERPK YANG BENAR di sistem kamu.
    // ============================================================
    const guserpk = @json(session('guserpk'));
    const APPROVER_LEVEL1 = [ /* TODO: isi guserpk approver level 1 */ ];
    const APPROVER_LEVEL2 = [ /* TODO: isi guserpk approver level 2 */ ];
    const APPROVER_LEVEL3 = [ /* TODO: isi guserpk approver level 3 */ ];

    // ============================================================
    // MODAL KONFIRMASI GENERIK -- didefinisikan PALING AWAL karena
    // dipakai oleh confirmCancelLo/removeLoItem/rejectLo di bawah.
    // Pengganti confirm() bawaan browser.
    // ============================================================
    function openConfirmModal(opts) {
        $('#confirmActionTitle').html(
            `<i class="fas fa-triangle-exclamation" style="color:${opts.iconColor || '#dc2626'};"></i> ${opts.title || 'Konfirmasi'}`
            );
        $('#confirmActionMessage').text(opts.message || '');
        $('#confirmActionSubtext').text(opts.subtext || '');
        $('#confirmActionBtnLabel').text(opts.confirmLabel || 'Ya, Lanjutkan');
        $('#confirmActionBtnIcon').attr('class', 'small ' + (opts.confirmIcon || 'fas fa-check'));

        const $btn = $('#btnConfirmAction');
        $btn.attr('class', 'btn btn-sm px-4 d-inline-flex align-items-center gap-1 ' + (opts.confirmBtnClass ||
                'btn-dark'))
            .css({
                'font-size': '13px',
                'border-radius': '6px',
                'height': '33px'
            });

        $btn.off('click').on('click', function() {
            $btn.prop('disabled', true);
            bootstrap.Modal.getInstance(document.getElementById('confirmActionModal'))?.hide();
            if (typeof opts.onConfirm === 'function') opts.onConfirm();
            setTimeout(() => $btn.prop('disabled', false), 500);
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).show();
    }

    // ============================================================
    // MODAL: BUAT LO BARU
    // ============================================================
    let loCart = {}; // { bjpk: {bjpk, grade, pcs, POno, OP, material, secsz, sizes} }

    function openCreateLoModal() {
        loCart = {};
        $('#loKeterangan').val('');
        $('#loItemSearch').val('');
        renderLoCart();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('createLoModal')).show();
        loadLoItems();
    }

    let loItemSearchTimer = null;
    $(document).on('keyup', '#loItemSearch', function() {
        clearTimeout(loItemSearchTimer);
        loItemSearchTimer = setTimeout(loadLoItems, 300);
    });

    function loadLoItems() {
        $.get("{{ route('lo.available-items') }}", {
            search: $('#loItemSearch').val()
        }, function(data) {
            renderLoItemGroups(data.groups || []);
        });
    }

    function gradeClass(grade) {
        const g = String(grade || '').trim().toUpperCase();
        if (g === 'A') return 'grade-a';
        if (g === 'B') return 'grade-b';
        if (g === 'C') return 'grade-c';
        return '';
    }

    function buildLoSizeChips(sizes) {
        if (!sizes || !sizes.length) {
            return '<span class="lo-size-empty">Tidak ada breakdown size</span>';
        }
        return sizes.map(function(s) {
            return `
                    <div class="lo-size-chip">
                        <div class="sc-label">${s.label}</div>
                        <div class="sc-value">${s.qty}</div>
                    </div>
                `;
        }).join('');
    }

    // Expand size di modal BUAT LO (kartu browse item).
    function toggleLoSizeDetail(bjpk) {
        const $detail = $('#loSizeDetail_' + bjpk);
        const $icon = $('#loExpandIcon_' + bjpk);
        const $btn = $icon.closest('.lo-item-expand-btn');
        const isOpen = $detail.hasClass('is-open');
        $detail.toggleClass('is-open', !isOpen);
        $btn.toggleClass('is-open', !isOpen);
        $icon.toggleClass('fa-chevron-down fa-chevron-up');
    }

    // Expand size di modal DETAIL LO (tabel item) -- BEDA target
    // elemen dari toggleLoSizeDetail() di atas, jangan digabung.
    function toggleDetailSize(bjpk) {
        const $row = $('#loDetailSizeRow_' + bjpk);
        const $icon = $('#loDetailExpandIcon_' + bjpk);
        const isOpen = $row.is(':visible');
        $row.toggle(!isOpen);
        $icon.toggleClass('fa-chevron-down fa-chevron-up');
    }

    function renderLoItemGroups(groups) {
        const wrap = $('#loItemGroups');
        wrap.empty();
        if (!groups.length) {
            $('#loItemEmpty').removeClass('d-none');
            return;
        }
        $('#loItemEmpty').addClass('d-none');

        groups.forEach(function(g) {
            let itemsHtml = '';
            g.items.forEach(function(item) {
                const isSelected = !!loCart[item.bjpk];
                const sizesHtml = buildLoSizeChips(item.sizes);
                itemsHtml += `
                        <div class="lo-item-wrap ${isSelected ? 'selected' : ''}" data-bjpk="${item.bjpk}">
                            <div class="lo-item-card" onclick="toggleLoItem(${item.bjpk}, this.closest('.lo-item-wrap'))">
                                <div class="lo-item-grade-badge ${gradeClass(item.grade)}">${item.grade || '-'}</div>
                                <div class="lo-item-info">
                                    <div class="li-main">${item.material ?? '-'} ${item.secsz ? '(' + item.secsz + ')' : ''}</div>
                                    <div class="li-sub">${item.POno}</div>
                                </div>
                                <div class="lo-item-pcs">${item.pcs} pcs</div>
                                <span class="lo-item-expand-btn" title="Lihat detail size"
                                    onclick="event.stopPropagation(); toggleLoSizeDetail(${item.bjpk})">
                                    <i class="fas fa-chevron-down" id="loExpandIcon_${item.bjpk}"></i>
                                </span>
                                <input type="checkbox" class="form-check-input" ${isSelected ? 'checked' : ''} style="pointer-events:none;">
                            </div>
                            <div class="lo-item-size-detail" id="loSizeDetail_${item.bjpk}">
                                <div class="lo-size-chip-row">${sizesHtml}</div>
                            </div>
                        </div>
                    `;
                window['loItemData_' + item.bjpk] = item;
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

    function toggleLoItem(bjpk, wrapEl) {
        if (loCart[bjpk]) {
            delete loCart[bjpk];
        } else {
            loCart[bjpk] = window['loItemData_' + bjpk];
        }
        $(wrapEl).toggleClass('selected');
        $(wrapEl).find('input[type="checkbox"]').prop('checked', !!loCart[bjpk]);
        renderLoCart();
    }

    function removeFromLoCart(bjpk) {
        delete loCart[bjpk];
        renderLoCart();
        $(`.lo-item-wrap[data-bjpk="${bjpk}"]`).removeClass('selected')
            .find('input[type="checkbox"]').prop('checked', false);
    }

    function renderLoCart() {
        const bjpks = Object.keys(loCart);
        $('#loCartCount').text(bjpks.length);
        const list = $('#loCartList');
        list.empty();
        if (!bjpks.length) {
            list.html(
                '<div class="text-muted text-center py-4" style="font-size:12.5px;">Belum ada item dipilih.</div>');
            return;
        }
        bjpks.forEach(function(bjpk) {
            const item = loCart[bjpk];
            const sizesSummary = (item.sizes || []).map(s => `${s.label}:${s.qty}`).join('  ');
            list.append(`
                    <div class="lo-cart-row">
                        <div>
                            <strong>${item.material ?? '-'}</strong> (${item.grade})<br>
                            <span class="text-muted">${item.POno} &middot; ${item.pcs} pcs</span>
                            ${sizesSummary ? `<div class="lo-cart-sizes">${sizesSummary}</div>` : ''}
                        </div>
                        <i class="fas fa-times lc-remove" onclick="removeFromLoCart(${bjpk})"></i>
                    </div>
                `);
        });
    }

    function submitCreateLo() {
        const bjpks = Object.keys(loCart);
        if (!bjpks.length) {
            showToast('warning', 'Pilih minimal 1 item.');
            return;
        }
        $('#btnSubmitLo').prop('disabled', true);
        $.ajax({
            url: "{{ route('lo.store') }}",
            method: 'POST',
            data: {
                keterangan: $('#loKeterangan').val(),
                bjpks: bjpks
            },
            success: function(res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('createLoModal')).hide();
                window.EasyuiDG.reload('dgLo');
            },
            error: function(xhr) {
                const res = xhr.responseJSON || {
                    icon: 'error',
                    title: 'Gagal menyimpan LO.'
                };
                showToast(res.icon, res.title);
            },
            complete: function() {
                $('#btnSubmitLo').prop('disabled', false);
            }
        });
    }

    // ============================================================
    // MODAL: DETAIL LO
    // ============================================================
    let currentDetailLopk = null;

    function openDetailLoModal(lopk) {
        currentDetailLopk = lopk;
        $.get("{{ url('/kirim-sisa') }}/" + lopk, function(data) {
            renderLoDetail(data.lo, data.items || []);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('detailLoModal')).show();
        });
    }

    function renderLoDetail(lo, items) {
        $('#detailLoNo').text('#' + lo.lopk);
        $('#detailLoDate').text(formatLoDate(lo.lodate));
        $('#detailLoKeterangan').text(lo.keterangan || '-');

        const steps = [1, 2, 3].map(function(lvl) {
            const val = lo['stsapv' + lvl];
            let cls = 'bg-secondary-subtle text-secondary',
                icon = 'fa-circle';
            if (val === 1) {
                cls = 'bg-success-subtle text-success';
                icon = 'fa-check-circle';
            } else if (val === 0) {
                cls = 'bg-danger-subtle text-danger';
                icon = 'fa-times-circle';
            }
            return `<span class="badge ${cls}" style="font-size:11px;"><i class="fas ${icon} me-1"></i>Level ${lvl}</span>`;
        }).join('<i class="fas fa-arrow-right text-muted mx-1" style="font-size:10px;"></i>');
        $('#detailLoApprovalSteps').html(steps);

        const body = $('#detailLoItemsBody');
        body.empty();
        items.forEach(function(item) {
            const sizesHtml = buildLoSizeChips(item.sizes);
            body.append(`
                    <tr>
                        <td class="text-center">
                            <span class="lo-item-expand-btn" style="width:22px;height:22px;" title="Lihat detail size"
                                onclick="toggleDetailSize(${item.bjpk})">
                                <i class="fas fa-chevron-down" id="loDetailExpandIcon_${item.bjpk}" style="font-size:10px;"></i>
                            </span>
                        </td>
                        <td><span class="lo-item-grade-badge ${gradeClass(item.grade)}" style="width:26px;height:26px;font-size:11px;display:inline-flex;">${item.grade}</span></td>
                        <td style="font-size:12.5px;">${item.POno}<br><span class="text-muted">OP ${item.OP}</span></td>
                        <td style="font-size:12.5px;">${item.poref || '<span class="text-muted">-</span>'}</td>
                        <td style="font-size:12.5px;">${item.customer || '<span class="text-muted">-</span>'}</td>
                        <td style="font-size:12.5px;">${item.material ?? '-'} ${item.secsz ? '(' + item.secsz + ')' : ''}</td>
                        <td class="text-end fw-semibold">${item.pcs}</td>
                        <td class="text-center">
                            ${lo.stsapv1 !== 1 ? `<i class="fas fa-trash text-danger" style="cursor:pointer;" title="Hapus item" onclick="removeLoItem(${lo.lopk}, ${item.bjpk})"></i>` : ''}
                        </td>
                    </tr>
                    <tr id="loDetailSizeRow_${item.bjpk}" style="display:none;">
                        <td colspan="8" style="background:#fafbfc; padding:8px 12px;">
                            <div class="lo-size-chip-row">${sizesHtml}</div>
                        </td>
                    </tr>
                `);
        });

        let footer = '';
        const nextLevel = lo.stsapv1 !== 1 ? 1 : (lo.stsapv2 !== 1 ? 2 : (lo.stsapv3 !== 1 ? 3 : null));
        const approverMap = {
            1: APPROVER_LEVEL1,
            2: APPROVER_LEVEL2,
            3: APPROVER_LEVEL3
        };

        if (nextLevel && approverMap[nextLevel].includes(guserpk)) {
            footer += `
                    <button class="btn btn-outline-danger btn-sm" onclick="rejectLo(${lo.lopk}, ${nextLevel})">
                        <i class="fas fa-times me-1"></i>Tolak
                    </button>
                    <button class="btn btn-dark btn-sm" onclick="approveLo(${lo.lopk}, ${nextLevel})">
                        <i class="fas fa-check me-1"></i>Approve Level ${nextLevel}
                    </button>
                `;
        } else if (nextLevel) {
            footer += `<span class="text-muted" style="font-size:12px;">Menunggu approve Level ${nextLevel}.</span>`;
        } else {
            footer +=
                `<span class="text-success fw-semibold" style="font-size:12.5px;"><i class="fas fa-check-circle me-1"></i>LO sudah selesai (semua level approved).</span>`;
        }

        if (lo.stsapv1 !== 1) {
            footer +=
                `<button class="btn btn-outline-secondary btn-sm" onclick="confirmCancelLoFromDetail(${lo.lopk})"><i class="fas fa-ban me-1"></i>Batalkan LO</button>`;
        }

        $('#detailLoFooter').html(footer);
    }

    function approveLo(lopk, level) {
        $.ajax({
            url: "{{ url('/kirim-sisa') }}/" + lopk + "/approve/" + level,
            method: 'POST',
            success: function(res) {
                showToast(res.icon, res.title);
                openDetailLoModal(lopk);
                window.EasyuiDG.reload('dgLo');
            },
            error: function(xhr) {
                const res = xhr.responseJSON || {
                    icon: 'error',
                    title: 'Gagal approve.'
                };
                showToast(res.icon, res.title);
            }
        });
    }

    // ============================================================
    // AKSI DESTRUKTIF -- SEMUA pakai openConfirmModal(), TIDAK ADA
    // duplikasi definisi (masing-masing HANYA 1x di seluruh file).
    // ============================================================
    function rejectLo(lopk, level) {
        openConfirmModal({
            title: 'Tolak Approval',
            message: `Tolak LO #${lopk} di Level ${level}?`,
            subtext: 'LO tidak bisa dilanjutkan ke level berikutnya setelah ditolak.',
            confirmLabel: 'Ya, Tolak',
            confirmIcon: 'fas fa-times',
            confirmBtnClass: 'btn-danger',
            onConfirm: function() {
                $.ajax({
                    url: "{{ url('/kirim-sisa') }}/" + lopk + "/reject/" + level,
                    method: 'POST',
                    success: function(res) {
                        showToast(res.icon, res.title);
                        openDetailLoModal(lopk);
                        window.EasyuiDG.reload('dgLo');
                    },
                    error: function(xhr) {
                        const res = xhr.responseJSON || {
                            icon: 'error',
                            title: 'Gagal menolak.'
                        };
                        showToast(res.icon, res.title);
                    }
                });
            }
        });
    }

    function confirmCancelLo(lopk) {
        openConfirmModal({
            title: 'Batalkan LO',
            message: `Batalkan LO #${lopk}?`,
            subtext: 'Semua item yang sudah dipilih akan ter-unlock kembali dan bisa dimasukkan ke LO lain.',
            confirmLabel: 'Ya, Batalkan',
            confirmIcon: 'fas fa-ban',
            confirmBtnClass: 'btn-dark',
            onConfirm: function() {
                $.ajax({
                    url: "{{ url('/kirim-sisa') }}/" + lopk,
                    method: 'DELETE',
                    success: function(res) {
                        showToast(res.icon, res.title);
                        window.EasyuiDG.reload('dgLo');
                    },
                    error: function(xhr) {
                        const res = xhr.responseJSON || {
                            icon: 'error',
                            title: 'Gagal membatalkan LO.'
                        };
                        showToast(res.icon, res.title);
                    }
                });
            }
        });
    }

    function confirmCancelLoFromDetail(lopk) {
        bootstrap.Modal.getInstance(document.getElementById('detailLoModal'))?.hide();
        confirmCancelLo(lopk);
    }

    function removeLoItem(lopk, bjpk) {
        openConfirmModal({
            title: 'Hapus Item',
            message: 'Hapus item ini dari LO?',
            subtext: 'Item akan ter-unlock dan bisa dipilih lagi di LO lain setelah dihapus.',
            confirmLabel: 'Ya, Hapus',
            confirmIcon: 'fas fa-trash',
            confirmBtnClass: 'btn-dark',
            onConfirm: function() {
                $.ajax({
                    url: "{{ url('/kirim-sisa') }}/" + lopk + "/item/" + bjpk,
                    method: 'DELETE',
                    success: function(res) {
                        showToast(res.icon, res.title);
                        openDetailLoModal(lopk);
                        window.EasyuiDG.reload('dgLo');
                    },
                    error: function(xhr) {
                        const res = xhr.responseJSON || {
                            icon: 'error',
                            title: 'Gagal menghapus item.'
                        };
                        showToast(res.icon, res.title);
                    }
                });
            }
        });
    }
</script>
