<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orbit_activities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('instance_id')->nullable()->index();
            $table->string('category', 64)->index();
            $table->string('event', 100)->index();
            $table->string('description', 191)->nullable();

            $table->string('subject_type', 191)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->string('subject_label', 191)->nullable();

            $table->string('causer_type', 191)->nullable();
            $table->string('causer_id', 64)->nullable();
            $table->string('causer_label', 191)->nullable();

            $table->string('auth_identifier', 191)->nullable()->index();
            $table->string('ip_address', 64)->nullable();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->string('browser_family', 64)->nullable()->index();
            $table->string('device_type', 32)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id'], 'orbit_activities_subject_index');
            $table->index(['causer_type', 'causer_id'], 'orbit_activities_causer_index');
            $table->index(['instance_id', 'created_at'], 'orbit_activities_instance_created_index');
            $table->index(['category', 'created_at'], 'orbit_activities_category_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orbit_activities');
    }
};
