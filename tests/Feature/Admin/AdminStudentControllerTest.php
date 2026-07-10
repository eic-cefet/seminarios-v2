<?php

use App\Models\AuditLog;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
});

describe('GET /admin/students', function () {
    it('lists students registered in the given semester', function () {
        actingAsAdmin();

        $student = User::factory()->student()->create();
        $seminar = Seminar::factory()->create(['scheduled_at' => '2026-03-10 10:00:00']);
        Registration::factory()->create(['user_id' => $student->id, 'seminar_id' => $seminar->id]);

        $response = $this->getJson('/api/admin/students?semester=2026.1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $student->id)
            ->assertJsonPath('summary.semester', '2026.1');
    });

    it('defaults to the current semester when none is given', function () {
        actingAsAdmin();
        Carbon\Carbon::setTestNow('2026-03-15 10:00:00');

        $response = $this->getJson('/api/admin/students');

        $response->assertOk()->assertJsonPath('summary.semester', '2026.1');

        Carbon\Carbon::setTestNow();
    });

    it('scopes results to a teacher\'s own seminars', function () {
        $teacher = actingAsTeacher();

        $ownStudent = User::factory()->student()->create();
        $ownSeminar = Seminar::factory()->create(['scheduled_at' => '2026-03-10 10:00:00', 'created_by' => $teacher->id]);
        Registration::factory()->create(['user_id' => $ownStudent->id, 'seminar_id' => $ownSeminar->id]);

        $otherSeminar = Seminar::factory()->create(['scheduled_at' => '2026-03-11 10:00:00']);
        $otherStudent = User::factory()->student()->create();
        Registration::factory()->create(['user_id' => $otherStudent->id, 'seminar_id' => $otherSeminar->id]);

        $response = $this->getJson('/api/admin/students?semester=2026.1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownStudent->id);
    });

    it('rejects a malformed semester', function () {
        actingAsAdmin();

        $this->getJson('/api/admin/students?semester=not-a-semester')
            ->assertUnprocessable();
    });

    it('rejects unauthenticated requests', function () {
        $this->getJson('/api/admin/students')->assertUnauthorized();
    });

    it('rejects plain students', function () {
        actingAsUser();

        $this->getJson('/api/admin/students')->assertForbidden();
    });
});

describe('GET /admin/students/{user}/dashboard', function () {
    it('returns totals, by_type, registrations and certificates', function () {
        actingAsAdmin();

        $student = User::factory()->student()->create();
        $type = SeminarType::factory()->create(['name' => 'Palestra']);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => '2026-03-01 10:00:00',
            'duration_minutes' => 60,
            'seminar_type_id' => $type->id,
        ]);
        Registration::factory()->withCertificate()->create([
            'user_id' => $student->id,
            'seminar_id' => $seminar->id,
        ]);

        $response = $this->getJson("/api/admin/students/{$student->id}/dashboard?semester=2026.1");

        $response->assertOk()
            ->assertJsonPath('data.totals.attended', 1)
            ->assertJsonPath('data.by_type.0.type', 'Palestra')
            ->assertJsonCount(1, 'data.registrations')
            ->assertJsonCount(1, 'data.certificates')
            ->assertJsonPath('data.certificates.0.certificate_code', function ($code) {
                return is_string($code);
            });
    });

    it('marks a future registration as upcoming, not missed', function () {
        actingAsAdmin();
        Carbon\Carbon::setTestNow('2026-03-01 00:00:00');

        $student = User::factory()->student()->create();
        $seminar = Seminar::factory()->create(['scheduled_at' => '2026-06-01 10:00:00']);
        Registration::factory()->create(['user_id' => $student->id, 'seminar_id' => $seminar->id, 'present' => false]);

        $response = $this->getJson("/api/admin/students/{$student->id}/dashboard?semester=2026.1");

        $response->assertOk()->assertJsonPath('data.registrations.0.status', 'upcoming');

        Carbon\Carbon::setTestNow();
    });

    it('records an audit log entry', function () {
        actingAsAdmin();
        $student = User::factory()->student()->create();

        $this->getJson("/api/admin/students/{$student->id}/dashboard?semester=2026.1")->assertOk();

        expect(AuditLog::where('event_name', 'admin.student_dashboard_viewed')->where('auditable_id', $student->id)->exists())
            ->toBeTrue();
    });

    it('denies a teacher viewing a student with no shared registrations', function () {
        actingAsTeacher();
        $student = User::factory()->student()->create();

        $this->getJson("/api/admin/students/{$student->id}/dashboard?semester=2026.1")
            ->assertForbidden();
    });

    it('rejects plain students', function () {
        actingAsUser();
        $student = User::factory()->student()->create();

        $this->getJson("/api/admin/students/{$student->id}/dashboard")->assertForbidden();
    });
});

describe('GET /admin/students/{user}/ai-summary', function () {
    beforeEach(function () {
        config([
            'ai.api_key' => 'test-key',
            'ai.base_url' => 'https://api.openai.com/v1',
            'ai.model' => 'gpt-4o-mini',
        ]);
    });

    it('returns a generated summary', function () {
        actingAsAdmin();
        $student = User::factory()->student()->create();

        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => 'Resumo do aluno.']]],
        ])]);

        $response = $this->getJson("/api/admin/students/{$student->id}/ai-summary?semester=2026.1");

        $response->assertOk()->assertJsonPath('data.summary', 'Resumo do aluno.');
    });

    it('records an audit log entry on success', function () {
        actingAsAdmin();
        $student = User::factory()->student()->create();

        Http::fake(['api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => 'Resumo.']]],
        ])]);

        $this->getJson("/api/admin/students/{$student->id}/ai-summary?semester=2026.1")->assertOk();

        expect(AuditLog::where('event_name', 'ai.student_summary_generated')->where('auditable_id', $student->id)->exists())
            ->toBeTrue();
    });

    it('returns 503 when AI is not configured', function () {
        actingAsAdmin();
        config(['ai.api_key' => null]);
        $student = User::factory()->student()->create();

        $this->getJson("/api/admin/students/{$student->id}/ai-summary?semester=2026.1")
            ->assertStatus(503)
            ->assertJsonPath('error', 'ai_not_configured');
    });

    it('returns 502 when the AI request fails', function () {
        actingAsAdmin();
        $student = User::factory()->student()->create();

        Http::fake(['api.openai.com/*' => Http::response(['error' => 'fail'], 500)]);

        $this->getJson("/api/admin/students/{$student->id}/ai-summary?semester=2026.1")
            ->assertStatus(502)
            ->assertJsonPath('error', 'ai_request_failed');
    });

    it('denies a teacher with no shared registrations', function () {
        actingAsTeacher();
        $student = User::factory()->student()->create();

        $this->getJson("/api/admin/students/{$student->id}/ai-summary")->assertForbidden();
    });
});
