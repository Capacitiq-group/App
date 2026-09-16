<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This is a minimal ledger scoped to "tips create wallet credit" — the
     * fuller Wallet system from the platform report (pending/validated/
     * payable/paid lifecycle, payout requests, bank details) is a separate,
     * larger feature. What's here is enough for tips to have somewhere real
     * to land, with an auditable trail, without building payouts yet.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Signed — positive for a credit (tip received), negative for a
            // future debit (payout, once that exists).
            $table->integer('amount_cents');
            // Running balance snapshot right after this transaction, so the
            // ledger is independently verifiable without replaying every row.
            $table->integer('balance_after_cents');

            $table->string('type'); // e.g. 'tip_received' — kept as a plain string, not an enum table, since this ledger will grow new transaction types as payout/other features are added
            $table->string('reference_type')->nullable(); // e.g. 'tip'
            $table->unsignedBigInteger('reference_id')->nullable(); // e.g. tips.id

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
