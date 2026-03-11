<?php

use App\Mail\SeminarRescheduled;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\User;
use Carbon\Carbon;

describe('SeminarRescheduled Mailable', function () {
    it('renders with old and new dates', function () {
        $user = User::factory()->create(['name' => 'João']);
        $seminar = Seminar::factory()->create([
            'name' => 'Intro to AI',
            'scheduled_at' => '2026-06-20 14:00:00',
        ]);
        $oldScheduledAt = Carbon::parse('2026-06-15 14:00:00');

        $mailable = new SeminarRescheduled($user, $seminar, $oldScheduledAt);

        $mailable->assertSeeInHtml('João');
        $mailable->assertSeeInHtml('Intro to AI');
        $mailable->assertSeeInHtml('15/06/2026');
        $mailable->assertSeeInHtml('20/06/2026');
    });

    it('has correct subject line', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'name' => 'Test Seminar',
            'scheduled_at' => now()->addDays(10),
        ]);
        $oldScheduledAt = now()->addDays(5);

        $mailable = new SeminarRescheduled($user, $seminar, $oldScheduledAt);

        $mailable->assertHasSubject('Alteração de data: Test Seminar - '.config('mail.name'));
    });

    it('includes ics attachment with correct mime type', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDays(10),
        ]);
        $oldScheduledAt = now()->addDays(5);

        $mailable = new SeminarRescheduled($user, $seminar, $oldScheduledAt);

        $attachments = $mailable->attachments();
        expect($attachments)->toHaveCount(1);
    });

    it('shows location when available', function () {
        $user = User::factory()->create();
        $location = SeminarLocation::factory()->create(['name' => 'Sala 301']);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDays(10),
            'seminar_location_id' => $location->id,
        ]);
        $oldScheduledAt = now()->addDays(5);

        $mailable = new SeminarRescheduled($user, $seminar, $oldScheduledAt);

        $mailable->assertSeeInHtml('Sala 301');
    });

    it('shows room link when available', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDays(10),
            'room_link' => 'https://meet.google.com/abc',
        ]);
        $oldScheduledAt = now()->addDays(5);

        $mailable = new SeminarRescheduled($user, $seminar, $oldScheduledAt);

        $mailable->assertSeeInHtml('https://meet.google.com/abc');
    });

    it('uses seminar id as slug fallback in button url', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDays(10),
            'slug' => null,
        ]);
        $oldScheduledAt = now()->addDays(5);

        $mailable = new SeminarRescheduled($user, $seminar, $oldScheduledAt);

        $mailable->assertSeeInHtml('/seminario/'.$seminar->id);
    });

    it('sanitizes room link in ics description by stripping CRLF', function () {
        $user = User::factory()->create();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDays(10),
            'room_link' => "https://meet.google.com/abc\r\nINJECTED:value",
        ]);
        $oldScheduledAt = now()->addDays(5);

        $mailable = new SeminarRescheduled($user, $seminar, $oldScheduledAt);

        // Use reflection to access private generateIcs and verify sanitization
        $reflection = new ReflectionMethod(SeminarRescheduled::class, 'generateIcs');
        $icsContent = $reflection->invoke($mailable, $seminar);

        expect($icsContent)->toContain('https://meet.google.com/abcINJECTED:value');
        expect($icsContent)->not->toMatch('/\r\nINJECTED/');
    });
});
