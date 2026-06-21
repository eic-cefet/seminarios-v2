<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Grammatical helper for naming a presentation type in Brazilian Portuguese with
 * the correct article/contraction and gender agreement.
 *
 * The per-type map carries the type's grammatical gender plus its singular and
 * plural noun (with proper casing/accents — "ao TCC", "às aulas inaugurais").
 * Article contractions (a/em/de + o/a/os/as), the definite article and the
 * demonstrative are derived from gender + number, so each surface asks for the
 * exact form it needs. Lookup is case/accent-insensitive (keyed by Str::slug).
 * Unknown / null / custom type names fall back to the neutral feminine
 * "apresentação" / "apresentações", preserving the behaviour shipped in v2.39.8.
 */
final class PresentationTypeGrammar
{
    /**
     * @var array<string, array{gender: string, singular: string, plural: string}>
     */
    private const MAP = [
        'seminario' => ['gender' => 'm', 'singular' => 'seminário', 'plural' => 'seminários'],
        'dissertacao' => ['gender' => 'f', 'singular' => 'dissertação', 'plural' => 'dissertações'],
        'doutorado' => ['gender' => 'm', 'singular' => 'doutorado', 'plural' => 'doutorados'],
        'painel' => ['gender' => 'm', 'singular' => 'painel', 'plural' => 'painéis'],
        'qualificacao' => ['gender' => 'f', 'singular' => 'qualificação', 'plural' => 'qualificações'],
        'aula-inaugural' => ['gender' => 'f', 'singular' => 'aula inaugural', 'plural' => 'aulas inaugurais'],
        'tcc' => ['gender' => 'm', 'singular' => 'TCC', 'plural' => 'TCCs'],
    ];

    /**
     * @var array{gender: string, singular: string, plural: string}
     */
    private const FALLBACK = ['gender' => 'f', 'singular' => 'apresentação', 'plural' => 'apresentações'];

    private function __construct(
        private readonly string $gender,
        private readonly string $noun,
        private readonly bool $plural,
    ) {}

    public static function for(?string $typeName, bool $plural = false): self
    {
        $entry = self::MAP[Str::slug($typeName ?? '')] ?? self::FALLBACK;

        return new self($entry['gender'], $plural ? $entry['plural'] : $entry['singular'], $plural);
    }

    public function gender(): string
    {
        return $this->gender;
    }

    public function noun(): string
    {
        return $this->noun;
    }

    public function definite(): string
    {
        return $this->article('o', 'os', 'a', 'as').' '.$this->noun;
    }

    public function withA(): string
    {
        return $this->article('ao', 'aos', 'à', 'às').' '.$this->noun;
    }

    public function withEm(): string
    {
        return $this->article('no', 'nos', 'na', 'nas').' '.$this->noun;
    }

    public function withDe(): string
    {
        return $this->article('do', 'dos', 'da', 'das').' '.$this->noun;
    }

    public function demonstrative(): string
    {
        return $this->article('este', 'estes', 'esta', 'estas').' '.$this->noun;
    }

    public function agree(string $masculine, string $feminine): string
    {
        return $this->gender === 'm' ? $masculine : $feminine;
    }

    private function article(string $mSingular, string $mPlural, string $fSingular, string $fPlural): string
    {
        return match (true) {
            $this->gender === 'm' && ! $this->plural => $mSingular,
            $this->gender === 'm' => $mPlural,
            ! $this->plural => $fSingular,
            default => $fPlural,
        };
    }
}
