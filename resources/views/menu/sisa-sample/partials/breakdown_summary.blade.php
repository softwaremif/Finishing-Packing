{{-- BREAKDOWN QUANTITY --}}
@php
    // Fallback aman: kalau partial ini dipanggil dari tempat lain yang tidak
    // mengirim variabel-variabel ini (misal endpoint AJAX terpisah), jangan fatal.
    $activeSizes = $activeSizes ?? [];
    $orderQty    = $orderQty ?? [];
    $readyQty    = $readyQty ?? [];
    $diffQty     = $diffQty ?? [];
    $totalBalance = $totalBalance ?? 0;
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
                        Size
                        @if (!empty($dt->secsz))
                            <span class="text-muted fw-bold text-lowercase">({{ $dt->secsz }})</span>
                        @endif
                    </th>
                    @foreach ($activeSizes as $size)
                        <th style="font-size: 12px;" class="text-dark">{{ $size }}</th>
                    @endforeach
                    <th class="sticky-col-end fw-bold text-dark" style="font-size: 12px;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-start px-3 text-secondary sticky-col-start">Order Quantity</td>
                    @foreach ($activeSizes as $key => $size)
                        <td class="text-dark fw-medium">{{ $orderQty[$key] ?? '0' }}</td>
                    @endforeach
                    <td class="fw-bold text-dark sticky-col-end bg-total-cell">{{ $dt->qty ?? 0 }}</td>
                </tr>
                <tr class="table-sm">
                    <td class="text-start px-3 text-secondary sticky-col-start">Ready Quantity</td>
                    @foreach ($activeSizes as $key => $size)
                        <td class="text-dark fw-medium">{{ $readyQty[$key] ?? '0' }}</td>
                    @endforeach
                    <td class="fw-bold bg-light text-dark border-start">{{ $summary->pcs ?? 0 }}</td>
                </tr>
                <tr class="border-top border-light" style="border-width: 1.5px;">
                    <td class="text-start px-3 fw-bold text-secondary sticky-col-start">Balance (+/-)</td>
                    @foreach ($activeSizes as $key => $size)
                        @php $val = $diffQty[$key] ?? 0; @endphp
                        <td
                            class="fw-bold {{ $val < 0 ? 'text-danger' : ($val > 0 ? 'text-success' : 'text-muted opacity-50') }}">
                            {{ $val > 0 ? '+' . $val : $val }}
                        </td>
                    @endforeach
                    <td class="fw-bold sticky-col-end {{ $totalBalance < 0 ? 'text-danger bg-danger-subtle' : ($totalBalance > 0 ? 'text-success bg-success-subtle' : 'text-muted opacity-50 bg-total-cell') }}">{{ $totalBalance > 0 ? '+' . $totalBalance : ($totalBalance != 0 ? $totalBalance : '0') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>