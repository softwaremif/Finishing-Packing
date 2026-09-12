<style>
    /* ============================================================
       BARU: restyle header, tab toggle, dan legend -- tampilan pill
       segmented control + legend flat (teks polos), sesuai referensi.
       ============================================================ */
    .matrix-header-title {
        font-size: 19px;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.2px;
        margin-bottom: 2px;
    }
    .matrix-header-subtitle {
        font-size: 12.5px;
        color: #94a3b8;
    }

    .matrix-tab-toggle {
        display: inline-flex;
        background: #f1f5f9;
        border-radius: 999px;
        padding: 3px;
        gap: 2px;
        flex-shrink: 0;
    }
    .matrix-tab-toggle button {
        border: none;
        background: transparent;
        border-radius: 999px;
        padding: 7px 20px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: background-color .15s ease, color .15s ease;
        white-space: nowrap;
    }
    .matrix-tab-toggle button:hover:not(.active) {
        color: #0f172a;
    }
    .matrix-tab-toggle button.active {
        background: #0f172a;
        color: #fff;
    }

    .matrix-legend-flat {
        display: flex;
        gap: 22px;
        flex-wrap: wrap;
        font-size: 13px;
        padding-top: 10px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
        margin-bottom: 14px;
    }
    .matrix-legend-flat .legend-item {
        cursor: pointer;
        color: #64748b;
        transition: color .15s ease;
        padding: 2px 0;
    }
    .matrix-legend-flat .legend-item:hover {
        color: #0f172a;
    }
    .matrix-legend-flat .legend-item.active {
        font-weight: 700;
        color: #0f172a;
    }

    .matrix-chip { /* dipertahankan supaya JS lama (kalau masih dipanggil di tempat lain) tidak rusak */
        display: none;
    }
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
    /* Planning (Plan vs Order) */
    #matrixTable td.plan-exact { background: #dcfce7; color: #15803d; }
    #matrixTable td.plan-short { background: #fee2e2; color: #991b1b; }
    #matrixTable td.plan-over  { background: #dbeafe; color: #1d4ed8; }
    #matrixTable td.plan-none  { background: #f1f5f9; color: #94a3b8; }
    #matrixTable td.plan-blank { color: #cbd5e1; background: transparent; }
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

    #matrixTable {
        border-collapse: separate;
    }
 
    #matrixTable .sticky-col-start {
        position: sticky;
        left: 0;
        z-index: 2;
        background: #fff;
    }
    #matrixTable thead .sticky-col-start {
        z-index: 3;
        background: #fff;
    }
 
    #matrixTable .sticky-col-end {
        position: sticky;
        right: 0;
        z-index: 2;
        background: #fff;
    }
    #matrixTable thead .sticky-col-end {
        z-index: 3;
        background: #fff;
    }
 
    /* Baris TOTAL punya background beda (#f8fafc) -- kolom sticky-nya
       harus ikut background yang SAMA, bukan putih, biar tidak "bocor"
       menampakkan konten di belakangnya saat di-scroll. */
    #matrixTable tr.matrix-total-row .sticky-col-start,
    #matrixTable tr.matrix-total-row .sticky-col-end {
        background: #f8fafc;
    }
 
    /* Garis pemisah halus supaya kelihatan ada "potongan" sticky --
       opsional, tapi membantu secara visual. */
    #matrixTable .sticky-col-start {
        border-right: 1px solid #f1f5f9;
    }
    #matrixTable .sticky-col-end {
        border-left: 1px solid #f1f5f9;
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
            {{-- FIX UTAMA: header di-restyle -- title & subtitle pakai class
                 baru, tombol tab jadi pill segmented control (.matrix-tab-toggle),
                 urutan tetap Planning (aktif) -> Packing -> Coverage (akhir). --}}
            <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="matrix-header-title">Order Coverage Matrix</div>
                    <div class="matrix-header-subtitle" id="matrixSubtitle">
                        Planning vs Order per Color &amp; Sec Size &mdash; klik sel untuk filter carton
                    </div>
                </div>
                <div class="matrix-tab-toggle" role="group">
                    <button type="button" class="active" id="matrixTabPlanning" onclick="setMatrixTab('planning')">Planning</button>
                    <button type="button" id="matrixTabPacking" onclick="setMatrixTab('packing')">Packing</button>
                    {{-- <button type="button" id="matrixTabCoverage" onclick="setMatrixTab('coverage')">Coverage</button> --}}
                </div>
            </div>

            <div class="card-body pt-0 pb-3">

                {{-- FIX: legend sekarang teks flat (.matrix-legend-flat),
                     bukan pill berwarna -- fungsi onclick TETAP SAMA
                     (toggleMatrixChip), cuma tampilannya berubah. --}}
                <div class="matrix-legend-flat d-none" id="matrixLegendCoverage">
                    <span class="legend-item" data-cat="exact" onclick="toggleMatrixChip(this)">Sesuai Order</span>
                    <span class="legend-item" data-cat="short" onclick="toggleMatrixChip(this)">Kurang dari Order</span>
                    <span class="legend-item" data-cat="over" onclick="toggleMatrixChip(this)">Melebihi Order</span>
                    <span class="legend-item" data-cat="none" onclick="toggleMatrixChip(this)">Belum Direncanakan</span>
                </div>

                <div class="matrix-legend-flat" id="matrixLegendPlanning">
                    <span class="legend-item" data-cat="plan-exact" onclick="toggleMatrixChip(this)">Plan Sesuai Order</span>
                    <span class="legend-item" data-cat="plan-short" onclick="toggleMatrixChip(this)">Plan Kurang dari Order</span>
                    <span class="legend-item" data-cat="plan-over" onclick="toggleMatrixChip(this)">Plan Melebihi Order</span>
                    <span class="legend-item" data-cat="plan-none" onclick="toggleMatrixChip(this)">Belum Ada Planning</span>
                </div>

                <div class="matrix-legend-flat d-none" id="matrixLegendPacking">
                    <span class="legend-item" data-cat="full" onclick="toggleMatrixChip(this)">Sudah Dipacking</span>
                    <span class="legend-item" data-cat="progress" onclick="toggleMatrixChip(this)">Sedang Dipacking</span>
                    <span class="legend-item" data-cat="empty" onclick="toggleMatrixChip(this)">Belum Dipacking</span>
                </div>

                <div class="table-responsive style-scrollbar">
                    <table class="table table-sm text-center align-middle mb-0" id="matrixTable">
                        <thead>
                            <tr style="font-size:11px; text-transform:uppercase; color:#94a3b8; letter-spacing:.4px;">
                                <th class="text-start sticky-col-start" style="min-width:170px;">Color \ Sec Size</th>
                                @foreach ($activeSizes as $i => $sz)
                                    <th style="min-width:75px;">{{ $sz }}</th>
                                @endforeach
                                <th class="sticky-col-end" style="min-width:90px;">Total</th>
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
                                    <td class="text-start sticky-col-start">
                                        <span class="matrix-color-dot" data-name="{{ $group['material'] ?? '-' }}"></span>
                                        <strong style="font-size:14px;">{{ $group['material'] ?? '-' }}</strong>
                                        @if (!empty($group['secsz']))
                                            <div class="text-muted" style="font-size:11px;">{{ $group['secsz'] }}</div>
                                        @endif
                                        @if (!empty($group['customer']))
                                            <div class="text-muted" style="font-size:10.5px;">{{ $group['customer'] }}</div>
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
                                            <div class="cov d-none">
                                                <span class="matrix-frac">{{ $trans }}/{{ $order }}</span>
                                                <div class="matrix-bar-track"><div class="matrix-bar-fill"></div></div>
                                            </div>
                                            <div class="planning">
                                                <span class="matrix-frac">{{ $plan }}/{{ $order }}</span>
                                                <div class="matrix-bar-track"><div class="matrix-bar-fill"></div></div>
                                            </div>
                                            <div class="pack d-none">
                                                <span class="matrix-frac">{{ $actual }}/{{ $plan }}</span>
                                                <div class="matrix-bar-track"><div class="matrix-bar-fill"></div></div>
                                            </div>
                                        </td>
                                    @endforeach
                                    <td class="fw-bold sticky-col-end">
                                        <span class="cov d-none">{{ $rowTransTotal }}/{{ $rowOrderTotal }}</span>
                                        <span class="planning">{{ $rowPlanTotal }}/{{ $rowOrderTotal }}</span>
                                        <span class="pack d-none">{{ $rowReadyTotal }}/{{ $rowPlanTotal }}</span>
                                    </td>
                                </tr>
                            @endforeach
                    
                            {{-- Baris TOTAL keseluruhan -- tambahkan class matrix-total-row --}}
                            <tr class="fw-bold matrix-total-row" style="background:#f8fafc;">
                                <td class="text-start sticky-col-start">TOTAL</td>
                                @foreach ($activeSizes as $i => $sz)
                                    <td>
                                        <span class="cov d-none">{{ $aggQty['transQty'][$i] ?? 0 }}/{{ $aggQty['orderQty'][$i] ?? 0 }}</span>
                                        <span class="planning">{{ $aggQty['planQty'][$i] ?? 0 }}/{{ $aggQty['orderQty'][$i] ?? 0 }}</span>
                                        <span class="pack d-none">{{ $aggQty['readyQty'][$i] ?? 0 }}/{{ $aggQty['planQty'][$i] ?? 0 }}</span>
                                    </td>
                                @endforeach
                                <td class="sticky-col-end">
                                    <span class="cov d-none">{{ $aggQty['totTrans'] ?? 0 }}/{{ $aggQty['totOrder'] ?? 0 }}</span>
                                    <span class="planning">{{ $aggQty['totPlan'] ?? 0 }}/{{ $aggQty['totOrder'] ?? 0 }}</span>
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
                        <div class="text-secondary text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Segel</div>
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
                    Plan = total carton yang sudah dibuat &middot; <br>
                    Segel = carton yang SUDAH disegel (Sealed) &middot;
                </div>
            </div>
        </div>
        </div>
    </div>
</div>