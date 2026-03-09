<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PruneAuditLogsCommand extends Command
{
    protected $signature = 'audit:prune {--days=90 : Number of days to keep}';

    protected $description = 'Prune audit log entries older than the specified number of days';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("Pruning audit logs older than {$days} days...");

        $cutoff = now()->subDays($days);
        $total = 0;

        do {
            $deleted = AuditLog::where('created_at', '<', $cutoff)->limit(1000)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        $this->info("Pruned {$total} audit log(s).");

        return self::SUCCESS;
    }
}
