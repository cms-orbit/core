<?php

namespace CmsOrbit\Core\Foundation\Commands;

use CmsOrbit\Core\Foundation\Orbit;

abstract class GeneratorCommand extends \Illuminate\Console\GeneratorCommand
{
    /**
     * Resolve the fully-qualified path to the stub.
     */
    protected function resolveStubPath(string $stub): string
    {
        $path = $this->laravel->basePath('stubs/orbit/platform/'.trim($stub, '/'));

        return file_exists($path)
            ? $path
            : Orbit::path('stubs/'.$stub);
    }
}
