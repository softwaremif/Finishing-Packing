@extends('layout.main')
@php
    $cfg = array_merge([
        'mode' => 'kirim',
        'title' => 'Daftar LO',
        'showAddButton' => true,
        'showStatusFilter' => true,
        'routes' => [],
    ], $pageConfig ?? []);
@endphp

@section('css_custom')
    <style>
        /* ============================================================
           STYLE HALAMAN INDEX (SUDAH ADA -- tidak berubah)
           ============================================================ */
        .page-wrap { padding: 16px; }
        .action-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 30px; height: 30px; border-radius: 8px;
            background: #e0f2fe; color: #0369a1; font-size: 13px;
            transition: .2s; border: none; cursor: pointer;
            text-decoration: none;
        }
        .action-btn:hover { background: #bae6fd; color: #0c4a6e; transform: scale(1.05); text-decoration: none; }
        a.action-btn.action-btn-danger { background: #fee2e2; color: #b91c1c; }
        a.action-btn.action-btn-danger:hover { background: #fecaca; color: #7f1d1d; }
        a.action-btn.action-btn-success { background: #dcfce7; color: #166534; }
        a.action-btn.action-btn-success:hover { background: #bbf7d0; color: #14532d; }
        .action-btn.action-btn-success { background: #dcfce7; color: #166534; } /* untuk <span> non-klik juga */
        .action-btn.action-btn-muted { background: #f1f5f9; color: #94a3b8; cursor: default; }
        .status-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px; }
        .status-pill.st-pending  { background: #fef3c7; color: #92400e; }
        .status-pill.st-progress { background: #dbeafe; color: #1e40af; }
        .status-pill.st-done     { background: #dcfce7; color: #166534; }
        .status-pill.st-rejected { background: #fee2e2; color: #991b1b; }
        .status-pill.st-ready    { background: #ede9fe; color: #6d28d9; }

        /* ============================================================
           PINDAH DARI modal-create.blade.php -- FIX UTAMA: SEKARANG
           selalu ke-load di kedua mode (kirim & terima), karena dulu
           cuma ada di file modal-create yang TIDAK di-include di
           halaman terima-sisa.
           ============================================================ */
        .lo-po-group { margin-bottom: 18px; }
        .lo-po-group-title {
            font-size: 12.5px; font-weight: 700; color: #374151;
            margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
        }
        .lo-po-group-title .buyer-tag { font-size: 11px; color: #94a3b8; font-weight: 500; }

        .lo-item-wrap {
            border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 6px;
            overflow: hidden; background: #fff; transition: border-color .15s ease;
        }
        .lo-item-wrap.selected { border-color: #359DD9; box-shadow: 0 0 0 2px rgba(53,157,217,.15); }

        .lo-item-card {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; cursor: pointer; transition: background .15s ease;
        }
        .lo-item-card:hover { background: #f8fafc; }
        .lo-item-wrap.selected .lo-item-card { background: #f0f9ff; }

        .lo-item-grade-badge {
            width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 13px; color: #fff;
        }
        .lo-item-grade-badge.grade-a { background: #16a34a; }
        .lo-item-grade-badge.grade-b { background: #2563eb; }
        .lo-item-grade-badge.grade-c { background: #d97706; }
        .lo-item-info { flex: 1 1 auto; min-width: 0; }
        .lo-item-info .li-main { font-size:12.5px; font-weight:600; color:#0f172a; }
        .lo-item-info .li-sub { font-size:11px; color:#64748b; }
        .lo-item-pcs { font-size: 13px; font-weight: 700; color: #0f172a; flex-shrink: 0; }

        .lo-item-expand-btn {
            width: 26px; height: 26px; border-radius: 6px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            color: #94a3b8; background: #f8fafc; cursor: pointer;
            transition: .15s ease;
        }
        .lo-item-expand-btn:hover { background: #e2e8f0; color: #1e293b; }
        .lo-item-expand-btn.is-open { background: #1e293b; color: #fff; }

        .lo-item-size-detail {
            display: none;
            padding: 10px 12px 12px 46px;
            background: #fafbfc;
            border-top: 1px dashed #e5e7eb;
        }
        .lo-item-size-detail.is-open { display: block; }
        .lo-size-chip-row {
            display: flex; flex-wrap: wrap; gap: 6px;
        }
        .lo-size-chip {
            background: #fff; border: 1px solid #eef1f5; border-radius: 6px;
            padding: 4px 8px; text-align: center; min-width: 44px;
        }
        .lo-size-chip .sc-label { font-size: 9px; color: #94a3b8; font-weight: 700; text-transform: uppercase; }
        .lo-size-chip .sc-value { font-size: 13px; font-weight: 800; color: #0f172a; }
        .lo-size-empty { font-size: 11.5px; color: #94a3b8; }

        .lo-cart-row {
            display: flex; align-items: flex-start; justify-content: space-between;
            font-size: 12px; padding: 8px 0; border-bottom: 1px dashed #e2e8f0;
        }
        .lo-cart-row .lc-remove { color: #dc2626; cursor: pointer; flex-shrink: 0; margin-left: 8px; }
        .lo-cart-sizes { font-size: 10.5px; color: #94a3b8; margin-top: 2px; }
        .status-pill.st-received { background: #f1f5f9; color: #475569; }
    </style>
@endsection

@section('content')
    <div class="page-wrap">
        <x-table-default id="dgLo" :search="false" :buyer="false" :year="false" :exfactory="false" :sort="false">
            <x-slot name="filters">
                <div class="d-flex justify-content-between align-items-center" style="flex:1 1 100%;">
                    <div class="order-title mb-0">{{ $cfg['title'] }}</div>
                    @if ($cfg['showAddButton'])
                        <button class="btn btn-dark btn-sm d-inline-flex align-items-center px-3 fw-semibold"
                            style="font-size:12.5px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                            onclick="openCreateLoModal()">
                            <i class="fas fa-plus me-1.5"></i> Buat LO Baru
                        </button>
                    @endif
                </div>

                <div class="input-group" style="width:200px;">
                    <span class="input-group-text search">
                        <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18">
                    </span>
                    <input type="text" class="form-control search" data-dg-filter="lopk" data-dg-filter-type="search"
                        data-dg-chip-label="No LO" placeholder="Cari No LO...">
                </div>

                @if ($cfg['showStatusFilter'])
                    <input data-dg-filter="status" data-dg-filter-type="select" data-dg-chip-label="Status Approve"
                        data-dg-options='[
                            {"value":"","text":"Semua Status"},
                            {"value":"pending1","text":"Menunggu Approve Manager"},
                            {"value":"pending2","text":"Menunggu Approve PPIC"},
                            {"value":"pending3","text":"Menunggu Approve Purchasing"},
                            {"value":"approved","text":"Selesai (Approved)"},
                            {"value":"rejected","text":"Ditolak"}
                        ]'
                        data-dg-default="" style="width:230px">
                @endif
            </x-slot>

            <table id="dgLo" class="easyui-datagrid" style="width:100%;height:600px" url="{{ $cfg['routes']['list'] }}"
                method="get" pagination="true" pageSize="50" pageList="[25,50,100]" rownumbers="false"
                singleSelect="true" fitColumns="false" border="false">
                <thead>
                    <tr>
                        <th field="action" width="150" align="center" formatter="formatLoAction">Aksi</th>
                        <th field="lopk" width="250" align="center" formatter="formatLopk">No LO</th>
                        <th field="lodate" width="250" align="center" formatter="formatLoDate">Tanggal Dibuat</th>
                        <th field="keterangan" width="250" formatter="formatDashLo">Keterangan</th>
                        <th field="jumlah_item" width="100" align="center">Jumlah Item</th>
                        <th field="status_label" width="250" align="center" formatter="formatStatusPill">Status</th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>

    @if ($cfg['showAddButton'] && !empty($cfg['routes']['modalCreate']))
        @include($cfg['routes']['modalCreate'])
    @endif
    @include('menu.shared.lo-modal-detail')
    @include('menu.lo.modal-confirm')
@endsection

@section('js_custom')
    <script>
        window.loPageCfg = @json($cfg);
    </script>
    <script>
        // ============================================================
        // ROLE APPROVER -- ISI SESUAI GUSERPK YANG BENAR di sistem kamu.
        // ============================================================
        const guserpk = @json(session('guserpk'));
        const APPROVER_LEVEL1 = [/* TODO: isi guserpk approver Manager */];
        const APPROVER_LEVEL2 = [/* TODO: isi guserpk approver PPIC */];
        const APPROVER_LEVEL3 = [/* TODO: isi guserpk approver Purchasing */];

        // ============================================================
        // FORMATTER INDEX (satu-satunya definisi -- versi mode-aware)
        // ============================================================
        function formatLopk(value, row) {
            return row.no_lo || `#${value}`;
        }

        function formatDashLo(value) { return value ?? '<span class="text-muted">-</span>'; }
        function formatLoDate(value) {
            if (!value) return '-';
            const d = new Date(value.replace(' ', 'T'));
            if (isNaN(d)) return value;
            const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            return `${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
        }

        function formatStatusPill(value) {
            let cls = 'st-pending';
            if (String(value).includes('Ditolak')) cls = 'st-rejected';
            else if (String(value).includes('Sudah Diterima')) cls = 'st-received'; // BARU -- cek DULUAN
            else if (String(value).includes('Siap Diterima')) cls = 'st-ready';
            else if (String(value).includes('Selesai')) cls = 'st-done';
            else if (String(value).includes('Menunggu Approve')) cls = 'st-progress';
            return `<span class="status-pill ${cls}">${value}</span>`;
        }

        // FIX UTAMA: aksi Batalkan HANYA muncul di mode 'kirim'.
        function formatLoAction(value, row) {
            const cfg = window.loPageCfg;
            const isKirimMode = cfg.mode === 'kirim';
            const canEdit = row.can_edit === true;
        
            // BARU -- FIX UTAMA: kalau ketiga level SUDAH approve, tombol
            // Kirim Email tidak perlu ada lagi (tidak ada gunanya kirim ulang).
            const allApproved = row.stsapv1 === 1 && row.stsapv2 === 1 && row.stsapv3 === 1;
        
            let html = `
                <div class="d-flex justify-content-center gap-1">
                    <a href="javascript:void(0)" class="action-btn" title="Lihat Detail" onclick="openDetailLoModal(${row.lopk}, ${row.mif})">
                        <i class="fas fa-eye"></i>
                    </a>
            `;
        
            if (isKirimMode) {
                if (canEdit) {
                    html += `
                        <a href="javascript:void(0)" class="action-btn" style="background:#fef3c7;color:#92400e;" title="Edit LO"
                            onclick="openEditLoModal(${row.lopk}, ${row.mif})">
                            <i class="fas fa-pen"></i>
                        </a>
                    `;
                }
                if (!allApproved) {
                    html += `
                        <a href="javascript:void(0)" class="action-btn" style="background:#ede9fe;color:#6d28d9;" title="Kirim Email Approval"
                            onclick="sendLoEmailAction(${row.lopk}, ${row.mif})">
                            <i class="fas fa-paper-plane"></i>
                        </a>
                    `;
                }
                if (canEdit) {
                    html += `
                        <a href="javascript:void(0)" class="action-btn action-btn-danger" title="Batalkan LO"
                            onclick="confirmCancelLo(${row.lopk}, ${row.mif})">
                            <i class="fas fa-ban"></i>
                        </a>
                    `;
                }
            }
        
            html += `</div>`;
            return html;
        }

        function sendLoEmailAction(lopk, mif) {
            $.ajax({
                url: "{{ url('/kirim-sisa') }}/" + lopk + "/send-email",
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
        // MODAL KONFIRMASI GENERIK
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
        // MODAL: BUAT LO BARU (hanya dipakai di mode 'kirim', tapi
        // fungsi ini tetap boleh ada di halaman 'terima' -- cuma tidak
        // pernah dipanggil karena tombolnya tidak dirender).
        // ============================================================
        let loCart = {};
        let loEditLopk = null;
 
        function openCreateLoModal() {
            loEditLopk = null;
            loCart = {};
            $('#loKeterangan').val('');
            $('#loItemSearch').val('');
            $('#createLoModalTitle').html('<span class="rounded me-2" style="width:4px;height:16px;display:inline-block;background:#1e293b;"></span>Buat LO | Kirim Sisa ke Gudang');
            $('#btnSubmitLo').html('<i class="fas fa-paper-plane me-1"></i> Simpan');
            renderLoCart();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('createLoModal')).show();
            loadLoItems();
        }

        function openEditLoModal(lopk, mif) {
            loEditLopk = lopk;
            loCart = {};
            $('#loItemSearch').val('');
            $('#createLoModalTitle').html('<span class="rounded me-2" style="width:4px;height:16px;display:inline-block;background:#1e293b;"></span>Edit LO #' + lopk);
            $('#btnSubmitLo').html('<i class="fas fa-save me-1"></i> Simpan Perubahan');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('createLoModal')).show();
        
            const urlBase = window.loPageCfg.routes.detailByLopk.replace('__LOPK__', lopk) + '?mif=' + mif;
            $.get(urlBase, function (data) {
                $('#loKeterangan').val(data.lo.keterangan || '');
                (data.items || []).forEach(function (item) {
                    loCart[item.bjpk] = item; // item sudah punya bjpk/grade/pcs/sizes/dll, langsung reuse
                });
                renderLoCart();
                loadLoItems(); // browse list -- item yg sudah ada di cart otomatis ke-mark 'selected'
            });
        }


        let loItemSearchTimer = null;
        $(document).on('keyup', '#loItemSearch', function () {
            clearTimeout(loItemSearchTimer);
            loItemSearchTimer = setTimeout(loadLoItems, 300);
        });

        function loadLoItems() {
            $.get("{{ route('lo.available-items') }}", { search: $('#loItemSearch').val() }, function (data) {
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
            return sizes.map(function (s) {
                return `
                    <div class="lo-size-chip">
                        <div class="sc-label">${s.label}</div>
                        <div class="sc-value">${s.qty}</div>
                    </div>
                `;
            }).join('');
        }

        function toggleLoSizeDetail(bjpk) {
            const $detail = $('#loSizeDetail_' + bjpk);
            const $icon = $('#loExpandIcon_' + bjpk);
            const $btn = $icon.closest('.lo-item-expand-btn');
            const isOpen = $detail.hasClass('is-open');
            $detail.toggleClass('is-open', !isOpen);
            $btn.toggleClass('is-open', !isOpen);
            $icon.toggleClass('fa-chevron-down fa-chevron-up');
        }

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
            groups.forEach(function (g) {
                let itemsHtml = '';
                g.items.forEach(function (item) {
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
                list.html('<div class="text-muted text-center py-4" style="font-size:12.5px;">Belum ada item dipilih.</div>');
                return;
            }
            bjpks.forEach(function (bjpk) {
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
        
            const isEdit = loEditLopk !== null;
            const url = isEdit ? ("{{ url('/kirim-sisa') }}/" + loEditLopk) : "{{ route('lo.store') }}";
            const method = isEdit ? 'PUT' : 'POST';
        
            $.ajax({
                url: url,
                method: method,
                data: { keterangan: $('#loKeterangan').val(), bjpks: bjpks },
                success: function (res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('createLoModal')).hide();
                    window.EasyuiDG.reload('dgLo');
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || { icon: 'error', title: 'Gagal menyimpan.' };
                    showToast(res.icon, res.title);
                },
                complete: function () { $('#btnSubmitLo').prop('disabled', false); }
            });
        }

        // ============================================================
        // MODAL: DETAIL LO (satu-satunya definisi -- versi mode-aware,
        // pakai cfg.routes.detailByLopk, bukan hardcode /kirim-sisa)
        // ============================================================
        let currentDetailLopk = null;
        let currentDetailMif = null; // BARU
        
        function openDetailLoModal(lopk, mif) {
            currentDetailLopk = lopk;
            currentDetailMif = mif; // BARU
            const cfg = window.loPageCfg;
            const urlBase = cfg.routes.detailByLopk.replace('__LOPK__', lopk) + '?mif=' + mif;
            $.get(urlBase, function (data) {
                renderLoDetail(data.lo, data.items || []);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('detailLoModal')).show();
            });
        }

        function renderLoDetail(lo, items) {
            const cfg = window.loPageCfg;

            $('#detailLoNo').text(lo.no_lo || ('#' + lo.lopk));
            $('#detailLoDate').text(formatLoDate(lo.lodate));
            $('#detailLoKeterangan').text(lo.keterangan || '-');

            const stepLabels = { 1: 'Manager', 2: 'PPIC', 3: 'Purchasing' };
            const steps = [1, 2, 3].map(function (lvl) {
                const val = lo['stsapv' + lvl];
                let cls = 'bg-secondary-subtle text-secondary', icon = 'fa-circle';
                if (val === 1) { cls = 'bg-success-subtle text-success'; icon = 'fa-check-circle'; }
                else if (val === 0) { cls = 'bg-danger-subtle text-danger'; icon = 'fa-times-circle'; }
                return `<span class="badge ${cls}" style="font-size:11px;"><i class="fas ${icon} me-1"></i>${stepLabels[lvl]}</span>`;
            }).join('<i class="fas fa-arrow-right text-muted mx-1" style="font-size:10px;"></i>');
            $('#detailLoApprovalSteps').html(steps);

            const body = $('#detailLoItemsBody');
            body.empty();
            const allApproved = lo.stsapv1 === 1 && lo.stsapv2 === 1 && lo.stsapv3 === 1;
            const canDeleteItem = cfg.mode === 'kirim' && lo.stsapv1 !== 1;

            items.forEach(function (item) {
                const sizesHtml = buildLoSizeChips(item.sizes);
                const tglDiterimaLabel = item.tglin ? formatLoDate(item.tglin) : '<span class="text-muted">-</span>';
            
                let actionCell = '';
 
                if (cfg.mode === 'kirim') {
                    actionCell = ''; // tidak ada aksi apa pun -- memang kosong, bukan soal styling
                } else if (cfg.mode === 'terima') {
                    if (item.tglin) {
                        actionCell = buildActionChip({
                            icon: 'fa-check-circle',
                            colorClass: 'action-btn-success',
                            clickable: false,
                            title: 'Sudah diterima',
                        });
                    } else if (allApproved) {
                        actionCell = buildActionChip({
                            icon: 'fa-box-open',
                            colorClass: 'action-btn-success',
                            clickable: true,
                            onClick: `terimaItemPerRow(${lo.lopk}, ${item.bjpk})`,
                            title: 'Terima item ini',
                        });
                    } else {
                        actionCell = '';
                    }
                }
            
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
                        <td style="font-size:12px;">${tglDiterimaLabel}</td>
                        <td class="text-center">${actionCell}</td>
                    </tr>
                    <tr id="loDetailSizeRow_${item.bjpk}" style="display:none;">
                        <td colspan="9" style="background:#fafbfc; padding:8px 12px;">
                            <div class="lo-size-chip-row">${sizesHtml}</div>
                        </td>
                    </tr>
                `);
            });

            let footer = '';

            if (cfg.mode === 'terima') {
                const allReceived = items.length > 0 && items.every(i => i.tglin !== null);
                if (allReceived) {
                    footer = `<span class="text-success fw-semibold" style="font-size:12.5px;"><i class="fas fa-check-circle me-1"></i>Semua item sudah diterima.</span>`;
                } else if (allApproved) {
                    footer = `<span class="text-muted" style="font-size:12px;">Klik ikon <i class="fas fa-box-open text-success"></i> di tiap baris untuk konfirmasi penerimaan barang.</span>`;
                } else {
                    footer = `<span class="text-muted" style="font-size:12px;">LO ini belum selesai approve 3 level.</span>`;
                }
            } else {
                let anyButtonShown = false;
                const approverMap = { 1: APPROVER_LEVEL1, 2: APPROVER_LEVEL2, 3: APPROVER_LEVEL3 };
                const levelLabels = { 1: 'Manager', 2: 'PPIC', 3: 'Purchasing' };
            
                [1, 2, 3].forEach(function (lvl) {
                    const val = lo['stsapv' + lvl];
                    if (val === null && approverMap[lvl].includes(guserpk)) {
                        anyButtonShown = true;
                        footer += `
                            <div class="d-flex gap-1 mb-1 w-100">
                                <button class="btn btn-outline-danger btn-sm flex-fill" onclick="rejectLo(${lo.lopk}, ${lvl})">
                                    <i class="fas fa-times me-1"></i>Tolak (${levelLabels[lvl]})
                                </button>
                                <button class="btn btn-dark btn-sm flex-fill" onclick="approveLo(${lo.lopk}, ${lvl})">
                                    <i class="fas fa-check me-1"></i>Approve (${levelLabels[lvl]})
                                </button>
                            </div>
                        `;
                    }
                });
            
                if (!anyButtonShown) {
                    const allDone = lo.stsapv1 !== null && lo.stsapv2 !== null && lo.stsapv3 !== null;
                    footer += allDone
                        ? `<span class="text-success fw-semibold" style="font-size:12.5px;"><i class="fas fa-check-circle me-1"></i>Semua approval sudah diproses.</span>`
                        : `<span class="text-muted" style="font-size:12px;">Menunggu approver lain.</span>`;
                }
            
                if (lo.can_edit) {
                    footer += `<button class="btn btn-outline-secondary btn-sm" onclick="confirmCancelLoFromDetail(${lo.lopk})"><i class="fas fa-ban me-1"></i>Batalkan LO</button>`;
                }
            }

            $('#detailLoFooter').html(footer);
        }

        // ============================================================
        // AKSI KHUSUS MODE 'kirim' -- hardcode '/kirim-sisa' AMAN karena
        // fungsi ini TIDAK PERNAH terpanggil di mode 'terima' (tombol
        // pemicunya tidak pernah dirender oleh renderLoDetail() di atas).
        // ============================================================
        function approveLo(lopk, level) {
            $.ajax({
                url: "{{ url('/kirim-sisa') }}/" + lopk + "/approve/" + level,
                method: 'POST',
                data: { mif: currentDetailMif }, // BARU
                success: function (res) {
                    showToast(res.icon, res.title);
                    openDetailLoModal(lopk, currentDetailMif); // BARU -- pakai mif yg tersimpan
                    window.EasyuiDG.reload('dgLo');
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || { icon: 'error', title: 'Gagal approve.' };
                    showToast(res.icon, res.title);
                }
            });
        }

        function rejectLo(lopk, level) {
            openConfirmModal({
                title: 'Tolak Approval',
                message: `Tolak LO #${lopk} di Level ${level}?`,
                subtext: 'LO tidak bisa dilanjutkan ke level berikutnya setelah ditolak.',
                confirmLabel: 'Ya, Tolak',
                confirmIcon: 'fas fa-times',
                confirmBtnClass: 'btn-danger',
                onConfirm: function () {
                    $.ajax({
                        url: "{{ url('/kirim-sisa') }}/" + lopk + "/reject/" + level,
                        method: 'POST',
                        data: { mif: currentDetailMif }, // BARU
                        success: function (res) {
                            showToast(res.icon, res.title);
                            openDetailLoModal(lopk, currentDetailMif);
                            window.EasyuiDG.reload('dgLo');
                        },
                        error: function (xhr) {
                            const res = xhr.responseJSON || { icon: 'error', title: 'Gagal menolak.' };
                            showToast(res.icon, res.title);
                        }
                    });
                }
            });
        }

        function confirmCancelLo(lopk, mif) {
            openConfirmModal({
                title: 'Batalkan LO',
                message: `Batalkan LO #${lopk}?`,
                subtext: 'Semua item yang sudah dipilih akan ter-unlock kembali dan bisa dimasukkan ke LO lain.',
                confirmLabel: 'Ya, Batalkan',
                confirmIcon: 'fas fa-ban',
                confirmBtnClass: 'btn-dark',
                onConfirm: function () {
                    $.ajax({
                        url: "{{ url('/kirim-sisa') }}/" + lopk,
                        method: 'DELETE',
                        data: { mif: mif }, // BARU
                        success: function (res) {
                            showToast(res.icon, res.title);
                            window.EasyuiDG.reload('dgLo');
                        },
                        error: function (xhr) {
                            const res = xhr.responseJSON || { icon: 'error', title: 'Gagal membatalkan LO.' };
                            showToast(res.icon, res.title);
                        }
                    });
                }
            });
        }
        
        function confirmCancelLoFromDetail(lopk) {
            bootstrap.Modal.getInstance(document.getElementById('detailLoModal'))?.hide();
            confirmCancelLo(lopk, currentDetailMif); // BARU -- pakai mif yg tersimpan
        }

        function removeLoItem(lopk, bjpk) {
            openConfirmModal({
                title: 'Hapus Item',
                message: 'Hapus item ini dari LO?',
                subtext: 'Item akan ter-unlock dan bisa dipilih lagi di LO lain setelah dihapus.',
                confirmLabel: 'Ya, Hapus',
                confirmIcon: 'fas fa-trash',
                confirmBtnClass: 'btn-dark',
                onConfirm: function () {
                    $.ajax({
                        url: "{{ url('/kirim-sisa') }}/" + lopk + "/item/" + bjpk,
                        method: 'DELETE',
                        data: { mif: currentDetailMif }, // BARU
                        success: function (res) {
                            showToast(res.icon, res.title);
                            openDetailLoModal(lopk, currentDetailMif);
                            window.EasyuiDG.reload('dgLo');
                        },
                        error: function (xhr) {
                            const res = xhr.responseJSON || { icon: 'error', title: 'Gagal menghapus item.' };
                            showToast(res.icon, res.title);
                        }
                    });
                }
            });
        }

        // ============================================================
        // AKSI KHUSUS MODE 'terima'
        // ============================================================
        function terimaItemPerRow(lopk, bjpk) {
            openConfirmModal({
                title: 'Terima Barang',
                message: `Konfirmasi item ini sudah diterima gudang?`,
                subtext: 'Aksi ini tidak dapat dibatalkan setelah dikonfirmasi.',
                confirmLabel: 'Ya, Sudah Diterima',
                confirmIcon: 'fas fa-box-open',
                confirmBtnClass: 'btn-dark',
                onConfirm: function () {
                    $.ajax({
                        url: "{{ url('/terima-sisa') }}/" + lopk + "/item/" + bjpk + "/terima",
                        method: 'POST',
                        data: { mif: currentDetailMif },
                        success: function (res) {
                            showToast(res.icon, res.title);
                            openDetailLoModal(lopk, currentDetailMif); // refresh modal biar baris ter-update
                            window.EasyuiDG.reload('dgLo');
                        },
                        error: function (xhr) {
                            const res = xhr.responseJSON || { icon: 'error', title: 'Gagal menerima item.' };
                            showToast(res.icon, res.title);
                        }
                    });
                }
            });
        }

        function buildActionChip({ icon, colorClass, clickable, onClick, title }) {
            const tag = clickable ? 'a' : 'span';
            const hrefAttr = clickable ? 'href="javascript:void(0)"' : '';
            const onclickAttr = clickable && onClick ? `onclick="${onClick}"` : '';
            return `
                <${tag} ${hrefAttr} class="action-btn ${colorClass}" style="width:26px;height:26px;" title="${title}" ${onclickAttr}>
                    <i class="fas ${icon}" style="font-size:11px;"></i>
                </${tag}>
            `;
        }
    </script>
@endsection