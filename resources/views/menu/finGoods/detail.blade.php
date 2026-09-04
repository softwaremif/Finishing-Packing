@extends('layout.main')

@section('content')
<style>
    :root {
        --primary-color: #4f46e5;
        --primary-light: rgba(79, 70, 229, 0.1);
        --text-main: #0f172a;
        --text-muted: #64748b;
        --bg-header: #f8fafc;
        --border-color: #e2e8f0;
    }

    a i { transition: transform 0.2s ease; }
    a:hover i { transform: scale(1.2) translateX(-2px); }

    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .info-table td {
        padding: 5px 6px;
        vertical-align: top;
        border-bottom: 1px dashed #e2e8f0;
    }

    .print-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 11px;
    }
    .print-table th, .print-table td {
        border: 1px solid #94a3b8;
        padding: 6px 4px;
        text-align: center;
    }
    .print-table th {
        background-color: #cbd5e1 !important;
        font-weight: bold;
    }
    .print-table td.cell-cartons { text-align: left; }

    .ftitle {
        font-size: 14px;
        font-weight: bold;
        text-transform: uppercase;
        margin: 15px 0 8px 0;
        color: #0f172a;
        border-left: 4px solid #475569;
        padding-left: 8px;
        text-align: left;
    }

    .carton-input {
        border: 1px solid #cbd5e1;
        border-radius: 3px;
        padding: 2px 4px;
        margin: 2px;
        font-family: inherit;
        font-size: 10px;
        text-align: center;
        display: inline-block;
    }
    .carton-input[data-group="green"],
    .carton-input[data-group="gray"] {
        cursor: pointer;
    }
    .carton-input.carton-selected {
        outline: 3px solid #1d4ed8 !important;
        outline-offset: 1px;
        box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.35) !important;
        font-weight: bold;
        transform: scale(1.06);
    }

    .sticky-order-bar {
        display: none;
        position: fixed;
        top: 58px;
        left: 0;
        right: 0;
        z-index: 1030;
        background: #359DD9;
        color: #fff;
    }
    .sticky-order-inner {
        height: 44px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 16px;
    }
    .sticky-action {
        cursor: pointer;
        margin-left: 10px;
        font-weight: 500;
        opacity: .9;
    }
    .sticky-action:hover { opacity: 1; }
</style>

@php
    // (tidak ada helper tambahan yang dibutuhkan saat ini)
@endphp

{{-- Menu biru: tampil saat ada carton terpilih.
     hijau  -> Proses Inspect + Proses Shipment
     abu2   -> Kembalikan ke Stuffing
     biru   -> tidak bisa dipilih (menu tidak muncul) --}}
<div id="stickTopBar" class="sticky-order-bar">
    <div class="sticky-order-inner">
        <div>
            <strong>
                <span id="selectedCount">0</span>
                item terpilih
            </strong>
        </div>
        <div>
            <span class="sticky-action" id="btnProsesInspect" data-action="inspect">
                Proses Inspect
            </span>
            <span class="sticky-action" id="btnProsesShipment" data-action="shipment">
                Proses Shipment
            </span>
            <span class="sticky-action" id="btnKembalikanStuffing" data-action="stuffing">
                Kembalikan ke Stuffing
            </span>
            <span class="sticky-action" id="btnCloseSelection">
                Close
            </span>
        </div>
    </div>
</div>

<div class="container-fluid py-4 px-4">

    <x-details.header
        title="Detail Stuffing"
        :back-url="route('finGoods.index')"
    />

    {{-- Info PO -- representatif, lintas semua place/color dalam PO+OP ini. --}}
    {{-- <table class="info-table">
        <tr>
            <td width="12%"><strong>Customer</strong></td>
            <td width="18%">
                @forelse ($customersList as $c)
                    {{ $c }}@if (!$loop->last), @endif
                @empty
                    -
                @endforelse
            </td>
            <td width="10%"><strong>Season</strong></td>
            <td width="15%">{{ $dt2->season ?? '-' }}</td>
            <td width="10%"><strong>PO.No</strong></td>
            <td width="15%">{{ $dt2->POno ?? '-' }}</td>
            <td width="10%"><strong>OP#</strong></td>
            <td width="15%">{{ $dt2->OP ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Buyer</strong></td>
            <td>{{ $dt2->buyer ?? '-' }}</td>
            <td><strong>Style</strong></td>
            <td>{{ $dt2->style ?? '-' }}</td>
            <td><strong>Color</strong></td>
            <td>
                @forelse ($materialsList as $m)
                    {{ $m }}@if (!$loop->last), @endif
                @empty
                    -
                @endforelse
            </td>
            <td><strong>Silhouette</strong></td>
            <td>{{ $dt2->silhouette ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Total CTN</strong></td>
            <td><strong>{{ $totalCtn }}</strong></td>
            <td><strong>MEAS</strong></td>
            <td colspan="5">
                @forelse ($measList as $x)
                    {{ $x }}@if (!$loop->last), @endif
                @empty
                    -
                @endforelse
            </td>
        </tr>
        <tr>
            <td><strong>Last Updated</strong></td>
            <td colspan="7">{{ $dtp ? $dtp->tanggal . ' ' . $dtp->waktu : '-' }}</td>
        </tr>
    </table> --}}
    @php
// $shipdateVal = isset($dt->{"ship{$part}"}) ? $dt->{"ship{$part}"} : null;
// if (!$shipdateVal || $shipdateVal === '0000-00-00' || $shipdateVal === '0000-00-00 00:00:00') {
//     $shipdateVal = '-';
// }

// Helper format tanggal: '-' untuk kosong / tanggal nol
$fmtDate = function ($v) {
    if (!$v || $v === '0000-00-00' || $v === '0000-00-00 00:00:00') {
        return '-';
    }
    return date('d M Y', strtotime($v));
};
$infoItems = [
    [
        'title' => 'PO Number',
        'value' => $dt2->POno,
        'icon' => 'fa-hashtag',
        'class' => 'bg-primary-subtle text-primary',
    ],
    [
        'title' => 'OP',
        'value' => $dt2->OP,
        'icon' => 'fa-layer-group',
        'class' => 'bg-info-subtle text-info',
    ],
    // [
    //     'title' => 'Ship Date',
    //     'value' => $shipdateVal,
    //     'icon' => 'fa-shipping-fast',
    //     'class' => 'bg-primary-subtle text-primary',
    // ],
    [
        'title' => 'License PO Ref',
        'value' => $dt2->poref ?? '-',
        'icon' => 'fa-certificate',
        'class' => 'bg-light text-dark',
    ],
    [
        'title' => 'Buyer Name',
        'value' => $dt2->buyer,
        'icon' => 'fa-user-tie',
        'class' => 'bg-success-subtle text-success',
    ],
    [
        'title' => 'Place',
        'value' => $dt2->customer,
        'icon' => 'fa-map-marker-alt',
        'class' => 'bg-danger-subtle text-danger',
    ],
    [
        'title' => 'Season',
        'value' => $dt2->season,
        'icon' => 'fa-calendar-alt',
        'class' => 'bg-warning-subtle text-warning',
    ],
    [
        'title' => 'Style Code',
        'value' => $dt2->style,
        'icon' => 'fa-tshirt',
        'class' => 'bg-secondary-subtle text-secondary',
    ],
    [
        'title' => 'Color / Material',
        'value' => $dt2->material,
        'icon' => 'fa-palette',
        'class' => 'bg-light text-dark',
    ],
    [
        'title' => 'Shipdate Plan',
        'value' => $fmtDate($dt2->shipdate1 ?? null),
        'icon' => 'fa-calendar-alt',
        'class' => 'bg-secondary-subtle text-secondary',
    ],
    [
        'title' => 'Shipdate Actual',
        'value' => $fmtDate($dt2->shipdate2 ?? null),
        'icon' => 'fa-calendar-check',
        'class' => 'bg-success-subtle text-success',
    ],
    [
        'title' => 'SAP ID',
        'value' => $dt2->sap1 ?? '-',
        'icon' => 'fa-id-card',
        'class' => 'bg-primary-subtle text-primary',
    ],
    [
        'title' => 'SAP No',
        'value' => $dt2->sap2 ?? '-',
        'icon' => 'fa-file-signature',
        'class' => 'bg-primary-subtle text-primary',
    ],
    [
        'title' => 'Warehouse',
        'value' => $dt2->wh ?? '-',
        'icon' => 'fa-warehouse',
        'class' => 'bg-info-subtle text-info',
    ],
    [
        'title' => 'Keterangan',
        'value' => $dt2->ket ?? '-',
        'icon' => 'fa-comment-alt',
        'class' => 'bg-light text-dark',
    ],
    [
        'title' => 'Description',
        'description' => $dt2->silhouette ?? '-',
        'icon' => 'fa-align-left',
        'class' => 'bg-light text-dark',
    ],
];
@endphp

<x-details.info-card
        :items="$infoItems"
    />
    {{-- ===================== BREAKDOWN SIZE & QTY (GLOBAL) ===================== --}}
    <div class="ftitle">Breakdown Size &amp; Qty</div>
    <table class="print-table">
        <tr style="background:#cbd5e1;">
            <th width="10%">Color</th>
            <th width="8%">Sec Size</th>
            <th width="12%">Item</th>
            @foreach ($activeSizes as $i => $sz)
                <th>{{ $sz }}</th>
            @endforeach
            <th>Total</th>
        </tr>

        @foreach ($orderShipByCombo as $combo)
            @php $d = $combo['data']; @endphp
            <tr>
                <td align="center" rowspan="4" style="vertical-align:middle;">{{ $combo['material'] }}</td>
                <td align="center" rowspan="4" style="vertical-align:middle;">{{ $combo['secsz'] ?: '-' }}</td>
                <th>Order Qty</th>
                @foreach ($activeSizes as $i => $sz)
                    <td>{{ $d['order'][$i] ?? '' }}</td>
                @endforeach
                <th>{{ $d['order_total'] ?? 0 }}</th>
            </tr>
            <tr>
                <th>Ship Qty</th>
                @foreach ($activeSizes as $i => $sz)
                    <td>{{ $d['ship'][$i] ?? '' }}</td>
                @endforeach
                <th>{{ $d['ship_total'] ?? 0 }}</th>
            </tr>
            <tr>
                <th>+/-</th>
                @foreach ($activeSizes as $i => $sz)
                    <td>{{ $d['diff'][$i] ?? '' }}</td>
                @endforeach
                <th>{{ $d['diff_total'] ?? 0 }}</th>
            </tr>
            <tr>
                <th>%</th>
                @foreach ($activeSizes as $i => $sz)
                    <td>{{ ($d['pct'][$i] ?? '') !== '' ? number_format($d['pct'][$i], 2, ',', '.') : '' }}</td>
                @endforeach
                <th>{{ number_format($d['pct_total'] ?? 0, 2, ',', '.') }}</th>
            </tr>
        @endforeach

        <tr style="background:#e5e7eb;">
            <th colspan="3">TOTAL</th>
            @foreach ($activeSizes as $i => $sz)
                <th></th>
            @endforeach
            <th></th>
        </tr>
        <tr>
            <td colspan="2"></td>
            <th>Order Qty</th>
            @foreach ($activeSizes as $i => $sz)
                <td>{{ $orderShip['order'][$i] ?? '' }}</td>
            @endforeach
            <th>{{ $orderShip['order_total'] ?? 0 }}</th>
        </tr>
        <tr>
            <td colspan="2"></td>
            <th>Ship Qty</th>
            @foreach ($activeSizes as $i => $sz)
                <td>{{ $orderShip['ship'][$i] ?? '' }}</td>
            @endforeach
            <th>{{ $orderShip['ship_total'] ?? 0 }}</th>
        </tr>
        <tr>
            <td colspan="2"></td>
            <th>+/-</th>
            @foreach ($activeSizes as $i => $sz)
                <td>{{ $orderShip['diff'][$i] ?? '' }}</td>
            @endforeach
            <th>{{ $orderShip['diff_total'] ?? 0 }}</th>
        </tr>
        <tr>
            <td colspan="2"></td>
            <th>%</th>
            @foreach ($activeSizes as $i => $sz)
                <td>{{ ($orderShip['pct'][$i] ?? '') !== '' ? number_format($orderShip['pct'][$i], 2, ',', '.') : '' }}</td>
            @endforeach
            <th>{{ number_format($orderShip['pct_total'] ?? 0, 2, ',', '.') }}</th>
        </tr>

        <tr style="background:#cbd5e1;">
            <th colspan="{{ count($activeSizes) + 4 }}"></th>
        </tr>
        <tr>
            <th colspan="3">N.W</th>
            @foreach ($nwCells as $cell)
                <td>{{ $cell }}</td>
            @endforeach
        </tr>
        <tr>
            <th colspan="3">G.W</th>
            @foreach ($gwCells as $cell)
                <td>{{ $cell }}</td>
            @endforeach
        </tr>
    </table>

    {{-- ===================== SCAN NOBAR (scanner / manual) ===================== --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
            <div class="fw-bold text-dark d-flex align-items-center" style="font-size: 15px; white-space: nowrap;">
                <span class="rounded me-2"
                    style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
                Scan Nobar
            </div>

            <input type="text"
                   id="scanNobarInput"
                   class="form-control"
                   style="max-width: 320px; font-size: 14px;"
                   placeholder="Scan barcode / ketik nobar lalu Enter..."
                   autocomplete="off"
                   autofocus>

            <div id="scanNobarFeedback" class="fw-semibold" style="font-size: 13px;"></div>
        </div>
    </div>

    {{-- ===================== DETAIL FINISHED GOODS (SEMUA COLOR) ===================== --}}
    <div class="pdf-content-container">
        <div class="ftitle">Detail Finished Goods</div>
        <table class="print-table">
            <tr>
                <th width="10%">Color</th>
                <th width="8%">Sec Size</th>
                <th width="10%">Size</th>
                <th width="5%">CTN</th>
                <th width="5%">PCS</th>
                <th width="62%">CARTON NO</th>
            </tr>

            {{-- 1) Carton simple (1 combo per carton) --}}
            @foreach ($detailPackingSimpleRows as $row)
                <tr>
                    <td align="center">{{ $row['material'] }}</td>
                    <td align="center">{{ $row['secsz'] ?: '-' }}</td>
                    <td>
                        @foreach ($row['sizeLines'] as $line)
                            {{ $line }}<br>
                        @endforeach
                    </td>
                    <td align="center">{{ $row['ctn'] }}</td>
                    <td align="center">{{ $row['pcsp'] }}</td>
                    <td class="cell-cartons">
                        @foreach ($row['cartonRows'] as $c)
                            <input type="text" class="carton-input" value=" {{ $c['label'] }} "
                                   style="{{ $c['style'] }}" data-carton="{{ $c['carton'] }}"
                                   data-group="{{ $c['group'] }}" readonly>
                        @endforeach
                    </td>
                </tr>
            @endforeach

            {{-- 2) Carton mixed (lintas Color/Sec Size dalam 1 carton fisik) --}}
            @foreach ($detailPackingMixedRows as $row)
                <tr>
                    @if ($row['showColor'])
                        <td align="center" rowspan="{{ $row['colorRowspan'] }}" style="vertical-align:middle;">
                            {{ $row['material'] }}
                        </td>
                    @endif
                    @if ($row['showSecsz'])
                        <td align="center" rowspan="{{ $row['secszRowspan'] }}" style="vertical-align:middle;">
                            {{ $row['secsz'] ?: '-' }}
                        </td>
                    @endif
                    <td>
                        @foreach ($row['sizeLines'] as $line)
                            {{ $line }}<br>
                        @endforeach
                    </td>
                    @if ($row['isFirstOfCarton'])
                        <td align="center" rowspan="{{ $row['rowspanCarton'] }}" style="vertical-align:middle;">
                            {{ $row['ctn'] }}
                        </td>
                    @endif
                    <td align="center">{{ $row['pcsp'] }}</td>
                    @if ($row['isFirstOfCarton'])
                        <td class="cell-cartons" rowspan="{{ $row['rowspanCarton'] }}" style="vertical-align:middle;">
                            @foreach ($row['cartonInputs'] as $c)
                                <input type="text" class="carton-input" value=" {{ $c['label'] }} "
                                       style="{{ $c['style'] }}" data-carton="{{ $c['carton'] }}"
                                       data-group="{{ $c['group'] }}" readonly>
                            @endforeach
                        </td>
                    @endif
                </tr>
            @endforeach
        </table>
    </div>
</div>

<script>
(function () {
    var input    = document.getElementById('scanNobarInput');
    var feedback = document.getElementById('scanNobarFeedback');
    var scanUrl  = "{{ route('finGoods.scan') }}";
    var bulkUrl  = "{{ route('finGoods.bulk') }}";
    var csrf     = "{{ csrf_token() }}";
    var pono     = "{{ $pono }}";
    var op       = "{{ $op }}";
    var reloadTimer = null;
    var busy = false;

    function focusScan() {
        if (!input) return;
        try {
            input.focus({ preventScroll: true });
        } catch (e) {
            input.focus();
        }
    }

    focusScan();

    var skipRefocus = false;
    document.addEventListener('mousedown', function (e) {
        var t = e.target;
        if (t && t.closest &&
            t.closest('a, button, input, textarea, select, label, [onclick], [role="button"]')) {
            skipRefocus = true;
            setTimeout(function () { skipRefocus = false; }, 500);
        }
    }, true);

    var leaving = false;
    window.addEventListener('beforeunload', function () {
        leaving = true;
    });

    input.addEventListener('blur', function () {
        setTimeout(function () {
            if (leaving || skipRefocus) return;

            var ae = document.activeElement;
            var tag = ae ? ae.tagName : '';
            if (tag !== 'INPUT' && tag !== 'TEXTAREA' && tag !== 'SELECT'
                && tag !== 'A' && tag !== 'BUTTON') {
                focusScan();
            }
        }, 150);
    });

    function showFeedback(ok, msg) {
        feedback.textContent = msg;
        feedback.style.color = ok ? '#15803d' : '#DC143C';
    }

    function postJson(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify(payload)
        }).then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, data: data };
            });
        });
    }

    /* ===================== SCAN NOBAR (global -- pono/op) ===================== */

    input.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();

        var nobar = input.value.trim();
        input.value = '';
        if (nobar === '' || busy) return;

        busy = true;
        showFeedback(true, 'Memproses ' + nobar + ' ...');

        postJson(scanUrl, { pono: pono, op: op, nobar: nobar })
        .then(function (r) {
            showFeedback(r.ok && r.data.success, r.data.message || 'Terjadi kesalahan.');

            if (r.ok && r.data.success) {
                if (reloadTimer) clearTimeout(reloadTimer);
                reloadTimer = setTimeout(function () {
                    location.reload();
                }, 1500);
            }
        })
        .catch(function () {
            showFeedback(false, 'Gagal menghubungi server.');
        })
        .finally(function () {
            busy = false;
            focusScan();
        });
    });

    /* ============ MULTI-SELECT CARTON + MENU BIRU (global) ============ */

    var bar         = document.getElementById('stickTopBar');
    var countEl     = document.getElementById('selectedCount');
    var btnInspect  = document.getElementById('btnProsesInspect');
    var btnShipment = document.getElementById('btnProsesShipment');
    var btnStuffing = document.getElementById('btnKembalikanStuffing');
    var btnClose    = document.getElementById('btnCloseSelection');

    function selectedEls() {
        return Array.prototype.slice.call(
            document.querySelectorAll('.carton-input.carton-selected')
        );
    }

    function setSelected(el, on) {
        if (on) {
            el.classList.add('carton-selected');
            if (el.value.indexOf('\u2713 ') !== 0) {
                el.value = '\u2713 ' + el.value;
            }
        } else {
            el.classList.remove('carton-selected');
            el.value = el.value.replace(/^\u2713 /, '');
        }
    }

    function selectedGroup() {
        var sel = selectedEls();
        return sel.length ? sel[0].dataset.group : null;
    }

    function selectedCartons() {
        var seen = {};
        var list = [];
        selectedEls().forEach(function (el) {
            var c = el.dataset.carton;
            if (c && !seen[c]) {
                seen[c] = true;
                list.push(c);
            }
        });
        return list;
    }

    function refreshBar() {
        var cartons = selectedCartons();
        countEl.textContent = cartons.length;

        if (cartons.length === 0) {
            bar.style.display = 'none';
            return;
        }

        var group = selectedGroup();
        btnInspect.style.display  = group === 'green' ? '' : 'none';
        btnShipment.style.display = group === 'green' ? '' : 'none';
        btnStuffing.style.display = group === 'gray'  ? '' : 'none';

        bar.style.display = 'block';
    }

    function clearSelection() {
        selectedEls().forEach(function (el) {
            setSelected(el, false);
        });
        refreshBar();
    }

    document.addEventListener('click', function (e) {
        var el = e.target;
        if (!el.classList || !el.classList.contains('carton-input')) return;

        var group = el.dataset.group;
        if (group !== 'green' && group !== 'gray') return;

        var current = selectedGroup();
        if (current && current !== group && !el.classList.contains('carton-selected')) {
            showFeedback(false, 'Tidak boleh memilih carton dengan warna berbeda.');
            return;
        }

        setSelected(el, !el.classList.contains('carton-selected'));
        refreshBar();
    });

    btnClose.addEventListener('click', clearSelection);

    function runBulkAction(action) {
        var cartons = selectedCartons();
        if (busy || cartons.length === 0) return;

        busy = true;
        showFeedback(true, 'Memproses ' + cartons.length + ' carton ...');

        postJson(bulkUrl, { pono: pono, op: op, action: action, cartons: cartons })
        .then(function (r) {
            showFeedback(r.ok && r.data.success, r.data.message || 'Terjadi kesalahan.');
            if (r.ok && r.data.success) {
                clearSelection();
                setTimeout(function () {
                    location.reload();
                }, 800);
            }
        })
        .catch(function () {
            showFeedback(false, 'Gagal menghubungi server.');
        })
        .finally(function () {
            busy = false;
        });
    }

    [btnInspect, btnShipment, btnStuffing].forEach(function (btn) {
        btn.addEventListener('click', function () {
            runBulkAction(btn.dataset.action);
        });
    });
})();
</script>
@endsection