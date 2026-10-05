<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use CmsOrbit\Core\Frontend\FrontendManifest;
use CmsOrbit\Core\Frontend\FrontendSync;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'orbit:frontend-sync')]
class FrontendSyncCommand extends Command
{
    protected $signature = 'orbit:frontend-sync {--force : Overwrite existing host page bridges}';

    protected $description = 'Generate Inertia page bridges, Vite aliases, and tsconfig paths for installed cms-orbit packages';

    public function handle(): int
    {
        $sync = new FrontendSync(base_path());
        $result = $sync->sync((bool) $this->option('force'));

        $this->reportTsconfig($sync, $result['tsconfig']);

        if ($result['bridges'] === [] && ! $result['vite'] && ! $result['css'] && ! $result['entry'] && $result['npm'] === []) {
            $this->components->info('No frontend scaffolding changes were required.');

            return self::SUCCESS;
        }

        if ($result['bridges'] !== []) {
            $this->components->info('Created or refreshed Inertia page bridges:');
            foreach ($result['bridges'] as $bridge) {
                $this->line('  - '.$bridge);
            }
        }

        if ($result['css']) {
            $this->components->info('Generated the Orbit CSS entry: resources/css/orbit.css');
        }

        if ($result['entry']) {
            $this->components->info(__('Prepared the React entry for the Orbit admin panel.'));
        }

        if ($result['vite']) {
            $this->components->info('Updated Vite aliases for: '.implode(', ', $result['aliases']));
        } elseif ($result['aliases'] !== []) {
            $this->components->warn('Vite config was not updated automatically. Add aliases manually or ensure `vite.config.*` contains an `alias` block.');
        }

        if ($result['npm'] !== []) {
            $this->components->info('Added NPM dependencies to package.json: '.implode(', ', $result['npm']));
            $this->components->warn('Run `npm install` (and `npm run build`) to pull in the new dependencies.');
        }

        return self::SUCCESS;
    }

    /**
     * Vite resolves the page bridges through `resolve.alias`, but tsc needs the
     * same mapping in `compilerOptions.paths` or `types:check` fails with
     * TS2307 on every bridge while the build still passes. When the file cannot
     * be rewritten safely (missing, or containing comments that json_encode
     * would drop) print the block for the host to paste.
     *
     * @param array{updated: bool, reason: string|null} $tsconfig
     */
    private function reportTsconfig(FrontendSync $sync, array $tsconfig): void
    {
        if ($tsconfig['updated']) {
            $this->components->info('Updated tsconfig.json compilerOptions.paths.');

            return;
        }

        if ($tsconfig['reason'] === null) {
            return;
        }

        $block = $sync->tsconfigPathsBlock(FrontendManifest::discover(base_path()));

        if ($block === []) {
            return;
        }

        $this->components->warn($tsconfig['reason'] === 'missing'
            ? 'No tsconfig.json found. Add these paths if you type-check the host:'
            : 'tsconfig.json could not be rewritten safely (comments or trailing commas). Add these paths manually:');

        foreach ($block as $alias => $targets) {
            $this->line(sprintf('  "%s": ["%s"]', $alias, implode('", "', $targets)));
        }
    }
}
