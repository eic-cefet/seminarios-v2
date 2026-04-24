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

        $due = User::withTrashed()
            ->whereNotNull('anonymization_requested_at')
            ->whereNull('anonymized_at')
            ->where('anonymization_requested_at', '<=', $cutoff)
            ->get();

        $this->info("Found {$due->count()} user(s) past grace period.");

        if ($this->option('dry-run')) {
            foreach ($due as $user) {
                $this->line("would-anonymize user_id={$user->id}");
            }

            return self::SUCCESS;
        }

        foreach ($due as $user) {
            AnonymizeUserJob::dispatch($user->id);
        }

        return self::SUCCESS;
    }
}
