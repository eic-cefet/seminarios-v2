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

class DataExportReady extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $downloadUrl,
        public Carbon $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Seus dados estão prontos para download');
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Entity-Ref-ID' => 'data-export:'.$this->user->id,
                'X-Mail-Class' => self::class,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.data-export-ready',
            with: [
                'userName' => $this->user->name,
                'downloadUrl' => $this->downloadUrl,
                'expiresAt' => $this->expiresAt->format('d/m/Y H:i'),
            ],
        );
    }
}
