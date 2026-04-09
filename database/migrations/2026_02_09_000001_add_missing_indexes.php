<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registrations: user_id alone needs its own index
        // (composite unique on seminar_id,user_id doesn't help user_id-only queries)
        Schema::table('registrations', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('certificate_code');
        });

        // Soft delete columns need indexes for WHERE deleted_at IS NULL filtering
        Schema::table('users', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('seminars', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        // User student data: used in report filtering
        Schema::table('user_student_data', function (Blueprint $table) {
            $table->index('course_situation');
        });

        // Pivot tables: reverse-direction lookups need individual indexes
        // Primary key is (seminar_id, subject_id) so subject_id-only queries are slow
        Schema::table('seminar_subject', function (Blueprint $table) {
            $table->index('subject_id');
        });

        // Primary key is (seminar_id, user_id) so user_id-only queries are slow
        Schema::table('seminar_speaker', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['certificate_code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('seminars', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('user_student_data', function (Blueprint $table) {
            $table->dropIndex(['course_situation']);
        });

        Schema::table('seminar_subject', function (Blueprint $table) {
            $table->dropIndex(['subject_id']);
        });

        Schema::table('seminar_speaker', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
