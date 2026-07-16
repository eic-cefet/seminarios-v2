<?php

use App\Enums\ExperienceReason;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\Subject;
use App\Models\User;
use App\Models\Workshop;
use App\Services\GamificationSnapshotBuilder;
use Carbon\CarbonImmutable;

beforeEach(function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-15 12:00:00', 'America/Sao_Paulo'));
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('uses confirmed registrations as the sole attendance authority and tolerates missing relationships', function () {
    $user = User::factory()->create();
    $attended = Seminar::factory()->create([
        'seminar_type_id' => null,
        'scheduled_at' => CarbonImmutable::parse('2026-07-10 10:00:00', 'America/Sao_Paulo'),
    ]);
    $absent = Seminar::factory()->create([
        'scheduled_at' => CarbonImmutable::parse('2026-07-11 10:00:00', 'America/Sao_Paulo'),
    ]);

    Registration::factory()->present()->for($user)->for($attended)->create();
    Registration::factory()->for($user)->for($absent)->create();

    $snapshot = app(GamificationSnapshotBuilder::class)->for($user);

    expect($snapshot->metric('attendance_count'))->toBe(1)
        ->and($snapshot->metric('distinct_subjects'))->toBe(0)
        ->and($snapshot->metric('distinct_types'))->toBe(0)
        ->and($snapshot->metric('distinct_speakers'))->toBe(0)
        ->and($snapshot->metric('completed_workshops'))->toBe(0);
});

it('deduplicates subjects seminar types and speakers across attendance history', function () {
    $user = User::factory()->create();
    $speaker = User::factory()->speaker()->create();
    $type = SeminarType::factory()->create();
    $subjects = Subject::factory()->count(2)->create();
    $seminars = Seminar::factory()->count(2)->create([
        'seminar_type_id' => $type->id,
        'scheduled_at' => CarbonImmutable::parse('2026-07-10 10:00:00', 'America/Sao_Paulo'),
    ]);

    $seminars[0]->subjects()->attach($subjects->pluck('id'));
    $seminars[1]->subjects()->attach($subjects[0]);
    $seminars[0]->speakers()->attach($speaker);
    $seminars[1]->speakers()->attach($speaker);

    foreach ($seminars as $seminar) {
        Registration::factory()->present()->for($user)->for($seminar)->create();
    }

    $snapshot = app(GamificationSnapshotBuilder::class)->for($user);

    expect($snapshot->metric('distinct_subjects'))->toBe(2)
        ->and($snapshot->metric('distinct_types'))->toBe(1)
        ->and($snapshot->metric('distinct_speakers'))->toBe(1);
});

it('uses local calendar day ISO week month semester and year attendance maxima', function () {
    $user = User::factory()->create();
    $dates = [
        '2025-01-06 09:00:00',
        '2025-01-06 10:00:00',
        '2025-01-06 11:00:00',
        '2025-01-07 09:00:00',
        '2025-01-07 10:00:00',
        '2025-01-15 09:00:00',
        '2025-01-16 09:00:00',
        '2025-01-17 09:00:00',
        '2025-06-29 09:00:00',
        '2025-06-30 09:00:00',
        '2025-07-10 09:00:00',
        '2025-07-20 09:00:00',
        '2025-08-10 09:00:00',
        '2025-08-20 09:00:00',
        '2025-09-10 09:00:00',
        '2025-09-20 09:00:00',
        '2025-10-10 09:00:00',
        '2025-10-20 09:00:00',
        '2025-11-10 09:00:00',
        '2025-12-10 09:00:00',
    ];

    foreach ($dates as $date) {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => CarbonImmutable::parse($date, 'America/Sao_Paulo'),
        ]);
        Registration::factory()->present()->for($user)->for($seminar)->create();
    }

    $snapshot = app(GamificationSnapshotBuilder::class)->for($user);

    expect($snapshot->metric('max_attendances_day'))->toBe(3)
        ->and($snapshot->metric('max_attendances_week'))->toBe(5)
        ->and($snapshot->metric('max_attendances_month'))->toBe(8)
        ->and($snapshot->metric('max_attendances_semester'))->toBe(10)
        ->and($snapshot->metric('max_attendances_year'))->toBe(20)
        ->and($snapshot->metric('active_semesters'))->toBe(2);
});

it('normalizes timezone-bearing presentation dates through regular model persistence before grouping', function () {
    $user = User::factory()->create();

    foreach (['2026-01-01T02:30:00Z', '2026-01-01T03:30:00Z'] as $scheduledAt) {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $scheduledAt,
        ]);
        Registration::factory()->present()->for($user)->for($seminar)->create();
    }

    expect(app(GamificationSnapshotBuilder::class)->for($user)->metric('max_attendances_day'))
        ->toBe(1);
});

it('maps January and June to the first semester and July and December to the second', function () {
    $user = User::factory()->create();

    foreach (['2024-01-15', '2024-06-15', '2024-07-15', '2024-12-15'] as $date) {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => CarbonImmutable::parse("{$date} 10:00:00", 'America/Sao_Paulo'),
        ]);
        Registration::factory()->present()->for($user)->for($seminar)->create();
    }

    $snapshot = app(GamificationSnapshotBuilder::class)->for($user);

    expect($snapshot->metric('max_attendances_semester'))->toBe(2)
        ->and($snapshot->metric('active_semesters'))->toBe(2);
});

it('counts only evaluations backed by matching confirmed registrations', function () {
    $user = User::factory()->create();
    $seminars = Seminar::factory()->count(11)->create([
        'scheduled_at' => CarbonImmutable::parse('2026-07-10 10:00:00', 'America/Sao_Paulo'),
    ]);

    foreach ($seminars->take(10) as $seminar) {
        Registration::factory()->present()->for($user)->for($seminar)->create();
    }

    foreach ($seminars->take(9) as $seminar) {
        Rating::factory()->for($user)->for($seminar)->create();
    }

    Registration::factory()->for($user)->for($seminars[10])->create();
    Rating::factory()->for($user)->for($seminars[10])->create();

    $snapshot = app(GamificationSnapshotBuilder::class)->for($user);

    expect($snapshot->metric('evaluation_count'))->toBe(9)
        ->and($snapshot->metric('feedback_champion'))->toBe(1);
});

it('does not award feedback champion below ten attendances even at full coverage', function () {
    $user = User::factory()->create();
    $seminars = Seminar::factory()->count(9)->create([
        'scheduled_at' => CarbonImmutable::parse('2026-07-10 10:00:00', 'America/Sao_Paulo'),
    ]);

    foreach ($seminars as $seminar) {
        Registration::factory()->present()->for($user)->for($seminar)->create();
        Rating::factory()->for($user)->for($seminar)->create();
    }

    expect(app(GamificationSnapshotBuilder::class)->for($user)->metric('feedback_champion'))
        ->toBe(0);
});

it('completes a workshop only after all active past presentations are attended', function () {
    $user = User::factory()->create();
    $workshop = Workshop::factory()->create();
    $seminars = Seminar::factory()->past()->count(2)->for($workshop)->create();
    Registration::factory()->present()->for($user)->for($seminars[0])->create();

    expect(app(GamificationSnapshotBuilder::class)->for($user)->metric('completed_workshops'))
        ->toBe(0);

    Registration::factory()->present()->for($user)->for($seminars[1])->create();

    expect(app(GamificationSnapshotBuilder::class)->for($user)->metric('completed_workshops'))
        ->toBe(1);
});

it('requires two active seminars and lets a future active seminar block workshop completion', function () {
    $user = User::factory()->create();
    $singleWorkshop = Workshop::factory()->create();
    $singleSeminar = Seminar::factory()->past()->for($singleWorkshop)->create();
    Registration::factory()->present()->for($user)->for($singleSeminar)->create();

    $blockedWorkshop = Workshop::factory()->create();
    $pastSeminars = Seminar::factory()->past()->count(2)->for($blockedWorkshop)->create();
    Seminar::factory()->upcoming()->for($blockedWorkshop)->create();

    foreach ($pastSeminars as $seminar) {
        Registration::factory()->present()->for($user)->for($seminar)->create();
    }

    expect(app(GamificationSnapshotBuilder::class)->for($user)->metric('completed_workshops'))
        ->toBe(0);
});

it('ignores inactive and soft deleted seminars when completing workshops', function () {
    $user = User::factory()->create();
    $workshop = Workshop::factory()->create();
    $pastSeminars = Seminar::factory()->past()->count(2)->for($workshop)->create();
    Seminar::factory()->upcoming()->inactive()->for($workshop)->create();
    Seminar::factory()->upcoming()->for($workshop)->create()->delete();

    foreach ($pastSeminars as $seminar) {
        Registration::factory()->present()->for($user)->for($seminar)->create();
    }

    expect(app(GamificationSnapshotBuilder::class)->for($user)->metric('completed_workshops'))
        ->toBe(1);
});

it('returns deterministic experience sources with the exact configured point values', function () {
    expect(config('gamification.points'))->toBe([
        'attendance' => 100,
        'evaluation' => 20,
        'new_subject' => 25,
        'workshop_completion' => 150,
    ]);

    $user = User::factory()->create();
    $workshop = Workshop::factory()->create();
    $subject = Subject::factory()->create();
    $seminars = Seminar::factory()->past()->count(2)->for($workshop)->create();
    $registrations = collect();

    foreach ($seminars as $seminar) {
        $seminar->subjects()->attach($subject);
        $registrations->push(Registration::factory()->present()->for($user)->for($seminar)->create());
    }

    $rating = Rating::factory()->for($user)->for($seminars[0])->create();
    $sources = app(GamificationSnapshotBuilder::class)->for($user)->experienceSources();

    expect($sources)->toBe([
        [
            'reason' => ExperienceReason::Attendance,
            'source_key' => "attendance:{$registrations[0]->id}",
            'points' => 100,
        ],
        [
            'reason' => ExperienceReason::Attendance,
            'source_key' => "attendance:{$registrations[1]->id}",
            'points' => 100,
        ],
        [
            'reason' => ExperienceReason::Evaluation,
            'source_key' => "evaluation:{$rating->id}",
            'points' => 20,
        ],
        [
            'reason' => ExperienceReason::NewSubject,
            'source_key' => "subject:{$subject->id}",
            'points' => 25,
        ],
        [
            'reason' => ExperienceReason::WorkshopCompletion,
            'source_key' => "workshop:{$workshop->id}",
            'points' => 150,
        ],
    ]);
});
