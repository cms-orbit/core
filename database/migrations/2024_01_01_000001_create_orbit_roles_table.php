<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                $table->json('permissions')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('role_users')) {
            Schema::create('role_users', function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id');
                $table->uuid('role_id');
                $table->primary(['user_id', 'role_id']);
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreign('role_id')
                    ->references('id')->on('roles')
                    ->cascadeOnUpdate()->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_users');
        Schema::dropIfExists('roles');
    }
};
