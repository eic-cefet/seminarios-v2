<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FullName implements ValidationRule
{
    public const MESSAGE = 'Informe seu nome completo (nome e sobrenome).';

    /**
     * Pure check for the full-name shape. Use this from models or services
     * that need a yes/no answer without paying for a Rule instantiation
     * and a closure trampoline on every call.
     */
    public static function passes(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $parts = preg_split('/\s+/u', trim($value)) ?: [];

        if (count($parts) < 2) {
            return false;
        }

        foreach ($parts as $part) {
            if (! preg_match("/^[\p{L}'\-]{2,}$/u", $part)) {
                return false;
            }
        }

        return true;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::passes($value)) {
            $fail(self::MESSAGE);
        }
    }
}
