<?php

use App\Http\Resources\SeminarTypeResource;
use App\Models\SeminarType;

it('exposes gender and the sentence noun for a known type', function () {
    $type = SeminarType::factory()->create(['name' => 'Seminário']);

    $array = (new SeminarTypeResource($type))->toArray(request());

    expect($array)->toMatchArray([
        'name' => 'Seminário',
        'gender' => 'm',
        'noun' => 'seminário',
    ]);
});

it('exposes the neutral feminine fallback for a custom type', function () {
    $type = SeminarType::factory()->create(['name' => 'Palestra']);

    $array = (new SeminarTypeResource($type))->toArray(request());

    expect($array)->toMatchArray(['gender' => 'f', 'noun' => 'apresentação']);
});
