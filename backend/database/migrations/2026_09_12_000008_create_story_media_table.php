<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Deliberately a separate table from `medias` (which is tightly coupled
     * to post_id) rather than adding a nullable story_id there — avoids
     * turning a table every other post-related feature already queries
     * assuming post_id is set into a dual-purpose one.
     */
    public function up(): void
    {
        Schema::create('story_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->smallInteger('type'); // App\Enums\Media\MediaTypeEnum — reused from posts
            $table->integer('order')->default(0);
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignId('upload_file_id')->constrained('upload_files')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['story_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_media');
    }
};
