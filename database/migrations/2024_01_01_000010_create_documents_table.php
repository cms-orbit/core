<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Central documents table shared by every DocumentModel-based content type.
     *
     * - documentable_* : polymorphic link to the concrete child model row
     *                    (the engine join key).
     * - document_type  : stable type key (e.g. "board"), used for filtering and
     *                    type-scoped configuration.
     * - instance_id    : XE3-style deployed-instance identifier. A single type
     *                    can be deployed multiple times; documents belonging to
     *                    one deployment share an instance_id (nullable for
     *                    single-instance types).
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id('document_id');
            $table->uuid('document_uuid')->unique();

            $table->string('document_type')->index()->comment('content type key');
            $table->string('documentable_type');
            $table->unsignedBigInteger('documentable_id');
            $table->index(['documentable_type', 'documentable_id'], 'documents_documentable_index');

            $table->unsignedBigInteger('instance_id')->nullable()->index()
                ->comment('XE3 deployed instance id');

            $table->unsignedBigInteger('parent_id')->nullable()->index()->comment('parent document id');

            $table->nullableMorphs('author');
            $table->string('writer', 200)->nullable()->comment('writer display name');
            $table->string('email')->nullable()->comment('guest email');
            $table->string('certify_key', 200)->nullable()->comment('guest certify key');

            $table->string('thumbnail')->nullable()->comment('thumbnail path');

            $table->unsignedInteger('read_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('assent_count')->default(0);
            $table->unsignedInteger('dissent_count')->default(0);

            $table->boolean('is_notice')->index()->default(false);
            $table->boolean('is_secret')->index()->default(false);
            $table->tinyInteger('approved')->default(30)->comment('0:rejected/10:waiting/30:approved');

            $table->string('ipaddress', 45)->nullable();
            $table->timestamp('public_at')->nullable();
            $table->unsignedBigInteger('sort_order')->default(0)->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
