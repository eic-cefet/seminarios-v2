<?php

namespace App\Mail;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Models\Seminar;
use App\Models\User;
use App\Services\FeatureFlags;
use App\Services\IcsGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SeminarReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected ?string $cachedRefId = null;

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
            ? 'Lembrete: Seminário amanhã!'
            : "Lembrete: {$count} seminários amanhã!";

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
            markdown: 'emails.seminar-reminder',
            with: [
                'userName' => $this->user->name,
                'seminars' => $this->seminars,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $icsService = app(IcsGenerationService::class);
        $attachments = [];

        foreach ($this->seminars as $seminar) {
            if ($seminar->scheduled_at) {
                $icsContent = $icsService->generateForSeminar($seminar);
                $filename = 'seminario-'.($seminar->slug ?? $seminar->id).'.ics';

                $attachments[] = Attachment::fromData(fn () => $icsContent, $filename)
                    ->withMime('text/calendar');
            }
        }

        return $attachments;
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
        if ($this->cachedRefId !== null) {
            return $this->cachedRefId;
        }

        $seminarIds = $this->seminars->pluck('id')->sort()->values()->implode('-');

        return $this->cachedRefId = 'seminar-reminder:'.$this->user->id.':'.$seminarIds.':'.now()->format('Ymd');
    }
}
