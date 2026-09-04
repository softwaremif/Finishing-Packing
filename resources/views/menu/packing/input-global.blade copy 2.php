@extends('layout.main')

@section('css_custom')
    <style>
        .page-wrap {
            padding: 16px;
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
    <div class="container-fluid py-4 px-4">

        <div
            class="d-flex flex-column flex-md-row align-items-md-center justify-content-between border-bottom pb-3 mb-4 gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:void(0)" onclick="goBack()"
                    class="btn btn-icon-custom d-inline-flex align-items-center justify-content-center shadow-sm border bg-white text-secondary rounded-circle"
                    style="width: 38px; height: 38px; transition: all 0.2s ease;" title="Kembali ke Daftar Data OP">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold text-dark mb-0" style="font-size: 1.15rem; letter-spacing: -0.3px;">
                        Packing list - Style {{ $dt2->style ?? '-' }}
                    </h4>
                    <span>OP {{ $op }} &middot; Season {{ $dt2->season ?? '-' }} &middot; Buyer:
                        {{ $dt2->buyer ?? '-' }} &middot; PO {{ $po }}</span>
                </div>
            </div>
        </div>

        @php
            $totalPcs = array_sum($orderQty ?? []);
            $plannedPcs = array_sum($planQty ?? []);
            $packedPcs = array_sum($readyQty ?? []);
            $shortPcs = max(0, $totalPcs - $plannedPcs);

            $plannedPct = $totalPcs > 0 ? round(($plannedPcs / $totalPcs) * 100) : 0;
            $packedPct = $totalPcs > 0 ? round(($packedPcs / $totalPcs) * 100) : 0;

            $totalColors = $totalColors ?? 0;
            $totalSizes = count($activeSizes ?? []);

            $totalCarton = $totalCarton ?? 0;
            $sealedCarton = $sealedCarton ?? 0;
            $openCarton = $openCarton ?? 0;
        @endphp

        <div class="row g-3 mb-4">

            <div class="col-6 col-md-3 col-lg-2">
                <div class="bg-white border rounded-3 p-3 h-100" style="border-color:#e5e7eb;">
                    <div class="text-secondary mb-1" style="font-size:12px;">Order breakdown</div>
                    <div class="fw-bold text-dark" style="font-size:1.6rem;">{{ number_format($totalPcs) }}</div>
                    <div class="text-secondary" style="font-size:11.5px;">
                        pcs across {{ $totalColors }} colors &middot; {{ $totalSizes }} sizes
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <div class="bg-white border rounded-3 p-3 h-100" style="border-color:#e5e7eb;">
                    <div class="text-secondary mb-1" style="font-size:12px;">Planned into cartons</div>
                    <div class="mb-1">
                        <span class="fw-bold text-dark" style="font-size:1.6rem;">{{ number_format($plannedPcs) }}</span>
                        <span class="text-secondary" style="font-size:12px;">/ {{ number_format($totalPcs) }}</span>
                    </div>
                    <div class="progress mb-1" style="height:5px; background-color:#e5e7eb;">
                        <div class="progress-bar" role="progressbar"
                            style="width:{{ min(100, $plannedPct) }}%; background-color:#3b82f6;"></div>
                    </div>
                    <div class="text-secondary" style="font-size:11.5px;">{{ $plannedPct }}% of order covered</div>
                </div>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <div class="bg-white border rounded-3 p-3 h-100" style="border-color:#e5e7eb;">
                    <div class="text-secondary mb-1" style="font-size:12px;">Packed &amp; verified</div>
                    <div class="mb-1">
                        <span class="fw-bold text-dark" style="font-size:1.6rem;">{{ number_format($packedPcs) }}</span>
                        <span class="text-secondary" style="font-size:12px;">/ {{ number_format($totalPcs) }}</span>
                    </div>
                    <div class="progress mb-1" style="height:5px; background-color:#e5e7eb;">
                        <div class="progress-bar" role="progressbar"
                            style="width:{{ min(100, $packedPct) }}%; background-color:#22c55e;"></div>
                    </div>
                    <div class="text-secondary" style="font-size:11.5px;">{{ $packedPct }}% of order packed</div>
                </div>
            </div>

            <div class="col-6 col-md-3 col-lg-2">
                <div class="bg-white border rounded-3 p-3 h-100" style="border-color:#e5e7eb;">
                    <div class="text-secondary mb-1" style="font-size:12px;">Cartons</div>
                    <div class="fw-bold text-dark" style="font-size:1.6rem;">{{ number_format($totalCarton) }}</div>
                    <div class="text-secondary" style="font-size:11.5px;">
                        {{ $sealedCarton }} sealed &middot; {{ $openCarton }} open
                    </div>
                </div>
            </div>

            @if ($shortPcs > 0)
                <div class="col-12 col-lg-4">
                    <div class="rounded-3 p-3 h-100 d-flex align-items-start gap-3 text-white"
                        style="background-color:#f97316;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:34px; height:34px; background-color:rgba(255,255,255,.25);">
                            <i class="fas fa-exclamation-triangle" style="font-size:14px;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:0.95rem;">
                                {{ number_format($shortPcs) }} pcs short of order
                            </div>
                            <div style="font-size:12.5px; opacity:.95;">
                                Add cartons to cover the remaining breakdown
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <div id="breakdownSummaryWrapper">
            @include('menu.packing.partials.breakdown_summary_global')
        </div>

        {{-- ===================== DETAIL PACK TABLE (GLOBAL, LINTAS SEMUA POPK) ===================== --}}
        {{-- ===================== DETAIL PACK TABLE (GLOBAL, LINTAS SEMUA POPK) — VERSI CARD ===================== --}}
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="rounded me-2"
                        style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
                    <strong class="text-dark"> Detail Packing / Carton </strong>
                </div>
            </div>
            <div class="card-body">
                <div class="px-0 py-2 border-bottom bg-white mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <!-- Filter -->
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="input-group" style="width:260px;">
                                <span class="input-group-text search">
                                    <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18"
                                        alt="Search">
                                </span>
                                <input type="text" class="form-control search" id="searchPackingGlobal"
                                    placeholder="Search Barcode / No CTN">
                            </div>
                            <input id="filterSizeGlobal" style="width:170px;">
                            <input id="filterColorGlobal" style="width:170px;">
                            <input id="filterSecszGlobal" style="width:170px;">
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

                <div id="packingCardsWrapper" class="row g-3"></div>

                <div id="packingCardsPager" class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <div class="text-secondary" style="font-size:12.5px;" id="packingCardsInfo">-</div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPackingCardsPrev" onclick="changePackingCardsPage(-1)">
                            <i class="fas fa-chevron-left"></i> Prev
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnPackingCardsNext" onclick="changePackingCardsPage(1)">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>



    </div>
@endsection

@section('js_custom')
    <script>
        function reloadBreakdownSummary() {
            $.get("{{ route('packing.breakdownSummaryGlobal') }}", {
                po: @json($po),
                op: @json($op),
                poref: @json($poref ?? null),
                mif: @json($mif)
            }, function(html) {
                $('#breakdownSummaryWrapper').html(html);
            });
        }
    </script>

    <script>
        window.activeSizesGlobal = @json($activeSizes);
        window.colorListGlobal   = @json($colorList ?? []);
        window.secszListGlobal   = @json($secszList ?? []);

        let packingCardsPage  = 1;
        const packingCardsRowsPerPage = 12;
        let packingCardsTotal = 0;

        $(function () {
            initFilterColorGlobal();
            initFilterSizeGlobalCombobox();
            initFilterSecszGlobal();
            loadPackingCards(1);
        });

        function loadPackingCards(page) {
            packingCardsPage = page;

            $.get("{{ route('packing.list.detail.global') }}", {
                po: @json($po),
                op: @json($op),
                poref: @json($poref ?? null),
                mif: @json($mif),
                page: page,
                rows: packingCardsRowsPerPage,
                search: $('#searchPackingGlobal').val(),
                size: $('#filterSizeGlobal').combobox('getValue'),
                color: $('#filterColorGlobal').combobox('getValue'),
                secsz: $('#filterSecszGlobal').combobox('getValue')
            }, function (data) {
                packingCardsTotal = data.total || 0;
                renderPackingCards(data.rows || []);
                updatePackingCardsPager();
            });
        }

        function changePackingCardsPage(delta) {
            const totalPages = Math.max(1, Math.ceil(packingCardsTotal / packingCardsRowsPerPage));
            const next = packingCardsPage + delta;
            if (next < 1 || next > totalPages) return;
            loadPackingCards(next);
        }

        function updatePackingCardsPager() {
            const totalPages = Math.max(1, Math.ceil(packingCardsTotal / packingCardsRowsPerPage));
            $('#packingCardsInfo').text(`Halaman ${packingCardsPage} dari ${totalPages} — ${packingCardsTotal} carton`);
            $('#btnPackingCardsPrev').prop('disabled', packingCardsPage <= 1);
            $('#btnPackingCardsNext').prop('disabled', packingCardsPage >= totalPages);
        }

        let packingGlobalSearchTimer = null;
        $('#searchPackingGlobal').on('keyup', function () {
            clearTimeout(packingGlobalSearchTimer);
            packingGlobalSearchTimer = setTimeout(function () {
                loadPackingCards(1);
            }, 300);
        });

        function reloadPackingGlobal() {
            loadPackingCards(1);
        }

        function initFilterColorGlobal() {
            let colorData = [{ value: '', text: 'Semua Color' }];
            (window.colorListGlobal || []).forEach(function (c) {
                colorData.push({ value: c, text: c });
            });

            $('#filterColorGlobal').combobox({
                data: colorData,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function () {
                    reloadPackingGlobal();
                }
            });
        }

        function initFilterSizeGlobalCombobox() {
            let sizeData = [{ value: '', text: 'Semua Size' }];
            Object.keys(window.activeSizesGlobal || {}).forEach(function (i) {
                sizeData.push({ value: i, text: window.activeSizesGlobal[i] });
            });

            $('#filterSizeGlobal').combobox({
                data: sizeData,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function () {
                    reloadPackingGlobal();
                }
            });
        }

        function initFilterSecszGlobal() {
            let secszData = [{ value: '', text: 'Semua Sec Size' }];
            (window.secszListGlobal || []).forEach(function (s) {
                secszData.push({ value: s, text: s });
            });

            $('#filterSecszGlobal').combobox({
                data: secszData,
                valueField: 'value',
                textField: 'text',
                value: '',
                editable: false,
                panelHeight: 'auto',
                onChange: function () {
                    reloadPackingGlobal();
                }
            });
        }

        // ============================================================
        // RENDER CARD -- pengganti EasyUI datagrid.
        // ============================================================
        function renderPackingCards(rows) {
            const wrapper = $('#packingCardsWrapper');
            wrapper.empty();

            if (!rows.length) {
                wrapper.html(`
                    <div class="col-12">
                        <div class="text-center py-5">
                            <img src="{{ asset('public/css/images/no-data-6.svg') }}" width="140">
                            <div class="mt-2 fw-semibold text-dark">No Data Found</div>
                            <div class="text-secondary" style="font-size:12px;">Try changing filter</div>
                        </div>
                    </div>
                `);
                return;
            }

            rows.forEach(function (row) {
                wrapper.append(buildPackingCardHtml(row));
            });
        }

        function buildPackingCardHtml(row) {
            const activeIdx = Object.keys(window.activeSizesGlobal || {});
            let totalPlan = 0;
            let totalActual = 0;
            let sizeLines = [];

            activeIdx.forEach(function (i) {
                const plan = Number(row[`qtyp${i}`] || 0);
                if (plan <= 0) return;

                const actual = Number(row[`qty${i}`] || 0);
                totalPlan   += plan;
                totalActual += actual;

                const pct = Math.min(100, Math.round((actual / plan) * 100));
                const barColor = actual >= plan ? '#22c55e' : (actual > 0 ? '#3b82f6' : '#e5e7eb');

                sizeLines.push(`
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="text-dark" style="font-size:13px;">
                            ${row.material ?? '-'} &middot; ${window.activeSizesGlobal[i]}
                        </div>
                        <div class="d-flex align-items-center gap-2" style="min-width:150px;">
                            <div class="progress flex-grow-1" style="height:6px;background:#e5e7eb;">
                                <div class="progress-bar" style="width:${pct}%;background:${barColor};"></div>
                            </div>
                            <span class="text-secondary" style="font-size:12px;min-width:42px;text-align:right;">${actual}/${plan}</span>
                        </div>
                    </div>
                `);
            });

            const isSegel     = Number(row.segel) === 1;
            const isComplete  = totalPlan > 0 && totalActual >= totalPlan;
            const overallPct  = totalPlan > 0 ? Math.min(100, Math.round((totalActual / totalPlan) * 100)) : 0;
            const overallColor = (isSegel || isComplete) ? '#22c55e' : (totalActual > 0 ? '#3b82f6' : '#e5e7eb');

            let statusBadge;
            if (isSegel) {
                statusBadge = `<span class="badge bg-success-subtle text-success-emphasis fw-semibold">Sealed</span>`;
            } else if (isComplete) {
                statusBadge = `<span class="badge bg-success-subtle text-success-emphasis fw-semibold">Complete</span>`;
            } else if (totalActual > 0) {
                statusBadge = `<span class="badge bg-primary fw-semibold">Packing</span>`;
            } else {
                statusBadge = `<span class="badge bg-secondary-subtle text-secondary-emphasis fw-semibold">Planned</span>`;
            }

            const assortedBadge = sizeLines.length > 1
                ? `<span class="badge bg-light text-secondary border fw-normal">Assorted</span>`
                : '';

            const scanDisabled       = isSegel || isComplete;
            const markPackedDisabled = isSegel || isComplete;
            const sealDisabled       = isSegel || !isComplete;

            return `
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="border rounded-3 p-3 h-100 bg-white" style="border-color:#e5e7eb;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <strong style="font-size:15px;">CTN - ${row.carton ?? '-'}</strong>
                                ${assortedBadge}
                                ${statusBadge}
                            </div>
                            <div class="d-flex align-items-center gap-2 text-secondary">
                                <i class="fas fa-pen small" style="cursor:pointer;" title="Edit"
                                    onclick="showToast('info','Edit carton (mode Global) belum tersedia.')"></i>
                                <i class="fas fa-copy small" style="cursor:pointer;" title="Copy"
                                    onclick="showToast('info','Copy carton (mode Global) belum tersedia.')"></i>
                                <i class="fas fa-trash small" style="cursor:pointer;" title="Delete"
                                    onclick="showToast('info','Delete carton (mode Global) belum tersedia.')"></i>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="progress flex-grow-1 me-3" style="height:7px;background:#e5e7eb;">
                                <div class="progress-bar" style="width:${overallPct}%;background:${overallColor};"></div>
                            </div>
                            <div class="text-nowrap" style="font-size:13px;">
                                <strong>${totalActual}</strong> / ${totalPlan} pcs
                                <span class="text-secondary">${overallPct}%</span>
                            </div>
                        </div>

                        <div class="mt-3">
                            ${sizeLines.join('')}
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1"
                                ${scanDisabled ? 'disabled' : ''}
                                onclick="showToast('info','Scan +1 (mode Global) belum tersedia.')">
                                Scan +1
                            </button>
                            <button type="button" class="btn btn-dark btn-sm flex-grow-1"
                                ${markPackedDisabled ? 'disabled' : ''}
                                onclick="showToast('info','Mark packed (mode Global) belum tersedia.')">
                                Mark packed
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-sm flex-grow-1"
                                ${sealDisabled ? 'disabled' : ''}
                                onclick="showToast('info','Seal (mode Global) belum tersedia.')">
                                Seal
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
    </script>

    <script>
        // ============================================================
        // FORMATTER -- WAJIB ada di halaman ini karena dipakai sebagai
        // formatter di columns: dgPackingGlobal. Kalau salah satu dari
        // fungsi ini tidak terdefinisi, seluruh script init datagrid akan
        // GAGAL TOTAL (ReferenceError) dan tabel tidak akan pernah muncul.
        // ============================================================
        function formatAction(value, row, index) {
            let isShipped = row.status == 5;
            let isSegel = Number(row.segel) === 1;

            if (isShipped || isSegel) return '';

            return `
            <a href="javascript:void(0)" onclick="showToast('info','Edit carton (mode Global) belum tersedia.')"
                class="action-btn" title="Edit">
                <i class="fas fa-edit"></i>
            </a>
        `;
        }

        function isRowComplete(row) {
            const activeIdx = Object.keys(window.activeSizesGlobal || {});
            let hasAnyPlan = false;

            const semuaSama = activeIdx.every(function(i) {
                const plan = Number(row[`qtyp${i}`] || 0);
                if (plan <= 0) return true;
                hasAnyPlan = true;
                const actual = Number(row[`qty${i}`] || 0);
                return actual === plan;
            });

            return hasAnyPlan && semuaSama;
        }

        function formatSegel(value, row) {
            if (Number(row.segel) === 1) {
                return `<img src="{{ asset('public/css/images/Segel_carton.png') }}" width="28" title="Sudah Disegel">`;
            }
            if (isRowComplete(row)) {
                return `<img src="{{ asset('public/css/images/Complete_carton.png') }}" width="28" title="Actual = Plan (Complete)">`;
            }
            return '<span class="text-muted">-</span>';
        }

        function formatPA(value, row) {
            return `
            <div style="line-height:18px;text-align:center;">
                <div style="font-weight:bold;border-bottom:1px solid #dcdcdc;">P</div>
                <div style="font-weight:bold;">A</div>
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
            let cls = balance < 0 ? 'color:#dc3545;font-weight:bold' : 'color:#198754;font-weight:bold';
            return `<span style="${cls}">${balance}</span>`;
        }

        @foreach ($activeSizes as $i => $sz)
            function formatSize{{ $i }}(value, row) {
                return `
                <div style="display:flex;flex-direction:column;height:40px;text-align:center;">
                    <div style="flex:1;border-bottom:1px solid #d9d9d9;color:#6c757d;">
                        ${row.qtyp{{ $i }} || ''}
                    </div>
                    <div style="flex:1;font-weight:bold;color:#212529;">
                        ${row.qty{{ $i }} ?? '&nbsp;'}
                    </div>
                </div>
            `;
            }
        @endforeach

        function rowStylerPacking(index, row) {
            if (row.status == 5) return 'datagrid-row-packing-shipped';
            return '';
        }

        // Dipakai tombol back & 2 tombol di toolbar Detail Packing -- juga
        // belum ada di halaman ini sama sekali.
        function goBack() {
            if (document.referrer && document.referrer.indexOf('/packing') !== -1) {
                window.history.back();
            } else {
                window.location.href = "{{ route('packing.index') }}";
            }
        }

        function openUrutkanCtnModal() {
            showToast('info', 'Fitur Urutkan CTN untuk mode Global belum tersedia.');
        }

        function openPackingModal() {
            showToast('info', 'Fitur Add Packing untuk mode Global belum tersedia.');
        }
    </script>
@endsection
