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
        Schema::table('seminar_speaker', function (Blueprint $table) {
            $table->timestamp('reminder_24h_sent_at')->nullable();
            $table->timestamp('recap_sent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seminar_speaker', function (Blueprint $table) {
            $table->dropColumn(['reminder_24h_sent_at', 'recap_sent_at']);
        });
    }
};
