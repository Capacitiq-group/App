<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: this is a minimal stub so `spaces.topic_id` has something to
     * reference. The full platform-curated Topics system (categories like
     * Business, Sport, Music, etc., surfaced across feeds/discovery) is a
     * separate, larger feature still to be designed — this table only
     * exists to unblock Space tagging in the meantime.
     */
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
