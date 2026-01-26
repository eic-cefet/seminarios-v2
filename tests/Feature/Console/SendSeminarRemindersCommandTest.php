<?php

use App\Jobs\SendSeminarReminderJob;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

describe('reminders:seminars command', function () {
    it('outputs no reminders when there are none to send', function () {
        $this->artisan('reminders:seminars')
            ->expectsOutput('Finding seminars happening tomorrow...')
            ->expectsOutput('No reminders to send.')
            ->assertExitCode(0);
    });

    it('finds registrations for seminars scheduled tomorrow', function () {
        Queue::fake();

        $tomorrow = now()->addDay()->setHour(14);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $tomorrow,
            'active' => true,
        ]);

        $user = User::factory()->create();

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'reminder_sent' => false,
        ]);

        $this->artisan('reminders:seminars')
            ->expectsOutput('Finding seminars happening tomorrow...')
            ->expectsOutputToContain('Found 1 users to remind.')
            ->expectsOutputToContain($user->email)
            ->expectsOutput('Dispatched 1 reminder(s).')
            ->assertExitCode(0);

        Queue::assertPushed(SendSeminarReminderJob::class, 1);
    });

    it('ignores seminars not scheduled tomorrow', function () {
        Queue::fake();

        // Seminar scheduled in 3 days
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDays(3),
            'active' => true,
        ]);

        Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'reminder_sent' => false,
        ]);

        $this->artisan('reminders:seminars')
            ->expectsOutput('No reminders to send.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('ignores seminars scheduled today', function () {
        Queue::fake();

        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->setHour(18),
            'active' => true,
        ]);

        Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'reminder_sent' => false,
        ]);

        $this->artisan('reminders:seminars')
            ->expectsOutput('No reminders to send.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('ignores registrations that were already reminded', function () {
        Queue::fake();

        $tomorrow = now()->addDay()->setHour(14);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $tomorrow,
            'active' => true,
        ]);

        Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'reminder_sent' => true,
        ]);

        $this->artisan('reminders:seminars')
            ->expectsOutput('No reminders to send.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('ignores inactive seminars', function () {
        Queue::fake();

        $tomorrow = now()->addDay()->setHour(14);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $tomorrow,
            'active' => false,
        ]);

        Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'reminder_sent' => false,
        ]);

        $this->artisan('reminders:seminars')
            ->expectsOutput('No reminders to send.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('groups registrations by user', function () {
        Queue::fake();

        $tomorrow = now()->addDay()->setHour(14);
        $user = User::factory()->create();

        // User registered for 2 seminars tomorrow
        $seminar1 = Seminar::factory()->create([
            'scheduled_at' => $tomorrow,
            'active' => true,
        ]);

        $seminar2 = Seminar::factory()->create([
            'scheduled_at' => $tomorrow->copy()->addHour(),
            'active' => true,
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar1->id,
            'reminder_sent' => false,
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar2->id,
            'reminder_sent' => false,
        ]);

        $this->artisan('reminders:seminars')
            ->expectsOutputToContain('Found 1 users to remind.')
            ->expectsOutputToContain('2 seminar(s)')
            ->expectsOutput('Dispatched 1 reminder(s).')
            ->assertExitCode(0);

        // Should dispatch only 1 job (grouped by user)
        Queue::assertPushed(SendSeminarReminderJob::class, 1);
    });

    it('dispatches separate jobs for different users', function () {
        Queue::fake();

        $tomorrow = now()->addDay()->setHour(14);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $tomorrow,
            'active' => true,
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Registration::factory()->create([
            'user_id' => $user1->id,
            'seminar_id' => $seminar->id,
            'reminder_sent' => false,
        ]);

        Registration::factory()->create([
            'user_id' => $user2->id,
            'seminar_id' => $seminar->id,
            'reminder_sent' => false,
        ]);

        $this->artisan('reminders:seminars')
            ->expectsOutputToContain('Found 2 users to remind.')
            ->expectsOutput('Dispatched 2 reminder(s).')
            ->assertExitCode(0);

        Queue::assertPushed(SendSeminarReminderJob::class, 2);
    });

    it('skips users without valid user record', function () {
        Queue::fake();

        $tomorrow = now()->addDay()->setHour(14);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => $tomorrow,
            'active' => true,
        ]);

        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'reminder_sent' => false,
        ]);

        // Delete user to simulate orphaned registration
        $registration->user->delete();

        $this->artisan('reminders:seminars')
            ->expectsOutputToContain('Found 1 users')
            ->expectsOutput('Dispatched 0 reminder(s).')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

});
