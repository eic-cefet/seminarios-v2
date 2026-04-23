<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_preference_seminar_type', function (Blueprint $table) {
            $table->foreignId('alert_preference_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seminar_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['alert_preference_id', 'seminar_type_id']);
            $table->index('seminar_type_id');
        });

        // Backfill from existing JSON column.
        DB::table('alert_preferences')
            ->whereNotNull('seminar_type_ids')
            ->orderBy('id')
            ->each(function (object $pref) {
                $ids = json_decode($pref->seminar_type_ids, true);
                if (! is_array($ids) || $ids === []) {
                    return;
                }

                $rows = array_map(
                    fn ($typeId) => [
                        'alert_preference_id' => $pref->id,
                        'seminar_type_id' => (int) $typeId,
                    ],
                    $ids,
                );

                DB::table('alert_preference_seminar_type')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_preference_seminar_type');
    }
};
