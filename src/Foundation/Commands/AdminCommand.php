<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use CmsOrbit\Core\Foundation\Models\Role;
use CmsOrbit\Core\Foundation\Models\User as OrbitUser;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'orbit:admin')]
class AdminCommand extends Command
{
    protected const DEFAULT_EMAIL_FALLBACK = 'admin@localhost.test';

    protected const DEFAULT_PASSWORD = 'orbit1234';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'orbit:admin';

    /**
     * @var string
     */
    protected $signature = 'orbit:admin {name=Admin} {email?} {password?} {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update an Orbit admin user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $userId = $this->option('id');

            empty($userId)
                ? $this->createOrEnsureAdmin()
                : $this->updateUserPermissions((string) $userId);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Create a new admin user, or ensure an existing account keeps the role.
     */
    protected function createOrEnsureAdmin(): void
    {
        $role = $this->ensureSuperAdminRole();
        $name = (string) $this->argument('name');
        $email = $this->resolveEmail();
        $userModelClass = $this->resolveUserModelClass();

        /** @var Model|null $existingUser */
        $existingUser = $userModelClass::query()
            ->where('email', $email)
            ->first();

        if ($existingUser !== null) {
            $this->attachRole($existingUser, $role);
            $this->info(sprintf('Admin user "%s" already exists. Ensured the super-admin role.', $email));

            return;
        }

        /** @var Model $user */
        $user = $userModelClass::query()->create([
            'name'                 => $name,
            'email'                => $email,
            'password'             => Hash::make($this->resolvePassword()),
            'must_change_password' => true,
            'permissions'          => [],
        ]);

        $this->attachRole($user, $role);

        $this->info(sprintf('Admin user "%s" created successfully.', $email));
    }

    /**
     * Update the permissions of an existing user.
     */
    protected function updateUserPermissions(string $id): void
    {
        $userModelClass = $this->resolveUserModelClass();

        $userModelClass::query()
            ->findOrFail($id)
            ->forceFill([
                'permissions' => Orbit::getAllowAllPermission()->toArray(),
            ])
            ->save();

        $this->info('User permissions updated.');
    }

    protected function resolveEmail(): string
    {
        $email = $this->argument('email');

        if (is_string($email) && filled($email)) {
            return $email;
        }

        $default = $this->defaultAdminEmail();

        if (! $this->input->isInteractive()) {
            return $default;
        }

        return (string) $this->ask('What is the admin email?', $default);
    }

    protected function resolvePassword(): string
    {
        $password = $this->argument('password');

        if (is_string($password) && filled($password)) {
            return $password;
        }

        if (! $this->input->isInteractive()) {
            return self::DEFAULT_PASSWORD;
        }

        return (string) ($this->secret(
            sprintf('What is the password? (leave blank to use %s)', self::DEFAULT_PASSWORD)
        ) ?: self::DEFAULT_PASSWORD);
    }

    protected function defaultAdminEmail(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($host) || blank($host)) {
            return self::DEFAULT_EMAIL_FALLBACK;
        }

        $domain = Str::contains($host, '.') ? $host : "{$host}.test";

        return "admin@{$domain}";
    }

    /**
     * @return class-string<Model>
     */
    protected function resolveUserModelClass(): string
    {
        $guard = (string) config('orbit.guard', config('auth.defaults.guard', 'web'));
        $provider = config("auth.guards.{$guard}.provider");
        $modelClass = is_string($provider)
            ? config("auth.providers.{$provider}.model")
            : null;

        if (is_string($modelClass) && class_exists($modelClass)) {
            return $modelClass;
        }

        return Orbit::model(OrbitUser::class);
    }

    protected function ensureSuperAdminRole(): Role
    {
        $roleModelClass = Orbit::model(Role::class);
        $permissions = Orbit::getAllowAllPermission()->toArray();

        /** @var Role|null $role */
        $role = $roleModelClass::query()
            ->where('system_key', Role::SystemKeySuperAdmin)
            ->orWhere('slug', Role::SystemKeySuperAdmin)
            ->orWhere(function ($query): void {
                $query->whereNull('system_key')
                    ->whereIn('name', ['super-admin', Role::DisplayNameSuperAdmin]);
            })
            ->first();

        if ($role === null) {
            /** @var Role $role */
            $role = $roleModelClass::query()->create([
                'name'         => Role::DisplayNameSuperAdmin,
                'slug'         => Role::SystemKeySuperAdmin,
                'system_key'   => Role::SystemKeySuperAdmin,
                'is_deletable' => false,
                'permissions'  => $permissions,
            ]);

            return $role;
        }

        $role->forceFill([
            'slug'         => Role::SystemKeySuperAdmin,
            'system_key'   => Role::SystemKeySuperAdmin,
            'is_deletable' => false,
            'permissions'  => $permissions,
        ])->save();

        return $role;
    }

    protected function attachRole(Model $user, Role $role): void
    {
        if (! method_exists($user, 'roles')) {
            throw new \RuntimeException('The configured admin user model must define a roles relationship.');
        }

        $user->roles()->syncWithoutDetaching([$role->getKey()]);
    }
}
