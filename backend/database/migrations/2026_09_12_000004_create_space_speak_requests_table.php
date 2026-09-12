<?php

use App\Enums\Space\SpaceSpeakRequestStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('space_speak_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('status')->default(SpaceSpeakRequestStatusEnum::PENDING->value);

            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Host's ordered "hands up" queue for a given Space.
            $table->index(['space_id', 'status']);
        });

        // A user can only have one pending speak request per Space at a time.
        // Postgres partial unique index — Laravel's schema builder has no native
        // "unique where" helper, so this is raw SQL.
        DB::statement(
            'CREATE UNIQUE INDEX space_speak_requests_one_pending_per_user
             ON space_speak_requests (space_id, user_id)
             WHERE status = \'pending\''
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS space_speak_requests_one_pending_per_user');
        Schema::dropIfExists('space_speak_requests');
    }
};
