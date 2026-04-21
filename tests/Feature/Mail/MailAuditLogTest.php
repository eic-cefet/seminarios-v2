<?php

use App\Enums\AuditEvent;
use App\Mail\BugReportMail;
use App\Mail\CertificateGenerated;
use App\Mail\EvaluationReminder;
use App\Mail\ReportReady;
use App\Mail\SeminarReminder;
use App\Mail\SeminarRescheduled;
use App\Mail\WelcomeUser;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Mail;

describe('MessageSent audit listener', function () {
    beforeEach(function () {
        config([
            'mail.default' => 'array',
            'queue.default' => 'sync',
            'features.email_audit.enabled' => true,
        ]);
    });

    it('records one audit entry per successful send', function () {
        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create();

        Mail::to($user)->send(new CertificateGenerated($registration, 'pdf'));

        $logs = AuditLog::forEvent(AuditEvent::EmailSent)->get();
        expect($logs)->toHaveCount(1);
        expect($logs->first()->event_data['mail'])->toBe(CertificateGenerated::class);
        expect($logs->first()->event_data['ref_id'])->toBe('certificate:'.$registration->id);
        expect($logs->first()->event_data['to'])->toBe([$user->email]);
    });

    it('does not duplicate audits even when sending repeatedly through queued mailables', function () {
        $user = User::factory()->create();

        Mail::to($user)->send(new WelcomeUser($user));
        Mail::to($user)->send(new WelcomeUser($user));

        expect(AuditLog::forEvent(AuditEvent::EmailSent)->count())->toBe(2);
    });

    it('audits EvaluationReminder with seminars encoded in the ref id', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(2)->create();

        Mail::to($user)->send(new EvaluationReminder($user, $seminars));

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->first();
        $expected = 'evaluation-reminder:'.$user->id.':'.$seminars->pluck('id')->sort()->values()->implode('-').':'.now()->format('Ymd');
        expect($log->event_data['ref_id'])->toBe($expected);
        expect($log->event_data['mail'])->toBe(EvaluationReminder::class);
    });

    it('audits SeminarReminder', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(2)->create(['scheduled_at' => now()->addDay()]);

        Mail::to($user)->send(new SeminarReminder($user, $seminars));

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->first();
        expect($log->event_data['mail'])->toBe(SeminarReminder::class);
        expect($log->event_data['ref_id'])->toContain('seminar-reminder:'.$user->id.':');
    });

    it('audits SeminarRescheduled', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addWeek()]);
        $old = now()->subWeek()->toDateTime();

        Mail::to($user)->send(new SeminarRescheduled($user, $seminar, $old));

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->first();
        expect($log->event_data['ref_id'])->toBe('seminar-rescheduled:'.$seminar->id.':'.$user->id.':'.$old->getTimestamp());
    });

    it('audits BugReportMail with a uuid-based ref id', function () {
        config(['mail.bug_report_email' => 'bugs@example.com']);

        $mail = new BugReportMail('Crash', 'Body', 'Alice', 'alice@example.com', []);
        Mail::to('bugs@example.com')->send($mail);

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->first();
        expect($log->event_data['ref_id'])->toBe($mail->refId);
        expect($log->event_data['mail'])->toBe(BugReportMail::class);
    });

    it('audits ReportReady', function () {
        $user = User::factory()->create();

        $mail = new ReportReady('Monthly Report', 'https://example.com/r.zip');
        Mail::to($user->email)->send($mail);

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->first();
        expect($log->event_data['ref_id'])->toBe($mail->refId);
    });

    it('audits ResetPassword notification once per send', function () {
        $user = User::factory()->create();
        $notification = new ResetPassword('token-xyz');

        $user->notify($notification);

        $logs = AuditLog::forEvent(AuditEvent::EmailSent)->get();
        expect($logs)->toHaveCount(1);
        expect($logs->first()->event_data['mail'])->toBe(ResetPassword::class);
        expect($logs->first()->event_data['ref_id'])->toBe($notification->refId);
    });

    it('skips the audit when the email_audit feature flag is disabled', function () {
        config(['features.email_audit.enabled' => false]);
        $user = User::factory()->create();

        Mail::to($user)->send(new WelcomeUser($user));

        expect(AuditLog::forEvent(AuditEvent::EmailSent)->exists())->toBeFalse();
    });

    it('skips the audit for emails without an X-Entity-Ref-ID header', function () {
        Mail::raw('plain body', function ($message) {
            $message->to('nobody@example.com')->subject('Untagged');
        });

        expect(AuditLog::forEvent(AuditEvent::EmailSent)->exists())->toBeFalse();
    });
});
