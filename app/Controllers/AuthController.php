<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authentication\Authenticators\Session;

class AuthController extends BaseController
{
    public function loginView(): RedirectResponse|string
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config('Auth')->loginRedirect())->withCookies();
        }

        return view(config('Auth')->views['login']);
    }

    public function loginAction(): RedirectResponse
    {
        $identifier = trim((string) $this->request->getPost('identifier'));
        $password   = (string) $this->request->getPost('password');
        $mode       = config('Auth')->loginIdentifier;

        if ($identifier === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', lang('App.invalidCredentials'));
        }

        // In 'both' mode the view submits which radio the user actually
        // picked (identifier_mode) - that selection is now authoritative,
        // not just decorative. A non-JS client that omits it (or an old
        // cached page) falls back to guessing from the identifier's shape,
        // same as before.
        if ($mode === 'both') {
            $requested = (string) $this->request->getPost('identifier_mode');
            $field     = in_array($requested, ['email', 'username'], true)
                ? $requested
                : (filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false ? 'email' : 'username');
        } else {
            $field = $mode;
        }

        // Same format rules Shield itself enforces elsewhere (user
        // creation, its own registration flow) - checked here too so a
        // malformed identifier for the selected mode fails with a clear
        // reason instead of a generic auth failure three steps later
        // inside attempt(). Reuses Shield's own config, not a duplicated
        // set of rules that could drift out of sync with it.
        $rules = ($field === 'email' ? config('Auth')->emailValidationRules : config('Auth')->usernameValidationRules)['rules'];
        if (! $this->validateData(['identifier' => $identifier], ['identifier' => $rules])) {
            return redirect()->back()->withInput()->with('error', lang('App.invalidCredentials'));
        }

        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();
        $result = $authenticator
            ->remember((bool) $this->request->getPost('remember'))
            ->attempt([$field => $identifier, 'password' => $password]);

        if (! $result->isOK()) {
            return redirect()->route('login')->withInput()->with('error', lang('App.invalidCredentials'));
        }

        $switch = $this->fastestNodeRedirect();
        if ($switch !== null) {
            return $switch->withCookies();
        }

        return redirect()->to(config('Auth')->loginRedirect())->withCookies();
    }

    // "Auto" toggle in the taskbar's "Switch server" dropdown (see
    // user_profiles.auto_switch_node) - only checked on a genuine manual
    // login through THIS form, never on an incoming SSO handoff
    // (AdGo\Cluster\Controllers\SsoController::consume() is a separate code
    // path in the other package and never calls this), so switching once
    // here can never ping-pong back and forth between nodes.
    //
    // "Fastest" reuses Cluster::networkSummary()'s avgSpeedBps (the same
    // number already shown on the Dashboard's network graph) rather than
    // adding a second live network probe at login time - a peer with no
    // recorded transfer activity yet has nothing to compare, so it's
    // skipped rather than treated as "0 = fastest".
    private function fastestNodeRedirect(): ?RedirectResponse
    {
        if (! class_exists(\AdGo\Cluster\Cluster::class)) {
            return null;
        }

        $user = auth()->user();
        if ($user === null) {
            return null;
        }

        $profile = model(\App\Models\UserProfileModel::class)->forUser($user->id);
        if (empty($profile['auto_switch_node'])) {
            return null;
        }

        $cluster = new \AdGo\Cluster\Cluster();
        $nodes   = $cluster->networkSummary()['nodes'] ?? [];

        $fastest      = null;
        $fastestSpeed = 0.0;
        foreach ($nodes as $name => $node) {
            $speed = (float) ($node['avgSpeedBps'] ?? 0);
            if (($node['type'] ?? '') === 'public' && $speed > $fastestSpeed) {
                $fastest      = $name;
                $fastestSpeed = $speed;
            }
        }

        return $fastest !== null ? redirect()->to(site_url('cluster/sso/start') . '?node=' . urlencode($fastest)) : null;
    }

    public function logoutView(): string
    {
        return view('Auth/logout');
    }

    public function logoutAction(): RedirectResponse
    {
        // Cluster-wide logout (see ad-go/cluster's Cluster::broadcastLogout())
        // - reuses the exact same email-keyed invalidation mechanism
        // ProfileController::update() already triggers on a password
        // change (SessionInvalidationFilter, cluster-wide), just fired
        // here instead so THIS session's own logout kills every other
        // node's session for the same email too, not only this one.
        // Email must be read BEFORE auth()->logout() below clears the
        // session/user - class_exists()-guarded since ad-go/cluster is an
        // optional peer package (same convention as every other call site
        // that touches it).
        if (class_exists(\AdGo\Cluster\Cluster::class)) {
            $identity = auth()->user()?->getEmailIdentity();
            $email    = $identity !== null ? (string) $identity->secret : '';
            if ($email !== '') {
                (new \AdGo\Cluster\Cluster())->broadcastLogout($email);
            }
        }

        auth()->logout();

        return redirect()->to(site_url('login'))->with('message', lang('Auth.successLogout'));
    }
}
