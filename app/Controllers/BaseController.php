<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Persist locale across requests without URL locale prefixes:
        // without this, LocaleController's session()->set('locale', ...) is
        // saved but never applied back to the current request's locale on
        // subsequent page loads.
        $locale = session('locale');
        if (is_string($locale) && in_array($locale, config('App')->supportedLocales, true)) {
            $this->request->setLocale($locale);
        }

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Shared by every controller whose view extends Layout/app.php: the
     * logged-in user's theme + resizable-divider preferences, with sane
     * defaults for guests or a user with no saved profile row yet. Shield's
     * User entity has no 'avatar' property - it only knows identities
     * (email/password, tokens, etc.) - so the avatar path is threaded
     * through here, from user_profiles, not read off $user in the view.
     */
    protected function layoutData(): array
    {
        // themeColor (the accent color swatch) is site-wide, like the logo
        // and title - not per-user, so it applies even before login.
        $themeColor = setting('Site.themeColor') ?? 'blue';

        if (! auth()->loggedIn()) {
            return [
                'theme' => 'light', 'themeColor' => $themeColor, 'navbarHeight' => 56, 'taskbarWidth' => 220,
                'avatar' => null, 'autoSwitchNode' => false, 'clusterPeers' => [], 'thisNodeName' => '',
            ];
        }

        $profile = model(\App\Models\UserProfileModel::class)->forUser(auth()->id());

        return [
            'theme'          => $profile['theme'] ?? 'light',
            'themeColor'     => $themeColor,
            'navbarHeight'   => $profile['navbar_height'] ?? 56,
            'taskbarWidth'   => $profile['taskbar_width'] ?? 220,
            'avatar'         => $profile['avatar'] ?? null,
            'autoSwitchNode' => (bool) ($profile['auto_switch_node'] ?? false),
            'clusterPeers'   => $this->clusterPeers(),
            'thisNodeName'   => class_exists(\AdGo\Cluster\Cluster::class) ? (new \AdGo\Cluster\Cluster())->thisNodeName() : '',
        ];
    }

    /**
     * Public peers only (see Cluster::publicPeers()'s own docblock) - the
     * taskbar's "Switch server" dropdown links to `cluster/sso/start?node=X`
     * for each, and a 'nat' peer (private-LAN-only) is never reachable from
     * a plain browser anyway. Optional package (class_exists guard, same
     * convention as every other call site in this app).
     *
     * @return array<string, string> node name => baseURL
     */
    private function clusterPeers(): array
    {
        if (! class_exists(\AdGo\Cluster\Cluster::class)) {
            return [];
        }

        return array_map(
            static fn (array $node): string => $node['baseURL'],
            (new \AdGo\Cluster\Cluster())->publicPeers()
        );
    }

    /**
     * Every JSON response from a CSRF-protected AJAX endpoint must include
     * the CURRENT token: Config\Security::$regenerate is true (CI4
     * default), so the token issued with the page becomes stale after the
     * very first successful POST - reusing it for a second request 403s.
     * Every AJAX endpoint below returns this, and the matching JS in
     * public/assets/{app,settings,users}.js re-syncs window.CI4_CSRF from it.
     */
    protected function csrfPayload(): array
    {
        return ['name' => csrf_token(), 'hash' => csrf_hash()];
    }
}
