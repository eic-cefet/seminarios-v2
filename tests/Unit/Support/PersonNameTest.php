<?php

use App\Support\PersonName;

it('returns the first word of a full name', function () {
    expect(PersonName::first('Mariana Costa Lima'))->toBe('Mariana');
});

it('returns the whole value when there is a single name', function () {
    expect(PersonName::first('Mariana'))->toBe('Mariana');
});

it('returns the first token after trimming surrounding whitespace', function () {
    expect(PersonName::first('  Mariana  Costa '))->toBe('Mariana');
});

it('returns an empty string for null', function () {
    expect(PersonName::first(null))->toBe('');
});

it('returns an empty string for an empty or whitespace-only value', function (string $value) {
    expect(PersonName::first($value))->toBe('');
})->with(['', '   ']);
