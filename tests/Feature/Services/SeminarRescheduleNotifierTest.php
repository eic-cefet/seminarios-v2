<?php

use App\Enums\AuditEvent;
use App\Mail\SeminarRescheduled;
use App\Mail\SpeakerSeminarRescheduled;
use App\Models\AlertPreference;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Notifications\SeminarRescheduledNotification;
use App\Services\SeminarRescheduleNotifier;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

describe('SeminarRescheduleNotifier', function () {
    it('queues reschedule mail to opted-in attendees', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(10)]);
        $registration = Registration::factory()->for($seminar)->create();
        $oldDate = now()->addDays(7);

        app(SeminarRescheduleNotifier::class)->notify($seminar, $oldDate);

        Mail::assertQueued(
            SeminarRescheduled::class,
            fn (SeminarRescheduled $m) => $m->hasTo($registration->user->email),
        );
    });

    it('skips mail for users opted out of the reschedule communication', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(5)]);
        $oldDate = now()->addDays(10);

        $optedOut = User::factory()->create();
        AlertPreference::updateOrCreate(
            ['user_id' => $optedOut->id],
            ['new_seminar_alert' => false, 'seminar_rescheduled' => false],
        );

        $optedIn = User::factory()->create();
        $optedIn->alertPreference()?->delete();

        foreach ([$optedOut, $optedIn] as $u) {
            Registration::factory()->create([
                'user_id' => $u->id,
                'seminar_id' => $seminar->id,
            ]);
        }

        app(SeminarRescheduleNotifier::class)->notify($seminar, $oldDate);

        Mail::assertQueued(SeminarRescheduled::class, 1);
        Mail::assertQueued(
            SeminarRescheduled::class,
            fn (SeminarRescheduled $m) => $m->hasTo($optedIn->email),
        );
    });

    it('resets reminder_sent on every registration', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
        $oldDate = now()->addDay();

        $reg1 = Registration::factory()->create(['seminar_id' => $seminar->id, 'reminder_sent' => true]);
        $reg2 = Registration::factory()->create(['seminar_id' => $seminar->id, 'reminder_sent' => true]);

        app(SeminarRescheduleNotifier::class)->notify($seminar, $oldDate);

        expect($reg1->fresh()->reminder_sent)->toBeFalse();
        expect($reg2->fresh()->reminder_sent)->toBeFalse();
    });

    it('resets reminder_7d_sent when a seminar is rescheduled', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'reminder_sent' => true,
            'reminder_7d_sent' => true,
        ]);

        app(SeminarRescheduleNotifier::class)->notify($seminar, $seminar->scheduled_at->copy()->subDay());

        $fresh = $registration->fresh();
        expect($fresh->reminder_sent)->toBeFalse()
            ->and($fresh->reminder_7d_sent)->toBeFalse();
    });

    it('records the SeminarRescheduled audit event with metadata', function () {
        Mail::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(7)]);
        $oldDate = now()->addDay();

        Registration::factory()->count(3)->create(['seminar_id' => $seminar->id]);

        app(SeminarRescheduleNotifier::class)->notify($seminar, $oldDate);

        $audit = AuditLog::forEvent(AuditEvent::SeminarRescheduled)->first();
        expect($audit)->not->toBeNull();
        expect($audit->event_data['old_scheduled_at'])->toBe($oldDate->format('Y-m-d H:i:s'));
        expect($audit->event_data['new_scheduled_at'])->toBe($seminar->scheduled_at->format('Y-m-d H:i:s'));
        expect($audit->event_data['notified_users'])->toBe(3);
        expect($audit->auditable_id)->toBe($seminar->id);
    });

    it('dispatches the SeminarRescheduledNotification even to opted-out users', function () {
        Notification::fake();

        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDays(5)]);
        $oldDate = now()->addDays(10);

        $optedOut = User::factory()->create();
        AlertPreference::updateOrCreate(
            ['user_id' => $optedOut->id],
            ['new_seminar_alert' => false, 'seminar_rescheduled' => false],
        );

        Registration::factory()->create([
            'user_id' => $optedOut->id,
            'seminar_id' => $seminar->id,
        ]);

        app(SeminarRescheduleNotifier::class)->notify($seminar, $oldDate);

        Notification::assertSentTo($optedOut, SeminarRescheduledNotification::class);
    });

    it('queues SpeakerSeminarRescheduled to all speakers when a seminar is rescheduled', function () {
        Mail::fake();
        $speaker1 = User::factory()->create();
        $speaker2 = User::factory()->create();
        $seminar = Seminar::factory()->create();
        $seminar->speakers()->attach([$speaker1->id, $speaker2->id]);
        $oldAt = $seminar->scheduled_at->copy()->subDay();

        app(SeminarRescheduleNotifier::class)->notify($seminar, $oldAt);

        Mail::assertQueued(SpeakerSeminarRescheduled::class, 2);
        Mail::assertQueued(SpeakerSeminarRescheduled::class, fn ($m) => $m->speaker->is($speaker1) && $m->seminar->is($seminar));
        Mail::assertQueued(SpeakerSeminarRescheduled::class, fn ($m) => $m->speaker->is($speaker2));
    });

    it('does not gate speaker reschedule mails on alert preferences', function () {
        Mail::fake();
        $speaker = User::factory()->create();
        AlertPreference::factory()->for($speaker)->create(['seminar_rescheduled' => false]);
        $seminar = Seminar::factory()->create();
        $seminar->speakers()->attach($speaker->id);
        $oldAt = $seminar->scheduled_at->copy()->subDay();

        app(SeminarRescheduleNotifier::class)->notify($seminar, $oldAt);

        Mail::assertQueued(SpeakerSeminarRescheduled::class, fn ($m) => $m->speaker->is($speaker));
    });
});
