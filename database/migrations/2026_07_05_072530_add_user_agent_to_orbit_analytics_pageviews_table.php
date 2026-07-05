<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orbit_analytics_pageviews', function (Blueprint $table): void {
            $table->text('user_agent')->nullable()->after('browser_family');
        });
    }

    public function down(): void
    {
        Schema::table('orbit_analytics_pageviews', function (Blueprint $table): void {
            $table->dropColumn('user_agent');
        });
    }
};
