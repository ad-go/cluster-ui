<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>" data-bs-theme="<?= esc($theme ?? 'light') ?>" data-bs-theme-primary="<?= esc($themeColor ?? 'blue') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?= esc($title ?? setting('Site.title') ?? lang('App.dashboard')) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/tabler/tabler.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/tabler/tabler-themes.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/app.css') ?>">
    <!-- Deferred scripts run in DOCUMENT order, not load-completion order -
         these must appear before any page-specific script (e.g. users.js,
         rendered inside <body>'s content section further down) that calls
         window.bootstrap.* at top level, or window.bootstrap would still be
         undefined when that code runs. -->
    <script src="<?= base_url('assets/tabler/bootstrap.bundle.min.js') ?>" defer></script>
    <script src="<?= base_url('assets/tabler/tabler.min.js') ?>" defer></script>
</head>
<body>
<div class="page"
     style="--navbar-height: <?= (int) ($navbarHeight ?? 56) ?>px; --taskbar-width: <?= (int) ($taskbarWidth ?? 220) ?>px;">
    <!-- Logo/title lives in the SAME row as the navbar (not inside the
         taskbar below it) - found live 2026-08-18: the previous placement
         inside #app-taskbar could be made the same HEIGHT as the navbar,
         but sat in a different row entirely, one full navbar-height lower
         than the navbar's own icons. #app-topbar puts both as siblings in
         one flex row: the brand takes exactly --taskbar-width (so its
         right edge lines up with the taskbar/content divider below it),
         the navbar takes the rest. -->
    <div class="d-print-none" id="app-topbar">
        <a href="<?= url_to('dashboard') ?>" id="taskbar-brand">
            <?php $logo = setting('Site.logo'); ?>
            <!-- Always the mark, never a text title - a custom Site.logo
                 upload if there is one, otherwise this package's own default
                 (public/assets/logo-default.svg). -->
            <img src="<?= $logo ? esc(base_url($logo)) : base_url('assets/logo-default.svg') ?>" alt="<?= esc(setting('Site.title') ?? 'Cluster') ?>">
        </a>
        <header class="navbar navbar-expand-md d-print-none" id="app-navbar">
            <div class="container-xl justify-content-end">
                <button type="button" class="btn btn-icon btn-ghost-secondary d-md-none me-auto" id="taskbar-toggle" title="<?= lang('App.menu') ?>" aria-label="<?= lang('App.menu') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 6l16 0" /><path d="M4 12l16 0" /><path d="M4 18l16 0" /></svg>
                </button>
                <div class="navbar-nav flex-row order-md-last align-items-center">
                    <button type="button" class="btn btn-icon btn-ghost-secondary me-2" id="theme-toggle" title="<?= lang('App.theme') ?>">
                        <span id="theme-icon-light" aria-hidden="true">&#9728;</span>
                        <span id="theme-icon-dark" aria-hidden="true" style="display:none">&#9789;</span>
                    </button>
                    <div class="dropdown me-3">
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
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php /* $avatar comes from layoutData() (user_profiles.avatar) - Shield's
                                     User entity has no 'avatar' property, so $user->avatar is always null. */ ?>
                            <span class="avatar avatar-sm rounded-circle"
                                  style="<?= ! empty($avatar) ? "background-image: url(" . esc(base_url($avatar)) . ")" : '' ?>">
                                <?= ! empty($avatar) ? '' : esc(strtoupper(substr($user->username ?? $user->email ?? '?', 0, 1))) ?>
                            </span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?= esc($user->username ?? $user->email ?? '') ?></div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="<?= url_to('ProfileController::index') ?>" class="dropdown-item"><?= lang('App.profile') ?></a>
                            <div class="dropdown-divider"></div>
                            <form method="post" action="<?= url_to('logout') ?>">
                                <?= csrf_field() ?>
                                <button class="dropdown-item"><?= lang('App.logout') ?></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>
    </div>
    <div class="resizer resizer-navbar" id="resizer-navbar" title="<?= lang('App.dragToResize') ?>"></div>
    <div id="app-body">
        <!-- Plain <aside>, NOT Tabler's .navbar-vertical/.navbar classes: those
             ship their own position/height/flex rules for Tabler's exact
             sidebar-layout markup, which this page doesn't replicate -
             mixing them in pushes real content down. #app-body/#app-taskbar/
             #app-content below own 100% of this layout's geometry via plain
             flexbox in app.css; Tabler's .nav/.nav-link/.dropdown/.avatar/
             .btn etc. component classes are still used for styling and work
             fine without the .navbar-vertical wrapper. -->
        <aside class="d-print-none" id="app-taskbar">
            <?php if (auth()->user()) : ?>
                <?php $currentPath = current_url(true)->getPath(); ?>
                <ul class="nav flex-column pt-3">
                    <?php if (auth()->user()->inGroup('superadmin')) : ?>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2<?= strpos($currentPath, 'users') === 0 ? ' active' : '' ?>" href="<?= url_to('UsersController::index') ?>">
                                <span class="nav-link-icon bg-azure-lt text-azure">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
                                </span>
                                <?= lang('App.usersMenu') ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2<?= strpos($currentPath, 'settings') === 0 ? ' active' : '' ?>" href="<?= url_to('SettingsController::index') ?>">
                                <span class="nav-link-icon bg-lime-lt text-lime">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg>
                                </span>
                                <?= lang('App.settingsMenu') ?>
                            </a>
                        </li>
                    <?php endif ?>
                </ul>
                <?php if ($clusterPeers !== []) : ?>
                    <!-- mt-auto (pushes below the nav items) AND position:sticky
                         bottom:0 (see app.css's #taskbar-bottom) - #app-taskbar
                         itself is overflow-y:auto and stretches to match whatever
                         page content is tallest, and on a page like this Dashboard
                         (two 600px ECharts cards stacked) that's taller than the
                         viewport - mt-auto alone would push this below the fold,
                         off-screen until scrolled all the way down. Sticky keeps it
                         pinned to the visible bottom of the taskbar's own scroll
                         area regardless of page length, which is what "absolute
                         bottom of the taskbar" actually means for a user, not just
                         "last in the sidebar's DOM order". "Auto" persists like
                         every other preference in this layout (theme/navbar
                         height/taskbar width - see app.js's post('/profile/
                         preference', ...)); the actual switch only ever happens
                         once, right after a manual login (see AuthController::
                         loginAction()'s fastestNodeRedirect()) - never here, so
                         this checkbox is purely a saved preference, not a live
                         action trigger. -->
                    <div class="mt-auto p-2 bg-body" id="taskbar-bottom">
                        <label class="form-check form-switch d-flex align-items-center gap-2 mb-2 ps-0" title="<?= esc(lang('App.autoSwitchNodeHint')) ?>">
                            <input class="form-check-input ms-0" type="checkbox" id="auto-switch-checkbox" <?= $autoSwitchNode ? 'checked' : '' ?>>
                            <span class="form-check-label small text-secondary"><?= lang('App.autoSwitchNode') ?></span>
                        </label>
                        <div class="dropup">
                            <a href="#" class="nav-link d-flex align-items-center gap-2 px-0" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="nav-link-icon bg-cyan-lt text-cyan">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12h13l-4 -4" /><path d="M16 16l4 -4" /><path d="M21 12h-13l4 4" /><path d="M8 8l-4 4" /></svg>
                                </span>
                                <span class="small"><?= lang('App.switchServer') ?></span>
                            </a>
                            <div class="dropdown-menu">
                                <?php foreach ($clusterPeers as $peerName => $peerBaseUrl) : ?>
                                    <?php
                                        // Carries the page being switched FROM through the SSO
                                        // handoff (see ad-go/cluster's SsoController::start()/
                                        // safeReturnPath()) so landing on the other node lands
                                        // on the same page instead of always the dashboard.
                                        $switchUrl = site_url('cluster/sso/start') . '?node=' . urlencode($peerName) . '&return=' . urlencode(current_url(true)->getPath());
                                    ?>
                                    <a class="dropdown-item" title="<?= esc($peerBaseUrl) ?>" href="<?= esc($switchUrl, 'attr') ?>"><?= esc($peerName) ?></a>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                <?php endif ?>
            <?php endif ?>
        </aside>
        <div class="resizer resizer-taskbar" id="resizer-taskbar" title="<?= lang('App.dragToResize') ?>"></div>
        <div id="app-content"><div class="container-xl py-3"><?= $this->renderSection('content') ?></div></div>
    </div>
    <footer class="footer footer-transparent d-print-none">
        <div class="container-xl">
            <div class="text-secondary"><?= esc(setting('Site.footer') ?? '') ?></div>
        </div>
    </footer>
</div>
<script>window.CI4_CSRF = { name: '<?= csrf_token() ?>', hash: '<?= csrf_hash() ?>' };</script>
<script src="<?= base_url('assets/app.js') ?>" defer></script>
</body>
</html>
