<?php

use App\Support\PresentationTypeGrammar;

it('exposes gender for known types and feminine for the fallback', function (?string $type, string $gender) {
    expect(PresentationTypeGrammar::for($type)->gender())->toBe($gender);
})->with([
    ['Seminário', 'm'],
    ['Dissertação', 'f'],
    ['Doutorado', 'm'],
    ['Painel', 'm'],
    ['Qualificação', 'f'],
    ['Aula inaugural', 'f'],
    ['TCC', 'm'],
    [null, 'f'],
    ['Workshop', 'f'],
]);

it('returns the bare noun with proper casing', function () {
    expect(PresentationTypeGrammar::for('Seminário')->noun())->toBe('seminário');
    expect(PresentationTypeGrammar::for('TCC')->noun())->toBe('TCC');
    expect(PresentationTypeGrammar::for('Aula inaugural')->noun())->toBe('aula inaugural');
    expect(PresentationTypeGrammar::for(null)->noun())->toBe('apresentação');
    expect(PresentationTypeGrammar::for('Seminário', plural: true)->noun())->toBe('seminários');
});

it('derives the definite article', function () {
    expect(PresentationTypeGrammar::for('Seminário')->definite())->toBe('o seminário');
    expect(PresentationTypeGrammar::for('Dissertação')->definite())->toBe('a dissertação');
    expect(PresentationTypeGrammar::for(null)->definite())->toBe('a apresentação');
    expect(PresentationTypeGrammar::for('Seminário', plural: true)->definite())->toBe('os seminários');
});

it('derives the "a" contraction (matches the certificate clause)', function () {
    expect(PresentationTypeGrammar::for('Seminário')->withA())->toBe('ao seminário');
    expect(PresentationTypeGrammar::for('Dissertação')->withA())->toBe('à dissertação');
    expect(PresentationTypeGrammar::for('TCC')->withA())->toBe('ao TCC');
    expect(PresentationTypeGrammar::for(null)->withA())->toBe('à apresentação');
    expect(PresentationTypeGrammar::for('Painel', plural: true)->withA())->toBe('aos painéis');
    expect(PresentationTypeGrammar::for('Aula inaugural', plural: true)->withA())->toBe('às aulas inaugurais');
});

it('derives the "em" contraction', function () {
    expect(PresentationTypeGrammar::for('Seminário')->withEm())->toBe('no seminário');
    expect(PresentationTypeGrammar::for('Dissertação')->withEm())->toBe('na dissertação');
    expect(PresentationTypeGrammar::for(null)->withEm())->toBe('na apresentação');
});

it('derives the "de" contraction', function () {
    expect(PresentationTypeGrammar::for('Seminário')->withDe())->toBe('do seminário');
    expect(PresentationTypeGrammar::for('Dissertação')->withDe())->toBe('da dissertação');
    expect(PresentationTypeGrammar::for(null)->withDe())->toBe('da apresentação');
});

it('derives the demonstrative', function () {
    expect(PresentationTypeGrammar::for('Seminário')->demonstrative())->toBe('este seminário');
    expect(PresentationTypeGrammar::for('Dissertação')->demonstrative())->toBe('esta dissertação');
    expect(PresentationTypeGrammar::for(null)->demonstrative())->toBe('esta apresentação');
});

it('agrees an adjective/participle to the type gender', function () {
    expect(PresentationTypeGrammar::for('Seminário')->agree('reagendado', 'reagendada'))->toBe('reagendado');
    expect(PresentationTypeGrammar::for('Dissertação')->agree('reagendado', 'reagendada'))->toBe('reagendada');
    expect(PresentationTypeGrammar::for(null)->agree('novo', 'nova'))->toBe('nova');
});

it('matches type names case- and accent-insensitively', function (string $type) {
    expect(PresentationTypeGrammar::for($type)->withEm())->toBe('no seminário');
})->with(['seminário', 'SEMINÁRIO', 'Seminario', '  Seminário  ']);
