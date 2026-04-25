<?php

use App\Models\Seminar;
use App\Services\SeminarQueryService;

it('exposes canonical relation lists', function (): void {
    expect(SeminarQueryService::LIST_RELATIONS)->toEqualCanonicalizing([
        'seminarType', 'subjects', 'speakers.speakerData', 'seminarLocation',
    ]);

    expect(SeminarQueryService::DETAIL_RELATIONS)->toEqualCanonicalizing([
        'seminarType', 'subjects', 'speakers.speakerData', 'seminarLocation', 'workshop',
    ]);
});

it('eager-loads list relations, registrations count and ratings avg on a query', function (): void {
    $seminar = Seminar::factory()->hasSubjects(2)->create();

    $loaded = (new SeminarQueryService)->forList(Seminar::query())->find($seminar->id);

    expect($loaded->relationLoaded('subjects'))->toBeTrue()
        ->and($loaded->relationLoaded('seminarType'))->toBeTrue()
        ->and($loaded->registrations_count)->toBe(0)
        ->and($loaded->ratings_avg_score)->toBeNull();
});

it('eager-loads detail relations, registrations count and ratings avg on a query', function (): void {
    $seminar = Seminar::factory()->create();

    $loaded = (new SeminarQueryService)->forDetail(Seminar::query())->find($seminar->id);

    expect($loaded->relationLoaded('workshop'))->toBeTrue()
        ->and($loaded->relationLoaded('seminarLocation'))->toBeTrue()
        ->and($loaded->registrations_count)->toBe(0)
        ->and($loaded->ratings_avg_score)->toBeNull();
});
