<?php

namespace App\Mail;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Models\Seminar;
use App\Models\User;
use App\Services\FeatureFlags;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class EvaluationReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, Seminar>  $seminars
     */
    public function __construct(
        public User $user,
        public Collection $seminars,
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->seminars->count();
        $subject = $count === 1
            ? 'Avalie o seminário que você participou'
            : "Avalie os {$count} seminários que você participou";

        return new Envelope(
            subject: $subject.' - '.config('mail.name'),
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: ['X-Entity-Ref-ID' => $this->refId()],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.evaluation-reminder',
            with: [
                'userName' => $this->user->name,
                'seminars' => $this->seminars,
                'evaluationUrl' => url('/avaliar'),
            ],
        );
    }

    public function send($mailer)
    {
        $result = parent::send($mailer);

        if (FeatureFlags::enabled('email_audit')) {
            AuditLog::record(
                AuditEvent::EmailSent,
                AuditEventType::System,
                auditable: $this->user,
                eventData: [
                    'mail' => self::class,
                    'to' => $this->user->email,
                    'ref_id' => $this->refId(),
                    'seminar_ids' => $this->seminars->pluck('id')->all(),
                ],
            );
        }

        return $result;
    }

    protected function refId(): string
    {
        $seminarIds = $this->seminars->pluck('id')->sort()->values()->implode('-');

        return 'evaluation-reminder:'.$this->user->id.':'.$seminarIds.':'.now()->format('Ymd');
    }
}
