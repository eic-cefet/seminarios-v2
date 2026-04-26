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
        $hasher = app(IpHasher::class);
        // Fail before any schema change if the salt is not configured —
        // otherwise we'd add ip_hash, throw mid-backfill, and leave the
        // table in a half-migrated state with both columns present.
        $hasher->salt();

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('ip_hash', 64)->nullable()->after('origin');
        });

        DB::table('audit_logs')
            ->whereNotNull('ip_address')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use ($hasher): void {
                foreach ($rows as $row) {
                    DB::table('audit_logs')
                        ->where('id', $row->id)
                        ->update(['ip_hash' => $hasher->hash($row->ip_address)]);
                }
            });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('origin');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('ip_hash');
        });
    }
};
