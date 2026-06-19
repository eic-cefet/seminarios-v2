<?php

namespace App\Console\Commands;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Jobs\RegenerateCertificateJob;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Services\CertificateService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CertificateReprocessCommand extends Command
{
    use TracksAuditContext;

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
        $this->setAuditContext();

        $query = $this->buildQuery();
        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('No certificates match the given filters. Nothing to reprocess.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("Dry run: {$count} certificate(s) would be reprocessed.");

            return self::SUCCESS;
        }

        $this->info("Reprocessing {$count} certificate(s)...");

        $sync = (bool) $this->option('sync');
        $dispatched = 0;

        $query->chunkById(self::CHUNK_SIZE, function (Collection $registrations) use ($sync, &$dispatched): void {
            foreach ($registrations as $registration) {
                if ($sync) {
                    (new RegenerateCertificateJob($registration))->handle(app(CertificateService::class));
                } else {
                    RegenerateCertificateJob::dispatch($registration);
                }

                $dispatched++;
            }
        });

        $this->info("Dispatched {$dispatched} regenerate job(s).");

        AuditLog::record(AuditEvent::CertificatesProcessed, AuditEventType::System, eventData: [
            'action' => 'reprocess',
            'dispatched' => $dispatched,
            'filters' => array_filter([
                'since' => $this->option('since'),
                'type' => $this->option('type'),
                'seminar' => $this->option('seminar'),
            ]),
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
