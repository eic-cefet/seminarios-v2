<?php

use App\Services\IpHasher;

beforeEach(function () {
    config(['audit.hash_salt' => 'test-salt-2026']);
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

it('throws when neither audit.hash_salt nor app.key is set', function () {
    config(['audit.hash_salt' => '', 'app.key' => '']);

    expect(fn () => (new IpHasher)->hash('192.168.1.1'))
        ->toThrow(RuntimeException::class, 'audit.hash_salt is empty');
});

it('falls back to app.key with the base64 prefix stripped', function () {
    config(['audit.hash_salt' => '', 'app.key' => 'base64:c29tZS1zZWNyZXQ=']);

    $hasher = new IpHasher;

    // hash_hmac with the decoded key prefix stripped should match
    // an explicit hash using that same trailing string as salt.
    config(['audit.hash_salt' => 'c29tZS1zZWNyZXQ=']);
    $expected = $hasher->hash('192.168.1.10');

    config(['audit.hash_salt' => '', 'app.key' => 'base64:c29tZS1zZWNyZXQ=']);
    expect($hasher->hash('192.168.1.10'))->toBe($expected);
});
