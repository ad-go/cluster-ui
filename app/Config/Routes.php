<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
// Deliberately no second literal 'dashboard' route alongside this one -
// found live 2026-08-20: the two collided on CI4's own implicit route
// NAME (this route's explicit 'as' => 'dashboard' vs. the second route's
// auto-derived name, also 'dashboard'), which silently made the literal
// /dashboard URI 404 rather than erroring at route-registration time.
// Nothing in this app ever links to that literal path anyway - every
// internal link uses url_to('dashboard'), which resolves via the NAME
// (registered here) to this route's own URI ('/'), never a hardcoded
// '/dashboard' string.
$routes->get('/', 'Dashboard::index', ['as' => 'dashboard', 'filter' => 'session']);
$routes->get('dashboard/network-status', 'Dashboard::networkStatus', ['filter' => 'session']);
$routes->post('locale', 'LocaleController::update', ['as' => 'locale.update']);
$routes->get('login', 'AuthController::loginView', ['as' => 'login']);
$routes->post('login', 'AuthController::loginAction');
$routes->get('logout', 'AuthController::logoutView', ['filter' => 'session']);
$routes->post('logout', 'AuthController::logoutAction', ['as' => 'logout', 'filter' => 'session']);

$routes->get('profile', 'ProfileController::index', ['filter' => 'session']);
$routes->post('profile', 'ProfileController::updateField', ['filter' => 'session']);
$routes->post('profile/preference', 'ProfileController::preference', ['filter' => 'session']);
$routes->post('profile/avatar', 'ProfileController::uploadAvatar', ['filter' => 'session']);
$routes->post('profile/avatar/delete', 'ProfileController::deleteAvatar', ['filter' => 'session']);

$routes->get('settings', 'SettingsController::index', ['filter' => 'session']);
$routes->post('settings', 'SettingsController::update', ['filter' => 'session']);
$routes->post('settings/nodes', 'SettingsController::updateNode', ['filter' => 'session']);
$routes->post('settings/nodes/test', 'SettingsController::testNode', ['filter' => 'session']);
$routes->get('settings/test-result', 'SettingsController::testResult', ['filter' => 'session']);
$routes->post('settings/databases', 'SettingsController::updateDatabase', ['filter' => 'session']);
$routes->post('settings/databases/test', 'SettingsController::testDatabase', ['filter' => 'session']);
$routes->post('settings/logo', 'SettingsController::uploadLogo', ['filter' => 'session']);
$routes->post('settings/logo/delete', 'SettingsController::deleteLogo', ['filter' => 'session']);
$routes->get('settings/export', 'SettingsController::exportSettings', ['filter' => 'session']);
$routes->post('settings/import', 'SettingsController::importSettings', ['filter' => 'session']);

$routes->get('users', 'UsersController::index', ['filter' => 'session']);
$routes->get('users/list', 'UsersController::list', ['filter' => 'session']);
$routes->get('users/(:num)', 'UsersController::show/$1', ['filter' => 'session']);
$routes->post('users', 'UsersController::create', ['filter' => 'session']);
$routes->post('users/(:num)', 'UsersController::update/$1', ['filter' => 'session']);
$routes->delete('users/(:num)', 'UsersController::delete/$1', ['filter' => 'session']);
$routes->post('users/(:num)/ban', 'UsersController::ban/$1', ['filter' => 'session']);
$routes->post('users/(:num)/unban', 'UsersController::unban/$1', ['filter' => 'session']);

service('auth')->routes($routes);

// ad-go/cluster is a separate install step, not part of this UI package -
// a fresh install must not 500 every request just because it hasn't been
// composer-required yet. Guarded so this file works identically before
// and after that package lands. Also found live to be NOT optional in
// practice even once installed - a route registered by hand directly on a
// node (not through this file) is silently wiped the next time cluster-ui
// itself gets re-applied there (CI4post.php overwrites this exact file),
// which is exactly what happened testing this integration the first time.
if (class_exists(\AdGo\Cluster\RouteRegistrar::class)) {
    \AdGo\Cluster\RouteRegistrar::register($routes);
}
