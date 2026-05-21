<?php

use App\Enums\AuditEvent;
use App\Jobs\ProcessSeminarRescheduleJob;
use App\Mail\SeminarRescheduled;
use App\Models\AuditLog;
use App\Models\PresenceLink;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Notifications\SeminarRescheduledNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

describe('ProcessSeminarRescheduleJob', function () {
    it('sends rescheduled email to all registered users', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
        $oldDate = now()->addDay();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Registration::factory()->create(['seminar_id' => $seminar->id, 'user_id' => $user1->id]);
        Registration::factory()->create(['seminar_id' => $seminar->id, 'user_id' => $user2->id]);

        $job = new ProcessSeminarRescheduleJob($seminar, $oldDate);
        $job->handle();

        Mail::assertQueued(SeminarRescheduled::class, 2);
        Mail::assertQueued(SeminarRescheduled::class, fn ($mail) => $mail->user->id === $user1->id);
        Mail::assertQueued(SeminarRescheduled::class, fn ($mail) => $mail->user->id === $user2->id);
    });

    it('resets reminder sent to false on all registrations', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
        $oldDate = now()->addDay();

        $reg1 = Registration::factory()->create(['seminar_id' => $seminar->id, 'reminder_sent' => true]);
        $reg2 = Registration::factory()->create(['seminar_id' => $seminar->id, 'reminder_sent' => true]);

        $job = new ProcessSeminarRescheduleJob($seminar, $oldDate);
        $job->handle();

        expect($reg1->fresh()->reminder_sent)->toBeFalse();
        expect($reg2->fresh()->reminder_sent)->toBeFalse();
    });

    it('records audit event with old and new dates and notified count', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
        $oldDate = now()->addDay();

        Registration::factory()->count(3)->create(['seminar_id' => $seminar->id]);

        $job = new ProcessSeminarRescheduleJob($seminar, $oldDate);
        $job->handle();

        $audit = AuditLog::forEvent(AuditEvent::SeminarRescheduled)->first();
        expect($audit)->not->toBeNull();
        expect($audit->event_data['old_scheduled_at'])->toBe($oldDate->format('Y-m-d H:i:s'));
        expect($audit->event_data['new_scheduled_at'])->toBe($seminar->scheduled_at->format('Y-m-d H:i:s'));
        expect($audit->event_data['notified_users'])->toBe(3);
        expect($audit->auditable_id)->toBe($seminar->id);
    });

    it('does not send emails when no registrations exist', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
        $oldDate = now()->addDay();

        $job = new ProcessSeminarRescheduleJob($seminar, $oldDate);
        $job->handle();

        Mail::assertNothingQueued();

        $audit = AuditLog::forEvent(AuditEvent::SeminarRescheduled)->first();
        expect($audit)->not->toBeNull();
        expect($audit->event_data['notified_users'])->toBe(0);
    });

    it('sets audit.origin to the job FQCN when dispatched through the queue', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
        $oldDate = now()->addDay();

        // Dispatching through the queue fires JobProcessing on the sync driver,
        // which routes through SetAuditOriginForQueuedJob to set audit.origin.
        ProcessSeminarRescheduleJob::dispatch($seminar, $oldDate);

        $audit = AuditLog::forEvent(AuditEvent::SeminarRescheduled)->first();
        expect($audit->origin)->toBe(ProcessSeminarRescheduleJob::class);
    });

    it('has correct tries and backoff values', function () {
        $seminar = Seminar::factory()->create();
        $job = new ProcessSeminarRescheduleJob($seminar, now());

        expect($job->tries)->toBe(3);
        expect($job->backoff)->toBe(60);
    });

    it('dispatches SeminarRescheduledNotification to every registered user', function () {
        Mail::fake();
        Notification::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
        $oldDate = now()->addDay();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        Registration::factory()->create(['seminar_id' => $seminar->id, 'user_id' => $user1->id]);
        Registration::factory()->create(['seminar_id' => $seminar->id, 'user_id' => $user2->id]);
        Registration::factory()->create(['seminar_id' => $seminar->id, 'user_id' => $user3->id]);

        (new ProcessSeminarRescheduleJob($seminar, $oldDate))->handle();

        Notification::assertSentTo([$user1, $user2, $user3], SeminarRescheduledNotification::class);
    });
});

describe('ProcessSeminarRescheduleJob presence link expiry sync', function () {
    it('moves an active presence link expiry to the new date plus 4 hours', function () {
        Mail::fake();
        $this->freezeTime();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(14)]);
        $link = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => true,
            'expires_at' => now()->addDay()->addHours(4),
        ]);

        (new ProcessSeminarRescheduleJob($seminar, now()->addDay()))->handle();

        expect($link->fresh()->expires_at->timestamp)
            ->toBe(now()->addDays(14)->addHours(4)->timestamp);
    });

    it('clamps an active link expiry to one hour from now when the new date is too soon', function () {
        Mail::fake();
        $this->freezeTime();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->subHours(6)]);
        $link = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => true,
            'expires_at' => now()->addDays(2),
        ]);

        (new ProcessSeminarRescheduleJob($seminar, now()->addDays(2)))->handle();

        expect($link->fresh()->expires_at->timestamp)
            ->toBe(now()->addHour()->timestamp);
    });

    it('moves an inactive link expiry to the new date plus 4 hours when it had one', function () {
        Mail::fake();
        $this->freezeTime();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(10)]);
        $link = PresenceLink::factory()->inactive()->create([
            'seminar_id' => $seminar->id,
            'expires_at' => now()->addDay()->addHours(4),
        ]);

        (new ProcessSeminarRescheduleJob($seminar, now()->addDay()))->handle();

        expect($link->fresh()->expires_at->timestamp)
            ->toBe(now()->addDays(10)->addHours(4)->timestamp);
    });

    it('keeps a deactivated link expiry null', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(10)]);
        $link = PresenceLink::factory()->inactive()->create([
            'seminar_id' => $seminar->id,
            'expires_at' => null,
        ]);

        (new ProcessSeminarRescheduleJob($seminar, now()->addDay()))->handle();

        expect($link->fresh()->expires_at)->toBeNull();
    });

    it('runs cleanly when the seminar has no presence link', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(10)]);

        (new ProcessSeminarRescheduleJob($seminar, now()->addDay()))->handle();

        expect(PresenceLink::query()->count())->toBe(0);
    });

    it('gives an active never-expiring link a computed expiry on reschedule', function () {
        Mail::fake();
        $this->freezeTime();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(14)]);
        $link = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => true,
            'expires_at' => null,
        ]);

        (new ProcessSeminarRescheduleJob($seminar, now()->addDay()))->handle();

        expect($link->fresh()->expires_at->timestamp)
            ->toBe(now()->addDays(14)->addHours(4)->timestamp);
    });
});
