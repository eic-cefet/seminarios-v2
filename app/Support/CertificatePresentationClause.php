<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Maps a presentation-type name to the certificate clause that names it with the
 * correct Portuguese article (contracted preposition + article + noun).
 *
 * Full clauses are stored per type rather than derived from grammatical gender so
 * that irregular casing and acronyms stay correct (e.g. "ao TCC", not "ao tcc").
 * The map is keyed by the slugified type name so lookups are case- and
 * accent-insensitive ("Seminário", "seminário" and "SEMINARIO" all resolve).
 * Unknown / null / custom type names fall back to the neutral "à apresentação",
 * preserving the behaviour shipped in v2.39.8.
 */
class CertificatePresentationClause
{
    /**
     * @var array<string, array{singular: string, plural: string}>
     */
    private const MAP = [
        'seminario' => ['singular' => 'ao seminário', 'plural' => 'aos seminários'],
        'dissertacao' => ['singular' => 'à dissertação', 'plural' => 'às dissertações'],
        'doutorado' => ['singular' => 'ao doutorado', 'plural' => 'aos doutorados'],
        'painel' => ['singular' => 'ao painel', 'plural' => 'aos painéis'],
        'qualificacao' => ['singular' => 'à qualificação', 'plural' => 'às qualificações'],
        'aula-inaugural' => ['singular' => 'à aula inaugural', 'plural' => 'às aulas inaugurais'],
        'tcc' => ['singular' => 'ao TCC', 'plural' => 'aos TCCs'],
    ];

    private const FALLBACK = ['singular' => 'à apresentação', 'plural' => 'às apresentações'];

    public static function for(?string $typeName, bool $plural = false): string
    {
        $entry = self::MAP[Str::slug($typeName ?? '')] ?? self::FALLBACK;

        return $plural ? $entry['plural'] : $entry['singular'];
    }
}
