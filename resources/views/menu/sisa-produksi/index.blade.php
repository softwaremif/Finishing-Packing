@extends('layout.main')

@php
    $cfg = array_merge([
        'title'            => 'Daftar Sisa Produksi (Digrade)',
        'showExportExcel'  => false,
        'exportExcelRoute' => null,
        'pdfUrlBase'       => url('/sisa-produksi'),
        'routes'           => [],
    ], $pageConfig ?? []);
@endphp

@section('css_custom')
    <style>
        .datagrid-body td[field="transfer"],
        .datagrid-body td[field="balance"],
        .datagrid-body td[field="qty"],
        .datagrid-body td[field="loading"],
        .datagrid-body td[field="rq"],
        .datagrid-body td[field="keluar"] {
            text-align: right !important;
            font-weight: 500;
        }
        .action-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 30px; height: 30px; border-radius: 8px;
            background: #e0f2fe; color: #0369a1; font-size: 13px;
            transition: 0.2s; text-decoration: none; border: none; outline: none;
            background-clip: padding-box; cursor: pointer; font-family: inherit;
        }
        .action-btn:hover { background: #bae6fd; color: #0c4a6e; transform: scale(1.05); }
        .action-btn-pdf { background: #fef2f2; }
        #dgSisaProduksi .datagrid-header .datagrid-cell { font-weight: 600; color: #374151; font-size: 12px; }
        #dgSisaProduksi .datagrid-body .datagrid-cell { font-size: 13px; padding-top: 8px; padding-bottom: 8px; }
        #dgSisaProduksi .datagrid-row:hover td { background-color: #f8fafc !important; }
        .cell-stack { text-align: left; line-height: 1.35; }
        .cell-stack .cs-main { font-weight: 600; font-size: 13px; color: #0f172a; }
        .cell-stack .cs-sub { font-size: 11px; color: #64748b; }
        .cell-stack .cs-meta { font-size: 10.5px; color: #94a3b8; }
        .mif-badge { font-size: 9px; margin-left: 4px; vertical-align: 1px; }
    </style>
@endsection

@section('content')
    <div class="page-wrap">
        <x-table-default id="dgSisaProduksi" title="{{ $cfg['title'] ?? 'Daftar Sisa Produksi (Digrade)' }}"
            search search-name="search" search-placeholder="Search..."
            buyer buyer-name="buyer" buyer-url="{{ route('api.buyer-list') }}"
            buyer-value-field="buyer" buyer-text-field="buyer_name" buyer-mode="local"
            year year-name="year">

            @if ($cfg['showExportExcel'] ?? false)
                <x-slot name="filters">
                    <a href="javascript:void(0)" id="btnExportExcel"
                        class="btn btn-outline-success btn-sm d-inline-flex align-items-center px-2.5"
                        style="border-radius:6px;">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </a>
                </x-slot>
            @endif

            <table id="dgSisaProduksi" class="easyui-datagrid" style="width:100%;height:600px"
                url="{{ $cfg['routes']['list'] }}" method="get" pagination="true" pageSize="50"
                pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true" fitColumns="false" border="false">
                <thead>
                    <tr>
                        <th field="action" width="80" formatter="formatAction" align="center">Aksi</th>
                        <th field="OP" width="230" formatter="formatOrderInfo">Order Information</th>
                        <th field="POno" width="150" formatter="formatPOno">PO No</th>
                        <th field="qty" width="90" align="right" formatter="formatNumber">Order Qty</th>
                        <th field="loading" width="90" align="right" formatter="formatNumber">Loading</th>
                        <th field="rq" width="90" align="right" formatter="formatNumber">R+Q</th>
                        <th field="transfer" width="110" align="right" formatter="formatNumber">Sisa Digrade</th>
                        <th field="diterima" width="100" align="right" formatter="formatNumber">Diterima</th>
                        <th field="keluar" width="110" align="right" formatter="formatNumber">Sudah Keluar</th>
                        <th field="balance" width="150" align="right" formatter="formatBalance">Sisa di Gudang</th>
                        <th field=" " width="10"> </th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>
    @include('menu.sisa-produksi.sisa-produksi-detail-modal')
@endsection

@section('js_custom')
<script>
    window.pageCfg = @json($cfg);
    const R = window.pageCfg.routes;
    const localNoImg = "{{ asset('public/css/images/no-img.png') }}";

    function formatNumber(value) { return Number(value || 0).toLocaleString('id-ID'); }

    function formatBalance(value) {
        value = Number(value);
        if (value > 0) return `<span style="color:#dc2626;font-weight:700;">${value.toLocaleString()}</span>`;
        return `<span style="color:#94a3b8;font-weight:600;">0</span>`;
    }

    function formatPOno(value, row) {
        return `<div class="cell-stack"><div class="cs-main">${value ?? '-'}</div><div class="cs-sub">${row.customer ?? '-'}</div></div>`;
    }

    function formatOrderInfo(value, row) {
        const mifBadge = `<span class="badge bg-secondary-subtle text-secondary-emphasis mif-badge">mif ${row.mif}</span>`;
        return `
            <div class="cell-stack">
                <div class="cs-main">${row.OP ?? '-'}${mifBadge}</div>
                <div class="cs-sub">${row.buyer ?? '-'} &middot; ${row.season ?? '-'}</div>
                <div class="cs-sub">${row.style ?? '-'}</div>
            </div>
        `;
    }

    function formatAction(value, row) {
        return `
            <div class="d-flex justify-content-center gap-1">
                <a href="javascript:void(0)" class="action-btn" title="Lihat Detail"
                    onclick='openDetailModal(event, ${JSON.stringify(row.POno ?? '')}, ${JSON.stringify(row.OP)})'>
                    <i class="fas fa-eye"></i>
                </a>
                <a href="${window.pageCfg.pdfUrlBase}/${row.popk}/pdf" target="_blank" class="action-btn action-btn-pdf" title="Cetak PDF">
                    <i class="fas fa-file-pdf" style="color:#dc2626;"></i>
                </a>
            </div>
        `;
    }

    // ============================================================
    // MODAL DETAIL
    // ============================================================
    let currentModalPo = null, currentModalOp = null;

    function openDetailModal(e, po, op) {
        if (e) { e.preventDefault(); e.stopPropagation(); }
        currentModalPo = po ?? ''; currentModalOp = op;

        $('#detailModalPO').text(po || '-');
        $('#detailModalOP').text(op);

        const modalEl = document.getElementById('detailModal');
        new bootstrap.Modal(modalEl).show();
        $(modalEl).one('shown.bs.modal', function () { $('#dgDetailModal').datagrid('resize'); });

        if (!$('#dgDetailModal').data('datagrid')) {
            $('#dgDetailModal').datagrid({ url: R.detailByPoOp, method: 'get', rownumbers: false, singleSelect: true, fitColumns: false, border: false });
        }
        $('#dgDetailModal').datagrid('load', { po: po ?? '', op: op });
    }

    document.getElementById('detailModal').addEventListener('hidden.bs.modal', function () {
        if (window.EasyuiDG) window.EasyuiDG.reload('dgSisaProduksi');
    });

    function formatBalanceModal(value) {
        value = Number(value);
        if (value > 0) return `<span style="color:#dc2626;font-weight:700;">${value.toLocaleString()}</span>`;
        return `<span style="color:#94a3b8;font-weight:600;">0</span>`;
    }
    function formatNoModal(value, row, index) { return index + 1; }
    function formatActionModal(value, row) {
        return `<a href="javascript:void(0)" class="action-btn" title="Input" onclick="openSisaInput(event, ${row.popk}, ${row.mif})"><i class="fas fa-edit"></i></a>`;
    }
    function openSisaInput(e, popk, mif) {
        if (e) { e.preventDefault(); e.stopPropagation(); }
        window.location.href = R.inputUrlBase + "/" + popk + "?mif=" + mif;
    }

    @if ($cfg['showExportExcel'] ?? false)
    document.getElementById('btnExportExcel')?.addEventListener('click', function () {
        const params = new URLSearchParams({
            search: $('#dgSisaProduksi_filterbar [data-dg-filter="search"]').val() || '',
            buyer: $('#dgSisaProduksi_filterbar [data-dg-filter="buyer"]').combobox('getValue') || '',
            year: $('#dgSisaProduksi_filterbar [data-dg-filter="year"]').combobox('getValue') || '',
        });
        window.open("{{ $cfg['exportExcelRoute'] ?? '' }}?" + params.toString(), '_blank');
    });
    @endif
</script>
@endsection