<?php

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Enums\ExperienceReason;
use App\Models\AuditLog;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('backfills users with historical presence or rating data while attendance remains authoritative', function () {
    $presentUser = User::factory()->create();
    Registration::factory()->present()->for($presentUser)->create();

    $ratedUser = User::factory()->create();
    $ratedSeminar = Seminar::factory()->create();
    Registration::factory()->present()->for($ratedUser)->for($ratedSeminar)->create();
    Rating::factory()->for($ratedUser)->for($ratedSeminar)->create();

    $unconfirmedRatingUser = User::factory()->create();
    Rating::factory()->for($unconfirmedRatingUser)->create();
    $unrelatedUser = User::factory()->create();
    AuditLog::query()->delete();

    $this->artisan('gamification:backfill')->assertSuccessful();

    expect($presentUser->experienceEvents()->exists())->toBeTrue()
        ->and($ratedUser->experienceEvents()->where('reason', ExperienceReason::Evaluation)->exists())->toBeTrue()
        ->and($unconfirmedRatingUser->experienceEvents()->exists())->toBeFalse()
        ->and($unrelatedUser->experienceEvents()->exists())->toBeFalse();

    $audit = AuditLog::query()->forEvent(AuditEvent::GamificationBackfilled)->sole();

    expect($audit->event_type)->toBe(AuditEventType::System)
        ->and($audit->event_data['processed_users'])->toBe(3)
        ->and($audit->event_data['xp_earned'])->toBeGreaterThan(0)
        ->and($audit->event_data['new_badges'])->toBeGreaterThan(0);
});

it('processes more than one hundred historical users across chunks', function () {
    $seminar = Seminar::factory()->create();
    $users = User::factory()->count(101)->create();

    foreach ($users as $user) {
        Registration::factory()->present()->for($user)->for($seminar)->create();
    }

    AuditLog::query()->delete();

    $this->artisan('gamification:backfill')->assertSuccessful();

    expect($users->sum(fn (User $user): int => $user->badges()->count()))->toBe(101)
        ->and(AuditLog::query()->forEvent(AuditEvent::GamificationBackfilled)->sole()->event_data['processed_users'])
        ->toBe(101);
});

it('limits reconciliation to an explicitly requested user', function () {
    $target = User::factory()->create();
    $other = User::factory()->create();
    Registration::factory()->present()->for($target)->create();
    Registration::factory()->present()->for($other)->create();
    AuditLog::query()->delete();

    $this->artisan('gamification:backfill', ['--user' => $target->id])->assertSuccessful();

    expect($target->experienceEvents()->exists())->toBeTrue()
        ->and($other->experienceEvents()->exists())->toBeFalse();

    $data = AuditLog::query()->forEvent(AuditEvent::GamificationBackfilled)->sole()->event_data;

    expect($data['processed_users'])->toBe(1)
        ->and($data['requested_user_id'])->toBe($target->id);
});

it('fails with a Portuguese message for an unknown explicit user', function () {
    $this->artisan('gamification:backfill', ['--user' => 999999])
        ->expectsOutputToContain('Usuário não encontrado.')
        ->assertFailed();

    expect(AuditLog::query()->forEvent(AuditEvent::GamificationBackfilled)->exists())->toBeFalse();
});

it('is idempotent and never creates unlock notifications during backfill', function () {
    $user = User::factory()->create();
    Registration::factory()->present()->for($user)->create();

    $this->artisan('gamification:backfill')->assertSuccessful();
    $badgeState = $user->badges()->get()->toArray();
    $eventState = $user->experienceEvents()->get()->toArray();
    $gamificationAuditCount = AuditLog::query()
        ->whereIn('event_name', [
            'user_badge.created',
            'user_badge.updated',
            'user_badge.deleted',
            'user_experience_event.created',
            'user_experience_event.updated',
            'user_experience_event.deleted',
        ])
        ->count();

    $this->artisan('gamification:backfill')->assertSuccessful();

    expect($user->badges()->get()->toArray())->toBe($badgeState)
        ->and($user->experienceEvents()->get()->toArray())->toBe($eventState)
        ->and(AuditLog::query()
            ->whereIn('event_name', [
                'user_badge.created',
                'user_badge.updated',
                'user_badge.deleted',
                'user_experience_event.created',
                'user_experience_event.updated',
                'user_experience_event.deleted',
            ])
            ->count())->toBe($gamificationAuditCount)
        ->and(AuditLog::query()->forEvent(AuditEvent::GamificationBackfilled)->count())->toBe(2)
        ->and(DB::table('notifications')->count())->toBe(0);
});
