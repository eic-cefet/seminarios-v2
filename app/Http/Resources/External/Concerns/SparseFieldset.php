<?php

namespace App\Http\Resources\External\Concerns;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait SparseFieldset
{
    /**
     * Filter the payload down to the keys requested via ?fields=a,b,c.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $available
     * @return array<string, mixed>
     */
    protected function applyFieldset(array $payload, Request $request, array $available): array
    {
        $raw = $request->query('fields');
        if (! is_string($raw) || trim($raw) === '') {
            return $payload;
        }

        $requested = array_values(array_filter(
            array_map('trim', explode(',', $raw)),
            static fn (string $field): bool => $field !== '',
        ));

        if ($requested === []) {
            return $payload;
        }

        $unknown = array_values(array_diff($requested, $available));
        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'fields' => 'Unknown field(s): '.implode(', ', $unknown),
            ]);
        }

        return array_intersect_key($payload, array_flip($requested));
    }
}
