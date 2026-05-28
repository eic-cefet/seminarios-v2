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

it('accepts academic honorifics with a trailing period', function () {
    expect(validateFullName('Dra. Mariana Costa Silva'))->toBeNull();
    expect(validateFullName('Dr. João Silva'))->toBeNull();
    expect(validateFullName('Prof. Maria Souza'))->toBeNull();
    expect(validateFullName('Profa. Ana Lima'))->toBeNull();
});

it('still rejects single-letter initials with a period', function () {
    // {2,} applies to the letter run; "C." has only 1 letter so it fails.
    expect(validateFullName('Maria C. Silva'))->not->toBeNull();
});

it('still rejects empty parts and stray punctuation', function () {
    expect(validateFullName('Maria .. Silva'))->not->toBeNull();
    expect(validateFullName('M. S.'))->not->toBeNull();
});
