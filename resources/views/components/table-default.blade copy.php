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

        /* Wrapper visual: border, radius, dan overflow-hidden HANYA di sini,
        bukan di .datagrid-view, supaya tidak bentrok dengan
        mekanisme sync-scroll internal EasyUI */
        .easyui-dg-wrap .easyui-dg-table {
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            background: #fff;
        }

        /* Jangan taruh overflow:hidden atau border di sini lagi */
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

        /* Pastikan box-sizing konsisten (content-box) supaya perhitungan
        lebar kolom header & body oleh EasyUI tetap presisi meski
        ada padding custom */
        .easyui-dg-wrap .datagrid-view * {
            box-sizing: content-box;
        }

        .easyui-dg-wrap .datagrid-cell {
            padding: 6px 8px !important;
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

                var timer = null;
                var lastQuery = {};

                EasyuiDG._instances[gridId] = {
                    loadData: loadData
                };

                /* INIT */
                bindFilter();

                $grid.datagrid({
                    onBeforeLoad: clearEmptyState,
                    onLoadSuccess: onLoadTable,
                    onLoadError: function () {
                        showEmpty();
                    }
                });

                setTimeout(function () {
                    $grid.datagrid('resize');
                }, 0);

                /* =========================
                   FILTER SYSTEM (SEARCH + BUYER + TAHUN + CUSTOM)
                ========================= */
                function bindFilter() {

                    // Global search
                    $filterBar.find('[data-dg-filter-type="search"]').on('input', function () {
                        clearTimeout(timer);

                        timer = setTimeout(function () {
                            loadData();
                        }, 400);
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
                    });

                    // Select (tahun default, atau select custom dari slot filters)
                    $filterBar.find('[data-dg-filter-type="select"]').each(function () {
                        var $el = $(this);
                        var data;

                        if ($el.data('dg-generator') === 'years') {
                            var currentYear = new Date().getFullYear();
                            var yearsBack = $el.data('dg-years-back') != null ? $el.data('dg-years-back') : 5;

                            data = [{
                                value: '',
                                text: $el.data('dg-all-label') || 'All Year'
                            }];
                            for (var y = currentYear; y >= currentYear - yearsBack; y--) {
                                data.push({
                                    value: y,
                                    text: y
                                });
                            }
                        } else {
                            var raw = $el.attr('data-dg-options');
                            data = raw ? JSON.parse(raw) : [];
                        }

                        $el.combobox({
                            data: data,
                            valueField: $el.data('dg-value-field') || 'value',
                            textField: $el.data('dg-text-field') || 'text',
                            editable: false,
                            panelHeight: 'auto',
                            value: '',
                            onChange: function (newValue, oldValue) {
                                console.log(oldValue, '=>', newValue);
                                loadData(1);
                            }
                        });
                    });
                }

                /* =========================
                   LOAD DATA
                ========================= */
                function loadData(page) {
                    page = page || 1;

                    var query = { page: page };

                    $filterBar.find('[data-dg-filter]').each(function () {
                        var $el = $(this);
                        var key = $el.data('dg-filter');
                        var type = $el.data('dg-filter-type') || 'search';

                        query[key] = (type === 'combobox' || type === 'select')
                            ? $el.combobox('getValue')
                            : $el.val();
                    });

                    if (JSON.stringify(query) === JSON.stringify(lastQuery)) {
                        return;
                    }

                    lastQuery = query;
                    console.log(query);
                    console.log(lastQuery);

                    clearEmptyState();

                    $grid.datagrid('load', query);
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

            /* Trigger reload dari luar, misal setelah modal input sukses */
            EasyuiDG.reload = function (id, page) {
                var instance = EasyuiDG._instances[id];
                if (instance) instance.loadData(page || 1);
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
                       placeholder="{{ $searchPlaceholder }}">
            </div>
        @endif

        {{-- Default filter: buyer (combobox remote) --}}
        @if($buyer)
            <div class="p-0">
                <input data-dg-filter="{{ $buyerName }}"
                       data-dg-filter-type="combobox"
                       data-dg-url="{{ $buyerUrl }}"
                       data-dg-value-field="{{ $buyerValueField }}"
                       data-dg-text-field="{{ $buyerTextField }}"
                       data-dg-mode="{{ $buyerMode }}"
                       style="width:{{ $buyerWidth }}px">
            </div>
        @endif

        {{-- Default filter: tahun (select, auto generate range tahun) --}}
        @if($year)
            <div class="p-0">
                <input data-dg-filter="{{ $yearName }}"
                       data-dg-filter-type="select"
                       data-dg-generator="years"
                       data-dg-years-back="{{ $yearsBack }}"
                       data-dg-all-label="{{ $yearAllLabel }}"
                       style="width:{{ $yearWidth }}px">
            </div>
        @endif

        {{-- Filter tambahan khusus per halaman --}}
        {{ $filters ?? '' }}
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