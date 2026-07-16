<?php

use App\Enums\BadgeKey;
use App\Enums\ExperienceReason;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

afterEach(function () {
    CarbonImmutable::setTestNow();
});

describe('GET /api/profile/gamification', function () {
    it('requires authentication', function () {
        $this->getJson('/api/profile/gamification')->assertUnauthorized();
    });

    it('returns the empty aggregate profile for the authenticated user', function () {
        actingAsUser();

        $response = $this->getJson('/api/profile/gamification');

        $response->assertOk()
            ->assertJsonPath('data.progress', [
                'total_xp' => 0,
                'level' => 1,
                'rank' => 'Iniciante',
                'current_level_xp' => 0,
                'next_level_xp' => 100,
                'progress_percent' => 0,
            ])
            ->assertJsonPath('data.summary', [
                'earned_badges' => 0,
                'total_badges' => 30,
            ])
            ->assertJsonCount(6, 'data.categories')
            ->assertJsonCount(6, 'data.categories.0.badges')
            ->assertJsonCount(0, 'data.recent_badges')
            ->assertJsonPath('data.categories.0.badges.0.description', 'Participe de uma apresentação.')
            ->assertJsonMissingPath('data.categories.0.badges.0.metric')
            ->assertJsonMissingPath('data.categories.0.badges.0.threshold')
            ->assertJsonMissingPath('data.experience_events');

        $categories = collect($response->json('data.categories'));

        expect($categories->map(
            fn (array $category): array => Arr::only($category, ['key', 'label']),
        )->all())->toBe([
            ['key' => 'participacao', 'label' => 'Participação'],
            ['key' => 'exploracao', 'label' => 'Exploração'],
            ['key' => 'ritmo', 'label' => 'Ritmo'],
            ['key' => 'constancia', 'label' => 'Constância'],
            ['key' => 'workshops', 'label' => 'Workshops'],
            ['key' => 'contribuicao', 'label' => 'Contribuição'],
        ])->and($categories->flatMap(fn (array $category): array => $category['badges']))
            ->toHaveCount(30)
            ->each->toMatchArray([
                'earned' => false,
                'earned_at' => null,
            ]);
    });

    it('returns earned and locked cards grouped by category with exact level progress and recent ordering', function () {
        CarbonImmutable::setTestNow('2026-07-15 12:00:00');
        $user = actingAsUser();
        $earnedKeys = array_slice(BadgeKey::cases(), 0, 6);
        $firstEarnedAt = now()->subMinutes(6)->toIso8601String();

        foreach ($earnedKeys as $index => $badgeKey) {
            $user->badges()->create([
                'badge_key' => $badgeKey,
                'earned_at' => now()->subMinutes(6 - $index),
            ]);
        }

        $user->experienceEvents()->create([
            'reason' => ExperienceReason::Attendance,
            'source_key' => 'attendance:self-profile',
            'points' => 650,
        ]);
        $countsBefore = [
            'badges' => $user->badges()->count(),
            'events' => $user->experienceEvents()->count(),
            'audits' => AuditLog::query()->count(),
        ];

        $response = $this->getJson('/api/profile/gamification');
        $badges = collect($response->json('data.categories'))
            ->flatMap(fn (array $category): array => $category['badges']);

        $response->assertOk()
            ->assertJsonPath('data.progress', [
                'total_xp' => 650,
                'level' => 4,
                'rank' => 'Curioso',
                'current_level_xp' => 600,
                'next_level_xp' => 1000,
                'progress_percent' => 12,
            ])
            ->assertJsonPath('data.summary', [
                'earned_badges' => 6,
                'total_badges' => 30,
            ])
            ->assertJsonPath('data.categories.0.key', 'participacao')
            ->assertJsonPath('data.categories.0.label', 'Participação')
            ->assertJsonPath('data.categories.5.key', 'contribuicao')
            ->assertJsonPath('data.categories.5.label', 'Contribuição')
            ->assertJsonPath('data.recent_badges.0.key', 'attendance_100')
            ->assertJsonPath('data.recent_badges.0.description', 'Participe de 100 apresentações.')
            ->assertJsonPath('data.recent_badges.4.key', 'attendance_5')
            ->assertJsonCount(5, 'data.recent_badges')
            ->assertJsonMissingPath('data.recent_badges.0.metric')
            ->assertJsonMissingPath('data.recent_badges.0.threshold')
            ->assertJsonMissingPath('data.experience_events')
            ->assertJsonMissingPath('data.events');

        expect($badges)->toHaveCount(30)
            ->and($badges->firstWhere('key', 'first_presence'))->toMatchArray([
                'earned' => true,
                'earned_at' => $firstEarnedAt,
            ])
            ->and($badges->firstWhere('key', 'subjects_3'))->toMatchArray([
                'earned' => false,
                'earned_at' => null,
            ])
            ->and([
                'badges' => $user->badges()->count(),
                'events' => $user->experienceEvents()->count(),
                'audits' => AuditLog::query()->count(),
            ])->toBe($countsBefore);

    });

    it('returns only the requester rows and never another users achievement data', function () {
        $user = actingAsUser();
        $other = User::factory()->create();
        $user->experienceEvents()->create([
            'reason' => ExperienceReason::Attendance,
            'source_key' => 'attendance:requester',
            'points' => 100,
        ]);
        $other->experienceEvents()->create([
            'reason' => ExperienceReason::Attendance,
            'source_key' => 'attendance:other',
            'points' => 900,
        ]);
        $other->badges()->create([
            'badge_key' => BadgeKey::FirstPresence,
            'earned_at' => now(),
        ]);

        $this->getJson('/api/profile/gamification')
            ->assertOk()
            ->assertJsonPath('data.progress.total_xp', 100)
            ->assertJsonPath('data.summary.earned_badges', 0)
            ->assertJsonCount(0, 'data.recent_badges')
            ->assertJsonMissingPath('data.experience_events');
    });
});
