<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use CmsOrbit\Core\Foundation\Models\User;
use CmsOrbit\Core\Support\Facades\Orbit;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'orbit:admin')]
class AdminCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'orbit:admin';

    /**
     * @var string
     */
    protected $signature = 'orbit:admin {name?} {email?} {password?} {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update admin user';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {
            $userId = $this->option('id');

            empty($userId)
                ? $this->createNewUser()
                : $this->updateUserPermissions((string) $userId);
        } catch (Exception|QueryException $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Create a new user administrator.
     */
    protected function createNewUser(): void
    {
        Orbit::modelClass(User::class)
            ->createAdmin(
                $this->argument('name') ?? $this->ask('What is your name?', 'admin'),
                $this->argument('email') ?? $this->ask('What is your email?', 'admin@admin.com'),
                $this->argument('password') ?? $this->secret('What is the password?')
            );

        $this->info('User created successfully.');
    }

    /**
     * Update the permissions of an existing user.
     */
    protected function updateUserPermissions(string $id): void
    {
        Orbit::modelClass(User::class)
            ->findOrFail($id)
            ->forceFill([
                'permissions' => Orbit::getAllowAllPermission(),
            ])
            ->save();

        $this->info('User permissions updated.');
    }
}
