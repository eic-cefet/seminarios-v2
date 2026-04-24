<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AccountDeletionConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public string $refId;

    public function __construct(
        public User $user,
        public string $confirmUrl,
        public Carbon $expiresAt,
    ) {
        $this->refId = 'deletion-confirmation:'.(string) Str::uuid();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirme a exclusão da sua conta');
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'X-Entity-Ref-ID' => $this->refId,
            'X-Mail-Class' => self::class,
        ]);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.account-deletion-confirmation',
            with: [
                'userName' => $this->user->name,
                'confirmUrl' => $this->confirmUrl,
                'expiresAtFormatted' => $this->expiresAt->format('d/m/Y H:i'),
            ],
        );
    }
}
