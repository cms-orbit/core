<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cms:rows')]
class RowsCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cms:rows';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new rows layout class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Rows';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('rows.stub');
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
