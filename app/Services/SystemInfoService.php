<?php

namespace App\Services;

use DateTimeZone;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemInfoService
{
    /**
     * @return array<string, mixed>
     */
    public function collect(): array
    {
        return [
            'runtime' => $this->runtime(),
            'server' => $this->server(),
            'memory' => $this->memory(),
            'database' => $this->database(),
            'drivers' => $this->drivers(),
            'storage' => $this->storage(),
            'extensions' => $this->extensions(),
            'php_config' => $this->phpConfig(),
            'scheduler' => $this->scheduler(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function runtime(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => config('app.env'),
            'debug' => (bool) config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function server(): array
    {
        return [
            'os_family' => PHP_OS_FAMILY,
            'os_release' => php_uname('r'),
            'hostname' => php_uname('n'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'cli',
            'sapi' => PHP_SAPI,
            'architecture' => php_uname('m'),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function memory(): array
    {
        return [
            'limit_bytes' => $this->parseBytes((string) ini_get('memory_limit')),
            'current_bytes' => memory_get_usage(true),
            'peak_bytes' => memory_get_peak_usage(true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function database(): array
    {
        $connectionName = config('database.default');
        $config = config("database.connections.{$connectionName}", []);

        return [
            'driver' => $connectionName,
            'database' => $config['database'] ?? null,
            'host' => $config['host'] ?? null,
            'version' => $this->databaseVersion($connectionName),
        ];
    }

    private function databaseVersion(string $driver): string
    {
        try {
            return match ($driver) {
                'sqlite' => DB::connection($driver)->selectOne('select sqlite_version() as v')->v,
                'mysql', 'mariadb' => DB::connection($driver)->selectOne('select version() as v')->v,
                'pgsql' => DB::connection($driver)->selectOne('show server_version')->server_version,
                default => 'unknown',
            };
        } catch (Throwable) {
            return 'unknown';
        }
    }

    /**
     * @return array<string, ?string>
     */
    private function drivers(): array
    {
        return [
            'cache' => config('cache.default'),
            'queue' => config('queue.default'),
            'session' => config('session.driver'),
            'mail' => config('mail.default'),
            'filesystem' => config('filesystems.default'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storage(): array
    {
        $path = storage_path();

        return [
            'path' => $path,
            'free_bytes' => (int) @disk_free_space($path),
            'total_bytes' => (int) @disk_total_space($path),
        ];
    }

    /**
     * @return list<string>
     */
    private function extensions(): array
    {
        $loaded = get_loaded_extensions();
        sort($loaded);

        return array_values($loaded);
    }

    /**
     * @return array<string, mixed>
     */
    private function phpConfig(): array
    {
        return [
            'max_execution_time' => (int) ini_get('max_execution_time'),
            'post_max_size' => (string) ini_get('post_max_size'),
            'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
            'opcache_enabled' => (bool) ini_get('opcache.enable'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scheduler(): array
    {
        $schedule = app(Schedule::class);

        return collect($schedule->events())
            ->map(function (Event $event): array {
                $command = $event->command ?? $event->getSummaryForDisplay();
                $command = is_string($command)
                    ? trim(str_replace([PHP_BINARY, "'", '"'], '', $command))
                    : (string) $command;

                try {
                    $next = $event->nextRunDate()->format(DATE_ATOM);
                } catch (Throwable) {
                    $next = null;
                }

                return [
                    'command' => $command,
                    'description' => $event->description,
                    'expression' => $event->getExpression(),
                    'timezone' => $event->timezone instanceof DateTimeZone
                        ? $event->timezone->getName()
                        : ($event->timezone ?? config('app.timezone')),
                    'without_overlapping' => (bool) $event->withoutOverlapping,
                    'on_one_server' => (bool) $event->onOneServer,
                    'next_run_at' => $next,
                ];
            })
            ->values()
            ->all();
    }

    private function parseBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return -1;
        }

        $unit = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (int) $value,
        };
    }
}
