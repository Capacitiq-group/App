<?php

use App\Enums\Verification\VerificationBillingCycleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Verification is now a recurring subscription (R99/month or R999/year)
     * rather than a flat one-time fee. The Paystack preauth hold still
     * exists — it confirms a real, funded card before the identity check
     * runs — but the amount held/captured now depends on this column, and
     * approval also creates a Paystack Subscription against the matching
     * plan code so it renews automatically afterward.
     */
    public function up(): void
    {
        Schema::table('verification_applications', function (Blueprint $table) {
            $table->string('billing_cycle')->default(VerificationBillingCycleEnum::MONTHLY->value)->after('applicant_type');
            $table->string('paystack_subscription_code')->nullable()->after('paystack_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('verification_applications', function (Blueprint $table) {
            $table->dropColumn(['billing_cycle', 'paystack_subscription_code']);
        });
    }
};
