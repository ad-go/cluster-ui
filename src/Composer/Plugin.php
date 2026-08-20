<?php

declare(strict_types=1);

namespace AdGo\ClusterUi\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Makes `composer require ad-go/cluster-ui` the only command needed.
 *
 * This package ships views/controllers meant to live in the CONSUMING
 * app's own App\ namespace (app/Controllers/Dashboard.php etc.) and CSS/JS
 * under its public/assets/ - none of it is autoloadable PHP run from
 * vendor/, so plain `composer require` alone (which only ever populates
 * vendor/) can't put it where CodeIgniter's own autoloading and web root
 * expect it. A plain composer.json "scripts" entry can't do this either -
 * Composer only ever executes script hooks declared in the ROOT project's
 * own composer.json, never a dependency's, precisely so a required package
 * can't run arbitrary code without the installing project explicitly
 * opting in. A Composer plugin is the one mechanism a dependency itself
 * can use to hook into install/update - hence this class, not a script.
 *
 * Runs on every `composer install`/`composer update` in the consuming
 * project (not just the first time cluster-ui itself is installed/
 * updated) and unconditionally overwrites app/ and public/assets/ files
 * at the same relative path - same "lay on top" semantics the README
 * documents for the plain-archive install method, so behavior doesn't
 * differ by install method. A local edit to a copied file (e.g.
 * app/Controllers/Dashboard.php) will be overwritten by the next
 * `composer update` that touches this package, same as re-extracting the
 * archive over an edited tree would. Runs `php spark migrate` afterward
 * (see runMigrations()) so the user_profiles migration just copied in is
 * actually applied too - without it "composer require is the only
 * command" wouldn't be true, migrate would still be a required manual step.
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;

    private IOInterface $io;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io       = $io;
    }

    // Required by PluginInterface (Composer 2) - nothing to undo: the
    // copied app/ and public/assets/ files become the consuming project's
    // own files the moment they land, same as a manually extracted
    // archive would, not something this plugin tracks or owns afterward.
    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'copyIntoApp',
            ScriptEvents::POST_UPDATE_CMD  => 'copyIntoApp',
        ];
    }

    public function copyIntoApp(Event $event): void
    {
        // vendor-dir defaults to (and overwhelmingly is, in practice)
        // <project root>/vendor - the project root isn't exposed directly
        // by Composer's API, so it's derived from vendor-dir the same way
        // Composer's own runtime code does in the equivalent situation.
        $vendorDir   = rtrim($this->composer->getConfig()->get('vendor-dir'), '/\\');
        $projectRoot = dirname($vendorDir);
        $packageDir  = "$vendorDir/ad-go/cluster-ui";

        if (! is_dir($packageDir)) {
            // ad-go/cluster-ui was removed (composer remove) rather than
            // installed/updated - nothing to copy. Files already copied
            // into the consumer's app/ are left as-is (see class docblock).
            return;
        }

        $this->io->write('<info>cluster-ui:</info> copying app/ and public/assets/ into the project root');
        $this->copyTree("$packageDir/app", "$projectRoot/app");
        $this->copyTree("$packageDir/public/assets", "$projectRoot/public/assets");

        $this->runMigrations($projectRoot);
    }

    // The whole point of this plugin is that `composer require ad-go/
    // cluster-ui` is the only command needed - stopping short of running
    // its own migration (app/Database/Migrations/AddUserProfiles.php, just
    // copied above) would leave a manual `php spark migrate` step as the
    // one thing standing between that promise and reality. Runs with the
    // SAME PHP binary Composer itself is running under (PHP_BINARY) - a
    // consumer using a specific PHP version to run Composer is using that
    // same version to run spark, no separate detection needed.
    private function runMigrations(string $projectRoot): void
    {
        $spark = "$projectRoot/spark";
        if (! is_file($spark)) {
            $this->io->writeError('<warning>cluster-ui:</warning> no spark file found at ' . $spark . ' - run `php spark migrate` yourself.');
            return;
        }

        $this->io->write('<info>cluster-ui:</info> running php spark migrate');
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process     = proc_open([PHP_BINARY, 'spark', 'migrate'], $descriptors, $pipes, $projectRoot);
        if (! is_resource($process)) {
            $this->io->writeError('<warning>cluster-ui:</warning> could not start php spark migrate - run it yourself.');
            return;
        }

        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $this->io->write($output);
        if ($exitCode !== 0) {
            $this->io->writeError('<warning>cluster-ui:</warning> php spark migrate exited with code ' . $exitCode . ' - check the output above.');
        }
    }

    private function copyTree(string $from, string $to): void
    {
        if (! is_dir($from)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($from) + 1);
            $target   = "$to/$relative";

            if ($item->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0775, true);
                }
                continue;
            }

            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0775, true);
            }
            copy($item->getPathname(), $target);
        }
    }
}
