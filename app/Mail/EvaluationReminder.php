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
            ? 'Avalie a apresentação que você participou'
            : "Avalie as {$count} apresentações que você participou";

        return new Envelope(
            subject: $subject.' - '.config('mail.name'),
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
            markdown: 'emails.evaluation-reminder',
            with: [
                'userName' => $this->user->name,
                'seminars' => $this->seminars,
                'evaluationUrl' => url('/avaliar'),
            ],
        );
    }

    protected function refId(): string
    {
        $seminarIds = $this->seminars->pluck('id')->sort()->values()->implode('-');

        return 'evaluation-reminder:'.$this->user->id.':'.$seminarIds.':'.now()->format('Ymd');
    }
}
