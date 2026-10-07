<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Boost;

use Composer\InstalledVersions;
use Laravel\Boost\BoostServiceProvider;
use Laravel\Boost\Support\Config;

/**
 * Keeps installed cms-orbit/* Boost packages registered and refreshes Boost
 * when the host application already completed an initial boost:install.
 */
final class BoostPackageSync
{
    /**
     * @return list<string>
     */
    public function discoverOrbitPackages(): array
    {
        return collect(InstalledVersions::getInstalledPackages())
            ->filter(static fn (string $package): bool => str_starts_with($package, 'cms-orbit/'))
            ->filter(static function (string $package): bool {
                $path = InstalledVersions::getInstallPath($package);

                return $path !== null && (is_dir($path.'/resources/boost/guidelines')
                    || is_dir($path.'/resources/boost/skills'));
            })
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function registerOrbitPackages(): int
    {
        if (! class_exists(BoostServiceProvider::class)) {
            return 0;
        }

        $packages = $this->discoverOrbitPackages();

        if ($packages === []) {
            return 0;
        }

        $config = app(Config::class);
        $merged = array_values(array_unique(array_merge($config->getPackages(), $packages)));
        sort($merged);
        $config->setPackages($merged);

        return count($packages);
    }

    public function canRefresh(): bool
    {
        if (! class_exists(BoostServiceProvider::class)) {
            return false;
        }

        $config = app(Config::class);

        return $config->isValid() && $config->getAgents() !== [];
    }
}
