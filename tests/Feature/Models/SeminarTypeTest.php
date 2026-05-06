<?php

use App\Models\Seminar;
use App\Models\SeminarType;

describe('SeminarType Model', function () {
    it('has seminars relationship', function () {
        $seminarType = SeminarType::factory()->create();
        $seminar = Seminar::factory()->create([
            'seminar_type_id' => $seminarType->id,
        ]);

        expect($seminarType->seminars)->toHaveCount(1);
        expect($seminarType->seminars->first()->id)->toBe($seminar->id);
    });

    it('can create seminar type with factory', function () {
        $seminarType = SeminarType::factory()->create([
            'name' => 'Workshop',
        ]);

        expect($seminarType->name)->toBe('Workshop');
        expect($seminarType->exists)->toBeTrue();
    });

    it('has fillable name attribute', function () {
        $seminarType = SeminarType::create([
            'name' => 'Palestra',
        ]);

        expect($seminarType->name)->toBe('Palestra');
    });

    it('exposes ifMasculine that picks the masculine variant for a masculine type', function () {
        $type = SeminarType::factory()->masculine()->create();

        expect($type->ifMasculine('Novo', 'Nova'))->toBe('Novo');
    });

    it('exposes ifMasculine that picks the feminine variant for a feminine type', function () {
        $type = SeminarType::factory()->feminine()->create();

        expect($type->ifMasculine('Novo', 'Nova'))->toBe('Nova');
    });

    it('inlineName returns the name lowercased for regular nouns', function () {
        $type = SeminarType::factory()->create(['name' => 'Dissertação']);

        expect($type->inlineName())->toBe('dissertação');
    });

    it('inlineName preserves acronyms (all-uppercase alpha)', function () {
        $type = SeminarType::factory()->create(['name' => 'TCC']);

        expect($type->inlineName())->toBe('TCC');
    });

    it('inlinePlural uses the lowercased name_plural when set', function () {
        $type = SeminarType::factory()->create([
            'name' => 'Dissertação',
            'name_plural' => 'Dissertações',
        ]);

        expect($type->inlinePlural())->toBe('dissertações');
    });

    it('inlinePlural falls back to inlineName when name_plural is null', function () {
        $type = SeminarType::factory()->create([
            'name' => 'TCC',
            'name_plural' => null,
        ]);

        expect($type->inlinePlural())->toBe('TCC');
    });
});
