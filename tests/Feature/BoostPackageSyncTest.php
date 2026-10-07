<?php

declare(strict_types=1);

use CmsOrbit\Core\Boost\BoostPackageSync;
use Composer\InstalledVersions;
use Illuminate\Filesystem\Filesystem;

it('discovers installed Orbit guidance without depending on Boost internal APIs', function () {
    expect((new BoostPackageSync)->discoverOrbitPackages())->toContain('cms-orbit/core');
});

it('includes guideline and skill packages while excluding unrelated and virtual packages', function () {
    $filesystem = new Filesystem;
    $directory = sys_get_temp_dir().'/orbit-boost-'.bin2hex(random_bytes(8));
    $installed = InstalledVersions::getRawData();

    try {
        $filesystem->makeDirectory($directory.'/guidelines/resources/boost/guidelines', 0755, true);
        $filesystem->makeDirectory($directory.'/skills/resources/boost/skills', 0755, true);
        InstalledVersions::reload([
            'root'     => $installed['root'],
            'versions' => [
                'cms-orbit/z-guidelines' => ['install_path' => $directory.'/guidelines'],
                'cms-orbit/a-skills'     => ['install_path' => $directory.'/skills'],
                'cms-orbit/empty'        => ['install_path' => $directory.'/empty'],
                'cms-orbit/virtual'      => ['install_path' => null],
                'other/guidelines'       => ['install_path' => $directory.'/guidelines'],
            ],
        ]);

        $packages = (new BoostPackageSync)->discoverOrbitPackages();

        expect($packages)->toBe(['cms-orbit/a-skills', 'cms-orbit/core', 'cms-orbit/z-guidelines']);
    } finally {
        InstalledVersions::reload($installed);
        $filesystem->deleteDirectory($directory);
    }
});
