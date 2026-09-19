<?php

use App\Enums\Tip\TipStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reuses TipStatusEnum for the status column (pending/completed/failed)
     * rather than defining an identical SpaceTicketStatusEnum — same
     * lifecycle shape, no reason to fork it.
     */
    public function up(): void
    {
        Schema::create('space_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('space_id')->constrained('spaces')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedInteger('amount_cents');
            $table->unsignedInteger('platform_fee_cents');
            $table->unsignedInteger('host_amount_cents');

            $table->string('paystack_reference')->unique();
            $table->string('status')->default(TipStatusEnum::PENDING->value);

            $table->timestamps();

            $table->unique(['space_id', 'buyer_id']); // one ticket per person per Space
            $table->index(['space_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_tickets');
    }
};
