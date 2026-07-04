<?php

use App\Models\Seminar;
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
