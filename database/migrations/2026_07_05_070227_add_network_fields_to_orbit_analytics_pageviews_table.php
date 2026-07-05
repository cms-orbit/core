<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orbit_analytics_pageviews', function (Blueprint $table): void {
            $table->string('ip_address', 64)->nullable()->after('referrer_host');
            $table->string('country_code', 2)->nullable()->after('ip_address');

            $table->index(['instance_id', 'country_code', 'visited_on'], 'orbit_analytics_country_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('orbit_analytics_pageviews', function (Blueprint $table): void {
            $table->dropIndex('orbit_analytics_country_date_index');
            $table->dropColumn(['ip_address', 'country_code']);
        });
    }
};
