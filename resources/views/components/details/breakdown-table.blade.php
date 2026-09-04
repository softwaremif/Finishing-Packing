@props([
    'title'   => 'Breakdown Size & Qty',
    'secsz'   => null,
    'columns' => [], // [ ['key' => mixed, 'label' => string], ... ]
    'rows'    => [], // lihat struktur di bawah
])

{{--
    Struktur tiap item $rows:

    [
        'type'   => 'primary' | 'normal' | 'balance', // default 'normal'
        'label'  => 'Order Quantity',
        'badge'  => ['text' => 'Barcode', 'bg' => 'bg-secondary-subtle', 'color' => 'text-secondary'], // optional
        'values' => [$columnKey => $qty, ...],
        'total'  => 123,
        'children' => [ // optional, breakdown per-line di bawah row ini
            ['label' => 'Line A', 'values' => [$columnKey => $qty, ...], 'total' => 10],
            ...
        ],
    ]

    - type 'primary'  -> row PERTAMA (mis. Order Quantity), total cell pakai bg-total-cell (tanpa border-start).
    - type 'normal'   -> row biasa, total cell pakai bg-light + border-start.
    - type 'balance'  -> row terakhir, warna otomatis mengikuti tanda (+/-/0) tiap sel & total.
--}}

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
        <div class="fw-bold text-dark d-flex align-items-center">
            <span class="rounded me-2" style="width: 4px; height: 16px; display: inline-block; background: #64748b;"></span>
            {{ $title }}
        </div>
    </div>
    <div class="card-body p-0 table-responsive style-scrollbar">
        <table class="table table-hover text-center mb-0 align-middle table-breakdown">
            <thead>
                <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <th class="text-start px-3 sticky-col-start fw-bold" style="font-size: 12px;">
                        Size
                        @if (!empty($secsz))
                            <span class="text-muted fw-bold text-lowercase">({{ $secsz }})</span>
                        @endif
                    </th>
                    @foreach ($columns as $col)
                        <th style="font-size: 12px;" class="text-dark">{{ $col['label'] }}</th>
                    @endforeach
                    <th class="sticky-col-end fw-bold text-dark" style="font-size: 12px;">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php
                        $type = $row['type'] ?? 'normal';
                        $total = $row['total'] ?? 0;
                    @endphp

                    <tr @class(['table-sm' => $type === 'normal', 'border-top border-light' => $type === 'balance'])
                        @if ($type === 'balance') style="border-width: 1.5px;" @endif>
                        <td class="text-start px-3 sticky-col-start {{ $type === 'balance' ? 'fw-bold' : '' }} text-secondary">
                            {{ $row['label'] }}
                            @if (!empty($row['badge']))
                                <span class="badge {{ $row['badge']['bg'] ?? '' }} {{ $row['badge']['color'] ?? '' }}" style="font-size:9px;">{{ $row['badge']['text'] }}</span>
                            @endif
                        </td>

                        @foreach ($columns as $col)
                            @php $val = $row['values'][$col['key']] ?? 0; @endphp
                            @if ($type === 'balance')
                                <td class="fw-bold {{ $val < 0 ? 'text-danger' : ($val > 0 ? 'text-success' : 'text-muted opacity-50') }}">
                                    {{ $val > 0 ? '+' . $val : $val }}
                                </td>
                            @else
                                <td class="text-dark fw-medium">{{ $val }}</td>
                            @endif
                        @endforeach

                        @if ($type === 'balance')
                            <td class="fw-bold sticky-col-end {{ $total < 0 ? 'text-danger bg-danger-subtle' : ($total > 0 ? 'text-success bg-success-subtle' : 'text-muted opacity-50 bg-total-cell') }}">
                                {{ $total > 0 ? '+' . $total : ($total != 0 ? $total : '0') }}
                            </td>
                        @elseif ($type === 'primary')
                            <td class="fw-bold text-dark sticky-col-end bg-total-cell">{{ $total }}</td>
                        @else
                            <td class="fw-bold bg-light text-dark border-start">{{ $total }}</td>
                        @endif
                    </tr>

                    @if (!empty($row['children']))
                        @foreach ($row['children'] as $child)
                            <tr class="dc-line-row">
                                <td class="text-start px-3 sticky-col-start">
                                    <div class="dc-line-label">
                                        <span class="dc-line-connector"></span>
                                        <span class="dc-line-icon">↳</span>
                                        <span class="dc-line-text">{{ $child['label'] }}</span>
                                    </div>
                                </td>
                                @foreach ($columns as $col)
                                    <td class="text-secondary" style="font-size:11.5px;">{{ $child['values'][$col['key']] ?? 0 }}</td>
                                @endforeach
                                <td class="fw-medium text-secondary bg-light border-start" style="font-size:11.5px;">{{ $child['total'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>