<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Pre-generate Laravel's real-time facade cache files for the few
 * classes our framework dependencies use that way.
 *
 * Laravel's `\Facades\Some\Class` syntax lazily writes a stub class file
 * to `storage/framework/cache/facade-<sha1>.php` the first time it's
 * referenced. That tempnam-based write can fail (with an E_NOTICE that
 * Laravel turns into a fatal) on hosts where the cache directory isn't
 * writable by PHP-FPM but IS writable by the CLI user — typical cPanel
 * jailshell pattern.
 *
 * Running this command after every deploy as the CLI user pre-fills
 * those files so the FPM request path never needs to call tempnam.
 *
 * Add to the deploy hook in deploy/deploy.sh:
 *     php artisan lexa:warm-facades
 */
class WarmFacadesCommand extends Command
{
    protected $signature = 'lexa:warm-facades';

    protected $description = 'Pre-generate real-time facade cache files so file uploads work on perm-strict hosts.';

    /**
     * Each entry is a real-time facade `\Facades\...` class name that
     * Livewire (or any framework code) references at runtime. Loading
     * them with class_exists($name, true) triggers AliasLoader, which
     * writes the stub to disk.
     */
    private const FACADES = [
        'Facades\Livewire\Features\SupportFileUploads\FileUploadController',
    ];

    public function handle(): int
    {
        $this->info('Warming '.count(self::FACADES).' real-time facade(s)…');

        foreach (self::FACADES as $class) {
            if (class_exists($class, true)) {
                $this->line('  ✓ '.$class);
            } else {
                $this->warn('  ✗ '.$class.' (autoload returned false — check the class still exists in vendor/)');
            }
        }

        $this->newLine();
        $this->info('Done. Facade stubs land in storage/framework/cache/facade-*.php');

        return self::SUCCESS;
    }
}
