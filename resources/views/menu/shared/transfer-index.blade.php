{{-- menu/shared/transfer-index.blade.php --}}
{{-- Dipakai oleh KEDUA halaman: Transfer/Polibag DAN Stok Sisa (Grade).
     Kontroler masing-masing kirim $pageConfig untuk menentukan route,
     judul, dan kolom tambahan mana yang tampil. --}}
@extends('layout.main')

@php
    $cfg = array_merge([
        'title'               => 'Daftar Data OP',
        'polibagColumnLabel'  => 'Polibag',
        'finishingField'      => 'transfer_finishing',
        'showQtyColumn'       => false,
        'showPackingColumn'   => false,
        'showKeluarColumn'    => false,
        'showMifBadgeAlways'  => false, // BARU -- Sisa Produksi: true (badge selalu tampil, bukan cuma super user)
        'showExportExcel'     => false, // BARU -- Sisa Produksi: true
        'exportExcelRoute'    => null,  // BARU
        'inputUrlBase'        => '/polibag/input',
        'routes' => [],
    ], $pageConfig ?? []);
@endphp

@section('css_custom')
    <style>
        .datagrid-body td[field="transfer"],
        .datagrid-body td[field="balance"],
        .datagrid-body td[field="qty"],
        .datagrid-body td[field="finishing"],
        .datagrid-body td[field="packing"],
        .datagrid-body td[field="keluar"] {
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
    </style>
@endsection

@section('content')
    <div class="page-wrap">
        <x-table-default id="dgOrder" title="{{ $cfg['title'] }}" search search-name="search"
            search-placeholder="Search..." buyer buyer-name="buyer" buyer-url="{{ route('api.buyer-list') }}"
            buyer-value-field="buyer" buyer-text-field="buyer_name" buyer-mode="local" year year-name="year"
            exfactory exfactory-name="ex_factory" sort-dropdown sort-asc-label="Awal Ex-Factory"
            sort-desc-label="Akhir Ex-Factory">

            @if ($cfg['showExportExcel'])
                <x-slot name="filters">
                    <a href="javascript:void(0)" id="btnExportExcel"
                        class="btn btn-outline-success btn-sm d-inline-flex align-items-center px-2.5"
                        style="border-radius:6px;">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </a>
                </x-slot>
            @endif

            <table id="dgOrder" class="easyui-datagrid" style="width:100%;height:600px"
                url="{{ $cfg['routes']['list'] }}" method="get" pagination="true" pageSize="50"
                pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true" fitColumns="false"
                border="false">
                <thead>
                    <tr>
                        <th field="action" width="55" formatter="formatAction" align="center">Aksi</th>
                        <th field="OP" width="230" formatter="formatOrderInfo">Order Information</th>
                        <th field="POno" width="230" formatter="formatPOno">PO No</th>
                        <th field="GAC" width="100" align="center" formatter="formatExFactory">Ex-Factory</th>
                        @if ($cfg['showQtyColumn'])
                            <th field="qty" width="90" align="right" formatter="formatNumber">Qty</th>
                        @endif
                        <th field="{{ $cfg['finishingField'] }}" width="150" align="right" formatter="formatNumber">Transfer To<br>Finishing</th>
                        <th field="transfer" width="150" align="right" formatter="formatNumber">{{ $cfg['polibagColumnLabel'] }}</th>
                        @if ($cfg['showPackingColumn'])
                            <th field="packing" width="120" align="right" formatter="formatNumber">Packing</th>
                        @endif
                        <th field="balance" width="150" align="right" formatter="formatBalance">Balance</th>
                        @if ($cfg['showKeluarColumn'])
                            <th field="keluar" width="120" align="right" formatter="formatNumber">Keluar</th>
                        @endif
                        <th field=" " width="10" align="right"> </th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>
    @include($cfg['routes']['modalView'])
@endsection

@section('js_custom')
    <script>
        window.pageCfg = @json($cfg);
        const R = window.pageCfg.routes;
        const isSuperUser = @json(session('guserpk') == 34);
        const localNoImg = "{{ asset('public/css/images/no-img.png') }}";

        function formatAction(value, row, index) {
            const poArg = JSON.stringify(row.POno ?? '');
            return `
                <a href="javascript:void(0)"
                    onclick='openDetailModal(event, ${poArg}, ${JSON.stringify(row.OP)}, ${row.mif})'
                    class="action-btn"
                    title="Lihat Detail">
                    <i class="fas fa-eye"></i>
                </a>
            `;
        }

        function formatExFactory(value) {
            if (value === null || value === undefined || value === '') return '';
            const datePart = String(value).split(' ')[0].split('T')[0];
            const parts = datePart.split('-');
            if (parts.length !== 3) return '';
            const year = parseInt(parts[0], 10);
            const monthIdx = parseInt(parts[1], 10) - 1;
            const dayNum = parseInt(parts[2], 10);
            if (!Number.isFinite(year) || year <= 0 || !Number.isFinite(monthIdx) || monthIdx < 0 || monthIdx > 11 ||
                !Number.isFinite(dayNum) || dayNum <= 0 || dayNum > 31) {
                return '';
            }
            const bulanSingkat = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            return `${dayNum} ${bulanSingkat[monthIdx]} ${year}`;
        }

        function formatPOno(value, row) {
            return `
                <div class="cell-stack">
                    <div class="cs-main">${value ?? '-'}</div>
                    <div class="cs-sub">${row.customer ?? '-'}</div>
                </div>
            `;
        }

        function formatOrderInfo(value, row) {
            const showMifBadge = isSuperUser || window.pageCfg.showMifBadgeAlways; // BARU
            const mifBadge = showMifBadge
                ? `<span class="badge bg-secondary-subtle text-secondary-emphasis mif-badge">mif ${row.mif}</span>`
                : '';
            const dupBadge = row.has_mif_duplicate // BARU -- tanda PONo+OP juga ada di mif lain
                ? `<span class="badge bg-warning-subtle text-warning-emphasis mif-badge" title="PONo+OP ini juga ada di mif lain">dup mif</span>`
                : '';
            const imgUrl = row.order_image || localNoImg;
            const imgHtml = `
                <img src="${imgUrl}" width="60" height="60"
                    style="object-fit:cover;border-radius:6px;flex-shrink:0;"
                    onerror="this.onerror=null;this.src='${localNoImg}';">
            `;
            return `
                <div class="d-flex align-items-start gap-2">
                    ${imgHtml}
                    <div class="cell-stack">
                        <div class="cs-main">${row.OP ?? '-'}${mifBadge}${dupBadge}</div>
                        <div class="cs-sub">${row.buyer ?? '-'} &middot; ${row.season ?? '-'}</div>
                        <div class="cs-sub">${row.style ?? '-'}</div>
                        <div class="cs-meta">Qty: <strong style="color:#334155;">${Number(row.qty || 0).toLocaleString()}</strong></div>
                    </div>
                </div>
            `;
        }

        function formatNumber(value) {
            return Number(value || 0).toLocaleString('id-ID');
        }

        function formatBalance(value) {
            value = Number(value);
            if (value > 0) return `<span style="color:#16a34a;font-weight:600;">${value.toLocaleString()}</span>`;
            if (value < 0) return `<span style="color:#dc2626;font-weight:600;">${value.toLocaleString()}</span>`;
            return `<span style="font-weight:600;">0</span>`;
        }

        // ============================================================
        // STATE (filter index + modal yang terbuka)
        // ============================================================
        let currentModalPo = null, currentModalOp = null, currentModalMif = null;

        function saveNavState() {
            let pager = $('#dgOrder').datagrid('getPager');
            let pageNumber = pager.pagination('options').pageNumber;
            let state = {
                search: $('#dgOrder_filterbar [data-dg-filter="search"]').val(),
                buyer: $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('getValue'),
                year: $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('getValue'),
                exFactory: $('#dgOrder_filterbar [data-dg-filter="ex_factory"]').combobox('getValue'),
                page: pageNumber,
                modalPo: currentModalPo,
                modalOp: currentModalOp,
                modalMif: currentModalMif,
            };
            sessionStorage.setItem(R.navStateKey, JSON.stringify(state));
        }

        function getSavedNavState() {
            let raw = sessionStorage.getItem(R.navStateKey);
            if (!raw) return null;
            sessionStorage.removeItem(R.navStateKey);
            try { return JSON.parse(raw); } catch (e) { return null; }
        }

        function openDetailModal(e, po, op, mif) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            currentModalPo = po ?? ''; currentModalOp = op; currentModalMif = mif;

            const poLabel = (po === null || po === undefined || po === '') ? '' : po;
            $('#detailModalPO').text(poLabel);
            $('#detailModalOP').text(op);
            $('#detailModalBuyer').text('');

            const modalEl = document.getElementById('detailModal');
            new bootstrap.Modal(modalEl).show();
            $(modalEl).one('shown.bs.modal', function () { $('#dgDetailModal').datagrid('resize'); });

            if (!$('#dgDetailModal').data('datagrid')) {
                $('#dgDetailModal').datagrid();
            }
            $('#dgDetailModal').datagrid('load', { po: po ?? '', op: op, mif: mif });
        }

        function onDetailModalLoad(data) {
            const rows = (data && data.rows) || [];
            $('#detailModalBuyer').text(rows.length ? (rows[0].buyer ?? '-') : '-');
            $('#detailModalDesc').text(rows.length ? (rows[0].silhouette ?? '-') : '-');
        }

        document.getElementById('detailModal').addEventListener('hidden.bs.modal', function () {
            if (window.EasyuiDG) window.EasyuiDG.reload('dgOrder');
        });

        function formatDashModal(value) {
            return (value === null || value === undefined || value === '') ? '-' : value;
        }
        function formatBalanceModal(value) {
            value = Number(value);
            if (value > 0) return `<span style="color:#16a34a;font-weight:600;">${value.toLocaleString()}</span>`;
            if (value < 0) return `<span style="color:#dc2626;font-weight:600;">${value.toLocaleString()}</span>`;
            return `<span style="font-weight:600;">0</span>`;
        }
        function formatNoModal(value, row, index) { return index + 1; }

        function formatActionModal(value, row, index) {
            if (!window.pageCfg.showPdfAction) {
                return `
                    <button type="button" class="action-btn" title="Input" onclick="openTransfer(event, ${row.popk}, ${row.mif})">
                        <i class="fas fa-edit"></i>
                    </button>
                `;
            }
        
            const pdfBtn = `
                <a href="${window.pageCfg.pdfUrlBase}/${row.popk}/pdf" target="_blank"
                    class="action-btn action-btn-pdf" title="Cetak PDF">
                    <img src="{{ asset('/public/css/images/pdf.jpg') }}" width="18" height="18">
                </a>
            `;
        
            // balance <= 0 -- stok sudah habis/tidak ada sisa, HANYA boleh cetak,
            // TIDAK BOLEH Input Transfer lagi.
            if (!(row.balance > 0)) {
                return pdfBtn;
            }
        
            const editBtn = `
                <a href="javascript:void(0)" onclick="openTransfer(event, ${row.popk}, ${row.mif})"
                    class="action-btn" title="Input Transfer">
                    <i class="fas fa-edit"></i>
                </a>
            `;
        
            return `<div class="d-flex justify-content-center gap-1">${editBtn}${pdfBtn}</div>`;
        }

        // SAMA URL untuk kedua mode -- halaman input SUDAH SATU, dibedakan
        // guserpk di dalamnya (lihat menu.transfer.input).
        function openTransfer(e, popk, mif) {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            saveNavState();
            let url = R.inputUrlBase + "/" + popk + "?mif=" + mif;
            window.location.href = url;
        }

        window.addEventListener('pageshow', function (event) {
            if (!event.persisted) return;
            if (window.EasyuiDG) window.EasyuiDG.reload('dgOrder');
            const modalEl = document.getElementById('detailModal');
            if (modalEl && modalEl.classList.contains('show')) {
                $('#dgDetailModal').datagrid('load', { po: currentModalPo ?? '', op: currentModalOp, mif: currentModalMif });
            }
        });

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
            let saved = getSavedNavState();
            if (saved) {
                $('#dgOrder_filterbar [data-dg-filter="search"]').val(saved.search || '');
                $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('setValue', saved.buyer || '');
                $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('setValue', saved.year ?? new Date().getFullYear());
                if (saved.exFactory) {
                    $('#dgOrder_filterbar [data-dg-filter="ex_factory"]').combobox('setValue', saved.exFactory);
                }
                restoredDgOrderPage = saved.page || 1;
                if (saved.modalOp) {
                    openDetailModal(null, saved.modalPo, saved.modalOp, saved.modalMif);
                }
            }
        });
    </script>
    @if ($cfg['showExportExcel'])
        <script>
        document.getElementById('btnExportExcel')?.addEventListener('click', function () {
            const params = new URLSearchParams({
                search: $('#dgOrder_filterbar [data-dg-filter="search"]').val() || '',
                buyer: $('#dgOrder_filterbar [data-dg-filter="buyer"]').combobox('getValue') || '',
                year: $('#dgOrder_filterbar [data-dg-filter="year"]').combobox('getValue') || '',
            });
            window.open("{{ $cfg['exportExcelRoute'] }}?" + params.toString(), '_blank');
        });
        </script>
    @endif
@endsection