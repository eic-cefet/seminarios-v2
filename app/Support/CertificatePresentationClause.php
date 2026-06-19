<?php

namespace App\Support;

/**
 * Maps a presentation-type name to the certificate clause that names it with the
 * correct Portuguese article (contracted preposition + article + noun).
 *
 * Full clauses are stored per type rather than derived from grammatical gender so
 * that irregular casing and acronyms stay correct (e.g. "ao TCC", not "ao tcc").
 * Unknown / null / custom type names fall back to the neutral "à apresentação",
 * preserving the behaviour shipped in v2.39.8.
 */
class CertificatePresentationClause
{
    /**
     * @var array<string, array{singular: string, plural: string}>
     */
    private const MAP = [
        'Seminário' => ['singular' => 'ao seminário', 'plural' => 'aos seminários'],
        'Dissertação' => ['singular' => 'à dissertação', 'plural' => 'às dissertações'],
        'Doutorado' => ['singular' => 'ao doutorado', 'plural' => 'aos doutorados'],
        'Painel' => ['singular' => 'ao painel', 'plural' => 'aos painéis'],
        'Qualificação' => ['singular' => 'à qualificação', 'plural' => 'às qualificações'],
        'Aula inaugural' => ['singular' => 'à aula inaugural', 'plural' => 'às aulas inaugurais'],
        'TCC' => ['singular' => 'ao TCC', 'plural' => 'aos TCCs'],
    ];

    private const FALLBACK = ['singular' => 'à apresentação', 'plural' => 'às apresentações'];

    public static function for(?string $typeName, bool $plural = false): string
    {
        $entry = self::MAP[$typeName] ?? self::FALLBACK;

        return $plural ? $entry['plural'] : $entry['singular'];
    }
}
