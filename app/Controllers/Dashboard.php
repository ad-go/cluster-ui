<?php

namespace App\Controllers;

use AdGo\Cluster\Cluster;
use AdGo\Cluster\SessionInvalidation;
use CodeIgniter\HTTP\ResponseInterface;

// Rewritten 2026-08-19 as a single-purpose network view - one ECharts graph
// of the cluster's nodes/speed/transfer, plus a colored-icon summary strip,
// replacing the previous six-card GridStack layout (system info, packages,
// file sync, DB sync, session sync, clock drift) entirely. That data still
// exists in ad-go/cluster (dashboardSummary()/dbSyncDashboardSummary()/
// clockDriftSummary() are all still there) - this controller just no
// longer renders most of it. If a use for it resurfaces, pull individual
// pieces back in rather than reverting this file wholesale.
class Dashboard extends BaseController
{
    public function index(): string
    {
        $isSuperadmin = (bool) (auth()->user()?->inGroup('superadmin'));

        return view('dashboard', [
            'user'        => auth()->user(),
            'networkInfo' => $isSuperadmin ? $this->networkInfo() : null,
            'tableInfo'   => $isSuperadmin ? $this->tableInfo() : null,
            // Distinct from networkInfo === null - that's also true for a
            // logged-in NON-superadmin even when ad-go/cluster is
            // installed, and the two need different messages (found live
            // 2026-08-19: a regular user saw "ad-go/cluster is not
            // installed on this node", which was simply false).
            'clusterInstalled' => class_exists(\AdGo\Cluster\Cluster::class),
        ] + $this->layoutData());
    }

    // Polled by public/assets/dashboard-network.js every 5s so the graph
    // and summary strip update live - same data index() already renders
    // server-side on first load, re-served as JSON. Gated the same way
    // index() gates the whole view - reachable directly by URL, not just
    // via the dashboard page, so it needs its own check.
    public function networkStatus(): ResponseInterface
    {
        if (! (auth()->user()?->inGroup('superadmin'))) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'superadmin only']);
        }

        return $this->response->setJSON(['networkInfo' => $this->networkInfo()]);
    }

    // ad-go/cluster is an OPTIONAL peer package (see app/Config/Routes.php's
    // own guard) - cluster-ui must not 500 on a node that never installed
    // it. class_exists() rather than a try/catch: cheap, reads clearly as
    // "is this package even here".
    //
    // Combines three of ad-go/cluster's own summaries (network topology +
    // file-sync totals + DB-sync totals) plus this session's own
    // would-survive-invalidation check into the one flat structure the
    // graph and the summary strip both read from - a single poll endpoint
    // for a single view, rather than the one-controller-method-per-card
    // split the previous six-card layout used.
    private function networkInfo(): ?array
    {
        if (! class_exists(Cluster::class)) {
            return null;
        }

        $cluster = new Cluster();
        $network = $cluster->networkSummary();
        $files   = $cluster->dashboardSummary();
        $db      = $cluster->dbSyncDashboardSummary();

        foreach ($network['nodes'] as &$node) {
            $node['avgSpeedHuman']         = $this->humanSize((int) round($node['avgSpeedBps'])) . '/s';
            $node['totalBytesHuman']       = $this->humanSize($node['totalBytes']);
            $node['lastTransferBytesHuman'] = $this->humanSize($node['lastTransferBytes']);
            $node['lastSyncAgo']           = $this->timeAgo($node['lastSyncAt']);
            $node['lastPushInAgo']         = $this->timeAgo($node['lastPushInAt']);
            $node['lastPullAgo']           = $this->timeAgo($node['lastPullAt']);
        }
        unset($node);

        return [
            'thisNode'            => $network['thisNode'],
            'configured'          => $network['configured'],
            'nodes'               => $network['nodes'],
            'totalFiles'          => $files['totalFiles'],
            'syncedFiles'         => $files['syncedFiles'],
            'pendingFiles'        => $files['pendingFiles'],
            'recentBytes'         => $files['recentBytes'],
            'recentBytesHuman'    => $this->humanSize($files['recentBytes']),
            'avgSpeedBps'         => $files['avgSpeedBps'],
            'avgSpeedHuman'       => $this->humanSize((int) round($files['avgSpeedBps'])) . '/s',
            'dbTotalSynced'       => $db['totalSynced'],
            'dbRecentOk'          => $db['recentOk'],
            'dbRecentErrors'      => $db['recentErrors'],
            'sessionWouldSurvive' => $this->sessionWouldSurvive(),
        ];
    }

    // Rendered once on page load, deliberately NOT part of networkStatus()'s
    // 5s poll: row counts/table sizes are structural, not fast-moving like
    // transfer speed - a COUNT(*) per table plus a dbstat scan on every
    // open Dashboard tab every 5s would be needless DB load for numbers
    // that rarely change minute to minute. Reload the page for a fresh
    // snapshot. Feeds the table sunburst (public/assets/dashboard-tables.js).
    private function tableInfo(): ?array
    {
        if (! class_exists(Cluster::class)) {
            return null;
        }

        $cluster = new Cluster();
        $stats   = $cluster->tableStats();

        $tables = [];
        foreach ($stats['tables'] as $name => $row) {
            $tables[$name] = [
                'records'      => $row['records'],
                'sizeBytes'    => $row['sizeBytes'],
                'sizeHuman'    => $row['sizeBytes'] !== null ? $this->humanSize($row['sizeBytes']) : null,
                'trafficBytes' => $row['trafficBytes'],
                'trafficHuman' => $this->humanSize($row['trafficBytes']),
            ];
        }

        $nodeStats = [];
        foreach ($cluster->dbSyncNodeStats() as $name => $row) {
            $nodeStats[$name] = $row + [
                'trafficHuman' => $this->humanSize($row['trafficBytes']),
                'avgSpeedHuman' => $this->humanSize((int) round($row['avgSpeedBps'])) . '/s',
            ];
        }

        return ['tables' => $tables, 'sizeSupported' => $stats['sizeSupported'], 'nodeStats' => $nodeStats];
    }

    // Same check SessionInvalidationFilter itself makes on every request
    // (see that filter's own docblock) - whether THIS session would still
    // be considered valid if a request came in right now, distilled to one
    // boolean for the summary strip's badge.
    private function sessionWouldSurvive(): bool
    {
        if (! class_exists(SessionInvalidation::class)) {
            return true;
        }

        $email = auth()->user()?->getEmailIdentity()?->secret;
        if ($email === null || $email === '') {
            return true;
        }

        $invalidatedAt = (new SessionInvalidation())->invalidatedAt($email);
        if ($invalidatedAt === null) {
            return true;
        }

        $loginAt = (int) (session('cluster_login_at') ?? 0);

        return $loginAt >= $invalidatedAt;
    }

    private function timeAgo(?int $timestamp): string
    {
        if ($timestamp === null) {
            return lang('App.fileSyncNeverSynced');
        }

        $seconds = max(0, time() - $timestamp);

        if ($seconds < 60) {
            return lang('App.fileSyncJustNow');
        }
        if ($seconds < 3600) {
            return lang('App.fileSyncMinutesAgo', [(string) (int) floor($seconds / 60)]);
        }
        if ($seconds < 86400) {
            return lang('App.fileSyncHoursAgo', [(string) (int) floor($seconds / 3600)]);
        }

        return lang('App.fileSyncDaysAgo', [(string) (int) floor($seconds / 86400)]);
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $value = $bytes;
        $i     = 0;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, 1) . ' ' . $units[$i];
    }
}
