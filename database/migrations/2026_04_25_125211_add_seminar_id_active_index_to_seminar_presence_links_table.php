<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_presence_links', function (Blueprint $table): void {
            $table->index(['seminar_id', 'active'], 'seminar_presence_links_seminar_id_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('seminar_presence_links', function (Blueprint $table): void {
            $table->dropIndex('seminar_presence_links_seminar_id_active_index');
        });
    }
};
