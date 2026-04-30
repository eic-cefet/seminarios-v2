<?php

namespace App\Support\Locking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Centralized factory for lock keys. Stringly-typed lock keys scattered
 * across the codebase are how mutual exclusion silently breaks; every
 * lock key in the app must be produced here.
 */
final class LockKey
{
    private const PREFIX = 'lock';

    /** @param class-string<Model> $modelClass */
    public static function slugGeneration(string $modelClass, string $candidate): string
    {
        return self::PREFIX.':slug:'.$modelClass.':'.Str::slug($candidate);
    }

    public static function seminarRegistration(int $seminarId): string
    {
        return self::PREFIX.':registration:seminar:'.$seminarId;
    }

    public static function ratingCreation(int $seminarId, int $userId): string
    {
        return self::PREFIX.':rating:seminar:'.$seminarId.':user:'.$userId;
    }

    public static function certificateGeneration(int $registrationId): string
    {
        return self::PREFIX.':certificate:registration:'.$registrationId;
    }

    public static function ratingSentiment(int $ratingId): string
    {
        return self::PREFIX.':sentiment:rating:'.$ratingId;
    }

    public static function externalIdempotency(string $tokenScope, string $idempotencyKey): string
    {
        return self::PREFIX.':external_api:idempotency:'.$tokenScope.':'.hash('sha256', $idempotencyKey);
    }
}
