<?php

namespace App\Console\Commands;

use App\Jobs\AnonymizeUserJob;
use App\Models\User;
use Illuminate\Console\Command;

class AnonymizePendingUsersCommand extends Command
{
    protected $signature = 'lgpd:anonymize-pending {--dry-run}';

    protected $description = 'Anonymize users whose LGPD deletion grace period has elapsed.';

    public function handle(): int
    {
        $graceDays = (int) config('lgpd.retention.account_deletion_grace_days', 30);
        $cutoff = now()->subDays($graceDays);

        $query = User::withTrashed()
            ->whereNotNull('anonymization_requested_at')
            ->whereNull('anonymized_at')
            ->where('anonymization_requested_at', '<=', $cutoff);

        $this->info("Found {$query->count()} user(s) past grace period.");

        $dryRun = $this->option('dry-run');
        $query->select(['id'])->lazyById()->each(function (User $user) use ($dryRun): void {
            if ($dryRun) {
                $this->line("would-anonymize user_id={$user->id}");

                return;
            }

            AnonymizeUserJob::dispatch($user->id);
        });

        return self::SUCCESS;
    }
}
