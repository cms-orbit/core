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

    private const REACT_ENTRY = 'resources/js/app.tsx';

    /**
     * Alias namespace this class owns inside the host tsconfig `paths`. Keys
     * under it are added/removed as packages come and go; anything else the
     * host declared (e.g. "@/*") is left alone.
     */
    private const MANAGED_PATH_PREFIX = '@cms-orbit/';

    public function __construct(private readonly string $basePath) {}

    /**
     * @return array{bridges: list<string>, vite: bool, tsconfig: array{updated: bool, reason: string|null}, aliases: list<string>, css: bool, entry: bool, npm: list<string>}
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

        $this->syncRegistrations($manifests);

        $entryUpdated = $this->syncReactEntry($manifests);
        $wayfinderUpdated = $this->syncWayfinderPlugin($manifests);
        $viteUpdated = $this->syncViteAliases($manifests);
        $tsconfig = $this->syncTsconfigPaths($manifests);
        $cssUpdated = $this->syncStyleEntry($manifests);
        $viteInputUpdated = $this->syncViteInput();
        $addedNpm = $this->syncNpmDependencies($manifests);

        return [
            'bridges'  => $createdBridges,
            'vite'     => $entryUpdated || $wayfinderUpdated || $viteUpdated || $viteInputUpdated,
            'tsconfig' => $tsconfig,
            'aliases'  => $aliases,
            'css'      => $cssUpdated,
            'entry'    => $entryUpdated,
            'npm'      => $addedNpm,
        ];
    }

    /**
     * Bootstrap React only when the host has no existing app.tsx. Plain Laravel
     * ships app.js, which remains untouched and keeps its original Vite input.
     *
     * @param list<FrontendManifest> $manifests
     */
    private function syncReactEntry(array $manifests): bool
    {
        $entryPath = $this->basePath.'/'.self::REACT_ENTRY;
        $vitePath = $this->resolveViteConfigPath();

        if ($manifests === [] || is_file($entryPath) || $vitePath === null) {
            return false;
        }

        $contents = (string) file_get_contents($vitePath);

        if (preg_match('/plugins\s*:\s*\[/', $contents) !== 1 || preg_match('/input\s*:\s*\[/', $contents) !== 1) {
            return false;
        }

        if (! str_contains($contents, '@vitejs/plugin-react')) {
            $contents = "import orbitReact from '@vitejs/plugin-react';\n".$contents;
            $contents = (string) preg_replace('/(plugins\s*:\s*\[)/', '$1orbitReact(), ', $contents, 1);
        }

        if (! str_contains($contents, self::REACT_ENTRY)) {
            $contents = (string) preg_replace('/(input\s*:\s*\[)/', "$1'".self::REACT_ENTRY."', ", $contents, 1);
        }

        $this->ensureDirectory(dirname($entryPath));
        copy(__DIR__.'/../../stubs/app/app.tsx.stub', $entryPath);
        file_put_contents($vitePath, $contents);

        return true;
    }

    /**
     * The admin shell imports routes generated in the host by Wayfinder.
     *
     * @param list<FrontendManifest> $manifests
     */
    private function syncWayfinderPlugin(array $manifests): bool
    {
        $vitePath = $this->resolveViteConfigPath();

        if ($manifests === [] || $vitePath === null) {
            return false;
        }

        $contents = (string) file_get_contents($vitePath);

        if (str_contains($contents, '@laravel/vite-plugin-wayfinder') || preg_match('/plugins\s*:\s*\[/', $contents) !== 1) {
            return false;
        }

        $contents = "import { wayfinder as orbitWayfinder } from '@laravel/vite-plugin-wayfinder';\n".$contents;
        $contents = (string) preg_replace('/(plugins\s*:\s*\[)/', '$1orbitWayfinder(), ', $contents, 1);
        file_put_contents($vitePath, $contents);

        return true;
    }

    /**
     * Generate a host aggregator that imports every package's registration
     * modules (side-effect `registerComponents` calls), and ensure the Orbit
     * screen bridge imports it so custom admin fields/screens resolve. Packages
     * contribute admin React without any host file edits.
     *
     * @param list<FrontendManifest> $manifests
     */
    private function syncRegistrations(array $manifests): bool
    {
        $specifiers = [];

        foreach ($manifests as $manifest) {
            foreach ($manifest->registrations() as $subpath) {
                $specifiers[] = in_array($subpath, ['index', ''], true)
                    ? $manifest->alias()
                    : $manifest->alias().'/'.ltrim($subpath, '/');
            }
        }

        $specifiers = array_values(array_unique($specifiers));

        $aggregatorPath = $this->basePath.'/resources/js/orbit/registrations.ts';
        $this->ensureDirectory(dirname($aggregatorPath));

        $lines = array_map(fn (string $specifier) => "import '{$specifier}';", $specifiers);
        $body = "/**\n * Generated by `orbit:frontend-sync`. Imports package registration\n * modules so custom admin components resolve. Do not edit by hand.\n */\n"
            .($lines === [] ? "export {};\n" : implode("\n", $lines)."\n");

        $existing = is_file($aggregatorPath) ? (string) file_get_contents($aggregatorPath) : null;

        if ($existing !== $body) {
            file_put_contents($aggregatorPath, $body);
        }

        // Ensure the screen bridge loads registrations before rendering.
        $screenBridge = $this->bridgePath('orbit/screen');
        $import = "import '../../orbit/registrations';";

        if (is_file($screenBridge)) {
            $contents = (string) file_get_contents($screenBridge);

            if (! str_contains($contents, $import)) {
                file_put_contents($screenBridge, $import."\n".$contents);
            }
        }

        return $specifiers !== [];
    }

    private function ensureDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0o755, true);
        }
    }

    /**
     * Merge each package's declared NPM dependencies into the host `package.json`
     * so `npm install && npm run build` resolves every import used by the Orbit
     * admin frontend. Existing host versions are never overwritten; only missing
     * packages are added.
     *
     * @param list<FrontendManifest> $manifests
     *
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
     * @param list<FrontendManifest> $manifests
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
     * @param list<FrontendManifest> $manifests
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
     * @param list<FrontendManifest> $manifests
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

    /**
     * Mirror the Vite aliases into the host tsconfig `compilerOptions.paths`.
     *
     * Vite resolves the generated page bridges through `resolve.alias`, but tsc
     * knows nothing about that — so the host built fine while `types:check`
     * failed with TS2307 on every bridge. The starter kit's `composer ci:check`
     * runs `types:check`, which left new projects red from the first commit.
     *
     * Only keys under the managed prefix are touched, so host-owned entries
     * such as "@/*" survive and stale package entries are dropped.
     *
     * @param list<FrontendManifest> $manifests
     *
     * @return array{updated: bool, reason: string|null}
     */
    private function syncTsconfigPaths(array $manifests): array
    {
        $path = $this->basePath.DIRECTORY_SEPARATOR.'tsconfig.json';

        if (! is_file($path)) {
            return ['updated' => false, 'reason' => 'missing'];
        }

        $contents = (string) file_get_contents($path);
        $data = json_decode($contents, true);

        if (! is_array($data)) {
            // tsconfig allows comments and trailing commas; rewriting such a
            // file through json_encode would drop them. Report instead so the
            // command can print the block for the host to paste.
            return ['updated' => false, 'reason' => 'unparsable'];
        }

        $existing = $data['compilerOptions']['paths'] ?? [];
        $existing = is_array($existing) ? $existing : [];

        /*
         * Same source as the paste-me block the command prints for hosts we
         * cannot rewrite. Building the entries twice is how the two drifted:
         * the bare-specifier entry landed in one and not the other.
         */
        $desired = $this->tsconfigPathsBlock($manifests);

        $merged = $existing;

        foreach (array_keys($merged) as $key) {
            if (Str::startsWith((string) $key, self::MANAGED_PATH_PREFIX)
                && ! array_key_exists((string) $key, $desired)) {
                unset($merged[$key]);
            }
        }

        foreach ($desired as $key => $value) {
            $merged[$key] = $value;
        }

        if ($merged === $existing) {
            return ['updated' => false, 'reason' => null];
        }

        if ($merged === []) {
            unset($data['compilerOptions']['paths']);
        } else {
            $data['compilerOptions'] ??= [];
            $data['compilerOptions']['paths'] = $merged;
        }

        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            return ['updated' => false, 'reason' => 'unparsable'];
        }

        file_put_contents($path, $encoded."\n");

        return ['updated' => true, 'reason' => null];
    }

    /**
     * The tsconfig `paths` block for hosts we could not write to automatically.
     *
     * @param list<FrontendManifest> $manifests
     *
     * @return array<string, list<string>>
     */
    public function tsconfigPathsBlock(array $manifests): array
    {
        $block = [];

        foreach ($manifests as $manifest) {
            $relativeJsRoot = trim(str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                $this->relativePath($manifest->jsRoot()),
            ), '/');

            $block[$manifest->alias().'/*'] = ['./'.$relativeJsRoot.'/*'];

            /*
             * The bare specifier needs its own entry. A `paths` wildcard only
             * matches when there is something after the slash, so
             * "@cms-orbit/core/*" leaves
             *
             *     import { registerComponents } from '@cms-orbit/core';
             *
             * unresolved — the form this package's own guidelines document.
             * Vite handles it because its alias points at the directory and
             * node resolution finds the index; tsc needs to be told.
             */
            $index = $this->packageIndexFile($manifest->jsRoot());

            if ($index !== null) {
                $block[$manifest->alias()] = ['./'.$relativeJsRoot.'/'.$index];
            }
        }

        return $block;
    }

    /**
     * The entry file a bare import of the package resolves to, if any.
     *
     * Satellite packages without an index only get the wildcard entry, so we
     * never write a path that resolves to nothing.
     */
    private function packageIndexFile(string $jsRoot): ?string
    {
        foreach (['index.ts', 'index.tsx', 'index.js'] as $candidate) {
            if (is_file($jsRoot.DIRECTORY_SEPARATOR.$candidate)) {
                return $candidate;
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
