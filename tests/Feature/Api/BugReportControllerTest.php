<?php

use App\Mail\BugReportMail;
use Illuminate\Support\Facades\Mail;

it('sends bug report email with valid data', function () {
    Mail::fake();

    $response = $this->postJson('/api/bug-report', [
        'subject' => 'Test Bug Report',
        'message' => 'This is a test bug report message.',
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $response->assertSuccessful()
        ->assertJson(['message' => 'Bug report enviado com sucesso!']);

    Mail::assertSent(BugReportMail::class);
});

it('sends bug report without optional name and email', function () {
    Mail::fake();

    $response = $this->postJson('/api/bug-report', [
        'subject' => 'Anonymous Bug Report',
        'message' => 'This is an anonymous bug report.',
    ]);

    $response->assertSuccessful()
        ->assertJson(['message' => 'Bug report enviado com sucesso!']);

    Mail::assertSent(BugReportMail::class);
});

it('validates required fields', function () {
    $response = $this->postJson('/api/bug-report', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['subject', 'message']);
});

it('validates subject is not too long', function () {
    $response = $this->postJson('/api/bug-report', [
        'subject' => str_repeat('a', 256),
        'message' => 'Valid message',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['subject']);
});

it('validates email format', function () {
    $response = $this->postJson('/api/bug-report', [
        'subject' => 'Test',
        'message' => 'Test message',
        'email' => 'invalid-email',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
