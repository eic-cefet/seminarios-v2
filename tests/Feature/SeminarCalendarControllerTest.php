<?php

use App\Models\Seminar;

describe('GET /seminario/{slug}/calendar.ics', function () {
    it('downloads an ics file for an active seminar', function () {
        $seminar = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->addDays(2)->setTime(14, 0),
        ]);

        $response = $this->get("/seminario/{$seminar->slug}/calendar.ics");

        $response->assertOk()
            ->assertHeader(
                'Content-Disposition',
                'attachment; filename="seminario-'.$seminar->slug.'.ics"',
            );

        expect($response->headers->get('Content-Type'))->toContain('text/calendar');
        expect($response->getContent())
            ->toContain('BEGIN:VCALENDAR')
            ->toContain('BEGIN:VEVENT')
            ->toContain($seminar->name);
    });

    it('returns 404 for inactive seminars', function () {
        $seminar = Seminar::factory()->create([
            'active' => false,
        ]);

        $this->get("/seminario/{$seminar->slug}/calendar.ics")
            ->assertNotFound();
    });

    it('returns 404 for missing seminars', function () {
        $this->get('/seminario/nao-existe/calendar.ics')
            ->assertNotFound();
    });
});
