<?php

use App\Support\External\IncludesParser;

it('maps comma-separated includes via whitelist', function () {
    $map = ['workshop' => 'workshop', 'speakers' => 'speakers.speakerData'];
    expect(IncludesParser::resolve('workshop,speakers', $map))
        ->toBe(['workshop', 'speakers.speakerData']);
});

it('throws on a key outside the map', function () {
    IncludesParser::resolve('mystery', ['workshop' => 'workshop']);
})->throws(InvalidArgumentException::class);

it('returns [] for null/empty', function () {
    expect(IncludesParser::resolve(null, ['x' => 'x']))->toBe([]);
    expect(IncludesParser::resolve('', ['x' => 'x']))->toBe([]);
});

it('skips empty tokens between commas', function () {
    $map = ['workshop' => 'workshop'];
    expect(IncludesParser::resolve('workshop,,', $map))->toBe(['workshop']);
});
