(function () {
    'use strict';

    var container = document.getElementById('node-tree');
    if (!container || typeof echarts === 'undefined') return;

    var strings = JSON.parse(container.dataset.strings);

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

    // Bare host/IP, not the full URL - shown in the table below the chart
    // (see updateTable()). For 'nat' peers this reduces to exactly the
    // literal IP (e.g. "192.168.0.253").
    function hostOnly(url) {
        return String(url || '').replace(/^https?:\/\//i, '').replace(/\/$/, '');
    }

    var COLORS = { self: '#206bc4', ok: '#2fb344', bad: '#d63939', idle: '#8c959e' };

    // Same "how healthy does this peer look" rule dashboard-network.js
    // uses for its own node/edge coloring - kept in sync intentionally so
    // the same peer reads the same color across both charts.
    function healthColor(node) {
        var ok = node.type === 'nat' ? (node.lastPushInAt !== null) : node.lastSyncOk;
        return ok === null || ok === undefined ? COLORS.idle : (ok ? COLORS.ok : COLORS.bad);
    }

    var chart = echarts.init(container);
    window.addEventListener('resize', function () { chart.resize(); });

    function buildOption(info) {
        var nodeNames = Object.keys(info.nodes);

        var children = nodeNames.map(function (name) {
            var node = info.nodes[name];
            return {
                name: name,
                value: node,
                itemStyle: { color: healthColor(node) },
                symbolSize: 16,
            };
        });

        var root = {
            name: info.thisNode || strings.thisNode,
            value: null,
            itemStyle: { color: COLORS.self },
            symbolSize: 20,
            children: children,
        };

        return {
            tooltip: {
                formatter: function (params) {
                    if (!params.data.value) {
                        return '<b>' + escapeHtml(params.data.name) + '</b><br>' + escapeHtml(strings.thisNode);
                    }
                    var n = params.data.value;
                    var typeLabel = n.type === 'nat' ? strings.nat : strings.public;
                    return [
                        '<b>' + escapeHtml(params.data.name) + '</b> (' + escapeHtml(typeLabel) + ')',
                        escapeHtml(n.baseURL),
                        strings.files + ': ' + n.filesSynced,
                        strings.size + ': ' + escapeHtml(n.totalBytesHuman),
                        strings.speed + ': ' + escapeHtml(n.avgSpeedHuman),
                        strings.traffic + ': ' + escapeHtml(n.lastTransferBytesHuman),
                    ].join('<br>');
                },
            },
            series: [{
                type: 'tree',
                data: [root],
                layout: 'orthogonal',
                orient: 'LR',
                symbol: 'circle',
                expandAndCollapse: false,
                label: {
                    position: 'top',
                    verticalAlign: 'middle',
                    align: 'center',
                    fontSize: 12,
                },
                // Just the name - found live 2026-08-19 that a second
                // baseURL/IP line here (even truncated) either overflowed
                // this card's narrow 3-column width or got clipped
                // unreadably short. The address now lives in the table
                // below the chart instead (see updateTable()), where a
                // full column width is actually available for it.
                leaves: {
                    label: { position: 'right', verticalAlign: 'middle', align: 'left', fontSize: 12 },
                },
                lineStyle: { color: '#c1c9d2', width: 1.5, curveness: 0.4 },
                emphasis: { focus: 'ancestor' },
            }],
        };
    }

    // Table below the chart - node/address/files transferred/size, one row
    // per peer. Plain HTML, not part of the chart itself: a table column
    // has a real, honest width to work with, unlike a leaf label crammed
    // into whatever horizontal space the tree layout leaves in this
    // card's third of the row.
    var tableBody = document.getElementById('node-tree-table-body');
    function updateTable(info) {
        if (!tableBody) return;
        var nodeNames = Object.keys(info.nodes);
        tableBody.innerHTML = nodeNames.map(function (name) {
            var n = info.nodes[name];
            var typeLabel = n.type === 'nat' ? strings.nat : strings.public;
            return '<tr>' +
                '<td>' + escapeHtml(name) + ' <span class="badge bg-secondary-lt">' + escapeHtml(typeLabel) + '</span></td>' +
                '<td>' + escapeHtml(hostOnly(n.baseURL)) + '</td>' +
                '<td class="text-end">' + n.filesSynced + '</td>' +
                '<td class="text-end">' + escapeHtml(n.totalBytesHuman) + '</td>' +
                '</tr>';
        }).join('');
    }

    // Deferred scripts run in DOCUMENT order, not load-completion order -
    // dashboard-network.js (listed before this one) broadcasts its INITIAL
    // payload synchronously as part of its own top-level execution, which
    // finishes before this script's addEventListener below ever runs, so
    // that first broadcast would otherwise be missed and the tree would
    // sit empty until the first 5s poll. Reading #net-graph's own
    // data-network directly for the FIRST render sidesteps the ordering
    // entirely; the event listener below only needs to cover updates from
    // then on.
    var netGraph = document.getElementById('net-graph');
    if (netGraph && netGraph.dataset.network) {
        var initialInfo = JSON.parse(netGraph.dataset.network);
        chart.setOption(buildOption(initialInfo));
        updateTable(initialInfo);
    }

    window.addEventListener('cluster:network-info', function (e) {
        chart.setOption(buildOption(e.detail));
        updateTable(e.detail);
    });
})();
