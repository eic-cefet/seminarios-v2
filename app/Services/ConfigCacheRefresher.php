<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Re-bakes the configuration cache and signals queue workers to restart so
 * every runtime picks up refreshed AWS secret env overrides. config:cache
 * runs in a SUBPROCESS: an in-process Artisan::call would boot against this
 * worker's immutable, putenv-polluted env (Dotenv cannot overwrite values
 * loaded earlier in the same process), which could silently re-bake stale
 * secret values on a second apply in the same worker.
 */
class ConfigCacheRefresher
{
    public function refresh(): void
    {
        $result = Process::path(base_path())
            ->timeout(120)
            ->run([PHP_BINARY, 'artisan', 'config:cache']);

        if (! $result->successful()) {
            throw new RuntimeException('config:cache failed: '.trim($result->errorOutput() ?: $result->output()));
        }

        Artisan::call('queue:restart');
    }
}
