<?php

use App\Enums\BadgeKey;
use App\Enums\ExperienceReason;
use App\Gamification\BadgeCatalog;
use App\Gamification\GamificationSnapshot;
use App\Models\AuditLog;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Models\UserExperienceEvent;
use App\Services\GamificationService;
use App\Services\GamificationSnapshotBuilder;
use Carbon\CarbonImmutable;

beforeEach(function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-15 12:00:00', 'America/Sao_Paulo'));
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('reconciles historical sources badges tier bonuses and lifetime progress on first sync', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create(['seminar_type_id' => null]);
    $registration = Registration::factory()->present()->for($user)->for($seminar)->create();
    $rating = Rating::factory()->for($user)->for($seminar)->create();

    $result = app(GamificationService::class)->sync($user, notify: false);

    expect($user->experienceEvents()->get()->mapWithKeys(
        fn ($event): array => ["{$event->reason->value}|{$event->source_key}" => $event->points],
    )->all())->toBe([
        "attendance|attendance:{$registration->id}" => 100,
        "evaluation|evaluation:{$rating->id}" => 20,
        'badge_bonus|badge:first_presence' => 25,
        'badge_bonus|badge:first_evaluation' => 25,
    ])->and($user->badges()->pluck('badge_key')->all())->toEqualCanonicalizing([
        BadgeKey::FirstPresence,
        BadgeKey::FirstEvaluation,
    ])->and($result->xpEarned)->toBe(170)
        ->and($result->totalXp)->toBe(170)
        ->and($result->level)->toBe([
            'level' => 2,
            'rank' => 'Curioso',
            'current_level_xp' => 100,
            'next_level_xp' => 300,
            'progress_percent' => 35,
        ])
        ->and(array_column($result->newBadges, 'key'))->toBe([
            'first_presence',
            'first_evaluation',
        ]);
});

it('adds the configured bonus source for every earned badge tier', function () {
    $snapshot = GamificationSnapshot::fromMetrics([
        'attendance_count' => 100,
        'distinct_subjects' => 20,
        'distinct_types' => 3,
        'distinct_speakers' => 15,
        'max_attendances_day' => 3,
        'max_attendances_week' => 5,
        'max_attendances_month' => 8,
        'max_attendances_semester' => 10,
        'max_attendances_year' => 20,
        'active_semesters' => 6,
        'completed_workshops' => 5,
        'evaluation_count' => 100,
    ]);
    $builder = Mockery::mock(GamificationSnapshotBuilder::class);
    $builder->shouldReceive('for')->once()->andReturn($snapshot);
    app()->instance(GamificationSnapshotBuilder::class, $builder);
    $user = User::factory()->create();

    $result = app(GamificationService::class)->sync($user, notify: false);
    $catalog = app(BadgeCatalog::class)->all();

    expect($user->badges()->count())->toBe(30)
        ->and($result->newBadges)->toHaveCount(30)
        ->and($user->experienceEvents()->count())->toBe(30);

    foreach ($catalog as $definition) {
        expect($user->experienceEvents()
            ->where('reason', ExperienceReason::BadgeBonus)
            ->where('source_key', "badge:{$definition->key->value}")
            ->value('points'))
            ->toBe(config("gamification.badge_tier_bonus.{$definition->tier}"));
    }
});

it('is idempotent across concurrent-equivalent repeated sync calls', function () {
    $user = User::factory()->create();
    Registration::factory()->present()->for($user)->create();
    $service = app(GamificationService::class);

    $first = $service->sync($user, notify: false);
    $badgeState = $user->badges()->get(['badge_key', 'earned_at', 'created_at', 'updated_at'])->toArray();
    $eventState = $user->experienceEvents()->get(['reason', 'source_key', 'points', 'created_at', 'updated_at'])->toArray();
    $auditCount = AuditLog::query()->count();
    $second = $service->sync($user, notify: false);

    expect($second->xpEarned)->toBe(0)
        ->and($second->totalXp)->toBe($first->totalXp)
        ->and($second->newBadges)->toBe([])
        ->and($user->badges()->get(['badge_key', 'earned_at', 'created_at', 'updated_at'])->toArray())->toBe($badgeState)
        ->and($user->experienceEvents()->get(['reason', 'source_key', 'points', 'created_at', 'updated_at'])->toArray())->toBe($eventState)
        ->and(AuditLog::query()->count())->toBe($auditCount);
});

it('returns only genuinely new badges and preserves the original earned timestamp', function () {
    $user = User::factory()->create();
    Registration::factory()->present()->for($user)->create();
    $service = app(GamificationService::class);

    $service->sync($user, notify: false);
    $firstEarnedAt = $user->badges()->where('badge_key', BadgeKey::FirstPresence)->value('earned_at');

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-16 12:00:00', 'America/Sao_Paulo'));
    Registration::factory()->present()->count(4)->for($user)->create();
    $result = $service->sync($user, notify: false);

    expect(array_column($result->newBadges, 'key'))->toContain('attendance_5')
        ->not->toContain('first_presence')
        ->and($user->badges()->where('badge_key', BadgeKey::FirstPresence)->value('earned_at'))
        ->toEqual($firstEarnedAt);
});

it('updates changed sources and deletes stale sources through auditable models', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->present()->for($user)->create();
    UserExperienceEvent::factory()->for($user)->create([
        'reason' => ExperienceReason::Attendance,
        'source_key' => "attendance:{$registration->id}",
        'points' => 1,
    ]);
    UserExperienceEvent::factory()->for($user)->create([
        'reason' => ExperienceReason::NewSubject,
        'source_key' => 'subject:999999',
        'points' => 25,
    ]);
    AuditLog::query()->delete();

    app(GamificationService::class)->sync($user, notify: false);

    expect($user->experienceEvents()->where('source_key', "attendance:{$registration->id}")->value('points'))->toBe(100)
        ->and($user->experienceEvents()->where('source_key', 'subject:999999')->exists())->toBeFalse()
        ->and(AuditLog::query()->where('event_name', 'user_experience_event.updated')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event_name', 'user_experience_event.deleted')->exists())->toBeTrue();
});

it('revokes derived rewards after an incorrect presence is removed', function () {
    $user = User::factory()->create();
    $registration = Registration::factory()->present()->for($user)->create();
    $service = app(GamificationService::class);

    $service->sync($user, notify: false);
    expect($user->experienceEvents()->sum('points'))->toBeGreaterThan(0)
        ->and($user->badges()->where('badge_key', BadgeKey::FirstPresence)->exists())->toBeTrue();

    $registration->update(['present' => false]);
    $result = $service->sync($user, notify: false);

    expect($result->xpEarned)->toBe(0)
        ->and($result->totalXp)->toBe(0)
        ->and($user->experienceEvents()->count())->toBe(0)
        ->and($user->badges()->count())->toBe(0);
});

it('revokes evaluation rewards after a rating is deleted', function () {
    $user = User::factory()->create();
    $seminar = Seminar::factory()->create();
    Registration::factory()->present()->for($user)->for($seminar)->create();
    $rating = Rating::factory()->for($user)->for($seminar)->create();
    $service = app(GamificationService::class);

    $service->sync($user, notify: false);
    $rating->delete();
    $result = $service->sync($user, notify: false);

    expect($result->xpEarned)->toBe(0)
        ->and($user->experienceEvents()->where('reason', ExperienceReason::Evaluation)->exists())->toBeFalse()
        ->and($user->experienceEvents()->where('source_key', 'badge:first_evaluation')->exists())->toBeFalse()
        ->and($user->badges()->where('badge_key', BadgeKey::FirstEvaluation)->exists())->toBeFalse()
        ->and($user->badges()->where('badge_key', BadgeKey::FirstPresence)->exists())->toBeTrue();
});

it('serializes all badges grouped by category with locked state and five recent badges without writes', function () {
    $user = User::factory()->create();
    $earnedKeys = array_slice(BadgeKey::cases(), 0, 6);

    foreach ($earnedKeys as $index => $key) {
        $user->badges()->create([
            'badge_key' => $key,
            'earned_at' => now()->subMinutes(6 - $index),
        ]);
    }

    $user->experienceEvents()->create([
        'reason' => ExperienceReason::Attendance,
        'source_key' => 'attendance:profile-test',
        'points' => 650,
    ]);
    $countsBefore = [
        'badges' => $user->badges()->count(),
        'events' => $user->experienceEvents()->count(),
        'audits' => AuditLog::query()->count(),
    ];

    $profile = app(GamificationService::class)->profileFor($user);
    $serializedBadges = collect($profile['categories'])->flatMap(fn (array $category): array => $category['badges']);

    expect($profile['progress'])->toBe([
        'total_xp' => 650,
        'level' => 4,
        'rank' => 'Curioso',
        'current_level_xp' => 600,
        'next_level_xp' => 1000,
        'progress_percent' => 12,
    ])->and($profile['summary'])->toBe([
        'earned_badges' => 6,
        'total_badges' => 30,
    ])->and(array_column($profile['categories'], 'name'))->toBe([
        'Participação',
        'Exploração',
        'Ritmo',
        'Constância',
        'Workshops',
        'Contribuição',
    ])->and($serializedBadges)->toHaveCount(30)
        ->and($serializedBadges->firstWhere('key', 'first_presence')['earned'])->toBeTrue()
        ->and($serializedBadges->firstWhere('key', 'subjects_3'))->toMatchArray([
            'earned' => false,
            'earned_at' => null,
        ])->and(array_column($profile['recent_badges'], 'key'))->toBe([
            'attendance_100',
            'attendance_50',
            'attendance_25',
            'attendance_10',
            'attendance_5',
        ])->and([
            'badges' => $user->badges()->count(),
            'events' => $user->experienceEvents()->count(),
            'audits' => AuditLog::query()->count(),
        ])->toBe($countsBefore);
});
