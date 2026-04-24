<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AccountAnonymized extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $originalName) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Sua conta foi removida');
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Entity-Ref-ID' => 'account-anonymized:'.md5($this->originalName.microtime()),
                'X-Mail-Class' => self::class,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.account-anonymized',
            with: ['userName' => $this->originalName],
        );
    }
}
