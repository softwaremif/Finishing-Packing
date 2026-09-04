@once
<script>
(function (global) {
    'use strict';
    var BsTable = { _instances: {} };

    BsTable.init = function (cfg) {
        var $ = global.jQuery;
        if (!$) { console.error('BsTable requires jQuery.'); return; }

        var id = cfg.id;
        var $wrap       = $('#' + id + '_wrap');
        var $filterBar  = $('#' + id + '_filterbar');
        var $chipsWrap  = $('#' + id + '_chips_wrap');
        var $chipsBar   = $('#' + id + '_chips');
        var $table      = $('#' + id + '_table');
        var $tbody      = $('#' + id + '_body');
        var $empty      = $('#' + id + '_empty');
        var $pageSize   = $('#' + id + '_pagesize');
        var $prevBtn    = $('#' + id + '_prev');
        var $nextBtn    = $('#' + id + '_next');
        var $pageLabel  = $('#' + id + '_pagelabel');
        var $info       = $('#' + id + '_info');

        var state = {
            page: 1,
            rows: cfg.pageSize || 50,
            total: 0,
            lastRows: []
        };
        var timer = null;

        // Baca definisi kolom dari <thead> yang di-render caller lewat slot.
       var columns = parseColumnsFromThead($table);
        function parseColumnsFromThead($table) {
            var rows = $table.find('thead tr').toArray();
            var grid = {};        // grid[r][c] = true kalau sudah terisi (oleh rowspan/colspan sebelumnya)
            var colsByIndex = {}; // kolom DATA (yang punya data-field), key = index kolom grid sebenarnya
        
            function isOccupied(r, c) { return grid[r] && grid[r][c]; }
            function occupy(r, c) { if (!grid[r]) grid[r] = {}; grid[r][c] = true; }
        
            rows.forEach(function (tr, r) {
                var c = 0;
                $(tr).children('th').each(function () {
                    var $th = $(this);
                    while (isOccupied(r, c)) c++; // lompat kolom yang sudah "dipakai" oleh rowspan/colspan cell sebelumnya
        
                    var colspan = parseInt($th.attr('colspan'), 10) || 1;
                    var rowspan = parseInt($th.attr('rowspan'), 10) || 1;
        
                    // HANYA cell yang punya atribut data-field yang dianggap kolom
                    // DATA -- header grup (mis. "Size", colspan>1, TANPA data-field)
                    // TIDAK dicatat sebagai kolom, cuma menandai occupancy grid saja.
                    if ($th.is('[data-field]')) {
                        colsByIndex[c] = {
                            field: $th.data('field') || '',
                            align: $th.data('align') || 'left',
                            formatter: $th.data('formatter') || null
                        };
                    }
        
                    for (var i = 0; i < rowspan; i++) {
                        for (var j = 0; j < colspan; j++) occupy(r + i, c + j);
                    }
                    c += colspan;
                });
            });
        
            return Object.keys(colsByIndex)
                .map(Number)
                .sort(function (a, b) { return a - b; })
                .map(function (i) { return colsByIndex[i]; });
        }

        BsTable._instances[id] = { 
            loadData: loadData, 
            reload: function (p) { loadData(p || state.page, true); },
            getRow: function (index) { return state.lastRows[index]; }
        };

        BsTable.getRow = function (id, index) {
            var inst = BsTable._instances[id];
            return inst ? inst.getRow(index) : null;
        };

        bindFilter();
        loadData(1, true);

        function bindFilter() {
            $filterBar.find('[data-dg-filter-type="search"]').on('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () { loadData(1); }, 400);
            }).on('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); loadData(1, true); }
            });

            $filterBar.find('[data-dg-filter-type="combobox"]').each(function () {
                var $el = $(this);
                $el.combobox({
                    method: 'get', url: $el.data('dg-url'),
                    valueField: $el.data('dg-value-field') || 'value',
                    textField: $el.data('dg-text-field') || 'text',
                    panelHeight: 300, editable: true, mode: $el.data('dg-mode') || 'remote',
                    onSelect: function () { loadData(1); },
                    onChange: function () { clearTimeout(timer); timer = setTimeout(function () { loadData(1); }, 300); }
                });
                $el.data('dg-initial-value', '');
            });

            $filterBar.find('[data-dg-filter-type="select"]').each(function () {
                var $el = $(this);
                var data, defaultValue = '';
                if ($el.data('dg-generator') === 'years') {
                    var cy = new Date().getFullYear();
                    var back = $el.data('dg-years-back') != null ? $el.data('dg-years-back') : 5;
                    var fwd  = $el.data('dg-years-forward') != null ? $el.data('dg-years-forward') : 1;
                    data = [];
                    for (var y = cy + fwd; y >= cy - back; y--) data.push({ value: y, text: y });
                    defaultValue = cy;
                } else {
                    var raw = $el.attr('data-dg-options');
                    data = raw ? JSON.parse(raw) : [];
                    defaultValue = $el.attr('data-dg-default') || '';
                }
                $el.combobox({
                    data: data,
                    valueField: $el.data('dg-value-field') || 'value',
                    textField: $el.data('dg-text-field') || 'text',
                    editable: false,
                    panelHeight: $el.data('dg-panel-height') != null ? $el.data('dg-panel-height') : 'auto',
                    value: defaultValue,
                    onChange: function () { loadData(1); }
                });
                $el.data('dg-initial-value', String(defaultValue));
            });

            // ============================================================
            // SORT -- card dropdown (Terbaru/Terlama, atau custom label lain).
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
                var val = $opt.data('sort-value');
                var icon = $opt.data('sort-icon') || 'fa-arrow-down';
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

            $filterBar.find('[data-dg-filter]')
                .not('[data-dg-filter-type="search"]')
                .not('[data-dg-filter-type="combobox"]')
                .not('[data-dg-filter-type="select"]')
                .not('[data-dg-filter-type="sort-dropdown"]')
                .on('change', function () { loadData(1); });

            $pageSize.on('change', function () {
                state.rows = parseInt($(this).val()) || 50;
                loadData(1, true);
            });
            $prevBtn.on('click', function () { if (state.page > 1) loadData(state.page - 1, true); });
            $nextBtn.on('click', function () {
                var maxPage = Math.max(1, Math.ceil(state.total / state.rows));
                if (state.page < maxPage) loadData(state.page + 1, true);
            });
        }

        function loadData(page, force) {
            page = page || 1;
            var query = { page: page, rows: state.rows };
            var chipItems = [];

            $filterBar.find('[data-dg-filter]').each(function () {
                var $el = $(this);
                var key = $el.data('dg-filter');
                var type = $el.data('dg-filter-type') || 'search';
                var val;
                if (type === 'combobox' || type === 'select') {
                    try { val = $el.combobox('getValue'); } catch (e) { val = ''; }
                } else if (type === 'sort-dropdown') {
                    val = $el.attr('data-value');
                } else {
                    val = $el.val();
                }
                val = (val === null || val === undefined) ? '' : val;
                query[key] = val;

                var hideChip = $el.data('dg-chip-hide') === true;
                var isSortType = (type === 'sort-dropdown');
                var initialVal = $el.data('dg-initial-value');
                var isStillDefault = (initialVal !== undefined && String(val) === initialVal);

                if (!hideChip && !isSortType && val !== '' && !isStillDefault) {
                    chipItems.push({ key: key, label: $el.data('dg-chip-label') || key, value: val, text: getDisplayText($el, type) });
                }
            });

            renderChips(chipItems);

            state.page = page;

           $.get(cfg.url, query, function (data) {
                state.total = data.total || 0;
                state.lastRows = data.rows || [];
            
                if (typeof cfg.onLoadSuccess === 'function') {
                    cfg.onLoadSuccess(data); 
                }
            
                render(state.lastRows);
            }).fail(function () {
                showEmpty(cfg.emptyTitleError || 'Gagal memuat data', 'Silakan coba lagi');
            });
        }

        function render(rows) {
            $tbody.empty();
            if (!rows.length) {
                showEmpty(cfg.emptyTitle || 'No Data Found', cfg.emptyDesc || 'Try changing filter');
                updatePager();
                return;
            }
            $empty.addClass('d-none');
            $table.removeClass('d-none');

            rows.forEach(function (row, index) {
                var tds = columns.map(function (col) {
                    var raw = col.field ? row[col.field] : null;
                    var html = col.formatter && typeof global[col.formatter] === 'function'
                        ? global[col.formatter](raw, row, index)
                        : (raw ?? '');
                    return '<td class="text-' + col.align + '">' + html + '</td>';
                }).join('');
                $tbody.append('<tr>' + tds + '</tr>');
            });
            updatePager();
        }

        function showEmpty(title, subtitle) {
            $tbody.empty();
            $table.addClass('d-none');
            $empty.removeClass('d-none').html(
                '<div class="bs-empty-state">' +
                    (cfg.emptyImage ? '<img src="' + cfg.emptyImage + '" width="160">' : '') +
                    '<div class="fw-semibold mt-2">' + title + '</div>' +
                    '<div class="text-secondary" style="font-size:12px;">' + subtitle + '</div>' +
                '</div>'
            );
        }

        function updatePager() {
            var maxPage = Math.max(1, Math.ceil(state.total / state.rows));
            $info.text(state.total + ' data');
            $pageLabel.text('Halaman ' + state.page + ' / ' + maxPage);
            $prevBtn.prop('disabled', state.page <= 1);
            $nextBtn.prop('disabled', state.page >= maxPage);
        }

        function getDisplayText($el, type) {
            if (type === 'combobox' || type === 'select') {
                try { var t = $el.combobox('getText'); if (t) return t; } catch (e) {}
                try { return $el.combobox('getValue'); } catch (e2) { return ''; }
            }
            return $el.val();
        }
        function escapeHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function renderChips(items) {
            if (!$chipsWrap.length) return;
            if (!items.length) { $chipsWrap.hide(); $chipsBar.empty(); return; }
            $chipsBar.html(items.map(function (item) {
                return '<span class="active-filter-chip" data-chip-key="' + item.key + '">' +
                    '<span>' + escapeHtml(item.label) + ': ' + escapeHtml(item.text || item.value) + '</span>' +
                    '<button type="button" class="chip-close">&times;</button></span>';
            }).join(''));
            $chipsWrap.css('display', 'flex');
        }
        $chipsBar.on('click', '.chip-close', function () {
            clearFilterByKey($(this).closest('.active-filter-chip').data('chip-key'));
        });
        $chipsWrap.on('click', '.chip-clear-all', function () {
            $filterBar.find('[data-dg-filter]').each(function () {
                var $el = $(this);
                var type = $el.data('dg-filter-type') || 'search';
                if (!$el.data('dg-chip-hide') && type !== 'sort-dropdown') resetFilterElement($el, type);
            });
            loadData(1, true);
        });
        function resetFilterElement($el, type) {
            var resetTo = $el.data('dg-initial-value');
            resetTo = (resetTo === undefined || resetTo === null) ? '' : resetTo;
            if (type === 'combobox' || type === 'select') {
                try { $el.combobox('setValue', resetTo); } catch (e) {}
            } else {
                $el.val(resetTo);
            }
        }
        function clearFilterByKey(key) {
            var $el = $filterBar.find('[data-dg-filter="' + key + '"]');
            if (!$el.length) return;
            resetFilterElement($el, $el.data('dg-filter-type') || 'search');
            loadData(1, true);
        }
    };

    BsTable.reload = function (id, page) {
        var inst = BsTable._instances[id];
        if (inst) inst.reload(page);
    };

    global.BsTable = BsTable;
})(window);
</script>
@endonce