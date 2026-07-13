<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use CmsOrbit\Core\Auth\Enums\LoginProvider;
use CmsOrbit\Core\Auth\Models\UserAccount;
use CmsOrbit\Core\Auth\Support\LoginIdentifierNormalizer;
use CmsOrbit\Core\Auth\UserAccountManager;
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
    protected $signature = 'orbit:admin {name=Admin} {identifier?} {password?} {--provider=email} {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or update an Orbit admin user';

    public function __construct(
        protected UserAccountManager $accounts,
    ) {
        parent::__construct();
    }

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
        $provider = $this->resolveProvider();
        $identifier = $this->resolveIdentifier($provider);
        $userModelClass = $this->resolveUserModelClass();

        /** @var Model|null $existingUser */
        $existingUser = match ($provider) {
            LoginProvider::Email => $userModelClass::query()->where('email', $identifier)->first(),
            default => UserAccount::query()
                ->where('provider', $provider->value)
                ->where('normalized_identifier', LoginIdentifierNormalizer::normalize($provider, $identifier))
                ->first()?->user,
        };

        if ($existingUser !== null) {
            $this->attachRole($existingUser, $role);
            $this->info(sprintf('Admin user "%s" already exists. Ensured the super-admin role.', $identifier));

            return;
        }

        $password = $this->resolvePassword();

        /** @var Model $user */
        $user = $userModelClass::query()->create([
            'name' => $name,
            'email' => $provider === LoginProvider::Email ? $identifier : null,
            'password' => Hash::make($password),
            'must_change_password' => true,
            'permissions' => [],
        ]);

        $this->accounts->syncManagedAccounts($user, [
            'primary_email' => $provider === LoginProvider::Email ? $identifier : null,
            'login_id' => $provider === LoginProvider::Id ? $identifier : null,
            'phone' => $provider === LoginProvider::Phone ? $identifier : null,
            'email_verified' => $provider === LoginProvider::Email,
            'phone_verified' => $provider === LoginProvider::Phone,
        ]);

        $this->attachRole($user, $role);

        $this->info(sprintf('Admin user "%s" created successfully.', $identifier));
        $this->reportLoginCredentials($provider, $identifier, $password);
    }

    /**
     * Print the exact credentials to sign in with, so the operator does not have
     * to guess the (possibly defaulted) password on the login screen.
     */
    protected function reportLoginCredentials(LoginProvider $provider, string $identifier, string $password): void
    {
        $this->newLine();
        $this->line('  <options=bold>로그인 정보 / Sign-in credentials</>');
        $this->line(sprintf('    %-16s <info>%s</info>', $provider->label().':', $identifier));

        if ($password === self::DEFAULT_PASSWORD) {
            $this->line(sprintf('    %-16s <info>%s</info>  <comment>(기본값 / default)</comment>', 'Password:', self::DEFAULT_PASSWORD));
        } else {
            $this->line(sprintf('    %-16s %s', 'Password:', '입력하신 비밀번호를 사용하세요 / use the password you entered'));
        }

        $this->line('    <comment>최초 로그인 후 비밀번호를 변경해야 합니다. / You must change the password after first sign-in.</comment>');
        $this->newLine();
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

    protected function resolveIdentifier(LoginProvider $provider): string
    {
        $identifier = $this->argument('identifier');

        if (is_string($identifier) && filled($identifier)) {
            return $identifier;
        }

        $default = $provider === LoginProvider::Email
            ? $this->defaultAdminEmail()
            : ($provider === LoginProvider::Phone ? '01012345678' : 'orbitadmin');

        if (! $this->input->isInteractive()) {
            return $default;
        }

        return (string) $this->ask(sprintf('What is the admin %s?', $provider->label()), $default);
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

    protected function resolveProvider(): LoginProvider
    {
        $provider = (string) $this->option('provider');

        return in_array($provider, [LoginProvider::Email->value, LoginProvider::Id->value, LoginProvider::Phone->value], true)
            ? LoginProvider::from($provider)
            : LoginProvider::Email;
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
                'name' => Role::DisplayNameSuperAdmin,
                'slug' => Role::SystemKeySuperAdmin,
                'system_key' => Role::SystemKeySuperAdmin,
                'is_deletable' => false,
                'permissions' => $permissions,
            ]);

            return $role;
        }

        $role->forceFill([
            'slug' => Role::SystemKeySuperAdmin,
            'system_key' => Role::SystemKeySuperAdmin,
            'is_deletable' => false,
            'permissions' => $permissions,
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
