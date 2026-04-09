<?php

use App\Jobs\SendEvaluationReminderJob;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

describe('reminders:evaluations command', function () {
    it('outputs no pending evaluations when there are none', function () {
        $this->artisan('reminders:evaluations')
            ->expectsOutput('Finding users with pending evaluations...')
            ->expectsOutput('No pending evaluations found.')
            ->assertExitCode(0);
    });

    it('finds registrations for seminars that ended 2+ days ago', function () {
        Queue::fake();

        $threeDaysAgo = now()->subDays(3);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $threeDaysAgo,
        ]);

        $user = User::factory()->create();

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
            'evaluation_sent_at' => null,
        ]);

        $this->artisan('reminders:evaluations')
            ->expectsOutput('Finding users with pending evaluations...')
            ->expectsOutputToContain('Found 1 users with pending evaluations.')
            ->expectsOutputToContain($user->email)
            ->expectsOutput('Dispatched 1 evaluation reminder(s).')
            ->assertExitCode(0);

        Queue::assertPushed(SendEvaluationReminderJob::class, 1);
    });

    it('ignores seminars that ended less than 2 days ago', function () {
        Queue::fake();

        $yesterday = now()->subDay();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $yesterday,
        ]);

        Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'present' => true,
            'evaluation_sent_at' => null,
        ]);

        $this->artisan('reminders:evaluations')
            ->expectsOutput('No pending evaluations found.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('ignores seminars that ended more than 30 days ago', function () {
        Queue::fake();

        $fortyDaysAgo = now()->subDays(40);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $fortyDaysAgo,
        ]);

        Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'present' => true,
            'evaluation_sent_at' => null,
        ]);

        $this->artisan('reminders:evaluations')
            ->expectsOutput('No pending evaluations found.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('ignores registrations where user was not present', function () {
        Queue::fake();

        $fiveDaysAgo = now()->subDays(5);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $fiveDaysAgo,
        ]);

        Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'present' => false,
            'evaluation_sent_at' => null,
        ]);

        $this->artisan('reminders:evaluations')
            ->expectsOutput('No pending evaluations found.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('ignores registrations where evaluation was already sent', function () {
        Queue::fake();

        $fiveDaysAgo = now()->subDays(5);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $fiveDaysAgo,
        ]);

        Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'present' => true,
            'evaluation_sent_at' => now()->subDay(),
        ]);

        $this->artisan('reminders:evaluations')
            ->expectsOutput('No pending evaluations found.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('ignores registrations where user already rated the seminar', function () {
        Queue::fake();

        $fiveDaysAgo = now()->subDays(5);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $fiveDaysAgo,
        ]);

        $user = User::factory()->create();

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
            'evaluation_sent_at' => null,
        ]);

        // User already rated this seminar
        Rating::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
        ]);

        $this->artisan('reminders:evaluations')
            ->expectsOutput('No pending evaluations found.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('groups registrations by user', function () {
        Queue::fake();

        $user = User::factory()->create();

        // User attended 2 seminars that need evaluation
        $seminar1 = Seminar::factory()->create([
            'scheduled_at' => now()->subDays(5),
        ]);

        $seminar2 = Seminar::factory()->create([
            'scheduled_at' => now()->subDays(10),
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar1->id,
            'present' => true,
            'evaluation_sent_at' => null,
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar2->id,
            'present' => true,
            'evaluation_sent_at' => null,
        ]);

        $this->artisan('reminders:evaluations')
            ->expectsOutputToContain('Found 1 users with pending evaluations.')
            ->expectsOutputToContain('2 seminar(s)')
            ->expectsOutput('Dispatched 1 evaluation reminder(s).')
            ->assertExitCode(0);

        // Should dispatch only 1 job (grouped by user)
        Queue::assertPushed(SendEvaluationReminderJob::class, 1);
    });

    it('dispatches separate jobs for different users', function () {
        Queue::fake();

        $fiveDaysAgo = now()->subDays(5);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $fiveDaysAgo,
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Registration::factory()->create([
            'user_id' => $user1->id,
            'seminar_id' => $seminar->id,
            'present' => true,
            'evaluation_sent_at' => null,
        ]);

        Registration::factory()->create([
            'user_id' => $user2->id,
            'seminar_id' => $seminar->id,
            'present' => true,
            'evaluation_sent_at' => null,
        ]);

        $this->artisan('reminders:evaluations')
            ->expectsOutputToContain('Found 2 users with pending evaluations.')
            ->expectsOutput('Dispatched 2 evaluation reminder(s).')
            ->assertExitCode(0);

        Queue::assertPushed(SendEvaluationReminderJob::class, 2);
    });

    it('skips users without valid user record', function () {
        Queue::fake();

        $fiveDaysAgo = now()->subDays(5);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $fiveDaysAgo,
        ]);

        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'present' => true,
            'evaluation_sent_at' => null,
        ]);

        // Delete user to simulate orphaned registration
        $registration->user->delete();

        $this->artisan('reminders:evaluations')
            ->expectsOutputToContain('Found 1 users')
            ->expectsOutput('Dispatched 0 evaluation reminder(s).')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('sends reminders synchronously with --sync option', function () {
        Queue::fake();
        Mail::fake();

        $threeDaysAgo = now()->subDays(3);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $threeDaysAgo,
        ]);

        $user = User::factory()->create();

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
            'evaluation_sent_at' => null,
        ]);

        $this->artisan('reminders:evaluations --sync')
            ->expectsOutputToContain('Found 1 users with pending evaluations.')
            ->expectsOutput('Dispatched 1 evaluation reminder(s).')
            ->assertExitCode(0);

        // Job should NOT be queued when using --sync
        Queue::assertNothingPushed();

        // Mail should have been queued (mailable implements ShouldQueue)
        Mail::assertQueued(\App\Mail\EvaluationReminder::class);
    });

});
