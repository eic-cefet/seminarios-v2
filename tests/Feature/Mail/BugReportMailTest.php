<?php

use App\Mail\BugReportMail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

describe('BugReportMail', function () {
    it('has correct subject with bug report prefix', function () {
        $mail = new BugReportMail(
            reportSubject: 'Login not working',
            message: 'Cannot login',
            reporterName: 'John',
            reporterEmail: 'john@example.com'
        );

        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('[Bug Report] Login not working');
    });

    it('uses markdown template', function () {
        $mail = new BugReportMail(
            reportSubject: 'Test',
            message: 'Test message',
            reporterName: null,
            reporterEmail: null
        );

        $content = $mail->content();

        expect($content->markdown)->toBe('emails.bug-report');
    });

    it('stores reporter information', function () {
        $mail = new BugReportMail(
            reportSubject: 'Test Subject',
            message: 'Test message body',
            reporterName: 'Jane Doe',
            reporterEmail: 'jane@example.com'
        );

        expect($mail->reportSubject)->toBe('Test Subject');
        expect($mail->message)->toBe('Test message body');
        expect($mail->reporterName)->toBe('Jane Doe');
        expect($mail->reporterEmail)->toBe('jane@example.com');
    });

    it('handles null reporter info', function () {
        $mail = new BugReportMail(
            reportSubject: 'Anonymous Report',
            message: 'Issue description',
            reporterName: null,
            reporterEmail: null
        );

        expect($mail->reporterName)->toBeNull();
        expect($mail->reporterEmail)->toBeNull();
    });

    it('returns empty attachments when no files provided', function () {
        $mail = new BugReportMail(
            reportSubject: 'Test',
            message: 'Test',
            reporterName: null,
            reporterEmail: null,
            files: []
        );

        expect($mail->attachments())->toBeEmpty();
    });

    it('attaches uploaded files', function () {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('screenshot.png', 100, 'image/png');

        $mail = new BugReportMail(
            reportSubject: 'Visual Bug',
            message: 'See attached screenshot',
            reporterName: 'User',
            reporterEmail: 'user@example.com',
            files: [$file]
        );

        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('attaches multiple files', function () {
        Storage::fake('local');

        $files = [
            UploadedFile::fake()->create('screenshot1.png', 100, 'image/png'),
            UploadedFile::fake()->create('screenshot2.jpg', 200, 'image/jpeg'),
        ];

        $mail = new BugReportMail(
            reportSubject: 'Multiple Issues',
            message: 'See attached files',
            reporterName: 'User',
            reporterEmail: 'user@example.com',
            files: $files
        );

        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(2);
    });
});
