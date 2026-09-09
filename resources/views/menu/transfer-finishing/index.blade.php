@extends('layout.main')

@section('css_custom')
    <style>
        .datagrid-body td[field="transfer"],
        .datagrid-body td[field="balance"] {
            text-align: right !important;
            font-weight: 500;
        }

        .action-btn {
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
            outline: none;
            background-clip: padding-box;
            cursor: pointer;
            font-family: inherit;
        }
        .action-btn:hover {
            background: #bae6fd;
            color: #0c4a6e;
            transform: scale(1.05);
        }
        .action-btn:focus-visible {
            outline: 2px solid #0369a1;
            outline-offset: 2px;
        }

        /* ============================================================
           TAMPILAN LEBIH MODERN & RINGKAS -- baris lebih tinggi (utk cell
           multi-baris seperti PO No+Line, Order Information), header
           lebih tegas, hover halus.
           ============================================================ */
        #dgOrder .datagrid-header .datagrid-cell {
            font-weight: 600;
            color: #374151;
            font-size: 12px;
        }
        #dgOrder .datagrid-body .datagrid-cell {
            font-size: 13px;
            padding-top: 8px;
            padding-bottom: 8px;
        }
        #dgOrder .datagrid-row:hover td {
            background-color: #f8fafc !important;
        }
        .cell-stack {
            text-align: left;
            line-height: 1.35;
        }
        .cell-stack .cs-main {
            font-weight: 600;
            font-size: 13px;
            color: #0f172a;
        }
        .cell-stack .cs-sub {
            font-size: 11px;
            color: #64748b;
        }
        .cell-stack .cs-meta {
            font-size: 10.5px;
            color: #94a3b8;
        }
        .mif-badge {
            font-size: 9px;
            margin-left: 4px;
            vertical-align: 1px;
        }

        .combo-qty-item {
            cursor: pointer;
            text-decoration: underline dotted;
            text-decoration-color: #cbd5e1;
        }
        .combo-qty-item:hover {
            color: #0369a1;
            text-decoration-color: #0369a1;
        }
        .combo-sep {
            color: #cbd5e1;
            margin: 0 2px;
        }
        .cell-stack .combo-line {
            line-height: 1.5;
        }
    </style>
@endsection

@section('content')
    <div class="page-wrap">
        <x-table-default
            id="dgOrder"
            title="Daftar Data OP"
            search
            search-name="search"
            search-placeholder="Search..."
            buyer
            buyer-name="buyer"
            buyer-url="{{ route('api.buyer-list') }}"
            buyer-value-field="buyer"
            buyer-text-field="buyer_name"
            buyer-mode="local"
            year
            year-name="year"
            exfactory
            exfactory-name="ex_factory"
            sort-dropdown
            sort-asc-label="Awal Ex-Factory"
            sort-desc-label="Akhir Ex-Factory"
        >
            <table id="dgOrder" style="width:100%;height:600px"
                url="{{ route('tf_finishing.list') }}" method="get" pagination="true" pageSize="50"
                pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true" checkOnSelect="true"
                selectOnCheck="true" fitColumns="false" border="false">
                <thead>
                    <tr>
                        <th field="action" width="55" formatter="formatAction" align="center">Aksi</th>
                        <th field="OP" width="230" formatter="formatOrderInfo">Order Information</th>
                        <th field="POno" width="230" formatter="formatPOno">PO No</th>
                        <th field="GAC" width="100" align="center" formatter="formatExFactory">Ex-Factory</th>
                        <th field="transfer" width="200" align="right">R+Q</th>
                        <th field="transfer_finishing" width="200" align="right">Transfer To<br>Finishing</th>
                        <th field="balance" width="200" align="right" formatter="formatBalance">Balance</th>
                        <th field=" " width="10" align="right"> </th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>

    @include('menu.transfer-finishing.modal-material-list')
@endsection

@section('js_custom')
    <script>
    // ============================================================
    // PO/OP/mif dari modal yang SEDANG terbuka -- disimpan di variabel
    // global supaya bisa dipakai lagi saat mau pindah ke Input Transfer
    // (buat sessionStorage) tanpa perlu baca ulang dari row datagrid.
    // ============================================================
    let currentModalPo       = null;
    let currentModalOp       = null;
    let currentModalMif      = null;
    let currentModalMaterial = null;
    let currentModalSecsz    = null;

    const isSuperUser = @json(session('guserpk') == 34);

    // ============================================================
    // KOLOM AKSI — cuma 1 tombol mata (buka modal detail color/size).
    // ============================================================
    function formatAction(value, row, index) {
        const poArg = JSON.stringify(row.POno ?? '');

        return `
            <a href="javascript:void(0)"
                onclick='openDetailModal(event, ${poArg}, ${JSON.stringify(row.OP)}, ${row.mif})'
                class="action-btn"
                title="Lihat Detail Color & Size">
                <i class="fas fa-eye"></i>
            </a>
        `;
    }

    function formatActionModal(value, row, index) {
        return `
            <button type="button" class="action-btn" title="Input Transfer"
                onclick="openTransfer(event, ${row.popk}, ${row.mif})">
                <i class="fas fa-edit"></i>
            </button>
        `;
    }

    function formatExFactory(value) {
        if (value === null || value === undefined || value === '') return '';
    
        // Buang bagian jam kalau ada (spasi ATAU 'T' ala ISO), lalu split '-'.
        const datePart = String(value).split(' ')[0].split('T')[0];
        const parts = datePart.split('-');
        if (parts.length !== 3) return '';
    
        const year     = parseInt(parts[0], 10);
        const monthIdx  = parseInt(parts[1], 10) - 1;
        const dayNum    = parseInt(parts[2], 10);
    
        // cek Number.isFinite() (menolak NaN) DAN rentang wajar --
        // SEBELUMNYA cuma cek "< 0 || > 11" yang bobol kalau nilainya NaN
        // (NaN < 0 dan NaN > 11 keduanya bernilai false di JS).
        if (
            !Number.isFinite(year) || year <= 0 ||
            !Number.isFinite(monthIdx) || monthIdx < 0 || monthIdx > 11 ||
            !Number.isFinite(dayNum) || dayNum <= 0 || dayNum > 31
        ) {
            return '';
        }
    
        const bulanSingkat = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return `${dayNum} ${bulanSingkat[monthIdx]} ${year}`;
    }

    // ============================================================
    // BARU: PO No + Line digabung 1 cell (Line jadi sub-text kecil).
    // ============================================================
    function formatPOno(value, row) {
        return `
            <div class="cell-stack">
                <div class="cs-main">${value ?? '-'}</div>
                <div class="cs-sub">${row.customer ?? '-'}</div>
            </div>
        `;
    }

    // ============================================================
    // BARU: Order Information -- OP, Buyer, Season, Qty digabung 1 cell.
    // ============================================================
    function formatOrderInfo(value, row) {
        const mifBadge = isSuperUser
            ? `<span class="badge bg-secondary-subtle text-secondary-emphasis mif-badge">mif ${row.mif}</span>`
            : '';
    
        const imgUrl = row.order_image || '';
        const localNoImg = "{{ asset('public/css/images/no-img.png') }}";
    
        const imgHtml = imgUrl
            ? `<img src="${imgUrl}" width="60" height="60" style="object-fit:cover;border-radius:6px;flex-shrink:0;"
                onerror="this.onerror=null;this.src='${localNoImg}';">`
            : `<img src="${localNoImg}" width="60" height="60" style="object-fit:cover;border-radius:6px;flex-shrink:0;">`;
    
        return `
            <div class="d-flex align-items-start gap-2">
                ${imgHtml}
                <div class="cell-stack">
                    <div class="cs-main">${row.OP ?? '-'}${mifBadge}</div>
                    <div class="cs-sub">${row.buyer ?? '-'} &middot; ${row.season ?? '-'}</div>
                    <div class="cs-sub">${row.style ?? '-'}</div>
                    <div class="cs-meta">Qty: <strong style="color:#334155;">${Number(row.qty || 0).toLocaleString()}</strong></div>
                </div>
            </div>
        `;
    }

    // ============================================================
    // SIMPAN / AMBIL STATE SEBELUM PINDAH KE INPUT TRANSFER
    // Menyimpan: filter index (search/buyer/year/page) + PO/OP/mif
    // modal yang sedang terbuka -- supaya saat Back, index balik ke
    // filter yang sama DAN modal yang sama otomatis terbuka lagi.
    // ============================================================
    function saveTransferNavState() {
        let pager      = $('#dgOrder').datagrid('getPager');
        let pageNumber = pager.pagination('options').pageNumber;
        let state = {
            search: $('#dgOrder_filterbar [data-dg-filter="search"]').val(),
            buyer:  $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('getValue'),
            year:   $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('getValue'),
            exFactory: $('#filterExFactory').val(),
            page:   pageNumber,
            modalPo:  currentModalPo,
            modalOp:  currentModalOp,
            modalMif: currentModalMif,
            modalMaterial: currentModalMaterial, // BARU
            modalSecsz:    currentModalSecsz     // BARU
        };
        sessionStorage.setItem('tfFinishingListState', JSON.stringify(state));
    }

    function getSavedTransferNavState() {
        let raw = sessionStorage.getItem('tfFinishingListState');
        if (!raw) return null;

        sessionStorage.removeItem('tfFinishingListState');

        try {
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    }

    // ============================================================
    // MODAL: Rincian PO — tabelnya EasyUI datagrid (#dgDetailModal).
    // ============================================================
    function openDetailModal(e, po, op, mif, material, secsz) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        currentModalPo       = po ?? '';
        currentModalOp       = op;
        currentModalMif      = mif;
        currentModalMaterial = material || null;
        currentModalSecsz    = secsz || null;

        const poLabel = (po === null || po === undefined || po === '') ? '' : po;
        $('#detailModalPO').text(poLabel);
        $('#detailModalOP').text(op);
        $('#detailModalBuyer').text('');

        const modalEl = document.getElementById('detailModal');
        new bootstrap.Modal(modalEl).show();

        $(modalEl).one('shown.bs.modal', function () {
            $('#dgDetailModal').datagrid('resize');
        });

        if (!$('#dgDetailModal').data('datagrid')) {
            $('#dgDetailModal').datagrid({
                method: 'get', 
                rownumbers: false,
                singleSelect: true,
                fitColumns: false,
                border: false,
                loadMsg: 'Memuat data...',
                onLoadSuccess: onDetailModalLoad
            });
        }

        $('#dgDetailModal').datagrid('options').url = "{{ route('tf_finishing.detail-by-po-op') }}";

        $('#dgDetailModal').datagrid('load', {
            po: po ?? '',
            op: op,
            mif: mif,
            material: material || '',
            secsz: secsz || ''
        });
    }

    function onDetailModalLoad(data) {
        const rows = (data && data.rows) || [];
        $('#detailModalBuyer').text(rows.length ? (rows[0].buyer ?? '-') : '-');
        $('#detailModalDesc').text(rows.length ? (rows[0].silhouette ?? '-') : '-');
    }

    function formatDashModal(value) {
        return (value === null || value === undefined || value === '') ? '-' : value;
    }

    function formatBalanceModal(value) {
        value = Number(value);

        if (value > 0) {
            return `<span style="color:#16a34a;font-weight:600;">${value.toLocaleString()}</span>`;
        }
        if (value < 0) {
            return `<span style="color:#dc2626;font-weight:600;">${value.toLocaleString()}</span>`;
        }
        return `<span style="font-weight:600;">0</span>`;
    }

    // Pindah ke halaman Input Transfer -- simpan state SEBELUM navigasi,
    // supaya begitu user tekan Back, index bisa restore filter + modal.
    function openTransfer(e, popk, mif) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        saveTransferNavState();

        let url = "{{ url('/tf-finishing/input') }}/" + popk + "?mif=" + mif;
        window.location.href = url;
    }

    // Restore dari bfcache (kasus browser tertentu yang cache halaman
    // penuh saat Back, bukan reload dari server).
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        if (window.EasyuiDG) {
            window.EasyuiDG.reload('dgOrder');
        }
        const modalEl = document.getElementById('detailModal');
        if (modalEl && modalEl.classList.contains('show')) {
            $('#dgDetailModal').datagrid('load', {
                po: currentModalPo ?? '',
                op: currentModalOp,
                mif: currentModalMif,
                material: currentModalMaterial || '',
                secsz: currentModalSecsz || ''
            });
        }
    });

    // ============================================================
    // INIT — restore filter + modal setelah Back dari Input Transfer.
    // ============================================================
    let restoredDgOrderPage = null;
    
    document.getElementById('detailModal').addEventListener('hidden.bs.modal', function () {
        if (!window.EasyuiDG) return;
    
        if (restoredDgOrderPage) {
            window.EasyuiDG.reload('dgOrder', restoredDgOrderPage);
            restoredDgOrderPage = null;
        } else {
            window.EasyuiDG.reload('dgOrder');
        }
    });

    $(function () {
        let saved = getSavedTransferNavState();
        if (saved) {
            $('#dgOrder_filterbar [data-dg-filter="search"]').val(saved.search || '');
            $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('setValue', saved.buyer || '');
            $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('setValue', saved.year ?? new Date().getFullYear());
            if (saved.exFactory) {
                $('#filterExFactory').val(saved.exFactory).trigger('change');
            }
            restoredDgOrderPage = saved.page || 1;
            if (saved.modalOp) {
                openDetailModal(null, saved.modalPo, saved.modalOp, saved.modalMif, saved.modalMaterial, saved.modalSecsz);
            }
        }
    });

    // ============================================================
    // FORMATTER KOLOM BALANCE — datagrid utama
    // ============================================================
    function formatBalance(value, row) {
        value = Number(value);

        if (value > 0) {
            return `<span style="color:#16a34a;font-weight:600;">${value.toLocaleString()}</span>`;
        }
        if (value < 0) {
            return `<span style="color:#dc2626;font-weight:600;">${value.toLocaleString()}</span>`;
        }
        return `<span style="font-weight:600;">0</span>`;
    }

    function formatNoModal(value, row, index) {
        return index + 1;
    }
</script>
@endsection