<?php

use App\Enums\Space\SpaceTypeEnum;
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
        Schema::table('spaces', function (Blueprint $table) {
            $table->string('type')->default(SpaceTypeEnum::PUBLIC->value)->after('discovery_scope');
            // Null = free Space. Only settable on Creator-type Spaces hosted
            // by a verified user — enforced in SpaceService::create(), not here.
            $table->unsignedInteger('ticket_price_cents')->nullable()->after('type');

            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spaces', function (Blueprint $table) {
            $table->dropColumn(['type', 'ticket_price_cents']);
        });
    }
};
