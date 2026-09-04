@extends('layout.main')

@section('css_custom')
    <style>
        .page-wrap { padding: 16px; }
        .action-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 30px; height: 30px; border-radius: 8px;
            background: #e0f2fe; color: #0369a1; font-size: 13px;
            transition: .2s; border: none; cursor: pointer; text-decoration: none;
        }
        .action-btn:hover { background: #bae6fd; color: #0c4a6e; transform: scale(1.05); text-decoration: none; }
        a.action-btn.action-btn-danger { background: #fee2e2; color: #b91c1c; }
        a.action-btn.action-btn-danger:hover { background: #fecaca; color: #7f1d1d; }
        .status-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px; }
        .status-pill.st-pending  { background: #fef3c7; color: #92400e; }
        .status-pill.st-progress { background: #dbeafe; color: #1e40af; }
        .status-pill.st-done     { background: #dcfce7; color: #166534; }
        .status-pill.st-rejected { background: #fee2e2; color: #991b1b; }

        /* Reuse gaya kartu item -- SAMA seperti modul LO */
        .lo-po-group { margin-bottom: 18px; }
        .lo-po-group-title { font-size: 12.5px; font-weight: 700; color: #374151; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .lo-po-group-title .buyer-tag { font-size: 11px; color: #94a3b8; font-weight: 500; }
        .lo-item-wrap { border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 6px; overflow: hidden; background: #fff; }
        .lo-item-card { display: flex; align-items: center; gap: 10px; padding: 8px 10px; }
        .lo-item-grade-badge { width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; color: #fff; }
        .lo-item-grade-badge.grade-a { background: #16a34a; }
        .lo-item-grade-badge.grade-b { background: #2563eb; }
        .lo-item-grade-badge.grade-c { background: #d97706; }
        .lo-item-info { flex: 1 1 auto; min-width: 0; }
        .lo-item-info .li-main { font-size:12.5px; font-weight:600; color:#0f172a; }
        .lo-item-info .li-sub { font-size:11px; color:#64748b; }
        .lo-item-expand-btn { width: 26px; height: 26px; border-radius: 6px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #94a3b8; background: #f8fafc; cursor: pointer; }
        .lo-item-expand-btn:hover { background: #e2e8f0; color: #1e293b; }
        .lo-item-expand-btn.is-open { background: #1e293b; color: #fff; }
        .lo-item-size-detail { display: none; padding: 10px 12px 12px 46px; background: #fafbfc; border-top: 1px dashed #e5e7eb; }
        .lo-item-size-detail.is-open { display: block; }

        /* Chip size KHUSUS keluar -- ada input qty (bukan chip statis). */
        .out-size-row { display: flex; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px dashed #eef1f5; }
        .out-size-row:last-child { border-bottom: none; }
        .out-size-label { font-size: 12px; font-weight: 700; color: #374151; width: 40px; flex-shrink: 0; }
        .out-size-remaining { font-size: 11px; color: #94a3b8; flex: 1; }
        .out-size-qty-input { width: 70px; font-size: 12px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; }
        .out-size-add-btn { width: 26px; height: 26px; border-radius: 6px; background: #1e293b; color: #fff; border: none; flex-shrink: 0; cursor: pointer; }

        .out-cart-row { display: flex; align-items: flex-start; justify-content: space-between; font-size: 12px; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; }
        .out-cart-row .lc-remove { color: #dc2626; cursor: pointer; flex-shrink: 0; margin-left: 8px; }
    </style>
    
@endsection

@section('content')
    <div class="page-wrap">
        <x-table-default id="dgOutsisa" :search="false" :buyer="false" :year="false" :exfactory="false" :sort="false">
            <x-slot name="filters">
                <div class="d-flex justify-content-between align-items-center" style="flex:1 1 100%;">
                    <div class="order-title mb-0">Keluarkan Sisa dari Gudang</div>
                    <button class="btn btn-dark btn-sm d-inline-flex align-items-center px-3 fw-semibold"
                        style="font-size:12.5px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                        onclick="openCreateOutsisaModal()">
                        <i class="fas fa-plus me-1.5"></i> Add Barang Keluar
                    </button>
                </div>

                <div class="input-group" style="width:200px;">
                    <span class="input-group-text search">
                        <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18">
                    </span>
                    <input type="text" class="form-control search" data-dg-filter="outpk" data-dg-filter-type="search"
                        data-dg-chip-label="No Keluar" placeholder="Cari No Keluar...">
                </div>

                <input data-dg-filter="status" data-dg-filter-type="select" data-dg-chip-label="Status Approve"
                    data-dg-options='[
                        {"value":"","text":"Semua Status"},
                        {"value":"pending1","text":"Menunggu Approve Purchasing"},
                        {"value":"pending2","text":"Menunggu Approve HRD"},
                        {"value":"pending3","text":"Menunggu Approve HRD2"},
                        {"value":"approved","text":"Selesai (Approved)"},
                        {"value":"rejected","text":"Ditolak"}
                    ]'
                    data-dg-default="" style="width:230px">
            </x-slot>

            <table id="dgOutsisa" class="easyui-datagrid" style="width:100%;height:600px" url="{{ route('lo.keluargudang.list') }}"
                method="get" pagination="true" pageSize="50" pageList="[25,50,100]" rownumbers="false"
                singleSelect="true" fitColumns="false" border="false">
                <thead>
                    <tr>
                        <th field="action" width="150" align="center" formatter="formatOutAction">Aksi</th>
                        <th field="outpk" width="200" align="center" formatter="formatOutpk">No Keluar</th>
                        <th field="tglout" width="150" align="center" formatter="formatOutDate">Tanggal Keluar</th>
                        <th field="penerima" width="150" formatter="formatDashOut">Penerima</th>
                        <th field="keterangan" width="250" formatter="formatDashOut">Keterangan</th>
                        <th field="jumlah_item" width="100" align="center">Jumlah Item</th>
                        <th field="status_label" width="250" align="center" formatter="formatOutStatusPill">Status</th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>

    @include('menu.keluar-gudang.modal-create')
    @include('menu.keluar-gudang.modal-detail')
    @include('menu.lo.modal-confirm')
@endsection

@section('js_custom')
    @include('menu.keluar-gudang.js-index')
@endsection