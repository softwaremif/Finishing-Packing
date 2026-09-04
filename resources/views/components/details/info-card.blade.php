@props([
    'sections' => [], // array of sections, each section = array of items
])

{{--
    Struktur $sections (dibangun di controller/blade caller):

    [
        [ // section 1
            ['icon' => 'fas fa-layer-group', 'iconBg' => 'bg-info-subtle', 'iconColor' => 'text-info', 'label' => 'OP', 'value' => $dt->OP ?? '-'],
            ['icon' => 'fas fa-certificate', 'iconBg' => '', 'iconColor' => 'text-dark', 'label' => 'License PO Ref', 'value' => $dt->poref ?? '-'],
            ...
            ['icon' => 'fas fa-align-left', 'iconBg' => '', 'iconColor' => 'text-dark', 'label' => 'Description', 'value' => $dt->silhouette ?? '-', 'type' => 'description'],
        ],
        [ // section 2 (otomatis dipisah <hr>)
            ['icon' => 'fas fa-hashtag', 'iconBg' => 'bg-primary-subtle', 'iconColor' => 'text-primary', 'label' => 'PO Number', 'value' => $dt->POno ?? '-'],
        ],
    ]
--}}

<div class="card border-0 shadow-sm mb-4 bg-white" style="border-radius: 12px;">
    <div class="card-body p-4">
        @foreach ($sections as $sIndex => $items)
            @if ($sIndex > 0)
                <hr class="my-4" style="border-color: #f1f5f9; border-width: 2px;">
            @endif

            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4 {{ $sIndex === 0 ? 'mb-4' : '' }}">
                @foreach ($items as $item)
                    <div class="col">
                        <div class="d-flex align-items-start gap-3">
                            <div class="info-icon-wrapper {{ $item['iconBg'] ?? '' }} {{ $item['iconColor'] ?? 'text-dark' }} rounded-3 d-flex align-items-center justify-content-center"
                                style="width: 36px; height: 36px; flex-shrink: 0; {{ empty($item['iconBg']) ? 'background-color: #f1f5f9;' : '' }}">
                                <i class="{{ $item['icon'] ?? 'fas fa-circle' }} fs-6"></i>
                            </div>

                            @if (($item['type'] ?? 'normal') === 'description')
                                <div>
                                    <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                        style="font-size: 10px; letter-spacing: 0.5px;">{{ $item['label'] }}</div>
                                    <div class="text-muted fw-normal"
                                        style="font-size: 12px; line-height: 1.4; word-break: break-word;">
                                        {{ $item['value'] }}
                                    </div>
                                </div>
                            @else
                                <div class="overflow-hidden">
                                    <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                                        style="font-size: 10px; letter-spacing: 0.5px;">{{ $item['label'] }}</div>
                                    <div class="fw-bold text-dark text-truncate" style="font-size: 14px;"
                                        title="{{ $item['value'] }}">{{ $item['value'] }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>