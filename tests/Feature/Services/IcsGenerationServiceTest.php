<?php

use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\SeminarType;
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

    it('uses custom seminar duration when available', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => '2026-03-15 10:00:00',
            'duration_minutes' => 90,
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('20260315T100000');
        expect($ics)->toContain('20260315T113000');
        expect($ics)->not->toContain('20260315T110000');
    });

    it('uses america/sao_paulo timezone', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('America/Sao_Paulo');
    });

    it('includes seminar name as summary prefixed with the seminar type', function () {
        $type = SeminarType::factory()->create(['name' => 'TCC']);
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'name' => 'AI Workshop 2026',
            'seminar_type_id' => $type->id,
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('SUMMARY:[TCC] AI Workshop 2026');
    });

    it('keeps the plain name as summary when the seminar has no type', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'name' => 'AI Workshop 2026',
            'seminar_type_id' => null,
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

    it('strips markdown syntax from description', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
            'description' => "# Resumo\nFala sobre **redes** de passe.",
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);
        $unfolded = preg_replace("/\r\n[ \t]/", '', $ics);

        expect($unfolded)->toContain('Resumo Fala sobre redes de passe.');
        expect($unfolded)->not->toContain('# Resumo');
        expect($unfolded)->not->toContain('**redes**');
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
            ->toThrow(InvalidArgumentException::class, 'Seminar does not have a scheduled date.');
    });

    it('includes product identifier with config mail name', function () {
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);

        $ics = app(IcsGenerationService::class)->generateForSeminar($seminar);

        expect($ics)->toContain('-//'.config('mail.name').'//Seminarios//PT');
    });

    describe('generateForSeminars', function () {
        it('emits one calendar containing one event per seminar', function () {
            $first = Seminar::factory()->create(['scheduled_at' => now()->addDay(), 'name' => 'Primeiro Evento']);
            $second = Seminar::factory()->create(['scheduled_at' => now()->addDays(2), 'name' => 'Segundo Evento']);

            $ics = app(IcsGenerationService::class)->generateForSeminars([$first, $second], 'Agenda Teste');

            expect(substr_count($ics, 'BEGIN:VCALENDAR'))->toBe(1);
            expect(substr_count($ics, 'BEGIN:VEVENT'))->toBe(2);
            expect($ics)->toContain('Primeiro Evento');
            expect($ics)->toContain('Segundo Evento');
        });

        it('sets the calendar name', function () {
            $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDay()]);

            $ics = app(IcsGenerationService::class)->generateForSeminars([$seminar], 'Agenda Teste');

            expect($ics)->toContain('X-WR-CALNAME');
            expect($ics)->toContain('Agenda Teste');
        });

        it('keeps per-event fields intact', function () {
            $seminar = Seminar::factory()->create([
                'scheduled_at' => now()->addDay(),
                'slug' => 'evento-multi',
            ]);
            $host = parse_url(config('app.url'), PHP_URL_HOST);

            $ics = app(IcsGenerationService::class)->generateForSeminars([$seminar], 'Agenda');

            expect($ics)->toContain('seminar-'.$seminar->id.'@'.$host);
            expect($ics)->toContain('/seminario/evento-multi');
            expect($ics)->toContain('STATUS:CONFIRMED');
        });

        it('produces a valid empty calendar for no seminars', function () {
            $ics = app(IcsGenerationService::class)->generateForSeminars([], 'Agenda Vazia');

            expect($ics)->toContain('BEGIN:VCALENDAR');
            expect($ics)->not->toContain('BEGIN:VEVENT');
        });

        it('throws when a seminar in the list has no scheduled date', function () {
            $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDay()]);
            $seminar->scheduled_at = null;

            expect(fn () => app(IcsGenerationService::class)->generateForSeminars([$seminar], 'Agenda'))
                ->toThrow(InvalidArgumentException::class, 'Seminar does not have a scheduled date.');
        });
    });
});
