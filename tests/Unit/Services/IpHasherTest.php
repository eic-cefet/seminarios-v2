<?php

use App\Services\IpHasher;

beforeEach(function () {
    config(['app.key' => 'base64:dGVzdC1zYWx0LTIwMjY=']);
});

it('produces deterministic hash for the same /24 network', function () {
    $hasher = new IpHasher;

    $a = $hasher->hash('192.168.1.10');
    $b = $hasher->hash('192.168.1.250');
    $c = $hasher->hash('192.168.2.10');

    expect($a)->toBe($b)->and($a)->not->toBe($c);
});

it('truncates IPv6 to /48 before hashing', function () {
    $hasher = new IpHasher;

    expect($hasher->hash('2001:db8:abcd:0001::1'))
        ->toBe($hasher->hash('2001:db8:abcd:ffff::ffff'))
        ->and($hasher->hash('2001:db8:abcd:0001::1'))
        ->not->toBe($hasher->hash('2001:db8:1234:0001::1'));
});

it('returns null for null/empty input', function () {
    $hasher = new IpHasher;

    expect($hasher->hash(null))->toBeNull()
        ->and($hasher->hash(''))->toBeNull();
});

it('hashes opaque strings deterministically', function () {
    $hasher = new IpHasher;

    $a = $hasher->hashOpaque('Mozilla/5.0 Foo');
    expect($a)->toHaveLength(64) // sha256 hex
        ->and($a)->toBe($hasher->hashOpaque('Mozilla/5.0 Foo'));
});

it('returns null when given an unparseable IP', function () {
    expect((new IpHasher)->hash('not-an-ip'))->toBeNull();
});

it('returns null when hashing an empty or null opaque value', function () {
    $hasher = new IpHasher;

    expect($hasher->hashOpaque(null))->toBeNull()
        ->and($hasher->hashOpaque(''))->toBeNull();
});

it('throws when app.key is empty', function () {
    config(['app.key' => '']);

    expect(fn () => (new IpHasher)->hash('192.168.1.1'))
        ->toThrow(RuntimeException::class, 'APP_KEY is empty');
});

it('assertConfigured throws when app.key is empty', function () {
    config(['app.key' => '']);

    expect(fn () => (new IpHasher)->assertConfigured())
        ->toThrow(RuntimeException::class, 'APP_KEY is empty');
});

it('assertConfigured returns silently when app.key is set', function () {
    expect((new IpHasher)->assertConfigured())->toBeNull();
});

it('strips the base64 prefix from app.key when deriving the salt', function () {
    config(['app.key' => 'base64:c29tZS1zZWNyZXQ=']);
    $withPrefix = (new IpHasher)->hash('192.168.1.10');

    config(['app.key' => 'c29tZS1zZWNyZXQ=']);
    $withoutPrefix = (new IpHasher)->hash('192.168.1.10');

    expect($withPrefix)->toBe($withoutPrefix);
});
