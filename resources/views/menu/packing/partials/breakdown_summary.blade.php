<div class="row g-4">
    {{-- ===================== SEKSI KIRI: BREAKDOWN SIZE ===================== --}}
    <div class="col-12 col-md-8">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
                <div class="fw-bold text-dark d-flex align-items-center" style="font-size: 15px;">
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
                                @if (!empty($dt2->secsz))
                                    <span class="text-muted fw-bold text-lowercase">({{ $dt2->secsz }})</span>
                                @endif
                            </th>
                            @foreach ($activeSizes as $i => $sz)
                                <th style="font-size: 12px;" class="text-dark">{{ $sz }}</th>
                            @endforeach
                            <th class="sticky-col-end fw-bold text-dark" style="font-size: 12px;">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Order Qty --}}
                        <tr>
                            <td class="text-start px-3 text-secondary sticky-col-start">Order Quantity</td>
                            @php $totOrder = 0; @endphp
                            @foreach ($activeSizes as $i => $sz)
                                <td class="text-dark">{{ $orderQty[$i] ?: '' }}</td>
                                @php $totOrder += $orderQty[$i]; @endphp
                            @endforeach
                            <td class="fw-bold text-dark sticky-col-end bg-total-cell">{{ $totOrder ?: '0' }}</td>
                        </tr>

                        {{-- Transfer Qty --}}
                        <tr>
                            <td class="text-start px-3 text-secondary sticky-col-start">Polibag Quantity</td>
                            @foreach ($activeSizes as $key => $size)
                                <td class="text-dark fw-medium">{{ $transQty[$key] ?? '0' }}</td>
                            @endforeach
                            <td class="fw-bold bg-light text-dark border-start">{{ $summary->pcs ?? 0 }}</td>
                        </tr>

                        {{-- Balance Transfer (+/-) = Transfer - Order --}}
                        <tr class="border-top border-light" style="border-width: 1.5px;">
                            <td class="text-start px-3 fw-bold text-secondary sticky-col-start">Balance (+/-)</td>
                            @foreach ($activeSizes as $i => $sz)
                                @php $val = $diffTransQty[$i] ?? 0; @endphp
                                <td
                                    class="fw-bold {{ $val < 0 ? 'text-danger' : ($val > 0 ? 'text-success' : 'text-muted opacity-50') }}">
                                    {{ $val > 0 ? '+' . $val : ($val < 0 ? $val : '') }}
                                </td>
                            @endforeach
                            <td
                                class="fw-bold sticky-col-end {{ $totDiffTrans < 0 ? 'text-danger bg-danger-subtle' : ($totDiffTrans > 0 ? 'text-success bg-success-subtle' : 'text-muted opacity-50 bg-total-cell') }}">
                                {{ $totDiffTrans > 0 ? '+' . $totDiffTrans : ($totDiffTrans != 0 ? $totDiffTrans : '0') }}
                            </td>
                        </tr>

                        {{-- Plan Pack Qty --}}
                        <tr>
                            <td class="text-start px-3 text-secondary sticky-col-start">Plan Pack Quantity</td>
                            @foreach ($activeSizes as $key => $size)
                                <td class="text-dark fw-medium">{{ $planQty[$key] ?? '0' }}</td>
                            @endforeach
                            <td class="fw-bold bg-light text-dark border-start">{{ $dt3->pcsp ?? 0 }}</td>
                        </tr>

                        {{-- Actual Pack Qty --}}
                        <tr>
                            <td class="text-start px-3 text-secondary sticky-col-start">Actual Pack Quantity</td>
                            @foreach ($activeSizes as $key => $size)
                                <td class="text-dark fw-medium">{{ $readyQty[$key] ?? '0' }}</td>
                            @endforeach
                            <td class="fw-bold bg-light text-dark border-start">{{ $dt3->pcs ?? 0 }}</td>
                        </tr>

                        {{-- Balance Pack (+/-) = Actual - Plan --}}
                        <tr class="border-top border-light" style="border-width: 1.5px;">
                            <td class="text-start px-3 fw-bold text-secondary sticky-col-start">Balance (+/-)</td>
                            @foreach ($activeSizes as $i => $sz)
                                @php $val = $diffPackQty[$i] ?? 0; @endphp
                                <td
                                    class="fw-bold {{ $val < 0 ? 'text-danger' : ($val > 0 ? 'text-success' : 'text-muted opacity-50') }}">
                                    {{ $val > 0 ? '+' . $val : ($val < 0 ? $val : '') }}
                                </td>
                            @endforeach
                            <td
                                class="fw-bold sticky-col-end {{ $totDiffPack < 0 ? 'text-danger bg-danger-subtle' : ($totDiffPack > 0 ? 'text-success bg-success-subtle' : 'text-muted opacity-50 bg-total-cell') }}">
                                {{ $totDiffPack > 0 ? '+' . $totDiffPack : ($totDiffPack != 0 ? $totDiffPack : '0') }}
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
                <span class="fw-bold text-dark">Summary CTN</span>
            </div>

            <div class="card-body p-0 table-responsive style-scrollbar">
                <table class="table text-center mb-0 align-middle">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <th class="py-2 text-secondary fw-semibold"
                                style="font-size: 10px; letter-spacing: 0.5px; text-transform: uppercase;">Plan
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
                            <td class="py-3 fw-bold text-dark fs-5 ">{{ $tctnp ?? 0 }}</td>
                            <td class="py-3 fw-bold text-dark fs-5 ">{{ $tctna ?? 0 }}</td>
                            <td
                                class="py-3 fw-bold fs-5  {{ $balanceCInteractive = $balanceCtn < 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">
                                {{ $balanceCtn > 0 ? '+' . $balanceCtn : $balanceCtn }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>