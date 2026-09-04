@once
<style>
    .easyui-dg-wrap .order-title {
        font-weight: 700;
        font-size: 18px;
        margin-bottom: 12px;
    }
    .easyui-dg-wrap .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
        align-items: center;
    }

    /* ============================================================
       "Selected filters" bar -- kotak bordered, label kiri, chip di
       tengah, "Clear All" di kanan.
       ============================================================ */
    .easyui-dg-wrap .active-filters-bar {
        display: none;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
        padding: 10px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
    }
    .easyui-dg-wrap .active-filters-label {
        font-size: 13px;
        color: #374151;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .easyui-dg-wrap .active-filters-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        flex: 1;
    }
    .easyui-dg-wrap .active-filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 8px 6px 14px;
        border-radius: 20px;
        background: #f1f5f9;
        color: #0f172a;
        font-size: 13px;
        font-weight: 600;
    }
    .easyui-dg-wrap .active-filter-chip .chip-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        border: none;
        background: transparent;
        color: #64748b;
        font-size: 14px;
        line-height: 1;
        cursor: pointer;
        padding: 0;
        transition: color .15s ease;
    }
    .easyui-dg-wrap .active-filter-chip .chip-close:hover {
        color: #0f172a;
    }
    .easyui-dg-wrap .chip-clear-all {
        font-size: 13px;
        font-weight: 600;
        color: #2563eb;
        text-decoration: none;
        cursor: pointer;
        background: none;
        border: none;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .easyui-dg-wrap .chip-clear-all:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }

    /* Wrapper visual tabel */
    .easyui-dg-wrap .easyui-dg-table {
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        background: #fff;
    }
    .easyui-dg-wrap .datagrid-wrap,
    .easyui-dg-wrap .datagrid-view {
        background: #fff;
    }
    .easyui-dg-wrap .datagrid-header,
    .easyui-dg-wrap .datagrid-header-inner {
        background: #ffffff !important;
    }
    .easyui-dg-wrap .datagrid-header .datagrid-cell {
        font-weight: 600;
        font-size: 12px;
        text-align: center !important;
        color: #374151;
    }
    .easyui-dg-wrap .datagrid-header-row td {
        border-bottom: 1px solid #e5e7eb !important;
    }
    .easyui-dg-wrap .datagrid-row {
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.2s ease;
    }
    .easyui-dg-wrap .datagrid-row:hover {
        background: #f9fafb !important;
    }
    .easyui-dg-wrap .datagrid-row-selected {
        background: #dbeafe !important;
    }
    .easyui-dg-wrap .datagrid-cell {
        font-size: 12px;
    }
    .easyui-dg-wrap .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #e0f2fe;
        color: #0369a1;
        font-size: 13px;
        transition: 0.2s;
        text-decoration: none;
    }
    .easyui-dg-wrap .action-btn:hover {
        background: #bae6fd;
        color: #0c4a6e;
        transform: scale(1.05);
    }
    .easyui-dg-wrap .sort-wrap {
        margin-left: auto;
    }
    .easyui-dg-wrap .sort-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #374151;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: 0.2s;
        user-select: none;
    }
    .easyui-dg-wrap .sort-btn:hover {
        background: #f9fafb;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    /* ============================================================
       Sort -- card panel mengambang (GANTI toggle klik-langsung lama).
       ============================================================ */
    .easyui-dg-wrap .sort-card-wrap {
        position: relative;
    }
    .easyui-dg-wrap .sort-btn .fa-chevron-down {
        transition: transform .15s ease;
    }
    .easyui-dg-wrap .sort-card-wrap.open .sort-btn .fa-chevron-down {
        transform: rotate(180deg);
    }
    .easyui-dg-wrap .sort-card-panel {
        display: none;
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        min-width: 230px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.14);
        padding: 8px;
        z-index: 60;
    }
    .easyui-dg-wrap .sort-card-panel.show {
        display: block;
    }
    .easyui-dg-wrap .sort-card-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
        color: #374151;
        transition: background .15s ease;
    }
    .easyui-dg-wrap .sort-card-option:hover {
        background: #f8fafc;
    }
    .easyui-dg-wrap .sort-card-option.active {
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 600;
    }
    .easyui-dg-wrap .sort-card-option .opt-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: #64748b;
        font-size: 12px;
        flex-shrink: 0;
    }
    .easyui-dg-wrap .sort-card-option.active .opt-icon {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .easyui-dg-wrap .sort-card-option .opt-check {
        margin-left: auto;
        color: #1d4ed8;
        font-size: 12px;
        opacity: 0;
    }
    .easyui-dg-wrap .sort-card-option.active .opt-check {
        opacity: 1;
    }

    .easyui-dg-wrap .easyui-empty-state {
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding-top: 120px;
        background: rgba(255, 255, 255, 0.96);
        z-index: 2;
    }
    .easyui-dg-wrap .empty-icon img {
        opacity: 0.9;
    }
    .datagrid-header .datagrid-cell-group {
        text-align: center !important;
        font-weight: bold !important;
    }
</style>
@endonce
@once
    <script>
        (function (global) {
            'use strict';
            var EasyuiDG = {
                _instances: {}
            };

            EasyuiDG.init = function (cfg) {
                var $ = global.jQuery;
                if (!$) {
                    console.error('EasyuiDG requires jQuery to be loaded first.');
                    return;
                }

                var gridId = cfg.id;
                var $grid = $('#' + gridId);
                var $filterBar = $('#' + gridId + '_filterbar');
                var $chipsWrap = $('#' + gridId + '_chips_wrap');
                var $chipsBar = $('#' + gridId + '_chips');
                var timer = null;
                var lastQuery = {};
                var filtersReady = false;
                var hasYearFilter = $filterBar.find('[data-dg-generator="years"]').length > 0;

                EasyuiDG._instances[gridId] = {
                    loadData: loadData
                };

                /* INIT */
                bindFilter();

                if (!hasYearFilter) {
                    filtersReady = true;
                }

                $grid.datagrid({
                    onBeforeLoad: function () {
                        if (!filtersReady) {
                            return false;
                        }
                        clearEmptyState();
                    },
                    onLoadSuccess: onLoadTable,
                    onLoadError: function () {
                        showEmpty();
                    }
                });

                setTimeout(function () {
                    $grid.datagrid('resize');
                }, 0);

                /* =========================
                   FILTER SYSTEM (SEARCH + BUYER + TAHUN + EX FACTORY + SORT + CUSTOM)
                ========================= */
                function bindFilter() {
                    // Global search
                    $filterBar.find('[data-dg-filter-type="search"]').on('input', function () {
                        clearTimeout(timer);
                        timer = setTimeout(function () {
                            loadData();
                        }, 400);
                    });
                    $filterBar.find('[data-dg-filter-type="search"]').on('keydown', function (e) {
                        if (e.key === 'Enter' || e.keyCode === 13) {
                            e.preventDefault();
                            clearTimeout(timer);
                            loadData(1, true);
                        }
                    });

                    // Combobox (buyer default, atau combobox custom dari slot filters)
                    $filterBar.find('[data-dg-filter-type="combobox"]').each(function () {
                        var $el = $(this);
                        $el.combobox({
                            method: 'get',
                            url: $el.data('dg-url'),
                            valueField: $el.data('dg-value-field') || 'value',
                            textField: $el.data('dg-text-field') || 'text',
                            panelHeight: 300,
                            editable: true,
                            mode: $el.data('dg-mode') || 'remote',
                            onSelect: function () {
                                loadData();
                            },
                            onChange: function () {
                                clearTimeout(timer);
                                timer = setTimeout(function () {
                                    loadData();
                                }, 300);
                            }
                        });
                        // Default combobox biasanya kosong -- tetap dicatat
                        // supaya konsisten dengan mekanisme reset-ke-default.
                        $el.data('dg-initial-value', '');
                    });

                    // Select EasyUI (tahun default, ex-factory, atau select custom)
                    $filterBar.find('[data-dg-filter-type="select"]').each(function () {
                        var $el = $(this);
                        var data;
                        var defaultValue = '';

                        if ($el.data('dg-generator') === 'years') {
                            var currentYear = new Date().getFullYear();
                            var yearsBack = $el.data('dg-years-back') != null ? $el.data('dg-years-back') : 5;
                            var yearsForward = $el.data('dg-years-forward') != null ? $el.data('dg-years-forward') : 1;
                            data = [];
                            for (var y = currentYear + yearsForward; y >= currentYear - yearsBack; y--) {
                                data.push({ value: y, text: y });
                            }
                            defaultValue = currentYear;
                        } else {
                            var raw = $el.attr('data-dg-options');
                            data = raw ? JSON.parse(raw) : [];
                            defaultValue = $el.attr('data-dg-default') || '';
                        }

                        var panelHeight = $el.data('dg-panel-height') != null
                            ? $el.data('dg-panel-height')
                            : 'auto';

                        $el.combobox({
                            data: data,
                            valueField: $el.data('dg-value-field') || 'value',
                            textField: $el.data('dg-text-field') || 'text',
                            editable: false,
                            panelHeight: panelHeight,
                            value: defaultValue,
                            onChange: function (newValue, oldValue) {
                                loadData(1);
                            }
                        });

                        // FIX: simpan default value elemen ini -- dipakai (a)
                        // untuk skip chip kalau value masih default, DAN
                        // (b) untuk reset KE default ini (bukan selalu kosong)
                        // saat Clear All / klik "x" chip individual.
                        $el.data('dg-initial-value', String(defaultValue));

                        if ($el.data('dg-generator') === 'years') {
                            setTimeout(function () {
                                filtersReady = true;
                                loadData(1, true);
                            }, 0);
                        }
                    });

                    // ============================================================
                    // Sort -- card panel (GANTI toggle klik-langsung lama).
                    // ============================================================
                    var $sortWrap  = $filterBar.find('.sort-card-wrap');
                    var $sortBtn   = $sortWrap.find('[data-dg-filter-type="sort-dropdown"]');
                    var $sortPanel = $sortWrap.find('.sort-card-panel');

                    $sortBtn.on('click', function (e) {
                        e.stopPropagation();
                        $sortWrap.toggleClass('open');
                        $sortPanel.toggleClass('show');
                    });

                    $sortPanel.on('click', '.sort-card-option', function () {
                        var $opt = $(this);
                        var val   = $opt.data('sort-value');
                        var icon  = $opt.data('sort-icon') || 'fa-arrow-down';
                        var label = $opt.clone().children('.opt-check').remove().end().text().trim();
                    
                        $sortBtn.attr('data-value', val);
                        $sortBtn.find('i').first().attr('class', 'fas ' + icon);
                        $sortBtn.find('span').first().text(label);
                    
                        $sortPanel.find('.sort-card-option').removeClass('active');
                        $opt.addClass('active');
                    
                        $sortWrap.removeClass('open');
                        $sortPanel.removeClass('show');
                    
                        loadData(1);
                    });

                    $(document).on('click', function (e) {
                        if (!$(e.target).closest('.sort-card-wrap').length) {
                            $sortWrap.removeClass('open');
                            $sortPanel.removeClass('show');
                        }
                    });

                    // Elemen filter generik lainnya (native <select>, dsb)
                    $filterBar.find('[data-dg-filter]')
                        .not('[data-dg-filter-type="search"]')
                        .not('[data-dg-filter-type="combobox"]')
                        .not('[data-dg-filter-type="select"]')
                        .not('[data-dg-filter-type="sort-dropdown"]')
                        .on('change', function () {
                            loadData(1);
                        });
                }

                /* =========================
                   LOAD DATA (+ bangun chip filter aktif)
                ========================= */
                function loadData(page, force) {
                    page = page || 1;

                    var query = { page: page };
                    var chipItems = [];

                    $filterBar.find('[data-dg-filter]').each(function () {
                        var $el = $(this);
                        var key = $el.data('dg-filter');
                        var type = $el.data('dg-filter-type') || 'search';
                        var val;

                        if (type === 'combobox' || type === 'select') {
                            try {
                                val = $el.combobox('getValue');
                            } catch (e) {
                                val = '';
                            }
                        } else if (type === 'sort-dropdown') {
                            val = $el.attr('data-value');
                        } else {
                            val = $el.val();
                        }
                        val = (val === null || val === undefined) ? '' : val;
                        query[key] = val;

                        var hideChip   = $el.data('dg-chip-hide') === true;
                        var isSortType = (type === 'sort-dropdown');

                        // Chip HANYA muncul kalau value BEDA dari default awal
                        // elemen ini (Tahun masih tahun ini / Ex Factory masih
                        // "Semua" -> TIDAK tampil sebagai chip).
                        var initialVal = $el.data('dg-initial-value');
                        var isStillDefault = (initialVal !== undefined && String(val) === initialVal);

                        if (!hideChip && !isSortType && val !== '' && !isStillDefault) {
                            chipItems.push({
                                key: key,
                                label: $el.data('dg-chip-label') || key,
                                value: val,
                                text: getDisplayText($el, type),
                                type: type
                            });
                        }
                    });

                    renderChips(chipItems);

                    if (!force && JSON.stringify(query) === JSON.stringify(lastQuery)) {
                        return;
                    }

                    lastQuery = query;

                    clearEmptyState();

                    $grid.datagrid('load', query);
                }

                /* =========================
                   CHIP "Selected filters" -- render & clear
                ========================= */
                function getDisplayText($el, type) {
                    if (type === 'combobox' || type === 'select') {
                        try {
                            var t = $el.combobox('getText');
                            if (t) return t;
                        } catch (e) {}
                        var $span = $el.next('span.combo');
                        if ($span.length) {
                            var t2 = $span.find('input.combo-text').val();
                            if (t2) return t2;
                        }
                        try {
                            return $el.combobox('getValue');
                        } catch (e2) {
                            return '';
                        }
                    }
                    if ($el.is('select')) {
                        return $el.find('option:selected').text();
                    }
                    return $el.val();
                }

                function escapeHtml(str) {
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');
                }

                function renderChips(items) {
                    if (!$chipsWrap.length) return;

                    if (!items.length) {
                        $chipsWrap.hide();
                        $chipsBar.empty();
                        return;
                    }

                    var html = items.map(function (item) {
                        var textShown = item.text || item.value;
                        return '<span class="active-filter-chip" data-chip-key="' + item.key + '">' +
                            '<span>' + escapeHtml(item.label) + ': ' + escapeHtml(textShown) + '</span>' +
                            '<button type="button" class="chip-close" aria-label="Hapus filter ' + escapeHtml(item.label) + '">&times;</button>' +
                        '</span>';
                    }).join('');

                    $chipsBar.html(html);
                    $chipsWrap.css('display', 'flex');
                }

                $chipsBar.on('click', '.chip-close', function () {
                    var key = $(this).closest('.active-filter-chip').data('chip-key');
                    clearFilterByKey(key);
                });

                $chipsWrap.on('click', '.chip-clear-all', function () {
                    $filterBar.find('[data-dg-filter]').each(function () {
                        var $el = $(this);
                        var hideChip = $el.data('dg-chip-hide') === true;
                        var type = $el.data('dg-filter-type') || 'search';
                        if (!hideChip && type !== 'sort-dropdown') {
                            resetFilterElement($el, type);
                        }
                    });
                    loadData(1, true);
                });

                // FIX UTAMA: reset SEKARANG kembali ke default masing-masing
                // elemen (dg-initial-value), BUKAN selalu ke kosong. Tahun
                // balik ke tahun ini, Ex Factory balik ke "Semua Ex Factory",
                // Search/Buyer (default kosong) tetap balik ke kosong.
                function resetFilterElement($el, type) {
                    var resetTo = $el.data('dg-initial-value');
                    resetTo = (resetTo === undefined || resetTo === null) ? '' : resetTo;

                    if (type === 'combobox' || type === 'select') {
                        try {
                            $el.combobox('setValue', resetTo);
                        } catch (e) {}
                    } else if ($el.is('select')) {
                        $el.val(resetTo);
                    } else {
                        $el.val(resetTo);
                    }
                }

                function clearFilterByKey(key) {
                    var $el = $filterBar.find('[data-dg-filter="' + key + '"]');
                    if (!$el.length) return;
                    var type = $el.data('dg-filter-type') || 'search';
                    resetFilterElement($el, type);
                    loadData(1, true);
                }

                /* =========================
                   EMPTY STATE SAFE
                ========================= */
                function clearEmptyState() {
                    $grid.datagrid('getPanel')
                        .find('.datagrid-view2 .easyui-empty-state')
                        .remove();
                }
                function onLoadTable(data) {
                    var rows = (data && data.rows) || [];
                    $grid.datagrid('clearChecked');
                    $grid.datagrid('clearSelections');
                    var panel = $grid.datagrid('getPanel');
                    var body = panel.find('.datagrid-view2 .datagrid-body');
                    panel.find('.easyui-empty-state').remove();
                    if (!rows.length) {
                        var opts = cfg.options || {};
                        body.append(
                            '<div class="easyui-empty-state">' +
                                '<div style="text-align:center">' +
                                    (opts.emptyImage ? '<img src="' + opts.emptyImage + '" width="180">' : '') +
                                    '<div style="margin-top:8px;font-weight:600;">' + (opts.emptyTitle || 'No Data Found') + '</div>' +
                                    '<div style="font-size:12px;color:#9ca3af;">' + (opts.emptyDesc || 'Try changing filter') + '</div>' +
                                '</div>' +
                            '</div>'
                        );
                    }
                }
                function showEmpty() {
                    onLoadTable({ rows: [] });
                }
            };

            EasyuiDG.reload = function (id, page) {
                var $ = global.jQuery;
                var $grid = $('#' + id);
                if (!$grid.length || !$grid.data('datagrid')) return;
                if (page != null) {
                    $grid.datagrid('getPager').pagination('select', page);
                } else {
                    $grid.datagrid('reload');
                }
            };

            global.EasyuiDG = EasyuiDG;
        })(window);
    </script>
@endonce
<div class="easyui-dg-wrap">
    @if($title)
        <div class="order-title">{{ $title }}</div>
    @endif
    <div class="filter-bar" id="{{ $id }}_filterbar">
        {{-- Default filter: global search --}}
        @if($search)
            <div class="input-group" style="width:{{ $searchWidth }}px;">
                <span class="input-group-text search">
                    <img src="{{ asset('public/css/images/Shape.png') }}" width="18" height="18">
                </span>
                <input type="text" class="form-control search"
                       data-dg-filter="{{ $searchName }}"
                       data-dg-filter-type="search"
                       data-dg-chip-label="Pencarian"
                       placeholder="{{ $searchPlaceholder }}">
            </div>
        @endif

        {{-- Default filter: buyer (combobox remote) --}}
        @if($buyer)
            <div class="p-0">
                <input data-dg-filter="{{ $buyerName }}"
                       data-dg-filter-type="combobox"
                       data-dg-chip-label="Buyer"
                       data-dg-url="{{ $buyerUrl }}"
                       data-dg-value-field="{{ $buyerValueField }}"
                       data-dg-text-field="{{ $buyerTextField }}"
                       data-dg-mode="{{ $buyerMode }}"
                       style="width:{{ $buyerWidth }}px">
            </div>
        @endif

        {{-- Default filter: tahun -- chip hanya tampil kalau BUKAN tahun
             ini, dan Clear All/x akan reset KEMBALI ke tahun ini. --}}
        @if($year)
            <div class="p-0">
                <input data-dg-filter="{{ $yearName }}"
                       data-dg-filter-type="select"
                       data-dg-generator="years"
                       data-dg-years-back="{{ $yearsBack }}"
                       data-dg-chip-label="Tahun"
                       style="width:{{ $yearWidth }}px">
            </div>
        @endif

        {{-- Filter Ex Factory -- opsi "Semua Ex Factory" sebagai default. --}}
        @if($exfactory ?? false)
            <div class="p-0">
                <input data-dg-filter="{{ $exfactoryName }}"
                    data-dg-filter-type="select"
                    data-dg-chip-label="Ex-Factory"
                    data-dg-options='[
                        {"value":"","text":"Ex-Factory"},
                        {"value":"today","text":"Hari Ini"},
                        {{-- {"value":"next_week","text":"Minggu Depan"}, --}}
                        {"value":"this_week","text":"Minggu Ini"},
                        {"value":"next_2_weeks","text":"2 Minggu Depan"},
                        {"value":"this_month","text":"Bulan Ini"},
                        {"value":"next_month","text":"Bulan Depan"},
                        {"value":"next_3_months","text":"3 Bulan Depan"},
                        {"value":"next_6_months","text":"6 Bulan Depan"},
                        {"value":"this_year","text":"Tahun Ini"}
                    ]'
                    data-dg-default=""
                    style="width:{{ $exfactoryWidth ?? 170 }}px">
            </div>
        @endif

        {{-- Filter tambahan khusus per halaman --}}
        {{ $filters ?? '' }}

        {{-- Sorting -- card panel, GANTI toggle klik-langsung lama --}}
        @if($sort ?? true)
            <div class="p-0 sort-wrap sort-card-wrap" id="{{ $id }}_sortWrap">
                <button type="button" class="sort-btn"
                    data-dg-filter="sort" data-dg-filter-type="sort-dropdown"
                    data-value="{{ $sortDefault ?? 'desc' }}">
                    <i class="fas {{ ($sortDefault ?? 'desc') === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                    <span>{{ ($sortDefault ?? 'desc') === 'asc' ? ($sortAscLabel ?? 'Terlama') : ($sortDescLabel ?? 'Terbaru') }}</span>
                    <i class="fas fa-chevron-down" style="font-size:9px;"></i>
                </button>
                <div class="sort-card-panel">
                    <div class="sort-card-option {{ ($sortDefault ?? 'desc') === 'asc' ? 'active' : '' }}"
                        data-sort-value="asc" data-sort-icon="fa-arrow-up">
                        <span>{{ $sortAscLabel ?? 'Terlama' }}</span>
                        <i class="fas fa-check opt-check"></i>
                    </div>
                    <div class="sort-card-option {{ ($sortDefault ?? 'desc') === 'desc' ? 'active' : '' }}"
                        data-sort-value="desc" data-sort-icon="fa-arrow-down">
                        <span>{{ $sortDescLabel ?? 'Terbaru' }}</span>
                        <i class="fas fa-check opt-check"></i>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- "Selected filters" bar --}}
    <div class="active-filters-bar" id="{{ $id }}_chips_wrap">
        <span class="active-filters-label">Selected filters :</span>
        <div class="active-filters-chips" id="{{ $id }}_chips"></div>
        <button type="button" class="chip-clear-all">Clear All</button>
    </div>

    <div class="easyui-dg-table">
        {{ $slot }}
    </div>
</div>
<script>
    (function () {
        var cfg = {
            id: @json($id),
            options: {
                emptyImage: @json(asset('public/css/images/no-data-6.svg')),
                emptyTitle: 'No Data Found',
                emptyDesc: 'Try changing filter'
            }
        };
        function boot() {
            if (window.EasyuiDG) {
                window.EasyuiDG.init(cfg);
            } else {
                console.error(
                    'EasyuiDG runtime tidak terbentuk. '
                    + 'Pastikan jquery.min.js dan jquery.easyui.min.js sudah di-load '
                    + 'SEBELUM komponen ini dirender.'
                );
            }
        }
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            setTimeout(boot, 0);
        } else {
            document.addEventListener('DOMContentLoaded', boot);
        }
    })();
</script>