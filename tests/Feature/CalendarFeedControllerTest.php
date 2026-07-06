<?php

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\User;
use Illuminate\Support\Facades\Route;

describe('GET /calendar/seminars.ics', function () {
    it('downloads a public feed with upcoming and recent-past active seminars', function () {
        Seminar::factory()->create(['active' => true, 'name' => 'EventoFuturo', 'scheduled_at' => now()->addDays(3)]);
        Seminar::factory()->create(['active' => true, 'name' => 'EventoRecente', 'scheduled_at' => now()->subDays(10)]);

        $response = $this->get('/calendar/seminars.ics');

        $response->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="seminarios-eic.ics"');

        expect($response->headers->get('Content-Type'))->toContain('text/calendar');
        expect($response->headers->get('Cache-Control'))->toContain('public');
        expect($response->headers->get('Cache-Control'))->toContain('max-age=3600');
        expect($response->getContent())
            ->toContain('BEGIN:VCALENDAR')
            ->toContain('X-WR-CALNAME')
            ->toContain('EventoFuturo')
            ->toContain('EventoRecente');
    });

    it('excludes seminars older than 30 days, inactive, and soft-deleted', function () {
        Seminar::factory()->create(['active' => true, 'name' => 'EventoMuitoAntigo', 'scheduled_at' => now()->subDays(45)]);
        Seminar::factory()->create(['active' => false, 'name' => 'EventoInativo', 'scheduled_at' => now()->addDays(3)]);
        $deleted = Seminar::factory()->create(['active' => true, 'name' => 'EventoExcluido', 'scheduled_at' => now()->addDays(4)]);
        $deleted->delete();

        $content = $this->get('/calendar/seminars.ics')->getContent();

        expect($content)
            ->not->toContain('EventoMuitoAntigo')
            ->not->toContain('EventoInativo')
            ->not->toContain('EventoExcluido');
    });

    it('orders events by scheduled date ascending', function () {
        Seminar::factory()->create(['active' => true, 'name' => 'EventoDepois', 'scheduled_at' => now()->addDays(10)]);
        Seminar::factory()->create(['active' => true, 'name' => 'EventoAntes', 'scheduled_at' => now()->addDays(2)]);

        $content = $this->get('/calendar/seminars.ics')->getContent();

        expect(strpos($content, 'EventoAntes'))->toBeLessThan(strpos($content, 'EventoDepois'));
    });

    it('prefixes event names with the seminar type', function () {
        $type = SeminarType::factory()->create(['name' => 'TCC']);
        Seminar::factory()->create(['active' => true, 'name' => 'DefesaFinal', 'scheduled_at' => now()->addDays(3), 'seminar_type_id' => $type->id]);
        Seminar::factory()->create(['active' => true, 'name' => 'EventoSemTipo', 'scheduled_at' => now()->addDays(4), 'seminar_type_id' => null]);

        $content = $this->get('/calendar/seminars.ics')->getContent();

        expect($content)
            ->toContain('[TCC] DefesaFinal')
            ->toContain('SUMMARY:EventoSemTipo');
    });

    it('returns a valid empty calendar when there are no seminars', function () {
        $response = $this->get('/calendar/seminars.ics');

        $response->assertOk();
        expect($response->getContent())
            ->toContain('BEGIN:VCALENDAR')
            ->not->toContain('BEGIN:VEVENT');
    });

    it('applies the public throttle limiter', function () {
        $route = Route::getRoutes()->getByName('calendar.public-feed');

        expect($route->middleware())->toContain('throttle:public');
    });

    it('returns 404 for unmatched calendar paths instead of serving the SPA', function () {
        $this->get('/calendar/nao-existe')->assertNotFound();
    });
});

describe('GET /calendar/personal/{token}.ics', function () {
    it('downloads the personal feed with registered seminars, past included', function () {
        $user = User::factory()->create(['calendar_feed_token' => str_repeat('a', 48)]);
        $future = Seminar::factory()->create(['active' => true, 'name' => 'MeuEventoFuturo', 'scheduled_at' => now()->addDays(2)]);
        $past = Seminar::factory()->create(['active' => true, 'name' => 'MeuEventoPassado', 'scheduled_at' => now()->subMonths(3)]);
        Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $future->id]);
        Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $past->id]);

        $response = $this->get('/calendar/personal/'.str_repeat('a', 48).'.ics');

        $response->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="minha-agenda.ics"');

        expect($response->headers->get('Content-Type'))->toContain('text/calendar');
        expect($response->headers->get('Cache-Control'))->toContain('private');
        expect($response->getContent())
            ->toContain('Minha agenda')
            ->toContain('MeuEventoFuturo')
            ->toContain('MeuEventoPassado');
    });

    it('excludes unregistered, inactive, soft-deleted, and other-user seminars', function () {
        $user = User::factory()->create(['calendar_feed_token' => str_repeat('a', 48)]);
        $other = User::factory()->create();

        Seminar::factory()->create(['active' => true, 'name' => 'EventoSemInscricao', 'scheduled_at' => now()->addDays(2)]);

        $inactive = Seminar::factory()->create(['active' => false, 'name' => 'EventoInativoMeu', 'scheduled_at' => now()->addDays(3)]);
        Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $inactive->id]);

        $deleted = Seminar::factory()->create(['active' => true, 'name' => 'EventoExcluidoMeu', 'scheduled_at' => now()->addDays(4)]);
        Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $deleted->id]);
        $deleted->delete();

        $otherSeminar = Seminar::factory()->create(['active' => true, 'name' => 'EventoDeOutro', 'scheduled_at' => now()->addDays(5)]);
        Registration::factory()->create(['user_id' => $other->id, 'seminar_id' => $otherSeminar->id]);

        $content = $this->get('/calendar/personal/'.str_repeat('a', 48).'.ics')->getContent();

        expect($content)
            ->not->toContain('EventoSemInscricao')
            ->not->toContain('EventoInativoMeu')
            ->not->toContain('EventoExcluidoMeu')
            ->not->toContain('EventoDeOutro');
    });

    it('prefixes event names with the seminar type', function () {
        $user = User::factory()->create(['calendar_feed_token' => str_repeat('a', 48)]);
        $type = SeminarType::factory()->create(['name' => 'Seminário']);
        $seminar = Seminar::factory()->create(['active' => true, 'name' => 'MeuEvento', 'scheduled_at' => now()->addDays(2), 'seminar_type_id' => $type->id]);
        Registration::factory()->create(['user_id' => $user->id, 'seminar_id' => $seminar->id]);

        $content = $this->get('/calendar/personal/'.str_repeat('a', 48).'.ics')->getContent();

        expect($content)->toContain('[Seminário] MeuEvento');
    });

    it('returns 404 for an unknown token', function () {
        $this->get('/calendar/personal/'.str_repeat('z', 48).'.ics')->assertNotFound();
    });

    it('returns 404 for a malformed token', function () {
        $this->get('/calendar/personal/abc%20def.ics')->assertNotFound();
    });

    it('applies the public throttle limiter', function () {
        $route = Route::getRoutes()->getByName('calendar.personal-feed');

        expect($route->middleware())->toContain('throttle:public');
    });
});
