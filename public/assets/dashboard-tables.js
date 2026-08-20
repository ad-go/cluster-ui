(function () {
    'use strict';

    var container = document.getElementById('tables-sunburst');
    if (!container || typeof echarts === 'undefined') return;

    var tables         = JSON.parse(container.dataset.tables);
    var strings        = JSON.parse(container.dataset.strings);
    var sizeSupported  = container.dataset.sizeSupported === '1';

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function humanSize(bytes) {
        var units = ['B', 'KB', 'MB', 'GB'];
        var value = bytes, i = 0;
        while (value >= 1024 && i < units.length - 1) { value /= 1024; i++; }
        return Math.round(value * 10) / 10 + ' ' + units[i];
    }

    // A handful of stable colors per table name (not per metric - a given
    // table should look the same color in all 3 rings so it's visually
    // trackable across them), cycled deterministically by name so the
    // same table always gets the same color across page loads.
    var PALETTE = ['#206bc4', '#4299e1', '#2fb344', '#f76707', '#ae3ec9', '#d63939', '#f59f00', '#0ca678', '#7048e8', '#e64980'];
    var tableNames = Object.keys(tables);
    var colorByTable = {};
    tableNames.forEach(function (name, i) { colorByTable[name] = PALETTE[i % PALETTE.length]; });

    // Each metric is its own ring segment (root -> metric -> table), not a
    // single ring keyed by one metric - size/records/traffic are different
    // units (bytes / row count / bytes) and can't share one arc's angle
    // meaningfully. Within a metric, each table's child value is
    // NORMALIZED to a percentage of that metric's own total (rather than
    // the raw byte/row count), so all 3 top-level wedges come out equal
    // thirds regardless of how the raw magnitudes compare across metrics -
    // only the proportions WITHIN a metric are meant to be compared. The
    // real value is kept on each node for the tooltip.
    function metricChildren(key, formatter) {
        var total = tableNames.reduce(function (sum, name) { return sum + (tables[name][key] || 0); }, 0);

        if (total <= 0) {
            return [{
                name: strings.noData,
                value: 100,
                itemStyle: { color: '#e9ecef' },
                label: { color: '#adb5bd' },
            }];
        }

        return tableNames
            .filter(function (name) { return (tables[name][key] || 0) > 0; })
            .map(function (name) {
                var raw = tables[name][key];
                return {
                    name: name,
                    value: (raw / total) * 100,
                    itemStyle: { color: colorByTable[name] },
                    tableData: tables[name],
                    rawValue: raw,
                    formatted: formatter(raw),
                };
            });
    }

    var categories = [];
    if (sizeSupported) {
        categories.push({ name: strings.size, key: 'sizeBytes', children: metricChildren('sizeBytes', humanSize) });
    }
    categories.push({ name: strings.records, key: 'records', children: metricChildren('records', function (v) { return String(v); }) });
    categories.push({ name: strings.traffic, key: 'trafficBytes', children: metricChildren('trafficBytes', humanSize) });

    var data = categories.map(function (cat) {
        return {
            name: cat.name,
            itemStyle: { color: '#495057' },
            children: cat.children,
        };
    });

    var chart = echarts.init(container);
    chart.setOption({
        tooltip: {
            formatter: function (params) {
                if (params.data.name === strings.noData) return escapeHtml(strings.noData);
                if (params.data.tableData === undefined) return escapeHtml(params.data.name);

                var t = params.data.tableData;
                var lines = ['<b>' + escapeHtml(params.data.name) + '</b>'];
                lines.push(strings.records + ': ' + t.records);
                lines.push(strings.size + ': ' + (t.sizeHuman !== null ? escapeHtml(t.sizeHuman) : '?'));
                lines.push(strings.traffic + ': ' + escapeHtml(t.trafficHuman));
                return lines.join('<br>');
            },
        },
        series: [{
            type: 'sunburst',
            radius: [0, '90%'],
            data: data,
            sort: null,
            label: { minAngle: 8 },
            emphasis: { focus: 'ancestor' },
            levels: [
                {},
                { r0: '15%', r: '40%', itemStyle: { borderWidth: 2 }, label: { rotate: 0, fontWeight: 'bold' } },
                { r0: '40%', r: '90%', label: { rotate: 'tangential', minAngle: 6 } },
            ],
        }],
    });
    window.addEventListener('resize', function () { chart.resize(); });
})();
