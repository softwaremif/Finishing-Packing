<style>
    .matrix-chip {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all .15s ease;
    }
    .matrix-chip:hover { border-color: #cbd5e1; }
    .matrix-chip.active { background: #0f172a; border-color: #0f172a; color: #fff; }

    .matrix-color-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 3px;
        margin-right: 4px;
        background: #64748b;
    }

    #matrixTable td.matrix-cell {
        cursor: pointer;
        font-weight: 600;
        font-size: 12.5px;
        border-radius: 6px;
        transition: opacity .15s ease, outline .1s ease;
    }
    #matrixTable td.matrix-cell:hover {
        outline: 2px solid #94a3b8;
        outline-offset: -2px;
    }
    #matrixTable td.matrix-cell.matrix-dim {
        opacity: .2;
    }

    /* Coverage */
    #matrixTable td.cov-exact { background: #dcfce7; color: #15803d; }
    #matrixTable td.cov-short { background: #fee2e2; color: #991b1b; }
    #matrixTable td.cov-over  { background: #dbeafe; color: #1d4ed8; }
    #matrixTable td.cov-none  { background: #f1f5f9; color: #94a3b8; }
    #matrixTable td.cov-blank { color: #cbd5e1; background: transparent; }

    /* Packing */
    #matrixTable td.pack-full     { background: #dcfce7; color: #15803d; }
    #matrixTable td.pack-progress { background: #dbeafe; color: #1d4ed8; }
    #matrixTable td.pack-empty    { background: #f8fafc; color: #94a3b8; }
    #matrixTable td.pack-blank    { color: #cbd5e1; background: transparent; }

    #matrixTable .matrix-frac {
        display: block;
        margin-bottom: 3px;
    }
    #matrixTable .matrix-bar-track {
        height: 4px;
        border-radius: 3px;
        background: rgba(148, 163, 184, .25);
        overflow: hidden;
    }
    #matrixTable .matrix-bar-fill {
        display: block;
        height: 100%;
        border-radius: 3px;
        width: 0%;
        transition: width .2s ease;
    }
</style>
@php
    $totalCtnPlan   = 0;
    $totalCtnActual = 0;
    foreach ($groups as $g) {
        $totalCtnPlan   += $g['tctnp'] ?? 0;
        $totalCtnActual += $g['tctna'] ?? 0;
    }
    $totalCtnBalance = $totalCtnActual - $totalCtnPlan;

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
            <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <div class="fw-bold text-dark" style="font-size:15px;">Order Coverage Matrix</div>
                    <div class="text-secondary" id="matrixSubtitle" style="font-size:12px;">
                        Order vs Polibag per Color &amp; Sec Size &mdash; klik sel untuk filter carton
                    </div>
                </div>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-dark active" id="matrixTabCoverage" onclick="setMatrixTab('coverage')">Coverage</button>
                    <button type="button" class="btn btn-outline-secondary" id="matrixTabPacking" onclick="setMatrixTab('packing')">Packing</button>
                </div>
            </div>
    
            <div class="card-body pt-3 pb-3">
    
                {{-- Chip filter -- Cakupan Order --}}
                <div class="d-flex gap-2 flex-wrap mb-3" id="matrixLegendCoverage">
                    <span class="matrix-chip" data-cat="exact" onclick="toggleMatrixChip(this)">
                        Sesuai Order
                    </span>

                    <span class="matrix-chip" data-cat="short" onclick="toggleMatrixChip(this)">
                        Kurang dari Order
                    </span>

                    <span class="matrix-chip" data-cat="over" onclick="toggleMatrixChip(this)">
                        Melebihi Order
                    </span>

                    <span class="matrix-chip" data-cat="none" onclick="toggleMatrixChip(this)">
                        Belum Direncanakan
                    </span>
                </div>

                {{-- Chip filter -- Status Packing --}}
                <div class="d-flex gap-2 flex-wrap mb-3 d-none" id="matrixLegendPacking">
                    <span class="matrix-chip" data-cat="full" onclick="toggleMatrixChip(this)">
                        Sudah Dipacking
                    </span>

                    <span class="matrix-chip" data-cat="progress" onclick="toggleMatrixChip(this)">
                        Sedang Dipacking
                    </span>

                    <span class="matrix-chip" data-cat="empty" onclick="toggleMatrixChip(this)">
                        Belum Dipacking
                    </span>
                </div>
    
                <div class="table-responsive style-scrollbar">
                    <table class="table table-sm text-center align-middle mb-0" id="matrixTable">
                        <thead>
                            <tr style="font-size:11px; text-transform:uppercase; color:#94a3b8; letter-spacing:.4px;">
                                <th class="text-start" style="min-width:170px;">Color \ Sec Size</th>
                                @foreach ($activeSizes as $i => $sz)
                                    <th style="min-width:75px;">{{ $sz }}</th>
                                @endforeach
                                <th style="min-width:90px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groups as $group)
                                @php
                                    $rowOrderTotal = array_sum($group['orderQty'] ?? []);
                                    $rowTransTotal = array_sum($group['transQty'] ?? []);
                                    $rowPlanTotal  = array_sum($group['planQty'] ?? []);
                                    $rowReadyTotal = array_sum($group['readyQty'] ?? []);
                                @endphp
                                <tr>
                                    <td class="text-start">
                                        <span class="matrix-color-dot" data-name="{{ $group['material'] ?? '-' }}"></span>
                                        <strong>{{ $group['material'] ?? '-' }}</strong>
                                        @if (!empty($group['secsz']))
                                            <div class="text-muted" style="font-size:11px;">{{ $group['secsz'] }}</div>
                                        @endif
                                    </td>
                                    @foreach ($activeSizes as $i => $sz)
                                        @php
                                            $order  = $group['orderQty'][$i] ?? 0;
                                            $trans  = $group['transQty'][$i] ?? 0;
                                            $plan   = $group['planQty'][$i] ?? 0;
                                            $actual = $group['readyQty'][$i] ?? 0;
                                        @endphp
                                        <td class="matrix-cell"
                                            data-material="{{ $group['material'] ?? '' }}"
                                            data-secsz="{{ $group['secsz'] ?? '' }}"
                                            data-size="{{ $i }}"
                                            data-order="{{ $order }}"
                                            data-trans="{{ $trans }}"
                                            data-plan="{{ $plan }}"
                                            data-actual="{{ $actual }}"
                                            onclick="onMatrixCellClick(this)">
                                            <div class="cov">
                                                <span class="matrix-frac">{{ $trans }}/{{ $order }}</span>
                                                <div class="matrix-bar-track"><div class="matrix-bar-fill"></div></div>
                                            </div>
                                            <div class="pack d-none">
                                                <span class="matrix-frac">{{ $actual }}/{{ $plan }}</span>
                                                <div class="matrix-bar-track"><div class="matrix-bar-fill"></div></div>
                                            </div>
                                        </td>
                                    @endforeach
                                    <td class="fw-bold">
                                        <span class="cov">{{ $rowTransTotal }}/{{ $rowOrderTotal }}</span>
                                        <span class="pack d-none">{{ $rowReadyTotal }}/{{ $rowPlanTotal }}</span>
                                    </td>
                                </tr>
                            @endforeach
    
                            {{-- Baris TOTAL keseluruhan --}}
                            <tr class="fw-bold" style="background:#f8fafc;">
                                <td class="text-start">TOTAL</td>
                                @foreach ($activeSizes as $i => $sz)
                                    <td>
                                        <span class="cov">{{ $aggQty['transQty'][$i] ?? 0 }}/{{ $aggQty['orderQty'][$i] ?? 0 }}</span>
                                        <span class="pack d-none">{{ $aggQty['readyQty'][$i] ?? 0 }}/{{ $aggQty['planQty'][$i] ?? 0 }}</span>
                                    </td>
                                @endforeach
                                <td>
                                    <span class="cov">{{ $aggQty['totTrans'] ?? 0 }}/{{ $aggQty['totOrder'] ?? 0 }}</span>
                                    <span class="pack d-none">{{ $aggQty['totReady'] ?? 0 }}/{{ $aggQty['totPlan'] ?? 0 }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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