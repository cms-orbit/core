<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
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
        $name = $this->argument('name') ?? $this->ask('What is your name?', 'admin');
        $email = $this->argument('email') ?? $this->ask('What is your email?', 'admin@admin.com');
        $password = $this->argument('password') ?? $this->secret('What is the password?');

        // Get user model class from config or use default
        $userClass = config('orbit.auth.user_model', config('auth.providers.users.model', \App\Entities\User\User::class));

        $user = $userClass::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'permissions' => Dashboard::getAllowAllPermission(),
        ]);

        $this->info('User created successfully.');
        $this->newLine();
        $this->comment('Login credentials:');
        $this->line("  Email: {$email}");
        $this->line("  Password: {$password}");
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
        // Get user model class from config or use default
        $userClass = config('orbit.auth.user_model', config('auth.providers.users.model', \App\Entities\User\User::class));

        $user = $userClass::findOrFail($id);
        $user->forceFill([
            'permissions' => Dashboard::getAllowAllPermission(),
        ])->save();

        $this->info('User permissions updated.');
        $this->line("  User: {$user->name} ({$user->email})");
    }
}
