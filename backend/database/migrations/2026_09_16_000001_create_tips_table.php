<?php

use App\Enums\Tip\TipSourceTypeEnum;
use App\Enums\Tip\TipStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The 90/10 split is computed and stored at creation time (platform_fee_cents
     * + recipient_amount_cents), not recomputed later from amount_cents and the
     * *current* fee percentage — so changing TIP_PLATFORM_FEE_PERCENTAGE in
     * config never silently reinterprets historical tips.
     */
    public function up(): void
    {
        Schema::create('tips', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tipper_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();

            $table->string('source_type')->default(TipSourceTypeEnum::PROFILE->value);
            // Nullable: a profile tip has no post/space to point at.
            // Not a foreign key — deliberately loose, since it can point at
            // either posts.id or spaces.id depending on source_type, and a
            // deleted post/Space shouldn't cascade-delete the payment record.
            $table->unsignedBigInteger('source_id')->nullable();

            $table->unsignedInteger('amount_cents');
            $table->unsignedInteger('platform_fee_cents');
            $table->unsignedInteger('recipient_amount_cents');

            $table->string('paystack_reference')->unique();
            $table->string('status')->default(TipStatusEnum::PENDING->value);

            $table->string('message', 280)->nullable();

            $table->timestamps();

            $table->index(['recipient_id', 'status']);
            $table->index(['tipper_id', 'created_at']);
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tips');
    }
};
