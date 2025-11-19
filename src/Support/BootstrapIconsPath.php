<?php

namespace CmsOrbit\Core;

use Composer\InstalledVersions;

class BootstrapIconsPath
{
    /**
     * Get the folder path of the Bootstrap Icons.
     */
    public static function getFolder(): string
    {
        $packagePath = InstalledVersions::getInstallPath('twbs/bootstrap-icons');

        return realpath($packagePath.'/icons');
    }
}
