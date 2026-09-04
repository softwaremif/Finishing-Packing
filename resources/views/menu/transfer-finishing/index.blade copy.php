@extends('layout.main')

@section('css_custom')
    <style>
        .datagrid-body td[field="qty"],
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
        >
            <table id="dgOrder" class="easyui-datagrid" style="width:100%;height:600px"
                url="{{ route('tf_finishing.list') }}" method="get" pagination="true" pageSize="50"
                pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true" checkOnSelect="true"
                selectOnCheck="true" fitColumns="false" border="false">
                <thead frozen="true">
                    <tr>
                        <th field="action" width="60" formatter="formatAction" align="center" rowspan="2">Aksi</th>
                        <th field="POno" width="150" rowspan="2">PO No</th>
                        <th field="OP" width="150" rowspan="2" formatter="formatPOno">OP</th>
                        <th field="linenm" width="80" rowspan="2">Line</th>
                    </tr>
                    <tr></tr>
                </thead>
                <thead>
                    <tr>
                        <th field="customer" width="150" rowspan="2">Place</th>
                        <th field="season" width="150" rowspan="2">Season</th>
                        <th field="buyer" width="150" rowspan="2">Buyer</th>
                        <th field="style" width="150" rowspan="2">Style</th>
                        <th colspan="4">Pcs</th>
                    </tr>
                    <tr>
                        <th field="qty" width="120" align="right">Qty</th>
                        <th field="transfer" width="120" align="right">R+Q</th>
                        <th field="transfer_finishing" width="120" align="right">Transfer To <br> Finishing</th>
                        <th field="balance" width="120" align="right" formatter="formatBalance">Balance</th>
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
    let currentModalPo  = null;
    let currentModalOp  = null;
    let currentModalMif = null;

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

    function formatFinishingModal(value) {
        return Number(value || 0).toLocaleString();
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
            page:   pageNumber,
            modalPo:  currentModalPo,
            modalOp:  currentModalOp,
            modalMif: currentModalMif
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
    function openDetailModal(e, po, op, mif) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        currentModalPo  = po ?? '';
        currentModalOp  = op;
        currentModalMif = mif;

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
            $('#dgDetailModal').datagrid();
        }

        $('#dgDetailModal').datagrid('load', { po: po ?? '', op: op, mif: mif });
    }

    function onDetailModalLoad(data) {
        const rows = (data && data.rows) || [];
        $('#detailModalBuyer').text(rows.length ? (rows[0].buyer ?? '-') : '-');
    }

    // Tutup modal (klik X, klik luar, atau Esc) -> reload tabel index,
    // supaya qty/transfer/balance ikut update kalau baru saja input.
    document.getElementById('detailModal').addEventListener('hidden.bs.modal', function () {
        if (window.EasyuiDG) {
            window.EasyuiDG.reload('dgOrder');
        }
    });

    // ============================================================
    // FORMATTER KHUSUS DATAGRID DI DALAM MODAL (#dgDetailModal)
    // ============================================================
    function formatActionModal(value, row, index) {
        return `
            <button type="button" class="action-btn" title="Input Transfer"
                onclick="openTransfer(event, ${row.popk}, ${row.mif})">
                <i class="fas fa-edit"></i>
            </button>
        `;
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
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
    
        if (window.EasyuiDG) {
            window.EasyuiDG.reload('dgOrder');
        }
    
        // FIX UTAMA: modal masih "shown" (belum pernah di-hide sebelum
        // navigasi ke Input Transfer) -- reload datanya juga.
        const modalEl = document.getElementById('detailModal');
        if (modalEl && modalEl.classList.contains('show')) {
            $('#dgDetailModal').datagrid('load', {
                po: currentModalPo ?? '',
                op: currentModalOp,
                mif: currentModalMif
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
            // Reload SEKALIGUS pindah ke halaman yang tadi disimpan --
            // hanya berlaku SATU KALI (langsung dinolkan) supaya penutupan
            // modal berikutnya kembali ke reload biasa (di halaman yg sama).
            window.EasyuiDG.reload('dgOrder', restoredDgOrderPage);
            restoredDgOrderPage = null;
        } else {
            window.EasyuiDG.reload('dgOrder');
        }
    });

    // ============================================================
    // INIT — restore filter + buka modal, TANPA reload manual duluan.
    // ============================================================
    $(function () {
        let saved = getSavedTransferNavState();

        if (saved) {
            $('#dgOrder_filterbar [data-dg-filter="search"]').val(saved.search || '');
            $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('setValue', saved.buyer || '');
            $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('setValue', saved.year ?? new Date().getFullYear());

            // Simpan nomor halaman untuk dipakai NANTI (saat modal ditutup),
            // BUKAN reload dgOrder sekarang.
            restoredDgOrderPage = saved.page || 1;

            // Modal dibuka LANGSUNG (tidak pakai setTimeout/delay lagi) --
            // tidak ada lagi reload dgOrder yang mendahuluinya secara visual.
            if (saved.modalOp) {
                openDetailModal(null, saved.modalPo, saved.modalOp, saved.modalMif);
            }
        }
    });

    // ============================================================
    // FORMATTER KOLOM OP (badge mif untuk super user) — datagrid utama
    // ============================================================
    const isSuperUser = @json(session('guserpk') == 34);

    function formatPOno(value, row) {
        if (!isSuperUser) {
            return value ?? '';
        }

        return `
            ${value ?? ''}
            <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:10px; margin-left:4px;">
                mif ${row.mif}
            </span>
        `;
    }

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