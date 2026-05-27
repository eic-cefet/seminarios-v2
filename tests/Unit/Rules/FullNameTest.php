<?php

use App\Rules\FullName;
use Illuminate\Translation\PotentiallyTranslatedString;

function validateFullName(string $value): ?string
{
    $rule = new FullName;
    $error = null;
    $rule->validate('name', $value, function (string $message) use (&$error): PotentiallyTranslatedString {
        $error = $message;

        return new PotentiallyTranslatedString($message, app('translator'));
    });

    return $error;
}

it('rejects empty strings', function () {
    expect(validateFullName(''))->not->toBeNull();
});

it('rejects a single word', function () {
    expect(validateFullName('Maria'))->not->toBeNull();
});

it('rejects a single letter in the surname slot', function () {
    expect(validateFullName('Maria S'))->not->toBeNull();
});

it('accepts a simple full name', function () {
    expect(validateFullName('Maria Silva'))->toBeNull();
});

it('accepts accented names', function () {
    expect(validateFullName('João da Conceição'))->toBeNull();
});

it('accepts hyphenated and apostrophed names', function () {
    expect(validateFullName("Anne-Marie D'Souza"))->toBeNull();
});

it('rejects the literal OAuth fallback name "User"', function () {
    expect(validateFullName('User'))->not->toBeNull();
});

it('rejects numbers in name parts', function () {
    expect(validateFullName('Maria 123'))->not->toBeNull();
});

it('trims surrounding whitespace before validating', function () {
    expect(validateFullName('   Maria Silva   '))->toBeNull();
});

it('exposes a static passes() helper that mirrors the rule', function () {
    expect(FullName::passes('Maria Silva'))->toBeTrue();
    expect(FullName::passes('Maria'))->toBeFalse();
    expect(FullName::passes(''))->toBeFalse();
    expect(FullName::passes(null))->toBeFalse();
    expect(FullName::passes(['array', 'value']))->toBeFalse();
});

it('uses the shared MESSAGE constant when validation fails', function () {
    expect(validateFullName('Maria'))->toBe(FullName::MESSAGE);
});
