<?php

use App\Console\Commands\AnonymizePendingUsersCommand;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Enums\BadgeKey;
use App\Enums\ExperienceReason;
use App\Jobs\AnonymizeUserJob;
use App\Mail\AccountAnonymized;
use App\Mail\AccountDeletionCancelled;
use App\Mail\AccountDeletionConfirmation;
use App\Mail\AccountDeletionScheduled;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserExperienceEvent;
use App\Notifications\BadgesUnlockedNotification;
use App\Services\GamificationService;
use App\Services\TwoFactorDeviceService;
use App\Services\UserAnonymizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

use function Pest\Laravel\artisan;

it('dispatches AnonymizeUserJob for users past the grace period', function () {
    Queue::fake();
    config()->set('lgpd.retention.account_deletion_grace_days', 30);

    $due = User::factory()->create([
        'anonymization_requested_at' => now()->subDays(31),
    ]);
    $notDue = User::factory()->create([
        'anonymization_requested_at' => now()->subDays(10),
    ]);
    User::factory()->create();

    artisan(AnonymizePendingUsersCommand::class)->assertExitCode(0);

    Queue::assertPushed(AnonymizeUserJob::class, fn ($job) => $job->userId === $due->id);
    Queue::assertNotPushed(AnonymizeUserJob::class, fn ($job) => $job->userId === $notDue->id);
});

it('lists users to anonymize in dry-run mode without dispatching jobs', function () {
    Queue::fake();
    config()->set('lgpd.retention.account_deletion_grace_days', 30);

    User::factory()->create([
        'anonymization_requested_at' => now()->subDays(35),
    ]);

    artisan(AnonymizePendingUsersCommand::class, ['--dry-run' => true])->assertExitCode(0);

    Queue::assertNothingPushed();
});

it('anonymizes the user and emails the original address when the job runs', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'original@example.com',
        'name' => 'Original Name',
        'anonymization_requested_at' => now()->subDays(31),
    ]);

    (new AnonymizeUserJob($user->id))->handle(
        app(UserAnonymizationService::class),
    );

    $fresh = User::withTrashed()->find($user->id);
    expect($fresh->anonymized_at)->not->toBeNull()
        ->and($fresh->email)->toBe("removed-{$user->id}@deleted.local");

    Mail::assertQueued(AccountAnonymized::class, fn ($mail) => $mail->hasTo('original@example.com'));
});

it('deletes only the anonymized users badges and experience events', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $userBadge = $user->badges()->create([
        'badge_key' => BadgeKey::FirstPresence,
        'earned_at' => now(),
    ]);
    $userEvent = $user->experienceEvents()->create([
        'reason' => ExperienceReason::Attendance,
        'source_key' => 'attendance:mine',
        'points' => 100,
    ]);
    $otherBadge = $otherUser->badges()->create([
        'badge_key' => BadgeKey::FirstPresence,
        'earned_at' => now(),
    ]);
    $otherEvent = $otherUser->experienceEvents()->create([
        'reason' => ExperienceReason::Attendance,
        'source_key' => 'attendance:other',
        'points' => 100,
    ]);

    app(UserAnonymizationService::class)->anonymize($user);

    $this->assertDatabaseMissing('user_badges', ['id' => $userBadge->id]);
    $this->assertDatabaseMissing('user_experience_events', ['id' => $userEvent->id]);
    $this->assertDatabaseHas('user_badges', ['id' => $otherBadge->id, 'user_id' => $otherUser->id]);
    $this->assertDatabaseHas('user_experience_events', ['id' => $otherEvent->id, 'user_id' => $otherUser->id]);
});

it('deletes only the anonymized users gamification notifications and derived audit history', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $userBadge = $user->badges()->create([
        'badge_key' => BadgeKey::FirstPresence,
        'earned_at' => now(),
    ]);
    $userEvent = $user->experienceEvents()->create([
        'reason' => ExperienceReason::Attendance,
        'source_key' => 'attendance:privacy-target',
        'points' => 100,
    ]);
    $otherBadge = $otherUser->badges()->create([
        'badge_key' => BadgeKey::FirstPresence,
        'earned_at' => now(),
    ]);
    $otherEvent = $otherUser->experienceEvents()->create([
        'reason' => ExperienceReason::Attendance,
        'source_key' => 'attendance:privacy-other',
        'points' => 100,
    ]);
    AuditLog::query()->delete();

    $targetAuditIds = [
        AuditLog::create([
            'user_id' => null,
            'event_name' => 'user_badge.created',
            'event_type' => AuditEventType::System,
            'auditable_type' => UserBadge::class,
            'auditable_id' => $userBadge->id,
            'event_data' => [
                'user_id' => $user->id,
                'badge_key' => BadgeKey::FirstPresence->value,
            ],
        ])->id,
        AuditLog::create([
            'user_id' => $admin->id,
            'event_name' => 'user_experience_event.updated',
            'event_type' => AuditEventType::System,
            'auditable_type' => UserExperienceEvent::class,
            'auditable_id' => $userEvent->id,
            'event_data' => [
                'old_values' => ['source_key' => 'attendance:privacy-target', 'points' => 1],
                'new_values' => ['source_key' => 'attendance:privacy-target', 'points' => 100],
            ],
        ])->id,
        AuditLog::create([
            'user_id' => $user->id,
            'event_name' => 'user_badge.updated',
            'event_type' => AuditEventType::System,
            'auditable_type' => UserBadge::class,
            'auditable_id' => $userBadge->id,
            'event_data' => [
                'old_values' => ['badge_key' => BadgeKey::FirstPresence->value],
                'new_values' => ['badge_key' => BadgeKey::Attendance5->value],
            ],
        ])->id,
    ];
    $otherAudit = AuditLog::create([
        'user_id' => $admin->id,
        'event_name' => 'user_experience_event.updated',
        'event_type' => AuditEventType::System,
        'auditable_type' => UserExperienceEvent::class,
        'auditable_id' => $otherEvent->id,
        'event_data' => [
            'old_values' => ['source_key' => 'attendance:privacy-other', 'points' => 1],
            'new_values' => ['source_key' => 'attendance:privacy-other', 'points' => 100],
        ],
    ]);

    $notificationRows = [
        [
            'id' => (string) Str::uuid(),
            'type' => BadgesUnlockedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['category' => 'gamification', 'body' => 'privacy target']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\SeminarReminderNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['category' => 'seminar', 'body' => 'keep target notification']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => (string) Str::uuid(),
            'type' => BadgesUnlockedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $otherUser->id,
            'data' => json_encode(['category' => 'gamification', 'body' => 'keep other notification']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ];
    DB::table('notifications')->insert($notificationRows);

    app(UserAnonymizationService::class)->anonymize($user);

    expect(AuditLog::query()->whereIn('id', $targetAuditIds)->exists())->toBeFalse()
        ->and(AuditLog::query()->where('event_data', 'like', '%attendance:privacy-target%')->exists())->toBeFalse()
        ->and(AuditLog::query()->whereKey($otherAudit->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->forEvent(AuditEvent::AccountAnonymized)->exists())->toBeTrue()
        ->and(DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->where('type', BadgesUnlockedNotification::class)
            ->exists())->toBeFalse()
        ->and(DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->where('type', 'App\\Notifications\\SeminarReminderNotification')
            ->exists())->toBeTrue()
        ->and(DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $otherUser->id)
            ->where('type', BadgesUnlockedNotification::class)
            ->exists())->toBeTrue();

    $this->assertDatabaseHas('user_badges', ['id' => $otherBadge->id]);
    $this->assertDatabaseHas('user_experience_events', ['id' => $otherEvent->id]);
});

it('deletes historical audit traces for rewards revoked before anonymization', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $registration = Registration::factory()->present()->for($user)->create();
    Registration::factory()->present()->for($otherUser)->create();
    $gamification = app(GamificationService::class);

    $gamification->sync($user, notify: false);
    $gamification->sync($otherUser, notify: false);

    $targetBadgeIds = $user->badges()->pluck('id')->all();
    $targetEventIds = $user->experienceEvents()->pluck('id')->all();
    $otherBadgeIds = $otherUser->badges()->pluck('id')->all();
    $otherEventIds = $otherUser->experienceEvents()->pluck('id')->all();
    $otherAuditIds = AuditLog::query()
        ->where(function ($query) use ($otherBadgeIds, $otherEventIds): void {
            $query->where(function ($badges) use ($otherBadgeIds): void {
                $badges->where('auditable_type', UserBadge::class)
                    ->whereIn('auditable_id', $otherBadgeIds);
            })->orWhere(function ($events) use ($otherEventIds): void {
                $events->where('auditable_type', UserExperienceEvent::class)
                    ->whereIn('auditable_id', $otherEventIds);
            });
        })
        ->pluck('id')
        ->all();

    $registration->update(['present' => false]);
    $gamification->sync($user, notify: false);
    AuditLog::create([
        'user_id' => null,
        'event_name' => 'user_experience_event.updated',
        'event_type' => AuditEventType::System,
        'auditable_type' => UserExperienceEvent::class,
        'auditable_id' => $targetEventIds[0],
        'event_data' => [
            'old_values' => ['user_id' => $user->id, 'source_key' => "attendance:{$registration->id}"],
            'new_values' => ['user_id' => $otherUser->id, 'source_key' => "attendance:{$registration->id}"],
        ],
    ]);

    expect($user->badges()->exists())->toBeFalse()
        ->and($user->experienceEvents()->exists())->toBeFalse()
        ->and(AuditLog::query()
            ->where('auditable_type', UserBadge::class)
            ->whereIn('auditable_id', $targetBadgeIds)
            ->exists())->toBeTrue()
        ->and(AuditLog::query()
            ->where('auditable_type', UserExperienceEvent::class)
            ->whereIn('auditable_id', $targetEventIds)
            ->exists())->toBeTrue();

    app(UserAnonymizationService::class)->anonymize($user);

    expect(AuditLog::query()
        ->where('auditable_type', UserBadge::class)
        ->whereIn('auditable_id', $targetBadgeIds)
        ->exists())->toBeFalse()
        ->and(AuditLog::query()
            ->where('auditable_type', UserExperienceEvent::class)
            ->whereIn('auditable_id', $targetEventIds)
            ->exists())->toBeFalse()
        ->and(AuditLog::query()
            ->where('event_data', 'like', "%attendance:{$registration->id}%")
            ->exists())->toBeFalse()
        ->and(AuditLog::query()->whereIn('id', $otherAuditIds)->count())->toBe(count($otherAuditIds))
        ->and($otherUser->badges()->whereIn('id', $otherBadgeIds)->count())->toBe(count($otherBadgeIds))
        ->and($otherUser->experienceEvents()->whereIn('id', $otherEventIds)->count())->toBe(count($otherEventIds));
});

it('skips already-anonymized users in the scheduler', function () {
    Queue::fake();

    User::factory()->create([
        'anonymization_requested_at' => now()->subDays(60),
        'anonymized_at' => now()->subDays(29),
    ]);

    artisan(AnonymizePendingUsersCommand::class);

    Queue::assertNothingPushed();
});

it('AnonymizeUserJob does nothing when user is already anonymized', function () {
    Mail::fake();

    $user = User::factory()->create([
        'anonymization_requested_at' => now()->subDays(31),
        'anonymized_at' => now()->subDays(1),
    ]);

    (new AnonymizeUserJob($user->id))->handle(
        app(UserAnonymizationService::class),
    );

    // The email is NOT sent and the model stays unchanged
    Mail::assertNothingQueued();
    expect($user->fresh()->anonymized_at)->not->toBeNull();
});

it('requires password to request deletion and sends confirmation email without marking the user', function () {
    Mail::fake();
    $user = actingAsUser();

    $this->postJson('/api/profile/delete-request', [
        'password' => 'wrong',
    ])->assertUnauthorized();

    $this->postJson('/api/profile/delete-request', [
        'password' => 'password',
    ])->assertSuccessful();

    expect($user->fresh()->anonymization_requested_at)->toBeNull();
    Mail::assertQueued(AccountDeletionConfirmation::class, fn ($mail) => $mail->hasTo($user->email));
    Mail::assertNotQueued(AccountDeletionScheduled::class);
    expect(AuditLog::where('event_name', AuditEvent::AccountDeletionConfirmationSent->value)->exists())->toBeTrue();
});

it('rejects deletion when already requested', function () {
    $user = actingAsUser();
    $user->forceFill(['anonymization_requested_at' => now()])->save();

    $this->postJson('/api/profile/delete-request', [
        'password' => 'password',
    ])->assertConflict();
});

it('cancels a pending deletion via dedicated endpoint', function () {
    Mail::fake();
    $user = actingAsUser();
    $user->forceFill(['anonymization_requested_at' => now()])->save();

    $this->postJson('/api/profile/delete-cancel')->assertSuccessful();

    expect($user->fresh()->anonymization_requested_at)->toBeNull();
    Mail::assertQueued(AccountDeletionCancelled::class);
    expect(AuditLog::where('event_name', AuditEvent::AccountDeletionCancelled->value)->exists())->toBeTrue();
});

it('rejects cancellation when no pending deletion', function () {
    actingAsUser();

    $this->postJson('/api/profile/delete-cancel')->assertNotFound();
});

it('cancels pending deletion when the user logs in again', function () {
    Mail::fake();
    $user = User::factory()->create([
        'password' => bcrypt('password'),
        'anonymization_requested_at' => now()->subDays(2),
    ]);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSuccessful();

    expect($user->fresh()->anonymization_requested_at)->toBeNull();
    Mail::assertQueued(AccountDeletionCancelled::class);
});

it('cancels pending deletion when a 2FA user completes login via TOTP code', function () {
    Mail::fake();
    $google = app(Google2FA::class);

    $user = User::factory()->create([
        'password' => Hash::make('secret123'),
        'anonymization_requested_at' => now()->subDays(2),
        'two_factor_secret' => encrypt($google->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-a', 'code-b'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $challenge = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->json('two_factor.challenge_token');

    $code = $google->getCurrentOtp(decrypt($user->two_factor_secret));

    $this->postJson('/api/auth/two-factor-challenge', [
        'challenge_token' => $challenge,
        'code' => $code,
    ])->assertSuccessful();

    expect($user->fresh()->anonymization_requested_at)->toBeNull();
    Mail::assertQueued(AccountDeletionCancelled::class, fn ($mail) => $mail->hasTo($user->email));
    expect(AuditLog::where('event_name', AuditEvent::AccountDeletionCancelled->value)->exists())->toBeTrue();
});

it('cancels pending deletion when a trusted-device 2FA user logs in', function () {
    Mail::fake();

    $user = User::factory()->create([
        'password' => Hash::make('secret123'),
        'anonymization_requested_at' => now()->subDays(2),
        'two_factor_secret' => encrypt(app(Google2FA::class)->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-z'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $trustedToken = app(TwoFactorDeviceService::class)->issue($user, 'TestBrowser', '127.0.0.1');

    $this->disableCookieEncryption();
    $response = $this->call(
        'POST',
        '/api/auth/login',
        [],
        [TwoFactorDeviceService::COOKIE_NAME => $trustedToken],
        [],
        ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'],
        json_encode(['email' => $user->email, 'password' => 'secret123']),
    );

    $response->assertSuccessful();
    expect($user->fresh()->anonymization_requested_at)->toBeNull();
    Mail::assertQueued(AccountDeletionCancelled::class, fn ($mail) => $mail->hasTo($user->email));
    expect(AuditLog::where('event_name', AuditEvent::AccountDeletionCancelled->value)->exists())->toBeTrue();
});

it('cancels pending deletion when a 2FA user completes login via recovery code', function () {
    Mail::fake();
    $google = app(Google2FA::class);

    $user = User::factory()->create([
        'password' => Hash::make('secret123'),
        'anonymization_requested_at' => now()->subDays(2),
        'two_factor_secret' => encrypt($google->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-x', 'code-y'])),
        'two_factor_confirmed_at' => now(),
    ]);

    $challenge = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ])->json('two_factor.challenge_token');

    $this->postJson('/api/auth/two-factor-challenge', [
        'challenge_token' => $challenge,
        'recovery_code' => 'code-x',
    ])->assertSuccessful();

    expect($user->fresh()->anonymization_requested_at)->toBeNull();
    Mail::assertQueued(AccountDeletionCancelled::class, fn ($mail) => $mail->hasTo($user->email));
    expect(AuditLog::where('event_name', AuditEvent::AccountDeletionCancelled->value)->exists())->toBeTrue();
});
