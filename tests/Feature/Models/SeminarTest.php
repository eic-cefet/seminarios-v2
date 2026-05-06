<?php

use App\Models\Seminar;
use App\Models\SeminarType;

describe('gender helpers', function () {
    it('Seminar::ifMasculine delegates to seminarType', function () {
        $type = SeminarType::factory()->feminine()->create();
        $seminar = Seminar::factory()->for($type, 'seminarType')->create();

        expect($seminar->ifMasculine('Novo', 'Nova'))->toBe('Nova');
    });

    it('Seminar::ifMasculine returns masculine fallback when seminarType is null', function () {
        $seminar = Seminar::factory()->create(['seminar_type_id' => null]);

        expect($seminar->ifMasculine('Novo', 'Nova'))->toBe('Novo');
    });

    it('Seminar::inlineName uses the type name lowercased', function () {
        $type = SeminarType::factory()->create(['name' => 'Dissertação']);
        $seminar = Seminar::factory()->for($type, 'seminarType')->create();

        expect($seminar->inlineName())->toBe('dissertação');
    });

    it('Seminar::inlineName falls back to "seminário" when type is null', function () {
        $seminar = Seminar::factory()->create(['seminar_type_id' => null]);

        expect($seminar->inlineName())->toBe('seminário');
    });

    it('Seminar::typeName uses the type name in titlecase', function () {
        $type = SeminarType::factory()->create(['name' => 'Dissertação']);
        $seminar = Seminar::factory()->for($type, 'seminarType')->create();

        expect($seminar->typeName())->toBe('Dissertação');
    });

    it('Seminar::typeName preserves acronyms (e.g., TCC stays uppercase)', function () {
        $type = SeminarType::factory()->create(['name' => 'TCC']);
        $seminar = Seminar::factory()->for($type, 'seminarType')->create();

        expect($seminar->typeName())->toBe('TCC');
    });

    it('Seminar::typeName falls back to "Seminário" when type is null', function () {
        $seminar = Seminar::factory()->create(['seminar_type_id' => null]);

        expect($seminar->typeName())->toBe('Seminário');
    });
});
