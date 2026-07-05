<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_accounts')) {
            return;
        }

        Schema::create('user_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 40)->index();
            $table->string('identifier')->nullable();
            $table->string('normalized_identifier')->nullable();
            $table->string('provider_user_id')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->timestamp('verified_at')->nullable();
            $table->json('meta')->nullable();
            $table->longText('access_token')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unique(['provider', 'normalized_identifier'], 'user_accounts_provider_identifier_unique');
            $table->unique(['provider', 'provider_user_id'], 'user_accounts_provider_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_accounts');
    }
};
