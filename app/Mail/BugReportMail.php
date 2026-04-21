<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class BugReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $refId;

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function __construct(
        public string $reportSubject,
        public string $message,
        public ?string $reporterName,
        public ?string $reporterEmail,
        public array $files = [],
    ) {
        $this->refId = 'bug-report:'.(string) Str::uuid();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Bug Report] '.$this->reportSubject,
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Entity-Ref-ID' => $this->refId,
                'X-Mail-Class' => self::class,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bug-report',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return collect($this->files)
            ->map(fn (UploadedFile $file) => Attachment::fromPath($file->getRealPath())
                ->as($file->getClientOriginalName())
                ->withMime($file->getMimeType()))
            ->all();
    }
}
