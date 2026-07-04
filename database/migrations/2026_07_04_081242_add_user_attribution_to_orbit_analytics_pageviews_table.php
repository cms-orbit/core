<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orbit_analytics_pageviews', function (Blueprint $table): void {
            $table->string('user_id', 191)->nullable()->after('instance_id')->index();
            $table->string('user_type', 191)->nullable()->after('user_id');
            $table->string('user_name', 191)->nullable()->after('user_type');
            $table->string('user_email', 191)->nullable()->after('user_name')->index();
        });
    }

    public function down(): void
    {
        Schema::table('orbit_analytics_pageviews', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['user_email']);
            $table->dropColumn([
                'user_id',
                'user_type',
                'user_name',
                'user_email',
            ]);
        });
    }
};
