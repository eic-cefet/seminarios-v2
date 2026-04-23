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
            $table->dropColumn(['seminar_type_ids', 'subject_ids']);
        });
    }

    public function down(): void
    {
        Schema::table('alert_preferences', function (Blueprint $table) {
            $table->json('seminar_type_ids')->nullable();
            $table->json('subject_ids')->nullable();
        });

        // Restore JSON data from pivot tables (best-effort reversibility).
        DB::table('alert_preferences')->orderBy('id')->each(function (object $pref) {
            $typeIds = DB::table('alert_preference_seminar_type')
                ->where('alert_preference_id', $pref->id)
                ->pluck('seminar_type_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $subjectIds = DB::table('alert_preference_subject')
                ->where('alert_preference_id', $pref->id)
                ->pluck('subject_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            DB::table('alert_preferences')->where('id', $pref->id)->update([
                'seminar_type_ids' => $typeIds === [] ? null : json_encode($typeIds),
                'subject_ids' => $subjectIds === [] ? null : json_encode($subjectIds),
            ]);
        });
    }
};
