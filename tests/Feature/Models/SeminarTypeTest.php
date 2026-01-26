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
});
