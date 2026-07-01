<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counter_logs', function (Blueprint $table): void {
            $table->id();

            $table->string('countable_type', 125);
            $table->unsignedBigInteger('countable_id');

            $table->string('causer_type', 125)->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();

            $table->string('action', 20)->comment('view, assent, dissent, etc.');
            $table->ipAddress('ip_address')->nullable();

            $table->timestamps();

            $table->index(
                ['countable_id', 'countable_type', 'action', 'causer_id', 'causer_type'],
                'counter_logs_countable_causer_action_index'
            );
            $table->index(['causer_id', 'causer_type', 'action'], 'counter_logs_causer_action_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counter_logs');
    }
};
