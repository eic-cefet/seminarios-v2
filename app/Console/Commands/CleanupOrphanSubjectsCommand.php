<?php

namespace App\Console\Commands;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\Subject;
use Illuminate\Console\Command;

class CleanupOrphanSubjectsCommand extends Command
{
    use TracksAuditContext;

    protected $signature = 'subjects:cleanup-orphans {--dry-run : Only show orphan subjects without deleting}';

    protected $description = 'Soft-delete subjects that have no seminars attached';

    public function handle(): int
    {
        $this->setAuditContext();

        $orphans = Subject::doesntHave('seminars')->orderBy('name')->get();

        if ($orphans->isEmpty()) {
            $this->info('No orphan subjects found.');

            return self::SUCCESS;
        }

        $this->info("Found {$orphans->count()} orphan subject(s):");
        $this->newLine();

        $this->table(
            ['ID', 'Name', 'Created At'],
            $orphans->map(fn (Subject $s) => [$s->id, $s->name, $s->created_at->format('Y-m-d')]),
        );

        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No changes were made.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Soft-delete {$orphans->count()} orphan subject(s)?")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $deletedIds = $orphans->pluck('id')->toArray();

        foreach ($orphans as $subject) {
            $subject->delete();
        }

        AuditLog::record(AuditEvent::OrphanSubjectsCleanedUp, eventData: [
            'deleted_ids' => $deletedIds,
            'count' => count($deletedIds),
        ]);

        $this->info("Done. Soft-deleted {$orphans->count()} subject(s).");

        return self::SUCCESS;
    }
}
