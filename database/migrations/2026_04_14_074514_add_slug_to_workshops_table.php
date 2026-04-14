<?php

use App\Models\Workshop;
use App\Services\SlugService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        $slugService = app(SlugService::class);
        Workshop::all()->each(function (Workshop $workshop) use ($slugService) {
            $workshop->slug = $slugService->generateUnique($workshop->name, Workshop::class);
            $workshop->saveQuietly();
        });

        Schema::table('workshops', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
