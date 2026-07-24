<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Frontend;

/**
 * @phpstan-type FrontendPage array{component: string, export: string}
 * @phpstan-type FrontendManifest array{
 *     alias: string,
 *     jsPath?: string,
 *     dependencies?: array<string, string>,
 *     devDependencies?: array<string, string>,
 *     pages?: list<FrontendPage>
 * }
 */
final class FrontendManifest
{
    public function __construct(
        public readonly string $package,
        public readonly string $packagePath,
        /** @var FrontendManifest $config */
        public readonly array $config,
    ) {}

    public function alias(): string
    {
        return $this->config['alias'];
    }

    public function jsRoot(): string
    {
        return rtrim($this->packagePath, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .ltrim($this->config['jsPath'] ?? 'resources/js', DIRECTORY_SEPARATOR);
    }

    /**
     * @return list<FrontendPage>
     */
    public function pages(): array
    {
        return $this->config['pages'] ?? [];
    }

    /**
     * Module subpaths (relative to the package alias) whose import side-effect
     * registers custom admin components via `registerComponents`. Loaded before
     * the Orbit screen renders so packages can contribute fields/screens without
     * host edits. An empty string or "index" imports the alias root.
     *
     * @return list<string>
     */
    public function registrations(): array
    {
        $registrations = $this->config['registrations'] ?? [];

        return array_values(array_filter(array_map(
            fn ($value) => is_string($value) ? trim($value) : '',
            is_array($registrations) ? $registrations : [],
        ), fn (string $value) => $value !== ''));
    }

    /**
     * NPM runtime dependencies this package's frontend requires so a plain host
     * can `npm install && npm run build` without hand-editing `package.json`.
     *
     * @return array<string, string>
     */
    public function npmDependencies(): array
    {
        return $this->config['dependencies'] ?? [];
    }

    /**
     * NPM development dependencies this package's frontend requires.
     *
     * @return array<string, string>
     */
    public function npmDevDependencies(): array
    {
        return $this->config['devDependencies'] ?? [];
    }

    /**
     * Absolute directories Tailwind should scan for this package's class usage.
     *
     * @return list<string>
     */
    public function sourceDirectories(): array
    {
        $base = rtrim($this->packagePath, DIRECTORY_SEPARATOR);

        $candidates = [
            $this->jsRoot(),
            $base.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views',
            $base.DIRECTORY_SEPARATOR.'src',
        ];

        return array_values(array_filter($candidates, static fn (string $path): bool => is_dir($path)));
    }

    /**
     * Absolute path to the package-owned theme stylesheet, when present.
     */
    public function themeCssPath(): ?string
    {
        $path = $this->jsRoot()
            .DIRECTORY_SEPARATOR.'theme'
            .DIRECTORY_SEPARATOR.'orbit-theme.css';

        return is_file($path) ? $path : null;
    }

    /**
     * @return list<self>
     */
    public static function discover(string $basePath): array
    {
        $manifests = [];

        foreach (self::candidateRoots($basePath) as $root) {
            if (! is_dir($root)) {
                continue;
            }

            foreach (scandir($root) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $packagePath = $root.DIRECTORY_SEPARATOR.$entry;
                $manifestPath = $packagePath.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'orbit'.DIRECTORY_SEPARATOR.'frontend.json';

                if (! is_file($manifestPath)) {
                    continue;
                }

                $decoded = json_decode((string) file_get_contents($manifestPath), true);

                if (! is_array($decoded) || ! isset($decoded['alias']) || ! is_string($decoded['alias'])) {
                    continue;
                }

                $manifests[] = new self(
                    package: 'cms-orbit/'.$entry,
                    packagePath: $packagePath,
                    config: $decoded,
                );
            }
        }

        usort(
            $manifests,
            static fn (self $left, self $right): int => strcmp($left->package, $right->package),
        );

        return $manifests;
    }

    /**
     * @return list<string>
     */
    private static function candidateRoots(string $basePath): array
    {
        return array_values(array_unique([
            $basePath.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'cms-orbit',
            $basePath.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'cms-orbit',
        ]));
    }
}
