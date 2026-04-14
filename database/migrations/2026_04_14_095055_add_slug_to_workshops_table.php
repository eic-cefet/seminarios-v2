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
        if (Schema::hasColumn('workshops', 'slug')) {
            return;
        }

        Schema::table('workshops', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        $slugService = app(SlugService::class);

        Workshop::query()->chunkById(200, function ($workshops) use ($slugService) {
            foreach ($workshops as $workshop) {
                $workshop->update([
                    'slug' => $slugService->generateUnique($workshop->name, Workshop::class),
                ]);
            }
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
