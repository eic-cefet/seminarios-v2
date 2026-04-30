<?php

use App\Support\External\IdempotencyStore;

it('stores and retrieves a record', function () {
    $store = new IdempotencyStore;
    $store->put('tok-1', 'key-1', ['request_hash' => 'abc', 'status' => 201, 'body' => '{}', 'headers' => []]);

    expect($store->get('tok-1', 'key-1'))->toMatchArray(['request_hash' => 'abc', 'status' => 201]);
});

it('returns null for unknown key', function () {
    expect((new IdempotencyStore)->get('tok-x', 'key-x'))->toBeNull();
});
