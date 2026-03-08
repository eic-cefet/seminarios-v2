<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->string('sentiment', 500)->nullable()->after('comment');
            $table->timestamp('sentiment_analyzed_at')->nullable()->after('sentiment');
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropColumn(['sentiment', 'sentiment_analyzed_at']);
        });
    }
};
