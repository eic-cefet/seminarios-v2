<?php

use App\Support\SemesterRange;

it('parses a first-semester string', function () {
    $range = SemesterRange::fromString('2026.1');

    expect($range->year)->toBe(2026)
        ->and($range->half)->toBe(1)
        ->and($range->startString())->toBe('2026-01-01 00:00:00')
        ->and($range->endString())->toBe('2026-06-30 23:59:59');
});

it('parses a second-semester string', function () {
    $range = SemesterRange::fromString('2026.2');

    expect($range->year)->toBe(2026)
        ->and($range->half)->toBe(2)
        ->and($range->startString())->toBe('2026-07-01 00:00:00')
        ->and($range->endString())->toBe('2026-12-31 23:59:59');
});

it('throws on a malformed semester string', function (string $invalid) {
    expect(fn () => SemesterRange::fromString($invalid))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'word suffix' => '2026.S1',
    'dash separator' => '2026-1',
    'plain word' => 'abc',
    'invalid half' => '2026.3',
    'short year' => '26.1',
    'empty string' => '',
]);
