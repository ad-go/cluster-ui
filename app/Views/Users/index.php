<?= $this->extend('Layout/app') ?>
<?= $this->section('content') ?>
<div class="page-header d-print-none"><div class="container-xl d-flex align-items-center gap-2">
    <h2 class="page-title mb-0"><?= lang('App.usersMenu') ?></h2>
    <button class="btn btn-icon btn-outline-primary" id="add-user-btn" title="<?= lang('App.addUser') ?>" aria-label="<?= lang('App.addUser') ?>">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4" /><path d="M16 19h6" /><path d="M19 16v6" /></svg>
    </button>
</div></div>
<div class="card">
    <table class="table card-table table-vcenter" id="users-table"
           data-list-endpoint="<?= url_to('UsersController::list') ?>"
           data-create-endpoint="<?= site_url('users') ?>"
           data-update-endpoint="<?= site_url('users') ?>"
           data-delete-endpoint="<?= site_url('users') ?>"
           data-delete-label="<?= esc(lang('App.delete')) ?>"
           data-ban-label="<?= esc(lang('App.banUser')) ?>"
           data-unban-label="<?= esc(lang('App.unbanUser')) ?>"
           data-status-active="<?= esc(lang('App.userActive')) ?>"
           data-status-banned="<?= esc(lang('App.userBanned')) ?>"
           data-add-title="<?= esc(lang('App.addUser')) ?>"
           data-edit-title="<?= esc(lang('App.editUser')) ?>"
           data-password-label="<?= esc(lang('App.password')) ?>"
           data-new-password-label="<?= esc(lang('App.newPassword')) ?>"
           data-save-failed="<?= esc(lang('App.saveFailed')) ?>"
           data-save-failed-network="<?= esc(lang('App.saveFailedNetwork')) ?>"
           data-superadmin-label="<?= esc(lang('App.superadminToggle')) ?>">
        <thead><tr>
            <th><?= lang('App.name') ?></th><th><?= lang('App.email') ?></th><th><?= lang('App.group') ?></th><th><?= lang('App.status') ?></th><th class="w-1"><?= lang('App.actions') ?></th>
        </tr></thead>
        <tbody id="users-table-body"></tbody>
    </table>
</div>

<div class="modal modal-blur fade" id="user-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="user-modal-title"><?= lang('App.addUser') ?></h5>
                <button type="button" class="btn-close" id="user-modal-close"></button></div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="user-modal-error" role="alert"></div>
                <input type="hidden" id="user-id" value="">
                <div class="mb-3"><label class="form-label"><?= lang('App.username') ?></label>
                    <input type="text" class="form-control" id="user-username"></div>
                <div class="mb-3"><label class="form-label"><?= lang('App.email') ?></label>
                    <input type="email" class="form-control" id="user-email"></div>
                <div class="mb-3"><label class="form-label" id="user-password-label"><?= lang('App.password') ?></label>
                    <input type="password" class="form-control" id="user-password" autocomplete="new-password"></div>
                <div class="mb-3">
                    <label class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="user-superadmin">
                        <span class="form-check-label"><?= lang('App.superadminToggle') ?></span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn" id="user-modal-cancel"><?= lang('App.cancel') ?></button>
                <button class="btn btn-primary" id="user-save-btn"><?= lang('App.save') ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="delete-user-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><?= lang('App.confirmDeleteUserTitle') ?></h5>
                <button type="button" class="btn-close" id="delete-user-modal-close"></button></div>
            <div class="modal-body"><?= lang('App.confirmDeleteUserBody') ?></div>
            <div class="modal-footer">
                <button class="btn" id="delete-user-modal-cancel"><?= lang('App.cancel') ?></button>
                <button class="btn btn-danger" id="delete-user-modal-confirm"><?= lang('App.delete') ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="ban-user-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><?= lang('App.confirmBanUserTitle') ?></h5>
                <button type="button" class="btn-close" id="ban-user-modal-close"></button></div>
            <div class="modal-body"><?= lang('App.confirmBanUserBody') ?></div>
            <div class="modal-footer">
                <button class="btn" id="ban-user-modal-cancel"><?= lang('App.cancel') ?></button>
                <button class="btn btn-danger" id="ban-user-modal-confirm"><?= lang('App.banUser') ?></button>
            </div>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/users.js') ?>" defer></script>
<?= $this->endSection() ?>
