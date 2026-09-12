<?php

use App\Enums\Space\SpaceParticipantRoleEnum;
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
        Schema::create('space_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('role')->default(SpaceParticipantRoleEnum::LISTENER->value);

            // Agora requires a numeric per-channel UID for every publisher/subscriber.
            $table->unsignedInteger('agora_uid');

            // Set if this join came from a shared invite link, for a lightweight
            // invite trail without a separate invitations table.
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_muted')->default(false);

            // Closes out this session when the host removes the participant mid-Space.
            // Distinct from space_bans, which blocks rejoining altogether.
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['space_id', 'agora_uid']);
            // "Who's currently in the room" / active roster.
            $table->index(['space_id', 'left_at']);
            $table->index(['space_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_participants');
    }
};
