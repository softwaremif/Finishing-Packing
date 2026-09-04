<script>
(function (global, $) {
    'use strict';

    if (!$) {
        console.error('EasyuiDG requires jQuery to be loaded first.');
        return;
    }

    var EasyuiDG = {
        _instances: {}
    };

    EasyuiDG.init = function (cfg) {
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
                console.log('LOAD ERROR');
                showEmpty();
            }
        });

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

})(window, window.jQuery);
</script>