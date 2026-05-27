<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FullName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Informe seu nome completo (nome e sobrenome).');

            return;
        }

        $parts = preg_split('/\s+/u', trim($value)) ?: [];

        if (count($parts) < 2) {
            $fail('Informe seu nome completo (nome e sobrenome).');

            return;
        }

        foreach ($parts as $part) {
            if (! preg_match("/^[\p{L}'\-]{2,}$/u", $part)) {
                $fail('Informe seu nome completo (nome e sobrenome).');

                return;
            }
        }
    }
}
