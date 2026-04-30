<?php

use App\Http\Resources\External\ExternalSeminarTypeResource;
use App\Models\SeminarType;
use Illuminate\Http\Request;

it('serialises a seminar type to the canonical external shape', function () {
    $type = SeminarType::factory()->make(['id' => 4, 'name' => 'TCC']);

    expect((new ExternalSeminarTypeResource($type))->toArray(new Request))
        ->toBe(['id' => 4, 'name' => 'TCC']);
});
