@extends('layout.main')

@section('css_custom')
    <style>
        /* ===================== PAGE ===================== */
        .page-wrap {
            padding: 16px;
        }

        /* ===================== CARD ===================== */
        .card-section {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 14px;
        }

        .section-title {
            font-weight: 700;
            font-size: 13px;
            color: #359DD9;
            text-transform: uppercase;
            letter-spacing: .4px;
            border-bottom: 2px solid #e5f3fd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        /* ===================== INFO TABLE ===================== */
        .info-label {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .info-value {
            font-weight: 600;
            font-size: 13px;
        }

        .form-control-sm,
        .form-select-sm {
            font-size: 12px;
        }

        /* ===================== SIZE TABLE ===================== */
        .tbl-size th,
        .tbl-size td {
            text-align: center;
            font-size: 12px;
            padding: 4px 6px;
            white-space: nowrap;
        }

        .tbl-size .row-label {
            text-align: left;
            background: #f8f9fa;
            font-weight: 600;
        }

        .tbl-size thead th {
            background: #f0f7ff;
            font-weight: 700;
        }

        /* ===================== CTN SUMMARY ===================== */
        .ctn-summary td {
            text-align: center;
            font-size: 13px;
            padding: 7px 14px;
            font-weight: 700;
        }

        .bal-ok {
            background: #d1fae5;
            color: #065f46;
        }

        .bal-neg {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ===================== FORM INPUT CTN ===================== */
        .tbl-input th,
        .tbl-input td {
            font-size: 11px;
            padding: 3px 5px;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }

        .tbl-input thead th {
            background: #f0f7ff;
            font-weight: 700;
        }

        .tbl-input input[type=number],
        .tbl-input input[type=text] {
            width: 58px;
            font-size: 11px;
            padding: 2px 4px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .tbl-input input[type=checkbox] {
            cursor: pointer;
        }

        /* ===================== FILTER ROW ===================== */
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
        }

        .filter-row .input-group-text {
            font-size: 12px;
            padding: 3px 8px;
        }

        .filter-row .form-control,
        .filter-row .form-select {
            font-size: 12px;
            height: auto;
            padding: 3px 8px;
        }

        /* ===================== TOOLS GRID ===================== */
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        @media (max-width: 768px) {
            .tools-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .tool-card {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 12px;
        }

        .tool-card label {
            font-weight: 600;
            font-size: 12px;
            display: block;
            margin-bottom: 5px;
        }

        .tool-card .form-control,
        .tool-card .form-select {
            font-size: 12px;
            padding: 3px 6px;
            height: auto;
        }

        /* ===================== DETAIL TABLE ===================== */
        .easyui-empty-state {
            /* position: absolute; */
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 120px;
            background: rgba(255, 255, 255, 0.96);
            z-index: 2;
        }

        .tbl-detail th,
        .tbl-detail td {
            font-size: 11px;
            padding: 3px 5px;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }

        .tbl-detail thead th {
            background: #f0f7ff;
            font-weight: 700;
        }

        .bal-cell-neg {
            background: #fee2e2 !important;
            color: #991b1b;
            font-weight: 700;
        }

        .bal-cell-ok {
            background: #f3f4f6;
        }

        .btn-icon {
            padding: 2px 6px;
            font-size: 11px;
            border-radius: 4px;
            line-height: 1.4;
        }

        .overflow-x {
            overflow-x: auto;
        }

        .info-block-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 3px;
            font-weight: 600;
        }

        .info-block-value {
            font-size: 0.9rem;
            color: #0f172a;
            font-weight: 500;
            min-height: 24px;
        }

        .form-control-minimal {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 0.45rem 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .form-control-minimal:focus {
            border-color: #94a3b8;
            box-shadow: 0 0 0 2px rgba(148, 163, 184, 0.12);
        }

        .table-matrix th {
            font-size: 0.775rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            background-color: #f8fafc !important;
            border-bottom: 2px solid #e2e8f0 !important;
            padding: 10px 8px !important;
        }

        .table-matrix td {
            font-size: 0.875rem;
            padding: 10px 8px !important;
            color: #334155;
        }

        .table-matrix .row-label {
            font-weight: 600;
            color: #475569;
            text-align: left;
            padding-left: 16px !important;
            background-color: #f8fafc;
            min-width: 120px;
        }

        .table-matrix .col-total {
            font-weight: 600;
            background-color: #f8fafc;
            color: #0f172a;
        }

        /* Status Selisih Ringan (Mencegah Merah Terlalu Terang) */
        .badge-diff-negatif {
            color: #b91c1c;
            background-color: #fef2f2;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* Sticky Kolom Pertama Tabel jika ukuran/size terlalu panjang ke kanan */
        .table-sticky-first th:first-child,
        .table-sticky-first td:first-child {
            position: sticky;
            left: 0;
            background-color: #ffffff;
            z-index: 3;
            font-weight: 600;
            border-right: 2px solid #e2e8f0;
            box-shadow: 3px 0 5px rgba(0, 0, 0, 0.02);
        }

        .table-sticky-first th:first-child {
            background-color: #f8fafc !important;
        }

        .btn-custom-gray {
            background-color: rgba(100, 116, 139, 0.12);
            /* Abu-abu transparan lembut */
            color: #475569;
            /* Warna teks abu-abu gelap */
            border: 1px solid transparent;
            transition: all 0.2s ease-in-out;
        }

        .btn-custom-gray:hover,
        .btn-custom-gray:focus {
            background-color: #1e293b;
            /* Hitam arang / Dark charcoal solid */
            color: #ffffff;
            /* Teks berubah menjadi putih */
            border-color: #1e293b;
        }

        .datagrid-header .datagrid-cell-group {
            font-weight: bold !important;
            padding-top: 16px;
        }

        [class*="dgPacking_datagrid-cell-c1-qty"],
        .dgPacking_datagrid-cell-c1-_pa {
            padding: 0 !important;
        }

        /* =========================
                                               STICKY BAR
                                            ========================= */
        .sticky-order-bar {
            display: none;
            position: fixed;
            top: 58px;
            left: 0;
            right: 0;
            z-index: 1030;
            background: #359DD9;
            color: #fff;
        }

        .sticky-order-inner {
            height: 44px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 16px;
        }

        .sticky-action {
            cursor: pointer;
            margin-left: 10px;
            font-weight: 500;
            opacity: .9;
        }

        .sticky-action:hover {
            opacity: 1;
        }

        .transition-edit {
            transition: all 0.2s ease;
        }

        .transition-edit:hover {
            background-color: #f8fafc !important;
            color: #1e293b !important;
            border-color: #cbd5e1 !important;
            transform: translateY(-1px);
        }

        /* CSS STICKY ACTION COLUMN UNTUK BREAKDOWN SIZE */
        .table-breakdown {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-breakdown .sticky-col-start {
            position: sticky;
            left: 0;
            background-color: #ffffff;
            z-index: 2;
            border-right: 1px solid #e2e8f0;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.03);
        }

        .table-breakdown thead tr th.sticky-col-start {
            background-color: #f8fafc;
            z-index: 3;
        }

        .table-breakdown .sticky-col-end {
            position: sticky;
            right: 0;
            z-index: 2;
            border-start: 1px solid #e2e8f0;
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.03);
        }

        .table-breakdown thead tr th.sticky-col-end {
            background-color: #f8fafc;
            z-index: 3;
        }

        .bg-total-cell {
            background-color: #f8fafc !important;
        }

        .table-breakdown tbody tr:hover td.sticky-col-start {
            background-color: #f1f5f9 !important;
        }

        .table-breakdown tbody tr:hover td.bg-total-cell {
            background-color: #e2e8f0 !important;
        }

        .style-scrollbar::-webkit-scrollbar {
            height: 5px;
            width: 5px;
        }

        .style-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
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
        }

        .action-btn:hover {
            background: #bae6fd;
            color: #0c4a6e;
            transform: scale(1.05);
        }

        .datagrid-row-packing-shipped {
            background: #f8fafc !important;
            color: #94a3b8;
        }

        .datagrid-row-packing-shipped:hover {
            background: #f1f5f9 !important;
        }

        .datagrid-row-packing-shipped .datagrid-cell-check {
            pointer-events: none;
            opacity: 0.4;
        }

        .btn-back-custom {
            color: var(--text-muted);
            padding: 8px 14px;
            border-radius: 8px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }

        .btn-back-custom:hover {
            color: var(--text-main);
            background: #f1f5f9;
            border-color: #cbd5e1;
        }

        .btn-icon-custom:hover {
            background-color: #f8fafc !important;
            color: #1e293b !important;
            transform: translateX(-3px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        }
    </style>
@endsection


@section('content')
    <!-- STICKY -->
    <div id="stickTopBar" class="sticky-order-bar">
        <div class="sticky-order-inner">
            <div>
                <strong>
                    <span id="selectedCount">0</span>
                    item terpilih
                </strong>
            </div>
            <div>
                <span class="sticky-action d-none" id="btnBukaSegel" onclick="bulkBukaSegel()">
                    Buka Segel
                </span>
                <span class="sticky-action" id="btnBulkSegelCtn" onclick="bulkSegelCtn()">
                    Segel CTN
                </span>
                <span class="sticky-action" id="btnBulkActualCtn" onclick="bulkActualCtn()">
                    Input Actual
                </span>
                <span class="sticky-action" id="btnBulkDeleteActualCtn" onclick="bulkDeleteActualCtn()">
                    Delete Actual
                </span>
                <span class="sticky-action" id="btnBulkCopy" onclick="bulkCopy()">
                    Copy CTN
                </span>
                <span class="sticky-action" id="btnBulkDelete" onclick="bulkDelete()">
                    Delete CTN
                </span>
                <span class="sticky-action" onclick="closeMenu()">
                    Close
                </span>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4 px-4">
        <div
            class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-3 mb-4 gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:void(0)" onclick="goBack()"
                    class="btn btn-icon-custom d-inline-flex align-items-center justify-content-center shadow-sm border bg-white text-secondary rounded-circle"
                    style="width: 38px; height: 38px; transition: all 0.2s ease;" title="Kembali ke Daftar Data OP"> <i
                        class="fas fa-arrow-left"></i> </a>
                <div>
                    <h4 class="fw-bold text-dark mb-0" style="font-size: 1.15rem; letter-spacing: -0.3px;">Input Data Packing</h4>
                </div>
            </div>
        </div>

        {{-- ===================== HEADER INFO & FORM ===================== --}}
        <div class="card shadow-sm border-0 mb-3 rounded-3 bg-white" id="poDetailCard">
            <div class="card-header bg-white py-3 border-bottom-0 d-flex align-items-center justify-content-between">
                <div class="fw-bold text-dark d-flex align-items-center" style="font-size: 15px;">
                    <span class="rounded me-2"
                        style="width: 4px; height: 16px; display: inline-block; background: #475569;"></span>
                    Informasi Detail PO
                </div>

                {{-- TOMBOL EDIT --}}
                <button type="button"
                    class="btn btn-dark btn-sm d-inline-flex align-items-center px-2.5 py-1.5 fw-semibold"
                    style="font-size: 12px; border-radius: 6px; background-color: #1e293b; border-color: #1e293b;"
                    onclick="openEditPackingModal('{{ $popk }}')">
                    <i class="fas fa-edit me-2 small"></i> Edit Info PO
                </button>
            </div>

            <div class="card-body px-4 pb-4 pt-1">

                {{-- SEKSI 1: DATA MASTER (DENGAN IKON BADGE) --}}
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4 mb-4">
                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="bg-info-subtle text-info rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-layer-group fs-6" style="font-size: 13px;"></i>
                            </div>
                            <div>
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">OP</div>
                                <div class="fw-bold " style="font-size: 14px;" id="view_OP">{{ $dt2->OP ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0; background-color: #f1f5f9;">
                                <i class="fas fa-certificate text-dark fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">License PO Ref</div>
                                <div class="text-dark fw-semibold text-truncate" style="font-size: 14px;">
                                    {{ $dt2->poref ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="bg-danger-subtle text-danger rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-map-marker-alt fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">Place</div>
                                <div class="text-dark fw-semibold text-truncate" style="font-size: 14px;"
                                    title="{{ $dt2->customer ?? '-' }}">{{ $dt2->customer ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="bg-warning-subtle text-warning rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-calendar-alt fs-6"></i>
                            </div>
                            <div>
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">Season</div>
                                <div class="text-dark fw-semibold">{{ $dt2->season ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="bg-success-subtle text-success rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-user-tie fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">Buyer</div>
                                <div class="text-dark fw-semibold text-truncate" style="font-size: 14px;"
                                    title="{{ $dt2->buyer ?? '-' }}">{{ $dt2->buyer ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="bg-secondary-subtle text-secondary rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-tshirt fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">Style Code</div>
                                <div class="text-dark fw-semibold text-truncate" style="font-size: 14px;">
                                    {{ $dt2->style ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0; background-color: #f1f5f9;">
                                <i class="fas fa-palette text-dark fs-6"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">Color / Material</div>
                                <div class="text-dark fw-semibold text-truncate" style="font-size: 14px;">
                                    {{ $dt2->material ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0; background-color: #f1f5f9;">
                                <i class="fas fa-align-left text-dark fs-6"></i>
                            </div>
                            <div>
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">Description</div>
                                <div class="text-muted fw-normal"
                                    style="font-size: 13px; line-height: 1.4; word-break: break-word;">
                                    {{ $dt2->silhouette ?? '-' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PEMBATAS SEKSI YANG HALUS --}}
                <hr class="my-4" style="border-color: #f1f5f9; border-width: 2px;">

                {{-- SEKSI 2: DATA LOGISTIK & TRANSAKSI (DENGAN IKON BADGE) --}}
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="bg-primary-subtle text-primary rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-hashtag fs-6"></i>
                            </div>
                            <div>
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">PO Number</div>
                                <div class="fw-bold text-dark " style="font-size: 14px;" id="view_POno">{{ $dt2->POno ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="bg-secondary-subtle text-secondary rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="far fa-calendar-alt fs-6"></i>
                            </div>
                            <div>
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">Shipdate Plan</div>
                                <div class="fw-semibold text-secondary" style="font-size: 14px;" id="view_shipdate1">
                                    {{ $dt2->shipdate1 ? date('d M Y', strtotime($dt2->shipdate1)) : '-' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="bg-success-subtle text-success rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-calendar-check fs-6"></i>
                            </div>
                            <div>
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">Shipdate Actual</div>
                                <div class="fw-semibold" style="font-size: 14px;" id="view_shipdate2">
                                    {{ $dt2->shipdate2 ? date('d M Y', strtotime($dt2->shipdate2)) : '-' }}
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="bg-primary-subtle text-primary rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-id-card fs-6"></i>
                            </div>
                            <div>
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">SAP ID</div>
                                <div class="fw-bold text-dark " style="font-size: 14px;" id="view_sap1">{{ $dt2->sap1 ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="bg-primary-subtle text-primary rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-file-signature fs-6"></i>
                            </div>
                            <div>
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">SAP No</div>
                                <div class="fw-bold text-dark " style="font-size: 14px;" id="view_sap2">{{ $dt2->sap2 ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex align-items-start gap-2">
                            <div class="bg-info-subtle text-info rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0;">
                                <i class="fas fa-warehouse fs-6"></i>
                            </div>
                            <div>
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">Warehouse</div>
                                <div class="fw-semibold text-dark" style="font-size: 14px;" id="view_wh">{{ $dt2->wh ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6" style="grid-column: span 2;">
                        <div class="d-flex align-items-start gap-2">
                            <div class="rounded-2 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0; background-color: #f1f5f9;">
                                <i class="fas fa-comment-alt text-dark fs-6"></i>
                            </div>
                            <div>
                                <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                    style="letter-spacing: 0.5px; font-size: 10px;">Keterangan</div>
                                <div class="text-secondary" style="font-size: 13px; line-height: 1.4;" id="view_ket">
                                    {{ $dt2->ket ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div id="breakdownSummaryWrapper">
            @include('menu.packing.partials.breakdown_summary')
        </div>

        {{-- ===================== DETAIL PACK TABLE ===================== --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="rounded me-2"
                        style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
                    <strong class="text-dark"> Detail Packing / Carton </strong>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="px-3 py-2 border-bottom bg-white">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                        <!-- Filter -->
                        <div class="d-flex align-items-center gap-2 flex-wrap">

                            <div class="input-group" style="width:260px;">
                                <span class="input-group-text search">
                                    <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18"
                                        alt="Search">
                                </span>

                                <input type="text" class="form-control search" id="searchPacking"
                                    placeholder="Search Barcode / No CTN">
                            </div>

                            {{-- <select id="filterSize" class="form-select form-select-sm" style="width:170px;">
                                <option value="">Semua Size</option>
                                @foreach ($activeSizes as $i => $sz)
                                    <option value="{{ $i }}">
                                        {{ $sz }}
                                    </option>
                                @endforeach
                            </select> --}}
                            <input id="filterSize" style="width:170px;">

                        </div>

                        <!-- Button -->
                        <div class="d-flex align-items-center gap-2">

                            <button class="btn btn-dark btn-sm d-flex align-items-center fw-semibold"
                                style="font-size:12px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                                onclick="openUrutkanCtnModal()">
                                <i class="fas fa-sort-numeric-down me-1"></i>
                                Urutkan CTN
                            </button>

                            <button class="btn btn-dark btn-sm d-flex align-items-center fw-semibold"
                                style="font-size:12px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                                onclick="openPackingModal()">
                                <i class="fas fa-plus me-1"></i>
                                Add Packing
                            </button>

                        </div>

                    </div>
                </div>
                <table id="dgPacking" class="easyui-datagrid" style="width:100%;height:650px"
                    url="{{ route('packing.list.detail', $popk) }}" method="get" pagination="true" pageSize="50"
                    pageList="[25,50,100,200,500]" singleSelect="false" checkOnSelect="true" selectOnCheck="true"
                    fitColumns="false" rownumbers="false" border="false"
                    data-options="
                        onLoadSuccess:onLoadPacking,
                        onCheck:validatePackingCheck,
                        onUncheck:updateSelection,
                        onCheckAll:validatePackingCheckAll,
                        onUncheckAll:updateSelection,
                        rowStyler:rowStylerPacking
                    ">
                    <thead>
                        <tr>
                            <th field="action" width="50" formatter="formatAction" align="center" rowspan="2">
                                Aksi</th>
                            <th field="segel" width="80" align="center" formatter="formatSegel" rowspan="2">Status</th>
                            <th field="ck" checkbox="true" rowspan="2"></th>
                            {{-- <th field="no" width="50" align="center" formatter="formatNo" rowspan="2">No --}}
                            </th>
                            <th field="nobar" width="130" rowspan="2">No Barcode</th>
                            <th field="carton" width="100" rowspan="2">No CTN</th>
                            <th field="_pa" width="50" align="center" formatter="formatPA" rowspan="2">P/A
                            </th>
                            <th colspan="{{ count($activeSizes) }}" align="center">
                                Size
                                @if (!empty($dt2->secsz))
                                    <span class="text-muted fw-bold text-lowercase">({{ $dt2->secsz }})</span>
                                @endif
                            </th>
                            <th field="total" width="80" align="center" formatter="formatTotal" rowspan="2">
                                Total<br>(Pcs)</th>
                            <th field="balance" width="80" align="center" formatter="formatBalance" rowspan="2">
                                Balance<br>(Pcs)</th>
                            <th field="nw" width="70" rowspan="2">N.W</th>
                            <th field="gw" width="70" rowspan="2">G.W</th>
                            <th field="meas" width="100" rowspan="2">Meas CTN</th>
                            <th field="keterangan" width="200" rowspan="2">Keterangan</th>
                        </tr>
                        <tr>
                            @foreach ($activeSizes as $i => $sz)
                                <th field="qty{{ $i }}" align="center" width="70"
                                    formatter="formatSize{{ $i }}">
                                    {{ $sz }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </div>{{-- page-wrap --}}
    @include('menu.packing.modal-edit-info-packing')
    @include('menu.packing.modal-delete-ctn')
    @include('menu.packing.modal-copy-ctn')
    @include('menu.packing.modal-packing')
    @include('menu.packing.modal-actual-ctn')
    @include('menu.packing.modal-delete-actual-ctn')
    @include('menu.packing.modal-urutkan-ctn')
    @include('menu.packing.modal-segel-ctn')
    @include('menu.packing.modal-history-actual')
@endsection


@section('js_custom')
    {{-- JS Validasi Edit Input Packing Carton --}}
    <script>
        window.readyQty = @json($readyQty);
        window.transQty = @json($transQty);
        window.activeSizes = @json($activeSizes);

        document.addEventListener('DOMContentLoaded', function() {

            const orderQty = {};
            const readyQty = {};
            const transQty = {};

            reloadBreakdownSummary();

            @foreach ($activeSizes as $i => $sz)
                orderQty['{{ $sz }}'] = {{ $orderQty[$i] ?? 0 }};
                readyQty['{{ $sz }}'] = {{ $readyQty[$i] ?? 0 }};
                transQty['{{ $sz }}'] = {{ $transQty[$i] ?? 0 }};
            @endforeach

            // console.group('Packing Summary');

            // console.log('Order Qty');
            // console.table(orderQty);

            // console.log('Ready Qty');
            // console.table(readyQty);

            // console.log('Transfer Qty');
            // console.table(transQty);

            // console.groupEnd();

        });

        $(document).on('input', '.actual-input', function() {
            const idx = $(this).data('index');
            const actualBaru = Number($(this).val()) || 0;
            const actualLama = Number(window.oldActualQty?.[idx] || 0);
            const ready = Number(window.readyQty[idx] || 0);
            const transfer = Number(window.transQty[idx] || 0);
            const planIni = Number($(`input[name="qty${idx}p"]`).val()) || 0;

            hidePackingAlert();

            // ============================================================
            // Validasi 1 (BARU): Actual TIDAK BOLEH melebihi Plan carton ini
            // sendiri -- berdiri sendiri, terpisah dari validasi Transfer di
            // bawah. Contoh: Polibag Qty=5, Plan carton ini=2, input Actual=3
            // -> DITOLAK meskipun Transfer secara total masih cukup.
            // ============================================================
            if (actualBaru > planIni) {
                showPackingAlert(
                    '<b>Qty Actual tidak dapat disimpan.</b><br>' +
                    `Qty Actual yang diinput (<b>${actualBaru}</b>) melebihi Plan Qty pada carton ini (<b>${planIni}</b>).<br>` +
                    '<span class="text-danger fw-bold">Maksimal Qty Actual untuk size ini adalah ' + planIni + '.</span>'
                );

                $(this).val(actualLama);
                $(this).focus();

                return;
            }

            // ============================================================
            // Validasi 2 (sudah ada): total Actual GABUNGAN semua carton untuk
            // popk+size ini tidak boleh melebihi Transfer (Polibag Qty).
            // ============================================================
            const total = (ready - actualLama) + actualBaru;

            if (total > transfer) {
                const sisa = transfer - (ready - actualLama);
                showPackingAlert(
                    '<b>Qty Actual tidak dapat disimpan.</b><br>' +
                    'Jumlah Qty Actual yang diinput menyebabkan total Actual Pack Quantity melebihi Qty Polibag.<br>' +

                    '<span class="text-danger fw-bold">Maksimal Qty yang masih dapat diinput adalah ' +
                    Math.max(0, sisa) + '.</span>'
                );

                $(this).val(actualLama);
                $(this).focus();

                return;
            }
        });
    </script>
    {{-- JS Open Modal dan Validasi Input Actual --}}
    <script>
        let bulkCheckedRows = [];
        let bulkActualIds = [];

        function bulkActualCtn() {
            const rows = $('#dgPacking').datagrid('getChecked');

            if (rows.length === 0) {
                $.messager.alert('Informasi', 'Pilih minimal satu carton.');
                return;
            }

            bulkCheckedRows = rows;
            bulkActualIds = rows.map(r => r.packpk);

            $('#bulkSizeSelect').val('');
            $('#bulkActualWarning').addClass('d-none');

            renderBulkActualPreview('');

            bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkActualCtnModal')).show();
        }

        $(document).on('change', '#bulkSizeSelect', function() {
            renderBulkActualPreview($(this).val());
        });

        function renderBulkActualPreview(size) {
            const rows = bulkCheckedRows;
            if (!rows.length) return;

            const activeIdx = Object.keys(window.activeSizes || {});
            const sizeIndexes = (size === '' || size === null) ? activeIdx : [size];

            const remaining = {};
            const isFilled = {};

            sizeIndexes.forEach(i => {
                const ready = Number(window.readyQty[i] || 0);
                const transfer = Number(window.transQty[i] || 0);

                let sumToProcess = 0;
                isFilled[i] = {};

                rows.forEach(r => {
                    const plan = Number(r[`qtyp${i}`] || 0);
                    const exist = Number(r[`qty${i}`] || 0);

                    if (plan <= 0) {
                        isFilled[i][r.packpk] = null;
                        return;
                    }

                    if (exist >= plan) {
                        isFilled[i][r.packpk] = true;
                    } else {
                        isFilled[i][r.packpk] = false;
                        sumToProcess += exist;
                    }
                });

                remaining[i] = transfer - (ready - sumToProcess);
            });

            let html = '';
            let adaMasalah = false;

            rows.forEach((row, index) => {
                const parts = [];
                let adaPlan = false;

                sizeIndexes.forEach(i => {
                    const plan = Number(row[`qtyp${i}`] || 0);
                    if (plan <= 0) return;
                    adaPlan = true;

                    const namaSize = window.activeSizes[i] || `Size ${i}`;
                    const exist = Number(row[`qty${i}`] || 0);

                    if (isFilled[i][row.packpk] === true) {
                        parts.push(
                            `<span class="text-primary">✔ ${namaSize}: sudah terisi penuh (${exist})</span>`
                        );
                        return;
                    }

                    const butuh = plan - exist;
                    const avail = Math.max(0, remaining[i]);

                    if (avail >= butuh) {
                        const label = exist > 0 ? `OK (+${butuh})` : `OK (${plan})`;
                        parts.push(`<span class="text-success">✅ ${namaSize}: ${label}</span>`);
                        remaining[i] -= butuh;
                    } else if (avail > 0) {
                        parts.push(
                            `<span class="text-warning fw-semibold">⚠ ${namaSize}: hanya bisa +${avail} pcs (total ${exist + avail})</span>`
                        );
                        remaining[i] -= avail;
                        adaMasalah = true;
                    } else {
                        parts.push(`<span class="text-danger">❌ ${namaSize}: Transfer sudah habis</span>`);
                        adaMasalah = true;
                    }
                });

                if (!adaPlan) parts.push('<span class="text-muted">Tidak ada Plan</span>');

                html += `
            <tr>
                <td class="text-center">${index + 1}</td>
                <td class="text-center ">${row.nobar ?? ''}</td>
                <td class="text-center"><strong>${row.carton}</strong></td>
                <td class="text-start" style="font-size:12px; line-height:1.7;">
                    ${parts.join('<br>')}
                </td>
            </tr>`;
            });

            $('#bulkActualList').html(html);
            $('#bulkActualWarning').toggleClass('d-none', !adaMasalah);
        }

        function submitBulkActualCtn() {
            if (!bulkActualIds.length) {
                showToast('warning', 'Pilih minimal satu carton.');
                return;
            }

            $.ajax({
                url: "{{ route('packing.update-ctn') }}",
                method: 'POST',
                data: {
                    popk: {{ $popk }},
                    size: $('#bulkSizeSelect').val(),
                    packpk: bulkActualIds.join(',')
                },
                beforeSend: function() {
                    $('#btnUpdateActual').prop('disabled', true);
                },
                success: function(res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('bulkActualCtnModal')).hide();

                    // reload datagrid saja, TANPA reload halaman
                    $('#dgPacking').datagrid('reload');
                    reloadBreakdownSummary();
                },
                error: function(xhr) {
                    let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    showToast(res.icon, res.title);
                },
                complete: function() {
                    $('#btnUpdateActual').prop('disabled', false);
                }
            });
        }
    </script>
    {{-- JS Open Modal Copy CTN  --}}
    <script>
        let bulkCopyRows = [];

        function bulkCopy() {
            let rows = $('#dgPacking').datagrid('getChecked');
            if (rows.length == 0) {
                $.messager.alert('Informasi', 'Pilih minimal satu carton.');
                return;
            }

            bulkCopyRows = rows;

            let ids = rows.map(r => r.packpk);
            $('#copyIds').val(ids.join(','));

            renderCopyPreview();

            bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkCopyModal')).show();
        }

        $(document).on('input', '#copyAmount', function() {
            renderCopyPreview();
        });

        function renderCopyPreview() {
            const rows = bulkCopyRows;
            const copies = Math.max(1, parseInt($('#copyAmount').val()) || 1);
            const activeIdx = Object.keys(window.activeSizes || {});

            const remaining = {};
            activeIdx.forEach(i => {
                remaining[i] = Number(window.transQty[i] || 0) - Number(window.readyQty[i] || 0);
            });

            let html = '';
            let adaDitolak = false;

            rows.forEach((row, index) => {

                const punyaActual = activeIdx.some(i => Number(row[`qty${i}`] || 0) > 0);
                let statusParts = [];

                if (!punyaActual) {
                    statusParts.push('<span class="text-muted">Tidak ada Actual (hanya Plan yang dicopy)</span>');
                } else {

                    activeIdx.forEach(i => {
                        const actualAsal = Number(row[`qty${i}`] || 0);
                        if (actualAsal <= 0) return;

                        const namaSize = window.activeSizes[i] || `Size ${i}`;
                        let bisaSampaiCopy = 0;

                        let sisa = remaining[i];
                        for (let c = 1; c <= copies; c++) {
                            if (sisa >= actualAsal) {
                                sisa -= actualAsal;
                                bisaSampaiCopy = c;
                            } else {
                                break;
                            }
                        }
                        remaining[i] = sisa;

                        if (bisaSampaiCopy === copies) {
                            statusParts.push(
                                `<span class="text-success">✅ ${namaSize}: Actual tersalin ke semua ${copies} copy</span>`
                            );
                        } else if (bisaSampaiCopy > 0) {
                            statusParts.push(
                                `<span class="text-warning fw-semibold">⚠ ${namaSize}: Actual hanya tersalin ke copy 1-${bisaSampaiCopy}, sisanya (copy ${bisaSampaiCopy+1}-${copies}) tidak diisi — Transfer habis</span>`
                            );
                            adaDitolak = true;
                        } else {
                            statusParts.push(
                                `<span class="text-danger">❌ ${namaSize}: Transfer sudah habis, Actual tidak ikut dicopy sama sekali</span>`
                            );
                            adaDitolak = true;
                        }
                    });
                }

                html += `
            <tr>
                <td>${index + 1}</td>
                <td>${row.nobar ?? ''}</td>
                <td>${row.carton}</td>
                <td class="text-start" style="font-size:12px; line-height:1.7;">
                    ${statusParts.join('<br>')}
                </td>
            </tr>`;
            });

            $('#copyList').html(html);

            if ($('#copyWarning').length === 0) {
                $('#copyList').closest('.table-responsive').after(
                    '<div id="copyWarning" class="alert alert-warning py-2 px-3 mb-2 d-none" style="font-size:12.5px;">' +
                    '<i class="fas fa-exclamation-triangle me-1"></i> Beberapa Actual Qty tidak akan ikut tersalin karena Transfer sudah habis.' +
                    '</div>'
                );
            }
            $('#copyWarning').toggleClass('d-none', !adaDitolak);
        }

        function processCopy() {
            let ids = $('#copyIds').val();
            let copies = $('#copyAmount').val();

            if (!ids) {
                showToast('warning', 'Pilih minimal satu carton.');
                return;
            }

            if (!copies || copies < 1) {
                showToast('warning', 'Jumlah duplikasi minimal 1.');
                return;
            }

            $.ajax({
                url: "{{ route('packing.copy-selected') }}",
                method: 'POST',
                data: {
                    packpk: ids,
                    copy: copies
                },
                beforeSend: function() {
                    $('#btnProcessCopy').prop('disabled', true);
                },
                success: function(res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('bulkCopyModal')).hide();

                    // reload datagrid saja, TANPA reload halaman
                    $('#dgPacking').datagrid('reload');
                    reloadBreakdownSummary();
                },
                error: function(xhr) {
                    let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    showToast(res.icon, res.title);
                },
                complete: function() {
                    $('#btnProcessCopy').prop('disabled', false);
                }
            });
        }
    </script>
    {{-- JS Open Modal Urutkan CTN --}}
    <script>
        function openUrutkanCtnModal() {
            bootstrap.Modal
                .getOrCreateInstance(
                    document.getElementById('urutkanCtnModal')
                )
                .show();
        }

        function saveUrutCtn() {
            let awal = $('#urutCtnAwal').val().trim();
            let check = $('#format6Digit').is(':checked') ? 1 : 0;

            if (awal === '') {
                showToast('error','Nomor CTN Awal wajib diisi.');
                return;
            }

            $.ajax({
                url: "{{ route('packing.urut') }}",
                method: 'POST',
                data:{
                    popk: {{ $popk }},
                    awal: awal,
                    check: check
                },
                beforeSend:function(){
                    $('#btnSaveUrutCtn').prop('disabled',true);
                },
                success:function(res){

                    showToast(res.icon,res.title);

                    bootstrap.Modal
                        .getInstance(document.getElementById('urutkanCtnModal'))
                        .hide();

                    $('#dgPacking').datagrid('reload');
                    reloadBreakdownSummary();

                },
                error:function(xhr){

                    let res = xhr.responseJSON ?? {
                        icon:'error',
                        title:'Terjadi kesalahan.'
                    };

                    showToast(res.icon,res.title);

                },
                complete:function(){
                    $('#btnSaveUrutCtn').prop('disabled',false);
                }
            });

        }
    </script> 
    {{-- JS Open Modal Add Packing dan Edit Packing --}}
    <script>
        function openPackingModal() {
            hidePackingAlert();
            $('#packingModalTitle').text('Add Packing');
            $('#packingForm')[0].reset();
            $('input[name=packpk]').val('');
            $('#autoSplitCheck').prop('disabled', false).prop('checked', true);
            $('#actualRow').hide();
            $('.actual-input').val('').prop('disabled', true);
            bootstrap.Modal
                .getOrCreateInstance(document.getElementById('packingModal'))
                .show();
        }
        // JS Open Modal Edit Packing
        function editPacking(row) {
            hidePackingAlert();
            $('#dgPacking').datagrid('clearSelections');
            $('#dgPacking').datagrid('clearChecked');
            $('#packingModalTitle').text('Edit Packing');
            $('#packingForm')[0].reset();
            $('input[name=packpk]').val(row.packpk);
            $('input[name=nocar]').val(row.carton);
            $('input[name=nobar]').val(row.nobar);
            $('input[name=nw]').val(row.nw);
            $('input[name=gw]').val(row.gw);
            $('input[name=meas]').val(row.meas);
            $('input[name=ket2]').val(row.keterangan);
            $('#autoSplitCheck').prop('checked', false).prop('disabled', true);
            $('#actualRow').show();
            window.oldActualQty = {};

            @foreach ($activeSizes as $i => $sz)
                window.oldActualQty[{{ $i }}] = Number(row.qty{{ $i }} ?? 0);
            @endforeach

            @foreach ($activeSizes as $i => $sz)
                var plan = row.qtyp{{ $i }};
                var actual = row.qty{{ $i }};
                var planInput = $('input[name="qty{{ $i }}p"]');
                var actualInput = $('input[name="qty{{ $i }}"]');
                planInput.val(
                    (plan == null || plan == 0) ? '' : plan
                );
                actualInput.val(
                    (actual == null || actual == 0) ? '' : actual
                );
                if (plan != null && Number(plan) > 0) {
                    actualInput
                        .prop('disabled', false)
                        .val(actual ?? '')
                        .css('visibility', 'visible');
                } else {
                    actualInput
                        .val('')
                        .prop('disabled', true)
                        .css('visibility', 'hidden');
                }
            @endforeach
            bootstrap.Modal.getOrCreateInstance(document.getElementById('packingModal')).show();
        }

        function formatAction(value, row, index) {
            let isShipped = row.status == 5;
            let isSegel   = Number(row.segel) === 1;

            const historyBtn = `
                <a href="javascript:void(0)"
                    onclick="openHistoryModal(${row.packpk}, '${row.carton}')"
                    class="action-btn"
                    title="Histori Input Actual"
                    style="background:#f1f5f9;color:#475569;">
                    <i class="fas fa-history"></i>
                </a>
            `;

            if (isShipped || isSegel) {
                // Tetap tampilkan History walau sudah shipped/segel, cuma
                // Edit yang disembunyikan.
                return historyBtn;
            }

            const editBtn = `
                <a href="javascript:void(0)"
                onclick="editPackingByIndex(event,${index})"
                class="action-btn"
                title="Edit">
                    <i class="fas fa-edit"></i>
                </a>
            `;

            return `<div class="d-flex justify-content-center gap-1">${editBtn}${historyBtn}</div>`;
        }
        
        function isRowComplete(row) {
            const activeIdx = Object.keys(window.activeSizes || {});
            let hasAnyPlan = false;
        
            const semuaSama = activeIdx.every(function (i) {
                const plan = Number(row[`qtyp${i}`] || 0);
                if (plan <= 0) return true; // size tanpa Plan diabaikan
        
                hasAnyPlan = true;
                const actual = Number(row[`qty${i}`] || 0);
                return actual === plan;
            });
        
            // Carton tanpa Plan sama sekali tidak dianggap "Complete".
            return hasAnyPlan && semuaSama;
        }
        
        // ============================================================
        // KOLOM SEGEL — prioritas: Segel (final) > Complete > kosong.
        // ============================================================
        function formatSegel(value, row) {
            if (Number(row.segel) === 1) {
                return `
                    <img src="{{ asset('public/css/images/Segel_carton.png') }}" width="55">
                `;
            }
        
            if (isRowComplete(row)) {
                return `
                    <img src="{{ asset('public/css/images/Complete_carton.png') }}" width="55">
                `;
            }
        
            return '<span class="text-muted">-</span>';
        }

        function editPackingByIndex(e, index) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            const dg = $('#dgPacking');
            dg.datagrid('unselectRow', index);
            const row = dg.datagrid('getRows')[index];

            editPacking(row);
        }

        function savePacking() {
            $('.is-invalid').removeClass('is-invalid');
            hidePackingAlert();

            let errors = [];
            const ctn = $('input[name="nocar"]');
            if ($.trim(ctn.val()) == '') {
                ctn.addClass('is-invalid');
                errors.push('No Carton wajib diisi.');
            }

            let adaSize = false;
            $('.plan-input:visible').each(function() {
                let val = parseInt($(this).val());
                if (!isNaN(val) && val > 0) {
                    adaSize = true;
                    return false;
                }
            });

            if (!adaSize) {
                $('.plan-input:visible:first').addClass('is-invalid');
                errors.push('Minimal satu Qty Plan harus diisi.');
            }

            if (errors.length) {
                Swal.fire({
                    toast: true,
                    position: 'bottom-start',
                    icon: 'warning',
                    title: errors.join(' '),
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                return;
            }

            $.ajax({
                url: "{{ route('packing.store') }}",
                method: 'POST',
                data: $('#packingForm').serialize(),
                beforeSend: function() {
                    $('#btnSavePacking').prop('disabled', true);
                },
                success: function(res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('packingModal')).hide();

                    // reload datagrid packing saja, TANPA reload halaman
                    $('#dgPacking').datagrid('reload');
                    reloadBreakdownSummary();
                },
                error: function(xhr) {
                    let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };

                    // Tampilkan pesan error DI DALAM modal (bukan toast),
                    // karena pesan warning qty actual > transfer cukup panjang/detail
                    // dan modal harus tetap terbuka supaya user bisa perbaiki input.
                    showPackingAlert(res.title);
                },
                complete: function() {
                    $('#btnSavePacking').prop('disabled', false);
                }
            });
        }
    </script>
    {{-- JS Open Modal Delete CTN --}}
    <script>
        let bulkDeleteIds = [];

        function bulkDelete() {
            let rows = $('#dgPacking').datagrid('getChecked');

            if (rows.length === 0) {
                $.messager.alert(
                    'Informasi',
                    'Pilih minimal satu carton.'
                );
                return;
            }

            let html = '';
            bulkDeleteIds = [];

            rows.forEach(function(row, index) {

                bulkDeleteIds.push(row.packpk);

                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${row.nobar ?? ''}</td>
                        <td><strong>${row.carton}</strong></td>
                    </tr>
                `;
            });

            $('#bulkDeleteList').html(html);

            bootstrap.Modal.getOrCreateInstance(
                document.getElementById('bulkDeleteModal')
            ).show();
        }

        function confirmBulkDelete() {
            if (!bulkDeleteIds.length) {
                showToast('warning', 'Tidak ada carton yang dipilih.');
                return;
            }

            $.ajax({
                url: "{{ route('packing.delete-selected') }}",
                method: 'DELETE',
                data: {
                    packpk: bulkDeleteIds.join(',')
                },
                beforeSend: function() {
                    $('#btnConfirmBulkDelete').prop('disabled', true);
                },
                success: function(res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('bulkDeleteModal')).hide();

                    // reload datagrid saja, TANPA reload halaman
                    $('#dgPacking').datagrid('reload');
                    reloadBreakdownSummary();

                    bulkDeleteIds = [];
                },
                error: function(xhr) {
                    let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    showToast(res.icon, res.title);
                },
                complete: function() {
                    $('#btnConfirmBulkDelete').prop('disabled', false);
                }
            });
        }
    </script>
    {{-- JS Open Modal Delete Actual --}}
    <script>
        let bulkDeleteActualIds = [];

        function bulkDeleteActualCtn() {
            const rows = $('#dgPacking').datagrid('getChecked');
            if (rows.length === 0) {
                $.messager.alert(
                    'Informasi',
                    'Pilih minimal satu carton.'
                );
                return;
            }

            bulkDeleteActualIds = rows.map(row => row.packpk);

            let html = '';
            rows.forEach(function(row, index) {
                html += `
                    <tr>
                        <td>${index+1}</td>
                        <td>${row.nobar ?? ''}</td>
                        <td><strong>${row.carton}</strong></td>
                    </tr>
                `;
            });

            $('#bulkDeleteActualList').html(html);
            $('#bulkDeleteActualSize').val('');

            bootstrap.Modal
                .getOrCreateInstance(
                    document.getElementById('bulkDeleteActualCtnModal')
                )
                .show();
        }

        function submitDeleteActualCtn() {
            if (!bulkDeleteActualIds.length) {
                showToast('warning', 'Pilih minimal satu carton.');
                return;
            }

            $.ajax({
                url: "{{ route('packing.delete-actual') }}",
                method: 'DELETE',
                data: {
                    popk: {{ $popk }},
                    size: $('#bulkDeleteActualSize').val(),
                    packpk: bulkDeleteActualIds.join(',')
                },
                beforeSend: function() {
                    $('#btnDeleteActual').prop('disabled', true);
                },
                success: function(res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('bulkDeleteActualCtnModal')).hide();

                    // reload datagrid saja, TANPA reload halaman
                    $('#dgPacking').datagrid('reload');
                    reloadBreakdownSummary();
                },
                error: function(xhr) {
                    let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    showToast(res.icon, res.title);
                },
                complete: function() {
                    $('#btnDeleteActual').prop('disabled', false);
                }
            });
        }
    </script>
    {{-- JS Search Pakcing --}}
    <script>
        window.activeSizes = @json($activeSizes);
        let packingTimer = null;

        $(function() {
            $('#dgPacking').datagrid();
            initFilterSizeCombobox();
        });

        function initFilterSizeCombobox() {
            // Bangun data combobox dari activeSizes yang di-passing dari Blade.
            // window.activeSizes sudah ada (dipakai juga di modal-modal lain),
            // jadi kita reuse, tidak perlu foreach ulang di Blade.
            let sizeData = [{ value: '', text: 'Semua Size' }];

            Object.keys(window.activeSizes || {}).forEach(function(i) {
                sizeData.push({
                    value: i,
                    text: window.activeSizes[i]
                });
            });

            $('#filterSize').combobox({
                data: sizeData,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function(newValue, oldValue) {
                    reloadPacking();
                }
            });
        }

        function reloadPacking() {
            $('#dgPacking').datagrid('load', {
                search: $('#searchPacking').val(),
                size: $('#filterSize').combobox('getValue')
            });
        }

        $('#searchPacking').on('keyup', function() {
            clearTimeout(packingTimer);

            packingTimer = setTimeout(function() {
                reloadPacking();
            }, 300);
        });
    </script>
    {{-- JS Tabel Data Grid --}}
    <script>
        document.querySelectorAll('input[name$="p"][name^="qty"]').forEach(function(el) {
            el.addEventListener('input', function() {
                let match = this.name.match(/^qty(\d+)p$/);
                if (!match) return;

                let idx = match[1];
                let aCell = document.querySelector('input[name="qty' + idx + '"]');
                let aTd = aCell ? aCell.closest('td') : null;

                if (aTd) {
                    aTd.style.display = (parseInt(this.value) > 0) ? '' : 'none';
                }
            });
        });

        document.querySelectorAll('form[action*="delete-carton"]').forEach(function(f) {
            f.addEventListener('submit', function(e) {
                let jml = this.querySelector('[name=jml]').value;
                if (!confirm('Hapus ' + jml + ' carton terakhir?')) e.preventDefault();
            });
        });

        // function formatNo(value, row, index) {
        //     let opts = $('#dgPacking').datagrid('options');
        //     return ((opts.pageNumber - 1) * opts.pageSize) + index + 1;
        // }

        function formatPA(value, row) {
            return `
                <div style="line-height:18px;text-align:center;">
                    <div style="
                        font-weight:bold;
                        border-bottom:1px solid #dcdcdc;
                    
                    ">
                        P
                    </div>

                    <div style="
                        font-weight:bold;
                    ">
                        A
                    </div>
                </div>
            `;
        }

        function formatTotal(value, row) {
            return `
                <div style="line-height:18px;text-align:right">
                    <div class="text-muted">${row.pcsp || 0}</div>
                    <div><b>${row.pcs || 0}</b></div>
                </div>
            `;
        }

        function formatBalance(value, row) {
            let balance = (row.pcs || 0) - (row.pcsp || 0);
            let cls = balance < 0 ?
                'color:#dc3545;font-weight:bold' :
                'color:#198754;font-weight:bold';

            return `
                <span style="${cls}">
                    ${balance}
                </span>
            `;
        }

        @foreach ($activeSizes as $i => $sz)
            function formatSize{{ $i }}(value, row) {

                return `
                <div style="
                    display:flex;
                    flex-direction:column;
                    height:40px;
                    text-align:center;
                ">

                    <div style="
                        flex:1;
                        border-bottom:1px solid #d9d9d9;
                        color:#6c757d;
                    ">
                        ${row.qtyp{{ $i }} || ''}
                    </div>

                    <div style="
                        flex:1;
                        font-weight:bold;
                        color:#212529;
                    ">
                        ${row.qty{{ $i }} ?? '&nbsp;'}
                    </div>

                </div>
            `;
            }
        @endforeach

        function onLoadPacking(data) {
            const rows = data.rows || [];
            let panel = $('#dgPacking').datagrid('getPanel');
            let body = panel.find('.datagrid-view2 .datagrid-body');
            panel.find('.easyui-empty-state').remove();
            if (!rows.length) {
                body.append(`
                    <div class="easyui-empty-state">
                        <div style="text-align:center;padding:40px">
                            <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="180">
                            <div style="margin-top:8px;font-weight:600;">No Data Found</div>
                            <div style="font-size:12px;color:#9ca3af;">Try changing filter</div>
                        </div>
                    </div>
                `);
            }
            $('#dgPacking').datagrid('clearChecked');
            updateSelection();
        }

        function isRowFullyFilled(row) {
            const activeIdx = Object.keys(window.activeSizes || {});
        
            return activeIdx.every(function (i) {
                const plan = Number(row[`qtyp${i}`] || 0);
                if (plan <= 0) return true;
        
                const actual = Number(row[`qty${i}`] || 0);
                return actual > 0;
            });
        }
        
        function updateSelection() {
            let checked = $('#dgPacking').datagrid('getChecked');
            $('#selectedCount').text(checked.length);

            if (checked.length > 0) {
                $('#stickTopBar').show();
            } else {
                $('#stickTopBar').hide();
            }

            // Kalau ADA baris terpilih yang sudah berstatus Segel (segel==1),
            // sticky bar HANYA menampilkan menu "Buka Segel" -- semua menu lain
            // disembunyikan (Segel CTN, Input Actual, Delete Actual, Copy CTN,
            // Delete CTN tidak relevan untuk data yang sudah disegel).
            const hasSegel = checked.some(r => Number(r.segel) === 1);

            if (hasSegel) {
                $('#btnBukaSegel').removeClass('d-none');
                $('#btnBulkSegelCtn, #btnBulkActualCtn, #btnBulkDeleteActualCtn, #btnBulkCopy, #btnBulkDelete')
                    .addClass('d-none');
                return;
            }

            $('#btnBukaSegel').addClass('d-none');
            $('#btnBulkActualCtn, #btnBulkDeleteActualCtn, #btnBulkCopy, #btnBulkDelete').removeClass('d-none');

            // Menu "Segel CTN" cuma tampil kalau ADA yang dipilih DAN SEMUA baris
            // yang dipilih sudah lengkap (setiap size ber-Plan sudah ada Actual-nya).
            const allFilled = checked.length > 0 && checked.every(isRowFullyFilled);

            if (allFilled) {
                $('#btnBulkSegelCtn').removeClass('d-none');
            } else {
                $('#btnBulkSegelCtn').addClass('d-none');
            }
        }

        function closeMenu() {
            $('#dgPacking').datagrid('clearChecked');
            $('#dgPacking').datagrid('unselectAll');
            $('#dgPacking').datagrid('clearSelections');
            updateSelection();
        }

        // JS Open Modal Edit Info PO
        function openEditPackingModal(popk) {
            let modalEl = document.getElementById('editPackingModal');
            let modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            $('#editPackingForm .is-invalid').removeClass('is-invalid');
            $('#editPackingForm .invalid-feedback').remove();

            modal.show();
        }

        function rowStylerPacking(index, row) {
            if (row.status == 5) {
                return 'datagrid-row-packing-shipped';
            }
            return '';
        }

        function validatePackingCheck(index, row) {
            if (row.status == 5) {
                $.messager.alert(
                    'Peringatan',
                    'Carton yang sudah diproses Shipment tidak dapat dipilih.',
                    'warning'
                );

                $('#dgPacking').datagrid('uncheckRow', index);
                updateSelection();
                return;
            }

            const checked = $('#dgPacking').datagrid('getChecked');
            if (checked.length > 1) {
                const segelValues = new Set(checked.map(r => Number(r.segel)));
        
                if (segelValues.size > 1) {
                    $.messager.alert(
                        'Peringatan',
                        'Tidak bisa memilih carton dengan status Segel berbeda secara bersamaan. Pilih carton yang statusnya SAMA saja (semua sudah Segel, atau semua Complete).',
                        'warning'
                    );
        
                    $('#dgPacking').datagrid('uncheckRow', index);
                    updateSelection();
                    return;
                }
            }

            updateSelection();
        }

        function validatePackingCheckAll(rows) {
            if (!rows.length) {
                updateSelection();
                return;
            }

            let hasShipped = rows.some(row => row.status == 5);

            if (hasShipped) {
                $.messager.alert(
                    'Peringatan',
                    'Check All tidak diizinkan karena terdapat carton yang sudah diproses Shipment.',
                    'warning'
                );

                $('#dgPacking').datagrid('clearChecked');
                $('#dgPacking').datagrid('clearSelections');
                updateSelection();
                return;
            }

            const segelValues = new Set(rows.map(r => Number(r.segel)));
            if (segelValues.size > 1) {
                $.messager.alert(
                    'Peringatan',
                    'Check All tidak diizinkan karena carton yang tampil memiliki status Segel yang berbeda-beda.',
                    'warning'
                );
        
                $('#dgPacking').datagrid('clearChecked');
                $('#dgPacking').datagrid('clearSelections');
                updateSelection();
                return;
            }

            updateSelection();
        }

        function goBack() {
            // Cek apakah ada history yang bisa diback (dari halaman index)
            if (document.referrer && document.referrer.indexOf('/packing') !== -1) {
                window.history.back();
            } else {
                // Fallback: navigasi manual ke index
                window.location.href = "{{ route('packing.index') }}";
            }
        }
    </script>
    {{-- JS Modal update info PO --}}
    <script>
        document.getElementById('editPackingForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn.innerHTML;
            const formData = new FormData(form);
            const payload = {};
            formData.forEach((value, key) => { payload[key] = value; });

            $.ajax({
                url: "{{ route('packing.saveHeader') }}",
                method: 'POST',
                data: payload,
                beforeSend: function () {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
                },
                success: function (res) {
                    showToast(res.icon, res.title);

                    // Update card info PO
                    updatePoDetailCard(res.data);

                    // Reload breakdown size & summary ctn (tanpa reload halaman)
                    reloadBreakdownSummary();
                    $('#dgPacking').datagrid('reload');

                    // Tutup modal
                    bootstrap.Modal.getInstance(document.getElementById('editPackingModal'))?.hide();
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors || {};
                        const msg = Object.values(errors).flat().join('\n');
                        showToast('error', msg || 'Data tidak valid.');
                    } else {
                        const res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan saat menyimpan data.' };
                        showToast(res.icon, res.title);
                    }
                },
                complete: function () {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            });
        });

        function reloadBreakdownSummary() {
            $.get("{{ route('packing.breakdownSummary', $popk) }}", function (html) {
                $('#breakdownSummaryWrapper').html(html);
            });
        }

        function updatePoDetailCard(dt2) {
            const setText = (id, val, fallback = '-') => {
                const el = document.getElementById(id);
                if (el) el.textContent = (val === null || val === undefined || val === '') ? fallback : val;
            };
            const formatDate = (dateStr) => {
                if (!dateStr) return '-';
                const d = new Date(dateStr);
                if (isNaN(d)) return '-';
                return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/\./g, '');
            };
            setText('view_OP', dt2.OP);
            setText('view_POno', dt2.POno);
            setText('view_shipdate1', formatDate(dt2.shipdate1));
            setText('view_shipdate2', formatDate(dt2.shipdate2));
            setText('view_sap1', dt2.sap1);
            setText('view_sap2', dt2.sap2);
            setText('view_wh', dt2.wh);
            setText('view_ket', dt2.ket);
        }
    </script>
    {{-- JS Segel CTN --}}
    <script>
        let bulkSegelIds    = [];
        let bulkSegelTarget = 1; // 1 = mau disegel, 0 = mau dibuka segelnya

        function bulkSegelCtn() {
            openSegelModal(1);
        }

        function bulkBukaSegel() {
            openSegelModal(0);
        }

        function openSegelModal(target) {
            const rows = $('#dgPacking').datagrid('getChecked');

            if (rows.length === 0) {
                $.messager.alert('Informasi', 'Pilih minimal satu carton.');
                return;
            }

            // Validasi kelengkapan Actual cuma relevan kalau mau MENYEGEL (target=1).
            if (target === 1) {
                const belumLengkap = rows.filter(r => !isRowFullyFilled(r));
                if (belumLengkap.length > 0) {
                    const daftarCarton = belumLengkap.map(r => r.carton).join(', ');
                    $.messager.alert(
                        'Peringatan',
                        `Carton berikut belum lengkap Actual-nya, tidak bisa disegel: <b>${daftarCarton}</b>`,
                        'warning'
                    );
                    return;
                }
            }

            bulkSegelIds    = rows.map(r => r.packpk);
            bulkSegelTarget = target;

            if (target === 1) {
                $('#bulkSegelCtnModalTitle').text('Segel Carton');
                $('#bulkSegelInfoText').html(`
                    <i class="fas fa-info-circle me-0.5"></i>
                    <strong id="bulkSegelCount" class="text-dark">${rows.length}</strong> carton berikut akan disegel.
                    Carton yang sudah disegel <strong class="text-dark">tidak dapat diedit lagi</strong>.
                `);
                $('#btnSubmitSegelCtn').html('<i class="fas fa-lock small"></i> Segel Sekarang');
            } else {
                $('#bulkSegelCtnModalTitle').text('Buka Segel Carton');
                $('#bulkSegelInfoText').html(`
                    <i class="fas fa-info-circle me-0.5"></i>
                    <strong id="bulkSegelCount" class="text-dark">${rows.length}</strong> carton berikut akan dibuka segelnya
                    dan bisa diedit kembali.
                `);
                $('#btnSubmitSegelCtn').html('<i class="fas fa-unlock small"></i> Buka Segel Sekarang');
            }

            let html = '';
            rows.forEach(function (row, index) {
                html += `
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td class="text-center">${row.nobar ?? ''}</td>
                        <td class="text-center"><strong>${row.carton}</strong></td>
                        <td class="text-end">${row.pcs ?? 0}</td>
                    </tr>
                `;
            });
            $('#bulkSegelList').html(html);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkSegelCtnModal')).show();
        }

        function submitBulkSegelCtn() {
            if (!bulkSegelIds.length) {
                showToast('warning', 'Pilih minimal satu carton.');
                return;
            }

            $.ajax({
                url: "{{ route('packing.update-segel') }}",
                method: 'POST',
                data: {
                    popk: {{ $popk }},
                    packpk: bulkSegelIds.join(','),
                    target: bulkSegelTarget
                },
                beforeSend: function () {
                    $('#btnSubmitSegelCtn').prop('disabled', true);
                },
                success: function (res) {
                    showToast(res.icon, res.title);
                    bootstrap.Modal.getInstance(document.getElementById('bulkSegelCtnModal')).hide();

                    // reload datagrid saja, TANPA reload halaman
                    $('#dgPacking').datagrid('reload');
                    reloadBreakdownSummary();
                    closeMenu();

                    bulkSegelIds = [];
                },
                error: function (xhr) {
                    let res = xhr.responseJSON || { icon: 'error', title: 'Terjadi kesalahan.' };
                    showToast(res.icon, res.title);
                },
                complete: function () {
                    $('#btnSubmitSegelCtn').prop('disabled', false);
                }
            });
        }
    </script>
@endsection
