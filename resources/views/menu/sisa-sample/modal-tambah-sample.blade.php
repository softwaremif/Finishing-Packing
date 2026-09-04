<div class="modal fade" id="tambahSampleModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold text-dark" id="tambahSampleModalTitle" style="font-size:15px;">
                    Cari SR# untuk Ditambahkan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-1">
                {{-- ===== STEP 1: CARI SR (kolom & search SAMA seperti index) ===== --}}
                <div id="stepSearchSR">
                    <div class="d-flex align-items-center mb-3">
                        <div class="position-relative" style="max-width:280px; width:100%;">
                            <i class="fas fa-search position-absolute text-muted"
                                style="left:12px; top:50%; transform:translateY(-50%); font-size:12px;"></i>
                            <input type="text" id="searchSRInput" class="form-control form-control-sm ps-4"
                                placeholder="Cari SR#..."
                                style="border-radius:6px;"
                                oninput="debouncedSearchSR()"
                                onkeydown="if(event.key==='Enter'){ event.preventDefault(); searchSR(); }">
                        </div>
                    </div>

                    {{-- FIX UTAMA: TIDAK pakai class="easyui-datagrid" -- supaya
                         EasyUI TIDAK auto-parse elemen ini saat page load (waktu
                         modal masih display:none, yang bikin datagrid salah
                         render/kosong). Inisialisasi & load dilakukan MANUAL
                         lewat JS, dipicu event shown.bs.modal. --}}
                    <table id="dgSRSearch" style="width:100%;height:480px"></table>
                </div>

                {{-- ===== STEP 2: PILIH SIZE ===== --}}
                <div id="stepSizeSelect" class="d-none">
                    <button type="button" class="btn btn-sm btn-link px-0 mb-3 text-decoration-none" onclick="backToSearchSR()">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke pencarian
                    </button>
                    <div id="srDetailInfo" class="mb-3 p-3 bg-light rounded-3" style="font-size:13px;"></div>
                    <div class="table-responsive" style="max-height:350px;">
                        <table class="table table-sm table-hover align-middle mb-0" id="sizeSelectTable">
                            <thead class="table-light sticky-top" style="font-size:12px;">
                                <tr>
                                    <th width="36" class="text-center">
                                        <input type="checkbox" id="checkAllSizes" onchange="toggleAllSizes(this)">
                                    </th>
                                    <th>Size</th>
                                    <th>CW#</th>
                                    <th class="text-end">Qty</th>
                                    <th width="120">Qty Sisa</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="sizeSelectBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            {{-- <div class="modal-footer border-0 pt-0" id="modalFooterSearch">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            </div> --}}
            <div class="modal-footer border-0 pt-0 d-none" id="modalFooterSizeSelect">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-dark" onclick="submitAddSizes()">
                    <i class="fas fa-check me-1"></i> Tambahkan yang Dipilih
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Tombol aksi di dalam modal -- SAMA gaya dengan .action-btn di index.blade.php */
    #tambahSampleModal .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #e0f2fe;
        color: #0369a1;
        font-size: 13px;
        transition: 0.2s;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }
    #tambahSampleModal .action-btn:hover {
        background: #bae6fd;
        color: #0c4a6e;
    }
</style>

<script>
    let currentSrpk = null;
    let currentStatuspk = null;
    let dgSRSearchInitialized = false;
    let srSearchDebounceTimer = null;

    // Base URL dari Blade -- otomatis ikut prefix subdirectory aplikasi
    // kalau ada, TIDAK hardcode "/sisa-sample/..." langsung.
    const sisaSampleBaseUrl = "{{ url('/sisa-sample') }}";
    const srSearchUrl = "{{ route('sisa-sample.cari.list') }}";

    // ============================================================
    // INISIALISASI DATAGRID -- kolom SAMA PERSIS dengan #dgOrder di
    // index.blade.php (action, no, srno, samplenm, date, buyernm, qty,
    // sendingdate, receiptdate, qtys, tglin, tglout, keterangan),
    // reuse formatter yang SAMA (formatSrFu, formatSampleOpsi,
    // formatQtyWash, formatNumber, formatDate) supaya konsisten 1:1.
    // Beda hanya di kolom Aksi: index -> link Detail, modal -> tombol Pilih.
    // ============================================================
    function initSRSearchGrid() {
        if (dgSRSearchInitialized) return;

        $('#dgSRSearch').datagrid({
            url: srSearchUrl,
            method: 'get',
            pagination: true,
            pageSize: 25,
            pageList: [25, 50, 100],
            rownumbers: false,
            singleSelect: true,
            fitColumns: false,
            border: false,
            loadMsg: 'Memuat data...',
            queryParams: { cari: '' },
            columns: [[
                { field: 'action', title: 'Aksi', width: 60, align: 'center', formatter: formatSRAction },
                { field: 'no', title: 'No', width: 50, align: 'center', formatter: formatNoModal },
                { field: 'srno', title: 'SR#', width: 130, formatter: formatSrFu },
                { field: 'samplenm', title: 'Sample Status<br>/ Option', width: 150, formatter: formatSampleOpsi },
                { field: 'date', title: 'Date', width: 90, formatter: formatDate },
                { field: 'buyernm', title: 'Buyer', width: 140 },
                { field: 'qty', title: 'Qty / Washing', width: 110, formatter: formatQtyWash },
                { field: 'sendingdate', title: 'Sending /<br>Receipt Date', width: 110, formatter: formatSendReceipt },
                { field: 'qtys', title: 'Qty Sisa', width: 100, align: 'right', formatter: formatNumber },
                { field: 'tglin', title: 'Tgl In /<br>Out Gdg', width: 110, formatter: formatInOut },
                { field: 'keterangan', title: 'Keterangan', width: 180 }
            ]],
            onBeforeLoad: clearEmptyStateModal,
            onLoadSuccess: onLoadSRSearchTable,
            onLoadError: onLoadSRSearchError
        });

        dgSRSearchInitialized = true;
    }

    // ============================================================
    // BUKA MODAL -- reset state, tampilkan modal. Datagrid di-init
    // & di-load setelah modal SELESAI transisi (shown.bs.modal),
    // supaya dimensi elemen sudah benar saat EasyUI menghitung layout.
    // ============================================================
    function openTambahSampleModal() {
        resetTambahModal();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('tambahSampleModal')).show();
    }

    function resetTambahModal() {
        currentSrpk = null;
        currentStatuspk = null;
        $('#searchSRInput').val('');

        $('#stepSearchSR').removeClass('d-none');
        $('#stepSizeSelect').addClass('d-none');
        $('#modalFooterSearch').removeClass('d-none');
        $('#modalFooterSizeSelect').addClass('d-none');
        $('#tambahSampleModalTitle').text('Cari SR# untuk Ditambahkan');
    }

    // Setiap kali modal SELESAI ditampilkan, pastikan datagrid ter-init
    // (sekali saja) lalu langsung load data (search kosong = semua data),
    // atau resize + reload kalau sudah pernah di-init sebelumnya.
    document.getElementById('tambahSampleModal').addEventListener('shown.bs.modal', function () {
        if (!dgSRSearchInitialized) {
            initSRSearchGrid();
        } else {
            $('#dgSRSearch').datagrid('resize');
            $('#dgSRSearch').datagrid('load', { cari: '' });
        }
    });

    function searchSR() {
        const cari = $('#searchSRInput').val();
        if (!dgSRSearchInitialized) {
            initSRSearchGrid();
            return;
        }
        $('#dgSRSearch').datagrid('load', { cari: cari });
    }

    // Debounce ketik di search box -- SAMA perilaku dengan search bar
    // x-table-default (search-name="cari") yang biasanya auto-search
    // tanpa perlu klik tombol.
    function debouncedSearchSR() {
        clearTimeout(srSearchDebounceTimer);
        srSearchDebounceTimer = setTimeout(searchSR, 400);
    }

    // ============================================================
    // EMPTY STATE -- SAMA PERSIS pola standar yang dipakai modul lain
    // (Packing, Polibag, dll): gambar no-data-6.svg + judul + subjudul,
    // BUKAN versi ad-hoc tanpa gambar sebelumnya.
    // ============================================================
    function clearEmptyStateModal() {
        $('#dgSRSearch').datagrid('getPanel')
            .find('.datagrid-view2 .easyui-empty-state')
            .remove();
    }

    function showEmptyStateModal(title, subtitle) {
        let panel = $('#dgSRSearch').datagrid('getPanel');
        let body = panel.find('.datagrid-view2 .datagrid-body');
        panel.find('.easyui-empty-state').remove();
        body.append(`
            <div class="easyui-empty-state">
                <div style="text-align:center">
                    <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="180">
                    <div style="margin-top:8px;font-weight:600;">${title}</div>
                    <div style="font-size:12px;color:#9ca3af;">${subtitle}</div>
                </div>
            </div>
        `);
    }

    function onLoadSRSearchTable(data) {
        const rows = data.rows || [];
        if (!rows.length) {
            showEmptyStateModal('No Data Found', 'Try changing filter');
        } else {
            clearEmptyStateModal();
        }
    }

    function onLoadSRSearchError() {
        showEmptyStateModal('Gagal memuat data', 'Silakan coba lagi');
    }

    // ============================================================
    // FORMATTER -- REUSE PERSIS dari index.blade.php (formatSrFu,
    // formatSampleOpsi, formatQtyWash, formatNumber, formatDate sudah
    // didefinisikan global di index.blade.php's @section('js_custom'),
    // jadi TIDAK didefinisikan ulang di sini supaya satu sumber
    // kebenaran -- satu-satunya formatter baru khusus modal adalah
    // formatSRAction (tombol Pilih) dan formatNoModal (nomor urut).
    // ============================================================
    function formatNoModal(value, row, index) {
        try {
            const opts = $('#dgSRSearch').datagrid('options');
            return ((opts.pageNumber - 1) * opts.pageSize) + index + 1;
        } catch (e) {
            return index + 1;
        }
    }

    function formatSRAction(value, row) {
        return `
            <a href="javascript:void(0)" class="action-btn" title="Pilih SR# ini"
                onclick="selectSR(${row.srpk}, ${row.statuspk})">
                <i class="fas fa-plus"></i>
            </a>
        `;
    }

    // ============================================================
    // STEP 2: PILIH SIZE
    // ============================================================
    function selectSR(srpk, statuspk) {
        currentSrpk = srpk;
        currentStatuspk = statuspk;

        $.get(`${sisaSampleBaseUrl}/detail/${srpk}/${statuspk}/sizes`, function (res) {
            renderSizeSelect(res.rows || []);
            $('#stepSearchSR').addClass('d-none');
            $('#stepSizeSelect').removeClass('d-none');
            $('#modalFooterSearch').addClass('d-none');
            $('#modalFooterSizeSelect').removeClass('d-none');
            $('#tambahSampleModalTitle').text('Pilih Size untuk Ditambahkan');
        });
    }

    function backToSearchSR() {
        $('#stepSizeSelect').addClass('d-none');
        $('#stepSearchSR').removeClass('d-none');
        $('#modalFooterSizeSelect').addClass('d-none');
        $('#modalFooterSearch').removeClass('d-none');
        $('#tambahSampleModalTitle').text('Cari SR# untuk Ditambahkan');
    }

    function renderSizeSelect(rows) {
        const $body = $('#sizeSelectBody');
        $body.empty();
 
        if (!rows.length) {
            $body.html('<tr><td colspan="6" class="text-center text-muted py-3">Tidak ada data size.</td></tr>');
            return;
        }
 
        rows.forEach(function (row) {
            const already = Number(row.masuk) === 1;
 
            // Tambah data-qty (batas maksimal) + attribute
            // max, dan validasi live saat diketik.
            const qtysInputHtml = already
                ? `<input type="number" class="form-control form-control-sm" value="${row.qtys ?? 0}" readonly>`
                : `<input type="number" class="form-control form-control-sm qtys-input-modal"
                        data-sizepk="${row.sizepk}" data-qty="${row.qty ?? 0}"
                        max="${row.qty ?? 0}" min="0"
                        placeholder="0" value="${row.qtys ?? ''}"
                        oninput="validateQtysInput(this)">`;
 
            $body.append(`
                <tr>
                    <td class="text-center">
                        <input type="checkbox" class="size-checkbox" value="${row.sizepk}" ${already ? 'checked disabled' : ''}>
                    </td>
                    <td>${row.size ?? '-'}</td>
                    <td>${row.cw ?? '-'}</td>
                    <td class="text-end">${row.qty ?? 0}</td>
                    <td>${qtysInputHtml}<div class="invalid-feedback d-block" style="font-size:11px;"></div></td>
                    <td class="text-center">
                        ${already
                            ? '<span class="badge bg-success-subtle text-success">Sudah Ditambahkan</span>'
                            : '<span class="badge bg-secondary-subtle text-secondary">Belum</span>'}
                    </td>
                </tr>
            `);
        });
    }

    function validateQtysInput(input) {
        const $input = $(input);
        const qty = parseFloat($input.data('qty')) || 0;
        const rawVal = $input.val();
        const val = parseFloat(rawVal);
        const $feedback = $input.closest('td').find('.invalid-feedback');
 
        // FIX UTAMA: kosong ATAU 0 -- tidak boleh.
        if (rawVal === '' || rawVal === null || isNaN(val) || val <= 0) {
            $input.addClass('is-invalid');
            $feedback.text('Qty Sisa wajib diisi, tidak boleh 0.');
            return false;
        }
 
        if (val > qty) {
            $input.addClass('is-invalid');
            $feedback.text(`Maksimal ${qty} (Qty Sample).`);
            return false;
        }
 
        $input.removeClass('is-invalid');
        $feedback.text('');
        return true;
    }

    function toggleAllSizes(el) {
        $('.size-checkbox:not(:disabled)').prop('checked', el.checked);
    }

    function submitAddSizes() {
        const sizes = [];
        let hasError = false;
 
        $('.size-checkbox:not(:disabled):checked').each(function () {
            const sizepk = Number($(this).val());
            const $qtysInput = $(`.qtys-input-modal[data-sizepk="${sizepk}"]`);
            const qtys = $qtysInput.length ? $qtysInput.val() : null;
 
            if ($qtysInput.length && !validateQtysInput($qtysInput[0])) {
                hasError = true;
            }
 
            sizes.push({ sizepk: sizepk, qtys: qtys });
        });
 
        if (hasError) {
            showToast('warning', 'Ada Qty Sisa yang kosong/0 atau melebihi Qty Sample. Periksa kembali sebelum menyimpan.');
            return;
        }
 
        if (!sizes.length) {
            showToast('warning', 'Pilih minimal satu size yang belum ditambahkan.');
            return;
        }
 
        $.ajax({
            url: "{{ route('sisa-sample.size.add-multiple') }}",
            method: 'POST',
            data: { sizes: sizes },
            success: function (res) {
                showToast(res.icon, res.title);
                bootstrap.Modal.getInstance(document.getElementById('tambahSampleModal')).hide();
 
                if (window.EasyuiDG) {
                    window.EasyuiDG.reload('dgOrder');
                } else if ($('#dgOrder').data('datagrid')) {
                    $('#dgOrder').datagrid('reload');
                }
            },
            error: function (xhr) {
                const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                showToast(res.icon, res.title);
            }
        });
    }
</script>