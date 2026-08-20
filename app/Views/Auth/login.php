<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>" data-bs-theme-primary="<?= esc(setting('Site.themeColor') ?? 'blue') ?>">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= lang('Auth.login') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/tabler/tabler.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/tabler/tabler-themes.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">
    <script src="<?= base_url('assets/tabler/bootstrap.bundle.min.js') ?>" defer></script>
    <script src="<?= base_url('assets/tabler/tabler.min.js') ?>" defer></script>
</head>
<body class="d-flex flex-column">
<div class="page page-center"><div class="container container-tight py-4">
    <div class="card card-md"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h2 mb-0"><?= lang('Auth.login') ?></h2>
            <div class="dropdown">
                <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= base_url(App\Config\Locales::flagImage(service('request')->getLocale())) ?>" width="22" alt="">
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <?php foreach (config('App')->supportedLocales as $locale) : ?>
                        <form method="post" action="<?= url_to('locale.update') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="locale" value="<?= esc($locale) ?>">
                            <button type="submit" class="dropdown-item d-flex align-items-center gap-2 <?= service('request')->getLocale() === $locale ? 'active' : '' ?>">
                                <img src="<?= base_url(App\Config\Locales::flagImage($locale)) ?>" width="18" alt="">
                                <span><?= esc(App\Config\Locales::label($locale)) ?></span>
                            </button>
                        </form>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
        <?php if (session('error')) : ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif ?>
        <?php
            $mode = config('Auth')->loginIdentifier;
            // Which radio was selected on the failed attempt, if any - old()
            // reads it straight from the re-populated POST (withInput()),
            // no separate flash needed. Defaults to 'email' on a fresh GET.
            $identifierMode = in_array(old('identifier_mode'), ['email', 'username'], true) ? old('identifier_mode') : 'email';
        ?>
        <form method="post" action="<?= url_to('login') ?>">
            <?= csrf_field() ?>
            <?php if ($mode === 'both') : ?>
                <input type="hidden" name="identifier_mode" id="identifier-mode-input" value="<?= esc($identifierMode) ?>">
            <?php endif ?>
            <div class="mb-3">
                <label class="form-label" for="identifier" id="identifier-label"><?php
                    if ($mode === 'email') { echo lang('Auth.email'); }
                    elseif ($mode === 'username') { echo lang('Auth.username'); }
                    else { echo $identifierMode === 'username' ? lang('Auth.username') : lang('Auth.email'); }
                ?></label>
                <input id="identifier" name="identifier" class="form-control"
                       type="<?= $mode !== 'both' || $identifierMode === 'email' ? 'email' : 'text' ?>"
                       autocomplete="<?= $mode === 'username' || ($mode === 'both' && $identifierMode === 'username') ? 'username' : 'email' ?>"
                       value="<?= esc(old('identifier')) ?>" required autofocus
                       data-label-email="<?= esc(lang('Auth.email')) ?>" data-label-username="<?= esc(lang('Auth.username')) ?>">
            </div>
            <div class="mb-3"><label class="form-label" for="password"><?= lang('Auth.password') ?></label>
                <input id="password" name="password" type="password" class="form-control" autocomplete="current-password" required></div>
            <?php if (setting('Auth.sessionConfig')['allowRemembering']) : ?><label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember"><span class="form-check-label"><?= lang('Auth.rememberMe') ?></span></label><?php endif ?>
            <button class="btn btn-primary w-100" type="submit"><?= lang('Auth.login') ?></button>
            <?php if ($mode === 'both') : ?>
                <div class="mt-3 d-flex align-items-center justify-content-center gap-2">
                    <span id="mode-label-email" class="<?= $identifierMode === 'email' ? 'fw-bold' : 'text-secondary' ?>"><?= lang('Auth.email') ?></span>
                    <label class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="mode-switch" role="switch" <?= $identifierMode === 'username' ? 'checked' : '' ?>>
                    </label>
                    <span id="mode-label-username" class="<?= $identifierMode === 'username' ? 'fw-bold' : 'text-secondary' ?>"><?= lang('Auth.username') ?></span>
                </div>
            <?php endif ?>
        </form>
        <?php if ($mode === 'both') : ?>
        <script>
        (function () {
            // Purely presentational - the identifier field is still named
            // "identifier" and validated server-side by AuthController on
            // the submitted identifier_mode value (identifier-mode-input's
            // value, kept in sync here), not by this script.
            var input = document.getElementById('identifier');
            var label = document.getElementById('identifier-label');
            var hidden = document.getElementById('identifier-mode-input');
            var toggle = document.getElementById('mode-switch');
            var emailLabel = document.getElementById('mode-label-email');
            var usernameLabel = document.getElementById('mode-label-username');
            toggle.addEventListener('change', function () {
                var isEmail = !toggle.checked;
                hidden.value = isEmail ? 'email' : 'username';
                label.textContent = isEmail ? input.dataset.labelEmail : input.dataset.labelUsername;
                input.type = isEmail ? 'email' : 'text';
                input.autocomplete = isEmail ? 'email' : 'username';
                emailLabel.classList.toggle('fw-bold', isEmail);
                emailLabel.classList.toggle('text-secondary', !isEmail);
                usernameLabel.classList.toggle('fw-bold', !isEmail);
                usernameLabel.classList.toggle('text-secondary', isEmail);
            });
        })();
        </script>
        <?php endif ?>
    </div></div>
</div></div>
<script src="<?= base_url('assets/app.js') ?>" defer></script>
</body></html>
