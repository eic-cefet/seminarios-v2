<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Extracts the first name from a full name for use in email greetings.
 *
 * Trims surrounding whitespace and returns the first whitespace-delimited
 * token. Null, empty, or whitespace-only input yields an empty string.
 */
final class PersonName
{
    public static function first(?string $fullName): string
    {
        $trimmed = trim($fullName ?? '');

        return Str::before($trimmed, ' ');
    }
}
