<?php

use App\Mail\CertificateGenerated;
use App\Mail\EvaluationReminder;
use App\Mail\SeminarReminder;
use App\Mail\WelcomeUser;
use App\Models\SeminarLocation;
use Illuminate\Support\Facades\Mail;

describe('SendTestEmailsCommand', function () {
    beforeEach(function () {
        Mail::fake();
        // Ensure a location exists for seminars
        SeminarLocation::factory()->create();
    });

    it('requires test email configuration', function () {
        config(['mail.test_mail' => null]);

        $this->artisan('mail:send-tests')
            ->expectsOutput('No test email configured. Set MAIL_TEST_MAIL in .env or use --to option.')
            ->assertExitCode(1);
    });

    it('sends all test emails with --to option', function () {
        $this->artisan('mail:send-tests', ['--to' => 'test@example.com'])
            ->expectsOutputToContain('Sending test emails to: test@example.com')
            ->expectsOutputToContain('Sending: Welcome User...')
            ->expectsOutputToContain('Sending: Certificate Generated...')
            ->expectsOutputToContain('Sending: Seminar Reminder...')
            ->expectsOutputToContain('Sending: Evaluation Reminder...')
            ->expectsOutputToContain('All test emails sent successfully!')
            ->assertExitCode(0);

        // ShouldQueue mailables are queued
        Mail::assertQueued(WelcomeUser::class);
        Mail::assertQueued(SeminarReminder::class);
        Mail::assertQueued(EvaluationReminder::class);
        // Non-ShouldQueue mailable is sent
        Mail::assertSent(CertificateGenerated::class);
    });

    it('sends only welcome email with --only=welcome', function () {
        $this->artisan('mail:send-tests', ['--to' => 'test@example.com', '--only' => 'welcome'])
            ->expectsOutputToContain('Sending: Welcome User...')
            ->assertExitCode(0);

        Mail::assertQueued(WelcomeUser::class);
        Mail::assertNotSent(CertificateGenerated::class);
        Mail::assertNotQueued(SeminarReminder::class);
        Mail::assertNotQueued(EvaluationReminder::class);
    });

    it('sends only certificate email with --only=certificate', function () {
        $this->artisan('mail:send-tests', ['--to' => 'test@example.com', '--only' => 'certificate'])
            ->expectsOutputToContain('Sending: Certificate Generated...')
            ->assertExitCode(0);

        Mail::assertSent(CertificateGenerated::class);
        Mail::assertNotQueued(WelcomeUser::class);
    });

    it('sends only reminder email with --only=reminder', function () {
        $this->artisan('mail:send-tests', ['--to' => 'test@example.com', '--only' => 'reminder'])
            ->expectsOutputToContain('Sending: Seminar Reminder...')
            ->assertExitCode(0);

        Mail::assertQueued(SeminarReminder::class);
        Mail::assertNotQueued(WelcomeUser::class);
    });

    it('sends only evaluation email with --only=evaluation', function () {
        $this->artisan('mail:send-tests', ['--to' => 'test@example.com', '--only' => 'evaluation'])
            ->expectsOutputToContain('Sending: Evaluation Reminder...')
            ->assertExitCode(0);

        Mail::assertQueued(EvaluationReminder::class);
        Mail::assertNotQueued(WelcomeUser::class);
    });

    it('rejects invalid email type', function () {
        $this->artisan('mail:send-tests', ['--to' => 'test@example.com', '--only' => 'invalid'])
            ->expectsOutput('Invalid email type: invalid')
            ->expectsOutputToContain('Available types:')
            ->assertExitCode(1);
    });

    it('uses configured test email when --to is not provided', function () {
        config(['mail.test_mail' => 'configured@example.com']);

        $this->artisan('mail:send-tests', ['--only' => 'welcome'])
            ->expectsOutputToContain('Sending test emails to: configured@example.com')
            ->assertExitCode(0);
    });

    it('cleans up test data after sending', function () {
        $this->artisan('mail:send-tests', ['--to' => 'test@example.com', '--only' => 'welcome'])
            ->expectsOutputToContain('Cleaning up test data...')
            ->expectsOutputToContain('Test data cleaned up')
            ->assertExitCode(0);
    });
});
