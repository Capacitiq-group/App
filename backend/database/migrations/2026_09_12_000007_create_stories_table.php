<?php

use App\Enums\Post\AudienceTypeEnum;
use App\Enums\Story\StoryTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * expires_at is nullable by design: non-null = normal ephemeral story
     * (24h TTL, enforced at query time, not by deleting the row); null =
     * permanent — set automatically the moment a story is added to a
     * Highlight, so the underlying row and its media survive past 24h
     * without needing a separate "is highlighted" flag to check on every
     * feed query.
     */
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('type')->default(StoryTypeEnum::IMAGE->value);
            $table->string('audience')->default(AudienceTypeEnum::PUBLIC->value);

            // TEXT stories only.
            $table->text('text_content')->nullable();
            $table->string('background_color', 20)->nullable();

            // SHARED_POST stories only. nullOnDelete rather than cascade —
            // if the original post is deleted, the story stays (frontend
            // shows a "post no longer available" state) instead of vanishing.
            $table->foreignId('shared_post_id')->nullable()->constrained('posts')->nullOnDelete();

            // How long each slide/frame of the story displays, in seconds.
            // Applies uniformly across a carousel's slides for V1 — a video
            // slide plays its natural length regardless of this value.
            $table->unsignedSmallInteger('duration_seconds')->default(5);

            $table->unsignedInteger('view_count')->default(0);

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // "My stories tray" / "stories from people I follow" queries.
            $table->index(['user_id', 'expires_at']);
            // Background expiry sweep.
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
