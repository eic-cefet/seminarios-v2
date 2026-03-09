<?php

use App\Models\AuditLog;

describe('PruneAuditLogsCommand', function () {
    beforeEach(function () {
        // Clear any audit logs created by model factories in beforeEach
        AuditLog::query()->delete();
    });

    it('prunes logs older than default 90 days', function () {
        AuditLog::factory()->create(['created_at' => now()->subDays(100)]);
        AuditLog::factory()->create(['created_at' => now()->subDays(91)]);
        AuditLog::factory()->create(['created_at' => now()->subDays(10)]);

        // Clear user.created logs from factory
        AuditLog::where('event_name', 'user.created')->delete();

        $this->artisan('audit:prune')
            ->expectsOutputToContain('Pruned 2 audit log(s)')
            ->assertExitCode(0);

        expect(AuditLog::count())->toBe(1);
    });

    it('prunes logs older than custom days', function () {
        AuditLog::factory()->create(['created_at' => now()->subDays(40)]);
        AuditLog::factory()->create(['created_at' => now()->subDays(20)]);

        AuditLog::where('event_name', 'user.created')->delete();

        $this->artisan('audit:prune --days=30')
            ->expectsOutputToContain('Pruned 1 audit log(s)')
            ->assertExitCode(0);

        expect(AuditLog::count())->toBe(1);
    });

    it('does not prune recent logs', function () {
        AuditLog::factory()->create(['created_at' => now()]);
        AuditLog::factory()->create(['created_at' => now()->subDays(5)]);

        AuditLog::where('event_name', 'user.created')->delete();

        $this->artisan('audit:prune')
            ->expectsOutputToContain('Pruned 0 audit log(s)')
            ->assertExitCode(0);

        expect(AuditLog::count())->toBe(2);
    });
});
