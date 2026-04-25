<?php

namespace App\Support\Locking;

use RuntimeException;

class LockTimeoutException extends RuntimeException
{
    public function __construct(public readonly string $key, public readonly int $waitedSeconds)
    {
        parent::__construct("Could not acquire lock '{$key}' within {$waitedSeconds}s.");
    }
}
