<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alert_preferences', function (Blueprint $table) {
            $table->boolean('certificate_ready')->default(true)->after('announcements');
            $table->boolean('seminar_rescheduled')->default(true)->after('certificate_ready');
        });

        DB::statement('
            UPDATE alert_preferences
               SET certificate_ready = 1,
                   seminar_rescheduled = 1
        ');
    }

    public function down(): void
    {
        Schema::table('alert_preferences', function (Blueprint $table) {
            $table->dropColumn(['certificate_ready', 'seminar_rescheduled']);
        });
    }
};
