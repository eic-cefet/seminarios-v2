<?php

use App\Services\IpHasher;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_consents', function (Blueprint $table) {
            $table->string('ip_hash', 64)->nullable()->after('version');
            $table->string('user_agent_hash', 64)->nullable()->after('ip_hash');
        });

        $hasher = app(IpHasher::class);

        DB::table('user_consents')
            ->whereNotNull('ip_address')
            ->orWhereNotNull('user_agent')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use ($hasher): void {
                foreach ($rows as $row) {
                    DB::table('user_consents')
                        ->where('id', $row->id)
                        ->update([
                            'ip_hash' => $hasher->hash($row->ip_address),
                            'user_agent_hash' => $hasher->hashOpaque($row->user_agent),
                        ]);
                }
            });

        Schema::table('user_consents', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent']);
        });
    }

    public function down(): void
    {
        Schema::table('user_consents', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
        });

        Schema::table('user_consents', function (Blueprint $table) {
            $table->dropColumn(['ip_hash', 'user_agent_hash']);
        });
    }
};
