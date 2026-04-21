<?php

namespace App\Mail;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\FeatureFlags;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class WelcomeUser extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bem-vindo(a) ao '.config('mail.name'),
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: ['X-Entity-Ref-ID' => 'welcome:'.$this->user->id],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',
            with: [
                'userName' => $this->user->name,
                'loginUrl' => url('/'),
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
                    'ref_id' => 'welcome:'.$this->user->id,
                ],
            );
        }

        return $result;
    }
}
