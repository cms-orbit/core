<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('locale', 12)->nullable()->after('email');
            $table->json('permissions')->nullable()->after('password');
            $table->boolean('must_change_password')->default(false)->after('permissions');
            $table->foreignUuid('avatar_id')
                ->nullable()
                ->after('must_change_password')
                ->constrained('attachments')
                ->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['avatar_id']);
            $table->dropColumn([
                'locale',
                'permissions',
                'must_change_password',
                'avatar_id',
            ]);
        });
    }
};
