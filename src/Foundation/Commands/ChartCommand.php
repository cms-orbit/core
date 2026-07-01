<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'orbit:chart')]
class ChartCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'orbit:chart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new chart layout class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Chart';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('chart.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Orbit\Layouts';
    }
}
