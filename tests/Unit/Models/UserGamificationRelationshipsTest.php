<?php

use App\Enums\BadgeKey;
use App\Enums\ExperienceReason;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserExperienceEvent;

it('defines the badge key catalog', function () {
    expect(BadgeKey::cases())->toHaveCount(30)
        ->and(array_column(BadgeKey::cases(), 'value'))->toBe([
            'first_presence',
            'attendance_5',
            'attendance_10',
            'attendance_25',
            'attendance_50',
            'attendance_100',
            'subjects_3',
            'subjects_5',
            'subjects_10',
            'subjects_20',
            'types_3',
            'speakers_5',
            'speakers_15',
            'double_day',
            'triple_day',
            'week_5',
            'month_8',
            'semester_10',
            'year_20',
            'semesters_2',
            'semesters_4',
            'semesters_6',
            'first_workshop',
            'workshops_3',
            'workshops_5',
            'first_evaluation',
            'evaluations_5',
            'evaluations_10',
            'evaluations_25',
            'feedback_champion',
        ]);
});

it('defines the experience reason catalog', function () {
    expect(array_column(ExperienceReason::cases(), 'value'))->toBe([
        'attendance',
        'evaluation',
        'new_subject',
        'workshop_completion',
        'badge_bonus',
    ]);
});

it('relates users to badges and experience events', function () {
    $user = User::factory()->create();
    $badge = UserBadge::factory()->for($user)->create();
    $event = UserExperienceEvent::factory()->for($user)->create();

    expect($user->badges->modelKeys())->toBe([$badge->id])
        ->and($user->experienceEvents->modelKeys())->toBe([$event->id])
        ->and($badge->badge_key)->toBeInstanceOf(BadgeKey::class)
        ->and($event->reason)->toBeInstanceOf(ExperienceReason::class);
});
