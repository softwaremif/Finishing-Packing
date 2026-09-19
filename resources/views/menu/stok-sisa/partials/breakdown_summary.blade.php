@php
    // Fallback aman -- kalau partial ini dipanggil dari tempat lain yang
    // tidak mengirim variabel ini, jangan fatal.
    $activeSizes = $activeSizes ?? [];
    $orderQty    = $orderQty ?? [];
    $shippedQty  = $shippedQty ?? [];
    $gradedQty   = $gradedQty ?? [];
    $sisaQty     = $sisaQty ?? [];
    $totalOrder  = $totalOrder ?? 0;
    $totalShipped = $totalShipped ?? 0;
    $totalGraded  = $totalGraded ?? 0;
    $totalSisaBelumDigrade = $totalSisaBelumDigrade ?? 0;
@endphp

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
        <div class="fw-bold text-dark d-flex align-items-center">
            <span class="rounded me-2"
                style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
            Breakdown Size & Qty
        </div>
    </div>

    <div class="card-body p-0 table-responsive style-scrollbar">
        <table class="table table-hover text-center mb-0 align-middle table-breakdown">
            <thead>
                <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <th class="text-start px-3 sticky-col-start fw-bold" style="font-size: 12px;">
                        Qty Type
                        @if (!empty($dt->secsz))
                            <span class="text-muted fw-bold text-lowercase">({{ $dt->secsz }})</span>
                        @endif
                    </th>
                    @foreach ($activeSizes as $i => $size)
                        <th style="font-size: 12px;" class="text-dark">{{ $size }}</th>
                    @endforeach
                    <th class="sticky-col-end fw-bold text-dark" style="font-size: 12px;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-start px-3 text-secondary sticky-col-start">Order Qty</td>
                    @foreach ($activeSizes as $i => $size)
                        <td class="text-dark fw-medium">{{ $orderQty[$i] ?? 0 }}</td>
                    @endforeach
                    <td class="fw-bold text-dark sticky-col-end bg-total-cell">{{ $totalOrder }}</td>
                </tr>
                <tr class="table-sm">
                    <td class="text-start px-3 text-secondary sticky-col-start">Shipped Qty</td>
                    @foreach ($activeSizes as $i => $size)
                        <td class="text-dark fw-medium">{{ $shippedQty[$i] ?? 0 }}</td>
                    @endforeach
                    <td class="fw-bold bg-light text-dark border-start">{{ $totalShipped }}</td>
                </tr>
                <tr class="table-sm">
                    <td class="text-start px-3 text-secondary sticky-col-start">Sudah Digrade</td>
                    @foreach ($activeSizes as $i => $size)
                        <td class="text-dark fw-medium">{{ $gradedQty[$i] ?? 0 }}</td>
                    @endforeach
                    <td class="fw-bold bg-light text-dark border-start">{{ $totalGraded }}</td>
                </tr>
                <tr class="border-top border-light" style="border-width: 1.5px;">
                    <td class="text-start px-3 fw-bold text-secondary sticky-col-start">Sisa Belum Digrade</td>
                    @foreach ($activeSizes as $i => $size)
                        @php $val = $sisaQty[$i] ?? 0; @endphp
                        <td class="fw-bold {{ $val > 0 ? 'text-danger' : 'text-muted opacity-50' }}">
                            {{ $val ?: '0' }}
                        </td>
                    @endforeach
                    <td class="fw-bold sticky-col-end {{ $totalSisaBelumDigrade > 0 ? 'text-danger bg-danger-subtle' : 'text-muted opacity-50 bg-total-cell' }}">
                        {{ $totalSisaBelumDigrade ?: '0' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
    /* Kolom pertama (label) & terakhir (TOTAL) tetap terlihat saat tabel
       di-scroll horizontal (banyak kolom Size). */
    .table-breakdown .sticky-col-start {
        position: sticky;
        left: 0;
        z-index: 2;
        background-color: #fff;
    }
    .table-breakdown thead .sticky-col-start {
        background-color: #f8fafc;
    }
    .table-breakdown .sticky-col-end {
        position: sticky;
        right: 0;
        z-index: 2;
    }
    .table-breakdown thead .sticky-col-end {
        background-color: #f8fafc;
    }
    .table-breakdown tbody .bg-total-cell {
        background-color: #f8fafc;
    }
    .table-breakdown th,
    .table-breakdown td {
        font-size: 12.5px;
        white-space: nowrap;
    }
    .style-scrollbar {
        scrollbar-width: thin;
    }
    .style-scrollbar::-webkit-scrollbar {
        height: 6px;
    }
    .style-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
</style>