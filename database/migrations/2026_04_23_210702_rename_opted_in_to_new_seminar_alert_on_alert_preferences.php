<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alert_preferences', function (Blueprint $table) {
            $table->renameColumn('opted_in', 'new_seminar_alert');
        });
    }

    public function down(): void
    {
        Schema::table('alert_preferences', function (Blueprint $table) {
            $table->renameColumn('new_seminar_alert', 'opted_in');
        });
    }
};
