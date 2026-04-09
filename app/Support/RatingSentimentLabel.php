<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class RatingSentimentLabel
{
    public static function fromText(?string $sentiment): ?string
    {
        if (! is_string($sentiment) || trim($sentiment) === '') {
            return null;
        }

        $normalized = mb_strtolower($sentiment);
        $hasPositive = str_contains($normalized, 'positivo');
        $hasNegative = str_contains($normalized, 'negativo');
        $hasNeutral = str_contains($normalized, 'neutro');
        $hasMixed = str_contains($normalized, 'misto');

        if ($hasMixed || ($hasPositive && $hasNegative)) {
            return 'mixed';
        }

        if ($hasPositive) {
            return 'positive';
        }

        if ($hasNegative) {
            return 'negative';
        }

        if ($hasNeutral) {
            return 'neutral';
        }

        return null;
    }

    public static function applyFilter(Builder $query, string $label): Builder
    {
        return match ($label) {
            'positive' => $query
                ->whereRaw('LOWER(sentiment) LIKE ?', ['%positivo%'])
                ->whereRaw('LOWER(sentiment) NOT LIKE ?', ['%negativo%'])
                ->whereRaw('LOWER(sentiment) NOT LIKE ?', ['%misto%']),
            'negative' => $query
                ->whereRaw('LOWER(sentiment) LIKE ?', ['%negativo%'])
                ->whereRaw('LOWER(sentiment) NOT LIKE ?', ['%positivo%'])
                ->whereRaw('LOWER(sentiment) NOT LIKE ?', ['%misto%']),
            'neutral' => $query->whereRaw('LOWER(sentiment) LIKE ?', ['%neutro%']),
            'mixed' => $query->where(function (Builder $builder) {
                $builder
                    ->whereRaw('LOWER(sentiment) LIKE ?', ['%misto%'])
                    ->orWhere(function (Builder $nested) {
                        $nested
                            ->whereRaw('LOWER(sentiment) LIKE ?', ['%positivo%'])
                            ->whereRaw('LOWER(sentiment) LIKE ?', ['%negativo%']);
                    });
            }),
            'null' => $query->where(function (Builder $builder) {
                $builder
                    ->whereNull('sentiment')
                    ->orWhere(function (Builder $nested) {
                        $nested
                            ->whereRaw('LOWER(sentiment) NOT LIKE ?', ['%positivo%'])
                            ->whereRaw('LOWER(sentiment) NOT LIKE ?', ['%negativo%'])
                            ->whereRaw('LOWER(sentiment) NOT LIKE ?', ['%neutro%'])
                            ->whereRaw('LOWER(sentiment) NOT LIKE ?', ['%misto%']);
                    });
            }),
            default => $query,
        };
    }
}
