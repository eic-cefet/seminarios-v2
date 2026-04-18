<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneExpiredCacheCommand extends Command
{
    protected $signature = 'cache:prune-expired {--chunk=1000 : Rows to delete per iteration}';

    protected $description = 'Delete expired rows from the database cache store (database driver only prunes lazily on reads).';

    public function handle(): int
    {
        if (config('cache.default') !== 'database') {
            $this->info('Skipping — default cache store is not "database".');

            return self::SUCCESS;
        }

        $this->info('Pruning expired cache rows...');

        $chunk = (int) $this->option('chunk');
        $total = 0;
        $total += $this->pruneTable(config('cache.stores.database.table') ?? 'cache', $chunk);
        $total += $this->pruneTable(config('cache.stores.database.lock_table') ?? 'cache_locks', $chunk);

        $this->info("Pruned {$total} expired cache row(s).");

        return self::SUCCESS;
    }

    private function pruneTable(string $table, int $chunk): int
    {
        $now = now()->timestamp;
        $total = 0;

        do {
            $deleted = DB::table($table)
                ->where('expiration', '<=', $now)
                ->limit($chunk)
                ->delete();
            $total += $deleted;
        } while ($deleted > 0);

        return $total;
    }
}
