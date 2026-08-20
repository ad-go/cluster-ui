<?= $this->extend('Layout/app') ?>
<?= $this->section('content') ?>
<div class="page-header d-print-none"><div class="container-xl"><h2 class="page-title"><?= lang('App.profile') ?></h2></div></div>
<div class="row">
    <div class="col-12 col-md-6">
        <div class="card">
            <div class="card-body" id="profile-form"
                 data-endpoint="<?= url_to('ProfileController::updateField') ?>"
                 data-avatar-endpoint="<?= url_to('ProfileController::uploadAvatar') ?>">
                <!-- Reactive form: no save button anywhere here on purpose - every
                     field autosaves via public/assets/settings.js on change/blur,
                     same pattern as the Settings page. -->
                <div class="mb-3"><label class="form-label"><?= lang('App.name') ?></label>
                    <input type="text" class="form-control" data-field="username" value="<?= esc($user->username ?? '') ?>"></div>
                <div class="mb-3"><label class="form-label"><?= lang('App.email') ?></label>
                    <input type="email" class="form-control" data-field="email" value="<?= esc($user->email ?? '') ?>"></div>
                <div class="mb-3"><label class="form-label"><?= lang('App.phone') ?></label>
                    <input type="text" class="form-control" data-field="phone" value="<?= esc($profile['phone'] ?? '') ?>"></div>
                <div class="mb-3"><label class="form-label"><?= lang('App.newPassword') ?></label>
                    <!-- Always rendered blank, even right after a save - see
                         ProfileController::updateField()'s own comment on why. -->
                    <input type="password" class="form-control" data-field="password" autocomplete="new-password"></div>
                <div class="mb-3">
                    <label class="form-label"><?= lang('App.avatar') ?></label>
                    <div class="d-flex align-items-center gap-2 mb-2 <?= empty($profile['avatar']) ? 'd-none' : '' ?>" id="avatar-thumb-wrap">
                        <img src="<?= ! empty($profile['avatar']) ? esc(base_url($profile['avatar'])) : '' ?>" alt="" class="avatar avatar-md rounded" id="avatar-thumb">
                        <span class="badge bg-red-lt" id="avatar-delete-btn" style="cursor:pointer" data-endpoint="<?= url_to('ProfileController::deleteAvatar') ?>"><?= lang('App.delete') ?></span>
                    </div>
                    <input type="file" class="form-control" id="profile-avatar" accept="image/*">
                </div>
                <span class="badge bg-green-lt d-none" id="profile-saved"><?= lang('App.saved') ?></span>
            </div>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/profile.js') ?>" defer></script>
<?= $this->endSection() ?>
