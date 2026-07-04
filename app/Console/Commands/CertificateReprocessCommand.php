<?php

namespace App\Console\Commands;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Jobs\RegenerateCertificateJob;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Services\CertificateService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class CertificateReprocessCommand extends Command
{
    private const CHUNK_SIZE = 200;

    protected $signature = 'certificates:reprocess
                            {--since= : Only seminars scheduled on/after this date (YYYY-MM-DD)}
                            {--type= : Only this seminar type name}
                            {--seminar= : Only this seminar (id or slug)}
                            {--dry-run : Print the matched count and dispatch nothing}
                            {--sync : Run regeneration inline instead of queuing}';

    protected $description = 'Re-render already-issued certificates (same S3 path, no email) for a filtered cut';

    public function handle(): int
    {
        $sync = (bool) $this->option('sync');
        $dryRun = (bool) $this->option('dry-run');
        $filters = array_filter([
            'since' => $this->option('since'),
            'type' => $this->option('type'),
            'seminar' => $this->option('seminar'),
        ]);

        Log::info('certificates:reprocess: started', [
            'mode' => $sync ? 'sync' : 'queued',
            'dry_run' => $dryRun,
            'filters' => $filters,
        ]);

        $query = $this->buildQuery();
        $count = (clone $query)->count();

        Log::info('certificates:reprocess: matched certificates', [
            'count' => $count,
            'filters' => $filters,
        ]);

        if ($count === 0) {
            $this->info('No certificates match the given filters. Nothing to reprocess.');
            Log::info('certificates:reprocess: nothing to reprocess', ['filters' => $filters]);

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Dry run: {$count} certificate(s) would be reprocessed.");
            Log::info('certificates:reprocess: dry run — nothing dispatched', [
                'count' => $count,
                'filters' => $filters,
            ]);

            return self::SUCCESS;
        }

        $this->info("Reprocessing {$count} certificate(s)...");

        $dispatched = 0;
        $failed = 0;

        $query->chunkById(self::CHUNK_SIZE, function (Collection $registrations) use ($sync, &$dispatched, &$failed): void {
            foreach ($registrations as $registration) {
                if ($sync) {
                    try {
                        (new RegenerateCertificateJob($registration))->handle(app(CertificateService::class));
                        Log::info('certificates:reprocess: reprocessed registration inline', [
                            'registration_id' => $registration->id,
                            'seminar_id' => $registration->seminar_id,
                        ]);
                    } catch (\Throwable $e) {
                        $failed++;
                        $this->error("Failed to reprocess registration {$registration->id}: {$e->getMessage()}");
                        Log::error('certificates:reprocess: failed for a registration', [
                            'registration_id' => $registration->id,
                            'seminar_id' => $registration->seminar_id,
                            'exception' => $e->getMessage(),
                        ]);

                        continue;
                    }
                } else {
                    RegenerateCertificateJob::dispatch($registration);
                    Log::info('certificates:reprocess: dispatched regenerate job', [
                        'registration_id' => $registration->id,
                        'seminar_id' => $registration->seminar_id,
                    ]);
                }

                $dispatched++;
            }
        });

        if ($failed > 0) {
            $this->warn("Completed with {$failed} failure(s).");
        }

        $this->info("Dispatched {$dispatched} regenerate job(s).");

        Log::info('certificates:reprocess: completed', [
            'mode' => $sync ? 'sync' : 'queued',
            'matched' => $count,
            'dispatched' => $dispatched,
            'failed' => $failed,
            'filters' => $filters,
        ]);

        AuditLog::record(AuditEvent::CertificatesProcessed, AuditEventType::System, eventData: [
            'action' => 'reprocess',
            'mode' => $sync ? 'sync' : 'queued',
            'dispatched' => $dispatched,
            'failed' => $failed,
            'filters' => $filters,
        ]);

        return self::SUCCESS;
    }

    private function buildQuery(): Builder
    {
        $query = Registration::query()
            ->where('present', true)
            ->whereNotNull('certificate_code')
            ->whereHas('seminar', function (Builder $seminar) {
                $seminar->whereNotNull('seminar_type_id');

                if ($since = $this->option('since')) {
                    $seminar->whereDate('scheduled_at', '>=', $since);
                }

                if ($seminarRef = $this->option('seminar')) {
                    $seminar->where(function (Builder $q) use ($seminarRef) {
                        $q->where('slug', $seminarRef);
                        if (ctype_digit((string) $seminarRef)) {
                            $q->orWhere('id', (int) $seminarRef);
                        }
                    });
                }
            });

        if ($type = $this->option('type')) {
            $query->whereHas('seminar.seminarType', fn (Builder $t) => $t->where('name', $type));
        }

        return $query;
    }
}
