<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Support\Locking\LockKey;
use App\Support\Locking\Mutex;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Symfony\Component\Console\Command\Command;

class DatabaseResetService
{
    public const CONFIRMATION_PHRASE = 'APAGAR BANCO';

    public function isAvailable(): bool
    {
        return FeatureFlags::enabled('database_reset')
            && ! app()->environment('production');
    }

    public function reset(): void
    {
        if (! $this->isAvailable()) {
            throw ApiException::forbidden('A recriação do banco não está disponível neste ambiente.');
        }

        Mutex::for(
            LockKey::databaseReset(),
            ttlSeconds: 600,
            waitSeconds: 1,
            store: 'file',
        )->protect(function (): void {
            $exitCode = Artisan::call('migrate:fresh', [
                '--seed' => true,
                '--force' => true,
            ]);

            if ($exitCode !== Command::SUCCESS) {
                throw new RuntimeException(
                    'migrate:fresh failed: '.trim(Artisan::output()),
                );
            }
        });
    }
}
