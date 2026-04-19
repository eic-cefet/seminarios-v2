<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\VersionController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class VersionSetCommand extends Command
{
    protected $signature = 'version:set {version}';

    protected $description = 'Store the current application version in cache';

    public function handle(): int
    {
        $version = trim((string) $this->argument('version'));

        if ($version === '') {
            $this->error('Version cannot be empty.');

            return self::FAILURE;
        }

        Cache::forever(VersionController::CACHE_KEY, $version);

        $this->info("Version set to {$version}.");

        return self::SUCCESS;
    }
}
