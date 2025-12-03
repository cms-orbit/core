<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Commands;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cms:table')]
class TableCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cms:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new table layout class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Table';

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('table.stub');
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
