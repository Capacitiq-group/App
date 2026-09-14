<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `verify` (existing column) already answers "is the badge showing".
     * These two add "what does the badge say" and "how strong is the claim
     * behind it" — the latter directly answers the open question in the
     * platform rules doc about whether a CIPC-registered company and a sole
     * trader should show identically. Both null until the first successful
     * verification.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('verify_badge_label')->nullable()->after('verify');
            // 'registry' = backed by an official registry match (CIPC KYB);
            // 'identity_only' = backed by one person's identity check alone
            // (sole trader, or any individual verification).
            $table->string('verify_depth')->nullable()->after('verify_badge_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['verify_badge_label', 'verify_depth']);
        });
    }
};
