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

it('does not collide when key contains the scope separator', function () {
    $store = new IdempotencyStore;
    $store->put('1', '2:foo', ['request_hash' => 'a', 'status' => 201, 'body' => '{"a":1}', 'headers' => []]);
    $store->put('1:2', 'foo', ['request_hash' => 'b', 'status' => 201, 'body' => '{"b":2}', 'headers' => []]);

    expect($store->get('1', '2:foo')['body'])->toBe('{"a":1}');
    expect($store->get('1:2', 'foo')['body'])->toBe('{"b":2}');
});
