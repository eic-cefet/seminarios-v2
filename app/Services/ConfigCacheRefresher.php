<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;

/**
 * Re-bakes the configuration cache and signals queue workers to restart so
 * every runtime picks up refreshed AWS secret env overrides: config:cache
 * performs a fresh boot (which re-runs AwsSecretEnvLoader), web requests read
 * the new cache on their next boot, and restarted workers re-boot against it.
 *
 * The managed AWS_ENV_SECRET_* keys are cleared from the process env first:
 * Dotenv's immutable repository refuses to overwrite keys already present in
 * a long-lived worker (loaded by a previous in-process re-cache), which would
 * silently re-bake stale values on a second apply. Clearing them makes the
 * fresh boot inside config:cache re-read the just-written .env.
 */
class ConfigCacheRefresher
{
    public const MANAGED_ENV_KEYS = [
        'AWS_ENV_SECRET_ID',
        'AWS_ENV_SECRET_REGION',
        'AWS_ENV_SECRET_ACCESS_KEY_ID',
        'AWS_ENV_SECRET_SECRET_ACCESS_KEY',
    ];

    public function refresh(): void
    {
        foreach (self::MANAGED_ENV_KEYS as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        Artisan::call('config:cache');
        Artisan::call('queue:restart');
    }
}
