<?php

use App\Models\Seminar;
use App\Models\SeminarSlugHistory;

describe('SeminarSlugObserver', function () {
    it('records the old slug in history when the slug changes', function () {
        $seminar = Seminar::factory()->create(['slug' => 'titulo-antigo']);

        $seminar->update(['slug' => 'titulo-novo']);

        expect(SeminarSlugHistory::query()->where('slug', 'titulo-antigo')->value('seminar_id'))
            ->toBe($seminar->id);
    });

    it('does not record history when the slug is unchanged', function () {
        $seminar = Seminar::factory()->create(['slug' => 'estavel']);

        $seminar->update(['description' => 'Nova descrição']);

        expect(SeminarSlugHistory::query()->count())->toBe(0);
    });

    it('retires a history row when its slug becomes current again', function () {
        $seminar = Seminar::factory()->create(['slug' => 'original']);
        $seminar->update(['slug' => 'renomeado']);
        $seminar->update(['slug' => 'original']);

        expect(SeminarSlugHistory::query()->where('slug', 'original')->exists())->toBeFalse()
            ->and(SeminarSlugHistory::query()->where('slug', 'renomeado')->value('seminar_id'))->toBe($seminar->id);
    });

    it('accumulates every previously used slug', function () {
        $seminar = Seminar::factory()->create(['slug' => 'v1']);
        $seminar->update(['slug' => 'v2']);
        $seminar->update(['slug' => 'v3']);

        expect(SeminarSlugHistory::query()->pluck('slug')->sort()->values()->all())
            ->toBe(['v1', 'v2']);
    });

    it('re-points a history slug that another seminar previously used', function () {
        $first = Seminar::factory()->create(['slug' => 'compartilhado']);
        $first->update(['slug' => 'primeiro-renomeado']);

        $second = Seminar::factory()->create(['slug' => 'segundo']);
        $second->forceFill(['slug' => 'compartilhado'])->save();
        $second->update(['slug' => 'segundo-de-novo']);

        expect(SeminarSlugHistory::query()->where('slug', 'compartilhado')->value('seminar_id'))
            ->toBe($second->id);
    });
});
