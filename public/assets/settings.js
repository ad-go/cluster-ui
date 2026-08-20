(function () {
    'use strict';
    var box = document.getElementById('settings-form');
    if (!box) return;
    var endpoint = box.dataset.endpoint;
    var logoEndpoint = box.dataset.logoEndpoint;
    var logoDeleteEndpoint = box.dataset.logoDeleteEndpoint;
    var savedBadge = document.getElementById('settings-saved');
    var showSaved = function () {
        savedBadge.classList.remove('d-none');
        clearTimeout(showSaved._t);
        showSaved._t = setTimeout(function () { savedBadge.classList.add('d-none'); }, 1500);
    };

    // See app.js's syncCsrf() for why this exists: Config\Security::
    // $regenerate is true, so every JSON response carries the NEXT valid
    // token and window.CI4_CSRF must be updated from it, every time.
    function syncCsrf(data) {
        if (data && data.csrf && data.csrf.name && data.csrf.hash) {
            window.CI4_CSRF = data.csrf;
        }
        return data;
    }

    var timers = {};
    box.querySelectorAll('[data-field]').forEach(function (el) {
        var send = function () {
            var body = new URLSearchParams();
            body.set('field', el.dataset.field);
            body.set('value', el.value);
            if (window.CI4_CSRF) body.set(window.CI4_CSRF.name, window.CI4_CSRF.hash);
            fetch(endpoint, { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                .then(syncCsrf)
                .then(function () { showSaved(); })
                .catch(function () { /* field left unsaved; next successful save will show the badge again */ });
        };
        var evt = el.tagName === 'SELECT' ? 'change' : 'input';
        el.addEventListener(evt, function () {
            clearTimeout(timers[el.dataset.field]);
            timers[el.dataset.field] = setTimeout(send, el.tagName === 'SELECT' ? 0 : 500);
        });
    });

    // Shared by the Nodes table (2 credential families: FTP/FTPS vs SSH/SCP)
    // and the Databases table (5: one per CI4-supported driver) - both are
    // "one row per node, N independent credential sets, a dropdown that
    // swaps which set the Host/Port/User/Pass columns show and re-saves
    // it" with only the family-selector and value->family mapping differing
    // (see app/Views/Settings/index.php's own comments on both tables).
    // familyOf(selectValue) maps the swap-select's current value to the
    // data-{family}-{role} attribute prefix used on the row.
    function bindReactiveTable(table, swapSelector, familyOf) {
        if (!table) return;
        var endpoint = table.dataset.endpoint;
        var rowTimers = {};
        table.querySelectorAll('[data-node][data-prop]').forEach(function (el) {
            var key = el.dataset.node + ':' + el.dataset.prop;
            var send = function () {
                var body = new URLSearchParams();
                body.set('node', el.dataset.node);
                body.set('prop', el.dataset.prop);
                body.set('value', el.value);
                if (window.CI4_CSRF) body.set(window.CI4_CSRF.name, window.CI4_CSRF.hash);
                fetch(endpoint, { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                    .then(syncCsrf)
                    .then(function () { showSaved(); })
                    .catch(function () { /* field left unsaved; next successful save will show the badge again */ });
            };
            var evt = el.tagName === 'SELECT' ? 'change' : 'input';
            el.addEventListener(evt, function () {
                clearTimeout(rowTimers[key]);
                rowTimers[key] = setTimeout(send, el.tagName === 'SELECT' ? 0 : 500);
            });
        });

        // Swap-select (Protocol on Nodes, Type on Databases) re-targets each
        // Host/Port/User/Pass field's data-prop to the newly-selected
        // family's key, from values already preloaded into the row's own
        // data-{family}-* attributes - no round trip needed. Re-dispatches
        // each field's own input/change event so the SAME autosave listener
        // bound just above (not a second fetch path here) is what actually
        // persists it - "switching family also saves automatically" without
        // two save paths to keep in sync.
        table.querySelectorAll(swapSelector).forEach(function (select) {
            select.addEventListener('change', function () {
                var tr = select.closest('tr');
                var family = familyOf(select.value);
                // 'database' is a no-op on the Nodes table (no data-field-role
                //="database" input there, the guard below just skips it) -
                // shared list works for both tables without a second copy.
                ['database', 'host', 'port', 'user', 'pass'].forEach(function (role) {
                    var input = tr.querySelector('[data-field-role="' + role + '"]');
                    if (!input) return;
                    var datasetKey = family + role.charAt(0).toUpperCase() + role.slice(1);
                    input.dataset.prop = datasetKey;
                    input.value = tr.dataset[datasetKey] || '';
                    input.dispatchEvent(new Event(input.tagName === 'SELECT' ? 'change' : 'input'));
                });
            });
        });
    }

    bindReactiveTable(document.getElementById('settings-nodes'), '[data-protocol-select]', function (value) {
        return (value === 'SSH' || value === 'SCP') ? 'ssh' : 'ftp';
    });
    // Databases table's Type values ARE the family names already (mysql/
    // postgres/sqlite3/oci8/sqlsrv - see SettingsController::DATABASE_TYPES),
    // no mapping needed.
    bindReactiveTable(document.getElementById('settings-databases'), '[data-type-select]', function (value) {
        return value;
    });

    // ONE shared modal for both the Nodes and Databases tables' node-name
    // badges - a REAL, live connect test against whatever credentials are
    // currently saved on that row (server side does the actual connecting -
    // see SettingsController::testNode()/testDatabase()), same idea as
    // this project's own one-off verification scripts used by hand
    // throughout setup, just permanent and in-app now. summaryFn builds the
    // success-case summary line from the response (the two endpoints
    // return different fields - driver/version for a database, protocol/
    // detail for a node - failure/loading/error handling is identical
    // either way, so only that one line varies per table).
    var connModalEl = document.getElementById('conn-test-modal');
    var connModalTitle = document.getElementById('conn-test-modal-title');
    var connModalLoading = document.getElementById('conn-test-modal-loading');
    var connModalWaiting = document.getElementById('conn-test-modal-waiting');
    var connModalResult = document.getElementById('conn-test-modal-result');
    var connModalBadge = document.getElementById('conn-test-modal-badge');
    var connModalSummary = document.getElementById('conn-test-modal-summary');
    var connModalDetail = document.getElementById('conn-test-modal-detail');
    // Result endpoint is shared/global (a requestId is already a unique
    // opaque token - see SettingsController::testResult()), read off the
    // modal itself rather than either table's own data-test-endpoint -
    // this is a plain static .js file, not a PHP view, so the URL has to
    // arrive via a data attribute (see index.php), not url_to() inline.
    var connTestResultEndpoint = connModalEl ? connModalEl.dataset.testResultEndpoint : '';
    // Bounds how long a NAT-target poll waits before giving up client-side
    // (see dispatchTest()'s own docblock on why this is async at all) -
    // generous relative to the plain once-a-minute cron cadence, so a
    // normal wait doesn't false-timeout; a node that's actually offline
    // still resolves in a reasonable time rather than spinning forever.
    var CONN_TEST_POLL_MS = 1500;
    var CONN_TEST_TIMEOUT_MS = 90000;
    var connPollTimer = null;

    function connModal() {
        return window.bootstrap ? bootstrap.Modal.getOrCreateInstance(connModalEl) : null;
    }

    function stopPolling() {
        if (connPollTimer) {
            clearTimeout(connPollTimer);
            connPollTimer = null;
        }
    }

    function showConnResult(ok, summaryText, errorText, testStrings) {
        connModalLoading.classList.add('d-none');
        connModalWaiting.classList.add('d-none');
        connModalResult.classList.remove('d-none');
        connModalBadge.className = 'badge ' + (ok ? 'bg-green-lt' : 'bg-red-lt');
        connModalBadge.textContent = (ok ? testStrings.ok : testStrings.failed) || (ok ? 'OK' : 'FAILED');
        connModalSummary.textContent = summaryText || '';
        connModalDetail.textContent = errorText || '';
    }

    function pollForResult(node, requestId, summaryFn, testStrings, deadline) {
        stopPolling();
        connPollTimer = setTimeout(function () {
            fetch(connTestResultEndpoint + '?requestId=' + encodeURIComponent(requestId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(syncCsrf)
                .then(function (data) {
                    if (data.pending) {
                        if (Date.now() >= deadline) {
                            showConnResult(false, '', (testStrings.timeout || 'Timed out.').replace('{0}', node), testStrings);

                            return;
                        }
                        pollForResult(node, requestId, summaryFn, testStrings, deadline);

                        return;
                    }
                    if (data.ok) {
                        showConnResult(true, summaryFn(data), '', testStrings);
                    } else {
                        showConnResult(false, data.ms !== undefined ? (data.ms + 'ms') : '', data.error || '', testStrings);
                    }
                })
                .catch(function () {
                    showConnResult(false, '', '', testStrings);
                });
        }, CONN_TEST_POLL_MS);
    }

    function bindTestModal(table, summaryFn) {
        if (!table || !connModalEl) return;
        var testEndpoint = table.dataset.testEndpoint;
        var testStrings = JSON.parse(table.dataset.testStrings || '{}');

        table.querySelectorAll('[data-test-conn]').forEach(function (badge) {
            badge.addEventListener('click', function () {
                var node = badge.dataset.testConn;
                stopPolling();
                connModalTitle.textContent = node;
                connModalLoading.classList.remove('d-none');
                connModalWaiting.classList.add('d-none');
                connModalResult.classList.add('d-none');
                var m = connModal();
                if (m) m.show();

                var body = new URLSearchParams();
                body.set('node', node);
                if (window.CI4_CSRF) body.set(window.CI4_CSRF.name, window.CI4_CSRF.hash);
                fetch(testEndpoint, { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(syncCsrf)
                    .then(function (data) {
                        if (data.pending && data.requestId) {
                            // NAT target - see dispatchTest()'s own
                            // docblock. No instant result yet; show why
                            // (not just a bare spinner) and start polling.
                            connModalWaiting.textContent = (testStrings.waiting || 'Waiting for {0}...').replace('{0}', node);
                            connModalWaiting.classList.remove('d-none');
                            pollForResult(node, data.requestId, summaryFn, testStrings, Date.now() + CONN_TEST_TIMEOUT_MS);

                            return;
                        }
                        if (data.ok) {
                            showConnResult(true, summaryFn(data), '', testStrings);
                        } else {
                            showConnResult(false, data.ms !== undefined ? (data.ms + 'ms') : '', data.error || '', testStrings);
                        }
                    })
                    .catch(function () {
                        showConnResult(false, '', '', testStrings);
                    });
            });
        });
    }

    bindTestModal(document.getElementById('settings-databases'), function (data) {
        return data.driver + ' - ' + data.version + ' (' + data.ms + 'ms)';
    });
    bindTestModal(document.getElementById('settings-nodes'), function (data) {
        return data.protocol + (data.detail ? ' - ' + data.detail : '') + ' (' + data.ms + 'ms)';
    });

    ['conn-test-modal-close', 'conn-test-modal-ok'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('click', function () {
            var m = connModal();
            if (m) m.hide();
        });
    });

    var logoInput = document.getElementById('settings-logo');
    var logoThumbWrap = document.getElementById('settings-logo-thumb-wrap');
    var logoThumb = document.getElementById('settings-logo-thumb');
    if (logoInput) {
        logoInput.addEventListener('change', function () {
            if (!logoInput.files[0]) return;
            var fd = new FormData();
            fd.append('logo', logoInput.files[0]);
            if (window.CI4_CSRF) fd.append(window.CI4_CSRF.name, window.CI4_CSRF.hash);
            fetch(logoEndpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                .then(syncCsrf)
                .then(function (data) {
                    showSaved();
                    if (data && data.path) {
                        logoThumb.src = '/' + data.path;
                        logoThumbWrap.classList.remove('d-none');
                    }
                })
                .catch(function () {});
        });
    }

    var logoDeleteBtn = document.getElementById('settings-logo-delete-btn');
    if (logoDeleteBtn) {
        logoDeleteBtn.addEventListener('click', function () {
            var body = new URLSearchParams();
            if (window.CI4_CSRF) body.set(window.CI4_CSRF.name, window.CI4_CSRF.hash);
            fetch(logoDeleteEndpoint, { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(syncCsrf)
                .then(function (data) {
                    if (data && data.ok) {
                        logoThumbWrap.classList.add('d-none');
                        logoInput.value = '';
                    }
                });
        });
    }

    // Import button (see app/Views/Settings/index.php - shared toolbar
    // above the Nodes table, covers both Nodes and Databases). The file
    // input's own 'change' event does the picking; clicking the visible
    // button just proxies to the hidden <input type=file> the same way
    // the logo picker above does.
    var importBox = document.getElementById('settings-export-import');
    if (importBox) {
        var importEndpoint = importBox.dataset.importEndpoint;
        var importStrings  = JSON.parse(importBox.dataset.importStrings || '{}');
        var importBtn      = document.getElementById('settings-import-btn');
        var importInput    = document.getElementById('settings-import-file');
        var importError    = document.getElementById('settings-import-error');

        importBtn.addEventListener('click', function () { importInput.click(); });

        importInput.addEventListener('change', function () {
            if (!importInput.files[0]) return;
            importError.classList.add('d-none');
            var fd = new FormData();
            fd.append('file', importInput.files[0]);
            if (window.CI4_CSRF) fd.append(window.CI4_CSRF.name, window.CI4_CSRF.hash);
            fetch(importEndpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                .then(function (result) {
                    syncCsrf(result.data);
                    if (result.ok && result.data && result.data.ok) {
                        showSaved();
                        // Nodes/Databases tables were seeded server-side from
                        // Settings on page load - a full reload is the
                        // simplest way to reflect what import just
                        // overwrote, same as any other bulk server-side change.
                        location.reload();
                        return;
                    }
                    var msg = (result.data && result.data.error) ? result.data.error : '';
                    importError.textContent = importStrings.failed ? importStrings.failed.replace('{0}', msg) : msg;
                    importError.classList.remove('d-none');
                })
                .catch(function () {
                    importError.textContent = importStrings.failed ? importStrings.failed.replace('{0}', '') : '';
                    importError.classList.remove('d-none');
                })
                .finally(function () { importInput.value = ''; });
        });
    }
})();
