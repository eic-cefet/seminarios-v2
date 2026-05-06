<?php

namespace App\Support;

use App\Enums\Gender;
use App\Models\Seminar;
use Illuminate\Support\Collection;

class SeminarPluralDescriptor
{
    public function __construct(
        public readonly string $noun,
        public readonly Gender $gender,
    ) {}

    /**
     * Build a descriptor from a collection of seminars.
     *
     * - If every seminar shares a non-null seminar_type, use that type's
     *   inline plural and gender.
     * - Otherwise (mixed types, any null type, empty collection),
     *   fall back to the generic feminine "apresentações".
     *
     * The collection should have `seminarType` eager-loaded; this method
     * will trigger lazy loading otherwise.
     *
     * @param  Collection<int, Seminar>  $seminars
     */
    public static function for(Collection $seminars): self
    {
        if ($seminars->isEmpty()) {
            return self::generic();
        }

        $hasNullType = $seminars->contains(fn ($seminar) => $seminar->seminarType === null);

        if ($hasNullType) {
            return self::generic();
        }

        $types = $seminars->pluck('seminarType')->unique('id');

        if ($types->count() === 1) {
            $type = $types->first();

            return new self(
                noun: $type->inlinePlural(),
                gender: $type->gender,
            );
        }

        return self::generic();
    }

    public function ifMasculine(string $masculine, string $feminine): string
    {
        return $this->gender->ifMasculine($masculine, $feminine);
    }

    private static function generic(): self
    {
        return new self(noun: 'apresentações', gender: Gender::Feminine);
    }
}
