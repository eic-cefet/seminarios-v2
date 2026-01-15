<?php

namespace App\Console\Commands;

use App\Jobs\GenerateCertificateJob;
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

    protected $description = 'Process and generate missing certificates (JPG and PDF) for all registrations with presence';

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

        $registrations = $query->get();

        $this->info("Encontrados {$registrations->count()} registros.");

        $bar = $this->output->createProgressBar($registrations->count());
        $bar->start();

        $processed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($registrations as $registration) {
            try {
                $this->certificateService->ensureCertificateCode($registration);

                $jpgMissing = !$this->certificateService->jpgExists($registration);
                $pdfMissing = !$this->certificateService->pdfExists($registration);

                if (!$jpgMissing && !$pdfMissing) {
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

        $this->info("Processamento concluído!");
        $this->table(
            ['Métrica', 'Quantidade'],
            [
                ['Total de registros', $registrations->count()],
                ['Processados/Enfileirados', $processed],
                ['Já existentes (ignorados)', $skipped],
                ['Erros', $errors],
            ]
        );

        return Command::SUCCESS;
    }
}
