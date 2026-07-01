<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attachments')) {
            Schema::create('attachments', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('original_name');
                $table->string('mime');
                $table->string('extension')->nullable();
                $table->bigInteger('size')->default(0);
                $table->integer('sort')->default(0);
                $table->string('path');
                $table->string('hash')->nullable();
                $table->string('disk')->default('public');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('group')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('attachmentable')) {
            Schema::create('attachmentable', function (Blueprint $table): void {
                $table->string('attachmentable_type');
                $table->string('attachmentable_id');
                $table->index(['attachmentable_type', 'attachmentable_id']);
                $table->foreignUuid('attachment_id')
                    ->references('id')->on('attachments')
                    ->cascadeOnUpdate()->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attachmentable');
        Schema::dropIfExists('attachments');
    }
};
