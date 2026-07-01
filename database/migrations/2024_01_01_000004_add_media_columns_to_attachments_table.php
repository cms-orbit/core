<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend the ported Attachment table with media-library metadata used by
     * the image/video/audio processing pipeline.
     */
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            if (! Schema::hasColumn('attachments', 'kind')) {
                $table->string('kind')->nullable()->index()->after('mime')
                    ->comment('image|video|audio|file');
            }
            if (! Schema::hasColumn('attachments', 'width')) {
                $table->unsignedInteger('width')->nullable()->after('size');
            }
            if (! Schema::hasColumn('attachments', 'height')) {
                $table->unsignedInteger('height')->nullable()->after('width');
            }
            if (! Schema::hasColumn('attachments', 'duration')) {
                $table->unsignedInteger('duration')->nullable()->after('height')
                    ->comment('media duration in seconds');
            }
            if (! Schema::hasColumn('attachments', 'alt')) {
                $table->string('alt')->nullable()->after('original_name');
            }
            if (! Schema::hasColumn('attachments', 'description')) {
                $table->text('description')->nullable()->after('alt');
            }
            if (! Schema::hasColumn('attachments', 'encoding_status')) {
                $table->string('encoding_status')->nullable()->index()->after('duration')
                    ->comment('pending|processing|done|skipped|failed');
            }
            if (! Schema::hasColumn('attachments', 'meta')) {
                $table->json('meta')->nullable()->after('encoding_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->dropColumn([
                'kind', 'width', 'height', 'duration', 'alt',
                'description', 'encoding_status', 'meta',
            ]);
        });
    }
};
