<?php

use App\Models\Seminar;

describe('seminar lookup via historical slug', function () {
    it('resolves the public show endpoint through a historical slug', function () {
        $seminar = Seminar::factory()->create(['slug' => 'nome-antigo', 'active' => true]);
        $seminar->update(['slug' => 'nome-novo']);

        $response = $this->getJson('/api/seminars/nome-antigo');

        $response->assertSuccessful();
        expect($response->json('data.id'))->toBe($seminar->id)
            ->and($response->json('data.slug'))->toBe('nome-novo');
    });

    it('prefers a live seminar over history when slugs collide', function () {
        $renamed = Seminar::factory()->create(['slug' => 'disputado', 'active' => true]);
        $renamed->update(['slug' => 'renomeado']);
        $current = Seminar::factory()->create(['slug' => 'disputado', 'active' => true]);

        $response = $this->getJson('/api/seminars/disputado');

        $response->assertSuccessful();
        expect($response->json('data.id'))->toBe($current->id);
    });

    it('returns 404 for a historical slug of a soft-deleted seminar', function () {
        $seminar = Seminar::factory()->create(['slug' => 'sera-apagado', 'active' => true]);
        $seminar->update(['slug' => 'apagado-depois']);
        $seminar->delete();

        $this->getJson('/api/seminars/sera-apagado')->assertNotFound();
    });

    it('returns 404 for a slug that never existed', function () {
        $this->getJson('/api/seminars/nunca-existiu')->assertNotFound();
    });

    it('resolves the calendar feed through a historical slug', function () {
        $seminar = Seminar::factory()->create(['slug' => 'com-agenda', 'active' => true]);
        $seminar->update(['slug' => 'com-agenda-novo']);

        $response = $this->get('/seminario/com-agenda/calendar.ics');

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'text/calendar; charset=UTF-8');
    });
});

describe('Seminar::resolveRouteBinding', function () {
    it('does not consult slug history when binding on a non-slug field', function () {
        $seminar = Seminar::factory()->create(['slug' => 'campo-nao-slug']);
        $seminar->update(['slug' => 'campo-nao-slug-novo']);

        $binder = new Seminar;

        expect($binder->resolveRouteBinding($seminar->id, 'id')->id)->toBe($seminar->id)
            ->and($binder->resolveRouteBinding('campo-nao-slug', 'id'))->toBeNull();
    });

    it('returns the live seminar without touching history when the slug is current', function () {
        $seminar = Seminar::factory()->create(['slug' => 'ainda-atual']);

        expect((new Seminar)->resolveRouteBinding('ainda-atual', 'slug')->id)->toBe($seminar->id);
    });
});
