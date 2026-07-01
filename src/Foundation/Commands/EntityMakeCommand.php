<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Scaffold an Entity descriptor into the root /entities/<Name>/ directory.
 */
#[AsCommand(name: 'orbit:entity')]
class EntityMakeCommand extends Command
{
    protected $signature = 'orbit:entity {name : The entity (StudlyCase) name} {--model= : The target Eloquent model class}';

    protected $description = 'Generate an Entity descriptor under /entities/<Name>/';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $model = $this->option('model') ?: 'App\\Models\\'.$name;

        $dir = base_path('entities/'.$name);
        File::ensureDirectoryExists($dir);

        $file = $dir.'/'.$name.'Entity.php';

        if (File::exists($file)) {
            $this->warn("Entity already exists: {$file}");

            return self::FAILURE;
        }

        $softDeletes = $this->modelUsesSoftDeletes($model);

        File::put($file, $this->stub($name, $model, $softDeletes));

        $this->info("Entity created: {$file}");
        $this->line($softDeletes
            ? 'Detected SoftDeletes: scaffolded crud() with trash + restore/forceDelete.'
            : 'No SoftDeletes detected: scaffolded crud() without trash/restore/forceDelete.');
        $this->line('Remember to register the /entities path via Orbit::registerEntities(base_path(\'entities\')).');

        return self::SUCCESS;
    }

    /**
     * Detect whether the target model opts into soft deletes. Unresolvable
     * models (not yet created) are treated as hard-delete only.
     */
    protected function modelUsesSoftDeletes(string $model): bool
    {
        $class = '\\'.ltrim($model, '\\');

        return class_exists($class)
            && in_array(SoftDeletes::class, class_uses_recursive($class), true);
    }

    protected function stub(string $name, string $model, bool $softDeletes): string
    {
        $modelClass = '\\'.ltrim($model, '\\');

        $crud = $softDeletes
            ? "['list', 'create', 'view', 'edit', 'delete', 'restore', 'forceDelete', 'trash']"
            : "['list', 'create', 'view', 'edit', 'delete']";

        $crudNote = $softDeletes
            ? 'The model uses SoftDeletes, so the trash listing and restore/forceDelete'
            : 'The model has no SoftDeletes, so there is no trash/restore/forceDelete';

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace Entities\\{$name};

        use CmsOrbit\\Core\\Foundation\\Entity\\Entity;
        use CmsOrbit\\Core\\Screen\\Fields\\Input;
        use CmsOrbit\\Core\\Screen\\TD;

        class {$name}Entity extends Entity
        {
            public function model(): string
            {
                return {$modelClass}::class;
            }

            /**
             * CRUD surface area for this entity — drives both the registered
             * routes and the submitted permissions. {$crudNote}
             * permissions are included. Edit this list to customise.
             *
             * @return array<int, string>
             */
            public function crud(): array
            {
                return {$crud};
            }

            /**
             * @return \\CmsOrbit\\Core\\Screen\\Field[]
             */
            public function fields(): array
            {
                return [
                    Input::make('name')->title('Name')->required(),
                ];
            }

            /**
             * @return TD[]
             */
            public function columns(): array
            {
                return [
                    TD::make('id', 'ID')->sort(),
                    TD::make('name', 'Name'),
                ];
            }
        }

        PHP;
    }
}
