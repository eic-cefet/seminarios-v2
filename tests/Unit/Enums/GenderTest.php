<?php

use App\Enums\Gender;

it('exposes Masculine and Feminine cases with string values', function () {
    expect(Gender::Masculine->value)->toBe('masculine');
    expect(Gender::Feminine->value)->toBe('feminine');
});

it('returns the masculine value for masculine gender', function () {
    expect(Gender::Masculine->ifMasculine('Novo', 'Nova'))->toBe('Novo');
});

it('returns the feminine value for feminine gender', function () {
    expect(Gender::Feminine->ifMasculine('Novo', 'Nova'))->toBe('Nova');
});

it('preserves whatever values are passed (works with non-string types via generics-by-value)', function () {
    expect(Gender::Masculine->ifMasculine('o', 'a'))->toBe('o');
    expect(Gender::Feminine->ifMasculine('do seminário', 'da dissertação'))->toBe('da dissertação');
});
