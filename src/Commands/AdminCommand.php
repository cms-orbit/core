<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use CmsOrbit\Core\Settings\Models\User;
use CmsOrbit\Core\Support\Facades\Dashboard;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cms:admin')]
class AdminCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cms:admin';

    /**
     * @var string
     */
    protected $signature = 'cms:admin {name?} {email?} {password?} {--id=}';

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
     *
     * @return void
     */
    protected function createNewUser(): void
    {
        Dashboard::modelClass(User::class)
            ->createAdmin(
                $this->argument('name') ?? $this->ask('What is your name?', 'admin'),
                $this->argument('email') ?? $this->ask('What is your email?', 'admin@admin.com'),
                $this->argument('password') ?? $this->secret('What is the password?')
            );

        $this->info('User created successfully.');
    }

    /**
     * Update the permissions of an existing user.
     *
     * @param string $id
     *
     * @return void
     */
    protected function updateUserPermissions(string $id): void
    {
        Dashboard::modelClass(User::class)
            ->findOrFail($id)
            ->forceFill([
                'permissions' => Dashboard::getAllowAllPermission(),
            ])
            ->save();

        $this->info('User permissions updated.');
    }
}
