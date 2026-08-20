(function () {
    'use strict';

    // Bootstrap's own [data-bs-toggle="dropdown"] data-api click delegation
    // does not reliably open these navbar dropdowns on this build (found
    // live 2026-08-18 - manually creating a bootstrap.Dropdown instance and
    // calling .show() always works, but a real/synthetic click on the
    // toggle does not, even though window.bootstrap is present; tabler.min.js
    // also touches dropdown/bs-toggle internals and is the likely source of
    // the conflict). Wired explicitly instead, same fix already applied to
    // the Users modal - bypasses the data-api layer entirely.
    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function (toggle) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            if (window.bootstrap) bootstrap.Dropdown.getOrCreateInstance(toggle).toggle();
        });
    });

    // Dashboard's collapsible card headers (System info / Packages).
    // Deliberately NOT data-bs-toggle="collapse" - found live 2026-08-18
    // that Bootstrap's own delegated data-api handler (tabler.min.js) DOES
    // fire on that attribute (unlike the dropdown case above, where it
    // silently did nothing) and raced with a hand-wired listener: the class
    // got toggled off but aria-expanded stayed stale, an inconsistent state
    // from two handlers fighting over the same click. Using a plain
    // data-collapse-toggle/data-collapse-target attribute instead means
    // Bootstrap's data-api selector never matches these elements at all -
    // full bypass, not a race.
    document.querySelectorAll('[data-collapse-toggle]').forEach(function (toggle) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            var target = document.querySelector(toggle.getAttribute('data-collapse-target'));
            if (!target) return;
            var expanded = target.classList.toggle('show');
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    });

    // Mobile/tablet taskbar drawer (below 768px - see app.css). The button
    // only exists in the DOM there (d-md-none), so this is a no-op above
    // that breakpoint. Closes on any click outside the drawer/button
    // itself, same convention as a dropdown - navigating via a taskbar
    // link closes it implicitly (full page reload starts closed again).
    var taskbarToggle = document.getElementById('taskbar-toggle');
    var appTaskbar = document.getElementById('app-taskbar');
    if (taskbarToggle && appTaskbar) {
        taskbarToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            document.body.classList.toggle('taskbar-open');
        });
        document.addEventListener('click', function (e) {
            if (!document.body.classList.contains('taskbar-open')) return;
            if (appTaskbar.contains(e.target) || taskbarToggle.contains(e.target)) return;
            document.body.classList.remove('taskbar-open');
        });
    }

    // Config\Security::$regenerate is true (CI4 default) - the CSRF token
    // issued with the page is single-use, and every response carries the
    // NEXT one. Every fetch()-based POST in this app must re-sync
    // window.CI4_CSRF from {csrf:{name,hash}} in the JSON response, or the
    // second AJAX call in a row 403s.
    function syncCsrf(data) {
        if (data && data.csrf && data.csrf.name && data.csrf.hash) {
            window.CI4_CSRF = data.csrf;
        }
        return data;
    }

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(Object.assign({}, body, window.CI4_CSRF ? { [window.CI4_CSRF.name]: window.CI4_CSRF.hash } : {})),
            // Found live 2026-08-18: resizing the taskbar/navbar divider,
            // then immediately clicking a nav link (very natural right
            // after a drag) aborted this save mid-flight - the browser
            // cancels in-flight fetches on navigation by default, so the
            // NEXT page loaded with the OLD persisted width. keepalive
            // lets the request finish even though the page that started it
            // is gone, same guarantee navigator.sendBeacon() gives, without
            // giving up the custom header/JSON body this already uses.
            keepalive: true,
        }).then(function (r) { return r.json().catch(function () { return null; }); }).then(syncCsrf);
    }

    // Theme toggle
    var toggle = document.getElementById('theme-toggle');
    if (toggle) {
        var html = document.documentElement;
        var syncIcons = function () {
            var dark = html.getAttribute('data-bs-theme') === 'dark';
            document.getElementById('theme-icon-light').style.display = dark ? 'none' : '';
            document.getElementById('theme-icon-dark').style.display = dark ? '' : 'none';
        };
        syncIcons();
        toggle.addEventListener('click', function () {
            var next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            syncIcons();
            post('/profile/preference', { theme: next });
        });
    }

    // "Auto" switch, bottom of the taskbar - see Layout/app.php's own
    // comment on this block. Purely a saved preference (same reactive
    // pattern as theme above); the actual node switch happens server-side,
    // once, right after a manual login - never from this checkbox directly.
    var autoSwitch = document.getElementById('auto-switch-checkbox');
    if (autoSwitch) {
        autoSwitch.addEventListener('change', function () {
            post('/profile/preference', { auto_switch_node: autoSwitch.checked ? 1 : 0 });
        });
    }

    // Draggable, hover-only dividers - navbar height + taskbar width, persisted per-user.
    // The handle itself is only 4px wide/tall (see .resizer in app.css) -
    // found live 2026-08-18 that listening for pointermove/pointerup on the
    // handle alone effectively never worked for a real drag: the pointer
    // leaves that thin strip on the very first move, and setPointerCapture()
    // wasn't reliably keeping events routed back to it. Move/up listeners
    // are now attached to the document only while a drag is in progress
    // (added on pointerdown, removed on pointerup/pointercancel), so the
    // drag keeps tracking regardless of the handle's own tiny hit area.
    function makeResizer(handle, cssVar, axis, min, max, prefKey) {
        if (!handle) return;
        var page = document.querySelector('.page');

        function onMove(e) {
            var value = axis === 'y' ? e.clientY : e.clientX;
            value = Math.max(min, Math.min(max, value));
            page.style.setProperty(cssVar, value + 'px');
        }

        function onStop() {
            handle.classList.remove('dragging');
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onStop);
            document.removeEventListener('pointercancel', onStop);
            var value = parseInt(getComputedStyle(page).getPropertyValue(cssVar), 10);
            var body = {}; body[prefKey] = value;
            post('/profile/preference', body);
        }

        handle.addEventListener('pointerdown', function (e) {
            e.preventDefault(); // avoid text selection while dragging over content
            handle.classList.add('dragging');
            document.addEventListener('pointermove', onMove);
            document.addEventListener('pointerup', onStop);
            document.addEventListener('pointercancel', onStop);
        });
    }
    makeResizer(document.getElementById('resizer-navbar'), '--navbar-height', 'y', 44, 120, 'navbar_height');
    makeResizer(document.getElementById('resizer-taskbar'), '--taskbar-width', 'x', 140, 420, 'taskbar_width');

    // Profile page: "Delete" badge next to the avatar thumbnail.
    var avatarDeleteBtn = document.getElementById('avatar-delete-btn');
    if (avatarDeleteBtn) {
        avatarDeleteBtn.addEventListener('click', function () {
            fetch(avatarDeleteBtn.dataset.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(window.CI4_CSRF ? { [window.CI4_CSRF.name]: window.CI4_CSRF.hash } : {}),
            }).then(function (r) { return r.json(); }).then(syncCsrf).then(function (data) {
                if (data && data.ok) {
                    document.getElementById('avatar-thumb-wrap').classList.add('d-none');
                }
            });
        });
    }
})();
