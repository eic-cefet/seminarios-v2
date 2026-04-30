<?php

namespace App\Http\Resources\External\Concerns;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Adds opt-in sparse-fieldset support to an external API JsonResource.
 *
 * Subclasses must declare `availableFields()` listing every top-level key
 * `toArray()` returns. When `?fields=a,b` is present on the request, the
 * resolved payload is filtered to that whitelist; an unknown field name
 * causes a 422 ValidationException.
 *
 * Implementation note: filtering happens in `resolve()` rather than inside
 * `toArray()` so static analyzers (Scramble) can still walk the literal
 * array returned from `toArray()` to infer the OpenAPI schema.
 */
trait SparseFieldset
{
    /**
     * @return list<string>
     */
    abstract public function availableFields(): array;

    /**
     * @param  Request|null  $request
     * @return array<string, mixed>
     */
    public function resolve($request = null)
    {
        $request ??= $this->resolveRequestFromContainer();

        /** @var array<string, mixed> $data */
        $data = parent::resolve($request);

        return $this->applyFieldset($data, $request, $this->availableFields());
    }

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
