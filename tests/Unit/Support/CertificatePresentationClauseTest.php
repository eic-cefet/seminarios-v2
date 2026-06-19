<?php

use App\Support\CertificatePresentationClause;

it('returns the singular clause for each known type', function (string $type, string $expected) {
    expect(CertificatePresentationClause::for($type))->toBe($expected);
})->with([
    ['Seminário', 'ao seminário'],
    ['Dissertação', 'à dissertação'],
    ['Doutorado', 'ao doutorado'],
    ['Painel', 'ao painel'],
    ['Qualificação', 'à qualificação'],
    ['Aula inaugural', 'à aula inaugural'],
    ['TCC', 'ao TCC'],
]);

it('returns the plural clause for each known type', function (string $type, string $expected) {
    expect(CertificatePresentationClause::for($type, plural: true))->toBe($expected);
})->with([
    ['Seminário', 'aos seminários'],
    ['Dissertação', 'às dissertações'],
    ['Doutorado', 'aos doutorados'],
    ['Painel', 'aos painéis'],
    ['Qualificação', 'às qualificações'],
    ['Aula inaugural', 'às aulas inaugurais'],
    ['TCC', 'aos TCCs'],
]);

it('falls back to "à apresentação" for null, unknown, or custom names', function () {
    expect(CertificatePresentationClause::for(null))->toBe('à apresentação');
    expect(CertificatePresentationClause::for('Workshop'))->toBe('à apresentação');
    expect(CertificatePresentationClause::for(''))->toBe('à apresentação');
});

it('falls back to "às apresentações" in plural for unmapped names', function () {
    expect(CertificatePresentationClause::for(null, plural: true))->toBe('às apresentações');
    expect(CertificatePresentationClause::for('Workshop', plural: true))->toBe('às apresentações');
});
