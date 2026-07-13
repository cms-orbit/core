<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Frontend;

use Illuminate\Support\Str;

final class FrontendSync
{
    private const VITE_MARKER_START = '// ORBIT:ALIASES:START';

    private const VITE_MARKER_END = '// ORBIT:ALIASES:END';

    /**
     * Host CSS entry generated for the Orbit admin panel. Referenced by the
     * `orbit/app.blade.php` view via `@vite` and registered as a Vite input.
     */
    private const CSS_ENTRY = 'resources/css/orbit.css';

    public function __construct(private readonly string $basePath) {}

    /**
     * @return array{bridges: list<string>, vite: bool, aliases: list<string>, css: bool, npm: list<string>}
     */
    public function sync(bool $force = false): array
    {
        $manifests = FrontendManifest::discover($this->basePath);
        $createdBridges = [];
        $aliases = [];

        foreach ($manifests as $manifest) {
            $aliases[] = $manifest->alias();

            foreach ($manifest->pages() as $page) {
                $bridgePath = $this->bridgePath($page['component']);

                if (! $force && is_file($bridgePath)) {
                    continue;
                }

                $this->writeBridge($bridgePath, $page, $manifest->alias());
                $createdBridges[] = $this->relativePath($bridgePath);
            }
        }

        $viteUpdated = $this->syncViteAliases($manifests);
        $cssUpdated = $this->syncStyleEntry($manifests);
        $viteInputUpdated = $this->syncViteInput();
        $addedNpm = $this->syncNpmDependencies($manifests);

        return [
            'bridges' => $createdBridges,
            'vite' => $viteUpdated || $viteInputUpdated,
            'aliases' => $aliases,
            'css' => $cssUpdated,
            'npm' => $addedNpm,
        ];
    }

    /**
     * Merge each package's declared NPM dependencies into the host `package.json`
     * so `npm install && npm run build` resolves every import used by the Orbit
     * admin frontend. Existing host versions are never overwritten; only missing
     * packages are added.
     *
     * @param  list<FrontendManifest>  $manifests
     * @return list<string> Names of packages newly added to the host manifest.
     */
    private function syncNpmDependencies(array $manifests): array
    {
        $packageJsonPath = $this->basePath.DIRECTORY_SEPARATOR.'package.json';

        if (! is_file($packageJsonPath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($packageJsonPath), true);

        if (! is_array($decoded)) {
            return [];
        }

        /** @var array<string, string> $dependencies */
        $dependencies = is_array($decoded['dependencies'] ?? null) ? $decoded['dependencies'] : [];
        /** @var array<string, string> $devDependencies */
        $devDependencies = is_array($decoded['devDependencies'] ?? null) ? $decoded['devDependencies'] : [];

        $existing = $dependencies + $devDependencies;
        $added = [];

        foreach ($manifests as $manifest) {
            foreach ($manifest->npmDependencies() as $name => $version) {
                if (isset($existing[$name])) {
                    continue;
                }

                $dependencies[$name] = $version;
                $existing[$name] = $version;
                $added[] = $name;
            }

            foreach ($manifest->npmDevDependencies() as $name => $version) {
                if (isset($existing[$name])) {
                    continue;
                }

                $devDependencies[$name] = $version;
                $existing[$name] = $version;
                $added[] = $name;
            }
        }

        if ($added === []) {
            return [];
        }

        ksort($dependencies);
        ksort($devDependencies);

        $decoded['dependencies'] = $dependencies;

        if ($devDependencies !== []) {
            $decoded['devDependencies'] = $devDependencies;
        }

        $encoded = json_encode(
            $decoded,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        if ($encoded === false) {
            return [];
        }

        file_put_contents($packageJsonPath, $encoded."\n");

        sort($added);

        return array_values(array_unique($added));
    }

    /**
     * Generate the self-contained host CSS entry that pulls in Tailwind, the
     * Orbit design tokens, and every installed package's class sources. This
     * keeps a plain Laravel host from having to hand-author any Orbit CSS.
     *
     * @param  list<FrontendManifest>  $manifests
     */
    private function syncStyleEntry(array $manifests): bool
    {
        if ($manifests === []) {
            return false;
        }

        $seen = [];
        $sourceLines = [];

        foreach ($manifests as $manifest) {
            foreach ($manifest->sourceDirectories() as $directory) {
                $key = realpath($directory) ?: $directory;

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $sourceLines[] = sprintf("@source '%s';", $this->cssRelativePath($directory));
            }
        }

        $seenThemes = [];
        $themeImports = [];

        foreach ($manifests as $manifest) {
            $themeCss = $manifest->themeCssPath();

            if ($themeCss === null) {
                continue;
            }

            $key = realpath($themeCss) ?: $themeCss;

            if (isset($seenThemes[$key])) {
                continue;
            }

            $seenThemes[$key] = true;
            $themeImports[] = sprintf("@import '%s';", $this->cssRelativePath($themeCss));
        }

        $sections = [
            "@import 'tailwindcss';",
            '@custom-variant dark (&:where(.dark, .dark *));',
        ];

        if ($sourceLines !== []) {
            $sections[] = implode("\n", $sourceLines);
        }

        if ($themeImports !== []) {
            $sections[] = implode("\n", $themeImports);
        }

        $contents = "/**\n"
            ." * Auto-generated by `php artisan orbit:frontend-sync`. Do not edit by hand.\n"
            ." *\n"
            ." * Regenerated whenever cms-orbit packages are installed or removed. It wires\n"
            ." * Tailwind content scanning to each package's sources and imports the Orbit\n"
            ." * design tokens so the admin panel renders correctly on any host.\n"
            ." */\n"
            .implode("\n\n", $sections)
            ."\n";

        $target = $this->basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, self::CSS_ENTRY);
        $directory = dirname($target);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (is_file($target) && (string) file_get_contents($target) === $contents) {
            return false;
        }

        file_put_contents($target, $contents);

        return true;
    }

    /**
     * Ensure the Orbit CSS entry is registered as a Laravel Vite input so it is
     * emitted into the build manifest.
     */
    private function syncViteInput(): bool
    {
        $vitePath = $this->resolveViteConfigPath();

        if ($vitePath === null) {
            return false;
        }

        $contents = (string) file_get_contents($vitePath);

        if (str_contains($contents, self::CSS_ENTRY)) {
            return false;
        }

        if (preg_match('/input\s*:\s*\[/', $contents) !== 1) {
            return false;
        }

        $updated = preg_replace(
            '/(input\s*:\s*\[)/',
            "$1'".self::CSS_ENTRY."', ",
            $contents,
            1,
        );

        if (! is_string($updated) || $updated === $contents) {
            return false;
        }

        file_put_contents($vitePath, $updated);

        return true;
    }

    /**
     * Path relative to the host `resources/css/` directory (where the generated
     * CSS entry lives), normalised to forward slashes for CSS `@source`/`@import`.
     */
    private function cssRelativePath(string $absolutePath): string
    {
        return '../../'.str_replace(DIRECTORY_SEPARATOR, '/', $this->relativePath($absolutePath));
    }

    /**
     * @param  list<FrontendManifest>  $manifests
     */
    private function syncViteAliases(array $manifests): bool
    {
        $vitePath = $this->resolveViteConfigPath();

        if ($vitePath === null) {
            return false;
        }

        $aliasBlock = $this->buildViteAliasBlock($manifests);
        $markedBlock = self::VITE_MARKER_START."\n".$aliasBlock.self::VITE_MARKER_END;
        $contents = (string) file_get_contents($vitePath);

        if (str_contains($contents, self::VITE_MARKER_START) && str_contains($contents, self::VITE_MARKER_END)) {
            // Existing Orbit-managed block: replace between the markers.
            $updated = (string) preg_replace(
                '/'.preg_quote(self::VITE_MARKER_START, '/').'.*?'.preg_quote(self::VITE_MARKER_END, '/').'/s',
                $markedBlock,
                $contents,
            );
        } elseif (preg_match('/alias\s*:\s*\{/', $contents) === 1) {
            // Host already declares `resolve.alias`: nest the marker block inside.
            $updated = (string) preg_replace(
                '/(alias\s*:\s*\{)/',
                '$1'."\n".$markedBlock."\n",
                $contents,
                1,
            );
        } elseif (preg_match('/resolve\s*:\s*\{/', $contents) === 1) {
            // Host has a `resolve` block but no `alias`: add an alias entry.
            $updated = (string) preg_replace(
                '/(resolve\s*:\s*\{)/',
                '$1'."\n        alias: {\n".$markedBlock."\n        },\n",
                $contents,
                1,
            );
        } elseif (preg_match('/defineConfig\s*\(\s*\{/', $contents) === 1) {
            // Plain host config (e.g. the blank starter kit): create the whole
            // `resolve.alias` block so package page bridges can resolve.
            $updated = (string) preg_replace(
                '/(defineConfig\s*\(\s*\{)/',
                '$1'."\n    resolve: {\n        alias: {\n".$markedBlock."\n        },\n    },\n",
                $contents,
                1,
            );
        } else {
            return false;
        }

        // The generated alias block resolves paths with `fileURLToPath(new URL(...))`,
        // so a plain host `vite.config.ts` (which does not import it) would otherwise
        // throw `ReferenceError: fileURLToPath is not defined` when Vite loads.
        $updated = $this->ensureFileUrlToPathImport($updated);

        if ($updated === '' || $updated === $contents) {
            return false;
        }

        file_put_contents($vitePath, $updated);

        return true;
    }

    /**
     * Ensure `fileURLToPath` is imported from `node:url` so the generated alias
     * block can resolve. No-op when the host already imports it.
     */
    private function ensureFileUrlToPathImport(string $contents): string
    {
        $alreadyImported = preg_match(
            '/import\s*\{[^}]*\bfileURLToPath\b[^}]*\}\s*from\s*[\'"](?:node:)?url[\'"]/',
            $contents,
        ) === 1;

        if ($alreadyImported) {
            return $contents;
        }

        return "import { fileURLToPath } from 'node:url';\n".$contents;
    }

    /**
     * @param  list<FrontendManifest>  $manifests
     */
    private function buildViteAliasBlock(array $manifests): string
    {
        $lines = [];

        foreach ($manifests as $manifest) {
            $relativeJsRoot = $this->relativePath($manifest->jsRoot());
            $lines[] = sprintf(
                "            '%s': fileURLToPath(new URL('./%s', import.meta.url)),",
                $manifest->alias(),
                str_replace(DIRECTORY_SEPARATOR, '/', $relativeJsRoot),
            );
        }

        if ($lines === []) {
            return '';
        }

        return implode("\n", $lines)."\n";
    }

    private function writeBridge(string $bridgePath, array $page, string $alias): void
    {
        $directory = dirname($bridgePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $component = $page['component'];
        $exportPath = $page['export'];

        $contents = <<<TSX
/**
 * Auto-generated by `php artisan orbit:frontend-sync`.
 * Host bridge for the Orbit "{$component}" page.
 */
export { default } from '{$alias}/{$exportPath}';

TSX;

        file_put_contents($bridgePath, $contents);
    }

    private function bridgePath(string $component): string
    {
        return $this->basePath
            .DIRECTORY_SEPARATOR.'resources'
            .DIRECTORY_SEPARATOR.'js'
            .DIRECTORY_SEPARATOR.'pages'
            .DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $component).'.tsx';
    }

    private function resolveViteConfigPath(): ?string
    {
        foreach (['vite.config.ts', 'vite.config.js', 'vite.config.mjs'] as $filename) {
            $path = $this->basePath.DIRECTORY_SEPARATOR.$filename;

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function relativePath(string $absolutePath): string
    {
        $normalizedBase = rtrim(str_replace('\\', '/', $this->basePath), '/').'/';
        $normalizedPath = str_replace('\\', '/', $absolutePath);

        return Str::startsWith($normalizedPath, $normalizedBase)
            ? Str::after($normalizedPath, $normalizedBase)
            : $normalizedPath;
    }
}
