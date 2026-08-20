<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= lang('App.logout') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/tabler/tabler.min.css') ?>">
</head>
<body class="d-flex flex-column">
<div class="page page-center"><div class="container container-tight py-4">
    <div class="card card-md"><div class="card-body text-center">
        <h2 class="h2 mb-3"><?= lang('App.logout') ?></h2>
        <p class="text-secondary"><?= lang('App.confirmLogout') ?></p>
        <form method="post" action="<?= site_url('logout') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-primary" type="submit"><?= lang('App.logout') ?></button>
            <a class="btn btn-link" href="<?= site_url('/') ?>"><?= lang('App.cancel') ?></a>
        </form>
    </div></div>
</div></div>
</body></html>
