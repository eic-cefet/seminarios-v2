<?php

use App\Models\SeminarType;
use App\Support\SeminarTypeBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminar_types', function (Blueprint $table) {
            $table->string('gender')->default('masculine')->after('name');
            $table->string('name_plural')->nullable()->after('gender');
        });

        SeminarType::query()->each(function (SeminarType $type) {
            SeminarTypeBackfill::apply($type);
        });
    }

    public function down(): void
    {
        Schema::table('seminar_types', function (Blueprint $table) {
            $table->dropColumn(['gender', 'name_plural']);
        });
    }
};
