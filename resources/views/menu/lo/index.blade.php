@extends('layout.main')

@section('css_custom')
    <style>
        .page-wrap {
            padding: 16px;
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
            transition: .2s;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }

        .action-btn:hover {
            background: #bae6fd;
            color: #0c4a6e;
            transform: scale(1.05);
            text-decoration: none;
        }

        .action-btn.action-btn-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .action-btn.action-btn-danger:hover {
            background: #fecaca;
            color: #7f1d1d;
        }

        a.action-btn.action-btn-danger {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        a.action-btn.action-btn-danger:hover {
            background: #fecaca;
            color: #7f1d1d;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
        }

        .status-pill.st-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-pill.st-progress {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-pill.st-done {
            background: #dcfce7;
            color: #166534;
        }

        .status-pill.st-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
@endsection

@section('content')
    <div class="page-wrap">
        {{-- FIX UTAMA: TIDAK pass prop 'title' lagi (biar tidak dobel
             render .order-title) -- judul + tombol sekarang jadi 1
             baris DI DALAM slot 'filters', dipaksa full-width lewat
             flex-basis:100% supaya filter di bawahnya otomatis pindah
             baris. --}}
        <x-table-default id="dgLo" :search="false" :buyer="false" :year="false" :exfactory="false" :sort="false">
            <x-slot name="filters">
                {{-- BARIS JUDUL + TOMBOL -- sejajar, rata kanan --}}
                <div class="d-flex justify-content-between align-items-center" style="flex:1 1 100%;">
                    <div class="order-title mb-0">Daftar LO - Kirim Sisa ke Gudang</div>
                    <button class="btn btn-dark btn-sm d-inline-flex align-items-center px-3 fw-semibold"
                        style="font-size:12.5px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                        onclick="openCreateLoModal()">
                        <i class="fas fa-plus me-1.5"></i> Buat LO Baru
                    </button>
                </div>

                {{-- CUSTOM FILTER 1: No LO --}}
                <div class="input-group" style="width:200px;">
                    <span class="input-group-text search">
                        <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18">
                    </span>
                    <input type="text" class="form-control search" data-dg-filter="lopk" data-dg-filter-type="search"
                        data-dg-chip-label="No LO" placeholder="Cari No LO...">
                </div>

                {{-- CUSTOM FILTER 2: Status Approve --}}
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
            </x-slot>

            <table id="dgLo" class="easyui-datagrid" style="width:100%;height:600px" url="{{ route('lo.list') }}"
                method="get" pagination="true" pageSize="50" pageList="[25,50,100]" rownumbers="false"
                singleSelect="true" fitColumns="false" border="false">
                <thead>
                    <tr>
                        <th field="action" width="90" align="center" formatter="formatLoAction">Aksi</th>
                        <th field="lopk" width="90" align="center" formatter="formatLopk">No LO</th>
                        <th field="lodate" width="150" align="center" formatter="formatLoDate">Tanggal Dibuat</th>
                        <th field="keterangan" width="260" formatter="formatDashLo">Keterangan</th>
                        <th field="jumlah_item" width="110" align="center">Jumlah Item</th>
                        <th field="status_label" width="220" align="center" formatter="formatStatusPill">Status Approval
                        </th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>

    @include('menu.lo.modal-create')
    @include('menu.lo.modal-detail')
    @include('menu.lo.modal-confirm')
@endsection

@section('js_custom')
    @include('menu.lo.js-index')
@endsection
