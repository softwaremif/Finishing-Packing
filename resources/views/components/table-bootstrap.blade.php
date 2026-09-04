@props([
    'id',
    'url',
    'title' => null,
    'search' => false, 'searchName' => 'search', 'searchWidth' => 260, 'searchPlaceholder' => 'Cari...',
    'buyer' => false, 'buyerName' => 'buyer', 'buyerUrl' => null, 'buyerValueField' => 'value', 'buyerTextField' => 'text', 'buyerMode' => 'remote', 'buyerWidth' => 180,
    'year' => false, 'yearName' => 'year', 'yearsBack' => 5, 'yearWidth' => 110,
    'exfactory' => false, 'exfactoryName' => 'ex_factory', 'exfactoryWidth' => 170,
    'sortDropdown' => false, 'sortName' => 'sort', 'sortDefault' => 'desc', 'sortAscLabel' => 'Terlama', 'sortDescLabel' => 'Terbaru',
    'pageSize' => 50, 'pageSizes' => [25, 50, 100, 200, 500],
    'emptyTitle' => 'No Data Found', 'emptyDesc' => 'Try changing filter',
    'onLoadSuccess' => null, 
])

@include('components.partials.bs-table-css')
@include('components.partials.bs-table-js')

<div class="bs-dg-wrap" id="{{ $id }}_wrap">
    @if($title)
        <div class="order-title">{{ $title }}</div>
    @endif

    <div class="filter-bar" id="{{ $id }}_filterbar">
        @if($search)
            <div class="input-group" style="width:{{ $searchWidth }}px;">
                <span class="input-group-text search">
                    <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18">
                </span>
                <input type="text" class="form-control search"
                    data-dg-filter="{{ $searchName }}" data-dg-filter-type="search"
                    data-dg-chip-label="Pencarian" placeholder="{{ $searchPlaceholder }}">
            </div>
        @endif

        @if($buyer)
            <div class="p-0">
                <input data-dg-filter="{{ $buyerName }}" data-dg-filter-type="combobox"
                    data-dg-chip-label="Buyer" data-dg-url="{{ $buyerUrl }}"
                    data-dg-value-field="{{ $buyerValueField }}" data-dg-text-field="{{ $buyerTextField }}"
                    data-dg-mode="{{ $buyerMode }}" style="width:{{ $buyerWidth }}px">
            </div>
        @endif

        @if($year)
            <div class="p-0">
                <input data-dg-filter="{{ $yearName }}" data-dg-filter-type="select"
                    data-dg-generator="years" data-dg-years-back="{{ $yearsBack }}"
                    data-dg-chip-label="Tahun" style="width:{{ $yearWidth }}px">
            </div>
        @endif

        @if($exfactory)
            <div class="p-0">
                <input data-dg-filter="{{ $exfactoryName }}" data-dg-filter-type="select"
                    data-dg-chip-label="Ext Factory date"
                    data-dg-options='[
                        {"value":"","text":"Ext Factory date"},
                        {"value":"today","text":"Hari Ini"},
                        {"value":"this_week","text":"Minggu Ini"},
                        {"value":"next_2_weeks","text":"2 Minggu Depan"},
                        {"value":"this_month","text":"Bulan Ini"},
                        {"value":"next_month","text":"Bulan Depan"},
                        {"value":"next_3_months","text":"3 Bulan Depan"},
                        {"value":"next_6_months","text":"6 Bulan Depan"},
                        {"value":"this_year","text":"Tahun Ini"}
                    ]'
                    data-dg-default="" style="width:{{ $exfactoryWidth }}px">
            </div>
        @endif

        {{ $filters ?? '' }}

        @if($sortDropdown)
            <div class="p-0 sort-wrap sort-card-wrap" id="{{ $id }}_sortWrap">
                <button type="button" class="sort-btn"
                    data-dg-filter="{{ $sortName }}" data-dg-filter-type="sort-dropdown"
                    data-value="{{ $sortDefault }}">
                    <i class="fas {{ $sortDefault === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                    <span>{{ $sortDefault === 'asc' ? $sortAscLabel : $sortDescLabel }}</span>
                    <i class="fas fa-chevron-down" style="font-size:9px;"></i>
                </button>
                <div class="sort-card-panel">
                    <div class="sort-card-option {{ $sortDefault === 'asc' ? 'active' : '' }}" data-sort-value="asc" data-sort-icon="fa-arrow-up">
                        <span>{{ $sortAscLabel }}</span><i class="fas fa-check opt-check"></i>
                    </div>
                    <div class="sort-card-option {{ $sortDefault === 'desc' ? 'active' : '' }}" data-sort-value="desc" data-sort-icon="fa-arrow-down">
                        <span>{{ $sortDescLabel }}</span><i class="fas fa-check opt-check"></i>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="active-filters-bar" id="{{ $id }}_chips_wrap">
        <span class="active-filters-label">Selected filters :</span>
        <div class="active-filters-chips" id="{{ $id }}_chips"></div>
        <button type="button" class="chip-clear-all">Clear All</button>
    </div>

    <div class="bs-dg-table-wrap">
        <div class="bs-dg-scroll">
            <table class="table table-sm table-hover align-middle bs-dg-table" id="{{ $id }}_table">
                <thead>
                    {{ $slot }}
                </thead>
                <tbody id="{{ $id }}_body"></tbody>
            </table>
            <div id="{{ $id }}_empty" class="d-none"></div>
        </div>
        <div class="bs-dg-footer">
            <div class="d-flex align-items-center gap-2">
                <span class="text-secondary" style="font-size:12px;">Tampilkan</span>
                <select id="{{ $id }}_pagesize" class="form-select form-select-sm" style="width:80px; font-size:12px;">
                    @foreach ($pageSizes as $ps)
                        <option value="{{ $ps }}" @selected($ps == $pageSize)>{{ $ps }}</option>
                    @endforeach
                </select>
            </div>
            <div class="text-secondary" style="font-size:12.5px;" id="{{ $id }}_info"></div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary" id="{{ $id }}_prev"><i class="fas fa-chevron-left"></i></button>
                <span style="font-size:12.5px;" id="{{ $id }}_pagelabel"></span>
                <button class="btn btn-sm btn-outline-secondary" id="{{ $id }}_next"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        function boot() {
            window.BsTable.init({
                id: @json($id),
                url: @json($url),
                pageSize: {{ $pageSize }},
                emptyImage: @json(asset('public/css/images/no-data-6.svg')),
                emptyTitle: @json($emptyTitle),
                emptyDesc: @json($emptyDesc),
                @if ($onLoadSuccess)
                    onLoadSuccess: window.{{ $onLoadSuccess }}, 
                @endif
            });
        }
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            setTimeout(boot, 0);
        } else {
            document.addEventListener('DOMContentLoaded', boot);
        }
    })();
</script>