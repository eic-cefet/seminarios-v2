<?php

use App\Enums\AuditEventType;
use App\Models\AuditLog;
use Illuminate\Support\Carbon;

beforeEach(function () {
    AuditLog::query()->delete();
});

it('keeps security events for the security retention but prunes default events at 90 days', function () {
    config([
        'audit.retention.default' => 90,
        'audit.retention.security' => 365,
        'audit.retention.system' => 30,
        'audit.tiers.security' => ['user.login_failed'],
        'audit.tiers.system' => ['email.sent'],
    ]);

    AuditLog::factory()->create([
        'event_name' => 'user.login_failed',
        'event_type' => AuditEventType::Manual,
        'created_at' => Carbon::now()->subDays(120), // would be pruned at default 90
    ]);

    AuditLog::factory()->create([
        'event_name' => 'email.sent',
        'event_type' => AuditEventType::System,
        'created_at' => Carbon::now()->subDays(60), // older than system 30, prune
    ]);

    AuditLog::factory()->create([
        'event_name' => 'subject.updated',
        'event_type' => AuditEventType::System,
        'created_at' => Carbon::now()->subDays(120), // older than default 90, prune
    ]);

    $this->artisan('audit:prune')->assertSuccessful();

    expect(AuditLog::where('event_name', 'user.login_failed')->exists())->toBeTrue();
    expect(AuditLog::where('event_name', 'email.sent')->exists())->toBeFalse();
    expect(AuditLog::where('event_name', 'subject.updated')->exists())->toBeFalse();
});

it('skips empty tier lists gracefully', function () {
    config([
        'audit.retention.default' => 90,
        'audit.retention.security' => 365,
        'audit.retention.system' => 30,
        'audit.tiers.security' => [], // empty — should be skipped
        'audit.tiers.system' => ['email.sent'],
    ]);

    AuditLog::factory()->create([
        'event_name' => 'email.sent',
        'event_type' => AuditEventType::System,
        'created_at' => Carbon::now()->subDays(60),
    ]);

    $this->artisan('audit:prune')->assertSuccessful();

    expect(AuditLog::where('event_name', 'email.sent')->exists())->toBeFalse();
});

it('still respects an explicit --days override across all tiers', function () {
    AuditLog::factory()->create([
        'event_name' => 'user.login_failed',
        'event_type' => AuditEventType::Manual,
        'created_at' => Carbon::now()->subDays(10),
    ]);

    $this->artisan('audit:prune', ['--days' => 5])->assertSuccessful();

    expect(AuditLog::where('event_name', 'user.login_failed')->exists())->toBeFalse();
});
