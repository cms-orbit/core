<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Key-value JSON store backing the Config registry. Values are addressed by
     * a dot-notation key; the optional instance scope supports the XE3
     * chain-of-responsibility fallback (type → type.{instanceId}).
     */
    public function up(): void
    {
        Schema::create('orbit_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->index();
            $table->unsignedBigInteger('instance_id')->nullable()->index();
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['key', 'instance_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orbit_configs');
    }
};
