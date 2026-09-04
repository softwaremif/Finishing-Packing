@once
    <style>
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
            box-shadow: 2px 0 5px rgba(0,0,0,0.03);
        }
        .table-breakdown thead tr th.sticky-col-start {
            background-color: #f8fafc;
            z-index: 3;
        }

        .table-breakdown .sticky-col-end {
            position: sticky;
            right: 0;
            z-index: 2;
            border-left: 1px solid #e2e8f0; /* fix: sebelumnya "border-start" (bukan properti CSS valid) */
            box-shadow: -2px 0 5px rgba(0,0,0,0.03);
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
    </style>
@endonce

@props([
    'sizes' => [],
    'sizeGroup' => null,
    'orderQty' => [],
    'readyQty' => [],
    'inspectQty' => [],   {{-- fix: sebelumnya 'inspect-qty' (kebab-case tidak jadi variabel) --}}
    'diffQty' => [],
    'orderTotal' => 0,
    'readyTotal' => 0,
    'inspectTotal' => 0,  {{-- fix: sebelumnya 'inspect-total' --}}
    'balanceTotal' => 0,
    'title' => 'Breakdown Size & Qty',

    // summary
    'sumTitle' => 'Summary CTN',
    'tctnp' => 0,       {{-- Plan: semua baris ship --}}
    'tctni' => 0,       {{-- Inspect: fca = 1 --}}
    'tctna' => 0,       {{-- Aktual: status >= 6 --}}
    'balanceCtn' => 0,
])

<div class="row g-4">
    {{-- ===================== SEKSI KIRI: BREAKDOWN SIZE ===================== --}}
    <div class="col-12 col-md-8">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
                <div class="fw-bold text-dark d-flex align-items-center" style="font-size: 15px;">
                    <span class="rounded me-2"
                        style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
                    {{ $title }}
                </div>
            </div>

            <div class="card-body p-0 table-responsive style-scrollbar">
                <table class="table table-hover text-center mb-0 align-middle table-breakdown">

                    <thead>
                        <tr style="background-color:#f8fafc;border-bottom:1px solid #e2e8f0;">

                            <th class="text-start px-3 sticky-col-start fw-bold" style="font-size:12px;">
                                Size
                                @if($sizeGroup)
                                    <span class="text-muted fw-bold text-lowercase">
                                        ({{ $sizeGroup }})
                                    </span>
                                @endif
                            </th>

                            @foreach($sizes as $size)
                                <th class="text-dark" style="font-size:12px;">
                                    {{ $size }}
                                </th>
                            @endforeach

                            <th class="sticky-col-end fw-bold text-dark" style="font-size:12px;">
                                TOTAL
                            </th>

                        </tr>
                    </thead>

                    <tbody>

                        {{-- ORDER: SUM(qtyp) --}}
                        <tr>
                            <td class="text-start px-3 text-secondary sticky-col-start">
                                Planning Shipment
                            </td>

                            @foreach($sizes as $key => $size)
                                <td class="text-dark fw-medium">
                                    {{ $orderQty[$key] ?? 0 }}
                                </td>
                            @endforeach

                            <td class="fw-bold text-dark sticky-col-end bg-total-cell">
                                {{ $orderTotal }}
                            </td>
                        </tr>

                        {{-- INSPECT: SUM(qty) dengan fca = 1
                             fix: sebelumnya menampilkan $readyQty/$readyTotal (salah copy) --}}
                        <tr class="table-sm">
                            <td class="text-start px-3 text-secondary sticky-col-start">
                                Inspect Shipment
                            </td>

                            @foreach($sizes as $key => $size)
                                <td class="text-dark fw-medium">
                                    {{ $inspectQty[$key] ?? 0 }}
                                </td>
                            @endforeach

                            <td class="fw-bold text-dark sticky-col-end bg-total-cell">
                                {{ $inspectTotal }}
                            </td>
                        </tr>

                        {{-- READY: SUM(qty) dengan status >= 6 --}}
                        <tr class="table-sm">
                            <td class="text-start px-3 text-secondary sticky-col-start">
                                Ready Shipment
                            </td>

                            @foreach($sizes as $key => $size)
                                <td class="text-dark fw-medium">
                                    {{ $readyQty[$key] ?? 0 }}
                                </td>
                            @endforeach

                            <td class="fw-bold text-dark sticky-col-end bg-total-cell">
                                {{ $readyTotal }}
                            </td>
                        </tr>

                        {{-- BALANCE --}}
                        <tr class="border-top border-light" style="border-width:1.5px;">
                            <td class="text-start px-3 fw-bold text-secondary sticky-col-start">
                                Balance (+/-)
                            </td>

                            @foreach($sizes as $key => $size)
                                @php
                                    $value = $diffQty[$key] ?? 0;
                                @endphp

                                <td class="fw-bold
                                    {{ $value < 0
                                        ? 'text-danger'
                                        : ($value > 0 ? 'text-success' : 'text-muted') }}">
                                    {{ $value > 0 ? '+' . $value : $value }}
                                </td>
                            @endforeach

                            <td class="fw-bold sticky-col-end
                                {{ $balanceTotal < 0
                                    ? 'text-danger bg-danger-subtle'
                                    : ($balanceTotal > 0
                                        ? 'text-success bg-success-subtle'
                                        : 'text-muted bg-total-cell') }}">
                                {{ $balanceTotal > 0 ? '+' . $balanceTotal : $balanceTotal }}
                            </td>
                        </tr>

                    </tbody>

                </table>
            </div>
        </div>
    </div>

    {{-- ===================== SEKSI KANAN: SUMMARY CTN ===================== --}}
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex align-items-center" style="font-size: 15px;">
                <span class="rounded me-2"
                    style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
                <span class="fw-bold text-dark">{{ $sumTitle }}</span>
            </div>

            <div class="card-body p-0 table-responsive style-scrollbar">
                <table class="table text-center mb-0 align-middle">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <th class="py-2 text-secondary fw-semibold"
                                style="font-size: 10px; letter-spacing: 0.5px; text-transform: uppercase;">Plan
                            </th>
                            <th class="py-2 text-secondary fw-semibold"
                                style="font-size: 10px; letter-spacing: 0.5px; text-transform: uppercase;">Inspect
                            </th>
                            <th class="py-2 text-secondary fw-semibold"
                                style="font-size: 10px; letter-spacing: 0.5px; text-transform: uppercase;">Aktual
                            </th>
                            <th class="py-2 text-secondary fw-semibold"
                                style="font-size: 10px; letter-spacing: 0.5px; text-transform: uppercase;">Balance
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-0">
                            <td class="py-3 fw-bold text-dark fs-5">{{ $tctnp ?? 0 }}</td>
                            <td class="py-3 fw-bold text-dark fs-5">{{ $tctni ?? 0 }}</td>
                            <td class="py-3 fw-bold text-dark fs-5">{{ $tctna ?? 0 }}</td>
                            {{-- fix: sebelumnya ada assignment $balanceCInteractive di dalam class
                                 dan nilai 0 ikut diberi warna hijau --}}
                            <td class="py-3 fw-bold fs-5
                                {{ $balanceCtn < 0
                                    ? 'bg-danger-subtle text-danger'
                                    : ($balanceCtn > 0
                                        ? 'bg-success-subtle text-success'
                                        : 'text-muted') }}">
                                {{ $balanceCtn > 0 ? '+' . $balanceCtn : $balanceCtn }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>