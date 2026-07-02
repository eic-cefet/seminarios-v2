<?php

use App\Models\Seminar;
use App\Models\SeminarSlugHistory;
use App\Models\Subject;
use App\Services\SlugService;

describe('SlugService with seminar slug history', function () {
    it('refuses a slug that lives in another seminar\'s history', function () {
        $other = Seminar::factory()->create();
        SeminarSlugHistory::factory()->create(['seminar_id' => $other->id, 'slug' => 'palestra-ia']);

        $slug = app(SlugService::class)->generateUnique('Palestra IA', Seminar::class);

        expect($slug)->toBe('palestra-ia-1');
    });

    it('lets a seminar reclaim its own historical slug', function () {
        $seminar = Seminar::factory()->create(['slug' => 'defesa-mestrado-atual']);
        SeminarSlugHistory::factory()->create(['seminar_id' => $seminar->id, 'slug' => 'defesa-mestrado']);

        $slug = app(SlugService::class)->generateUnique('Defesa Mestrado', Seminar::class, 'slug', $seminar->id);

        expect($slug)->toBe('defesa-mestrado');
    });

    it('does not consult seminar history for other models', function () {
        SeminarSlugHistory::factory()->create(['slug' => 'topico-livre']);

        $slug = app(SlugService::class)->generateUnique('Tópico Livre', Subject::class);

        expect($slug)->toBe('topico-livre');
    });
});
