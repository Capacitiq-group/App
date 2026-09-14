<?php

use App\Enums\Verification\VerificationPoliticalEntityTypeEnum;
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
        Schema::create('verification_political_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_application_id')->unique()->constrained('verification_applications')->cascadeOnDelete();

            $table->string('official_entity_name');
            $table->string('entity_type')->default(VerificationPoliticalEntityTypeEnum::POLITICAL_PARTY->value);
            $table->string('entity_type_other')->nullable();
            $table->string('registration_or_gazette_reference')->nullable();

            $table->string('representative_full_name');
            $table->string('representative_role_title');

            // No CIPC-equivalent registry exists for this category — per
            // policy, authority is confirmed manually through the entity's
            // own official channel, not any automated Didit path. This just
            // records that manual confirmation happened, and by what channel.
            $table->boolean('manually_confirmed_authority')->default(false);
            $table->string('manual_confirmation_channel')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_political_details');
    }
};
