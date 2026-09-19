<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Distinct from space_participants.invited_by (which just records who
     * shared the link with a joiner, for public Spaces). This table is a
     * real allowlist: for a Private Space, join() only succeeds if a row
     * exists here for that user.
     */
    public function up(): void
    {
        Schema::create('space_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('invited_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['space_id', 'invited_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_invitations');
    }
};
