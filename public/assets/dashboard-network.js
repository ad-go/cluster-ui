(function () {
    'use strict';

    var container = document.getElementById('net-graph');
    if (!container || typeof echarts === 'undefined') return;

    var strings = JSON.parse(container.dataset.strings);
    var statusUrl = container.dataset.statusUrl;

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function timeAgo(timestamp) {
        if (timestamp === null) return strings.never;
        var seconds = Math.max(0, Math.floor(Date.now() / 1000) - timestamp);
        if (seconds < 60) return strings.justNow;
        if (seconds < 3600) return strings.minutesAgo.replace('{0}', Math.floor(seconds / 60));
        if (seconds < 86400) return strings.hoursAgo.replace('{0}', Math.floor(seconds / 3600));
        return strings.daysAgo.replace('{0}', Math.floor(seconds / 86400));
    }

    function humanSize(bytes) {
        var units = ['B', 'KB', 'MB', 'GB'];
        var value = bytes, i = 0;
        while (value >= 1024 && i < units.length - 1) { value /= 1024; i++; }
        return Math.round(value * 10) / 10 + ' ' + units[i];
    }

    var CATEGORIES = [
        { name: 'self' },
        { name: 'public' },
        { name: 'nat' },
    ];
    var COLORS = { self: '#206bc4', ok: '#2fb344', bad: '#d63939', idle: '#8c959e' };

    // Node circle size scales (sqrt, not linear - a peer with 100x the
    // transfer volume of another shouldn't render 100x the radius, or the
    // small ones become invisible dots) between a fixed floor and ceiling
    // so an all-zero cluster (nothing synced yet) still renders legible
    // circles instead of collapsing to nothing.
    function nodeSize(bytes, maxBytes) {
        if (maxBytes <= 0) return 45;
        var ratio = Math.sqrt(bytes / maxBytes);
        return 35 + ratio * 45;
    }

    function buildOption(info) {
        var nodeNames = Object.keys(info.nodes);
        var maxBytes = nodeNames.reduce(function (max, name) {
            return Math.max(max, info.nodes[name].totalBytes);
        }, 0);

        var nodes = [{
            id: 'self',
            name: info.thisNode || strings.thisNode,
            category: 0,
            symbolSize: 60,
            itemStyle: { color: COLORS.self },
            label: { show: true, fontWeight: 'bold' },
        }];

        var links = nodeNames.map(function (name) {
            var node = info.nodes[name];
            var isNat = node.type === 'nat';
            var ok = isNat ? (node.lastPushInAt !== null) : node.lastSyncOk;
            var color = ok === null || ok === undefined ? COLORS.idle : (ok ? COLORS.ok : COLORS.bad);

            nodes.push({
                id: name,
                name: name,
                category: isNat ? 2 : 1,
                symbolSize: nodeSize(node.totalBytes, maxBytes),
                itemStyle: { color: color },
                label: { show: true },
                value: node,
            });

            return {
                source: 'self',
                target: name,
                lineStyle: {
                    color: color,
                    width: node.avgSpeedBps > 0 ? Math.min(10, 1 + Math.log10(1 + node.avgSpeedBps / 1024)) : 1,
                },
                label: {
                    show: node.avgSpeedBps > 0,
                    formatter: humanSize(node.avgSpeedBps) + '/s',
                    fontSize: 10,
                },
            };
        });

        return {
            tooltip: {
                formatter: function (params) {
                    if (params.dataType === 'edge') return '';
                    if (params.data.id === 'self') {
                        return '<b>' + escapeHtml(params.data.name) + '</b><br>' + escapeHtml(strings.thisNode);
                    }
                    var n = params.data.value;
                    var typeLabel = n.type === 'nat' ? strings.nat : strings.public;
                    var lines = [
                        '<b>' + escapeHtml(params.data.name) + '</b> (' + escapeHtml(typeLabel) + ')',
                        escapeHtml(n.baseURL),
                        strings.transfer + ': ' + escapeHtml(humanSize(n.totalBytes)),
                        strings.speed + ': ' + escapeHtml(humanSize(n.avgSpeedBps)) + '/s',
                    ];
                    if (n.type === 'nat') {
                        lines.push(strings.lastPushIn + ': ' + escapeHtml(timeAgo(n.lastPushInAt)));
                    } else {
                        lines.push(strings.lastSync + ': ' + escapeHtml(timeAgo(n.lastSyncAt)));
                    }
                    lines.push(strings.lastPull + ': ' + escapeHtml(timeAgo(n.lastPullAt)));
                    if (n.errors > 0) {
                        lines.push('<span style="color:' + COLORS.bad + '">' + escapeHtml(strings.errors) + ': ' + n.errors + '</span>');
                    }
                    return lines.join('<br>');
                },
            },
            series: [{
                type: 'graph',
                layout: 'force',
                roam: true,
                draggable: true,
                categories: CATEGORIES,
                force: { repulsion: 250, edgeLength: 160, gravity: 0.1 },
                edgeSymbol: ['none', 'arrow'],
                edgeSymbolSize: 8,
                data: nodes,
                links: links,
                lineStyle: { curveness: 0.1 },
            }],
        };
    }

    // Broadcast every info payload this script fetches (initial render AND
    // each 5s poll) so other charts on this page that want the SAME
    // per-node data (e.g. dashboard-node-tree.js) don't need their own
    // redundant poll against the same endpoint - one fetch, N listeners.
    function broadcast(info) {
        window.dispatchEvent(new CustomEvent('cluster:network-info', { detail: info }));
    }

    // Table below the graph - node/avg speed/traffic, one row per peer.
    // Plain HTML rather than squeezing more onto the graph itself, same
    // reasoning as dashboard-node-tree.js's own table.
    var speedTableBody = document.getElementById('net-graph-table-body');
    function updateSpeedTable(info) {
        if (!speedTableBody) return;
        var nodeNames = Object.keys(info.nodes);
        speedTableBody.innerHTML = nodeNames.map(function (name) {
            var n = info.nodes[name];
            var typeLabel = n.type === 'nat' ? strings.nat : strings.public;
            return '<tr>' +
                '<td>' + escapeHtml(name) + ' <span class="badge bg-secondary-lt">' + escapeHtml(typeLabel) + '</span></td>' +
                '<td class="text-end">' + escapeHtml(n.avgSpeedHuman) + '</td>' +
                '<td class="text-end">' + escapeHtml(n.lastTransferBytesHuman) + '</td>' +
                '</tr>';
        }).join('');
    }

    var initialInfo = JSON.parse(container.dataset.network);
    var chart = echarts.init(container);
    chart.setOption(buildOption(initialInfo));
    updateSpeedTable(initialInfo);
    broadcast(initialInfo);
    window.addEventListener('resize', function () { chart.resize(); });

    function updateSummary(info) {
        document.getElementById('net-thisnode').textContent = info.thisNode || '-';
        document.getElementById('net-speed').textContent = info.avgSpeedHuman;
        document.getElementById('net-transfer').textContent = info.recentBytesHuman;
        document.getElementById('net-synced').textContent = info.syncedFiles;
        document.getElementById('net-pending').textContent = info.pendingFiles;
        document.getElementById('net-db-ok').textContent = info.dbRecentOk;
        document.getElementById('net-db-errors').textContent = info.dbRecentErrors;

        document.getElementById('net-session').innerHTML = info.sessionWouldSurvive
            ? '<span class="text-success" title="' + escapeHtml(strings.sessionOkTitle) + '">' + escapeHtml(strings.sessionOk) + '</span>'
            : '<span class="text-danger" title="' + escapeHtml(strings.sessionKilledTitle) + '">' + escapeHtml(strings.sessionKilled) + '</span>';

        document.getElementById('net-badge').outerHTML = info.configured
            ? '<span id="net-badge" class="badge bg-green-lt">' + escapeHtml(strings.active) + '</span>'
            : '<span id="net-badge" class="badge bg-secondary-lt">' + escapeHtml(strings.notConfigured) + '</span>';
    }

    function poll() {
        fetch(statusUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                var info = data && data.networkInfo;
                if (!info) return;
                chart.setOption(buildOption(info));
                updateSummary(info);
                updateSpeedTable(info);
                broadcast(info);
            })
            .catch(function () { /* transient network hiccup - next poll retries */ });
    }

    setInterval(poll, 5000);
})();
