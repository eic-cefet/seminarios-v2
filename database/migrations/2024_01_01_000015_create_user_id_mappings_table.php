<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_id_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_table'); // 'users', 'speakers', 'students'
            $table->unsignedBigInteger('legacy_id');
            $table->foreignId('new_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role'); // 'admin', 'teacher', 'student'
            $table->timestamp('created_at');

            $table->unique(['legacy_table', 'legacy_id']);
            $table->index('new_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_id_mappings');
    }
};
