<?php

namespace App\Services;

use DateTimeZone;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemInfoService
{
    private static bool $consoleBooted = false;

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
        $driver = $config['driver'] ?? $connectionName;

        return [
            'driver' => $driver,
            'database' => $config['database'] ?? null,
            'host' => $config['host'] ?? null,
            'version' => $this->databaseVersion($connectionName, $driver),
        ];
    }

    private function databaseVersion(string $connectionName, string $driver): string
    {
        try {
            return match ($driver) {
                'sqlite' => DB::connection($connectionName)->selectOne('select sqlite_version() as v')->v,
                'mysql', 'mariadb' => DB::connection($connectionName)->selectOne('select version() as v')->v, // @codeCoverageIgnore
                'pgsql' => DB::connection($connectionName)->selectOne('show server_version')->server_version, // @codeCoverageIgnore
                default => 'unknown', // @codeCoverageIgnore
            };
            // @codeCoverageIgnoreStart
        } catch (Throwable) {
            return 'unknown';
        }
        // @codeCoverageIgnoreEnd
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
        // Don't expose the absolute filesystem path — only the byte counts are
        // useful to admins, and the path leaks server layout to the UI.
        $path = storage_path();

        return [
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
        // routes/console.php is only loaded by the console kernel; bootstrap it
        // so Schedule::events() is populated during HTTP requests too.
        // Guard against double-registration on cache miss within the same
        // PHP-FPM worker — calling bootstrap() twice re-binds schedule events.
        if (! self::$consoleBooted) {
            app(ConsoleKernel::class)->bootstrap();
            self::$consoleBooted = true;
        }

        $schedule = app(Schedule::class);

        return collect($schedule->events())
            ->map(function (Event $event): array {
                $command = $event->command ?? $event->getSummaryForDisplay();
                $command = is_string($command)
                    ? trim(str_replace([PHP_BINARY, "'", '"'], '', $command))
                    : (string) $command; // @codeCoverageIgnore

                try {
                    $next = $event->nextRunDate()->format(DATE_ATOM);
                    // @codeCoverageIgnoreStart
                } catch (Throwable) {
                    $next = null;
                }
                // @codeCoverageIgnoreEnd

                return [
                    'command' => $command,
                    'description' => $event->description,
                    'expression' => $event->getExpression(),
                    'timezone' => $event->timezone instanceof DateTimeZone
                        ? $event->timezone->getName() // @codeCoverageIgnore
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
