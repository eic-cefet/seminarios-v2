<?php

namespace App\Enums;

enum Gender: string
{
    case Masculine = 'masculine';
    case Feminine = 'feminine';

    public function ifMasculine(string $masculine, string $feminine): string
    {
        return $this === self::Masculine ? $masculine : $feminine;
    }
}
