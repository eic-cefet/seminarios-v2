<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_subject', function (Blueprint $table) {
            $table->foreignId('seminar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();

            $table->primary(['seminar_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_subject');
    }
};
