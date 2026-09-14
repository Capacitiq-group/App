<?php

use App\Enums\Verification\VerificationApplicantTypeEnum;
use App\Enums\Verification\VerificationStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per application *attempt* — a rejected application can be
     * resubmitted, which creates a new row rather than reusing the old one,
     * so history of prior attempts is preserved. `users.verify` (existing
     * column) is the simple "badge showing or not" flag the profile reads;
     * this table is the full workflow behind it.
     */
    public function up(): void
    {
        Schema::create('verification_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('applicant_type')->default(VerificationApplicantTypeEnum::INDIVIDUAL->value);
            $table->string('status')->default(VerificationStatusEnum::IN_PROGRESS->value);

            // Didit
            $table->string('didit_session_id')->nullable();
            $table->string('didit_workflow_id')->nullable();

            // Paystack preauthorization hold
            $table->string('paystack_reference')->nullable();
            $table->string('hold_status')->nullable();
            $table->timestamp('hold_expires_at')->nullable();

            // Decision
            $table->boolean('decided_manually')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_reason')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->timestamp('submitted_at')->useCurrent();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('paystack_reference');
            $table->index('didit_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_applications');
    }
};
