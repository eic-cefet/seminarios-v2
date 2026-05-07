<?php

use App\Enums\Gender;
use App\Models\Seminar;
use App\Models\SeminarType;
use App\Support\SeminarPluralDescriptor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses the single type plural and gender when all seminars share one type', function () {
    $type = SeminarType::factory()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
        'gender' => Gender::Feminine,
    ]);
    $seminars = Seminar::factory()->count(3)->for($type, 'seminarType')->create();

    $desc = SeminarPluralDescriptor::for($seminars->load('seminarType'));

    expect($desc->noun)->toBe('dissertações');
    expect($desc->gender)->toBe(Gender::Feminine);
});

it('falls back to "apresentações" (feminine) when the collection has multiple types', function () {
    $masc = SeminarType::factory()->create([
        'name' => 'Seminário',
        'name_plural' => 'Seminários',
        'gender' => Gender::Masculine,
    ]);
    $fem = SeminarType::factory()->create([
        'name' => 'Dissertação',
        'name_plural' => 'Dissertações',
        'gender' => Gender::Feminine,
    ]);
    $seminars = collect([
        Seminar::factory()->for($masc, 'seminarType')->create(),
        Seminar::factory()->for($fem, 'seminarType')->create(),
    ])->each->load('seminarType');

    $desc = SeminarPluralDescriptor::for($seminars);

    expect($desc->noun)->toBe('apresentações');
    expect($desc->gender)->toBe(Gender::Feminine);
});

it('falls back to "apresentações" when any seminar has a null type', function () {
    $type = SeminarType::factory()->create();
    $seminars = collect([
        Seminar::factory()->for($type, 'seminarType')->create(),
        Seminar::factory()->create(['seminar_type_id' => null]),
    ])->each->load('seminarType');

    $desc = SeminarPluralDescriptor::for($seminars);

    expect($desc->noun)->toBe('apresentações');
    expect($desc->gender)->toBe(Gender::Feminine);
});

it('falls back to "apresentações" for an empty collection', function () {
    $desc = SeminarPluralDescriptor::for(collect());

    expect($desc->noun)->toBe('apresentações');
    expect($desc->gender)->toBe(Gender::Feminine);
});

it('exposes ifMasculine using the descriptor gender', function () {
    $type = SeminarType::factory()->masculine()->create([
        'name' => 'Painel',
        'name_plural' => 'Painéis',
    ]);
    $seminars = Seminar::factory()->count(2)->for($type, 'seminarType')->create();

    $desc = SeminarPluralDescriptor::for($seminars->load('seminarType'));

    expect($desc->ifMasculine('nos', 'nas'))->toBe('nos');
    expect($desc->noun)->toBe('painéis');
});

it('falls back to "apresentações" when the single shared type has no name_plural', function () {
    $type = SeminarType::factory()->masculine()->create([
        'name' => 'Minicurso',
        'name_plural' => null,
    ]);
    $seminars = Seminar::factory()->count(3)->for($type, 'seminarType')->create();

    $desc = SeminarPluralDescriptor::for($seminars->load('seminarType'));

    expect($desc->noun)->toBe('apresentações');
    expect($desc->gender)->toBe(Gender::Feminine);
});
