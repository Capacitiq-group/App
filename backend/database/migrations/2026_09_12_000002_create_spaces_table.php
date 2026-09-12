<?php

use App\Enums\Space\SpaceDiscoveryScopeEnum;
use App\Enums\Space\SpaceStatusEnum;
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
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('host_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('description', 280)->nullable();
            $table->foreignId('topic_id')->nullable()->constrained('topics')->nullOnDelete();

            $table->string('discovery_scope')->default(SpaceDiscoveryScopeEnum::PUBLIC->value);
            $table->string('status')->default(SpaceStatusEnum::WAITING->value);

            // V1 rules — stored per-row (copied from config at creation time) rather
            // than hardcoded, so a future config change doesn't retroactively affect
            // Spaces already created under the old limits.
            $table->unsignedTinyInteger('min_to_start')->default(5);
            $table->unsignedTinyInteger('min_to_continue')->default(2);
            $table->unsignedInteger('max_participants')->default(50);
            $table->unsignedTinyInteger('max_speakers')->default(8);
            $table->unsignedSmallInteger('duration_minutes')->default(30);

            // Agora channel — the token itself is minted on demand and never stored.
            $table->string('agora_channel_name')->unique();

            $table->timestamp('waiting_started_at')->useCurrent();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('ended_reason')->nullable();

            $table->timestamps();

            // Discovery feed: "live Spaces in this Topic" / "live Spaces near me".
            $table->index(['status', 'topic_id']);
            // Rate limiting: "3 Spaces per rolling 7 days" and "one active/scheduled
            // Space at a time" both query by host_user_id + a time/status filter.
            $table->index(['host_user_id', 'created_at']);
            $table->index(['host_user_id', 'status']);
            // Background job: auto-end Spaces whose timer has expired.
            $table->index(['status', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
