<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'orbit:upgrade')]
class UpgradeCommand extends Command
{
    protected $signature = 'orbit:upgrade {--force : Overwrite publishable host stubs when possible}';

    protected $description = 'Upgrade helper for existing Orbit hosts (publishes safe scaffolding and reminders)';

    public function handle(): int
    {
        $this->call('orbit:sync', [
            '--force' => (bool) $this->option('force'),
        ]);

        $this->line('Core migrations and runtime authentication changes do not require `orbit:install` to be re-run.');

        return self::SUCCESS;
    }
}
