<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('rolls back the user extensions without removing host columns', function (): void {
    $this->artisan('migrate', ['--no-interaction' => true])->assertSuccessful();
    expect(Schema::hasColumns('users', ['locale', 'permissions', 'must_change_password', 'avatar_id']))->toBeTrue();
    $migration = require __DIR__.'/../../database/migrations/2024_01_01_000030_alter_users_table.php';
    $migration->down();
    expect(Schema::hasColumn('users', 'permissions'))->toBeFalse()
        ->and(Schema::hasColumns('users', ['id', 'name', 'email', 'password']))->toBeTrue();
    $migration->up();
    expect(Schema::hasColumns('users', ['locale', 'permissions', 'must_change_password', 'avatar_id']))->toBeTrue();
});
