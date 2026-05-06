<?php

namespace App\Mail;

use App\Models\Seminar;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class NewSeminarAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Seminar $seminar,
    ) {}

    public function envelope(): Envelope
    {
        $novo = $this->seminar->ifMasculine('Novo', 'Nova');
        $noun = $this->seminar->inlineName();

        return new Envelope(
            subject: "{$novo} {$noun}: ".$this->seminar->name.' - '.config('mail.name'),
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
            markdown: 'emails.new-seminar-alert',
            with: [
                'userName' => $this->user->name,
                'seminar' => $this->seminar,
            ],
        );
    }

    protected function refId(): string
    {
        return "seminar-alert:{$this->seminar->id}:{$this->user->id}";
    }
}
