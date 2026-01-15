<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('max_vacancies');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_locations');
    }
};
