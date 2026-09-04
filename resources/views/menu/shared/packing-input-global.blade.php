{{-- menu/shared/packing-input-global.blade.php --}}
{{-- Dipakai oleh KEDUA halaman: Packing dan FG/Stuffing. Kontroler
     masing-masing mengirim $pageConfig untuk menentukan route & fitur
     mana yang aktif. --}}

@extends('layout.main')

@php
    $cfg = array_merge(
        [
            'mode' => 'packing',
            'pageTitlePrefix' => 'Packing list',
            'guserpkSegel' => [38],
            'guserpkShipmentFlow' => [35],
            'guserpkTerima' => [38],

            'showPlanningChips' => false,
            'showShipmentChips' => false,
            'showSizeFilter' => true,
            'showPartFilter' => false,
            'showAddPacking' => false,
            'showCtnManagement' => false,
            'showScanNobar' => false,
            'showShipmentPlan' => false,
            'showShipmentActions' => false,
            'showEditButton' => true,
            'showSealAction' => true, 
            'showKembalikanButton' => false, 
            'showHistoryTab' => false, 
            'showBaseChips' => true,
            'backRouteName' => 'packing.index',

            'routes' => [],
        ],
        $pageConfig ?? [],
    );

    $guserpk = Session::get('guserpk');
@endphp

@section('css_custom')
    <style>
        .page-wrap {
            padding: 16px
        }

        .btn-icon-custom:hover {
            background-color: #f8fafc !important;
            color: #1e293b !important;
            transform: translateX(-3px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1) !important
        }

        .packing-card {
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            padding: 14px 16px;
            height: 300px;
            display: flex;
            flex-direction: column;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease
        }

        .packing-card.selected {
            border-color: #359DD9;
            box-shadow: 0 0 0 2px rgba(53, 157, 217, .25);
            background-color: #f0f9ff
        }

        .packing-card .icon-btn,
        .packing-card .card-actions .btn {
            cursor: pointer
        }

        .packing-card .ribbon-segel {
            position: absolute;
            top: 14px;
            right: -48px;
            transform: rotate(45deg);
            background: #dc2626;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 8px 60px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .25);
            pointer-events: none;
            z-index: 10
        }

        .packing-card .packing-card-sizes {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            margin-bottom: 8px;
            padding-right: 4px
        }

        .packing-card .packing-card-sizes::-webkit-scrollbar {
            width: 5px
        }

        .packing-card .packing-card-sizes::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px
        }

        .packing-card .ctn-code {
            font-weight: 700;
            font-size: 14px;
            color: #0f172a
        }

        .packing-card .badge-soft {
            font-size: 10.5px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 999px;
            border: 1px solid transparent
        }

        .packing-card .badge-soft.solid {
            background: #f1f5f9;
            color: #64748b;
            border-color: #e2e8f0
        }

        .packing-card .badge-soft.assorted {
            background: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd
        }

        .packing-card .badge-soft.mixed {
            background: linear-gradient(90deg, #f97316, #64748b, #8b5cf6);
            color: #f1f5f9;
            border-color: #fde68a
        }

        .packing-card .badge-status {
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 999px
        }

        .packing-card .badge-status.planned {
            background: #f1f5f9;
            color: #64748b
        }

        .packing-card .badge-status.packing {
            background: #0b89d2;
            color: #ffff
        }

        .packing-card .badge-status.complete {
            background: #8bc63f;
            color: #ffff
        }

        .packing-card .badge-status.sealed {
            background: #321414;
            color: #e2e8f0
        }

        .packing-card .icon-btn {
            color: #94a3b8;
            cursor: pointer;
            font-size: 13px;
            padding: 2px 4px
        }

        .packing-card .icon-btn:hover {
            color: #1e293b
        }

        .packing-card .progress-main {
            height: 6px;
            background: #eef0f2;
            border-radius: 3px;
            overflow: hidden
        }

        .packing-card .progress-main .bar {
            display: block;
            height: 100%;
            border-radius: 3px
        }

        .packing-card .subline {
            font-size: 12.5px;
            color: #334155
        }

        .packing-card .size-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            padding: 3px 0
        }

        .packing-card .size-row .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #0f172a;
            flex-shrink: 0
        }

        .packing-card .size-row .name {
            flex: 0 0 auto;
            min-width: 110px;
            color: #334155
        }

        .packing-card .size-row .mini-progress {
            flex: 1;
            height: 5px;
            background: #eef0f2;
            border-radius: 3px;
            overflow: hidden
        }

        .packing-card .size-row .mini-progress .bar {
            display: block;
            height: 100%;
            border-radius: 3px
        }

        .packing-card .size-row .frac {
            flex: 0 0 44px;
            text-align: right;
            color: #64748b
        }

        .packing-card .card-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            flex-shrink: 0
        }

        .packing-card .card-actions .btn {
            flex: 1;
            font-size: 12px;
            padding: 6px 8px
        }

        .packing-card .card-barcode {
            font-size: 14px;
            color: #475569;
            background: #f8fafc;
            border: 1px dashed #e2e8f0;
            border-radius: 6px;
            padding: 4px 8px;
            margin-bottom: 8px;
            flex-shrink: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px
        }

        .card-barcode .barcode-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1 1 auto;
            min-width: 0
        }

        .sticky-order-bar {
            display: none;
            position: fixed;
            left: 0;
            right: 0;
            z-index: 1030;
            background: #359DD9;
            color: #fff;
            transition: top .15s ease
        }

        .sticky-order-inner {
            height: 44px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 16px
        }

        .sticky-action {
            cursor: pointer;
            margin-left: 10px;
            font-weight: 500;
            opacity: .9
        }

        .sticky-action:hover {
            opacity: 1
        }

        #pgBreakdownTable .pg-empty-cell {
            border-top: none
        }

        #pgBreakdownTable tr[data-role="plan"] td {
            border-bottom: none
        }

        #pgBreakdownTable tr[data-role="actual"] td {
            border-top: none;
            border-bottom: 1px dashed #e2e8f0
        }

        #packingCardsGrid.list-mode .packing-card {
            height: auto;
            min-height: 0
        }

        #packingCardsGrid.list-mode .packing-card-sizes {
            max-height: 160px
        }

        #viewToggleGlobal .btn.active {
            background-color: #1e293b;
            border-color: #1e293b;
            color: #fff
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s ease
        }

        .status-chip:hover {
            border-color: #cbd5e1;
            background: #f8fafc
        }

        .status-chip.active {
            background: #0f172a;
            border-color: #0f172a;
            color: #fff
        }

        .status-chip .chip-count {
            background: rgba(0, 0, 0, .08);
            border-radius: 999px;
            padding: 1px 7px;
            font-size: 11px
        }

        .status-chip.active .chip-count {
            background: rgba(255, 255, 255, .2)
        }

        .status-chip.active[data-status="complete"] {
            background: #8bc63f;
            border-color: #8bc63f
        }

        .status-chip.active[data-status="inspect"] {
            background: #f59e0b;
            border-color: #f59e0b
        }

        .status-chip.active[data-status="shipped"] {
            background: #2563eb;
            border-color: #2563eb
        }

        .status-chip.active[data-status="returning"] {
            background: #7c3aed;
            border-color: #7c3aed
        }

        #packingListTable .packing-list-row {
            cursor: pointer;
            transition: background-color .1s ease
        }

        #packingListTable .packing-list-row:hover td {
            background-color: #f8fafc
        }

        #packingListTable .packing-list-row.selected td {
            background-color: #f0f9ff;
            border-color: #bae6fd
        }

        #packingListTable td {
            vertical-align: middle
        }

        #packingListTable .icon-btn {
            color: #94a3b8;
            cursor: pointer;
            font-size: 13px;
            padding: 2px 4px
        }

        #packingListTable .icon-btn:hover {
            color: #1e293b
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #f1f5f9;
            transition: .2s
        }

        .action-btn:hover {
            background: #e2e8f0;
            transform: scale(1.05)
        }

        .action-btn-pdf img {
            display: block
        }

        .action-btn.action-btn-pdf {
            background-color: #fee2e2 !important;
            color: #b91c1c !important
        }

        .action-btn.action-btn-pdf:hover {
            background-color: #fecaca !important;
            color: #7f1d1d !important
        }

        .ship-stamp {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 78px;
            height: 78px;
            border-radius: 50%;
            border: 3px solid;
            background: #fff;
            flex-shrink: 0;
            text-align: center;
            line-height: 1.15;
            font-family: 'Arial Narrow', 'Oswald', 'Segoe UI Condensed', Arial, sans-serif;
            transform: rotate(-14deg)
        }

        .packing-card .ship-stamp {
            position: absolute;
            right: 8px;
            bottom: 8px;
            z-index: 20;
            box-shadow: 0 3px 8px rgba(0, 0, 0, .18)
        }

        .ship-stamp-text {
            font-size: 12.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .8px
        }

        .ship-stamp-date {
            font-size: 8.5px;
            font-weight: 600;
            opacity: .85;
            margin-top: 1px;
            font-family: Arial, sans-serif
        }

        .ship-stamp-shipped {
            border-color: #2563eb;
            color: #2563eb
        }

        .ship-stamp-inspect {
            border-color: #f59e0b;
            color: #f59e0b
        }

        .ship-stamp-returning {
            border-color: #7c3aed;
            color: #7c3aed
        }

        .ship-stamp.ship-stamp-sm {
            width: 42px;
            height: 42px;
            transform: rotate(-10deg)
        }

        .ship-stamp.ship-stamp-sm .ship-stamp-text {
            font-size: 8.5px
        }

        .ship-stamp.ship-stamp-sm .ship-stamp-date {
            display: none
        }

        .scan-nobar-wrap {
            display: flex;
            justify-content: center
        }

        .scan-nobar-card {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .05);
            padding: 18px 20px
        }

        .scan-nobar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px
        }

        .scan-nobar-icon-wrap {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #e0f2fe;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s ease
        }

        .scan-nobar-icon {
            font-size: 17px;
            color: #0369a1;
            transition: color .15s ease
        }

        .scan-nobar-icon-wrap.is-processing {
            background: #fef3c7
        }

        .scan-nobar-icon-wrap.is-processing .scan-nobar-icon {
            color: #d97706;
            animation: scanIconSpin .9s linear infinite
        }

        .scan-nobar-icon-wrap.is-success {
            background: #dcfce7
        }

        .scan-nobar-icon-wrap.is-success .scan-nobar-icon {
            color: #16a34a
        }

        .scan-nobar-icon-wrap.is-error {
            background: #fee2e2
        }

        .scan-nobar-icon-wrap.is-error .scan-nobar-icon {
            color: #dc2626
        }

        @keyframes scanIconSpin {
            from {
                transform: rotate(0)
            }

            to {
                transform: rotate(360deg)
            }
        }

        .scan-nobar-title {
            font-weight: 700;
            font-size: 14.5px;
            color: #0f172a
        }

        .scan-nobar-subtitle {
            font-size: 11.5px;
            color: #94a3b8
        }

        .scan-nobar-input-wrap {
            position: relative;
            margin-bottom: 10px
        }

        .scan-nobar-input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 13px;
            pointer-events: none
        }

        .scan-nobar-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            padding: 9px 12px 9px 34px;
            color: #0f172a;
            transition: border-color .15s ease, box-shadow .15s ease
        }

        .scan-nobar-input:focus {
            outline: none;
            border-color: #0369a1;
            box-shadow: 0 0 0 3px rgba(3, 105, 161, .12)
        }

        .scan-nobar-feedback {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8
        }

        .scan-nobar-feedback.is-processing {
            color: #d97706
        }

        .scan-nobar-feedback.is-success {
            color: #16a34a
        }

        .scan-nobar-feedback.is-error {
            color: #dc2626
        }

        .shipment-plan-card {
            width: 230px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .05)
        }

        .shipment-plan-card.is-active {
            border-color: #359DD9;
            box-shadow: 0 0 0 2px rgba(53, 157, 217, .2)
        }

        .shipment-plan-card.is-done {
            border-color: #8bc63f;
            background: #f7fdf1
        }

        .shipment-plan-title {
            font-weight: 700;
            font-size: 13.5px;
            color: #0f172a;
            margin-bottom: 6px
        }

        .shipment-plan-progress-bar {
            height: 6px;
            background: #eef0f2;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 6px
        }

        .shipment-plan-progress-bar .bar {
            display: block;
            height: 100%
        }

        .shipment-plan-count {
            font-size: 12px;
            color: #475569;
            margin-bottom: 10px
        }

        .shipment-plan-actions {
            display: flex;
            gap: 6px
        }

        .shipment-plan-actions .btn {
            flex: 1;
            font-size: 11.5px;
            padding: 5px 8px
        }

        .shipment-plan-shipdate {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 8px
        }

        .shipment-plan-shipdate strong {
            color: #0f172a
        }

        .inspec-carton-header .badge-soft {
            font-size: 10px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 999px;
            border: 1px solid transparent;
        }

        .inspec-carton-header .badge-soft.solid {
            background: #f1f5f9;
            color: #64748b;
            border-color: #e2e8f0;
        }

        .inspec-carton-header .badge-soft.assorted {
            background: #e0f2fe;
            color: #0369a1;
            border-color: #bae6fd;
        }

        .inspec-carton-header .badge-soft.mixed {
            background: linear-gradient(90deg, #f97316, #64748b, #8b5cf6);
            color: #f1f5f9;
            border-color: #fde68a;
        }

        .size-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            padding: 5px 0;
            border-bottom: 1px dashed #eef1f5;
        }

        .size-row:last-child {
            border-bottom: none;
        }

        .size-row .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #0f172a;
            flex-shrink: 0;
        }

        .size-row .name {
            flex: 1 1 auto;
            min-width: 0;
            color: #334155;
        }

        .size-row .frac {
            color: #64748b;
            font-weight: 600;
        }

        .shipment-plan-card.is-active {
            position: sticky;
            top: var(--shipment-plan-sticky-top, 70px); 
            z-index: 40;
            box-shadow: 0 8px 20px rgba(0,0,0,.12);
            border: 1.5px solid #1e293b;
        }

        #activeShipmentFloatingCard {
            position: fixed;
            right: 20px;
            bottom: 20px;
            width: 260px;
            z-index: 1055; 
            background: #fff;
            border: 1.5px solid #1e293b;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,.18);
            padding: 14px;
            animation: shipmentFloatIn .2s ease;
        }
        @keyframes shipmentFloatIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 576px) {
            #activeShipmentFloatingCard {
                right: 12px;
                left: 12px;
                bottom: 12px;
                width: auto;
            }
        }

        /* section('css_custom') -- mode Compact/Tile, banyak carton
        muat dalam 1 layar (referensi: "carton wall" / bin-map di sistem WMS
        garment). */

        .packing-compact-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .packing-compact-tile {
            position: relative;
            width: 86px;
            height: 58px;
            border: 1px solid transparent;
            border-radius: 6px;
            padding: 5px 6px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: box-shadow .12s ease, transform .1s ease;
        }
        .packing-compact-tile:hover {
            box-shadow: 0 3px 10px rgba(15,23,42,.18);
            transform: translateY(-1px);
            z-index: 2;
        }
        .packing-compact-tile.selected {
            outline: 2px solid #2563eb;
            outline-offset: -2px;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .3);
            position: relative; /* pastikan ::after ter-posisi relatif ke tile ini */
        }

        .packing-compact-tile.selected::after {
            content: "\f00c"; /* fa-check */
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            bottom: 3px;
            right: 4px;
            width: 13px;
            height: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7.5px;
            color: #fff;
            background: #2563eb;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,.35);
            z-index: 4;
        }
        
        /* Warna background PENUH per status -- ini bagian utama yang berubah. */
        .packing-compact-tile.pc-status-planned {
            background: #f1f5f9;
        }
        .packing-compact-tile.pc-status-planned .pc-carton,
        .packing-compact-tile.pc-status-planned .pc-qty { color: #64748b; }
        
        .packing-compact-tile.pc-status-packing {
            background: #dbeefc;
        }
        .packing-compact-tile.pc-status-packing .pc-carton,
        .packing-compact-tile.pc-status-packing .pc-qty { color: #0369a1; }
        
        .packing-compact-tile.pc-status-complete {
            background: #dcfce7;
        }
        .packing-compact-tile.pc-status-complete .pc-carton,
        .packing-compact-tile.pc-status-complete .pc-qty { color: #15803d; }
        
        .packing-compact-tile.pc-status-sealed {
            background: #0f172a;
        }
        .packing-compact-tile.pc-status-sealed .pc-carton,
        .packing-compact-tile.pc-status-sealed .pc-qty { color: #f1f5f9; }
        .packing-compact-tile.pc-status-sealed .pc-progress { background: rgba(255,255,255,.15); }
        
        .packing-compact-tile .pc-carton {
            font-weight: 700;
            font-size: 11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.15;
        }
        .packing-compact-tile .pc-qty {
            font-size: 9px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            opacity: .85;
        }
        .packing-compact-tile .pc-progress {
            height: 3px;
            background: rgba(0,0,0,.08);
            border-radius: 2px;
            overflow: hidden;
        }
        .packing-compact-tile .pc-progress .bar {
            display: block;
            height: 100%;
            background: currentColor;
            opacity: .55;
        }
        .packing-compact-tile .pc-badges {
            position: absolute;
            top: 2px;
            right: 3px;
            display: flex;
            gap: 2px;
        }
        .packing-compact-tile .pc-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            box-shadow: 0 0 0 1px rgba(255,255,255,.6);
        }
        .packing-compact-tile .pc-dot.reject { background: #dc2626; }
        .packing-compact-tile .pc-dot.shipped { background: #2563eb; }

        .packing-compact-tile .pc-edit-btn {
            position: absolute;
            top: 2px;
            right: 3px;
            font-size: 8.5px;
            color: inherit;
            opacity: .55;
            cursor: pointer;
            padding: 2px;
            z-index: 3;
        }
        .packing-compact-tile .pc-edit-btn:hover {
            opacity: 1;
        }

        .spd-poop-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 5px 10px;
            font-size: 11.5px;
            margin: 2px;
            cursor: pointer;
            transition: all .12s ease;
        }
        .spd-poop-chip:hover {
            border-color: #94a3b8;
            background: #e2e8f0;
        }
        .spd-poop-chip.active {
            background: #1e293b;
            border-color: #1e293b;
            color: #fff;
        }
        .spd-poop-chip .spd-mif-tag {
            font-size: 9.5px;
            font-weight: 700;
            background: rgba(0,0,0,.08);
            border-radius: 4px;
            padding: 1px 5px;
        }
        .spd-poop-chip.active .spd-mif-tag {
            background: rgba(255,255,255,.18);
        }

        .spd-container-box {
            border: 3px solid #64748b;
            border-radius: 10px;
            background: linear-gradient(180deg, #f8fafc 0%, #eef1f5 100%);
            padding: 14px;
            min-height: 130px;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            align-content: flex-start;
            position: relative;
        }
        .spd-container-box::before {
            content: "CONTAINER";
            position: absolute;
            top: -10px;
            left: 10px;
            background: #fff;
            padding: 0 6px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .6px;
            color: #94a3b8;
        }
        .spd-carton-box {
            min-width: 34px;
            height: 26px;
            padding: 0 4px;
            background: #d97706;
            border: 1px solid #92400e;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9.5px;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 1px 1px rgba(0,0,0,.25);
            white-space: nowrap;
            opacity: 0;
            transform: scale(0.4);
            animation: spdCartonIn .25s ease forwards;
            transition: opacity .2s ease, filter .2s ease, transform .15s ease;
            cursor: default;
        }
        .spd-carton-box:hover {
            transform: scale(1.15);
            z-index: 2;
        }
        .spd-carton-box.dimmed {
            opacity: .15 !important;
            filter: grayscale(1);
            transform: scale(0.85) !important;
        }
        @keyframes spdCartonIn {
            to { opacity: 1; transform: scale(1); }
        }
        .spd-container-empty {
            color: #94a3b8;
            font-size: 12px;
            padding: 30px 0;
            width: 100%;
            text-align: center;
        }
        .spd-carton-box.shipped {
            background: #2563eb;
            border-color: #1e3a8a;
        }
    </style>
@endsection

@section('content')
    <div id="stickTopBarGlobal" class="sticky-order-bar">
        <div class="sticky-order-inner">
            <div><strong><span id="selectedCountGlobal">0</span> carton terpilih</strong></div>
            <div>
                @if ($cfg['showShipmentActions'] && in_array($guserpk, $cfg['guserpkShipmentFlow']))
                    <span class="sticky-action d-none" id="btnProsesShipmentGlobal"
                        onclick="bulkProsesShipmentGlobal()">Proses Shipment</span>
                @endif
                @if ($cfg['showKembalikanButton'])
                    <span class="sticky-action d-none" id="btnBuatDokumenInspectGlobal"
                        onclick="openInspectDocumentModal()">
                        Buat Dokumen Inspect
                    </span>
                    <span class="sticky-action d-none" id="btnKembalikanStuffingGlobal"
                        onclick="confirmKembalikanStuffing()">
                        Kembalikan
                    </span>
                @endif
                @if ($cfg['showShipmentActions'] && in_array($guserpk, $cfg['guserpkTerima']))
                    <span class="sticky-action d-none" id="btnTerimaCartonGlobal" onclick="terimaCartonGlobal()">Terima Carton</span>
                @endif
                @if (in_array($guserpk, $cfg['guserpkSegel']))
                    <span class="sticky-action d-none" id="btnBukaSegelGlobal" onclick="bulkBukaSegelGlobal()">Buka Segel</span>
                    <span class="sticky-action" id="btnBulkSegelCtnGlobal" onclick="bulkSegelCtnGlobal()">Segel CTN</span>
                    <span class="sticky-action d-none" id="btnProsesInspectGlobal"
                        onclick="bulkProsesInspectGlobal()">Proses Inspect</span>
                @endif
                @if ($cfg['showCtnManagement'])
                    <span class="sticky-action" id="btnBulkDimensiGlobal" onclick="openBulkDimensiModal()">Edit Ukuran CTN</span>
                    <span class="sticky-action" id="btnBulkActualCtnGlobal" onclick="bulkActualCtnGlobal()">Input Actual</span>
                    <span class="sticky-action" id="btnBulkDeleteActualCtnGlobal"
                        onclick="bulkDeleteActualCtnGlobal()">Delete Actual</span>
                    <span class="sticky-action" id="btnBulkCopyGlobal" onclick="bulkCopyGlobal()">Copy CTN</span>
                    <span class="sticky-action" id="btnBulkDeleteGlobal" onclick="bulkDeleteCtnGlobal()">Delete CTN</span>
                @endif
                <span class="sticky-action" onclick="closeMenuGlobal()">Close</span>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4 px-4">
        <div
            class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-3 mb-4 gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:void(0)" onclick="goBack()"
                    class="btn btn-icon-custom d-inline-flex align-items-center justify-content-center shadow-sm border bg-white text-secondary rounded-circle"
                    style="width:38px;height:38px;transition:all .2s ease" title="Kembali ke Daftar Data OP">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold text-dark mb-0" style="font-size:1.15rem;letter-spacing:-.3px">
                        {{ $cfg['pageTitlePrefix'] }} - Style {{ $dt2->style ?? '-' }}
                    </h4>
                    <span>OP {{ $op }} &middot; Season {{ $dt2->season ?? '-' }} &middot; Buyer:
                        {{ $dt2->buyer ?? '-' }} &middot; PO {{ $po }}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('laporan.pdf.global', ['po' => $po, 'op' => $op, 'poref' => $poref ?? null, 'mif' => $mif]) }}"
                    target="_blank" class="action-btn action-btn-pdf" title="Print PDF">
                    <img src="{{ asset('/public/css/images/pdf.jpg') }}" width="18" height="18">
                </a>
            </div>
        </div>

        <div id="headerInfoWrapperGlobal">
            @include($cfg['routes']['headerPartial'], [
                'dt2' => $dt2,
                'poNoList' => $poNoList,
                'colorList' => $colorList,
                'allPopks' => $allPopks,
            ])
        </div>

        <div id="cardsInfoWrapperGlobal">
            @include($cfg['routes']['cardsInfoPartial'], [
                'totalPcs' => array_sum($orderQty ?? []),
                'plannedPcs' => array_sum($planQty ?? []),
                'packedPcs' => array_sum($readyQty ?? []),
                'shortPcs' => max(0, array_sum($orderQty ?? []) - array_sum($planQty ?? [])),
                'totalColors' => $totalColors ?? 0,
                'totalSizes' => count($activeSizes ?? []),
                'totalCarton' => $totalCarton ?? 0,
                'sealedCarton' => $sealedCarton ?? 0,
                'openCarton' => $openCarton ?? 0,
            ])
        </div>

        @if ($cfg['showShipmentPlan'] && in_array($guserpk, $cfg['guserpkShipmentFlow']))
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="fw-bold text-dark" style="font-size:15px;">
                        <span class="rounded me-2"
                            style="width:4px;height:16px;display:inline-block;background:#64748b;"></span>
                        Shipment Plan
                    </div>
                    <div class="text-muted" style="font-size:11.5px;" id="activeSessionBadge"></div>
                </div>
                <div id="shipmentPlanCards" class="d-flex gap-3 flex-wrap"></div>
            </div>
        @endif

        <div id="breakdownSummaryWrapper">
            @include($cfg['routes']['breakdownPartial'])
        </div>

        @if ($cfg['showScanNobar'] && in_array($guserpk, $cfg['guserpkShipmentFlow']))
            <div class="scan-nobar-wrap mb-4">
                <div class="scan-nobar-card">
                    <div class="scan-nobar-header">
                        <div class="scan-nobar-icon-wrap" id="scanIconWrapGlobal">
                            <i class="fas fa-barcode scan-nobar-icon" id="scanIconGlobal"></i>
                        </div>
                        <div>
                            <div class="scan-nobar-title">Scan Barcode Carton</div>
                            <div class="scan-nobar-subtitle">Untuk proses Shipment</div>
                        </div>
                    </div>
                    <div class="scan-nobar-input-wrap">
                        <i class="fas fa-search scan-nobar-input-icon"></i>
                        <input type="text" id="scanNobarInputGlobal" class="scan-nobar-input"
                            placeholder="Scan barcode / ketik nobar lalu Enter..." autocomplete="off">
                    </div>
                    <div class="scan-nobar-feedback" id="scanNobarFeedbackGlobal">
                        <i class="fas fa-circle-info"></i><span>Siap menerima scan.</span>
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="rounded me-2"
                        style="width:4px;height:16px;display:inline-block;background:#64748b;"></span>
                    <strong class="text-dark"> Detail Packing / Carton </strong>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="px-3 py-2 border-bottom bg-white">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="input-group" style="width:260px;">
                                <span class="input-group-text search">
                                    <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18"
                                        alt="Search">
                                </span>
                                <input type="text" class="form-control search" id="searchPackingGlobal"
                                    placeholder="Search Barcode / No CTN">
                            </div>
                            @if ($cfg['showSizeFilter'])
                                <input id="filterSizeGlobal" style="width:110px;">
                            @endif
                            <input id="filterColorGlobal" style="width:150px;">
                            <input id="filterSecszGlobal" style="width:150px;">
                            <input id="sortFieldGlobal" style="width:150px;">
                            @if ($cfg['showPartFilter'])
                                <input id="filterPartGlobal" style="width:150px;">
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="btn-group btn-group-sm" role="group" id="viewToggleGlobal">
                                <button type="button" class="btn btn-outline-secondary" id="viewModeCompactBtn"
                                    onclick="setViewModeGlobal('compact')" title="Tampilan Compact (banyak carton sekaligus)"><i class="fas fa-table-cells"></i></button>
                                <button type="button" class="btn btn-outline-secondary active" id="viewModeGridBtn"
                                    onclick="setViewModeGlobal('grid')" title="Tampilan Grid"><i class="fas fa-th-large"></i></button>
                                <button type="button" class="btn btn-outline-secondary" id="viewModeListBtn"
                                    onclick="setViewModeGlobal('list')" title="Tampilan List"><i class="fas fa-list"></i></button>
                            </div>
                            
                            @if ($cfg['showAddPacking'])
                                <button class="btn btn-dark btn-sm d-flex align-items-center fw-semibold"
                                    style="font-size:12px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                                    onclick="openUrutkanCtnModal()">
                                    <i class="fas fa-sort-numeric-down me-1"></i> Penomoran CTN
                                </button>
                                <button class="btn btn-dark btn-sm d-flex align-items-center fw-semibold"
                                    style="font-size:12px;border-radius:6px;background:#1e293b;border-color:#1e293b;"
                                    onclick="openPackingGlobalModal()">
                                    <i class="fas fa-plus me-1"></i> Add Packing
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($cfg['showHistoryTab'])
                    <div class="px-3 py-2 border-bottom bg-white">
                        <div class="btn-group btn-group-sm" role="group" id="inspectionTabToggle">
                            <button type="button" class="btn btn-outline-dark active" id="tabInspectCurrentBtn"
                                onclick="setInspectionTab('current')">
                                <i class="fas fa-hourglass-half me-1"></i> Sedang Inspect
                            </button>
                            <button type="button" class="btn btn-outline-dark" id="tabInspectHistoryBtn"
                                onclick="setInspectionTab('history')">
                                <i class="fas fa-clock-rotate-left me-1"></i> History Inspect
                            </button>
                            <button type="button" class="btn btn-outline-dark" id="tabInspectDocumentsBtn"
                                onclick="setInspectionTab('documents')">
                                <i class="fas fa-clipboard-check me-1"></i> Dokumen Inspect
                            </button>
                        </div>
                    </div>
                @endif

                <div class="p-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-3" id="statusFilterChipsGlobal">
                        @if ($cfg['showBaseChips'])
                            <span class="status-chip active" data-status="" onclick="setStatusFilterGlobal('')">Semua
                                <span class="chip-count" id="chipCountAll">0</span></span>
                        @endif
                        @if ($cfg['showPlanningChips'])
                            <span class="status-chip" data-status="planned"
                                onclick="setStatusFilterGlobal('planned')">Planned <span class="chip-count"
                                    id="chipCountPlanned">0</span></span>
                            <span class="status-chip" data-status="packing"
                                onclick="setStatusFilterGlobal('packing')">Packing <span class="chip-count"
                                    id="chipCountPacking">0</span></span>
                        @endif
                        @if ($cfg['showBaseChips'])
                            <span class="status-chip" data-status="complete"
                                onclick="setStatusFilterGlobal('complete')">Complete <span class="chip-count"
                                    id="chipCountComplete">0</span></span>
                            <span class="status-chip" data-status="sealed"
                                onclick="setStatusFilterGlobal('sealed')">Sealed
                                <span class="chip-count" id="chipCountSealed">0</span></span>
                        @endif
                        @if ($cfg['showShipmentChips'])
                            <span class="status-chip" data-status="inspect"
                                onclick="setStatusFilterGlobal('inspect')">Inspect <span class="chip-count"
                                    id="chipCountInspect">0</span></span>
                            <span class="status-chip" data-status="shipped"
                                onclick="setStatusFilterGlobal('shipped')">Shipped <span class="chip-count"
                                    id="chipCountShipped">0</span></span>
                        @endif
                        @if (in_array($guserpk, $cfg['guserpkSegel']))
                            <span class="status-chip" data-status="returning"
                                onclick="setStatusFilterGlobal('returning')">Carton dari QA <span class="chip-count"
                                    id="chipCountReturning">0</span></span>
                        @endif
                        <div class="ms-auto d-flex align-items-center gap-2">
                            <span class="text-secondary" style="font-size:12px;">Tampilkan</span>
                            <input id="pageSizeGlobal" style="width:90px;">
                        </div>
                        <button type="button" id="btnSelectAllVisible" class="btn btn-sm btn-outline-dark"
                            style="font-size:12px;" onclick="toggleSelectAllVisible()">
                            <i class="fas fa-check-double me-1"></i> Pilih Semua
                        </button>
                    </div>

                    <div id="packingCardsGrid" class="row g-3"></div>
                    <div id="packingListTableWrapper" class="table-responsive d-none">
                        <table class="table table-sm table-hover align-middle mb-0" id="packingListTable">
                            <thead class="table-light text-secondary"
                                style="font-size:11px;text-transform:uppercase;letter-spacing:.3px;">
                                <tr>
                                    <th class="text-start py-2">No CTN</th>
                                    <th class="text-start py-2">Barcode</th>
                                    <th class="text-start py-2">Color / Sec Size</th>
                                    <th class="text-center py-2">Status</th>
                                    <th class="py-2">Progress</th>
                                    <th class="text-center py-2" width="90">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="packingListBody"></tbody>
                        </table>
                    </div>
                    {{-- TAMBAHKAN wrapper baru ini TEPAT SETELAH #packingListTableWrapper --}}
                    <div id="packingCompactWrapper" class="d-none">
                        <div id="packingCompactGrid" class="packing-compact-grid"></div>
                    </div>
                    <div id="packingCardsEmpty" class="d-none text-center py-5">
                        <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="160">
                        <div class="fw-semibold mt-2">No Data Found</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-secondary" style="font-size:12.5px;" id="packingCardsInfo"></div>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-outline-secondary" id="btnPackingCardsPrev"
                                onclick="packingCardsGoPage(-1)"><i class="fas fa-chevron-left"></i></button>
                            <span style="font-size:12.5px;" id="packingCardsPageLabel"></span>
                            <button class="btn btn-sm btn-outline-secondary" id="btnPackingCardsNext"
                                onclick="packingCardsGoPage(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($cfg['showAddPacking'] || $cfg['showCtnManagement'])
        @include($cfg['routes']['modalEditInfoPacking'])
        @include($cfg['routes']['modalPacking'])
        @include($cfg['routes']['modalActualCtn'])
        @include($cfg['routes']['modalDeleteActualCtn'])
        @include($cfg['routes']['modalDeleteCtn'])
        @include($cfg['routes']['modalCopyCtn'])
        @include($cfg['routes']['modalUrutkanCtn'])
        @include($cfg['routes']['modalBulkDimensiCtn'])
    @endif
    @if ($cfg['showSealAction'])
        @include($cfg['routes']['modalSegelCtn'])
    @endif
    @if ($cfg['showKembalikanButton'])
        @include($cfg['routes']['modalKembalikanStuffing'])
        @include($cfg['routes']['modalInspectDocument'])
    @endif
    @if ($cfg['showShipmentActions'])
        @include($cfg['routes']['modalShipmentCtn'])
        @include($cfg['routes']['modalEndSession'])
        @include($cfg['routes']['modalTerimaCarton'])
    @endif
@endsection

@section('js_custom')
    <script>
        window.pageCfg = @json($cfg);
        window.pgCombos = @json($colorSecszCombos ?? []);
        window.pgSizes = @json($activeSizes);
        window.canManageSegel = @json(in_array($guserpk, $cfg['guserpkSegel']));
        window.isSupervisorStuffing = @json(in_array($guserpk, [34]));
        window.activeSizesGlobal = @json($activeSizes);
        window.colorListGlobal = @json($colorList ?? []);
        window.secszListGlobal = @json($secszList ?? []);
        window.packingViewModeGlobal = localStorage.getItem('packingViewModeGlobal') || 'grid';
        window.isStuffingUser = @json(in_array($guserpk, $cfg['guserpkShipmentFlow'] ?? []));
        const R = window.pageCfg.routes;
        const PO = @json($po),
            OP = @json($op),
            POREF = @json($poref ?? null),
            MIF = @json($mif);
        const SESSION_KEY = 'stuffingActivePart_' + PO + '_' + OP;
    </script>

    <script>
        function reloadBreakdownSummary() {
            $.get(R.breakdownSummaryGlobal, {
                po: PO,
                op: OP,
                poref: POREF,
                mif: MIF
            }, function(html) {
                $('#breakdownSummaryWrapper').html(html);
                matrixTab = 'planning';
                $('.legend-item').removeClass('active');
                classifyMatrixCells();
            });
        }

        function refreshPgCombos() {
            return $.get(R.combosGlobal, {
                po: PO,
                op: OP,
                poref: POREF,
                mif: MIF
            }, function(data) {
                window.pgCombos = data || [];
            });
        }

        function reloadCardsInfoGlobal() {
            $.get(R.cardsInfoGlobal, {
                po: PO,
                op: OP,
                poref: POREF,
                mif: MIF
            }, function(html) {
                $('#cardsInfoWrapperGlobal').html(html);
            });
        }

        function reloadHeaderInfoGlobal() {
            return $.get(R.headerInfoGlobal, {
                po: PO,
                op: OP,
                poref: POREF,
                mif: MIF
            }, function(html) {
                $('#headerInfoWrapperGlobal').html(html);
            });
        }

        function classifyMatrixCells() {
            document.querySelectorAll('#matrixTable td.matrix-cell').forEach(function(td) {
                const order = Number(td.dataset.order || 0),
                    trans = Number(td.dataset.trans || 0),
                    plan = Number(td.dataset.plan || 0),
                    actual = Number(td.dataset.actual || 0);
                td.classList.remove('cov-exact', 'cov-short', 'cov-over', 'cov-none', 'cov-blank');
                let covColor = '#cbd5e1';
                if (order === 0 && trans === 0) td.classList.add('cov-blank');
                else if (trans === 0 && order > 0) {
                    td.classList.add('cov-none');
                    covColor = '#94a3b8';
                } else if (trans < order) {
                    td.classList.add('cov-short');
                    covColor = '#dc2626';
                } else if (trans > order) {
                    td.classList.add('cov-over');
                    covColor = '#2563eb';
                } else {
                    td.classList.add('cov-exact');
                    covColor = '#16a34a';
                }
                td.classList.remove('pack-full', 'pack-progress', 'pack-empty', 'pack-blank');
                let packColor = '#cbd5e1';
                if (plan === 0) td.classList.add('pack-blank');
                else if (actual >= plan) {
                    td.classList.add('pack-full');
                    packColor = '#8bc63f';
                } else if (actual > 0) {
                    td.classList.add('pack-progress');
                    packColor = '#f97316';
                } else {
                    td.classList.add('pack-empty');
                    packColor = '#94a3b8';
                }
                td.classList.remove('plan-exact', 'plan-short', 'plan-over', 'plan-none', 'plan-blank');
                let planColor = '#cbd5e1';
                if (order === 0 && plan === 0) td.classList.add('plan-blank');
                else if (plan === 0 && order > 0) {
                    td.classList.add('plan-none');
                    planColor = '#94a3b8';
                } else if (plan < order) {
                    td.classList.add('plan-short');
                    planColor = '#f97316';
                } else if (plan > order) {
                    td.classList.add('plan-over');
                    planColor = '#2563eb';
                } else {
                    td.classList.add('plan-exact');
                    planColor = '#8bc63f';
                }
                const covPct = order > 0 ? Math.min(100, (trans / order) * 100) : (trans > 0 ? 100 : 0),
                    packPct = plan > 0 ? Math.min(100, (actual / plan) * 100) : 0,
                    planPct = order > 0 ? Math.min(100, (plan / order) * 100) : (plan > 0 ? 100 : 0);
                const covFill = td.querySelector('.cov .matrix-bar-fill');
                if (covFill) {
                    covFill.style.width = covPct + '%';
                    covFill.style.background = covColor;
                }
                const packFill = td.querySelector('.pack .matrix-bar-fill');
                if (packFill) {
                    packFill.style.width = packPct + '%';
                    packFill.style.background = packColor;
                }
                const planFill = td.querySelector('.planning .matrix-bar-fill');
                if (planFill) {
                    planFill.style.width = planPct + '%';
                    planFill.style.background = planColor;
                }
            });
            document.querySelectorAll('.matrix-color-dot').forEach(function(dot) {
                const name = dot.dataset.name || '';
                let hash = 0;
                for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
                dot.style.background = `hsl(${Math.abs(hash)%360}, 45%, 45%)`;
            });
        }
        let matrixTab = 'planning';

        function setMatrixTab(tab) {
            matrixTab = tab;
            $('.matrix-tab-toggle button').removeClass('active');
            const btnMap = {
                coverage: '#matrixTabCoverage',
                planning: '#matrixTabPlanning',
                packing: '#matrixTabPacking'
            };
            $(btnMap[tab]).addClass('active');
            $('#matrixLegendCoverage').toggleClass('d-none', tab !== 'coverage');
            $('#matrixLegendPlanning').toggleClass('d-none', tab !== 'planning');
            $('#matrixLegendPacking').toggleClass('d-none', tab !== 'packing');
            $('#matrixTable .cov').toggleClass('d-none', tab !== 'coverage');
            $('#matrixTable .planning').toggleClass('d-none', tab !== 'planning');
            $('#matrixTable .pack').toggleClass('d-none', tab !== 'packing');
            const subtitles = {
                coverage: 'Polibag vs Order per Color & Sec Size — klik sel untuk filter carton',
                planning: 'Planning vs Order per Color & Sec Size — klik sel untuk filter carton',
                packing: 'Actual vs Plan per Color & Sec Size — klik sel untuk filter carton'
            };
            $('#matrixSubtitle').text(subtitles[tab]);
            $('.legend-item').removeClass('active');
            $('#matrixTable td.matrix-cell').removeClass('matrix-dim');
        }

        function toggleMatrixChip(el) {
            const $el = $(el),
                wasActive = $el.hasClass('active');
            $('.legend-item').removeClass('active');
            $('#matrixTable td.matrix-cell').removeClass('matrix-dim');
            if (wasActive) return;
            $el.addClass('active');
            const classMap = {
                exact: 'cov-exact',
                short: 'cov-short',
                over: 'cov-over',
                none: 'cov-none',
                full: 'pack-full',
                progress: 'pack-progress',
                empty: 'pack-empty',
                'plan-exact': 'plan-exact',
                'plan-short': 'plan-short',
                'plan-over': 'plan-over',
                'plan-none': 'plan-none'
            };
            const targetClass = classMap[$el.data('cat')];
            $('#matrixTable td.matrix-cell').each(function() {
                if (!$(this).hasClass(targetClass)) $(this).addClass('matrix-dim');
            });
        }

        function onMatrixCellClick(td) {
            const $td = $(td),
                material = $td.data('material') || '',
                secsz = $td.data('secsz') || '',
                size = String($td.data('size'));
            if ($('#filterColorGlobal').data('combobox')) $('#filterColorGlobal').combobox('setValue', material);
            if ($('#filterSecszGlobal').data('combobox')) $('#filterSecszGlobal').combobox('setValue', secsz);
            if ($('#filterSizeGlobal').length && $('#filterSizeGlobal').data('combobox')) $('#filterSizeGlobal').combobox(
                'setValue', size);
            document.getElementById('packingCardsGrid')?.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    </script>

    <script>
        let packingCardsPage = 1,
            packingCardsRows = 25,
            packingCardsTotal = 0,
            packingCardsTotalCarton = 0,
            statusFilterGlobal = '';
        window.selectedRowsCache = window.selectedRowsCache || {};

        $(function() {
            initFilterColorGlobal();
            if (window.pageCfg.showSizeFilter) initFilterSizeGlobalCombobox();
            initFilterSecszGlobal();
            if (window.pageCfg.showPartFilter) initFilterPartGlobal();
            initSortFieldGlobalCombobox();
            initPageSizeGlobalCombobox();
        
            applyViewModeUiState(window.packingViewModeGlobal); 
            loadPackingCards();
        
            reloadBreakdownSummary();
            reloadCardsInfoGlobal();
            if (window.pageCfg.showShipmentPlan) {
                loadShipmentPlanCards();
                renderActiveSessionBadge();
            } else {
                loadShipDateCacheOnlyGlobal();
            }
            syncStickyBarGlobalPosition();
        });

        function goBack() {
            window.location.href = R.back;
        }

        window.pgLastAppliedViewMode = window.pgLastAppliedViewMode ?? null;
 
        function applyViewModeUiState(mode) {
            const prevMode = window.pgLastAppliedViewMode; // null di panggilan PERTAMA -- GARANSI transisi terdeteksi
            window.pgLastAppliedViewMode = mode;
            window.packingViewModeGlobal = mode;
            localStorage.setItem('packingViewModeGlobal', mode);
            $('#viewModeGridBtn, #viewModeListBtn, #viewModeCompactBtn').removeClass('active');
            const btnMap = { grid: '#viewModeGridBtn', list: '#viewModeListBtn', compact: '#viewModeCompactBtn' };
            $(btnMap[mode]).addClass('active');
        
            const COMPACT_PAGE_SIZE = 500;
        
            if (mode === 'compact' && prevMode !== 'compact') {
                window.pgPageSizeBeforeCompact = packingCardsRows;
                packingCardsRows = COMPACT_PAGE_SIZE;
                if ($('#pageSizeGlobal').data('combobox')) {
                    window.suppressPageSizeChangeReload = true;
                    $('#pageSizeGlobal').combobox('setValue', COMPACT_PAGE_SIZE);
                    window.suppressPageSizeChangeReload = false;
                }
                return true; // butuh reload
            }
            if (mode !== 'compact' && prevMode === 'compact' && window.pgPageSizeBeforeCompact != null) {
                packingCardsRows = window.pgPageSizeBeforeCompact;
                window.pgPageSizeBeforeCompact = null;
                if ($('#pageSizeGlobal').data('combobox')) {
                    window.suppressPageSizeChangeReload = true;
                    $('#pageSizeGlobal').combobox('setValue', packingCardsRows);
                    window.suppressPageSizeChangeReload = false;
                }
                return true;
            }
            return false;
        }
        
        function setViewModeGlobal(mode) {
            const needReload = applyViewModeUiState(mode);
            if (needReload) {
                packingCardsPage = 1;
                loadPackingCards();
            } else {
                renderPackingCards(window.lastPackingRows || []);
            }
        }
        
        // ============================================================
        // BARU -- bangun 1 tile Compact utk 1 grup carton (SAMA data source
        // dgn buildPackingCard()/buildPackingListRow(), lewat computePackingGroupData()).
        // ============================================================
        function buildPackingCompactTile(groupRows) {
            const d = computePackingGroupData(groupRows);
        
            const tooltipParts = [
                `Carton ${d.first.carton ?? '-'}`,
                d.subline,
                `${d.status.label} -- ${d.totalActual}/${d.totalPlan} pcs (${d.pct}%)`,
                d.first.nobar ? `Barcode: ${d.first.nobar}` : 'Belum ada barcode',
            ];
            if (d.anyReject) tooltipParts.push('REJECT');
            if (d.shipStampKey === 'shipped') tooltipParts.push('Sudah Shipped');
        
            // BARU -- FIX UTAMA: SAMA PERSIS kondisi dgn buildPackingCard()/
            // buildPackingListRow() -- konsisten di seluruh mode tampilan.
            const editButtonHtml = (!window.pageCfg.showEditButton || d.isSealed || d.anyInspecting || d.anyReturning) ?
                '' :
                `<i class="fas fa-pen pc-edit-btn" title="Edit" onclick="event.stopPropagation(); editCartonGlobal('${d.packpksAttr}')"></i>`;
        
            const badgesHtml = `
                <div class="pc-badges">
                    ${d.anyReject ? '<span class="pc-dot reject" title="Reject"></span>' : ''}
                    ${d.shipStampKey === 'shipped' ? '<span class="pc-dot shipped" title="Shipped"></span>' : ''}
                </div>
            `;
        
            const progressColor = d.pct >= 100 ? '#8bc63f' : '#f97316';
        
            let tileBg, tileText;
            
            if (d.anyReject) {
                tileBg = '#fee2e2'; tileText = '#7f1d1d';
            } else if (d.shipStampKey === 'shipped') {
                tileBg = '#dbeafe'; tileText = '#1e3a8a';
            } else if (d.anyReturning) {
                tileBg = '#f3e8ff'; tileText = '#5b21b6';
            } else if (d.anyInspecting) {
                tileBg = '#fef3c7'; tileText = '#92400e';
            } else if (d.isSealed) {
                tileBg = '#1e293b'; tileText = '#e2e8f0';
            } else if (d.totalActual <= 0) {
                tileBg = '#f1f5f9'; tileText = '#64748b';
            } else if (d.pct >= 100) {
                tileBg = '#f0fdf4'; tileText = '#166534';
            } else {
                tileBg = '#fff7ed'; tileText = '#9a3412';
            }
        
            return `
                <div class="packing-select-item packing-compact-tile pc-status-${d.status.key}"
                    style="background:${tileBg}; color:${tileText};"
                    data-packpks="${d.packpksAttr}" data-sealed="${d.isSealed?1:0}" data-haspart="${d.hasPart?1:0}"
                    title="${tooltipParts.join(' | ').replace(/"/g, '&quot;')}"
                    onclick="onPackingItemClick(event, this)">
                    ${editButtonHtml}
                    ${badgesHtml}
                    <div class="pc-carton" style="color:${tileText};">${d.first.carton ?? '-'}</div>
                    <div class="pc-qty" style="color:${tileText}; opacity:.8;">${d.totalActual}/${d.totalPlan} &middot; ${d.pct}%</div>
                    <div class="pc-progress"><span class="bar" style="width:${Math.min(100,d.pct)}%; background:${progressColor};"></span></div>
                </div>
            `;
        }

        function initSortFieldGlobalCombobox() {
            $('#sortFieldGlobal').combobox({
                data: [{
                    value: '',
                    text: 'Urutan Default'
                }, {
                    value: 'carton_asc',
                    text: 'No Carton (A-Z)'
                }, {
                    value: 'carton_desc',
                    text: 'No Carton (Z-A)'
                }, {
                    value: 'nobar_asc',
                    text: 'Barcode (A-Z)'
                }, {
                    value: 'nobar_desc',
                    text: 'Barcode (Z-A)'
                }],
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    if (window.inspectionActiveTab === 'history') {
                        loadHistoryCards();
                    } else {
                        reloadPackingGlobal();
                    }
                }
            });
        }

        function toggleSelectAllVisible() {
            const currentlySelected = (window.selectedPackpksGlobal || []).length;
            const rows = window.lastPackingRows || [];

            if (!rows.length) {
                showToast('warning', 'Tidak ada carton untuk dipilih.');
                return;
            }

            if (currentlySelected > 0 && window.pgSelectAllActive) {
                closeMenuGlobal();
                window.pgSelectAllActive = false;
                $('#btnSelectAllVisible').html('<i class="fas fa-check-double me-1"></i> Pilih Semua');
                return;
            }

            const normalizePart = (p) => (p === null || p === undefined || p === '' || Number(p) === 0) ? '' : String(p);

            // Kelompokkan per carton.
            const cartonGroups = {};
            const cartonOrder = [];
            rows.forEach(function (row) {
                const key = row.carton ?? '(tanpa carton)';
                if (!cartonGroups[key]) { cartonGroups[key] = []; cartonOrder.push(key); }
                cartonGroups[key].push(row);
            });

            // ============================================================
            // BARU -- FIX UTAMA: buang dulu carton Shipped (SELALU tidak
            // eligible, apa pun keadaannya), baru hitung "kombinasi status"
            // (segel + session/part) TERBANYAK di antara sisanya -- itu yang
            // jadi baseline, BUKAN carton pertama yang ditemukan.
            // ============================================================
            let skippedShipped = 0;
            const eligible = []; // [{ cartonKey, groupRows, segelState, partState }]

            cartonOrder.forEach(function (cartonKey) {
                const groupRows = cartonGroups[cartonKey];
                if (groupRows.some(r => r.ship_shipped === true)) {
                    skippedShipped++;
                    return;
                }
                const segelState = groupRows.some(r => Number(r.segel) === 1);
                const partState = normalizePart(groupRows.map(r => r.part).find(p =>
                    p !== null && p !== undefined && p !== '' && Number(p) !== 0
                ) ?? '');
                eligible.push({ cartonKey, groupRows, segelState, partState });
            });

            if (!eligible.length) {
                showToast('warning', 'Tidak ada carton yang bisa dipilih (semua sudah Shipped).');
                return;
            }

            // Hitung frekuensi tiap kombinasi (segel|part) -- kombinasi dengan
            // JUMLAH CARTON TERBANYAK yang menang jadi baseline.
            const freq = new Map();
            eligible.forEach(function (item) {
                const key = item.segelState + '|' + item.partState;
                freq.set(key, (freq.get(key) || 0) + 1);
            });
            let majorityKey = null, majorityCount = -1;
            freq.forEach(function (count, key) {
                if (count > majorityCount) { majorityCount = count; majorityKey = key; }
            });

            // Pilih semua carton yang match kombinasi mayoritas, sisanya dilewati.
            const eligiblePackpks = [];
            let skippedMinority = 0;
            eligible.forEach(function (item) {
                const key = item.segelState + '|' + item.partState;
                if (key !== majorityKey) {
                    skippedMinority++;
                    return;
                }
                item.groupRows.forEach(function (row) {
                    eligiblePackpks.push(row.packpk);
                    window.selectedRowsCache[row.packpk] = row;
                });
            });

            if (!eligiblePackpks.length) {
                showToast('warning', 'Tidak ada carton yang bisa dipilih.');
                return;
            }

            window.selectedPackpksGlobal = eligiblePackpks;
            window.pgSelectAllActive = true;
            $('#btnSelectAllVisible').html('<i class="fas fa-xmark me-1"></i> Batal Semua');

            $('.packing-select-item').each(function () {
                const packpksArr = String($(this).data('packpks') || '').split(',').map(Number).filter(Boolean);
                $(this).toggleClass('selected', packpksArr.some(pk => eligiblePackpks.includes(pk)));
            });

            updateSelectionGlobal();

            const cartonCount = new Set(eligiblePackpks.map(pk => window.selectedRowsCache[pk]?.carton)).size;
            let msg = `${cartonCount} carton dipilih (mayoritas).`;
            if (skippedShipped > 0) msg += ` ${skippedShipped} dilewati (sudah Shipped).`;
            if (skippedMinority > 0) msg += ` ${skippedMinority} dilewati (status Segel/Session beda dari mayoritas).`;
            showToast(skippedShipped || skippedMinority ? 'warning' : 'success', msg);
        }

        function initPageSizeGlobalCombobox() {
            $('#pageSizeGlobal').combobox({
                data: [
                    { value: 25, text: '25' },
                    { value: 50, text: '50' },
                    { value: 100, text: '100' },
                    { value: 200, text: '200' },
                    { value: 500, text: '500' }
                ],
                valueField: 'value',
                textField: 'text',
                value: 25,
                editable: false,
                panelHeight: 'auto',
                onChange: function (v) {
                    if (window.suppressPageSizeChangeReload) return; 
                    packingCardsRows = parseInt(v) || 25;
                    reloadPackingGlobal();
                }
            });
        }

        function initFilterColorGlobal() {
            let data = [{
                value: '',
                text: 'Semua Color'
            }];
            (window.colorListGlobal || []).forEach(c => data.push({
                value: c,
                text: c
            }));
            $('#filterColorGlobal').combobox({
                data,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    if (window.inspectionActiveTab === 'history') {
                        loadHistoryCards();
                    } else {
                        reloadPackingGlobal();
                    }
                }
            });
        }

        function initFilterSizeGlobalCombobox() {
            let data = [{
                value: '',
                text: 'Semua Size'
            }];
            Object.keys(window.activeSizesGlobal || {}).forEach(i => data.push({
                value: i,
                text: window.activeSizesGlobal[i]
            }));
            $('#filterSizeGlobal').combobox({
                data,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    if (window.inspectionActiveTab === 'history') {
                        loadHistoryCards();
                    } else {
                        reloadPackingGlobal();
                    }
                }
            });
        }

        function initFilterPartGlobal() {
            populatePartFilterOptions([]);
        }

        function initFilterSecszGlobal() {
            let data = [{
                value: '',
                text: 'Semua Sec Size'
            }];
            (window.secszListGlobal || []).forEach(s => data.push({
                value: s,
                text: s
            }));
            $('#filterSecszGlobal').combobox({
                data,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function() {
                    if (window.inspectionActiveTab === 'history') {
                        loadHistoryCards();
                    } else {
                        reloadPackingGlobal();
                    }
                }
            });
        }
        let packingGlobalSearchTimer = null;
        $('#searchPackingGlobal').on('keyup', function() {
            clearTimeout(packingGlobalSearchTimer);
            packingGlobalSearchTimer = setTimeout(reloadPackingGlobal, 300);
        });

        function setStatusFilterGlobal(status) {
            statusFilterGlobal = status;
            $('#statusFilterChipsGlobal .status-chip').removeClass('active');
            $(`#statusFilterChipsGlobal .status-chip[data-status="${status}"]`).addClass('active');
            reloadPackingGlobal();
        }

        function loadPackingCards() {
            $.get(R.listDetailGlobal, {
                po: PO,
                op: OP,
                poref: POREF,
                mif: MIF,
                search: $('#searchPackingGlobal').val(),
                size: window.pageCfg.showSizeFilter ? $('#filterSizeGlobal').combobox('getValue') : '',
                color: $('#filterColorGlobal').combobox('getValue'),
                secsz: $('#filterSecszGlobal').combobox('getValue'),
                part: window.pageCfg.showPartFilter ? $('#filterPartGlobal').combobox('getValue') : '',
                sort: $('#sortFieldGlobal').combobox('getValue'),
                status: statusFilterGlobal,
                page: packingCardsPage,
                rows: packingCardsRows
            }, function(data) {
                packingCardsTotal = data.total || 0;
                packingCardsTotalCarton = data.total_carton || 0;
                const counts = data.status_counts || {};
                if (window.pageCfg.showBaseChips) {
                    $('#chipCountAll').text(counts.all ?? 0);
                    $('#chipCountComplete').text(counts.complete ?? 0);
                    $('#chipCountSealed').text(counts.sealed ?? 0);
                }
                if (window.pageCfg.showPlanningChips) {
                    $('#chipCountPlanned').text(counts.planned ?? 0);
                    $('#chipCountPacking').text(counts.packing ?? 0);
                }
                if (window.pageCfg.showShipmentChips) {
                    $('#chipCountInspect').text(counts.inspect ?? 0);
                    $('#chipCountShipped').text(counts.shipped ?? 0);
                    $('#chipCountReturning').text(counts.returning ?? 0);
                }
                window.lastPackingRows = data.rows || [];
                renderPackingCards(data.rows || []);
            });
        }

        function reloadPackingGlobal() {
            packingCardsPage = 1;
            loadPackingCards();
        }

        function packingCardsGoPage(delta) {
            const maxPage = Math.max(1, Math.ceil(packingCardsTotal / packingCardsRows));
            const next = packingCardsPage + delta;
            if (next < 1 || next > maxPage) return;
            packingCardsPage = next;
            loadPackingCards();
        }

        function renderPackingCards(rows) {
            const grid = $('#packingCardsGrid'),
                listWrapper = $('#packingListTableWrapper'),
                listBody = $('#packingListBody'),
                compactWrapper = $('#packingCompactWrapper'),
                compactGrid = $('#packingCompactGrid'),
                empty = $('#packingCardsEmpty');
        
            grid.empty();
            listBody.empty();
            compactGrid.empty();
        
            if (!rows.length) {
                empty.removeClass('d-none');
                grid.addClass('d-none');
                listWrapper.addClass('d-none');
                compactWrapper.addClass('d-none');
                $('#packingCardsInfo').text('0 carton');
                $('#packingCardsPageLabel').text('Halaman 1 / 1');
                updateSelectionGlobal();
                return;
            }
            empty.addClass('d-none');
        
            const cartonGroups = {}, cartonOrder = [];
            rows.forEach(function (row) {
                const key = row.carton ?? '(tanpa carton)';
                if (!cartonGroups[key]) { cartonGroups[key] = []; cartonOrder.push(key); }
                cartonGroups[key].push(row);
            });
        
            grid.addClass('d-none');
            listWrapper.addClass('d-none');
            compactWrapper.addClass('d-none');
        
            if (window.packingViewModeGlobal === 'list') {
                listWrapper.removeClass('d-none');
                cartonOrder.forEach(k => listBody.append(buildPackingListRow(cartonGroups[k])));
            } else if (window.packingViewModeGlobal === 'compact') {
                compactWrapper.removeClass('d-none');
                cartonOrder.forEach(k => compactGrid.append(buildPackingCompactTile(cartonGroups[k])));
            } else {
                grid.removeClass('d-none');
                cartonOrder.forEach(k => grid.append(buildPackingCard(cartonGroups[k])));
            }
        
            const persisted = window.selectedPackpksGlobal || [];
            $('.packing-select-item').each(function () {
                const packpksArr = String($(this).data('packpks') || '').split(',').map(Number).filter(Boolean);
                $(this).toggleClass('selected', packpksArr.some(pk => persisted.includes(pk)));
            });
        
            const maxPage = Math.max(1, Math.ceil(packingCardsTotal / packingCardsRows));
            $('#packingCardsInfo').text(packingCardsTotalCarton + ' carton');
            $('#packingCardsPageLabel').text('Halaman ' + packingCardsPage + ' / ' + maxPage);
            $('#btnPackingCardsPrev').prop('disabled', packingCardsPage <= 1);
            $('#btnPackingCardsNext').prop('disabled', packingCardsPage >= maxPage);
            updateSelectionGlobal();
        }

        function getCartonStatusForRow(row) {
            if (Number(row.segel) === 1) return 'sealed';
            if (isRowComplete(row)) return 'complete';
            if (Number(row.pcs) > 0) return 'packing';
            return 'planned';
        }

        function getGroupStatus(groupRows) {
            const statuses = groupRows.map(getCartonStatusForRow);
            if (statuses.some(s => s === 'sealed')) return {
                key: 'sealed',
                label: 'Sealed'
            };
            if (statuses.every(s => s === 'complete')) return {
                key: 'complete',
                label: 'Complete'
            };
            if (statuses.some(s => s === 'packing' || s === 'complete')) return {
                key: 'packing',
                label: 'Packing'
            };
            return {
                key: 'planned',
                label: 'Planned'
            };
        }

        function isRowComplete(row) {
            const activeIdx = Object.keys(window.activeSizesGlobal || {});
            let hasAnyPlan = false;
            const semuaSama = activeIdx.every(function(i) {
                const plan = Number(row[`qtyp${i}`] || 0);
                if (plan <= 0) return true;
                hasAnyPlan = true;
                return Number(row[`qty${i}`] || 0) === plan;
            });
            return hasAnyPlan && semuaSama;
        }

        function getComboMarkerForPopk(popk) {
            const combo = (window.pgCombos || []).find(c => String(c.popk) === String(popk));
            return combo?.duplicateMarker || null;
        }

        function getComboLabel(row) {
            const marker = getComboMarkerForPopk(row.popk);
            return marker ? `${row.material??'-'} ${marker}` : (row.material ?? '-');
        }

        function getShipStampForGroup(groupRows) {
            const anyShipped = groupRows.some(r => r.ship_shipped === true),
                anyReturning = groupRows.some(r => r.ship_returning === true),
                anyInspect = groupRows.some(r => r.ship_inspect === true);
            if (anyShipped) return 'shipped';
            if (anyReturning) return 'returning';
            if (anyInspect) return 'inspect';
            return null;
        }

        function buildShipStamp(stampKey, dateStr, size) {
            if (!stampKey) return '';
            const sizeClass = (size === 'sm') ? ' ship-stamp-sm' : '';
            if (stampKey === 'shipped') {
                const tgl = formatStampDate(dateStr);
                return `<span class="ship-stamp ship-stamp-shipped${sizeClass}" title="Sudah Shipped"><span class="ship-stamp-text">Shipped</span>${tgl?`<span class="ship-stamp-date">${tgl}</span>`:''}</span>`;
            }
            if (stampKey === 'returning') {
                return `<span class="ship-stamp ship-stamp-returning${sizeClass}" title="Menunggu Diterima FG/Stuffing"><span class="ship-stamp-text">Menunggu</span></span>`;
            }
            if (stampKey === 'inspect') {
                return `<span class="ship-stamp ship-stamp-inspect${sizeClass}" title="Sedang Inspect"><span class="ship-stamp-text">Inspect</span></span>`;
            }
            return '';
        }

        function formatStampDate(value) {
            if (!value) return '';
            const datePart = String(value).split(' ')[0].split('T')[0];
            const parts = datePart.split('-');
            if (parts.length !== 3) return '';
            const [y, m, d] = parts;
            const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const mi = parseInt(m, 10) - 1;
            if (mi < 0 || mi > 11) return '';
            return `${parseInt(d,10)} ${bulan[mi]} ${y}`;
        }

        function buildInspecDocBadge(row) {
            if (!row || !row.no_inspec) return '';
            return `<div style="font-size:10.5px; color:#0369a1; background:#e0f2fe; border-radius:6px; padding:3px 8px; margin-top:6px; margin-bottom:6px; display:inline-flex; align-items:center; gap:4px;">
                <i class="fas fa-clipboard-check"></i> ${row.no_inspec}
            </div>`;
        }

        function computePackingGroupData(groupRows) {
            const activeIdx = Object.keys(window.activeSizesGlobal || {});
            const first = groupRows[0];
            const uniqueCombos = new Set(groupRows.map(r => `${r.material??'-'}||${r.secsz??''}`));
            const compositionLabel = uniqueCombos.size > 1 ? 'Mixed' : (groupRows.some(r => activeIdx.filter(i => Number(r[
                `qtyp${i}`] || 0) > 0).length > 1) ? 'Assorted' : 'Solid');
            const compositionClass = compositionLabel.toLowerCase();
            const status = getGroupStatus(groupRows);
            const partValue = groupRows.map(r => r.part).find(p =>
                p !== null && p !== undefined && p !== '' && Number(p) !== 0
            );
            const partBadgeHtml = (partValue) ?
                `<span class="badge-soft" style="background:#ede9fe;color:#6d28d9;border-color:#ddd6fe;">${String(partValue)==='10'?'Complete':'Session '+partValue}</span>` :
                '';
            const anyInspecting = groupRows.some(r => r.ship_inspect === true);
            const anyReturning = groupRows.some(r => r.ship_returning === true);
            const anyReject = groupRows.some(r => Number(r.reject) === 1);
            const canSeal = status.key === 'complete' && !anyInspecting && !anyReturning && !anyReject;
            const rejectBadgeHtml = anyReject ?
                `<span class="badge-soft" style="background:#fee2e2;color:#991b1b;border-color:#fecaca;"><i class="fas fa-times-circle me-1"></i>Reject</span>` :
                '';

            const shipStampKey = getShipStampForGroup(groupRows);
            const shippedRow = groupRows.find(r => r.ship_shipped === true);
            
            const shipExportpk = shippedRow?.exportpk ?? null;
            const matchedExportForShip = (window.shipmentPlanPartsCache || [])
                .find(p => String(p.exportpk) === String(shipExportpk));
            const shipDate = matchedExportForShip?.exdate ?? null;
            
            const shipStampHtml = buildShipStamp(shipStampKey, shipDate, 'lg');

            const inspecDocRow = groupRows.find(r => r.no_inspec);
            const inspecDocBadgeHtml = buildInspecDocBadge(inspecDocRow);
            let totalPlan = 0,
                totalActual = 0,
                sizeRows = '';
            groupRows.forEach(function(row) {
                const plannedSizes = activeIdx.filter(i => Number(row[`qtyp${i}`] || 0) > 0);
                const materialLabel = getComboLabel(row);
                plannedSizes.forEach(function(i) {
                    const plan = Number(row[`qtyp${i}`] || 0),
                        actual = Number(row[`qty${i}`] || 0);
                    totalPlan += plan;
                    totalActual += actual;
                    const sizePct = plan > 0 ? Math.round((actual / plan) * 100) : 0;
                    const miniColor = sizePct >= 100 ? '#8bc63f' : '#f97316';
                    const secszTag = row.secsz ? ` (${row.secsz})` : '';
                    sizeRows +=
                        `<div class="size-row"><span class="dot"></span><span class="name">${materialLabel}${secszTag} &middot; ${window.activeSizesGlobal[i]??i}</span><span class="mini-progress"><span class="bar" style="width:${Math.min(100,sizePct)}%; background:${miniColor};"></span></span><span class="frac">${actual}/${plan}</span></div>`;
                });
            });
            const pct = totalPlan > 0 ? Math.round((totalActual / totalPlan) * 100) : 0;
            const barColor = status.key === 'sealed' ? '#8bc63f' : (pct >= 100 ? '#8bc63f' : '#f97316');
            let subline;
            if (uniqueCombos.size === 1) {
                const secszLabel = first.secsz ? ` &middot; Sec Size ${first.secsz}` : '';
                subline = `${getComboLabel(first)}${secszLabel}`;
            } else {
                subline = `${uniqueCombos.size} kombinasi Color/Sec Size`;
            }
            const isSealed = status.key === 'sealed';
            const packpks = groupRows.map(r => r.packpk);
            const packpksAttr = packpks.join(',');
            const allSegel = groupRows.every(r => Number(r.segel) === 1);
            const hasPart = groupRows.some(r =>
                r.part !== null && r.part !== undefined && r.part !== '' && Number(r.part) !== 0
            );
            return {
                first,
                uniqueCombos,
                compositionLabel,
                compositionClass,
                status,
                totalPlan,
                totalActual,
                sizeRows,
                pct,
                barColor,
                subline,
                canSeal,
                isSealed,
                packpks,
                packpksAttr,
                allSegel,
                hasPart,
                partBadgeHtml,
                rejectBadgeHtml,
                shipStampHtml,
                inspecDocBadgeHtml,
                shipStampKey,
                shipDate,
                anyInspecting,
                anyReturning,
                anyReject
            };
        }

        function buildActionButtonHtml(d) {
            if (!window.pageCfg.showSealAction) return ''; // Inspection: kosong total
            if (d.anyReturning)
                return `<button class="btn btn-outline-secondary" disabled title="Carton sedang Menunggu Diterima dari Inspect, tidak dapat disegel/diedit dulu"><i class="fas fa-clock me-1"></i>Menunggu</button>`;
            if (d.anyReject)
                return `<button class="btn btn-outline-secondary" disabled title="Carton di-reject saat Inspect, perbaiki/rework dulu sebelum bisa disegel"><i class="fas fa-triangle-exclamation me-1"></i>Reject</button>`;
        
            // user tanpa akses Segel (mis. role packing) --
            // tombol disembunyikan TOTAL, BUKAN lagi ditampilkan versi disabled.
            if (!window.canManageSegel) return '';
        
            if (d.isSealed && d.hasPart)
                return `<button class="btn btn-outline-secondary" disabled title="Sudah masuk proses shipment, tidak dapat dibuka Segel-nya lagi"><i class="fas fa-lock me-1"></i>Sealed</button>`;
            if (d.isSealed)
                return `<button class="btn btn-outline-secondary" onclick="event.stopPropagation(); unsealCarton('${d.packpksAttr}')"><i class="fas fa-unlock me-1"></i>Unseal</button>`;
            const sealTitle = d.anyInspecting ? 'title="Sedang proses Inspect, tidak dapat disegel dulu"' : '';
            return `<button class="btn ${d.canSeal?'btn-dark':'btn-outline-secondary'}" ${d.canSeal?'':'disabled'} ${sealTitle} onclick="event.stopPropagation(); sealCarton('${d.packpksAttr}')">Seal</button>`;
        }

        function buildPackingCard(groupRows) {
            const d = computePackingGroupData(groupRows);
            const ribbonHtml = d.allSegel ? '<div class="ribbon-segel">SEGEL</div>' : '';
            const editButtonHtml = (!window.pageCfg.showEditButton || d.isSealed || d.anyInspecting || d.anyReturning) ?
                '' :
                `<i class="fas fa-pen icon-btn" title="Edit" onclick="event.stopPropagation(); editCartonGlobal('${d.packpksAttr}')"></i>`;
            const actionButtonHtml = buildActionButtonHtml(d);
            return `<div class="col-12 col-md-6 col-xl-4"><div class="packing-select-item packing-card" data-packpks="${d.packpksAttr}" data-sealed="${d.isSealed?1:0}" data-haspart="${d.hasPart?1:0}" onclick="onPackingItemClick(event, this)">
                ${ribbonHtml}
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                    <span class="ctn-code">${d.first.carton??'-'}</span>
                    <span class="badge-soft ${d.compositionClass}">${d.compositionLabel}</span>
                    <span class="badge-status ${d.status.key}">${d.status.label}</span>
                    ${d.partBadgeHtml}
                    ${d.rejectBadgeHtml}
                    ${editButtonHtml}
                </div>
                <div class="subline mb-1">${d.subline}</div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="progress-main flex-grow-1"><span class="bar" style="width:${Math.min(100,d.pct)}%; background:${d.barColor};"></span></div>
                    <div class="text-nowrap" style="font-size:12.5px;"><strong>${d.totalActual}</strong> / ${d.totalPlan} pcs <span class="text-muted">${d.pct}%</span></div>
                </div>
                <div class="packing-card-sizes">${d.sizeRows}</div>
                <div class="card-barcode"><span class="barcode-text"><i class="fas fa-barcode me-1"></i>${d.first.nobar?d.first.nobar:'<span class="text-muted">Belum ada barcode</span>'}</span>${d.shipStampHtml}</div>
                ${d.inspecDocBadgeHtml}
                ${actionButtonHtml ? `<div class="card-actions">${actionButtonHtml}</div>` : ''}
            </div></div>`;
        }

        function buildPackingListRow(groupRows) {
            const d = computePackingGroupData(groupRows);
            const editButtonHtml = (!window.pageCfg.showEditButton || d.isSealed || d.anyInspecting || d.anyReturning) ?
                '<span class="text-muted small">-</span>' :
                `<i class="fas fa-pen icon-btn" title="Edit" onclick="event.stopPropagation(); editCartonGlobal('${d.packpksAttr}')"></i>`;
            const actionButtonHtml = buildActionButtonHtml(d).replace('btn ', 'btn btn-sm ');
            const segelIcon = d.allSegel ? '<i class="fas fa-lock text-danger ms-1" title="Sudah Segel"></i>' : '';

            // FIX UTAMA: SEBELUMNYA ada 2 <td> barcode (satu versi lama tanpa
            // badge, satu versi baru dengan badge) -- SEKARANG cuma 1 <td>.
            return `<tr class="packing-select-item packing-list-row" data-packpks="${d.packpksAttr}" data-sealed="${d.isSealed?1:0}" data-haspart="${d.hasPart?1:0}" onclick="onPackingItemClick(event, this)">
                <td class="text-start"><strong>${d.first.carton??'-'}</strong>${segelIcon}<div><span class="badge-soft ${d.compositionClass}" style="font-size:10px;">${d.compositionLabel}</span>${d.partBadgeHtml}${d.rejectBadgeHtml}</div></td>
                <td class="text-start" style="font-size:12.5px; color:#475569;">
                    <div class="d-flex align-items-center gap-2">
                        <span>${d.first.nobar ? d.first.nobar : '<span class="text-muted">-</span>'}</span>
                        ${buildShipStamp(d.shipStampKey, d.shipDate, 'sm')}
                    </div>
                    ${d.inspecDocBadgeHtml}
                </td>
                <td class="text-start" style="font-size:12.5px;">${d.subline}</td>
                <td class="text-center"><span class="badge-status ${d.status.key}">${d.status.label}</span></td>
                <td style="min-width:160px;"><div class="d-flex align-items-center gap-2"><div class="progress-main flex-grow-1"><span class="bar" style="width:${Math.min(100,d.pct)}%; background:${d.barColor};"></span></div><div class="text-nowrap" style="font-size:11.5px; min-width:70px;"><strong>${d.totalActual}</strong>/${d.totalPlan} <span class="text-muted">(${d.pct}%)</span></div></div></td>
                <td class="text-center"><div class="d-flex justify-content-center gap-2">${editButtonHtml}${actionButtonHtml}</div></td>
            </tr>`;
        }

        let pgLastClickedItem = null; 

        function onPackingItemClick(e, itemEl) {
            if ($(e.target).closest('.icon-btn, .pc-edit-btn, .card-actions, button, a').length) return;

            // Shift+Klik range select (mode Compact) -- TIDAK diubah dari
            // sebelumnya.
            if (e.shiftKey && pgLastClickedItem && window.packingViewModeGlobal === 'compact') {
                const $allItems = $('.packing-select-item');
                const startIdx = $allItems.index(pgLastClickedItem);
                const endIdx = $allItems.index(itemEl);
                if (startIdx !== -1 && endIdx !== -1) {
                    const [from, to] = startIdx < endIdx ? [startIdx, endIdx] : [endIdx, startIdx];
                    const rangeItems = $allItems.slice(from, to + 1);

                    let skippedShipped = 0, skippedInconsistent = 0;
                    let baselineSegel = null, baselinePart = null;

                    const alreadySelected = (window.selectedRowsCache && Object.values(window.selectedRowsCache)[0]) || null;
                    if (alreadySelected) {
                        baselineSegel = Number(alreadySelected.segel) === 1;
                        baselinePart = alreadySelected.part ?? '';
                    }

                    rangeItems.each(function () {
                        const packpksArr = String($(this).data('packpks') || '').split(',').map(Number).filter(Boolean);
                        const groupRows = (window.lastPackingRows || []).filter(r => packpksArr.includes(r.packpk));
                        if (!groupRows.length) return;

                        if (groupRows.some(r => r.ship_shipped === true)) { skippedShipped++; return; }

                        const segelState = groupRows.some(r => Number(r.segel) === 1);
                        const partState = groupRows.map(r => r.part).find(p =>
                            p !== null && p !== undefined && p !== '' && Number(p) !== 0
                        ) ?? '';

                        if (baselineSegel === null) {
                            baselineSegel = segelState; baselinePart = partState;
                        } else if (segelState !== baselineSegel || String(partState) !== String(baselinePart)) {
                            skippedInconsistent++; return;
                        }

                        groupRows.forEach(row => { window.selectedRowsCache[row.packpk] = row; });
                        $(this).addClass('selected');
                    });

                    updateSelectionGlobal();
                    if (skippedShipped || skippedInconsistent) {
                        showToast('warning', `${skippedShipped} dilewati (Shipped), ${skippedInconsistent} dilewati (beda status Segel/Session).`);
                    }
                    return;
                }
            }

            pgLastClickedItem = itemEl;

            const $item = $(itemEl);
            const wasSelected = $item.hasClass('selected');
            const packpksArr = String($item.data('packpks') || '').split(',').map(Number).filter(Boolean);

            // Klik ulang item yang SUDAH terpilih -- deselect, SELALU boleh.
            if (wasSelected) {
                packpksArr.forEach(pk => delete window.selectedRowsCache[pk]);
                $item.removeClass('selected');
                updateSelectionGlobal();
                return;
            }

            // ============================================================
            // BARU -- FIX UTAMA: validasi SEBELUM menyentuh DOM/cache sama
            // sekali, dibandingkan terhadap SELEKSI YANG SUDAH ADA
            // (window.selectedRowsCache) -- bukan scrape ulang dari DOM
            // setelah class ditoggle. Ini penyebab bug lama: item yang
            // ditolak bisa "menyelinap balik" ke seleksi via mekanisme
            // persisted-selection di updateSelectionGlobal().
            // ============================================================
            const rowsForThisItem = (window.lastPackingRows || []).filter(r => packpksArr.includes(r.packpk));
            if (!rowsForThisItem.length) return;

            if (rowsForThisItem.some(r => r.ship_shipped === true)) {
                showToast('warning', 'Carton yang sudah Shipment tidak dapat dipilih/diproses lagi.');
                return; // TIDAK ADA perubahan ke seleksi yang sudah ada
            }

            const existingRows = Object.values(window.selectedRowsCache || {});
            if (existingRows.length) {
                const existingSegel = existingRows.some(r => Number(r.segel) === 1);
                const newSegel = rowsForThisItem.some(r => Number(r.segel) === 1);
                if (existingSegel !== newSegel) {
                    showToast('warning', 'Tidak bisa memilih carton dengan status Segel berbeda secara bersamaan.');
                    return; // seleksi lama TETAP UTUH, carton ini TIDAK ditambahkan sama sekali
                }

                if (window.pageCfg.showPartFilter || window.pageCfg.showKembalikanButton) {
                    const normalizePart = (p) => (p === null || p === undefined || p === '' || Number(p) === 0) ? '' : String(p);
                    const existingPart = normalizePart(existingRows[0].part);
                    const newPart = normalizePart(rowsForThisItem[0].part);
                    if (existingPart !== newPart) {
                        showToast('warning', 'Tidak bisa memilih carton dengan Session berbeda secara bersamaan.');
                        return; // seleksi lama TETAP UTUH
                    }
                }
            }

            // Lolos SEMUA validasi -- BARU SEKARANG toggle DOM + cache.
            rowsForThisItem.forEach(row => { window.selectedRowsCache[row.packpk] = row; });
            $item.addClass('selected');
            updateSelectionGlobal();
        }

        function updateSelectionGlobal() {
            const domSelectedPackpks = [];
            $('.packing-select-item.selected').each(function() {
                String($(this).data('packpks') || '').split(',').forEach(p => {
                    if (p !== '') domSelectedPackpks.push(Number(p));
                });
            });
            const stillPersistedPackpks = (window.selectedPackpksGlobal || []).filter(pk => window.selectedRowsCache[pk] &&
                !domSelectedPackpks.includes(pk));
            const packpks = [...new Set([...domSelectedPackpks, ...stillPersistedPackpks])];
            window.selectedPackpksGlobal = packpks;
        
            if (packpks.length === 0 && window.pgSelectAllActive) {
                window.pgSelectAllActive = false;
                $('#btnSelectAllVisible').html('<i class="fas fa-check-double me-1"></i> Pilih Semua');
            }
        
            if (packpks.length === 0) {
                $('#selectedCountGlobal').text('0');
                $('#stickTopBarGlobal').hide();
                return;
            }
            const selectedRows = packpks.map(pk => window.selectedRowsCache[pk]).filter(Boolean);
            function abortSelection(message) {
                showToast('warning', message);
                $('.packing-select-item').removeClass('selected');
                window.selectedPackpksGlobal = [];
                window.selectedRowsCache = {};
                $('#selectedCountGlobal').text('0');
                $('#stickTopBarGlobal').hide();
                // BARU -- FIX UTAMA: abortSelection() juga mengosongkan seleksi total,
                // sinkronkan tombol di sini juga.
                if (window.pgSelectAllActive) {
                    window.pgSelectAllActive = false;
                    $('#btnSelectAllVisible').html('<i class="fas fa-check-double me-1"></i> Pilih Semua');
                }
            }
            if (selectedRows.some(r => r.ship_shipped === true)) {
                abortSelection('Carton yang sudah Shipment tidak dapat dipilih/diproses lagi.');
                return;
            }
            if (window.pageCfg.showPartFilter || window.pageCfg.showKembalikanButton) {
                const normalizePart = (p) => {
                    if (p === null || p === undefined || p === '' || Number(p) === 0) return '';
                    return String(p);
                };
                const uniqueParts = new Set(selectedRows.map(r => normalizePart(r.part)));
                if (uniqueParts.size > 1) {
                    abortSelection('Tidak bisa memilih carton dengan Session berbeda secara bersamaan.');
                    return;
                }
            }
            const uniqueSegel = new Set(selectedRows.map(r => Number(r.segel) === 1));
            if (uniqueSegel.size > 1) {
                abortSelection('Tidak bisa memilih carton dengan status Segel berbeda secara bersamaan.');
                return;
            }
            const uniqueCartonCount = new Set(selectedRows.map(r => r.carton)).size;
            $('#selectedCountGlobal').text(uniqueCartonCount);
            $('#stickTopBarGlobal').show();

            const hasSegel = selectedRows.some(r => Number(r.segel) === 1);
            const hasPart = selectedRows.some(r =>
                r.part !== null && r.part !== undefined && r.part !== '' && Number(r.part) !== 0
            );
            const anyInspecting = selectedRows.some(r => r.ship_inspect === true);
            const anyReturning = selectedRows.some(r => r.ship_returning === true);
            const anyReject = selectedRows.some(r => Number(r.reject) === 1);

            if ((anyInspecting || anyReturning) && !window.pageCfg.showKembalikanButton && !window.pageCfg.showShipmentActions) {
                $('#btnBukaSegelGlobal, #btnBulkSegelCtnGlobal, #btnProsesInspectGlobal, #btnProsesShipmentGlobal, ' +
                '#btnTerimaCartonGlobal, #btnBulkActualCtnGlobal, #btnBulkDeleteActualCtnGlobal, ' +
                '#btnBulkCopyGlobal, #btnBulkDeleteGlobal, #btnBuatDokumenInspectGlobal, #btnKembalikanStuffingGlobal')
                .addClass('d-none');
                return;
            }

            if (window.pageCfg.showKembalikanButton) {
                const allHaveInspecDoc = selectedRows.length > 0 && selectedRows.every(r => r.has_inspec_doc === true);
                $('#btnBuatDokumenInspectGlobal').toggleClass('d-none', allHaveInspecDoc);
                $('#btnKembalikanStuffingGlobal').toggleClass('d-none', !allHaveInspecDoc);
            }

            const allComplete = selectedRows.length > 0 && selectedRows.every(isRowComplete);
            $('#btnBukaSegelGlobal').toggleClass('d-none', !(hasSegel && !anyReturning));
            $('#btnBulkSegelCtnGlobal').toggleClass('d-none', !(allComplete && !hasSegel && !anyInspecting && !
                anyReturning && !anyReject));

            if (window.pageCfg.showShipmentActions) {
                const eligibleForShipFlow = hasSegel && !anyInspecting && hasPart;
                $('#btnProsesInspectGlobal').toggleClass('d-none', !eligibleForShipFlow);
            
                const selectedExportpkValue = selectedRows.length
                    ? (selectedRows[0].exportpk !== null && selectedRows[0].exportpk !== undefined ? String(selectedRows[0].exportpk) : '') // FIX -- exportpk, bukan part
                    : '';
                const matchedSessionPart = (window.shipmentPlanPartsCache || [])
                    .find(p => String(p.exportpk) === selectedExportpkValue);
                const sessionIsStarted = !!(matchedSessionPart && matchedSessionPart.startship && !matchedSessionPart.endship);
            
                $('#btnProsesShipmentGlobal').toggleClass('d-none', !(eligibleForShipFlow && sessionIsStarted));
            
                const allReturning = selectedRows.length > 0 && selectedRows.every(r => r.ship_returning === true);
                $('#btnTerimaCartonGlobal').toggleClass('d-none', !allReturning);
            }

            if (window.pageCfg.showCtnManagement) {
                const blockCtnManagement = hasSegel || anyInspecting || anyReturning;
            
                $('#btnBulkActualCtnGlobal, #btnBulkDeleteActualCtnGlobal, #btnBulkCopyGlobal')
                    .toggleClass('d-none', blockCtnManagement);
            
                // Delete CTN TAMBAHAN disembunyikan kalau carton
                // sudah punya session/part (hasPart), TIDAK PEDULI status segel/
                // inspect-nya apa.
                $('#btnBulkDeleteGlobal').toggleClass('d-none', blockCtnManagement || hasPart);
            }
        }

        function closeMenuGlobal() {
            $('.packing-select-item').removeClass('selected');
            window.selectedPackpksGlobal = [];
            window.selectedRowsCache = {};
            updateSelectionGlobal();
        }

        function sealCarton(csv) {
            openSegelModalGlobal(1, csv.split(',').map(Number));
        }

        function unsealCarton(csv) {
            openSegelModalGlobal(0, csv.split(',').map(Number));
        }

        function bulkSegelCtnGlobal() {
            openSegelModalGlobal(1);
        }

        function bulkBukaSegelGlobal() {
            openSegelModalGlobal(0);
        }

        function bulkActualCtnGlobal() {
            const packpks = window.selectedPackpksGlobal || [];
            if (!packpks.length) {
                showToast('warning', 'Pilih minimal satu carton.');
                return;
            }
            $('#bulkSizeSelectGlobal').val('');
            $('#bulkActualGlobalWarning').addClass('d-none');
            renderBulkActualPreviewGlobal('');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('bulkActualCtnGlobalModal')).show();
        }

        function markCartonPacked(csv) {
            $.ajax({
                url: R.updateCtn,
                method: 'POST',
                data: {
                    popk: '',
                    size: '',
                    packpk: csv
                },
                success: function(res) {
                    showToast(res.icon, res.title);
                    loadPackingCards();
                    reloadBreakdownSummary();
                },
                error: function(xhr) {
                    const res = xhr.responseJSON || {
                        icon: 'error',
                        title: 'Terjadi kesalahan.'
                    };
                    showToast(res.icon, res.title);
                }
            });
        }

        function syncStickyBarGlobalPosition() {
            const bar = document.getElementById('stickTopBarGlobal');
            const navbar = document.querySelector('#navbarMain, nav.navbar, header.navbar, .app-navbar');
            let top = 0;
            if (navbar) top = Math.max(0, navbar.getBoundingClientRect().bottom);
        
            if (bar) {
                bar.style.top = top + 'px';
            }
        
            // BARU -- FIX UTAMA: offset utk card Shipment Plan yang di-sticky-kan
            // (.shipment-plan-card.is-active) -- ikut turun kalau sticky bar carton
            // sedang tampil (supaya tidak numpuk), ikut naik kalau sticky bar
            // disembunyikan.
            let shipmentCardTop = top + 12; // +12px jarak dari navbar
            if (bar && bar.style.display !== 'none' && getComputedStyle(bar).display !== 'none') {
                shipmentCardTop = top + bar.offsetHeight + 12;
            }
            document.documentElement.style.setProperty('--shipment-plan-sticky-top', shipmentCardTop + 'px');
        }

        function loadShipDateCacheOnlyGlobal() {
            if (!R.partSummaryGlobal) return;
            $.get(R.partSummaryGlobal, { po: PO, op: OP, poref: POREF, mif: MIF }, function (data) {
                window.shipmentPlanPartsCache = data.parts || [];
                if (window.lastPackingRows && window.lastPackingRows.length) {
                    renderPackingCards(window.lastPackingRows);
                }
            });
        }
        
        window.addEventListener('scroll', syncStickyBarGlobalPosition, { passive: true });
        window.addEventListener('resize', syncStickyBarGlobalPosition);
    </script>

    @if ($cfg['showScanNobar'])
        <script>
            (function() {
                const input = document.getElementById('scanNobarInputGlobal'),
                    feedback = document.getElementById('scanNobarFeedbackGlobal');
                if (!input) return;
                let busy = false;

                function focusScan() {
                    try {
                        input.focus({
                            preventScroll: true
                        });
                    } catch (e) {
                        input.focus();
                    }
                }
                focusScan();
                let skipRefocus = false;
                document.addEventListener('mousedown', function(e) {
                    const t = e.target;
                    if (t && t.closest && t.closest(
                            'a, button, input, textarea, select, label, [onclick], [role="button"]')) {
                        skipRefocus = true;
                        setTimeout(() => {
                            skipRefocus = false;
                        }, 500);
                    }
                }, true);
                input.addEventListener('blur', function() {
                    setTimeout(function() {
                        if (skipRefocus) return;
                        const tag = document.activeElement ? document.activeElement.tagName : '';
                        if (!['INPUT', 'TEXTAREA', 'SELECT', 'A', 'BUTTON'].includes(tag)) focusScan();
                    }, 150);
                });

                function showScanFeedback(ok, msgHtml) {
                    feedback.innerHTML = msgHtml;
                    feedback.style.color = ok ? '#15803d' : '#DC143C';
                }
                input.addEventListener('keydown', function(e) {
                    if (e.key !== 'Enter') return;
                    e.preventDefault();
                    const nobar = input.value.trim();
                    input.value = '';
                    if (nobar === '' || busy) return;
                    busy = true;
                    showScanFeedback(true, `Memproses ${nobar} ...`);
                    $.ajax({
                        url: R.scanNobar,
                        method: 'POST',
                        data: {
                            po: PO,
                            op: OP,
                            poref: POREF,
                            mif: MIF,
                            nobar
                        },
                        success: function(res) {
                            showScanFeedback(true, res.title);
                            showToast(res.icon, res.title);
                            loadPackingCards();
                            reloadBreakdownSummary();
                            if (window.pageCfg.showShipmentPlan) {
                                loadShipmentPlanCards(); 
                            }
                        },
                        error: function(xhr) {
                            const res = xhr.responseJSON || {
                                icon: 'error',
                                title: 'Terjadi kesalahan.'
                            };
                            showScanFeedback(false, res.title);
                            showToast(res.icon, res.title);
                        },
                        complete: function() {
                            busy = false;
                            focusScan();
                        }
                    });
                });
            })();
        </script>
    @endif

    @if ($cfg['showShipmentPlan'] || $cfg['showShipmentActions'])
        <div id="activeShipmentFloatingCard" class="d-none"></div>

        <div class="modal fade" id="shipmentPlanDetailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-1">
                        <h5 class="fw-bold text-dark mb-0" style="font-size:15px;">
                            <i class="fas fa-file-export me-2 text-secondary"></i>Detail Shipment Plan
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-2" id="shipmentPlanDetailBody">
                        <div class="text-center text-muted py-5">Memuat...</div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function getActiveSessionPart() {
                return localStorage.getItem(SESSION_KEY) || null;
            }

            function setActiveSessionPart(part) {
                if (part === null) localStorage.removeItem(SESSION_KEY);
                else localStorage.setItem(SESSION_KEY, String(part));
                renderActiveSessionBadge();
            }

            function renderActiveSessionBadge() {
                const active = getActiveSessionPart();
                if (!active) {
                    $('#activeSessionBadge').html('');
                    return;
                }
                const parts = window.shipmentPlanPartsCache || [];
                const matchedPart = parts.find(p => String(p.exportpk) === String(active));
                const label = matchedPart ? sessionLabel(matchedPart) : `Session (exportpk ${active})`; // FIX -- sessionLabel(matchedPart)
                $('#activeSessionBadge').html(
                    `<i class="fas fa-circle-play text-primary me-1"></i>Sedang stuffing: <strong>${label}</strong>`
                );
            }

            function sessionLabel(p) {
                return p.part_complete ? 'Complete' : `Session ${p.session_no}`;
            }

            function populatePartFilterOptions(parts) {
                const $el = $('#filterPartGlobal');
                if (!$el.length) return;
            
                const currentValue = $el.data('combobox') ? $el.combobox('getValue') : '';
                const data = [{ value: '', text: 'Semua Session' }];
                parts.forEach(function (p) {
                    data.push({
                        value: String(p.exportpk),
                        text: `${sessionLabel(p)} (PEB ${p.pebno ?? '-'})` // FIX -- sessionLabel(p), bukan sessionLabel(p.session_no)
                    });
                });
            
                $el.combobox({
                    data: data,
                    valueField: 'value',
                    textField: 'text',
                    value: data.some(d => d.value === currentValue) ? currentValue : '',
                    editable: false,
                    panelHeight: 'auto',
                    onChange: function () {
                        if (window.inspectionActiveTab === 'history') {
                            loadHistoryCards();
                        } else {
                            reloadPackingGlobal();
                        }
                    }
                });
            }

            function loadShipmentPlanCards() {
                $.get(R.partSummaryGlobal, { po: PO, op: OP, poref: POREF, mif: MIF }, function (data) {
                    const parts = data.parts || [], wrap = $('#shipmentPlanCards');
                    wrap.empty();

                    window.shipmentPlanPartsCache = parts;
                    populatePartFilterOptions(parts);
                    renderActiveSessionBadge();

                    if (window.lastPackingRows && window.lastPackingRows.length) {
                        renderPackingCards(window.lastPackingRows);
                    }

                    if (data.error) {
                        wrap.html(`<div class="text-danger" style="font-size:12.5px;"><i class="fas fa-triangle-exclamation me-1"></i>${data.error}</div>`);
                        return;
                    }
                    if (!parts.length) {
                        wrap.html('<div class="text-muted" style="font-size:12.5px;">Belum ada rencana Export dari sistem EXIM untuk carton PO/OP ini.</div>');
                        return;
                    }

                    const anyStarted = parts.some(p => !!p.startship && !p.endship);
                    const activePart = getActiveSessionPart();
                    if (activePart !== null) {
                        const stillValid = parts.some(p => String(p.exportpk) === String(activePart) && !!p.startship && !p.endship);
                        if (!stillValid) setActiveSessionPart(null);
                    }

                    const notDoneNotStarted = parts.filter(p => !p.endship && !p.startship);
                    const nextEligibleExportpk = notDoneNotStarted.length
                        ? notDoneNotStarted.slice().sort((a, b) => a.session_no - b.session_no)[0].exportpk
                        : null;
                    const nextEligibleSessionNo = notDoneNotStarted.length
                        ? notDoneNotStarted.slice().sort((a, b) => a.session_no - b.session_no)[0].session_no
                        : null;

                    parts.forEach(function (p) {
                        const pct = p.total > 0 ? Math.round((p.shipped / p.total) * 100) : 0,
                            isDone = !!p.endship,
                            isStarted = !!p.startship && !p.endship,
                            barColor = isDone ? '#8bc63f' : '#f97316';
                        const exdateLabel = formatStampDate(p.exdate) || '-';
                    
                        const containerListHtml = (p.containers || []).map(function (c) {
                            const contStarted = !!c.start_ship;
                            const contEnded = !!c.segel;
                            let statusBadge;
                            if (contEnded) {
                                statusBadge = `<span class="badge" style="background:#8bc63f; font-size:9px;">Selesai</span>`;
                            } else if (contStarted) {
                                statusBadge = `<span class="badge" style="background:#f97316; font-size:9px;">Berjalan</span>`;
                            } else if (!p.actcontdate) {
                                statusBadge = `<span class="badge bg-secondary" style="font-size:9px;" title="Menunggu Actual Container Date dari EXIM">
                                    <i class="fas fa-lock" style="font-size:8px;"></i> Menunggu
                                </span>`;
                            } else {
                                statusBadge = `<button class="btn btn-outline-dark btn-sm py-0 px-2" style="font-size:10.5px;" onclick="startStuffingSession('${p.exportpk}', ${c.contpk})">Mulai</button>`;
                            }
                            return `
                                <div style="font-size:10.5px; color:#64748b; display:flex; align-items:center; justify-content:space-between; gap:6px; margin-bottom:3px;">
                                    <span><i class="fas fa-truck-fast me-1"></i><strong>${c.contno ?? '-'}</strong> &middot; ${c.typenm ?? c.type ?? '-'} &middot; ${c.qty_ctn} ctn</span>
                                    ${statusBadge}
                                </div>
                            `;
                        }).join('');
                    
                        let topActionHtml = '';
                        if (isDone) {
                            topActionHtml = `<button class="btn btn-outline-success btn-sm" disabled><i class="fas fa-check me-1"></i>Selesai</button>`;
                        } else if (isStarted) {
                            topActionHtml = `<button class="btn btn-dark btn-sm" onclick="focusSessionPart('${p.exportpk}')">Lanjut</button>`;
                        }
                    
                        wrap.append(`
                            <div class="shipment-plan-card ${isStarted ? 'is-active' : ''} ${isDone ? 'is-done' : ''}" style="width:280px;">
                                <div class="shipment-plan-title d-flex align-items-center justify-content-between">
                                    <span><i class="fas fa-file-export me-1 text-muted"></i>PEB ${p.pebno ?? '-'}</span>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge" style="background:#1e293b; font-size:10.5px;">${sessionLabel(p)}</span>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-secondary" style="font-size:12px;"
                                            title="Lihat Detail Shipment Plan" onclick="openShipmentPlanDetailModal(${p.exportpk})">
                                            <i class="fas fa-circle-info"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="shipment-plan-shipdate">
                                    <i class="fas fa-calendar-day me-1"></i>Export: <strong>${exdateLabel}</strong>
                                </div>
                                <div style="margin-bottom:8px;">${containerListHtml}</div>
                                ${p.remark ? `<div class="text-muted" style="font-size:10.5px; font-style:italic; margin-bottom:8px;"><i class="fas fa-note-sticky me-1"></i>${p.remark}</div>` : ''}
                                <div class="shipment-plan-progress-bar"><span class="bar" style="width:${pct}%;background:${barColor};"></span></div>
                                <div class="shipment-plan-count">${p.shipped} / ${p.total} carton masuk (${pct}%)</div>
                                ${topActionHtml ? `<div class="shipment-plan-actions">${topActionHtml}</div>` : ''}
                            </div>
                        `);
                    });
                    renderActiveShipmentFloatingCard(parts);
                    syncStickyBarGlobalPosition();
                });
            }

            function renderActiveShipmentFloatingCard(parts) {
                const $float = $('#activeShipmentFloatingCard');
            
                if (!window.isStuffingUser) {
                    $float.addClass('d-none').empty();
                    return;
                }
            
                const activeP = parts.find(p => !!p.startship && !p.endship);
            
                if (!activeP) {
                    $float.addClass('d-none').empty();
                    return;
                }
            
                const pct = activeP.total > 0 ? Math.round((activeP.shipped / activeP.total) * 100) : 0;
                const barColor = '#f97316';
            
                $float.html(`
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span style="font-size:11px; font-weight:700; color:#1e293b;">
                            <i class="fas fa-circle-play text-primary me-1"></i>Sedang Berjalan
                        </span>
                        <span class="badge" style="background:#1e293b; font-size:10px;">${sessionLabel(activeP)}</span>
                    </div>
                    <div style="font-size:12.5px; color:#475569; margin-bottom:8px;">
                        <i class="fas fa-file-export me-1"></i>PEB ${activeP.pebno ?? '-'}
                    </div>
                    <div class="shipment-plan-progress-bar mb-1"><span class="bar" style="width:${pct}%;background:${barColor};"></span></div>
                    <div style="font-size:11.5px; color:#64748b; margin-bottom:10px;">
                        ${activeP.shipped} / ${activeP.total} carton masuk (${pct}%)
                    </div>
                    <button class="btn btn-dark btn-sm w-100" onclick="focusSessionPart('${activeP.exportpk}')">Lanjut</button>
                `);
                $float.removeClass('d-none');
            }

            function openShipmentPlanDetailModal(exportpk) {
                $('#shipmentPlanDetailBody').html('<div class="text-center text-muted py-5">Memuat...</div>');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('shipmentPlanDetailModal')).show();
            
                $.get(R.shipmentPlanDetailGlobal, { exportpk: exportpk }, function (data) {
                    renderShipmentPlanDetail(data);
                }).fail(function (xhr) {
                    const msg = xhr.responseJSON?.error || 'Gagal memuat detail shipment plan.';
                    $('#shipmentPlanDetailBody').html(
                        `<div class="text-center text-danger py-5"><i class="fas fa-triangle-exclamation me-1"></i>${msg}</div>`
                    );
                });
            }
            
            let spdContainersCache = [];   // containers[] dari response terakhir
            let spdActivePoOpFilter = null; // { POno, OP } atau null (tampil semua)
            
            function renderShipmentPlanDetail(data) {
                const e = data.export || {};
                const poOpList = data.po_op_list || [];
                const containers = data.containers || [];
            
                spdContainersCache = containers;
                spdActivePoOpFilter = null;
            
                // BARU -- FIX UTAMA: chip sekarang KLIK-ABLE, gabung Factory + MIF,
                // dan tag data-poop supaya bisa dipakai filter visualisasi.
                const poOpChipsHtml = poOpList.length
                    ? poOpList.map((p, idx) => `
                        <span class="spd-poop-chip" id="spdChip_${idx}"
                            onclick="toggleSpdPoOpFilter('${(p.POno ?? '').replace(/'/g, "\\'")}', '${(p.OP ?? '').replace(/'/g, "\\'")}', ${idx})">
                            <span>${p.POno ?? '-'} &middot; ${p.OP ?? '-'}</span>
                            ${p.mif ? `<span class="spd-mif-tag">MIF ${p.mif}</span>` : ''}
                        </span>
                    `).join('')
                    : '<span class="text-muted" style="font-size:12px;">Tidak ada data PO/OP.</span>';
            
                const containersTableHtml = containers.length
                    ? containers.map(c => {
                        const isSegel = !!c.segel; // GANTI nama variabel -- lebih jelas: ini status SEGEL, bukan "berakhir kirim"
                        const contStarted = !!c.start_ship;
                        let statusHtml;
                        if (isSegel) statusHtml = `<span class="badge" style="background:#8bc63f;">Selesai</span>`;
                        else if (contStarted) statusHtml = `<span class="badge" style="background:#f97316;">Berjalan</span>`;
                        else statusHtml = `<span class="badge bg-secondary">Belum Mulai</span>`;
                        return `
                            <tr>
                                <td>${c.contno ?? '-'}</td>
                                <td>${c.typenm ?? c.type ?? '-'}</td>
                                <td class="text-center">${c.qty_ctn}</td>
                                <td class="text-center">${statusHtml}</td>
                                <td style="font-size:11px;">${formatStampDate(c.start_ship) || '-'}</td>
                                <td style="font-size:11px;">${formatStampDate(c.segel) || '-'}</td>
                            </tr>
                        `;
                    }).join('')
                    : `<tr><td colspan="6" class="text-center text-muted py-3">Belum ada container.</td></tr>`;
            
                $('#shipmentPlanDetailBody').html(`
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div class="text-secondary" style="font-size:11px;">PEB No</div>
                            <div class="fw-semibold" style="font-size:13px;">${e.pebno ?? '-'}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary" style="font-size:11px;">Buyer</div>
                            <div class="fw-semibold" style="font-size:13px;">${e.buyer ?? '-'}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary" style="font-size:11px;">Export Date</div>
                            <div class="fw-semibold" style="font-size:13px;">${formatStampDate(e.exdate) || '-'}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary" style="font-size:11px;">Actual Container Date</div>
                            <div class="fw-semibold" style="font-size:13px;">${formatStampDate(e.actcontdate) || '-'}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary" style="font-size:11px;">Vessel</div>
                            <div class="fw-semibold" style="font-size:13px;">${e.vessname ?? '-'}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary" style="font-size:11px;">ETD / ETA</div>
                            <div class="fw-semibold" style="font-size:13px;">${formatStampDate(e.etdvess) || '-'} &rarr; ${formatStampDate(e.etavess) || '-'}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary" style="font-size:11px;">Total Qty</div>
                            <div class="fw-semibold" style="font-size:13px;">${Number(e.totqty || 0).toLocaleString()}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-secondary" style="font-size:11px;">Total Carton</div>
                            <div class="fw-semibold" style="font-size:13px;">${Number(e.totctn || 0).toLocaleString()}</div>
                        </div>
                        ${e.remark ? `
                        <div class="col-12">
                            <div class="text-secondary" style="font-size:11px;">Remark</div>
                            <div style="font-size:12.5px; font-style:italic;">${e.remark}</div>
                        </div>` : ''}
                    </div>
            
                    <hr class="my-3">
            
                    <div class="text-secondary mb-2" style="font-size:12px;">PO / OP dalam shipment plan ini:</div>
                    <div class="mb-3" id="spdPoOpChipsWrap">${poOpChipsHtml}</div>
            
                    <div class="text-secondary mb-2" style="font-size:12px;">Container:</div>
                    <div class="table-responsive mb-2">
                        <table class="table table-sm table-bordered align-middle" style="font-size:12.5px;">
                            <thead class="table-light">
                                <tr>
                                    <th>No Container</th>
                                    <th>Tipe</th>
                                    <th class="text-center">Qty Ctn</th>
                                    <th class="text-center">Status</th>
                                    <th>Mulai</th>
                                    <th>Selesai</th>
                                </tr>
                            </thead>
                            <tbody>${containersTableHtml}</tbody>
                        </table>
                    </div>
            
                    <hr class="my-3">
            
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-secondary" style="font-size:12px;">Visualisasi Carton:</div>
                        <select id="spdContainerSelect" class="form-select form-select-sm" style="width:220px;"
                            onchange="onSpdContainerChange()"></select>
                    </div>
                    <div id="spdContainerBox" class="spd-container-box"></div>
                    <div class="text-muted mt-2" style="font-size:11px;">
                        <i class="fas fa-circle-info me-1"></i>Klik chip PO/OP di atas untuk menyorot carton milik kombinasi itu saja.
                    </div>
                `);
            
                // Populate dropdown container + render box container pertama.
                const $sel = $('#spdContainerSelect');
                $sel.empty();
                containers.forEach((c, idx) => {
                    $sel.append(`<option value="${idx}">${c.contno ?? ('Container ' + (idx + 1))} (${c.qty_ctn} ctn)</option>`);
                });
                onSpdContainerChange();
            }

            function naturalSortCartons(cartons) {
                return [...cartons].sort((a, b) =>
                    String(a.carton ?? '').localeCompare(String(b.carton ?? ''), undefined, { numeric: true, sensitivity: 'base' })
                );
            }

            function renderSpdContainerBox() {
                const idx = parseInt($('#spdContainerSelect').val());
                const container = spdContainersCache[idx];
                const $box = $('#spdContainerBox');
                $box.empty();
            
                if (!container || !container.cartons || !container.cartons.length) {
                    $box.html('<div class="spd-container-empty">Tidak ada carton di container ini.</div>');
                    return;
                }
            
                // BARU -- FIX UTAMA: natural sort di sisi klien juga, jaga-jaga.
                const sortedCartons = naturalSortCartons(container.cartons);
            
                sortedCartons.forEach((c, i) => {
                    const matchesFilter = !spdActivePoOpFilter
                        || (c.POno === spdActivePoOpFilter.POno && c.OP === spdActivePoOpFilter.OP);
                    const dimmedClass = matchesFilter ? '' : ' dimmed';
                    // BARU -- FIX UTAMA: kelas 'shipped' -- biru kalau carton ini SUDAH
                    // masuk (ship.status >= 6).
                    const shippedClass = c.shipped ? ' shipped' : '';
                    const cartonLabel = c.carton ?? '-';
                    const statusText = c.shipped ? ' | Sudah Masuk' : '';
                    const title = `Carton ${cartonLabel}${c.POno ? ' | ' + c.POno + ' &middot; ' + c.OP : ''}${statusText}`;
                    $box.append(
                        `<div class="spd-carton-box${dimmedClass}${shippedClass}" style="animation-delay:${Math.min(i * 8, 600)}ms;" title="${title.replace(/"/g,'')}">${cartonLabel}</div>`
                    );
                });
            }
            
            // BARU -- dipanggil saat dropdown container berubah.
            function onSpdContainerChange() {
                renderSpdContainerBox();
            }
            
            // BARU -- toggle filter PO/OP: klik chip yang sama lagi -> lepas filter
            // (tampilkan semua carton di container).
            function toggleSpdPoOpFilter(pono, op, idx) {
                const isSame = spdActivePoOpFilter && spdActivePoOpFilter.POno === pono && spdActivePoOpFilter.OP === op;
                $('#spdPoOpChipsWrap .spd-poop-chip').removeClass('active');
                if (isSame) {
                    spdActivePoOpFilter = null;
                } else {
                    spdActivePoOpFilter = { POno: pono, OP: op };
                    $(`#spdChip_${idx}`).addClass('active');
                }
                renderSpdContainerBox();
            }
 

            function startStuffingSession(part, contpk) {
                if (!contpk) {
                    showToast('error', 'Container tidak ditemukan untuk session ini.');
                    return;
                }
                $.ajax({
                    url: R.eximUpdateShipment,
                    method: 'POST',
                    data: { exportpk: part, contpk: contpk, action: 'start' },
                    success: function (res) {
                        showToast(res.icon, res.title);
                        setActiveSessionPart(part);
                        focusSessionPart(part);
                        loadShipmentPlanCards();
                    },
                    error: function (xhr) {
                        const res = xhr.responseJSON || { icon: 'error', title: 'Gagal mengirim start_ship ke EXIM. Session TIDAK dimulai.' };
                        showToast(res.icon, res.title);
                        loadShipmentPlanCards();
                    }
                });
            }

            function focusSessionPart(exportpk) {
                if ($('#filterPartGlobal').data('combobox')) {
                    $('#filterPartGlobal').combobox('setValue', String(exportpk));
                }
                const targetId = window.packingViewModeGlobal === 'list'
                    ? 'packingListTableWrapper'
                    : window.packingViewModeGlobal === 'compact'
                        ? 'packingCompactWrapper'
                        : 'packingCardsGrid';
            
                document.getElementById(targetId)?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }

            function terimaCartonGlobal() {
                const packpks = window.selectedPackpksGlobal || [];
                if (!packpks.length) {
                    showToast('warning', 'Pilih minimal satu carton.');
                    return;
                }
                const selectedRows = packpks.map(pk => window.selectedRowsCache[pk]).filter(Boolean);
                const cartonCount = new Set(selectedRows.map(r => r.carton)).size;
                const rejectCount = new Set(selectedRows.filter(r => Number(r.reject) === 1).map(r => r.carton)).size;
                $('#terimaCartonInfoText').html(
                    `Anda akan menerima <strong>${cartonCount}</strong> carton (${packpks.length} baris) dari Inspect.`);
                $('#terimaCartonRejectWarning').toggleClass('d-none', rejectCount === 0);
                if (rejectCount > 0) $('#terimaCartonRejectCount').text(rejectCount);
                const cartonNames = [...new Set(selectedRows.map(r => r.carton))];
                $('#terimaCartonListText').text(cartonNames.length > 6 ? cartonNames.slice(0, 6).join(', ') +
                    `, +${cartonNames.length-6} lainnya` : cartonNames.join(', '));
                bootstrap.Modal.getOrCreateInstance(document.getElementById('terimaCartonModal')).show();
            }

            function confirmTerimaCartonGlobal() {
                const packpks = window.selectedPackpksGlobal || [];
                if (!packpks.length) return;
                $('#btnConfirmTerimaCarton').prop('disabled', true);
                $.ajax({
                    url: R.bulkShipAction,
                    method: 'POST',
                    data: {
                        packpk: packpks.join(','),
                        action: 'accept_return',
                        mif: MIF
                    },
                    success: function(res) {
                        showToast(res.icon, res.title);
                        bootstrap.Modal.getInstance(document.getElementById('terimaCartonModal')).hide();
                        closeMenuGlobal();
                        loadPackingCards();
                        reloadBreakdownSummary();
                    },
                    error: function(xhr) {
                        const res = xhr.responseJSON || {
                            icon: 'error',
                            title: 'Gagal menerima carton.'
                        };
                        showToast(res.icon, res.title);
                    },
                    complete: function() {
                        $('#btnConfirmTerimaCarton').prop('disabled', false);
                    }
                });
            }

            function bulkProsesInspectGlobal() {
                const packpks = window.selectedPackpksGlobal || [];
                if (!packpks.length) return;
                $.ajax({
                    url: R.bulkShipAction,
                    method: 'POST',
                    data: {
                        packpk: packpks.join(','),
                        action: 'inspect',
                        mif: MIF
                    },
                    success: function(res) {
                        showToast(res.icon, res.title);
                        closeMenuGlobal();
                        loadPackingCards();
                        reloadBreakdownSummary();
                    },
                    error: function(xhr) {
                        const res = xhr.responseJSON || {
                            icon: 'error',
                            title: 'Gagal.'
                        };
                        showToast(res.icon, res.title);
                    }
                });
            }

            function bulkProsesShipmentGlobal() {
                const packpks = window.selectedPackpksGlobal || [];
                if (!packpks.length) return;
                $.ajax({
                    url: R.bulkShipAction,
                    method: 'POST',
                    data: {
                        packpk: packpks.join(','),
                        action: 'shipment',
                        mif: MIF
                    },
                    success: function(res) {
                        showToast(res.icon, res.title);
                        closeMenuGlobal();
                        loadPackingCards();
                        reloadBreakdownSummary();
                        loadShipmentPlanCards();
                    },
                    error: function(xhr) {
                        const res = xhr.responseJSON || {
                            icon: 'error',
                            title: 'Gagal.'
                        };
                        showToast(res.icon, res.title);
                    }
                });
            }
        </script>
    @endif

    @if ($cfg['showHistoryTab'])
        <script>
            window.inspectionActiveTab = 'current';

            function setInspectionTab(tab) {
                window.inspectionActiveTab = tab;
                $('#tabInspectCurrentBtn, #tabInspectHistoryBtn, #tabInspectDocumentsBtn').removeClass('active');
                const btnMap = {
                    current: '#tabInspectCurrentBtn',
                    history: '#tabInspectHistoryBtn',
                    documents: '#tabInspectDocumentsBtn', // BARU
                };
                $(btnMap[tab]).addClass('active');
                $('#statusFilterChipsGlobal').toggleClass('d-none', tab !== 'current');
                closeMenuGlobal();

                if (tab === 'history') {
                    loadHistoryCards();
                } else if (tab === 'documents') {
                    loadInspectDocuments(); // BARU
                } else {
                    loadPackingCards();
                }
            }


            // ============================================================
            // 2) BARU -- load & render dokumen inspect, dikelompokkan per Part/Session.
            // ============================================================
            function loadInspectDocuments() {
                $('#packingCardsGrid').removeClass('d-none').html(
                    '<div class="col-12 text-center text-muted py-5">Memuat data...</div>');
                $('#packingListTableWrapper').addClass('d-none');
                $('#packingCardsEmpty').addClass('d-none');

                $.get(R.inspectDocumentsList, {
                    po: PO,
                    op: OP
                }, function(data) {
                    renderInspectDocuments(data.rows || []);
                });
            }

            function renderInspectDocuments(rows) {
                const grid = $('#packingCardsGrid');
                grid.empty();

                if (!rows.length) {
                    $('#packingCardsEmpty').removeClass('d-none');
                    $('#packingCardsInfo').text('0 dokumen');
                    return;
                }

                // BARU -- kelompokkan per Part/Session (satu dokumen bisa "tanpa
                // part" kalau carton-nya belum punya session, ditaruh grup terpisah).
                const groups = {};
                const groupOrder = [];
                rows.forEach(function(doc) {
                    const partLabel = (doc.parts && doc.parts.length) ?
                        doc.parts.map(p => 'Session ' + p).join(', ') :
                        'Tanpa Session';
                    if (!groups[partLabel]) {
                        groups[partLabel] = [];
                        groupOrder.push(partLabel);
                    }
                    groups[partLabel].push(doc);
                });

                groupOrder.forEach(function(partLabel) {
                    grid.append(`
                        <div class="col-12">
                            <div class="fw-bold text-dark mb-2 mt-2" style="font-size:13.5px;">
                                <span class="rounded me-2" style="width:4px;height:14px;display:inline-block;background:#64748b;"></span>
                                ${partLabel}
                            </div>
                        </div>
                    `);
                    groups[partLabel].forEach(function(doc) {
                        grid.append(buildInspectDocumentCard(doc));
                    });
                });

                $('#packingCardsInfo').text(rows.length + ' dokumen');
                $('#packingCardsPageLabel').text('');
            }

            function buildInspectDocumentCard(doc) {
                const isLulus = doc.hasil === 1;
                const badgeCls = isLulus ? 'background:#dcfce7;color:#166534;' : 'background:#fee2e2;color:#991b1b;';
                const badgeLabel = isLulus ? 'LULUS' : 'REJECT';
                const tglLabel = doc.tgl ? doc.tgl.split(' ')[0].split('-').reverse().join('/') : '-';

                let cartonListHtml = '';
                (doc.cartons || []).forEach(function(c) {
                    const sizesHtml = (c.sizes || []).map(function(s) {
                        const pillCls = s.stspass === 1 ? 'background:#dcfce7;color:#166534;' :
                            'background:#fee2e2;color:#991b1b;';
                        const pillLabel = s.stspass === 1 ? 'Pass' : 'Defect';
                        return `<span class="badge-soft" style="${pillCls} font-size:10px; margin-right:4px;">${s.label}: ${pillLabel}</span>`;
                    }).join('');
                    cartonListHtml += `
                        <div style="font-size:11.5px; color:#475569; padding:4px 0; border-bottom:1px dashed #eef1f5;">
                            <strong>${c.carton ?? '-'}</strong> ${sizesHtml}
                        </div>
                    `;
                });

                return `
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="packing-card" style="height:auto; cursor:default;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="ctn-code">${doc.no_inspec ?? ('Dokumen #' + doc.inspecpk)}</span>
                                <span class="badge-soft" style="${badgeCls}">${badgeLabel}</span>
                            </div>
                            <div class="subline mb-2">
                                <i class="fas fa-calendar-day text-muted me-1"></i>${tglLabel}
                                &middot; AQL: <strong>${doc.aql}</strong>
                                &middot; Total Sample: <strong>${doc.totpcs}</strong> pcs
                            </div>
                            <div style="max-height:140px; overflow-y:auto;">${cartonListHtml}</div>
                            <div class="card-actions mt-2">
                                <button class="btn btn-outline-dark btn-sm w-100" onclick="printInspectPdf(${doc.inspecpk})">
                                    <i class="fas fa-print me-1"></i> Cetak PDF
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }

            // BARU -- buka laporan PDF di tab baru (browser Print > Save as PDF,
            // SAMA konvensi dengan link Print PDF lain di sistem ini).
            function printInspectPdf(inspecpk) {
                window.open(`${R.inspectPdfBase}/${inspecpk}?mif=${MIF}`, '_blank');
            }

            function loadHistoryCards() {
                $.get(R.historyListDetailGlobal, {
                    po: PO,
                    op: OP,
                    poref: POREF,
                    mif: MIF,
                    search: $('#searchPackingGlobal').val(),
                    color: $('#filterColorGlobal').combobox('getValue'),
                    secsz: $('#filterSecszGlobal').combobox('getValue'),
                    sort: $('#sortFieldGlobal').combobox('getValue'),
                    page: 1,
                    rows: 200
                }, function(data) {
                    renderHistoryCards(data.rows || []);
                });
            }

            function formatHistoryDate(value) {
                if (!value) return null;
                return formatStampDate(value);
            }

            function getHistoryBadge(groupRows) {
                const anyShipped = groupRows.some(r => r.ship_shipped === true);
                const anyReturning = groupRows.some(r => r.ship_returning === true);
                const anyInspect = groupRows.some(r => r.ship_inspect === true);
                const anyReject = groupRows.some(r => Number(r.reject) === 1);
                const anyAccepted = groupRows.some(r => r.ship_kembali);

                if (anyShipped) return {
                    key: 'shipped',
                    label: 'Shipped',
                    color: '#2563eb',
                    bg: '#dbeafe'
                };
                if (anyReturning) return {
                    key: 'returning',
                    label: 'Menunggu Diterima',
                    color: '#7c3aed',
                    bg: '#f3e8ff'
                };
                if (anyInspect) return {
                    key: 'inspect',
                    label: 'Sedang Inspect',
                    color: '#f59e0b',
                    bg: '#fef3c7'
                };
                if (anyReject) return {
                    key: 'reject',
                    label: 'Reject (Diterima)',
                    color: '#991b1b',
                    bg: '#fee2e2'
                };
                if (anyAccepted) return {
                    key: 'accepted',
                    label: 'Diterima Kembali',
                    color: '#16a34a',
                    bg: '#dcfce7'
                };
                return {
                    key: 'done',
                    label: 'Selesai',
                    color: '#64748b',
                    bg: '#f1f5f9'
                };
            }

            function buildHistoryCard(groupRows) {
                const first = groupRows[0];
                const badge = getHistoryBadge(groupRows);
                const pinjamRow = groupRows.find(r => r.ship_pinjam);
                const kembaliRow = groupRows.find(r => r.ship_kembali);
                const tglPinjam = pinjamRow ? formatHistoryDate(pinjamRow.ship_pinjam) : null;
                const tglKembali = kembaliRow ? formatHistoryDate(kembaliRow.ship_kembali) : null;
                const uniqueCombos = new Set(groupRows.map(r => `${r.material ?? '-'}||${r.secsz ?? ''}`));
                const subline = uniqueCombos.size === 1 ?
                    `${getComboLabel(first)}${first.secsz ? ' &middot; Sec Size ' + first.secsz : ''}` :
                    `${uniqueCombos.size} kombinasi Color/Sec Size`;

                // BARU
                const inspecDocRow = groupRows.find(r => r.no_inspec);
                const inspecDocBadgeHtml = buildInspecDocBadge(inspecDocRow);

                return `
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="packing-card" style="height:auto; cursor:default;">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <span class="ctn-code">${first.carton ?? '-'}</span>
                                <span class="badge-soft" style="background:${badge.bg};color:${badge.color};border-color:${badge.bg};">${badge.label}</span>
                            </div>
                            <div class="subline mb-2">${subline}</div>
                            <div style="font-size:12px; color:#475569; line-height:1.6;">
                                <div><i class="fas fa-arrow-right-to-bracket me-1" style="color:#f59e0b;"></i> Masuk Inspect: <strong>${tglPinjam ?? '-'}</strong></div>
                                <div><i class="fas fa-arrow-right-from-bracket me-1" style="color:#16a34a;"></i> Diterima Kembali: <strong>${tglKembali ?? 'Belum diterima'}</strong></div>
                            </div>
                            <div class="card-barcode mt-2">
                                <span class="barcode-text"><i class="fas fa-barcode me-1"></i>${first.nobar || '<span class="text-muted">Belum ada barcode</span>'}</span>
                            </div>
                            ${inspecDocBadgeHtml}
                        </div>
                    </div>
                `;
            }

            function buildHistoryListRow(groupRows) {
                const first = groupRows[0];
                const badge = getHistoryBadge(groupRows);
                const pinjamRow = groupRows.find(r => r.ship_pinjam);
                const kembaliRow = groupRows.find(r => r.ship_kembali);
                const tglPinjam = pinjamRow ? formatHistoryDate(pinjamRow.ship_pinjam) : '-';
                const tglKembali = kembaliRow ? formatHistoryDate(kembaliRow.ship_kembali) : 'Belum diterima';
                const uniqueCombos = new Set(groupRows.map(r => `${r.material ?? '-'}||${r.secsz ?? ''}`));
                const subline = uniqueCombos.size === 1 ?
                    `${getComboLabel(first)}${first.secsz ? ' &middot; ' + first.secsz : ''}` :
                    `${uniqueCombos.size} kombinasi`;

                // BARU
                const inspecDocRow = groupRows.find(r => r.no_inspec);
                const inspecDocBadgeHtml = buildInspecDocBadge(inspecDocRow);

                return `
                    <tr>
                        <td class="text-start"><strong>${first.carton ?? '-'}</strong></td>
                        <td class="text-start" style="font-size:12.5px;">${first.nobar || '-'}</td>
                        <td class="text-start" style="font-size:12.5px;">${subline}${inspecDocBadgeHtml}</td>
                        <td class="text-center"><span class="badge-status" style="background:${badge.bg};color:${badge.color};">${badge.label}</span></td>
                        <td style="font-size:12px;">Masuk: ${tglPinjam}<br>Kembali: ${tglKembali}</td>
                    </tr>
                `;
            }

            function renderHistoryCards(rows) {
                const grid = $('#packingCardsGrid'),
                    listWrapper = $('#packingListTableWrapper'),
                    listBody = $('#packingListBody'),
                    empty = $('#packingCardsEmpty');
                grid.empty();
                listBody.empty();

                if (!rows.length) {
                    empty.removeClass('d-none');
                    grid.addClass('d-none');
                    listWrapper.addClass('d-none');
                    $('#packingCardsInfo').text('0 carton');
                    return;
                }
                empty.addClass('d-none');

                const cartonGroups = {},
                    cartonOrder = [];
                rows.forEach(function(row) {
                    const key = row.carton ?? '(tanpa carton)';
                    if (!cartonGroups[key]) {
                        cartonGroups[key] = [];
                        cartonOrder.push(key);
                    }
                    cartonGroups[key].push(row);
                });

                if (window.packingViewModeGlobal === 'list') {
                    grid.addClass('d-none');
                    listWrapper.removeClass('d-none');
                    cartonOrder.forEach(k => listBody.append(buildHistoryListRow(cartonGroups[k])));
                } else {
                    listWrapper.addClass('d-none');
                    grid.removeClass('d-none');
                    cartonOrder.forEach(k => grid.append(buildHistoryCard(cartonGroups[k])));
                }
                $('#packingCardsInfo').text(cartonOrder.length + ' carton (histori)');
                $('#packingCardsPageLabel').text('');
                $('#btnPackingCardsPrev, #btnPackingCardsNext').prop('disabled', true);
            }
        </script>
    @endif

    @if ($cfg['showKembalikanButton'])
        <script>
            function safeIdPart(str) {
                return String(str).replace(/[^a-zA-Z0-9_-]/g, '_');
            }

            let inspecCart = []; // [{packpk, shippk, carton, size, color, secsz, qty:1}] -- SETIAP entry SELALU qty:1, TIDAK PERNAH digabung
            let inspecRemainingBySizeKey = {}; // key = `${packpk}|${sizeLabel}` -> sisa yang boleh diambil

            function openInspectDocumentModal() {
                inspecCart = [];
                inspecRemainingBySizeKey = {};
                $('#inspecAql').val('');
                $('#inspecHasilLulus').prop('checked', true);
                $('#inspecRejectWarning').addClass('d-none');
                renderInspecCart();
            
                // BARU -- FIX UTAMA: ambil part dari carton yang sedang dipilih di
                // halaman (SUDAH pasti seragam, divalidasi updateSelectionGlobal()).
                const packpks = window.selectedPackpksGlobal || [];
                const selectedRows = packpks.map(pk => window.selectedRowsCache[pk]).filter(Boolean);
                const selectedPart = selectedRows.length ? (selectedRows[0].part ?? '') : '';
                window.currentInspecDocPart = selectedPart; // simpan utk dipakai submitInspecDocument() kalau perlu
            
                bootstrap.Modal.getOrCreateInstance(document.getElementById('inspectDocumentModal')).show();
                loadInspectAvailableCartons(selectedPart);
            }
            
            // GANTI loadInspectAvailableCartons() -- TAMBAH parameter 'part' yang
            // diteruskan ke endpoint backend.
            function loadInspectAvailableCartons(part) {
                $.get(R.inspectAvailableCartons, { po: PO, op: OP, mif: MIF, part: part }, function (data) {
                    renderInspectCartonList(data.rows || []);
                });
            }

            $(document).on('change', 'input[name="inspecHasil"]', function() {
                $('#inspecRejectWarning').toggleClass('d-none', $(this).val() !== '0' || !$(this).is(':checked'));
            });

            function renderInspectCartonList(rows) {
                const wrap = $('#inspectCartonList');
                wrap.empty();
                if (!rows.length) {
                    $('#inspectCartonEmpty').removeClass('d-none');
                    return;
                }
                $('#inspectCartonEmpty').addClass('d-none');

                const cartonGroups = {};
                const cartonOrder = [];
                rows.forEach(function(row) {
                    window['inspecPackData_' + row.packpk] = row;
                    const key = row.carton ?? '(tanpa carton)';
                    if (!cartonGroups[key]) {
                        cartonGroups[key] = [];
                        cartonOrder.push(key);
                    }
                    cartonGroups[key].push(row);

                    row.sizes.forEach(function(s) {
                        const sizeKey = `${row.packpk}|${s.label}`;
                        if (!(sizeKey in inspecRemainingBySizeKey)) {
                            inspecRemainingBySizeKey[sizeKey] = s.qty;
                        }
                    });
                });

                cartonOrder.forEach(function(cartonNo) {
                    const packRowsInCarton = cartonGroups[cartonNo];
                    const uniqueCombos = new Set(packRowsInCarton.map(r => `${r.material ?? '-'}||${r.secsz ?? ''}`));
                    const isMixed = uniqueCombos.size > 1;
                    const cartonIdSafe = safeIdPart(cartonNo);

                    let sizePillsHtml = '';
                    packRowsInCarton.forEach(function(row) {
                        const materialLabel = row.material ?? '-';
                        const secszTag = row.secsz ? ` (${row.secsz})` : '';
                        row.sizes.forEach(function(s) {
                            const sizeKey = `${row.packpk}|${s.label}`;
                            const remaining = inspecRemainingBySizeKey[sizeKey];
                            const isEmpty = remaining <= 0;
                            const sizeIdSafe = safeIdPart(row.packpk + '_' + s.label);

                            sizePillsHtml += `
                                <div class="inspec-size-pill ${isEmpty ? 'is-empty' : ''}" id="inspecSizeRow_${sizeIdSafe}">
                                    <div class="isp-label">${s.label}</div>
                                    <div class="isp-combo">${materialLabel}${secszTag}</div>
                                    <div class="isp-remaining" id="inspecRemainingLabel_${sizeIdSafe}">sisa ${remaining}</div>
                                    <div>
                                        <button type="button" class="isp-add-btn" ${isEmpty ? 'disabled' : ''}
                                            onclick="addInspecSampleUnit(${row.packpk}, '${String(s.label).replace(/'/g, "\\'")}')">
                                            <i class="fas fa-plus" style="font-size:11px;"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                    });

                    const compositionLabel = isMixed ? 'Mixed' : (packRowsInCarton[0].sizes.length > 1 ? 'Assorted' :
                        'Solid');
                    const compositionCls = isMixed ? 'mixed' : (packRowsInCarton[0].sizes.length > 1 ? 'assorted' :
                        'solid');
                    const repRow = packRowsInCarton[0];

                    wrap.append(`
                        <div class="inspec-carton-row">
                            <div class="inspec-carton-header" onclick="toggleInspecCartonSize('${String(cartonNo).replace(/'/g, "\\'")}')">
                                <div class="inspec-carton-icon"><i class="fas fa-box-open"></i></div>
                                <div class="flex-grow-1">
                                    <div class="inspec-carton-title">
                                        Carton ${cartonNo}
                                        <span class="inspec-badge-soft ${compositionCls}">${compositionLabel}</span>
                                    </div>
                                    <div class="inspec-carton-sub"><i class="fas fa-barcode me-1"></i>${repRow.nobar ?? 'Belum ada barcode'}</div>
                                </div>
                                <i class="fas fa-chevron-down text-muted" id="inspecExpandIcon_${cartonIdSafe}"></i>
                            </div>
                            <div class="inspec-carton-perf"></div>
                            <div class="inspec-size-detail" id="inspecSizeDetail_${cartonIdSafe}">
                                <div class="inspec-size-grid">${sizePillsHtml}</div>
                            </div>
                        </div>
                    `);
                });
            }

            function toggleInspecCartonSize(cartonNo) {
                const cartonIdSafe = safeIdPart(cartonNo);
                $('#inspecSizeDetail_' + cartonIdSafe).toggleClass('is-open');
                $('#inspecExpandIcon_' + cartonIdSafe).toggleClass('fa-chevron-down fa-chevron-up');
            }

            let inspecDefectSubCache = {
                subs: [],
                defects: []
            };
            let currentDefectPickerLineIndex = null; // line yang sedang diedit lewat defectPickerModal
            let currentDefectPickerSelected = []; // defectpk yang sementara dipilih di picker (belum di-confirm)

            function addInspecSampleUnit(packpk, sizeLabel) {
                const sizeKey = `${packpk}|${sizeLabel}`;
                const remaining = inspecRemainingBySizeKey[sizeKey] ?? 0;

                if (remaining <= 0) {
                    showToast('warning', 'Sisa untuk size ini sudah habis, tidak bisa ditambah lagi.');
                    return;
                }

                const row = window['inspecPackData_' + packpk];

                inspecCart.push({
                    packpk: packpk,
                    shippk: row.shippk,
                    carton: row.carton,
                    size: sizeLabel,
                    color: row.material,
                    secsz: row.secsz,
                    qty: 1,
                    stspass: 1, // BARU -- default Pass
                    defects: [], // BARU
                });

                inspecRemainingBySizeKey[sizeKey] = remaining - 1;

                const sizeIdSafe = safeIdPart(packpk + '_' + sizeLabel);
                $('#inspecRemainingLabel_' + sizeIdSafe).text('sisa ' + inspecRemainingBySizeKey[sizeKey]);
                if (inspecRemainingBySizeKey[sizeKey] <= 0) {
                    $('#inspecSizeRow_' + sizeIdSafe + ' button').prop('disabled', true);
                }

                renderInspecCart();
                recomputeHasilDisplay();
            }

            function addInspecSampleLine(shippk, sizeIdx, inputId) {
                const row = window['inspecShipData_' + shippk];
                const sizeInfo = row.sizes[sizeIdx];
                const qty = parseFloat($('#' + inputId).val());

                if (!qty || qty <= 0) {
                    showToast('warning', 'Qty sample harus lebih dari 0.');
                    return;
                }
                if (qty > sizeInfo.qty) {
                    showToast('warning', `Qty sample melebihi total di carton ini (maks ${sizeInfo.qty}).`);
                    return;
                }

                inspecCart.push({
                    shippk: shippk,
                    size: sizeInfo.label,
                    color: row.material,
                    secsz: row.secsz,
                    qty: qty,
                    carton: row.carton,
                });

                renderInspecCart();
                showToast('success', `${row.carton} - ${sizeInfo.label} (${qty} pcs) ditambahkan ke dokumen.`);
            }

            function removeInspecCartLine(index) {
                const line = inspecCart[index];
                if (!line) return;

                inspecCart.splice(index, 1);

                const sizeKey = `${line.packpk}|${line.size}`;
                inspecRemainingBySizeKey[sizeKey] = (inspecRemainingBySizeKey[sizeKey] ?? 0) + 1;

                const sizeIdSafe = safeIdPart(line.packpk + '_' + line.size);
                const $label = $('#inspecRemainingLabel_' + sizeIdSafe);
                if ($label.length) {
                    $label.text('sisa ' + inspecRemainingBySizeKey[sizeKey]);
                    $('#inspecSizeRow_' + sizeIdSafe + ' button').prop('disabled', false);
                }

                renderInspecCart();
                recomputeHasilDisplay();
            }

            function renderInspecCart() {
                $('#inspecCartCount').text(inspecCart.length);
                $('#inspecTotalPcs').text(inspecCart.length);

                const list = $('#inspecCartList');
                list.empty();
                if (!inspecCart.length) {
                    list.html(
                        '<div class="text-muted text-center py-4" style="font-size:12.5px;">Belum ada sample dipilih.</div>'
                    );
                    return;
                }
                inspecCart.forEach(function(line, index) {
                    const isPass = line.stspass === 1;
                    const isDefect = line.stspass === 0;

                    let defectTagsHtml = '';
                    if (isDefect) {
                        defectTagsHtml = line.defects.length ?
                            `<div class="cart-line-defect-tags">${line.defects.map(d => `<span class="tag">${d.defectnm}</span>`).join('')}</div>` :
                            `<div class="cart-line-defect-tags"><span class="tag muted">Klik stempel Defect lagi utk pilih tipe</span></div>`;
                    }

                    list.append(`
                        <div class="inspec-cart-row">
                            <div class="icr-top">
                                <div>
                                    <div class="icr-title">Carton ${line.carton} &middot; ${line.size}</div>
                                    <div class="icr-sub">${line.color ?? '-'} ${line.secsz ? '(' + line.secsz + ')' : ''} &middot; 1 pcs</div>
                                </div>
                                <i class="fas fa-times ic-remove" onclick="removeInspecCartLine(${index})"></i>
                            </div>
                            <div class="qc-stamp-row">
                                <div class="qc-stamp pass ${isPass ? 'active' : ''}" onclick="setCartLineStatus(${index}, 1)">
                                    <i class="fas fa-check"></i>
                                    <span class="qc-stamp-text">Pass</span>
                                </div>
                                <div class="qc-stamp defect ${isDefect ? 'active' : ''}" onclick="setCartLineStatus(${index}, 0)">
                                    <i class="fas fa-xmark"></i>
                                    <span class="qc-stamp-text">Defect</span>
                                </div>
                            </div>
                            ${defectTagsHtml}
                        </div>
                    `);
                });
            }

            function submitInspecDocument() {
                if (!inspecCart.length) {
                    showToast('warning', 'Pilih minimal 1 sample (klik tombol + pada size yang diinginkan).');
                    return;
                }
                const aql = $('#inspecAql').val();
                if (aql === '' || Number(aql) < 0) {
                    showToast('warning', 'Isi nilai AQL terlebih dulu.');
                    return;
                }

                // Validasi: semua baris Defect WAJIB sudah punya minimal 1 defect dipilih.
                const belumPilihDefect = inspecCart.some(l => l.stspass === 0 && (!l.defects || !l.defects.length));
                if (belumPilihDefect) {
                    showToast('warning', 'Ada baris berstatus Defect yang belum dipilih tipe defect-nya.');
                    return;
                }

                $('#btnSubmitInspecDoc').prop('disabled', true);
                $.ajax({
                    url: R.inspectStore,
                    method: 'POST',
                    data: {
                        aql: aql,
                        mif: MIF,
                        lines: inspecCart.map(l => ({
                            shippk: l.shippk,
                            size: l.size,
                            color: l.color,
                            secsz: l.secsz,
                            qty: l.qty,
                            stspass: l.stspass,
                            defects: l.defects.map(d => d.defectpk),
                        })),
                    },
                    success: function(res) {
                        showToast(res.icon, res.title);
                        bootstrap.Modal.getInstance(document.getElementById('inspectDocumentModal')).hide();
                        window.lastInspecDocHasil = res.hasil;
                    },
                    error: function(xhr) {
                        const res = xhr.responseJSON || {
                            icon: 'error',
                            title: 'Gagal menyimpan dokumen inspect.'
                        };
                        showToast(res.icon, res.title);
                    },
                    complete: function() {
                        $('#btnSubmitInspecDoc').prop('disabled', false);
                    }
                });
            }
            //  function ini SEBELUMNYA cuma ada di blade Inspection
            // yang LAMA (menu.inspection.input-global) -- sekarang kita render
            // lewat blade GABUNGAN, jadi harus didefinisikan DI SINI. Dipakai
            // tombol "Kembalikan ke Stuffing" di sticky bar.
            function confirmKembalikanStuffing() {
                const packpks = window.selectedPackpksGlobal || [];
                if (!packpks.length) {
                    showToast('warning', 'Pilih minimal satu carton.');
                    return;
                }

                const selectedRows = packpks.map(pk => window.selectedRowsCache[pk]).filter(Boolean);
                const cartonCount = new Set(selectedRows.map(r => r.carton)).size;

                $('#kembalikanInfoText').html(
                    `Anda akan mengembalikan <strong>${cartonCount}</strong> carton ke FinishGood.`
                );

                // BARU -- FIX UTAMA: semua carton yang bisa sampai ke modal ini
                // SEHARUSNYA sudah punya dokumen (tombol ini baru muncul kalau
                // allHaveInspecDoc === true di updateSelectionGlobal()), tapi tetap
                // divalidasi ulang di sini utk jaga-jaga.
                const missingDoc = selectedRows.some(r => !r.has_inspec_doc);
                const $display = $('#kembalikanHasilDisplay');
                $('#kembalikanWarningBoxOk, #kembalikanWarningBoxReject, #kembalikanWarningBoxMissing').addClass('d-none');

                if (missingDoc) {
                    $display.attr('class', 'alert alert-secondary py-2 px-3 mb-0 text-center fw-bold')
                        .text('Belum ada Dokumen Inspect');
                    $('#kembalikanWarningBoxMissing').removeClass('d-none');
                    $('#btnConfirmKembalikanStuffing').prop('disabled', true);
                    return;
                }

                // Ambil hasil dari dokumen -- SEHARUSNYA seragam (1 session biasanya
                // 1 dokumen), tapi kalau kebetulan campur, REJECT menang (paling aman).
                const anyReject = selectedRows.some(r => r.inspec_hasil === 0);
                const repRow = selectedRows.find(r => r.no_inspec) || {};

                $('#btnConfirmKembalikanStuffing').prop('disabled', false);

                if (anyReject) {
                    $display.attr('class', 'alert alert-danger py-2 px-3 mb-0 text-center fw-bold')
                        .html(`<i class="fas fa-times-circle me-1"></i>REJECT &middot; Dokumen ${repRow.no_inspec ?? '-'}`);
                    $('#kembalikanWarningBoxReject').removeClass('d-none');
                } else {
                    $display.attr('class', 'alert alert-success py-2 px-3 mb-0 text-center fw-bold')
                        .html(`<i class="fas fa-check-circle me-1"></i>LULUS &middot; Dokumen ${repRow.no_inspec ?? '-'}`);
                    $('#kembalikanWarningBoxOk').removeClass('d-none');
                }

                bootstrap.Modal.getOrCreateInstance(document.getElementById('kembalikanStuffingModal')).show();
            }


            // GANTI submitKembalikanStuffing() -- HAPUS parameter 'reject' manual
            // (server sekarang tentukan sendiri dari dokumen inspec).
            function submitKembalikanStuffing() {
                const packpks = window.selectedPackpksGlobal || [];
                if (!packpks.length) return;
                $('#btnConfirmKembalikanStuffing').prop('disabled', true);
                $.ajax({
                    url: R.bulkShipAction,
                    method: 'POST',
                    data: {
                        packpk: packpks.join(','),
                        action: 'request_return',
                        mif: MIF,
                    },
                    success: function(res) {
                        showToast(res.icon, res.title);
                        bootstrap.Modal.getInstance(document.getElementById('kembalikanStuffingModal')).hide();
                        closeMenuGlobal();
                        loadPackingCards();
                        reloadBreakdownSummary();
                    },
                    error: function(xhr) {
                        const res = xhr.responseJSON || {
                            icon: 'error',
                            title: 'Gagal mengembalikan carton.'
                        };
                        showToast(res.icon, res
                            .title); // pesan ini akan berisi "belum ada Dokumen Inspect" kalau memang belum ada
                    },
                    complete: function() {
                        $('#btnConfirmKembalikanStuffing').prop('disabled', false);
                    }
                });
            }
        </script>

        <script>
            function setCartLineStatus(index, stspass) {
                const line = inspecCart[index];
                if (!line) return;

                line.stspass = stspass;
                if (stspass === 1) {
                    line.defects = []; // Pass -- bersihkan defect kalau sebelumnya sempat pilih
                }

                renderInspecCart();
                recomputeHasilDisplay();

                if (stspass === 0) {
                    openDefectPicker(index);
                }
            }


            // ============================================================
            // 4) BARU -- picker defect (grouped per sub kategori, TANPA canvas).
            // ============================================================
            function loadDefectSubDataIfNeeded(callback) {
                if (inspecDefectSubCache.defects.length) {
                    callback();
                    return;
                }
                $.get(R.inspectDefectSubList, {
                    mif: MIF
                }, function(data) {
                    inspecDefectSubCache = {
                        subs: data.subs || [],
                        defects: data.defects || []
                    };
                    callback();
                });
            }

            function openDefectPicker(lineIndex) {
                currentDefectPickerLineIndex = lineIndex;
                currentDefectPickerSelected = (inspecCart[lineIndex].defects || []).map(d => d.defectpk);

                loadDefectSubDataIfNeeded(function() {
                    renderDefectSubTabs();
                    renderDefectList(inspecDefectSubCache.subs[0]?.subpk ?? null);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('defectPickerModal')).show();
                });
            }

            function renderDefectSubTabs() {
                const wrap = $('#defectSubTabs');
                wrap.empty();
                inspecDefectSubCache.subs.forEach(function(sub, idx) {
                    wrap.append(`
                        <div class="defect-sub-tab ${idx === 0 ? 'active' : ''}" data-subpk="${sub.subpk}" onclick="switchDefectSubTab(${sub.subpk})">
                            ${sub.subnm}
                        </div>
                    `);
                });
            }

            function switchDefectSubTab(subpk) {
                $('#defectSubTabs .defect-sub-tab').removeClass('active');
                $(`#defectSubTabs .defect-sub-tab[data-subpk="${subpk}"]`).addClass('active');
                renderDefectList(subpk);
            }

            function renderDefectList(subpk) {
                const wrap = $('#defectListWrap');
                wrap.empty();
                const filtered = inspecDefectSubCache.defects.filter(d => d.subpk === subpk);
                if (!filtered.length) {
                    wrap.html('<div class="text-muted" style="font-size:12px;">Tidak ada defect di kategori ini.</div>');
                    return;
                }
                filtered.forEach(function(d) {
                    const isSelected = currentDefectPickerSelected.includes(d.defectpk);
                    wrap.append(`
                        <div class="defect-chip ${isSelected ? 'selected' : ''}" data-defectpk="${d.defectpk}" onclick="toggleDefectChip(${d.defectpk}, this)">
                            ${d.defectnm}
                        </div>
                    `);
                });
            }

            function toggleDefectChip(defectpk, el) {
                const idx = currentDefectPickerSelected.indexOf(defectpk);
                if (idx >= 0) {
                    currentDefectPickerSelected.splice(idx, 1);
                    $(el).removeClass('selected');
                } else {
                    currentDefectPickerSelected.push(defectpk);
                    $(el).addClass('selected');
                }
            }

            function confirmDefectPicker() {
                if (currentDefectPickerLineIndex === null) return;

                if (!currentDefectPickerSelected.length) {
                    showToast('warning', 'Pilih minimal 1 tipe defect.');
                    return;
                }

                const line = inspecCart[currentDefectPickerLineIndex];
                line.defects = currentDefectPickerSelected.map(pk => {
                    const d = inspecDefectSubCache.defects.find(x => x.defectpk === pk);
                    return {
                        defectpk: pk,
                        defectnm: d ? d.defectnm : ('#' + pk)
                    };
                });

                bootstrap.Modal.getInstance(document.getElementById('defectPickerModal')).hide();
                renderInspecCart();
                recomputeHasilDisplay();
            }


            // ============================================================
            // 5) BARU -- hitung & tampilkan hasil OTOMATIS (live), SAMA logic
            // dgn yang dihitung ulang di server saat submit.
            // ============================================================
            function recomputeHasilDisplay() {
                const aql = parseFloat($('#inspecAql').val());
                const defectCount = inspecCart.filter(l => l.stspass === 0).length;
                $('#inspecDefectCount').text(defectCount);

                const $display = $('#inspecHasilDisplay');

                if (isNaN(aql)) {
                    $display.attr('class', 'alert alert-secondary py-2 px-3 mb-0 text-center fw-bold').text(
                        'Isi AQL untuk melihat hasil');
                    return;
                }

                const isReject = defectCount >= aql;
                if (isReject) {
                    $display.attr('class', 'alert alert-danger py-2 px-3 mb-0 text-center fw-bold')
                        .html(`<i class="fas fa-times-circle me-1"></i>REJECT (Defect ${defectCount} &ge; AQL ${aql})`);
                } else {
                    $display.attr('class', 'alert alert-success py-2 px-3 mb-0 text-center fw-bold')
                        .html(`<i class="fas fa-check-circle me-1"></i>LULUS (Defect ${defectCount} &lt; AQL ${aql})`);
                }
            }
        </script>
    @endif
@endsection
