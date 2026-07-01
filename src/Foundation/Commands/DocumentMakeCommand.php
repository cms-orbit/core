<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Scaffold a document-type model + DocumentEntity into /entities/<Name>/.
 */
#[AsCommand(name: 'orbit:document')]
class DocumentMakeCommand extends Command
{
    protected $signature = 'orbit:document {name : The document type (StudlyCase) name}';

    protected $description = 'Generate a DocumentModel + DocumentEntity under /entities/<Name>/';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $dir = base_path('entities/'.$name);
        File::ensureDirectoryExists($dir);

        $modelFile = $dir.'/'.$name.'.php';
        $entityFile = $dir.'/'.$name.'Entity.php';

        if (File::exists($entityFile)) {
            $this->warn("Document entity already exists: {$entityFile}");

            return self::FAILURE;
        }

        File::put($modelFile, $this->modelStub($name));
        File::put($entityFile, $this->entityStub($name));

        $this->info("Document model created: {$modelFile}");
        $this->info("Document entity created: {$entityFile}");

        return self::SUCCESS;
    }

    protected function modelStub(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace Entities\\{$name};

        use CmsOrbit\\Core\\Document\\DocumentModel;

        class {$name} extends DocumentModel
        {
            protected \$table = '{$this->tableName($name)}';
        }

        PHP;
    }

    protected function entityStub(string $name): string
    {
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace Entities\\{$name};

        use CmsOrbit\\Core\\Foundation\\Entity\\DocumentEntity;

        class {$name}Entity extends DocumentEntity
        {
            public function model(): string
            {
                return {$name}::class;
            }
        }

        PHP;
    }

    protected function tableName(string $name): string
    {
        return Str::snake(Str::pluralStudly($name));
    }
}
