<?= $this->extend('Layout/app') ?>
<?= $this->section('content') ?>
<div class="page-header d-print-none"><div class="container-xl"><h2 class="page-title"><?= lang('App.settingsMenu') ?></h2></div></div>
<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-body compact-form" id="settings-form"
                 data-endpoint="<?= url_to('SettingsController::update') ?>"
                 data-logo-endpoint="<?= url_to('SettingsController::uploadLogo') ?>"
                 data-logo-delete-endpoint="<?= url_to('SettingsController::deleteLogo') ?>">
                <!-- Reactive form: no save button anywhere here on purpose - every
                     field autosaves via public/assets/settings.js on change/blur.
                     2-column grid (was one field per full-width row) - see
                     .compact-form in app.css for the smaller font/padding. -->
                <div class="row">
                    <div class="col-6 mb-2"><label class="form-label"><?= lang('App.title') ?></label>
                        <input type="text" class="form-control form-control-sm" data-field="title" value="<?= esc($siteTitle ?? '') ?>"></div>
                    <div class="col-6 mb-2"><label class="form-label"><?= lang('App.footer') ?></label>
                        <input type="text" class="form-control form-control-sm" data-field="footer" value="<?= esc($siteFooter ?? '') ?>"></div>
                    <div class="col-6 mb-2"><label class="form-label"><?= lang('App.theme') ?></label>
                        <select class="form-select form-select-sm" data-field="theme">
                            <option value="light" <?= ($siteTheme ?? 'light') === 'light' ? 'selected' : '' ?>><?= lang('App.light') ?></option>
                            <option value="dark" <?= ($siteTheme ?? 'light') === 'dark' ? 'selected' : '' ?>><?= lang('App.dark') ?></option>
                        </select></div>
                    <div class="col-6 mb-2"><label class="form-label"><?= lang('App.themeColor') ?></label>
                        <select class="form-select form-select-sm" data-field="themeColor">
                            <?php foreach (\App\Controllers\SettingsController::THEME_COLORS as $color) : ?>
                                <option value="<?= esc($color) ?>" <?= ($siteThemeColor ?? 'blue') === $color ? 'selected' : '' ?>><?= esc(ucfirst($color)) ?></option>
                            <?php endforeach ?>
                        </select></div>
                    <div class="col-12 mb-2">
                        <label class="form-label"><?= lang('App.logo') ?></label>
                        <div class="d-flex align-items-center gap-2 mb-2 <?= empty($siteLogo) ? 'd-none' : '' ?>" id="settings-logo-thumb-wrap">
                            <img src="<?= ! empty($siteLogo) ? esc(base_url($siteLogo)) : '' ?>" alt="" class="logo-thumb rounded" id="settings-logo-thumb">
                            <span class="badge bg-red-lt" id="settings-logo-delete-btn" style="cursor:pointer"><?= lang('App.delete') ?></span>
                        </div>
                        <input type="file" class="form-control form-control-sm" id="settings-logo" accept="image/*">
                    </div>
                </div>
                <span class="badge bg-green-lt d-none" id="settings-saved"><?= lang('App.saved') ?></span>
            </div>
        </div>
    </div>
</div>
<?php if ($nodes !== []) : ?>
<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?= lang('App.nodesTitle') ?></h3>
                <!-- Covers BOTH the Nodes and Databases tables below (one
                     combined file, see SettingsController::exportSettings())
                     - lives on the Nodes card since it's the first of the
                     two, not because it's Nodes-specific. -->
                <div class="card-actions ms-auto" id="settings-export-import"
                     data-export-endpoint="<?= url_to('SettingsController::exportSettings') ?>"
                     data-import-endpoint="<?= url_to('SettingsController::importSettings') ?>"
                     data-import-strings="<?= esc(json_encode(['success' => lang('App.importSuccess'), 'failed' => lang('App.importFailed')]), 'attr') ?>">
                    <a class="btn btn-sm btn-outline-primary me-2" href="<?= url_to('SettingsController::exportSettings') ?>"><?= lang('App.exportButton') ?></a>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="settings-import-btn"><?= lang('App.importButton') ?></button>
                    <input type="file" accept="application/json" class="d-none" id="settings-import-file">
                    <span class="badge bg-red-lt d-none ms-2" id="settings-import-error"></span>
                </div>
            </div>
            <div class="table-responsive compact-form">
                <table class="table card-table table-vcenter" id="settings-nodes"
                       data-endpoint="<?= url_to('SettingsController::updateNode') ?>"
                       data-test-endpoint="<?= url_to('SettingsController::testNode') ?>"
                       data-test-strings="<?= esc(json_encode(['ok' => lang('App.connTestOk'), 'failed' => lang('App.connTestFailed'), 'waiting' => lang('App.connTestWaiting'), 'timeout' => lang('App.connTestTimeout')]), 'attr') ?>">
                    <thead>
                        <tr>
                            <th><?= lang('App.nodeName') ?></th>
                            <th><?= lang('App.nodeType') ?></th>
                            <th><?= lang('App.nodeUrl') ?></th>
                            <th><?= lang('App.nodeProtocol') ?></th>
                            <th><?= lang('App.nodeHost') ?></th>
                            <th><?= lang('App.nodePort') ?></th>
                            <th><?= lang('App.nodeUser') ?></th>
                            <th><?= lang('App.nodePass') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($nodes as $name => $node) : ?>
                        <?php
                            // Two independent credential sets per node (FTP/FTPS and
                            // SSH/SCP - see SettingsController::NODE_PROPS's own
                            // docblock) - both loaded into data-ftp-*/data-ssh-*
                            // attributes below so settings.js can swap the visible
                            // Host/Port/User/Pass fields between them the instant the
                            // Protocol dropdown changes, no round trip needed. Which
                            // one starts visible just follows the currently stored
                            // protocol.
                            $family     = in_array($node['protocol'], ['SSH', 'SCP'], true) ? 'ssh' : 'ftp';
                            $activeProp = ['host' => $family . 'Host', 'port' => $family . 'Port', 'user' => $family . 'User', 'pass' => $family . 'Pass'];
                        ?>
                        <tr data-ftp-host="<?= esc($node['ftpHost']) ?>" data-ftp-port="<?= esc($node['ftpPort']) ?>" data-ftp-user="<?= esc($node['ftpUser']) ?>" data-ftp-pass="<?= esc($node['ftpPass']) ?>"
                            data-ssh-host="<?= esc($node['sshHost']) ?>" data-ssh-port="<?= esc($node['sshPort']) ?>" data-ssh-user="<?= esc($node['sshUser']) ?>" data-ssh-pass="<?= esc($node['sshPass']) ?>">
                            <td>
                                <span class="badge bg-blue-lt" style="cursor:pointer" data-test-conn="<?= esc($name) ?>"><?= esc($name) ?></span>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" data-node="<?= esc($name) ?>" data-prop="type">
                                    <option value="nat" <?= $node['type'] === 'nat' ? 'selected' : '' ?>><?= lang('App.nodeTypeNat') ?></option>
                                    <option value="public" <?= $node['type'] === 'public' ? 'selected' : '' ?>><?= lang('App.nodeTypeDirect') ?></option>
                                </select>
                            </td>
                            <td><input type="text" class="form-control form-control-sm" data-node="<?= esc($name) ?>" data-prop="url" value="<?= esc($node['url']) ?>"></td>
                            <td>
                                <!-- Every protocol actually used across this cluster's real
                                     nodes (see CI4cluster.asc) - FTP (upz), explicit FTPS/AUTH
                                     TLS (beta, h1q), plus SSH/SCP (h1q, bak, res, upz - see
                                     data-ssh-* above) - not generic placeholder labels.
                                     data-protocol-select marks this for settings.js's
                                     family-swap handler, separate from the plain autosave
                                     every other [data-node][data-prop] select/input already gets. -->
                                <select class="form-select form-select-sm" data-node="<?= esc($name) ?>" data-prop="protocol" data-protocol-select>
                                    <?php foreach (\App\Controllers\SettingsController::NODE_PROTOCOLS as $protocol) : ?>
                                        <option value="<?= esc($protocol) ?>" <?= $node['protocol'] === $protocol ? 'selected' : '' ?>><?= esc($protocol) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </td>
                            <td><input type="text" class="form-control form-control-sm" data-node="<?= esc($name) ?>" data-prop="<?= $activeProp['host'] ?>" data-field-role="host" value="<?= esc($node[$activeProp['host']]) ?>"></td>
                            <td><input type="number" class="form-control form-control-sm" style="width:5.5em" data-node="<?= esc($name) ?>" data-prop="<?= $activeProp['port'] ?>" data-field-role="port" value="<?= esc($node[$activeProp['port']]) ?>"></td>
                            <td><input type="text" class="form-control form-control-sm" data-node="<?= esc($name) ?>" data-prop="<?= $activeProp['user'] ?>" data-field-role="user" value="<?= esc($node[$activeProp['user']]) ?>"></td>
                            <td><input type="password" class="form-control form-control-sm" data-node="<?= esc($name) ?>" data-prop="<?= $activeProp['pass'] ?>" data-field-role="pass" value="<?= esc($node[$activeProp['pass']]) ?>"></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif ?>
<?php if ($databases !== []) : ?>
<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><?= lang('App.databasesTitle') ?></h3></div>
            <div class="table-responsive compact-form">
                <table class="table card-table table-vcenter" id="settings-databases"
                       data-endpoint="<?= url_to('SettingsController::updateDatabase') ?>"
                       data-test-endpoint="<?= url_to('SettingsController::testDatabase') ?>"
                       data-test-strings="<?= esc(json_encode(['ok' => lang('App.connTestOk'), 'failed' => lang('App.connTestFailed'), 'waiting' => lang('App.connTestWaiting'), 'timeout' => lang('App.connTestTimeout')]), 'attr') ?>">
                    <thead>
                        <tr>
                            <th><?= lang('App.dbNodeName') ?></th>
                            <th><?= lang('App.dbType') ?></th>
                            <th><?= lang('App.dbDatabase') ?></th>
                            <th><?= lang('App.dbHost') ?></th>
                            <th><?= lang('App.dbPort') ?></th>
                            <th><?= lang('App.dbUser') ?></th>
                            <th><?= lang('App.dbPass') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($databases as $name => $database) : ?>
                        <?php
                            // Five independent credential sets per node (one per
                            // CI4-supported driver - see SettingsController::
                            // DATABASE_TYPES/DATABASE_PROPS's own docblock), all
                            // preloaded into data-{type}-* attributes below so
                            // settings.js can swap the visible Host/Port/User/Pass
                            // fields instantly on Type change, no round trip - same
                            // pattern as the Nodes table's Protocol swap above, just
                            // 5-way instead of 2-way.
                            $activeType = $database['type'];
                            $activeProp = ['host' => $activeType . 'Host', 'port' => $activeType . 'Port', 'user' => $activeType . 'User', 'pass' => $activeType . 'Pass', 'database' => $activeType . 'Database'];
                        ?>
                        <tr<?php foreach (\App\Controllers\SettingsController::DATABASE_TYPES as $t) : ?> data-<?= $t ?>-host="<?= esc($database[$t . 'Host']) ?>" data-<?= $t ?>-port="<?= esc($database[$t . 'Port']) ?>" data-<?= $t ?>-user="<?= esc($database[$t . 'User']) ?>" data-<?= $t ?>-pass="<?= esc($database[$t . 'Pass']) ?>" data-<?= $t ?>-database="<?= esc($database[$t . 'Database']) ?>"<?php endforeach ?>>
                            <td><span class="badge bg-blue-lt" style="cursor:pointer" data-test-conn="<?= esc($name) ?>"><?= esc($name) ?></span></td>
                            <td>
                                <select class="form-select form-select-sm" data-node="<?= esc($name) ?>" data-prop="type" data-type-select>
                                    <?php foreach (\App\Controllers\SettingsController::DATABASE_TYPES as $t) : ?>
                                        <option value="<?= esc($t) ?>" <?= $activeType === $t ? 'selected' : '' ?>><?= esc($t) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </td>
                            <td><input type="text" class="form-control form-control-sm" data-node="<?= esc($name) ?>" data-prop="<?= $activeProp['database'] ?>" data-field-role="database" value="<?= esc($database[$activeProp['database']]) ?>"></td>
                            <td><input type="text" class="form-control form-control-sm" data-node="<?= esc($name) ?>" data-prop="<?= $activeProp['host'] ?>" data-field-role="host" value="<?= esc($database[$activeProp['host']]) ?>"></td>
                            <td><input type="number" class="form-control form-control-sm" style="width:5.5em" data-node="<?= esc($name) ?>" data-prop="<?= $activeProp['port'] ?>" data-field-role="port" value="<?= esc($database[$activeProp['port']]) ?>"></td>
                            <td><input type="text" class="form-control form-control-sm" data-node="<?= esc($name) ?>" data-prop="<?= $activeProp['user'] ?>" data-field-role="user" value="<?= esc($database[$activeProp['user']]) ?>"></td>
                            <td><input type="password" class="form-control form-control-sm" data-node="<?= esc($name) ?>" data-prop="<?= $activeProp['pass'] ?>" data-field-role="pass" value="<?= esc($database[$activeProp['pass']]) ?>"></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif ?>
<?php if ($nodes !== [] || $databases !== []) : ?>
<!-- Opened by clicking a node-name badge in the Nodes OR Databases table
     above (see settings.js) - ONE shared modal for both, populated per
     click rather than one per table, same "test the row's currently-saved
     credentials live" idea as this project's own one-off verification
     scripts used by hand throughout this project's setup, just permanent
     and in-app now. -->
<div class="modal modal-blur fade" id="conn-test-modal" tabindex="-1"
     data-test-result-endpoint="<?= url_to('SettingsController::testResult') ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="conn-test-modal-title"><?= lang('App.connTestTitle') ?></h5>
                <button type="button" class="btn-close" id="conn-test-modal-close"></button></div>
            <div class="modal-body">
                <div id="conn-test-modal-loading" class="text-center py-3">
                    <div class="spinner-border text-blue" role="status"></div>
                    <div id="conn-test-modal-waiting" class="text-muted small mt-2 d-none"></div>
                </div>
                <div id="conn-test-modal-result" class="d-none">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span id="conn-test-modal-badge" class="badge"></span>
                        <span id="conn-test-modal-summary"></span>
                    </div>
                    <pre id="conn-test-modal-detail" class="mb-0" style="white-space:pre-wrap;word-break:break-word;"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn" id="conn-test-modal-ok"><?= lang('App.close') ?></button>
            </div>
        </div>
    </div>
</div>
<?php endif ?>
<script src="<?= base_url('assets/settings.js') ?>" defer></script>
<?= $this->endSection() ?>
