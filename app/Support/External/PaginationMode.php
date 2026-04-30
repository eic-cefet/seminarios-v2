<?php

namespace App\Support\External;

use InvalidArgumentException;

enum PaginationMode: string
{
    case Page = 'page';
    case Cursor = 'cursor';

    public static function fromQuery(?string $value): self
    {
        if ($value === null || $value === '') {
            return self::Page;
        }

        return self::tryFrom($value)
            ?? throw new InvalidArgumentException("Unknown pagination mode '{$value}'");
    }
}
