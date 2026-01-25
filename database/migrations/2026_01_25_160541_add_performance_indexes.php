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
        Schema::table('seminars', function (Blueprint $table) {
            $table->index('scheduled_at');
            $table->index('active');
            $table->index('created_by');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->index('present');
            $table->index('created_at');
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seminars', function (Blueprint $table) {
            $table->dropIndex(['scheduled_at']);
            $table->dropIndex(['active']);
            $table->dropIndex(['created_by']);
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['present']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
