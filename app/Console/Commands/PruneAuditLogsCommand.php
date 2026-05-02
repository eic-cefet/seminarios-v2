<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneAuditLogsCommand extends Command
{
    protected $signature = 'audit:prune {--days= : Override retention (applies to all tiers)}';

    protected $description = 'Prune audit log entries using per-tier retention from config/audit.php';

    public function handle(): int
    {
        $override = $this->option('days');
        $total = 0;

        if ($override !== null) {
            $total += $this->pruneOlderThan(
                cutoff: now()->subDays((int) $override),
                whereCallback: null,
            );
            $this->info("Pruned {$total} audit log(s) using override of {$override} days.");

            return self::SUCCESS;
        }

        $tiers = [
            'security' => config('audit.tiers.security', []),
            'system' => config('audit.tiers.system', []),
        ];

        foreach ($tiers as $tier => $names) {
            if (empty($names)) {
                continue;
            }
            $days = (int) config("audit.retention.{$tier}", 90);
            $total += $this->pruneOlderThan(
                cutoff: now()->subDays($days),
                whereCallback: fn ($q) => $q->whereIn('event_name', $names),
            );
        }

        $defaultDays = (int) config('audit.retention.default', 90);
        $excluded = array_merge($tiers['security'], $tiers['system']);
        $total += $this->pruneOlderThan(
            cutoff: now()->subDays($defaultDays),
            whereCallback: fn ($q) => $q->whereNotIn('event_name', $excluded),
        );

        $this->info("Pruned {$total} audit log(s) using tiered retention.");

        return self::SUCCESS;
    }

    private function pruneOlderThan(Carbon $cutoff, ?callable $whereCallback): int
    {
        $total = 0;
        do {
            $query = AuditLog::where('created_at', '<', $cutoff);
            if ($whereCallback !== null) {
                $whereCallback($query);
            }
            $deleted = $query->limit(1000)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        return $total;
    }
}
