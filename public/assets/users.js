(function () {
    'use strict';
    var table = document.getElementById('users-table');
    if (!table) return;
    var listUrl = table.dataset.listEndpoint;
    var createUrl = table.dataset.createEndpoint;
    var updateBase = table.dataset.updateEndpoint;
    var deleteBase = table.dataset.deleteEndpoint;
    var deleteLabel = table.dataset.deleteLabel || 'Delete';
    var banLabel = table.dataset.banLabel || 'Ban';
    var unbanLabel = table.dataset.unbanLabel || 'Unban';
    var statusActiveLabel = table.dataset.statusActive || 'Active';
    var statusBannedLabel = table.dataset.statusBanned || 'Banned';
    var addTitle = table.dataset.addTitle || 'Add user';
    var editTitle = table.dataset.editTitle || 'Edit user';
    var passwordLabel = table.dataset.passwordLabel || 'Password';
    var newPasswordLabel = table.dataset.newPasswordLabel || 'New password';
    var saveFailedLabel = table.dataset.saveFailed || 'Could not save.';
    var saveFailedNetworkLabel = table.dataset.saveFailedNetwork || 'Could not save - a network or server error occurred.';
    var superadminLabel = table.dataset.superadminLabel || 'Superadmin';
    var body = document.getElementById('users-table-body');
    var modalError = document.getElementById('user-modal-error');

    function showModalError(message) {
        modalError.textContent = message;
        modalError.classList.remove('d-none');
    }
    function hideModalError() {
        modalError.classList.add('d-none');
        modalError.textContent = '';
    }

    // Tabler-style outline icon (MIT-licensed @tabler/icons path), inlined
    // rather than pulled in as a font/sprite dependency - Tabler's icon
    // package is separate from tabler.min.css/js and isn't a dependency
    // of this package. Same size
    // as Add user's icon (outlined, not filled, to read as "destructive
    // but secondary" next to it).
    var ICON_DELETE = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>';
    // Same Tabler outline-icon convention as ICON_DELETE above - "ban" (a
    // circle with a diagonal slash) for an active user's row, "check" for
    // an already-banned one (the action there is to lift it).
    var ICON_BAN = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M5.7 5.7l12.6 12.6" /></svg>';
    var ICON_UNBAN = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10" /></svg>';
    var modalEl = document.getElementById('user-modal');
    var deleteModalEl = document.getElementById('delete-user-modal');
    var banModalEl = document.getElementById('ban-user-modal');
    // Instantiated lazily, on demand, NOT once at parse time: found live
    // 2026-08-17 that even with bootstrap.bundle.min.js correctly ordered
    // before this script, a `new bootstrap.Modal(...)` captured into a
    // top-level variable here could end up permanently null - by the time
    // a real user actually clicks anything, every deferred script has long
    // since run, so checking window.bootstrap at call time (not parse
    // time) sidesteps whatever caused that ordering to not hold in
    // practice, rather than depending on it.
    function userModal() {
        return window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    }
    function deleteModal() {
        return window.bootstrap ? bootstrap.Modal.getOrCreateInstance(deleteModalEl) : null;
    }
    function banModal() {
        return window.bootstrap ? bootstrap.Modal.getOrCreateInstance(banModalEl) : null;
    }
    var csrfField = function (fd) { if (window.CI4_CSRF) fd.append(window.CI4_CSRF.name, window.CI4_CSRF.hash); return fd; };

    // See app.js's syncCsrf() for why this exists: Config\Security::
    // $regenerate is true, so every JSON response carries the NEXT valid
    // token and window.CI4_CSRF must be updated from it, every time.
    function syncCsrf(data) {
        if (data && data.csrf && data.csrf.name && data.csrf.hash) {
            window.CI4_CSRF = data.csrf;
        }
        return data;
    }

    function render(users) {
        body.innerHTML = '';
        users.forEach(function (u) {
            var tr = document.createElement('tr');
            tr.style.cursor = 'pointer';
            var banBtnTitle = u.banned ? unbanLabel : banLabel;
            tr.innerHTML =
                '<td>' + u.username + '</td>' +
                '<td>' + u.email + '</td>' +
                '<td>' + ((u.groups || []).indexOf('superadmin') !== -1 ? '<span class="badge bg-azure-lt">' + superadminLabel + '</span>' : '') + '</td>' +
                '<td><span class="badge ' + (u.banned ? 'bg-red-lt' : 'bg-green-lt') + '">' + (u.banned ? statusBannedLabel : statusActiveLabel) + '</span></td>' +
                '<td class="text-nowrap">' +
                '<button class="btn btn-icon ' + (u.banned ? 'btn-outline-success' : 'btn-outline-warning') + ' ban-btn" title="' + banBtnTitle + '" aria-label="' + banBtnTitle + '">' + (u.banned ? ICON_UNBAN : ICON_BAN) + '</button>' +
                '<button class="btn btn-icon btn-outline-danger delete-btn" title="' + deleteLabel + '" aria-label="' + deleteLabel + '">' + ICON_DELETE + '</button>' +
                '</td>';
            // Row selection opens edit - the ban/delete buttons sit inside
            // the same row, so their own clicks must stop propagation or
            // every click would also re-open the edit modal underneath it.
            tr.addEventListener('click', function () { openEdit(u); });
            tr.querySelector('.delete-btn').addEventListener('click', function (e) {
                e.stopPropagation();
                remove(u.id);
            });
            tr.querySelector('.ban-btn').addEventListener('click', function (e) {
                e.stopPropagation();
                if (u.banned) { performUnban(u.id); } else { requestBan(u.id); }
            });
            body.appendChild(tr);
        });
    }

    function load() {
        fetch(listUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) { if (data.ok) render(data.users); });
    }

    function openEdit(u) {
        hideModalError();
        document.getElementById('user-modal-title').textContent = editTitle;
        document.getElementById('user-id').value = u.id;
        document.getElementById('user-username').value = u.username || '';
        document.getElementById('user-email').value = u.email || '';
        var pw = document.getElementById('user-password');
        pw.value = '';
        pw.placeholder = newPasswordLabel;
        document.getElementById('user-password-label').textContent = newPasswordLabel;
        document.getElementById('user-superadmin').checked = !!(u.groups && u.groups.indexOf('superadmin') !== -1);
        var m = userModal();
        if (m) m.show();
    }

    // Native confirm() replaced with this modal 2026-08-19: besides being
    // visually inconsistent with the rest of this UI (which never uses
    // browser-native dialogs anywhere else), a real confirm()/alert() also
    // blocks the page's JS thread entirely - found live 2026-08-18 that it
    // froze a CDP-controlled browser automation session (renderer stopped
    // responding to any further command) when testing this exact button.
    var pendingDeleteId = null;
    function remove(id) {
        pendingDeleteId = id;
        var m = deleteModal();
        if (m) m.show();
    }

    function performDelete(id) {
        // A DELETE request has no form body for the CSRF token to ride in
        // (unlike the FormData POSTs above) - CI4's CSRF filter also
        // accepts it via the X-CSRF-TOKEN header (Config\Security::
        // $headerName), which is the only place this token can go here.
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (window.CI4_CSRF) headers['X-CSRF-TOKEN'] = window.CI4_CSRF.hash;
        fetch(deleteBase + '/' + id, { method: 'DELETE', headers: headers })
            .then(function (r) { return r.json(); })
            .then(syncCsrf)
            .then(load)
            // A non-2xx (stale CSRF token, transient network issue) can
            // reject at .json() (an error body isn't always valid JSON) -
            // without this the modal has already closed and nothing else
            // ever runs, so the row silently stays undeleted with no
            // indication anything went wrong. Reloading the table at least
            // makes the outcome visible (row still present = it didn't
            // work), rather than a fully silent no-op.
            .catch(load);
    }

    // Ban gets the same confirm-modal treatment as delete above (it signs
    // the user out everywhere immediately, not a trivial action) - unban
    // is purely additive/safe (they still need to log back in normally),
    // so it fires straight from the row button with no confirmation step.
    var pendingBanId = null;
    function requestBan(id) {
        pendingBanId = id;
        var m = banModal();
        if (m) m.show();
    }

    function performBan(id) {
        var fd = csrfField(new FormData());
        fetch(table.dataset.deleteEndpoint + '/' + id + '/ban', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(syncCsrf)
            .then(load)
            .catch(load);
    }

    function performUnban(id) {
        var fd = csrfField(new FormData());
        fetch(table.dataset.deleteEndpoint + '/' + id + '/unban', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(syncCsrf)
            .then(load)
            .catch(load);
    }

    document.getElementById('ban-user-modal-confirm').addEventListener('click', function () {
        var m = banModal();
        if (m) m.hide();
        if (pendingBanId !== null) { performBan(pendingBanId); pendingBanId = null; }
    });
    ['ban-user-modal-close', 'ban-user-modal-cancel'].forEach(function (id) {
        document.getElementById(id).addEventListener('click', function () {
            pendingBanId = null;
            var m = banModal();
            if (m) m.hide();
        });
    });

    document.getElementById('delete-user-modal-confirm').addEventListener('click', function () {
        var m = deleteModal();
        if (m) m.hide();
        if (pendingDeleteId !== null) { performDelete(pendingDeleteId); pendingDeleteId = null; }
    });
    ['delete-user-modal-close', 'delete-user-modal-cancel'].forEach(function (id) {
        document.getElementById(id).addEventListener('click', function () {
            pendingDeleteId = null;
            var m = deleteModal();
            if (m) m.hide();
        });
    });

    document.getElementById('add-user-btn').addEventListener('click', function () {
        hideModalError();
        document.getElementById('user-modal-title').textContent = addTitle;
        document.getElementById('user-id').value = '';
        document.getElementById('user-username').value = '';
        document.getElementById('user-email').value = '';
        var pw = document.getElementById('user-password');
        pw.value = '';
        pw.placeholder = '';
        document.getElementById('user-password-label').textContent = passwordLabel;
        document.getElementById('user-superadmin').checked = false;
        var m = userModal();
        if (m) m.show();
    });

    // data-bs-dismiss="modal" goes through the same unreliable Bootstrap
    // data-api layer as data-bs-toggle (see app.js) - wired explicitly here
    // too rather than depending on it.
    ['user-modal-close', 'user-modal-cancel'].forEach(function (id) {
        document.getElementById(id).addEventListener('click', function () {
            var m = userModal();
            if (m) m.hide();
        });
    });

    document.getElementById('user-save-btn').addEventListener('click', function () {
        hideModalError();
        var id = document.getElementById('user-id').value;
        var fd = csrfField(new FormData());
        fd.append('username', document.getElementById('user-username').value);
        // '1'/'0', not omitted when unchecked - UsersController::update()
        // reads getPost('superadmin') !== null to decide whether to touch
        // the superadmin group AT ALL (see that method's own comment on
        // why a wipe-and-replace single group field was replaced with
        // this additive/subtractive toggle) - an unchecked box must still
        // arrive as a real "false" value, not disappear from the request
        // entirely the way an unchecked HTML checkbox input normally would.
        fd.append('superadmin', document.getElementById('user-superadmin').checked ? '1' : '0');
        fd.append('email', document.getElementById('user-email').value);
        // On edit, an empty password means "leave it unchanged" (see
        // UsersController::update(), same convention as Profile's own
        // password field) - on add it's required, enforced server-side.
        var password = document.getElementById('user-password').value;
        if (password !== '' || !id) {
            fd.append('password', password);
        }
        fetch(id ? (updateBase + '/' + id) : createUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            // Found live 2026-08-19: a validation failure (422, duplicate
            // email, weak password, ...) has always come back as valid
            // JSON with ok:false, but nothing in this modal ever displayed
            // it - `if (data.ok)` correctly did nothing, silently, so a
            // rejected save looked EXACTLY like nothing happened at all.
            // Worse, a non-JSON error response (a stale-CSRF 403's HTML
            // page, a 500) had no .catch() here at all (unlike
            // performDelete()/performBan()/performUnban(), which already
            // learned this lesson) - an unhandled promise rejection, same
            // silent no-op from the admin's point of view. Both are now
            // shown in #user-modal-error instead of failing invisibly.
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (result) {
                syncCsrf(result.data);
                if (result.data && result.data.ok) {
                    var m = userModal();
                    if (m) m.hide();
                    load();
                    return;
                }
                var errors = result.data && result.data.errors ? Object.values(result.data.errors) : [];
                showModalError(errors.length ? errors.join(' ') : saveFailedLabel);
            })
            .catch(function () { showModalError(saveFailedNetworkLabel); });
    });

    load();
})();
