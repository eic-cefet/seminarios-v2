<?php

use App\Enums\AuditEvent;
use App\Listeners\AuditResetPasswordNotificationSent;
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
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;

describe('Mail audit logging and threading header', function () {
    beforeEach(function () {
        config(['features.email_audit.enabled' => true]);
    });

    it('skips audit recording when email_audit feature is disabled', function () {
        config(['features.email_audit.enabled' => false]);
        $user = User::factory()->create();

        Mail::to($user)->send(new WelcomeUser($user));

        expect(AuditLog::forEvent(AuditEvent::EmailSent)->exists())->toBeFalse();
    });

    it('audits and sets X-Entity-Ref-ID when CertificateGenerated is sent', function () {
        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create();

        $mail = new CertificateGenerated($registration, 'pdf');
        expect($mail->headers()->text)->toHaveKey('X-Entity-Ref-ID');
        expect($mail->headers()->text['X-Entity-Ref-ID'])->toBe('certificate:'.$registration->id);

        Mail::to($user)->send($mail);

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->latest('id')->first();
        expect($log)->not->toBeNull();
        expect($log->event_data['mail'])->toBe(CertificateGenerated::class);
        expect($log->event_data['ref_id'])->toBe('certificate:'.$registration->id);
    });

    it('audits and sets X-Entity-Ref-ID when WelcomeUser is sent', function () {
        $user = User::factory()->create();

        $mail = new WelcomeUser($user);
        expect($mail->headers()->text['X-Entity-Ref-ID'])->toBe('welcome:'.$user->id);

        Mail::to($user)->send($mail);

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->latest('id')->first();
        expect($log->event_data['mail'])->toBe(WelcomeUser::class);
        expect($log->event_data['ref_id'])->toBe('welcome:'.$user->id);
    });

    it('audits and sets X-Entity-Ref-ID when EvaluationReminder is sent', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(2)->create();

        $mail = new EvaluationReminder($user, $seminars);
        $expected = 'evaluation-reminder:'.$user->id.':'.$seminars->pluck('id')->sort()->values()->implode('-').':'.now()->format('Ymd');
        expect($mail->headers()->text['X-Entity-Ref-ID'])->toBe($expected);

        Mail::to($user)->send($mail);

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->latest('id')->first();
        expect($log->event_data['ref_id'])->toBe($expected);
        expect($log->event_data['seminar_ids'])->toEqualCanonicalizing($seminars->pluck('id')->all());
    });

    it('audits and sets X-Entity-Ref-ID when SeminarReminder is sent', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(2)->create(['scheduled_at' => now()->addDay()]);

        $mail = new SeminarReminder($user, $seminars);
        $expected = 'seminar-reminder:'.$user->id.':'.$seminars->pluck('id')->sort()->values()->implode('-').':'.now()->format('Ymd');
        expect($mail->headers()->text['X-Entity-Ref-ID'])->toBe($expected);

        Mail::to($user)->send($mail);

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->latest('id')->first();
        expect($log->event_data['ref_id'])->toBe($expected);
    });

    it('audits and sets X-Entity-Ref-ID when SeminarRescheduled is sent', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addWeek()]);
        $old = now()->subWeek()->toDateTime();

        $mail = new SeminarRescheduled($user, $seminar, $old);
        $expected = 'seminar-rescheduled:'.$seminar->id.':'.$user->id.':'.$old->getTimestamp();
        expect($mail->headers()->text['X-Entity-Ref-ID'])->toBe($expected);

        Mail::to($user)->send($mail);

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->latest('id')->first();
        expect($log->event_data['ref_id'])->toBe($expected);
        expect($log->auditable_type)->toBe($seminar->getMorphClass());
        expect($log->auditable_id)->toBe($seminar->id);
    });

    it('audits and sets X-Entity-Ref-ID when BugReportMail is sent', function () {
        config(['mail.bug_report_email' => 'bugs@example.com']);

        $mail = new BugReportMail('Crash', 'Body', 'Alice', 'alice@example.com', []);
        expect($mail->headers()->text['X-Entity-Ref-ID'])->toStartWith('bug-report:');

        Mail::to('bugs@example.com')->send($mail);

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->latest('id')->first();
        expect($log->event_data['reporter_email'])->toBe('alice@example.com');
        expect($log->event_data['ref_id'])->toBe($mail->refId);
    });

    it('audits and sets X-Entity-Ref-ID when ReportReady is sent', function () {
        $user = User::factory()->create();

        $mail = new ReportReady('Monthly Report', 'https://example.com/r.zip');
        expect($mail->headers()->text['X-Entity-Ref-ID'])->toStartWith('report-ready:');

        Mail::to($user->email)->send($mail);

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->latest('id')->first();
        expect($log->event_data['report_name'])->toBe('Monthly Report');
        expect($log->event_data['ref_id'])->toBe($mail->refId);
    });

    it('does not audit when ResetPassword toMail is called directly without a send', function () {
        $user = User::factory()->create();

        (new ResetPassword('token-xyz'))->toMail($user);

        expect(AuditLog::forEvent(AuditEvent::EmailSent)->exists())->toBeFalse();
    });

    it('audits ResetPassword notification after it is actually sent', function () {
        Mail::fake();

        $user = User::factory()->create();
        $notification = new ResetPassword('token-xyz');

        $user->notify($notification);

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->latest('id')->first();
        expect($log)->not->toBeNull();
        expect($log->event_data['mail'])->toBe(ResetPassword::class);
        expect($log->event_data['to'])->toBe($user->email);
        expect($log->event_data['ref_id'])->toBe($notification->refId);
    });

    it('skips ResetPassword audit when the feature flag is disabled', function () {
        config(['features.email_audit.enabled' => false]);
        Mail::fake();

        $user = User::factory()->create();
        $user->notify(new ResetPassword('token-xyz'));

        expect(AuditLog::forEvent(AuditEvent::EmailSent)->exists())->toBeFalse();
    });

    it('adds the X-Entity-Ref-ID header to the outgoing symfony message', function () {
        $user = User::factory()->create();
        $notification = new ResetPassword('token-xyz');

        $message = $notification->toMail($user);
        $email = new Email;
        foreach ($message->callbacks as $callback) {
            $callback($email);
        }

        expect($email->getHeaders()->get('X-Entity-Ref-ID')->getBodyAsString())
            ->toBe($notification->refId);
    });

    it('uses singular subject and ref id when EvaluationReminder has a single seminar', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();

        $mail = new EvaluationReminder($user, collect([$seminar]));

        expect($mail->envelope()->subject)->toStartWith('Avalie o seminário que você participou');
        expect($mail->headers()->text['X-Entity-Ref-ID'])->toContain(':'.$seminar->id.':');
    });

    it('uses singular subject when SeminarReminder has a single seminar', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDay()]);

        $mail = new SeminarReminder($user, collect([$seminar]));

        expect($mail->envelope()->subject)->toStartWith('Lembrete: Seminário amanhã!');
    });

    it('returns no attachments when SeminarRescheduled has no scheduled_at', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDay()]);
        $seminar->scheduled_at = null;

        $mail = new SeminarRescheduled($user, $seminar, now()->subWeek()->toDateTime());

        expect($mail->attachments())->toBe([]);
    });
});

describe('AuditResetPasswordNotificationSent listener guards', function () {
    beforeEach(function () {
        config(['features.email_audit.enabled' => true]);
    });

    it('ignores notifications that are not ResetPassword', function () {
        $user = User::factory()->create();

        $other = new class extends BaseNotification
        {
            public function via(object $notifiable): array
            {
                return ['mail'];
            }
        };

        (new AuditResetPasswordNotificationSent)->handle(
            new NotificationSent($user, $other, 'mail'),
        );

        expect(AuditLog::forEvent(AuditEvent::EmailSent)->exists())->toBeFalse();
    });

    it('ignores non-mail channels', function () {
        $user = User::factory()->create();

        (new AuditResetPasswordNotificationSent)->handle(
            new NotificationSent($user, new ResetPassword('tok'), 'database'),
        );

        expect(AuditLog::forEvent(AuditEvent::EmailSent)->exists())->toBeFalse();
    });

    it('records the audit with a null recipient when the notifiable cannot resolve an email', function () {
        (new AuditResetPasswordNotificationSent)->handle(
            new NotificationSent(new AnonymousNotifiable, new ResetPassword('tok'), 'mail'),
        );

        $log = AuditLog::forEvent(AuditEvent::EmailSent)->latest('id')->first();
        expect($log)->not->toBeNull();
        expect($log->event_data['to'])->toBeNull();
        expect($log->auditable_id)->toBeNull();
    });
});
