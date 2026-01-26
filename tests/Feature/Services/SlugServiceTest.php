<?php

use App\Models\Seminar;
use App\Services\SlugService;

describe('SlugService', function () {
    beforeEach(function () {
        $this->service = new SlugService;
    });

    it('generates slug from name', function () {
        $slug = $this->service->generateUnique('My Test Seminar', Seminar::class);

        expect($slug)->toBe('my-test-seminar');
    });

    it('generates unique slug when collision exists', function () {
        Seminar::factory()->create(['slug' => 'existing-seminar']);

        $slug = $this->service->generateUnique('Existing Seminar', Seminar::class);

        expect($slug)->toBe('existing-seminar-1');
    });

    it('increments counter for multiple collisions', function () {
        Seminar::factory()->create(['slug' => 'popular-topic']);
        Seminar::factory()->create(['slug' => 'popular-topic-1']);
        Seminar::factory()->create(['slug' => 'popular-topic-2']);

        $slug = $this->service->generateUnique('Popular Topic', Seminar::class);

        expect($slug)->toBe('popular-topic-3');
    });

    it('excludes specific id when checking uniqueness', function () {
        $seminar = Seminar::factory()->create(['slug' => 'my-seminar']);

        $slug = $this->service->generateUnique('My Seminar', Seminar::class, 'slug', $seminar->id);

        expect($slug)->toBe('my-seminar');
    });

    it('handles special characters in name', function () {
        $slug = $this->service->generateUnique('Café & Código: Introdução!', Seminar::class);

        expect($slug)->toBe('cafe-codigo-introducao');
    });

    it('uses custom column for slug check', function () {
        $slug = $this->service->generateUnique('Test Name', Seminar::class, 'slug');

        expect($slug)->toBe('test-name');
    });

    it('handles empty string gracefully', function () {
        $slug = $this->service->generateUnique('', Seminar::class);

        expect($slug)->toBe('');
    });

    it('handles unicode characters', function () {
        $slug = $this->service->generateUnique('Programação Avançada', Seminar::class);

        expect($slug)->toBe('programacao-avancada');
    });
});
