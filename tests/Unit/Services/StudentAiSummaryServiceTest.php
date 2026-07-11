<?php

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Services\StudentAiSummaryService;
use App\Support\SemesterRange;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'ai.api_key' => 'test-key',
        'ai.base_url' => 'https://api.openai.com/v1',
        'ai.model' => 'gpt-4o-mini',
    ]);
});

it('returns null when the AI service is not configured', function () {
    config(['ai.api_key' => null]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $student = User::factory()->student()->create();

    $summary = app(StudentAiSummaryService::class)->summaryFor(
        $student,
        SemesterRange::fromString('2026.1'),
        $admin,
    );

    expect($summary)->toBeNull();
});

it('generates and caches a summary for a day', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $student = User::factory()->student()->create();
    $seminar = Seminar::factory()->create(['scheduled_at' => '2026-03-01 10:00:00']);
    Registration::factory()->present()->create(['user_id' => $student->id, 'seminar_id' => $seminar->id]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => 'Resumo gerado pela IA.']]],
        ]),
    ]);

    $service = app(StudentAiSummaryService::class);
    $range = SemesterRange::fromString('2026.1');

    $first = $service->summaryFor($student, $range, $admin);
    $second = $service->summaryFor($student, $range, $admin);

    expect($first)->toBe('Resumo gerado pela IA.')
        ->and($second)->toBe('Resumo gerado pela IA.');

    Http::assertSentCount(1);
});

it('sends the payload as TOON, not JSON', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $student = User::factory()->student()->create(['name' => 'Maria Estudante']);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => 'ok']]],
        ]),
    ]);

    app(StudentAiSummaryService::class)->summaryFor($student, SemesterRange::fromString('2026.1'), $admin);

    Http::assertSent(function ($request) {
        $body = $request->data();
        $userMessage = $body['messages'][1]['content'];

        return str_contains($userMessage, 'student:')
            && str_contains($userMessage, 'name: Maria Estudante')
            && ! str_contains($userMessage, '{"student"');
    });
});

it('does not cache a failed AI request', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $student = User::factory()->student()->create();

    Http::fake(['api.openai.com/*' => Http::response(['error' => 'fail'], 500)]);

    $service = app(StudentAiSummaryService::class);
    $range = SemesterRange::fromString('2026.1');

    expect(fn () => $service->summaryFor($student, $range, $admin))
        ->toThrow(RuntimeException::class);

    expect(Cache::has("student_ai_summary:{$student->id}:2026.1:all"))->toBeFalse();
});

it('caches separately per viewer scope so a teacher never receives an admin-scoped summary', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');

    $student = User::factory()->student()->create();
    $seminar = Seminar::factory()->create(['scheduled_at' => '2026-03-01 10:00:00', 'created_by' => $teacher->id]);
    Registration::factory()->present()->create(['user_id' => $student->id, 'seminar_id' => $seminar->id]);

    Http::fake([
        'api.openai.com/*' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => 'Resumo do admin.']]]])
            ->push(['choices' => [['message' => ['content' => 'Resumo do professor.']]]]),
    ]);

    $service = app(StudentAiSummaryService::class);
    $range = SemesterRange::fromString('2026.1');

    $adminSummary = $service->summaryFor($student, $range, $admin);
    $teacherSummary = $service->summaryFor($student, $range, $teacher);

    expect($adminSummary)->toBe('Resumo do admin.')
        ->and($teacherSummary)->toBe('Resumo do professor.');

    Http::assertSentCount(2);

    expect(Cache::has("student_ai_summary:{$student->id}:2026.1:all"))->toBeTrue()
        ->and(Cache::has("student_ai_summary:{$student->id}:2026.1:teacher:{$teacher->id}"))->toBeTrue();
});
