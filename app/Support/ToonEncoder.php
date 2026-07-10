<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Encodes a PHP associative array into TOON (Token-Oriented Object
 * Notation) — a compact, indentation-based text format used instead of
 * JSON when sending structured data to an LLM, to reduce token count.
 */
final class ToonEncoder
{
    public static function encode(array $data, int $indent = 0): string
    {
        $pad = str_repeat('  ', $indent);
        $lines = [];

        foreach ($data as $key => $value) {
            if (is_array($value) && self::isList($value)) {
                $lines[] = self::encodeList($pad, (string) $key, $value, $indent);
            } elseif (is_array($value)) {
                $lines[] = "{$pad}{$key}:";
                $lines[] = self::encode($value, $indent + 1);
            } else {
                $lines[] = "{$pad}{$key}: ".self::encodeScalar($value);
            }
        }

        return implode("\n", $lines);
    }

    private static function encodeList(string $pad, string $key, array $items, int $indent): string
    {
        if ($items === []) {
            return "{$pad}{$key}[0]:";
        }

        $first = $items[0];

        if (! is_array($first)) {
            $values = implode(',', array_map([self::class, 'encodeScalar'], $items));

            return "{$pad}{$key}[".count($items).']: '.$values;
        }

        $fields = array_keys($first);
        $rowPad = str_repeat('  ', $indent + 1);
        $lines = ["{$pad}{$key}[".count($items).']{'.implode(',', $fields).'}:'];

        foreach ($items as $item) {
            if (array_keys($item) !== $fields) {
                throw new InvalidArgumentException("TOON tabular rows for \"{$key}\" must share the same fields.");
            }

            $row = implode(',', array_map(fn ($field) => self::encodeScalar($item[$field]), $fields));
            $lines[] = "{$rowPad}{$row}";
        }

        return implode("\n", $lines);
    }

    private static function isList(array $value): bool
    {
        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }

    private static function encodeScalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '';
        }

        $string = (string) $value;

        if (str_contains($string, ',') || str_contains($string, "\n") || str_contains($string, '"')) {
            return '"'.str_replace('"', '""', $string).'"';
        }

        return $string;
    }
}
