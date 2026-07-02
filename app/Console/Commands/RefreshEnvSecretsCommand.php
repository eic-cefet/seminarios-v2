<?php

namespace App\Console\Commands;

use App\Services\AwsSecretEnvLoader;
use App\Services\ConfigCacheRefresher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshEnvSecretsCommand extends Command
{
    public const LAST_HASH_CACHE_KEY = 'env-secrets:last-applied-hash';

    protected $signature = 'env-secrets:refresh';

    protected $description = 'Re-cache configuration when the AWS env secret changed, so rotated values apply without a redeploy';

    public function handle(ConfigCacheRefresher $refresher): int
    {
        if (! config('env-secrets.secret_id')) {
            $this->info('AWS secret env overrides are disabled; nothing to refresh.');

            return self::SUCCESS;
        }

        try {
            $envVars = $this->laravel->make(AwsSecretEnvLoader::class)->fetchEnvVars();
        } catch (Throwable $e) {
            Log::warning('env-secrets:refresh could not fetch the secret; keeping the current config cache.', [
                'error' => $e->getMessage(),
            ]);
            $this->error("Failed to fetch the secret: {$e->getMessage()}");

            return self::FAILURE;
        }

        $hash = hash('sha256', json_encode($envVars));

        if (Cache::get(self::LAST_HASH_CACHE_KEY) === $hash) {
            $this->info('Secret unchanged; config cache left as-is.');

            return self::SUCCESS;
        }

        $refresher->refresh();
        Cache::forever(self::LAST_HASH_CACHE_KEY, $hash);

        $this->info('Secret changed; configuration re-cached and queue restart signalled.');

        return self::SUCCESS;
    }
}
