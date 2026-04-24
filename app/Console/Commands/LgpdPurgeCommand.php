<?php

namespace App\Console\Commands;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\PresenceLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class LgpdPurgeCommand extends Command
{
    protected $signature = 'lgpd:purge {--dry-run}';

    protected $description = 'Purge data whose LGPD retention window has elapsed.';

    public function handle(): int
    {
        $summary = [
            'sessions' => $this->purgeSessions(),
            'personal_access_tokens' => $this->purgeTokens(),
            'presence_links' => $this->purgePresenceLinks(),
        ];

        foreach ($summary as $label => $count) {
            $this->line("{$label}: {$count}");
        }

        if (! $this->option('dry-run')) {
            AuditLog::record(
                event: AuditEvent::LgpdRetentionPurged,
                auditable: null,
                eventData: $summary,
            );
        }

        return self::SUCCESS;
    }

    private function purgeSessions(): int
    {
        $days = (int) config('lgpd.retention.sessions_days', 30);
        $cutoff = now()->subDays($days)->timestamp;

        if ($this->option('dry-run')) {
            return DB::table('sessions')->where('last_activity', '<', $cutoff)->count();
        }

        return DB::table('sessions')->where('last_activity', '<', $cutoff)->delete();
    }

    private function purgeTokens(): int
    {
        $days = (int) config('lgpd.retention.personal_access_tokens_days', 180);

        $query = PersonalAccessToken::query()->where(function ($q) use ($days) {
            $q->where('last_used_at', '<=', now()->subDays($days))
                ->orWhere(function ($inner) use ($days) {
                    $inner->whereNull('last_used_at')
                        ->where('created_at', '<=', now()->subDays($days));
                });
        });

        if ($this->option('dry-run')) {
            return $query->count();
        }

        return $query->delete();
    }

    private function purgePresenceLinks(): int
    {
        $days = (int) config('lgpd.retention.presence_links_days', 30);
        $cutoff = now()->subDays($days);

        $query = PresenceLink::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $cutoff);

        if ($this->option('dry-run')) {
            return $query->count();
        }

        return $query->delete();
    }
}
