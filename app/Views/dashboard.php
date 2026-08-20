<?= $this->extend('Layout/app') ?>
<?= $this->section('content') ?>
<?php if ($networkInfo !== null) : ?>
<div class="row row-cards mb-4" id="net-summary">
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card card-sm h-100"><div class="card-body">
            <span class="dash-card-icon bg-blue-lt text-blue">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 6a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M3 14a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M7 8l0 .01" /><path d="M7 16l0 .01" /></svg>
            </span>
            <div class="dash-card-text">
                <div class="font-weight-medium text-truncate" id="net-thisnode"><?= esc($networkInfo['thisNode'] !== '' ? $networkInfo['thisNode'] : '-') ?></div>
                <div class="text-secondary"><?= lang('App.netThisNode') ?></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card card-sm h-100"><div class="card-body">
            <span class="dash-card-icon bg-azure-lt text-azure">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7v5l3 3" /><path d="M6.3 6.3l1.4 1.4" /></svg>
            </span>
            <div class="dash-card-text">
                <div class="font-weight-medium text-truncate" id="net-speed"><?= esc($networkInfo['avgSpeedHuman']) ?></div>
                <div class="text-secondary"><?= lang('App.netAvgSpeed') ?></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card card-sm h-100"><div class="card-body">
            <span class="dash-card-icon bg-green-lt text-green">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
            </span>
            <div class="dash-card-text">
                <div class="font-weight-medium text-truncate" id="net-transfer"><?= esc($networkInfo['recentBytesHuman']) ?></div>
                <div class="text-secondary"><?= lang('App.netTransfer') ?></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card card-sm h-100"><div class="card-body">
            <span class="dash-card-icon bg-lime-lt text-lime">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 15l2 2l4 -4" /></svg>
            </span>
            <div class="dash-card-text">
                <div class="font-weight-medium text-truncate"><span id="net-synced" class="text-success"><?= (int) $networkInfo['syncedFiles'] ?></span> / <span id="net-pending" class="text-warning"><?= (int) $networkInfo['pendingFiles'] ?></span></div>
                <div class="text-secondary"><?= lang('App.netFiles') ?></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card card-sm h-100"><div class="card-body">
            <span class="dash-card-icon bg-purple-lt text-purple">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0" /><path d="M4 6v6a8 3 0 0 0 16 0v-6" /><path d="M4 12v6a8 3 0 0 0 16 0v-6" /></svg>
            </span>
            <div class="dash-card-text">
                <div class="font-weight-medium text-truncate"><span id="net-db-ok" class="text-success"><?= (int) $networkInfo['dbRecentOk'] ?></span> / <span id="net-db-errors" class="text-danger"><?= (int) $networkInfo['dbRecentErrors'] ?></span></div>
                <div class="text-secondary"><?= lang('App.netDbSync') ?></div>
            </div>
        </div></div>
    </div>
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card card-sm h-100"><div class="card-body">
            <span class="dash-card-icon bg-orange-lt text-orange">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 11m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h1" /><path d="M15 6h6" /><path d="M18 3v6" /><path d="M15 18h6" /><path d="M15 15v6" /></svg>
            </span>
            <div class="dash-card-text">
                <div class="font-weight-medium text-truncate" id="net-session">
                    <?php if ($networkInfo['sessionWouldSurvive']) : ?>
                        <span class="text-success" title="<?= esc(lang('App.sessionSyncWouldSurvive')) ?>"><?= lang('App.netSessionOk') ?></span>
                    <?php else : ?>
                        <span class="text-danger" title="<?= esc(lang('App.sessionSyncWouldBeKilled')) ?>"><?= lang('App.netSessionKilled') ?></span>
                    <?php endif ?>
                </div>
                <div class="text-secondary"><?= lang('App.netSession') ?></div>
            </div>
        </div></div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0"><?= lang('App.netGraphTitle') ?></h3>
                <div class="card-actions ms-auto">
                    <?php if ($networkInfo['configured']) : ?>
                        <span id="net-badge" class="badge bg-green-lt"><?= lang('App.fileSyncActive') ?></span>
                    <?php else : ?>
                        <span id="net-badge" class="badge bg-secondary-lt"><?= lang('App.fileSyncNotConfigured') ?></span>
                    <?php endif ?>
                </div>
            </div>
            <div class="card-body">
                <div id="net-graph"
                    style="height:450px;"
                    data-status-url="<?= site_url('dashboard/network-status') ?>"
                    data-network="<?= esc(json_encode($networkInfo), 'attr') ?>"
                    data-strings="<?= esc(json_encode([
                        'active'       => lang('App.fileSyncActive'),
                        'notConfigured' => lang('App.fileSyncNotConfigured'),
                        'thisNode'     => lang('App.netThisNode'),
                        'never'        => lang('App.fileSyncNeverSynced'),
                        'justNow'      => lang('App.fileSyncJustNow'),
                        'minutesAgo'   => lang('App.fileSyncMinutesAgo', ['{0}']),
                        'hoursAgo'     => lang('App.fileSyncHoursAgo', ['{0}']),
                        'daysAgo'      => lang('App.fileSyncDaysAgo', ['{0}']),
                        'lastSync'     => lang('App.fileSyncLastSync'),
                        'lastPull'     => lang('App.fileSyncLastPull'),
                        'lastPushIn'   => lang('App.dbSyncPushIn'),
                        'transfer'     => lang('App.netTransfer'),
                        'speed'        => lang('App.netAvgSpeed'),
                        'errors'       => lang('App.fileSyncErrors'),
                        'public'       => lang('App.netPublic'),
                        'nat'          => lang('App.netNat'),
                        'sessionOk'    => lang('App.netSessionOk'),
                        'sessionKilled' => lang('App.netSessionKilled'),
                        'sessionOkTitle'     => lang('App.sessionSyncWouldSurvive'),
                        'sessionKilledTitle' => lang('App.sessionSyncWouldBeKilled'),
                    ]), 'attr') ?>"
                ></div>
                <div class="d-flex flex-wrap gap-3 justify-content-center mt-2 text-secondary" style="font-size:.75rem;">
                    <span><span class="legend-dot" style="background:#206bc4"></span> <?= lang('App.netThisNode') ?></span>
                    <span><span class="legend-dot" style="background:#2fb344"></span> <?= lang('App.netLegendOk') ?></span>
                    <span><span class="legend-dot" style="background:#d63939"></span> <?= lang('App.netLegendError') ?></span>
                    <span><span class="legend-dot" style="background:#8c959e"></span> <?= lang('App.netLegendIdle') ?></span>
                </div>
                <hr class="my-3">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr>
                            <th><?= lang('App.netThisNode') ?></th>
                            <th class="text-end"><?= lang('App.netAvgSpeed') ?></th>
                            <th class="text-end"><?= lang('App.netTreeTraffic') ?></th>
                        </tr></thead>
                        <tbody id="net-graph-table-body">
                            <?php foreach ($networkInfo['nodes'] as $peerName => $peerRow) : ?>
                                <tr>
                                    <td><?= esc($peerName) ?> <span class="badge bg-secondary-lt"><?= $peerRow['type'] === 'nat' ? lang('App.netNat') : lang('App.netPublic') ?></span></td>
                                    <td class="text-end"><?= esc($peerRow['avgSpeedHuman']) ?></td>
                                    <td class="text-end"><?= esc($peerRow['lastTransferBytesHuman']) ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($tableInfo !== null && $tableInfo['tables'] !== []) : ?>
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0"><?= lang('App.netTablesTitle') ?></h3>
            </div>
            <div class="card-body">
                <div id="tables-sunburst"
                    style="height:450px;"
                    data-tables="<?= esc(json_encode($tableInfo['tables']), 'attr') ?>"
                    data-size-supported="<?= $tableInfo['sizeSupported'] ? '1' : '0' ?>"
                    data-strings="<?= esc(json_encode([
                        'size'             => lang('App.netTablesSize'),
                        'records'          => lang('App.netTablesRecords'),
                        'traffic'          => lang('App.netTablesTraffic'),
                        'noData'           => lang('App.netTablesNoData'),
                        'sizeUnsupported'  => lang('App.netTablesSizeUnsupported'),
                    ]), 'attr') ?>"
                ></div>
                <?php if (! $tableInfo['sizeSupported']) : ?>
                    <div class="text-secondary text-center mt-2" style="font-size:.75rem;"><?= lang('App.netTablesSizeUnsupported') ?></div>
                <?php endif ?>
                <?php if ($tableInfo['nodeStats'] !== []) : ?>
                    <hr class="my-3">
                    <h4 class="mb-2 text-secondary" style="font-size:.8rem;"><?= lang('App.netTablesNodesTitle') ?></h4>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr>
                                <th><?= lang('App.netThisNode') ?></th>
                                <th class="text-end"><?= lang('App.netTablesCommands') ?></th>
                                <th class="text-end"><?= lang('App.netAvgSpeed') ?></th>
                                <th class="text-end"><?= lang('App.netTablesTraffic') ?></th>
                            </tr></thead>
                            <tbody>
                                <?php foreach ($tableInfo['nodeStats'] as $peerName => $peerRow) : ?>
                                    <tr>
                                        <td><?= esc($peerName) ?> <span class="badge bg-secondary-lt"><?= $peerRow['type'] === 'nat' ? lang('App.netNat') : lang('App.netPublic') ?></span></td>
                                        <td class="text-end"><?= (int) $peerRow['commandCount'] ?></td>
                                        <td class="text-end"><?= esc($peerRow['avgSpeedHuman']) ?></td>
                                        <td class="text-end"><?= esc($peerRow['trafficHuman']) ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
    <?php endif ?>

    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0"><?= lang('App.netNodeTreeTitle') ?></h3>
            </div>
            <div class="card-body">
                <div id="node-tree"
                    style="height:450px;"
                    data-strings="<?= esc(json_encode([
                        'thisNode'   => lang('App.netThisNode'),
                        'files'      => lang('App.netTreeFiles'),
                        'size'       => lang('App.netTreeSize'),
                        'speed'      => lang('App.netAvgSpeed'),
                        'traffic'    => lang('App.netTreeTraffic'),
                        'public'     => lang('App.netPublic'),
                        'nat'        => lang('App.netNat'),
                    ]), 'attr') ?>"
                ></div>
                <hr class="my-3">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr>
                            <th><?= lang('App.netThisNode') ?></th>
                            <th><?= lang('App.netAddress') ?></th>
                            <th class="text-end"><?= lang('App.netTreeFiles') ?></th>
                            <th class="text-end"><?= lang('App.netTreeSize') ?></th>
                        </tr></thead>
                        <tbody id="node-tree-table-body">
                            <?php foreach ($networkInfo['nodes'] as $peerName => $peerRow) : ?>
                                <tr>
                                    <td><?= esc($peerName) ?> <span class="badge bg-secondary-lt"><?= $peerRow['type'] === 'nat' ? lang('App.netNat') : lang('App.netPublic') ?></span></td>
                                    <td><?= esc(preg_replace('#^https?://#i', '', rtrim($peerRow['baseURL'], '/'))) ?></td>
                                    <td class="text-end"><?= (int) $peerRow['filesSynced'] ?></td>
                                    <td class="text-end"><?= esc($peerRow['totalBytesHuman']) ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.legend-dot{display:inline-block;width:.6rem;height:.6rem;border-radius:50%;vertical-align:middle;}
.dash-card-text{min-width:0;}
</style>
<?php elseif (! $clusterInstalled) : ?>
<div class="text-secondary"><?= lang('App.netNotInstalled') ?></div>
<?php else : ?>
<div class="text-secondary"><?= lang('App.netSuperadminOnly') ?></div>
<?php endif ?>

<?php if ($networkInfo !== null) : ?>
<script src="<?= base_url('assets/echarts/echarts.min.js') ?>" defer></script>
<script src="<?= base_url('assets/dashboard-network.js') ?>" defer></script>
<script src="<?= base_url('assets/dashboard-node-tree.js') ?>" defer></script>
<?php if ($tableInfo !== null && $tableInfo['tables'] !== []) : ?>
<script src="<?= base_url('assets/dashboard-tables.js') ?>" defer></script>
<?php endif ?>
<?php endif ?>
<?= $this->endSection() ?>
