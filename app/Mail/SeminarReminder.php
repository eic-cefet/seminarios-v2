<?php

namespace App\Mail;

use App\Models\Seminar;
use App\Models\User;
use App\Services\IcsGenerationService;
use App\Support\SeminarPluralDescriptor;
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

        if ($count === 1) {
            $seminar = $this->seminars->first();
            $subject = "Lembrete: {$seminar->typeName()} amanhã!";
        } else {
            $desc = SeminarPluralDescriptor::for($this->seminars);
            $subject = "Lembrete: {$count} {$desc->noun} amanhã!";
        }

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
        $count = $this->seminars->count();
        $desc = SeminarPluralDescriptor::for($this->seminars);
        $singleSeminar = $count === 1 ? $this->seminars->first() : null;

        return new Content(
            markdown: 'emails.seminar-reminder',
            with: [
                'userName' => $this->user->name,
                'seminars' => $this->seminars,
                'count' => $count,
                'singleSeminar' => $singleSeminar,
                'collectionDescriptor' => $desc,
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

    protected function refId(): string
    {
        $seminarIds = $this->seminars->pluck('id')->sort()->values()->implode('-');

        return 'seminar-reminder:'.$this->user->id.':'.$seminarIds.':'.now()->format('Ymd');
    }
}
