<?php

use App\Enums\Verification\VerificationBusinessRoleEnum;
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
        Schema::create('verification_business_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_application_id')->unique()->constrained('verification_applications')->cascadeOnDelete();

            $table->string('legal_entity_name');
            $table->boolean('is_cipc_registered')->default(true);
            $table->string('registration_number')->nullable();
            $table->string('registration_country', 2)->nullable();

            $table->string('representative_full_name');
            $table->string('representative_role')->default(VerificationBusinessRoleEnum::DIRECTOR_OWNER->value);
            $table->string('representative_role_other')->nullable();

            // Sole trader only — optional, strengthens the claim, not required to pass.
            $table->string('sole_trader_trading_name')->nullable();
            $table->string('sole_trader_tax_vat_number')->nullable();
            $table->string('sole_trader_address_or_bank')->nullable();

            // Set from the Didit KYB webhook payload — see VerificationService.
            // Synkra's own access-control rule: only grant business-account
            // control when the applicant's personal check matched a real
            // CIPC-listed officer/director/UBO, not just whoever started the
            // session. Sole traders have no registry to match against, so
            // this stays null for them (their own personal KYC is sufficient).
            $table->boolean('matched_cipc_officer')->nullable();
            $table->string('matched_officer_name')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_business_details');
    }
};
