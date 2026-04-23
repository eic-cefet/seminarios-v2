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
            $table->boolean('seminar_reminder_7d')->default(true)->after('opted_in');
            $table->boolean('seminar_reminder_24h')->default(true)->after('seminar_reminder_7d');
            $table->boolean('evaluation_prompt')->default(true)->after('seminar_reminder_24h');
            $table->boolean('announcements')->default(true)->after('evaluation_prompt');
        });

        DB::statement('
            UPDATE alert_preferences
               SET seminar_reminder_7d = 1,
                   seminar_reminder_24h = 1,
                   evaluation_prompt = 1,
                   announcements = 1
        ');
    }

    public function down(): void
    {
        Schema::table('alert_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'seminar_reminder_7d',
                'seminar_reminder_24h',
                'evaluation_prompt',
                'announcements',
            ]);
        });
    }
};
