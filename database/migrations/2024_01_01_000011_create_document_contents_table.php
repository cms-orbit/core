<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-locale content rows for documents. The DocumentModel engine joins the
     * preferred locale row (current → fallback → first) via a window function.
     */
    public function up(): void
    {
        Schema::create('document_contents', function (Blueprint $table): void {
            $table->id('content_id');
            $table->foreignId('document_id')
                ->constrained('documents', 'document_id')
                ->cascadeOnDelete();

            $table->string('locale', 10)->default(app()->getLocale())->index();
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->string('description')->nullable();

            $table->tinyInteger('format')->default(10)->comment('0:none/10:HTML/20:Markdown/30:Textile');
            $table->text('pure_content')->nullable()->comment('plain text for search');
            $table->longText('content')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_contents');
    }
};
