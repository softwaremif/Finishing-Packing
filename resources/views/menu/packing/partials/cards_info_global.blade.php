{{-- menu/packing/partials/cards_info_global.blade.php --}}
@php
    $plannedPct = $totalPcs > 0 ? rtrim(rtrim(number_format(($plannedPcs / $totalPcs) * 100, 2), '0'), '.') : 0;
    $packedPct  = $totalPcs > 0 ? rtrim(rtrim(number_format(($packedPcs / $totalPcs) * 100, 2), '0'), '.') : 0;

    // BARU -- FIX UTAMA: warna bar dinamis -- hijau kalau sudah 100%
    // (atau lebih), oranye kalau belum.
    $plannedBarColor = ($totalPcs > 0 && $plannedPcs >= $totalPcs) ? '#8bc63f' : '#f97316';
    $packedBarColor  = ($totalPcs > 0 && $packedPcs  >= $totalPcs) ? '#8bc63f' : '#f97316';
@endphp
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-lg-2">
        <div class="bg-white border rounded-3 p-3 h-100" style="border-color:#e5e7eb;">
            <div class="text-secondary mb-1" style="font-size:12px;">Total Breakdown</div>
            <div class="fw-bold text-dark" style="font-size:1.6rem;">
                {{ number_format($totalPcs) }}
            </div>
            <div class="text-secondary" style="font-size:11.5px;">
                {{ $totalColors }} warna &middot; {{ $totalSizes }} ukuran
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="bg-white border rounded-3 p-3 h-100" style="border-color:#e5e7eb;">
            <div class="text-secondary mb-1" style="font-size:12px;">Rencana Packing</div>
            <div class="mb-1">
                <span class="fw-bold text-dark" style="font-size:1.6rem;">
                    {{ number_format($plannedPcs) }}
                </span>
                <span class="text-secondary" style="font-size:12px;">
                    / {{ number_format($totalPcs) }}
                </span>
            </div>
            <div class="progress mb-1" style="height:5px;background:#e5e7eb;">
                <div class="progress-bar"
                    style="width:{{ min(100,$plannedPct) }}%;background:{{ $plannedBarColor }};">
                </div>
            </div>
            <div class="text-secondary" style="font-size:11.5px;">
                {{ $plannedPct }}% dari total order
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="bg-white border rounded-3 p-3 h-100" style="border-color:#e5e7eb;">
            <div class="text-secondary mb-1" style="font-size:12px;">Packing Selesai</div>
            <div class="mb-1">
                <span class="fw-bold text-dark" style="font-size:1.6rem;">
                    {{ number_format($packedPcs) }}
                </span>
                <span class="text-secondary" style="font-size:12px;">
                    / {{ number_format($totalPcs) }}
                </span>
            </div>
            <div class="progress mb-1" style="height:5px;background:#e5e7eb;">
                <div class="progress-bar"
                    style="width:{{ min(100,$packedPct) }}%;background:{{ $packedBarColor }};">
                </div>
            </div>
            <div class="text-secondary" style="font-size:11.5px;">
                {{ $packedPct }}% sudah dipacking
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
        <div class="bg-white border rounded-3 p-3 h-100" style="border-color:#e5e7eb;">
            <div class="text-secondary mb-1" style="font-size:12px;">Total Carton</div>
            <div class="fw-bold text-dark" style="font-size:1.6rem;">
                {{ number_format($totalCarton) }}
            </div>
            <div class="text-secondary" style="font-size:11.5px;">
                {{ $sealedCarton }} sudah segel &middot;
                {{ $openCarton }} belum segel
            </div>
        </div>
    </div>
    @if ($shortPcs > 0)
        <div class="col-12 col-lg-4">
    <div class="rounded-3 p-3 h-100 d-flex justify-content-center align-items-center gap-3 text-white"
        style="background:#f97316;">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
            style="width:40px;height:40px;background:rgba(255,255,255,.25);">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
            <div class="fw-bold" style="font-size:.95rem;">
                Masih kurang {{ number_format($shortPcs) }} pcs
            </div>
            <div style="font-size:12.5px;opacity:.95;">
                Tambahkan carton agar seluruh quantity order terpenuhi.
            </div>
        </div>
    </div>
</div>
    @endif
</div>