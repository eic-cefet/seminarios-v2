<?php

use App\Enums\AuditEvent;
use App\Jobs\ProcessSeminarRescheduleJob;
use App\Mail\SeminarRescheduled;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

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

    it('sets audit context via tracks audit context', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
        $oldDate = now()->addDay();

        $job = new ProcessSeminarRescheduleJob($seminar, $oldDate);
        $job->handle();

        $audit = AuditLog::forEvent(AuditEvent::SeminarRescheduled)->first();
        expect($audit->origin)->toBe('ProcessSeminarRescheduleJob');
    });

    it('has correct tries and backoff values', function () {
        $seminar = Seminar::factory()->create();
        $job = new ProcessSeminarRescheduleJob($seminar, now());

        expect($job->tries)->toBe(3);
        expect($job->backoff)->toBe(60);
    });
});
