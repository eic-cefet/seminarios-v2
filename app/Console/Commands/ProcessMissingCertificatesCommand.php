<?php

namespace App\Console\Commands;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Jobs\GenerateCertificateJob;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Services\CertificateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessMissingCertificatesCommand extends Command
{
    protected $signature = 'certificates:process-missing
                            {--send-email : Send email to users after generating certificate}
                            {--sync : Process synchronously instead of queuing}
                            {--seminar= : Process only certificates for a specific seminar ID}';

    protected $description = 'Check S3 for missing certificates (JPG and PDF) and generate them for confirmed attendees';

    public function __construct(
        protected CertificateService $certificateService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Buscando registros com presença confirmada...');

        $query = Registration::query()
            ->with(['seminar', 'user'])
            ->where('present', true);

        if ($seminarId = $this->option('seminar')) {
            $query->where('seminar_id', $seminarId);
        }

        $total = $query->count();

        $this->info("Encontrados {$total} registros.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($query->lazyById(100) as $registration) {
            try {
                $this->certificateService->ensureCertificateCode($registration);

                $jpgMissing = ! $this->certificateService->jpgExists($registration, fresh: true);
                $pdfMissing = ! $this->certificateService->pdfExists($registration, fresh: true);

                if (! $jpgMissing && ! $pdfMissing) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                if ($this->option('sync')) {
                    if ($jpgMissing) {
                        $this->certificateService->generateJpg($registration);
                    }

                    if ($pdfMissing) {
                        $this->certificateService->generatePdf($registration);
                    }

                    $processed++;
                } else {
                    GenerateCertificateJob::dispatch(
                        $registration,
                        $this->option('send-email')
                    );
                    $processed++;
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->error("Erro ao processar registro #{$registration->id}: {$e->getMessage()}");
                Log::error("Erro ao processar registro #{$registration->id}: {$e->getMessage()}", [
                    'registration_id' => $registration->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Processamento concluído!');
        $this->table(
            ['Métrica', 'Quantidade'],
            [
                ['Total de registros', $total],
                ['Processados/Enfileirados', $processed],
                ['Já existentes (ignorados)', $skipped],
                ['Erros', $errors],
            ]
        );

        if ($processed > 0) {
            AuditLog::record(AuditEvent::MissingCertificatesProcessed, AuditEventType::System, eventData: [
                'processed' => $processed,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
