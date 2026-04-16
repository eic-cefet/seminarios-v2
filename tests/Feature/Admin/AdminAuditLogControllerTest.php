<?php

use App\Jobs\GenerateAuditLogReportJob;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Queue;

describe('GET /api/admin/audit-logs/summary', function () {
    it('returns summary stats for the period', function () {
        $admin = actingAsAdmin();
        AuditLog::query()->delete();

        AuditLog::factory()->manual()->count(2)->create(['user_id' => $admin->id]);
        AuditLog::factory()->count(3)->create(['user_id' => $admin->id]);

        $response = $this->getJson('/api/admin/audit-logs/summary');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => ['total', 'manual_count', 'system_count', 'top_events'],
            ]);

        expect($response->json('data.total'))->toBe(5);
        expect($response->json('data.manual_count'))->toBe(2);
        expect($response->json('data.system_count'))->toBe(3);
    });

    it('respects the days filter', function () {
        $admin = actingAsAdmin();
        AuditLog::query()->delete();

        AuditLog::factory()->create(['user_id' => $admin->id, 'created_at' => now()->subDays(5)]);
        AuditLog::factory()->create(['user_id' => $admin->id, 'created_at' => now()->subDays(20)]);

        $response = $this->getJson('/api/admin/audit-logs/summary?days=7');

        $response->assertSuccessful();
        expect($response->json('data.total'))->toBe(1);
    });

    it('defaults to 30 days', function () {
        $admin = actingAsAdmin();
        AuditLog::query()->delete();

        AuditLog::factory()->create(['user_id' => $admin->id, 'created_at' => now()->subDays(25)]);
        AuditLog::factory()->create(['user_id' => $admin->id, 'created_at' => now()->subDays(35)]);

        $response = $this->getJson('/api/admin/audit-logs/summary');

        $response->assertSuccessful();
        expect($response->json('data.total'))->toBe(1);
    });

    it('returns top events', function () {
        $admin = actingAsAdmin();
        AuditLog::query()->delete();

        AuditLog::factory()->count(5)->create(['user_id' => $admin->id, 'event_name' => 'user.login']);
        AuditLog::factory()->count(3)->create(['user_id' => $admin->id, 'event_name' => 'user.logout']);

        $response = $this->getJson('/api/admin/audit-logs/summary');

        $response->assertSuccessful();
        expect($response->json('data.top_events'))->toHaveKey('user.login');
    });

    it('validates days parameter accepts only allowed values', function () {
        actingAsAdmin();

        $this->getJson('/api/admin/audit-logs/summary?days=15')->assertStatus(422);
        $this->getJson('/api/admin/audit-logs/summary?days=7')->assertSuccessful();
        $this->getJson('/api/admin/audit-logs/summary?days=365')->assertSuccessful();
    });

    it('returns 403 for teacher users', function () {
        actingAsTeacher();

        $this->getJson('/api/admin/audit-logs/summary')->assertForbidden();
    });

    it('returns 403 for regular users', function () {
        actingAsUser();

        $this->getJson('/api/admin/audit-logs/summary')->assertForbidden();
    });

    it('returns 401 for unauthenticated users', function () {
        $this->getJson('/api/admin/audit-logs/summary')->assertUnauthorized();
    });
});

describe('GET /api/admin/audit-logs/export', function () {
    it('dispatches job and returns queued message', function () {
        $admin = actingAsAdmin();

        Queue::fake();

        $response = $this->getJson('/api/admin/audit-logs/export?days=30');

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Relatório sendo gerado. Você receberá um e-mail em breve.')
            ->assertJsonMissingPath('url');

        Queue::assertPushed(GenerateAuditLogReportJob::class, function ($job) use ($admin) {
            return $job->user->id === $admin->id && $job->days === 30;
        });
    });

    it('passes filters to the job', function () {
        $admin = actingAsAdmin();

        Queue::fake();

        $response = $this->getJson('/api/admin/audit-logs/export?days=7&event_name=user.login&event_type=manual&search=Jorge');

        $response->assertSuccessful();

        Queue::assertPushed(GenerateAuditLogReportJob::class, function ($job) use ($admin) {
            return $job->user->id === $admin->id
                && $job->days === 7
                && $job->eventName === 'user.login'
                && $job->eventType === 'manual'
                && $job->search === 'Jorge';
        });
    });

    it('validates days parameter', function () {
        actingAsAdmin();

        $this->getJson('/api/admin/audit-logs/export?days=15')->assertStatus(422);
    });

    it('validates event_type parameter', function () {
        actingAsAdmin();

        $this->getJson('/api/admin/audit-logs/export?event_type=invalid')->assertStatus(422);
    });

    it('returns 403 for teacher users', function () {
        actingAsTeacher();

        $this->getJson('/api/admin/audit-logs/export')->assertForbidden();
    });

    it('returns 403 for regular users', function () {
        actingAsUser();

        $this->getJson('/api/admin/audit-logs/export')->assertForbidden();
    });

    it('returns 401 for unauthenticated users', function () {
        $this->getJson('/api/admin/audit-logs/export')->assertUnauthorized();
    });
});

describe('GET /api/admin/audit-logs/event-names', function () {
    it('returns distinct event names sorted alphabetically', function () {
        $admin = actingAsAdmin();
        AuditLog::query()->delete();

        AuditLog::factory()->create(['user_id' => $admin->id, 'event_name' => 'user.login']);
        AuditLog::factory()->create(['user_id' => $admin->id, 'event_name' => 'user.logout']);
        AuditLog::factory()->create(['user_id' => $admin->id, 'event_name' => 'user.login']);

        $response = $this->getJson('/api/admin/audit-logs/event-names');

        $response->assertSuccessful()
            ->assertJsonStructure(['data']);

        $names = $response->json('data');
        expect($names)->toContain('user.login');
        expect($names)->toContain('user.logout');
        expect(count($names))->toBe(2);
    });

    it('returns 403 for teacher users', function () {
        actingAsTeacher();

        $this->getJson('/api/admin/audit-logs/event-names')->assertForbidden();
    });

    it('returns 403 for regular users', function () {
        actingAsUser();

        $this->getJson('/api/admin/audit-logs/event-names')->assertForbidden();
    });
});
