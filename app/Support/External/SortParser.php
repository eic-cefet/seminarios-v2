<?php

namespace App\Support\External;

use InvalidArgumentException;

class SortParser
{
    /**
     * Parse a comma-separated sort string into ordered pairs of [column, direction].
     *
     * Tokens prefixed with `-` are sorted descending. Columns must appear in the
     * provided whitelist or an InvalidArgumentException is thrown.
     *
     * @param  list<string>  $whitelist
     * @return list<array{0: string, 1: 'asc'|'desc'}>
     */
    public static function parse(?string $input, array $whitelist): array
    {
        if ($input === null || trim($input) === '') {
            return [];
        }

        $pairs = [];
        foreach (explode(',', $input) as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }
            $direction = 'asc';
            if (str_starts_with($token, '-')) {
                $direction = 'desc';
                $token = substr($token, 1);
            }
            if (! in_array($token, $whitelist, true)) {
                throw new InvalidArgumentException("Sort column '{$token}' is not allowed");
            }
            $pairs[] = [$token, $direction];
        }

        return $pairs;
    }
}
