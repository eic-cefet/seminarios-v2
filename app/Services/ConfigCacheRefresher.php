<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;

/**
 * Re-bakes the configuration cache and signals queue workers to restart so
 * every runtime picks up refreshed AWS secret env overrides: config:cache
 * performs a fresh boot (which re-runs AwsSecretEnvLoader), web requests read
 * the new cache on their next boot, and restarted workers re-boot against it.
 */
class ConfigCacheRefresher
{
    public function refresh(): void
    {
        Artisan::call('config:cache');
        Artisan::call('queue:restart');
    }
}
