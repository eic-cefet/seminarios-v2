<?php

namespace App\Exceptions;

use App\Models\User;
use RuntimeException;

class OAuthMergeRefusedException extends RuntimeException
{
    public const USER_MESSAGE = 'Este e-mail já possui uma conta local. Faça login com sua senha e vincule o provedor pelo seu perfil.';

    public function __construct(
        public readonly User $existingUser,
        public readonly string $provider,
    ) {
        parent::__construct(self::USER_MESSAGE);
    }
}
