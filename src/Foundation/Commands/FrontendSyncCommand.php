<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use CmsOrbit\Core\Frontend\FrontendSync;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'orbit:frontend-sync')]
class FrontendSyncCommand extends Command
{
    protected $signature = 'orbit:frontend-sync {--force : Overwrite existing host page bridges}';

    protected $description = 'Generate Inertia page bridges and Vite aliases for installed cms-orbit packages';

    public function handle(): int
    {
        $result = (new FrontendSync(base_path()))->sync((bool) $this->option('force'));

        if ($result['bridges'] === [] && ! $result['vite']) {
            $this->components->info('No frontend scaffolding changes were required.');

            return self::SUCCESS;
        }

        if ($result['bridges'] !== []) {
            $this->components->info('Created or refreshed Inertia page bridges:');
            foreach ($result['bridges'] as $bridge) {
                $this->line('  - '.$bridge);
            }
        }

        if ($result['vite']) {
            $this->components->info('Updated Vite aliases for: '.implode(', ', $result['aliases']));
        } elseif ($result['aliases'] !== []) {
            $this->components->warn('Vite config was not updated automatically. Add aliases manually or ensure `vite.config.*` contains an `alias` block.');
        }

        return self::SUCCESS;
    }
}
