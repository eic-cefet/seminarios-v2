<?php

use App\Http\Resources\External\ExternalCursorResourceCollection;
use App\Http\Resources\External\ExternalLocationResource;
use App\Models\SeminarLocation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;

it('emits per_page, next_cursor, prev_cursor, has_more', function () {
    $items = SeminarLocation::factory()->count(3)->make();
    $paginator = new CursorPaginator(
        $items,
        2,
        new Cursor(['id' => 5]),
        ['path' => '/api/external/v1/locations']
    );

    $payload = (new ExternalCursorResourceCollection($paginator, ExternalLocationResource::class))
        ->response(new Request)
        ->getData(true);

    expect($payload)->toHaveKeys(['data', 'meta']);
    expect(array_keys($payload['meta']))->toEqualCanonicalizing(['per_page', 'next_cursor', 'prev_cursor', 'has_more']);
});

it('omits the meta envelope when the resource is not a cursor paginator', function () {
    $items = SeminarLocation::factory()->count(2)->make();

    $payload = (new ExternalCursorResourceCollection($items, ExternalLocationResource::class))
        ->response(new Request)
        ->getData(true);

    expect($payload)->toHaveKey('data');
    expect($payload)->not->toHaveKey('meta');
    expect($payload)->not->toHaveKey('links');
});
