<?php

namespace App\Support;

class MarkdownStripper
{
    /**
     * Strip common Markdown syntax to produce plain text for previews.
     * Not a full parser — covers what users typically write in descriptions:
     * headings, emphasis, links, images, code, blockquotes, lists, rules.
     */
    public static function strip(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $patterns = [
            '/```[\s\S]*?```/' => '',
            '/`([^`]+)`/' => '$1',
            '/!\[([^\]]*)\]\([^)]*\)/' => '$1',
            '/\[([^\]]+)\]\([^)]*\)/' => '$1',
            '/^\s*#{1,6}\s+/m' => '',
            '/^\s*>\s?/m' => '',
            '/^\s*[-*+]\s+/m' => '',
            '/^\s*\d+\.\s+/m' => '',
            '/^\s*[-*_]{3,}\s*$/m' => '',
            '/\*\*([^*]+)\*\*/' => '$1',
            '/__([^_]+)__/' => '$1',
            '/(^|[^*])\*([^*\n]+)\*/' => '$1$2',
            '/(^|[^_])_([^_\n]+)_/' => '$1$2',
        ];

        $result = preg_replace(array_keys($patterns), array_values($patterns), $text);

        return trim((string) preg_replace('/\s+/', ' ', $result));
    }
}
