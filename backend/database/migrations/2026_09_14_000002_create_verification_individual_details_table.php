<?php

use App\Enums\Verification\VerificationIndividualTypeEnum;
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
        Schema::create('verification_individual_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_application_id')->unique()->constrained('verification_applications')->cascadeOnDelete();

            $table->string('full_legal_name');
            $table->string('verifying_as')->default(VerificationIndividualTypeEnum::ORDINARY_USER->value);
            $table->string('verifying_as_other')->nullable();
            $table->string('supporting_link')->nullable();
            $table->string('id_issuing_country', 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_individual_details');
    }
};
