<?php

use App\Console\Commands\AnonymizePendingUsersCommand;
use App\Enums\AuditEvent;
use App\Jobs\AnonymizeUserJob;
use App\Mail\AccountAnonymized;
use App\Mail\AccountDeletionCancelled;
use App\Mail\AccountDeletionScheduled;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\UserAnonymizationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

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

it('skips already-anonymized users', function () {
    Queue::fake();

    User::factory()->create([
        'anonymization_requested_at' => now()->subDays(60),
        'anonymized_at' => now()->subDays(29),
    ]);

    artisan(AnonymizePendingUsersCommand::class);

    Queue::assertNothingPushed();
});

it('requires password to request deletion and marks the user', function () {
    Mail::fake();
    $user = actingAsUser();

    $this->postJson('/api/profile/delete-request', [
        'password' => 'wrong',
    ])->assertUnauthorized();

    $this->postJson('/api/profile/delete-request', [
        'password' => 'password',
    ])->assertSuccessful();

    expect($user->fresh()->anonymization_requested_at)->not->toBeNull();
    Mail::assertQueued(AccountDeletionScheduled::class);
    expect(AuditLog::where('event_name', AuditEvent::AccountDeletionRequested->value)->exists())->toBeTrue();
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
