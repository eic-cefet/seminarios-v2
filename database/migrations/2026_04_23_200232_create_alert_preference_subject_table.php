<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_preference_subject', function (Blueprint $table) {
            $table->foreignId('alert_preference_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->primary(['alert_preference_id', 'subject_id']);
            $table->index('subject_id');
        });

        DB::table('alert_preferences')
            ->whereNotNull('subject_ids')
            ->orderBy('id')
            ->each(function (object $pref) {
                $ids = json_decode($pref->subject_ids, true);
                if (! is_array($ids) || $ids === []) {
                    return;
                }

                $rows = array_map(
                    fn ($subjectId) => [
                        'alert_preference_id' => $pref->id,
                        'subject_id' => (int) $subjectId,
                    ],
                    $ids,
                );

                DB::table('alert_preference_subject')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_preference_subject');
    }
};
