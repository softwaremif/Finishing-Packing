@php
    $totalCtnPlan   = 0;
    $totalCtnActual = 0;
    foreach ($groups as $g) {
        $totalCtnPlan   += $g['tctnp'] ?? 0;
        $totalCtnActual += $g['tctna'] ?? 0;
    }
    $totalCtnBalance = $totalCtnActual - $totalCtnPlan;

    // ============================================================
    // BARU: deteksi "run" grup yang BERURUTAN dan punya Sec Size /
    // Color yang SAMA -- supaya cell-nya digabung (rowspan), bukan
    // dibuat baru terus-menerus tiap grup.
    //
    // $secszRowspan[$idx] / $colorRowspan[$idx]:
    //   > 0  -> ini baris AWAL dari runtutan yang sama, render <td>
    //           dengan rowspan = (jumlah grup dalam runtutan) * 4
    //   0    -> baris ini SAMA dengan grup sebelumnya (sudah tercover
    //           rowspan grup awal), JANGAN render <td> sama sekali.
    // ============================================================
    $groupList = $groups instanceof \Illuminate\Support\Collection
        ? $groups->values()->all()
        : array_values($groups);

    $totalGroup   = count($groupList);
    $secszRowspan = array_fill(0, $totalGroup, 0);
    $colorRowspan = array_fill(0, $totalGroup, 0);

    for ($idx = 0; $idx < $totalGroup; $idx++) {
        $curSecsz = $groupList[$idx]['secsz'] ?? null;
        $curColor = $groupList[$idx]['material'] ?? null;
        $prevSecsz = $idx > 0 ? ($groupList[$idx - 1]['secsz'] ?? null) : null;
        $prevColor = $idx > 0 ? ($groupList[$idx - 1]['material'] ?? null) : null;

        if ($idx === 0 || $curSecsz !== $prevSecsz) {
            $run = 1;
            for ($j = $idx + 1; $j < $totalGroup && ($groupList[$j]['secsz'] ?? null) === $curSecsz; $j++) {
                $run++;
            }
            $secszRowspan[$idx] = $run * 4;
        }

        if ($idx === 0 || $curColor !== $prevColor) {
            $run = 1;
            for ($j = $idx + 1; $j < $totalGroup && ($groupList[$j]['material'] ?? null) === $curColor; $j++) {
                $run++;
            }
            $colorRowspan[$idx] = $run * 4;
        }
    }

    $ctnPlan    = $ctnSummary['ctnPlan']    ?? 0;
    $ctnActual  = $ctnSummary['ctnActual']  ?? 0;
    $ctnBalance = $ctnSummary['ctnBalance'] ?? 0;
@endphp

<div class="row g-4">
    {{-- ===================== SEKSI KIRI: BREAKDOWN SIZE (SATU TABEL) ===================== --}}
    <div class="col-12 col-md-8">
        <div class="card border-0 shadow-sm rounded-3 mb-2">
            <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
                <div class="fw-bold text-dark d-flex align-items-center" style="font-size: 15px;">
                    <span class="rounded me-2"
                        style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
                    Breakdown Size &amp; Qty
                </div>
            </div>
            <div class="card-body p-0 table-responsive style-scrollbar">
                <table class="table table-hover text-center mb-0 align-middle table-breakdown">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <th class="text-start fw-bold" style="font-size: 12px; min-width:100px;">Color</th>
                            <th class="text-start px-3 sticky-col-start fw-bold" style="font-size: 12px; min-width:90px;">Sec Size</th>
                            <th class="text-start px-3 fw-bold" style="font-size: 12px; min-width:170px;">Item</th>
                            @foreach ($activeSizes as $i => $sz)
                                <th class="text-dark" style="font-size: 13px; font-weight:700; min-width: 110px; padding: 12px 8px;">
                                    {{ $sz }}
                                </th>
                            @endforeach
                            <th class="sticky-col-end fw-bold text-dark" style="font-size: 13px; min-width: 110px;">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groups as $idx => $group)
                            {{-- Baris 1: Order / Polibag Qty --}}
                            <tr class="border-top" style="border-width: 2px; border-color: #e2e8f0 !important;">
                                {{-- Color: cuma render <td> kalau ini AWAL runtutan --}}
                                @if ($colorRowspan[$idx] > 0)
                                    <td rowspan="{{ $colorRowspan[$idx] }}" class="text-start align-middle">
                                        <span class="badge bg-primary-subtle text-primary-emphasis" style="font-size:11px;">
                                            {{ $group['material'] ?? '-' }}
                                        </span>
                                    </td>
                                @endif

                                {{-- Sec Size: cuma render <td> kalau ini AWAL runtutan --}}
                                @if ($secszRowspan[$idx] > 0)
                                    <td rowspan="{{ $secszRowspan[$idx] }}" class="text-start px-3 sticky-col-start fw-semibold align-middle">
                                        {{ $group['secsz'] ?: '-' }}
                                    </td>
                                @endif

                                <td class="text-start px-3 text-secondary">Order / Polibag Qty</td>
                                @foreach ($activeSizes as $i => $sz)
                                    <td class="text-dark fw-medium" style="font-size: 13.5px; padding: 10px 8px;">
                                        {{ $group['orderQty'][$i] ?? 0 }} / {{ $group['transQty'][$i] ?? 0 }}
                                    </td>
                                @endforeach
                                <td class="fw-bold text-dark sticky-col-end bg-total-cell" style="font-size: 13.5px;">
                                    {{ $group['totOrder'] ?? 0 }} / {{ $group['summary']->pcs ?? 0 }}
                                </td>
                            </tr>
                            {{-- Baris 2: Balance (Polibag - Order) --}}
                            <tr>
                                <td class="text-start px-3 fw-bold text-secondary">Balance (+/-)</td>
                                @foreach ($activeSizes as $i => $sz)
                                    @php $val = $group['diffTransQty'][$i] ?? 0; @endphp
                                    <td class="fw-bold {{ $val < 0 ? 'text-danger' : ($val > 0 ? 'text-success' : 'text-muted opacity-50') }}" style="font-size: 13px;">
                                        {{ $val > 0 ? '+' . $val : ($val < 0 ? $val : '') }}
                                    </td>
                                @endforeach
                                <td class="fw-bold sticky-col-end {{ $group['totDiffTrans'] < 0 ? 'text-danger bg-danger-subtle' : ($group['totDiffTrans'] > 0 ? 'text-success bg-success-subtle' : 'text-muted opacity-50 bg-total-cell') }}" style="font-size: 13px;">
                                    {{ $group['totDiffTrans'] > 0 ? '+' . $group['totDiffTrans'] : ($group['totDiffTrans'] != 0 ? $group['totDiffTrans'] : '0') }}
                                </td>
                            </tr>
                            {{-- Baris 3: Plan / Actual Pack Qty --}}
                            <tr class="border-top" style="border-color: #f1f5f9 !important;">
                                <td class="text-start px-3 text-secondary">Plan / Actual Qty</td>
                                @foreach ($activeSizes as $i => $sz)
                                    <td class="text-dark fw-medium" style="font-size: 13.5px; padding: 10px 8px;">
                                        {{ $group['planQty'][$i] ?? 0 }} / {{ $group['readyQty'][$i] ?? 0 }}
                                    </td>
                                @endforeach
                                <td class="fw-bold bg-light text-dark border-start" style="font-size: 13.5px;">
                                    {{ $group['dt3']->pcsp ?? 0 }} / {{ $group['dt3']->pcs ?? 0 }}
                                </td>
                            </tr>
                            {{-- Baris 4: Balance (Actual - Plan) --}}
                            <tr>
                                <td class="text-start px-3 fw-bold text-secondary">Balance (+/-)</td>
                                @foreach ($activeSizes as $i => $sz)
                                    @php $val = $group['diffPackQty'][$i] ?? 0; @endphp
                                    <td class="fw-bold {{ $val < 0 ? 'text-danger' : ($val > 0 ? 'text-success' : 'text-muted opacity-50') }}" style="font-size: 13px;">
                                        {{ $val > 0 ? '+' . $val : ($val < 0 ? $val : '') }}
                                    </td>
                                @endforeach
                                <td class="fw-bold sticky-col-end {{ $group['totDiffPack'] < 0 ? 'text-danger bg-danger-subtle' : ($group['totDiffPack'] > 0 ? 'text-success bg-success-subtle' : 'text-muted opacity-50 bg-total-cell') }}" style="font-size: 13px;">
                                    {{ $group['totDiffPack'] > 0 ? '+' . $group['totDiffPack'] : ($group['totDiffPack'] != 0 ? $group['totDiffPack'] : '0') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {{-- ===================== SEKSI KANAN: SUMMARY CTN (SATU TABEL, 1 BARIS PER GRUP) ===================== --}}
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 mb-2">
            <div class="card-header bg-white py-3 border-0 d-flex align-items-center" style="font-size: 15px;">
                <span class="rounded me-2"
                    style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
                <span class="fw-bold text-dark">Summary CTN</span>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-4">
                        <div class="text-secondary text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Plan</div>
                        <div class="fw-bold text-dark" style="font-size: 1.6rem;">{{ number_format($ctnPlan) }}</div>
                    </div>
                    <div class="col-4 border-start border-end">
                        <div class="text-secondary text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Aktual</div>
                        <div class="fw-bold text-dark" style="font-size: 1.6rem;">{{ number_format($ctnActual) }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-secondary text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Balance</div>
                        <div class="fw-bold {{ $ctnBalance < 0 ? 'text-danger' : 'text-success' }}" style="font-size: 1.6rem;">
                            {{ $ctnBalance > 0 ? '+' . $ctnBalance : $ctnBalance }}
                        </div>
                    </div>
                </div>
                <hr class="my-3">
                <div class="text-secondary" style="font-size: 11.5px;">
                    Plan = total carton yang sudah dibuat &middot;
                    Aktual = carton yang Actual-nya sudah lengkap (complete/sealed)
                </div>
            </div>
        </div>
        </div>
    </div>
</div>