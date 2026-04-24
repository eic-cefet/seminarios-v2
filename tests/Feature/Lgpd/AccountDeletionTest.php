<?php

use App\Console\Commands\AnonymizePendingUsersCommand;
use App\Jobs\AnonymizeUserJob;
use App\Mail\AccountAnonymized;
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
