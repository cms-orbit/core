<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use CmsOrbit\Core\Boost\BoostPackageSync;
use CmsOrbit\Core\Foundation\Providers\ConsoleServiceProvider;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'orbit:sync')]
class SyncCommand extends Command
{
    protected $signature = 'orbit:sync {--force : Overwrite publishable host stubs when possible}';

    protected $description = 'Safely publish Orbit host scaffolding updates without replacing the user model';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--provider' => ConsoleServiceProvider::class,
            '--tag'      => [
                'orbit-config',
                'orbit-app-stubs',
            ],
            '--force' => (bool) $this->option('force'),
        ]);

        $this->call('orbit:frontend-sync', [
            '--force' => (bool) $this->option('force'),
        ]);

        $sync = app(BoostPackageSync::class);
        $sync->registerOrbitPackages();

        if ($sync->canRefresh()) {
            $this->call('boost:update');
        }

        $this->components->info('Orbit host scaffolding synchronized.');
        $this->line('If your app uses a custom user model, ensure it includes `CmsOrbit\Core\Auth\Concerns\HasOrbitUserAccounts`.');

        return self::SUCCESS;
    }
}
