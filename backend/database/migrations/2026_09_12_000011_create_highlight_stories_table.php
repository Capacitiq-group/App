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
        Schema::create('highlight_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('highlight_id')->constrained('highlights')->cascadeOnDelete();
            $table->foreignId('story_id')->constrained('stories')->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->timestamp('added_at')->useCurrent();

            $table->unique(['highlight_id', 'story_id']);
            $table->index(['highlight_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('highlight_stories');
    }
};
