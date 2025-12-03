<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->uuid('document_uuid')->unique();
            
            // Polymorphic relation to instance (actual model)
            $table->string('instance_type');
            $table->unsignedBigInteger('instance_id');
            $table->index(['instance_type', 'instance_id']);
            
            // Polymorphic relation to author
            $table->nullableMorphs('author');
            
            // Hierarchy
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('document_id')->on('documents')->onDelete('cascade');
            
            // Media
            $table->string('thumbnail')->nullable();
            
            // Guest author info
            $table->string('writer')->nullable();
            $table->string('email')->nullable();
            $table->string('certify_key')->nullable();
            
            // Counters
            $table->unsignedInteger('read_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->unsignedInteger('assent_count')->default(0);
            $table->unsignedInteger('dissent_count')->default(0);
            
            // Flags
            $table->boolean('is_notice')->default(false);
            $table->boolean('is_secret')->default(false);
            $table->tinyInteger('approved')->default(30); // 0:rejected, 10:waiting, 30:approved
            
            // Meta
            $table->string('ipaddress', 45)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('is_notice');
            $table->index('approved');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

