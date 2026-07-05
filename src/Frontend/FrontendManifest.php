<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Frontend;

/**
 * @phpstan-type FrontendPage array{component: string, export: string}
 * @phpstan-type FrontendManifest array{
 *     alias: string,
 *     jsPath?: string,
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
