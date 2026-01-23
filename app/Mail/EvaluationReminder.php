<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class EvaluationReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, \App\Models\Seminar>  $seminars
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
}
