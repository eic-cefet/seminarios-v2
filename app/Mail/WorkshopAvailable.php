<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Workshop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class WorkshopAvailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Workshop $workshop,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Novo workshop disponível: '.$this->workshop->name.' - '.config('mail.name'),
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Entity-Ref-ID' => $this->refId(),
                'X-Mail-Class' => self::class,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.workshop-available',
            with: [
                'userName' => $this->user->name,
                'workshop' => $this->workshop,
            ],
        );
    }

    protected function refId(): string
    {
        return "workshop-available:{$this->workshop->id}:{$this->user->id}";
    }
}
