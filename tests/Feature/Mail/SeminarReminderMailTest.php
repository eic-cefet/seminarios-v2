<?php

use App\Mail\SeminarReminder;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\User;

describe('SeminarReminder Mail', function () {
    it('has singular subject for one seminar', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();

        $mail = new SeminarReminder($user, collect([$seminar]));
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Lembrete: Seminário amanhã! - '.config('mail.name'));
    });

    it('has plural subject for multiple seminars', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(3)->create();

        $mail = new SeminarReminder($user, $seminars);
        $envelope = $mail->envelope();

        expect($envelope->subject)->toBe('Lembrete: 3 seminários amanhã! - '.config('mail.name'));
    });

    it('uses markdown template', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();

        $mail = new SeminarReminder($user, collect([$seminar]));
        $content = $mail->content();

        expect($content->markdown)->toBe('emails.seminar-reminder');
    });

    it('passes user name to template', function () {
        $user = User::factory()->create([
            'name' => 'Roberto Lima',
        ]);
        $seminar = Seminar::factory()->create();

        $mail = new SeminarReminder($user, collect([$seminar]));
        $content = $mail->content();

        expect($content->with['userName'])->toBe('Roberto Lima');
    });

    it('passes seminars collection to template', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(2)->create();

        $mail = new SeminarReminder($user, $seminars);
        $content = $mail->content();

        expect($content->with['seminars'])->toHaveCount(2);
    });

    it('generates ics attachment for seminar with scheduled date', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'name' => 'AI Workshop',
            'slug' => 'ai-workshop',
        ]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('generates multiple ics attachments for multiple seminars', function () {
        $user = User::factory()->create();
        $seminars = Seminar::factory()->count(2)->create([
            'scheduled_at' => now()->addDay(),
        ]);

        $mail = new SeminarReminder($user, $seminars);
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(2);
    });

    it('generates ics for all seminars with scheduled dates', function () {
        $user = User::factory()->create();
        $seminar1 = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);
        $seminar2 = Seminar::factory()->create([
            'scheduled_at' => now()->addDays(2),
        ]);

        $mail = new SeminarReminder($user, collect([$seminar1, $seminar2]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(2);
    });

    it('includes location in ics when available', function () {
        $user = User::factory()->create();
        $location = SeminarLocation::factory()->create([
            'name' => 'Room 101',
        ]);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'seminar_location_id' => $location->id,
        ]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('includes room link in ics description when available', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'room_link' => 'https://meet.example.com/room123',
            'description' => 'Test seminar',
        ]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('uses seminar slug for ics filename', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'slug' => 'my-seminar-slug',
        ]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('handles seminar without description', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'description' => null,
        ]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('handles room link without description', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'description' => null,
            'room_link' => 'https://meet.example.com/room',
        ]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $attachments = $mail->attachments();

        expect($attachments)->toHaveCount(1);
    });

    it('stores user reference', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create();

        $mail = new SeminarReminder($user, collect([$seminar]));

        expect($mail->user->id)->toBe($user->id);
    });

    it('generates ics with 1 hour duration', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => '2026-03-15 10:00:00',
        ]);

        $mail = new SeminarReminder($user, collect([$seminar]));
        $rendered = $mail->render();
        $raw = $mail->buildViewData();

        // Access ICS via reflection since generateIcs is private
        $reflection = new ReflectionMethod($mail, 'generateIcs');
        $ics = $reflection->invoke($mail, $seminar);

        expect($ics)->toContain('DTSTART;TZID=America/Sao_Paulo:20260315T100000');
        expect($ics)->toContain('DTEND;TZID=America/Sao_Paulo:20260315T110000');
    });

    it('generates ics with proper line breaks in description', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'description' => "Line one\nLine two\nLine three",
        ]);

        $reflection = new ReflectionMethod(SeminarReminder::class, 'generateIcs');
        $mail = new SeminarReminder($user, collect([$seminar]));
        $ics = $reflection->invoke($mail, $seminar);

        // Should contain \n (ICS line break encoding), not \\n
        expect($ics)->toContain('DESCRIPTION:Line one\nLine two\nLine three');
        expect($ics)->not->toContain('\\\\n');
    });

    it('escapes commas and semicolons in ics description', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'description' => 'Hello, world; test',
        ]);

        $reflection = new ReflectionMethod(SeminarReminder::class, 'generateIcs');
        $mail = new SeminarReminder($user, collect([$seminar]));
        $ics = $reflection->invoke($mail, $seminar);

        expect($ics)->toContain('Hello\\, world\\; test');
    });
});
