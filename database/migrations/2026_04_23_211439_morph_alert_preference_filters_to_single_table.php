<?php

use App\Models\SeminarType;
use App\Models\Subject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_preference_new_seminar_alert_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_preference_id')
                ->constrained()
                ->cascadeOnDelete()
                ->name('apnsas_apid_fk');
            $table->morphs('settable', 'apnsas_settable_index');
            $table->unique(['alert_preference_id', 'settable_type', 'settable_id'], 'apnsas_unique');
        });

        DB::table('alert_preference_seminar_type')->orderBy('alert_preference_id')->each(function (object $row) {
            DB::table('alert_preference_new_seminar_alert_settings')->insert([
                'alert_preference_id' => $row->alert_preference_id,
                'settable_type' => SeminarType::class,
                'settable_id' => $row->seminar_type_id,
            ]);
        });

        DB::table('alert_preference_subject')->orderBy('alert_preference_id')->each(function (object $row) {
            DB::table('alert_preference_new_seminar_alert_settings')->insert([
                'alert_preference_id' => $row->alert_preference_id,
                'settable_type' => Subject::class,
                'settable_id' => $row->subject_id,
            ]);
        });

        Schema::dropIfExists('alert_preference_seminar_type');
        Schema::dropIfExists('alert_preference_subject');
    }

    public function down(): void
    {
        Schema::create('alert_preference_seminar_type', function (Blueprint $table) {
            $table->foreignId('alert_preference_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seminar_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['alert_preference_id', 'seminar_type_id']);
        });

        Schema::create('alert_preference_subject', function (Blueprint $table) {
            $table->foreignId('alert_preference_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->primary(['alert_preference_id', 'subject_id']);
        });

        DB::table('alert_preference_new_seminar_alert_settings')
            ->where('settable_type', SeminarType::class)
            ->orderBy('id')
            ->each(function (object $row) {
                DB::table('alert_preference_seminar_type')->insert([
                    'alert_preference_id' => $row->alert_preference_id,
                    'seminar_type_id' => $row->settable_id,
                ]);
            });

        DB::table('alert_preference_new_seminar_alert_settings')
            ->where('settable_type', Subject::class)
            ->orderBy('id')
            ->each(function (object $row) {
                DB::table('alert_preference_subject')->insert([
                    'alert_preference_id' => $row->alert_preference_id,
                    'subject_id' => $row->settable_id,
                ]);
            });

        Schema::dropIfExists('alert_preference_new_seminar_alert_settings');
    }
};
