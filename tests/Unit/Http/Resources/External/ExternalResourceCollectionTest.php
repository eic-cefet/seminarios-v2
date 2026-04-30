<?php

use App\Http\Resources\External\ExternalLocationResource;
use App\Http\Resources\External\ExternalResourceCollection;
use App\Models\SeminarLocation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

it('emits the canonical {data, meta} envelope without links', function () {
    $items = SeminarLocation::factory()->count(3)->make();
    $paginator = new LengthAwarePaginator($items, 30, 15, 2, ['path' => '/api/external/v1/locations']);

    $payload = (new ExternalResourceCollection($paginator, ExternalLocationResource::class))
        ->response(new Request)
        ->getData(true);

    expect($payload)->toHaveKeys(['data', 'meta']);
    expect($payload)->not->toHaveKey('links');
    expect($payload['meta'])->toMatchArray([
        'current_page' => 2,
        'last_page' => 2,
        'per_page' => 15,
        'total' => 30,
        'from' => 16,
        'to' => 18,
    ]);
});
