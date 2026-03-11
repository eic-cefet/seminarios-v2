<?php

use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Services\IcsGenerationService;

describe('IcsGenerationService', function () {
    it('generates valid ics structure', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'name' => 'Test Seminar',
            'description' => 'A description',
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('BEGIN:VCALENDAR');
        expect($ics)->toContain('END:VCALENDAR');
        expect($ics)->toContain('BEGIN:VEVENT');
        expect($ics)->toContain('END:VEVENT');
    });

    it('sets correct uid format', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);
        $host = parse_url(config('app.url'), PHP_URL_HOST);

        expect($ics)->toContain('seminar-'.$seminar->id.'@'.$host);
    });

    it('sets 1 hour duration', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => '2026-03-15 10:00:00',
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('20260315T100000');
        expect($ics)->toContain('20260315T110000');
        expect($ics)->not->toContain('20260315T120000');
    });

    it('uses america/sao_paulo timezone', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('America/Sao_Paulo');
    });

    it('includes seminar name as summary', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'name' => 'AI Workshop 2026',
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('SUMMARY:AI Workshop 2026');
    });

    it('includes status confirmed', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('STATUS:CONFIRMED');
    });

    it('includes location when seminar location is set', function () {
        $location = SeminarLocation::factory()->create([
            'name' => 'Room 101',
        ]);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'seminar_location_id' => $location->id,
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('Room 101');
    });

    it('always includes location from factory', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('LOCATION');
    });

    it('includes description and strips html tags', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'description' => '<p>Seminar about <strong>AI</strong></p>',
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('DESCRIPTION');
        expect($ics)->toContain('Seminar about AI');
        expect($ics)->not->toContain('<p>');
        expect($ics)->not->toContain('<strong>');
    });

    it('includes room link in description when available', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'description' => 'Test seminar',
            'room_link' => 'https://meet.example.com/room123',
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        // Unfold ICS line continuations before checking
        $unfolded = preg_replace("/\r\n[ \t]/", '', $ics);

        expect($unfolded)->toContain('Link de acesso: https://meet.example.com/room123');
    });

    it('handles room link without description', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'description' => null,
            'room_link' => 'https://meet.example.com/room',
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        // Unfold ICS line continuations before checking
        $unfolded = preg_replace("/\r\n[ \t]/", '', $ics);

        expect($unfolded)->toContain('Link de acesso: https://meet.example.com/room');
    });

    it('handles null description', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'description' => null,
            'room_link' => null,
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->not->toContain('DESCRIPTION');
    });

    it('includes seminar url', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'slug' => 'my-seminar',
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('/seminario/my-seminar');
    });

    it('throws exception when seminar has no scheduled date', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);
        $seminar->scheduled_at = null;

        expect(fn () => app(IcsGenerationService::class)->generateForSeminar($seminar))
            ->toThrow(\InvalidArgumentException::class, 'Seminar does not have a scheduled date.');
    });

    it('includes product identifier with config mail name', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('-//'.config('mail.name').'//Seminarios//PT');
    });
});
