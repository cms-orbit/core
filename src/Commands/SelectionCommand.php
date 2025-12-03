<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cms:selection')]
class SelectionCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cms:selection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new selection layout class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Selection';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('selection.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\CmsOrbit\Core\Layouts';
    }
}
