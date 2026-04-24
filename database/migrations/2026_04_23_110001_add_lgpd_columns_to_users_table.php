<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('anonymization_requested_at')->nullable()->after('deleted_at')->index();
            $table->timestamp('anonymized_at')->nullable()->after('anonymization_requested_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['anonymization_requested_at', 'anonymized_at']);
        });
    }
};
