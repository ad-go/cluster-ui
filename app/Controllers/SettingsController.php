<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Taskbar "Settings" menu: a reactive form with no save button - every
 * field autosaves on change via fetch(), one field per request, straight
 * into the Settings package's own store (config('Settings')/database
 * handler, no dedicated Config class needed for these free-form keys).
 * Superadmin only (see app/Views/Layout/app.php taskbar guard).
 */
class SettingsController extends BaseController
{
    // Matches the [data-bs-theme-primary="..."] variants tabler-themes.min.css
    // ships (see this repo's README - Tabler's CSS/JS isn't bundled here).
    public const THEME_COLORS = ['blue', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green', 'teal', 'cyan'];

    // Per-node fields editable from the Nodes table below. 'name' isn't in
    // here - it's the row key (ad-go/cluster's own node registry), not an
    // editable value. Stored as Settings key 'Nodes.{prop}' with the node
    // name as $context - see nodeRows()'s own docblock.
    //
    // 'protocol' plus TWO independent credential sets, not one - ftp* for
    // the FTP/FTPS family, ssh* for the SSH/SCP family (SCP rides over an
    // SSH connection, same host/port/user/pass either way). Switching
    // 'protocol' between families must never overwrite the OTHER family's
    // stored credentials - a node deployed over FTPS today but reachable
    // over SSH too (h1q, bak, res all are) needs both remembered
    // independently, not the last-selected one clobbering the other.
    public const NODE_PROPS = ['type', 'url', 'protocol', 'ftpHost', 'ftpPort', 'ftpUser', 'ftpPass', 'sshHost', 'sshPort', 'sshUser', 'sshPass'];
    public const NODE_TYPES = ['nat', 'public'];
    public const NODE_PROTOCOLS = ['FTP', 'FTPS explicit (AUTH TLS)', 'SSH', 'SCP'];

    // Per-node Databases table, same swap-on-select shape as Nodes' FTP/SSH
    // credential families above, generalized to FIVE independent sets
    // instead of two - one per CI4-supported driver, not just the driver
    // this app itself happens to run. Each node can be reached (for ad-hoc
    // admin/inspection, not this app's own runtime connection) via any of
    // these; switching the Type dropdown swaps which credential set the
    // Host/Port/User/Pass/Database columns show and re-saves them, exactly
    // like Nodes' protocol swap, just across 5 families instead of 2.
    // Database name IS its own field (added 2026-08-19 - some of this
    // project's real nodes reuse a pre-existing, differently-named database
    // rather than one literally called after the CI4 connection group, e.g.
    // upz's 'production' group actually points at its pre-existing D10beta
    // database - the credential set alone doesn't capture that).
    public const DATABASE_TYPES = ['mysql', 'postgres', 'sqlite3', 'oci8', 'sqlsrv'];
    public const DATABASE_PROPS = [
        'type',
        'mysqlHost', 'mysqlPort', 'mysqlUser', 'mysqlPass', 'mysqlDatabase',
        'postgresHost', 'postgresPort', 'postgresUser', 'postgresPass', 'postgresDatabase',
        'sqlite3Host', 'sqlite3Port', 'sqlite3User', 'sqlite3Pass', 'sqlite3Database',
        'oci8Host', 'oci8Port', 'oci8User', 'oci8Pass', 'oci8Database',
        'sqlsrvHost', 'sqlsrvPort', 'sqlsrvUser', 'sqlsrvPass', 'sqlsrvDatabase',
    ];

    public function index(): string
    {
        if (! (auth()->user()?->inGroup('superadmin'))) {
            return redirect()->to('dashboard');
        }

        return view('Settings/index', [
            'siteTitle'  => setting('Site.title'),
            'siteFooter' => setting('Site.footer'),
            'siteLogo'   => setting('Site.logo'),
            // Named siteTheme (not 'theme') so it can't collide with the
            // per-user 'theme' that layoutData() below feeds to
            // Layout/app.php's <html data-bs-theme="...">.
            'siteTheme'  => setting('Site.theme') ?? 'light',
            'siteThemeColor' => setting('Site.themeColor') ?? 'blue',
            'user'       => auth()->user(),
            'nodes'      => $this->nodeRows(),
            'databases'  => $this->databaseRows(),
        ] + $this->layoutData());
    }

    public function update(): ResponseInterface
    {
        if (! (auth()->user()?->inGroup('superadmin'))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }

        $allowed = ['title', 'footer', 'theme', 'themeColor'];
        $field   = (string) $this->request->getPost('field');
        $value   = (string) $this->request->getPost('value');
        if (! in_array($field, $allowed, true)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        // Only the exact palette tabler-themes.min.css ships
        // [data-bs-theme-primary="..."] rules for - anything else silently
        // falls back to Tabler's default blue with no indication why.
        if ($field === 'themeColor' && ! in_array($value, self::THEME_COLORS, true)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        service('settings')->set('Site.' . $field, $value);

        return $this->response->setJSON(['ok' => true, 'csrf' => $this->csrfPayload()]);
    }

    /**
     * One row per node known to ad-go/cluster's own registry (env
     * 'cluster.nodes' - see Cluster::allNodes()), with any Settings-stored
     * override (type/url/ftp*) layered on top. Nothing is written to the
     * Settings store until a superadmin actually edits a field - this just
     * seeds the table so it isn't blank on first load.
     *
     * Keyed as 'Nodes.{prop}' with the node NAME as the Settings package's
     * own $context parameter (Settings::get()'s 3rd arg) - NOT jammed into
     * the dotted key itself as 'Nodes.{name}.{prop}'. Found live
     * 2026-08-19: codeigniter4/settings' own parseDotSyntax() splits on
     * EVERY dot and prepareClassAndProperty() then keeps only the first
     * two parts (class, property), silently dropping anything after the
     * second dot - 'Nodes.beta.ftpHost' collapsed to class=Nodes,
     * property=beta, so every property write for the SAME node overwrote
     * the exact same row instead of five independent ones. $context is
     * the mechanism this package actually ships for exactly this case.
     *
     * @return array<string, array<string, string>>
     */
    private function nodeRows(): array
    {
        $registry = class_exists(\AdGo\Cluster\Cluster::class) ? (new \AdGo\Cluster\Cluster())->allNodes() : [];
        $settings = service('settings');

        $rows = [];
        foreach ($registry as $name => $node) {
            $rows[$name] = [
                'type'     => $settings->get('Nodes.type', $name) ?? $node['type'],
                'url'      => $settings->get('Nodes.url', $name) ?? $node['baseURL'],
                'protocol' => $settings->get('Nodes.protocol', $name) ?? 'FTP',
                'ftpHost'  => $settings->get('Nodes.ftpHost', $name) ?? '',
                'ftpPort'  => $settings->get('Nodes.ftpPort', $name) ?? '',
                'ftpUser'  => $settings->get('Nodes.ftpUser', $name) ?? '',
                'ftpPass'  => $settings->get('Nodes.ftpPass', $name) ?? '',
                'sshHost'  => $settings->get('Nodes.sshHost', $name) ?? '',
                'sshPort'  => $settings->get('Nodes.sshPort', $name) ?? '',
                'sshUser'  => $settings->get('Nodes.sshUser', $name) ?? '',
                'sshPass'  => $settings->get('Nodes.sshPass', $name) ?? '',
            ];
        }

        return $rows;
    }

    /**
     * One row per node, mirroring nodeRows() above exactly (same Settings
     * $context mechanism, same "nothing written until a superadmin edits a
     * field" seeding rule) but for the Databases table - five independent
     * driver-credential sets per node instead of Nodes' two protocol
     * families. 'type' defaults to 'mysql' (the only driver this project
     * actually has real credentials for anywhere - see CI4cluster.asc/upz
     * and h1q's own freshly-provisioned local MariaDB) rather than
     * whatever the node's OWN app.php DBDriver happens to be - this table
     * is an admin credential book for reaching a node's database directly
     * (any of the 5 drivers), not a mirror of the app's own active
     * connection group.
     *
     * @return array<string, array<string, string>>
     */
    private function databaseRows(): array
    {
        $registry = class_exists(\AdGo\Cluster\Cluster::class) ? (new \AdGo\Cluster\Cluster())->allNodes() : [];
        $settings = service('settings');

        $rows = [];
        foreach ($registry as $name => $node) {
            $row = ['type' => $settings->get('Databases.type', $name) ?? 'mysql'];
            foreach (self::DATABASE_PROPS as $prop) {
                if ($prop === 'type') {
                    continue;
                }
                $row[$prop] = $settings->get('Databases.' . $prop, $name) ?? '';
            }
            $rows[$name] = $row;
        }

        return $rows;
    }

    // Reactive Databases table on the Settings page - same autosave/context
    // shape as updateNode() below, just against DATABASE_PROPS/DATABASE_TYPES.
    public function updateDatabase(): ResponseInterface
    {
        if (! (auth()->user()?->inGroup('superadmin'))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }

        $node  = (string) $this->request->getPost('node');
        $prop  = (string) $this->request->getPost('prop');
        $value = (string) $this->request->getPost('value');

        $known = class_exists(\AdGo\Cluster\Cluster::class) ? (new \AdGo\Cluster\Cluster())->allNodes() : [];
        if (! array_key_exists($node, $known) || ! in_array($prop, self::DATABASE_PROPS, true)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        if ($prop === 'type' && ! in_array($value, self::DATABASE_TYPES, true)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        service('settings')->set('Databases.' . $prop, $value, $node);

        return $this->response->setJSON(['ok' => true, 'csrf' => $this->csrfPayload()]);
    }

    // Node-name badge on the Databases table opens a modal (see settings.js)
    // that calls this. Gathers this row's currently-saved credentials into
    // the wire shape DbConnectionChecker::checkParams() (and, for a public/
    // NAT peer, RemoteTestController) expect, then hands off to
    // dispatchTest() - see that method's own docblock for WHY this can't
    // just call checkNode() locally like it used to.
    public function testDatabase(): ResponseInterface
    {
        if (! (auth()->user()?->inGroup('superadmin'))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }

        $node  = (string) $this->request->getPost('node');
        $known = class_exists(\AdGo\Cluster\Cluster::class) ? (new \AdGo\Cluster\Cluster())->allNodes() : [];
        if (! array_key_exists($node, $known)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Unknown node.']);
        }
        if (! class_exists(\AdGo\Cluster\DbConnectionChecker::class)) {
            return $this->response->setStatusCode(503)->setJSON(['ok' => false, 'error' => 'ad-go/cluster is not installed.']);
        }

        $settings = service('settings');
        $type     = (string) ($settings->get('Databases.type', $node) ?? 'mysql');

        return $this->dispatchTest('database', $node, [
            'driverType' => $type,
            'host'       => (string) ($settings->get('Databases.' . $type . 'Host', $node) ?? ''),
            'port'       => (string) ($settings->get('Databases.' . $type . 'Port', $node) ?? ''),
            'user'       => (string) ($settings->get('Databases.' . $type . 'User', $node) ?? ''),
            'pass'       => (string) ($settings->get('Databases.' . $type . 'Pass', $node) ?? ''),
            'database'   => (string) ($settings->get('Databases.' . $type . 'Database', $node) ?? ''),
        ]);
    }

    // Reactive Nodes table on the Settings page - one field per request,
    // same autosave pattern as update() above, just keyed by (node, prop)
    // instead of a flat field name since this is a table, not a form.
    public function updateNode(): ResponseInterface
    {
        if (! (auth()->user()?->inGroup('superadmin'))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }

        $node  = (string) $this->request->getPost('node');
        $prop  = (string) $this->request->getPost('prop');
        $value = (string) $this->request->getPost('value');

        $known = class_exists(\AdGo\Cluster\Cluster::class) ? (new \AdGo\Cluster\Cluster())->allNodes() : [];
        if (! array_key_exists($node, $known) || ! in_array($prop, self::NODE_PROPS, true)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        if ($prop === 'type' && ! in_array($value, self::NODE_TYPES, true)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        if ($prop === 'protocol' && ! in_array($value, self::NODE_PROTOCOLS, true)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        // $node is the Settings package's own $context param, not part of
        // the dotted key - see nodeRows()'s own docblock for why.
        service('settings')->set('Nodes.' . $prop, $value, $node);

        return $this->response->setJSON(['ok' => true, 'csrf' => $this->csrfPayload()]);
    }

    // Node-name badge on the Nodes table opens a modal (see settings.js,
    // the same shared conn-test-modal the Databases table uses) that calls
    // this - a REAL connect (FTP/FTPS/SSH/SCP, whichever is currently
    // selected for that row) against whatever deploy credentials are
    // currently saved. Same dispatchTest() hand-off as testDatabase() -
    // see that method's own docblock.
    public function testNode(): ResponseInterface
    {
        if (! (auth()->user()?->inGroup('superadmin'))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }

        $node  = (string) $this->request->getPost('node');
        $known = class_exists(\AdGo\Cluster\Cluster::class) ? (new \AdGo\Cluster\Cluster())->allNodes() : [];
        if (! array_key_exists($node, $known)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Unknown node.']);
        }
        if (! class_exists(\AdGo\Cluster\NodeConnectionChecker::class)) {
            return $this->response->setStatusCode(503)->setJSON(['ok' => false, 'error' => 'ad-go/cluster is not installed.']);
        }

        $settings = service('settings');
        $protocol = (string) ($settings->get('Nodes.protocol', $node) ?? 'FTP');
        $family   = in_array($protocol, ['SSH', 'SCP'], true) ? 'ssh' : 'ftp';

        return $this->dispatchTest('node', $node, [
            'protocol' => $protocol,
            'host'     => (string) ($settings->get('Nodes.' . $family . 'Host', $node) ?? ''),
            'port'     => (string) ($settings->get('Nodes.' . $family . 'Port', $node) ?? ''),
            'user'     => (string) ($settings->get('Nodes.' . $family . 'User', $node) ?? ''),
            'pass'     => (string) ($settings->get('Nodes.' . $family . 'Pass', $node) ?? ''),
        ]);
    }

    /**
     * Shared by testDatabase()/testNode() above - decides HOW to actually
     * run a connection test for $node and returns the CI4 JSON response
     * either way. The credentials being tested (a Databases row's host is
     * often literally 127.0.0.1) only mean something from the TARGET
     * node's own local network position - running the check wherever the
     * admin's browser session happens to be connected (this app's
     * previous behavior) silently tested the WRONG thing for any node
     * other than itself. Found live 2026-08-19: testing bak's Nodes-table
     * SSH row from h1q's dashboard timed out (h1q genuinely can't reach
     * 192.168.0.253) while the identical test run from res's own local
     * network context succeeded instantly - and bak/res have no public
     * URL at all, so there was previously no way to test their own local
     * resources through this UI whatsoever.
     *
     * Three cases:
     * - $node IS this node: run the check right here, no network hop -
     *   the common case (an admin testing the node whose dashboard
     *   they're already on), and avoids any self-referential-HTTPS
     *   weirdness a loopback call to this node's own public URL could hit.
     * - $node is 'public': reachable directly - a synchronous signed
     *   POST to that node's own cluster/test-connection (see
     *   RemoteTestController), which runs the check there and returns
     *   the result in the same response.
     * - $node is 'nat': NOT reachable directly, ever (see this package's
     *   own README "NAT pulling"). Enqueued locally (RemoteTestQueue) for
     *   that node's own cluster:pull cycle to claim, execute, and report
     *   back next time it asks THIS node "anything pending for me" - see
     *   PullSync::pullTestRequests(). The response here is `{pending:
     *   true, requestId}`, not a result; settings.js polls testResult()
     *   below until one shows up or its own client-side timeout gives up.
     *   Latency is bounded by that pull cadence (as fast as ~5s with
     *   Config\Cluster::$pullLoopSeconds tuned down, up to ~60s on the
     *   plain once-a-minute cron default) - not instant, by design.
     */
    private function dispatchTest(string $kind, string $node, array $params): ResponseInterface
    {
        $cluster = new \AdGo\Cluster\Cluster();
        $params['kind'] = $kind;

        if ($node === $cluster->thisNodeName()) {
            $result = $this->runLocalTest($kind, $params);

            return $this->response->setJSON($result + ['csrf' => $this->csrfPayload()]);
        }

        $target = $cluster->node($node);
        if (($target['type'] ?? '') === 'public') {
            $result = $this->callRemoteTest($cluster, (string) $target['baseURL'], $params);

            return $this->response->setJSON($result + ['csrf' => $this->csrfPayload()]);
        }

        // 'nat' (or an unrecognized type - fail toward the safe/async path
        // rather than assuming direct reachability).
        $requestId = bin2hex(random_bytes(16));
        if (class_exists(\AdGo\Cluster\RemoteTestQueue::class)) {
            (new \AdGo\Cluster\RemoteTestQueue())->enqueue($node, $requestId, $params);
        }

        return $this->response->setJSON([
            'pending'   => true,
            'requestId' => $requestId,
            'node'      => $node,
            'csrf'      => $this->csrfPayload(),
        ]);
    }

    // Same single dispatch point RemoteTestController/PullSync route
    // every remote check through too - see CapabilityChecker's own
    // docblock. Was its own $kind==='database' ternary here until
    // 2026-08-19; that made THREE independent copies of the same
    // kind->checker mapping across this repo and ad-go/cluster.
    private function runLocalTest(string $kind, array $params): array
    {
        return (new \AdGo\Cluster\CapabilityChecker())->run($kind, $params);
    }

    private function callRemoteTest(\AdGo\Cluster\Cluster $cluster, string $baseURL, array $params): array
    {
        try {
            $client   = service('curlrequest', ['baseURI' => $baseURL, 'timeout' => 15], null, null, false);
            $response = $client->post('cluster/test-connection', [
                'headers' => ['Authorization' => $cluster->authHeader()],
                'json'    => $params,
            ]);
            $decoded = json_decode($response->getBody(), true);

            return is_array($decoded) ? $decoded : ['ok' => false, 'error' => 'Invalid response from target node.', 'ms' => 0.0];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage(), 'ms' => 0.0];
        }
    }

    // Polling endpoint for the async NAT-relay path (see dispatchTest()) -
    // shared by both the Nodes and Databases tables since a requestId is
    // already a globally unique opaque token, no need for two near-
    // identical endpoints. {pending:true} until RemoteTestQueue actually
    // has a result recorded (see RemoteTestController::testResult(), the
    // NAT node's own report-back call) - settings.js keeps polling on
    // that response and stops once a real ok/error result (or its own
    // client-side timeout) arrives.
    public function testResult(): ResponseInterface
    {
        if (! (auth()->user()?->inGroup('superadmin'))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }

        $requestId = (string) $this->request->getGet('requestId');
        if ($requestId === '' || ! class_exists(\AdGo\Cluster\RemoteTestQueue::class)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Invalid request.']);
        }

        $result = (new \AdGo\Cluster\RemoteTestQueue())->result($requestId);
        if ($result === null) {
            return $this->response->setJSON(['pending' => true]);
        }

        return $this->response->setJSON($result + ['csrf' => $this->csrfPayload()]);
    }

    // Export button on the Nodes card - dumps nodeRows()/databaseRows() as-is
    // (same shape this controller already builds for index(), so "export"
    // and "what the tables show" can never drift apart) into a downloadable
    // {host}-{date time}.json. Includes plaintext credentials, same as the
    // tables themselves already show a superadmin on screen - not a new
    // exposure, just a portable copy of it.
    public function exportSettings(): ResponseInterface
    {
        if (! (auth()->user()?->inGroup('superadmin'))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }

        $cluster = class_exists(\AdGo\Cluster\Cluster::class) ? new \AdGo\Cluster\Cluster() : null;
        $host    = $cluster?->thisNodeName() ?: (gethostname() ?: 'node');

        $payload = [
            'host'       => $host,
            'exportedAt' => date('Y-m-d H:i:s'),
            'nodes'      => $this->nodeRows(),
            'databases'  => $this->databaseRows(),
        ];

        $filename = $host . '-' . date('Y-m-d_H-i-s') . '.json';

        return $this->response
            ->setContentType('application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    // Import button's counterpart - loads a file in exportSettings()'s exact
    // shape and overwrites the Settings table from it. Validated in a FIRST
    // pass, entirely, before a SECOND pass writes anything - a malformed or
    // partly-invalid file must not leave the Settings table half-overwritten
    // (same all-or-nothing principle used for cluster.dbSyncGroup elsewhere
    // in this project).
    public function importSettings(): ResponseInterface
    {
        if (! (auth()->user()?->inGroup('superadmin'))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }

        $file = $this->request->getFile('file');
        if ($file === null || ! $file->isValid()) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'No valid file uploaded.']);
        }

        $decoded = json_decode((string) file_get_contents($file->getTempName()), true);
        if (! is_array($decoded) || ! isset($decoded['nodes'], $decoded['databases']) || ! is_array($decoded['nodes']) || ! is_array($decoded['databases'])) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Invalid file - expected {"nodes": {...}, "databases": {...}}.']);
        }

        $known = class_exists(\AdGo\Cluster\Cluster::class) ? (new \AdGo\Cluster\Cluster())->allNodes() : [];

        foreach ($decoded['nodes'] as $node => $props) {
            if ($error = $this->invalidImportRow((string) $node, $props, $known, self::NODE_PROPS)) {
                return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'nodes.' . $node . ': ' . $error]);
            }
            foreach ($props as $prop => $value) {
                if ($prop === 'type' && ! in_array((string) $value, self::NODE_TYPES, true)) {
                    return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => "nodes.{$node}: invalid type '{$value}'."]);
                }
                if ($prop === 'protocol' && ! in_array((string) $value, self::NODE_PROTOCOLS, true)) {
                    return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => "nodes.{$node}: invalid protocol '{$value}'."]);
                }
            }
        }
        foreach ($decoded['databases'] as $node => $props) {
            if ($error = $this->invalidImportRow((string) $node, $props, $known, self::DATABASE_PROPS)) {
                return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'databases.' . $node . ': ' . $error]);
            }
            foreach ($props as $prop => $value) {
                if ($prop === 'type' && ! in_array((string) $value, self::DATABASE_TYPES, true)) {
                    return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => "databases.{$node}: invalid type '{$value}'."]);
                }
            }
        }

        $settings = service('settings');
        foreach ($decoded['nodes'] as $node => $props) {
            foreach ($props as $prop => $value) {
                $settings->set('Nodes.' . $prop, (string) $value, (string) $node);
            }
        }
        foreach ($decoded['databases'] as $node => $props) {
            foreach ($props as $prop => $value) {
                $settings->set('Databases.' . $prop, (string) $value, (string) $node);
            }
        }

        return $this->response->setJSON(['ok' => true, 'csrf' => $this->csrfPayload()]);
    }

    // Shared row-shape check for importSettings() - returns an error string
    // (or null if the row is fine) rather than throwing, so the caller can
    // fold it straight into its own '{table}.{node}: {error}' message.
    private function invalidImportRow(string $node, mixed $props, array $known, array $allowedProps): ?string
    {
        if (! array_key_exists($node, $known)) {
            return "unknown node '{$node}'.";
        }
        if (! is_array($props)) {
            return 'expected an object of properties.';
        }
        foreach ($props as $prop => $value) {
            if (! in_array($prop, $allowedProps, true)) {
                return "unknown property '{$prop}'.";
            }
            if (! is_scalar($value) && $value !== null) {
                return "property '{$prop}' must be a string.";
            }
        }

        return null;
    }

    public function uploadLogo(): ResponseInterface
    {
        if (! (auth()->user()?->inGroup('superadmin'))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }

        $logo = $this->request->getFile('logo');
        if ($logo === null || ! $logo->isValid() || $logo->hasMoved()) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        $old = setting('Site.logo');
        if ($old && is_file(FCPATH . $old)) {
            @unlink(FCPATH . $old);
        }
        $name = $logo->getRandomName();
        $logo->move(FCPATH . 'uploads/site', $name);
        service('settings')->set('Site.logo', 'uploads/site/' . $name);

        return $this->response->setJSON(['ok' => true, 'path' => 'uploads/site/' . $name, 'csrf' => $this->csrfPayload()]);
    }

    // Thumbnail "Delete" badge next to the logo upload field.
    public function deleteLogo(): ResponseInterface
    {
        if (! (auth()->user()?->inGroup('superadmin'))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false]);
        }

        $path = setting('Site.logo');
        if ($path && is_file(FCPATH . $path)) {
            @unlink(FCPATH . $path);
        }
        service('settings')->set('Site.logo', null);

        return $this->response->setJSON(['ok' => true, 'csrf' => $this->csrfPayload()]);
    }
}
