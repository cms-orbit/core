<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orbit_analytics_pageviews', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('instance_id')->nullable()->index();
            $table->string('user_id', 191)->nullable()->index();
            $table->string('user_type', 191)->nullable();
            $table->string('user_name', 191)->nullable();
            $table->string('user_email', 191)->nullable()->index();
            $table->string('visitor_hash', 64)->index();
            $table->string('visit_token', 64)->index();
            $table->boolean('is_entrance')->default(false)->index();
            $table->string('route_name', 191)->nullable()->index();
            $table->string('route_uri', 191)->nullable()->index();
            $table->string('page_path', 191)->index();
            $table->string('referrer_host', 191)->nullable()->index();
            $table->string('ip_address', 64)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('browser_family', 64)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 32)->nullable()->index();
            $table->boolean('is_bot')->default(false)->index();
            $table->date('visited_on')->index();
            $table->timestamps();

            $table->index(['instance_id', 'visited_on'], 'orbit_analytics_instance_date_index');
            $table->index(['instance_id', 'route_uri', 'visited_on'], 'orbit_analytics_route_date_index');
            $table->index(['instance_id', 'referrer_host', 'visited_on'], 'orbit_analytics_referrer_date_index');
            $table->index(['instance_id', 'country_code', 'visited_on'], 'orbit_analytics_country_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orbit_analytics_pageviews');
    }
};
