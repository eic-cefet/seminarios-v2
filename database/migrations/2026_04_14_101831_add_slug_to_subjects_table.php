<?php

use App\Models\Subject;
use App\Services\SlugService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('subjects', 'slug')) {
            return;
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        $slugService = app(SlugService::class);

        Subject::withTrashed()->chunkById(200, function ($subjects) use ($slugService) {
            foreach ($subjects as $subject) {
                $subject->update([
                    'slug' => $slugService->generateUnique($subject->name, Subject::class),
                ]);
            }
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
