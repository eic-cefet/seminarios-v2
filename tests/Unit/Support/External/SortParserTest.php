<?php

use App\Support\External\SortParser;

it('parses a comma-separated sort string into pairs', function () {
    $pairs = SortParser::parse('-scheduled_at,name', ['scheduled_at', 'name']);
    expect($pairs)->toBe([['scheduled_at', 'desc'], ['name', 'asc']]);
});

it('throws on a column outside the whitelist', function () {
    SortParser::parse('password', ['scheduled_at']);
})->throws(InvalidArgumentException::class);

it('returns empty on null/empty input', function () {
    expect(SortParser::parse(null, ['x']))->toBe([]);
    expect(SortParser::parse('', ['x']))->toBe([]);
});

it('skips empty tokens between commas', function () {
    $pairs = SortParser::parse('name,,', ['name']);
    expect($pairs)->toBe([['name', 'asc']]);
});
