<?php

namespace App\Support\External;

use InvalidArgumentException;

class IncludesParser
{
    /**
     * Resolve a comma-separated include string against a whitelist map.
     *
     * @param  array<string, string>  $map  Client-key => eager-load-path
     * @return list<string>
     */
    public static function resolve(?string $input, array $map): array
    {
        if ($input === null || trim($input) === '') {
            return [];
        }

        $out = [];
        foreach (explode(',', $input) as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }
            if (! array_key_exists($token, $map)) {
                throw new InvalidArgumentException("Unknown include '{$token}'");
            }
            $out[] = $map[$token];
        }

        return $out;
    }
}
