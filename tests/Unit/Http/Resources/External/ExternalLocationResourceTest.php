<?php

use App\Http\Resources\External\ExternalLocationResource;
use App\Models\SeminarLocation;
use Illuminate\Http\Request;

it('serialises a location to the canonical external shape', function () {
    $location = SeminarLocation::factory()->make([
        'id' => 7,
        'name' => 'Auditório',
        'max_vacancies' => 200,
    ]);

    $payload = (new ExternalLocationResource($location))->toArray(new Request);

    expect($payload)->toBe([
        'id' => 7,
        'name' => 'Auditório',
        'max_vacancies' => 200,
    ]);
});
