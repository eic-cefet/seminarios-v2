<?php

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
        Schema::create('user_badges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('badge_key', 64);
            $table->timestamp('earned_at');
            $table->timestamps();
            $table->unique(['user_id', 'badge_key']);
            $table->index(['user_id', 'earned_at']);
        });

        Schema::create('user_experience_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 32);
            $table->string('source_key', 128);
            $table->unsignedInteger('points');
            $table->timestamps();
            $table->unique(['user_id', 'reason', 'source_key'], 'user_xp_source_unique');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_experience_events');
        Schema::dropIfExists('user_badges');
    }
};
