<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Console\Command;
use Laravel\Boost\BoostServiceProvider;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Distribute Orbit's development guidelines to the host application's AI tools.
 *
 * Mirrors Filament's development-aid approach: a single canonical guideline
 * (shipped at `resources/boost/guidelines/orbit.md`) is appended to the common
 * agent files with idempotent markers and written to tool-specific locations.
 *
 * When Laravel Boost is installed it already auto-discovers the package
 * guideline, so this command mainly serves non-Boost setups and tool files
 * Boost does not manage.
 */
#[AsCommand(name: 'orbit:ai')]
class AiCommand extends Command
{
    protected $signature = 'orbit:ai {--force : Overwrite tool-specific files instead of skipping existing ones}';

    protected $description = 'Distribute Orbit development guidelines to AI tooling (AGENTS.md, CLAUDE.md, Cursor, Copilot)';

    private const MARKER_START = '<!-- ORBIT:START -->';

    private const MARKER_END = '<!-- ORBIT:END -->';

    public function handle(): int
    {
        $source = Orbit::path('resources/boost/guidelines/orbit.md');

        if (! is_file($source)) {
            $this->error('Orbit guideline source not found: '.$source);

            return self::FAILURE;
        }

        $guideline = trim((string) file_get_contents($source));

        $this->syncMarkerFile(base_path('AGENTS.md'), $guideline);
        $this->syncMarkerFile(base_path('CLAUDE.md'), $guideline);
        $this->syncMarkerFile(base_path('.github/copilot-instructions.md'), $guideline);
        $this->writeCursorRule($guideline);

        if ($this->boostAvailable()) {
            $this->line('');
            $this->comment('Laravel Boost detected. Run "php artisan boost:install" to include Orbit guidelines in Boost-managed files too.');
        }

        $this->info('Orbit AI guidelines distributed.');

        return self::SUCCESS;
    }

    /**
     * Append or refresh the Orbit section inside a marker-delimited block so the
     * command is safe to re-run without duplicating content.
     */
    private function syncMarkerFile(string $path, string $guideline): void
    {
        $block = self::MARKER_START."\n".$guideline."\n".self::MARKER_END;

        $this->ensureDirectory($path);

        $existing = is_file($path) ? (string) file_get_contents($path) : '';

        if (str_contains($existing, self::MARKER_START) && str_contains($existing, self::MARKER_END)) {
            $updated = (string) preg_replace(
                '/'.preg_quote(self::MARKER_START, '/').'.*?'.preg_quote(self::MARKER_END, '/').'/s',
                $block,
                $existing,
            );
            $action = 'Updated';
        } elseif ($existing === '') {
            $updated = $block."\n";
            $action = 'Created';
        } else {
            $updated = rtrim($existing)."\n\n".$block."\n";
            $action = 'Appended to';
        }

        file_put_contents($path, $updated);

        $this->line(sprintf('%s %s', $action, $this->relativePath($path)));
    }

    /**
     * Write the Cursor rule file with the required frontmatter. Existing files
     * are only overwritten when `--force` is passed.
     */
    private function writeCursorRule(string $guideline): void
    {
        $path = base_path('.cursor/rules/orbit.mdc');

        if (is_file($path) && ! $this->option('force')) {
            $this->line(sprintf('Skipped %s (exists; use --force)', $this->relativePath($path)));

            return;
        }

        $this->ensureDirectory($path);

        $contents = <<<MDC
        ---
        description: Orbit CMS development guidelines
        alwaysApply: true
        ---

        {$guideline}

        MDC;

        file_put_contents($path, $contents);

        $this->line(sprintf('Wrote %s', $this->relativePath($path)));
    }

    private function ensureDirectory(string $path): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    private function relativePath(string $path): string
    {
        return ltrim(str_replace(base_path(), '', $path), DIRECTORY_SEPARATOR);
    }

    private function boostAvailable(): bool
    {
        return class_exists(BoostServiceProvider::class);
    }
}
