<?php

namespace App\Console\Commands;

use App\Jobs\GenerateCertificateJob;
use App\Models\Registration;
use Illuminate\Console\Command;

class ProcessPendingCertificatesCommand extends Command
{
    protected $signature = 'certificates:process-pending
                            {--sync : Process synchronously instead of queuing}
                            {--no-email : Skip sending emails}';

    protected $description = 'Process pending certificates for registrations with confirmed presence';

    public function handle(): int
    {
        $this->info('Finding registrations with pending certificates...');

        // Find registrations where:
        // - User was present
        // - Seminar has already happened
        // - Certificate not yet sent OR certificate code is missing
        $registrations = Registration::query()
            ->with(['seminar', 'user'])
            ->where('present', true)
            ->where(function ($query) {
                $query->where('certificate_sent', false)
                    ->orWhereNull('certificate_code');
            })
            ->get();

        if ($registrations->isEmpty()) {
            $this->info('No pending certificates to process.');

            return self::SUCCESS;
        }

        $this->info("Found {$registrations->count()} registration(s) with pending certificates.");

        $dispatched = 0;
        $sendEmail = ! $this->option('no-email');

        foreach ($registrations as $registration) {
            if (! $registration->user || ! $registration->seminar) {
                continue;
            }

            if ($this->option('sync')) {
                (new GenerateCertificateJob($registration, $sendEmail))->handle(
                    app(\App\Services\CertificateService::class)
                );
            } else {
                GenerateCertificateJob::dispatch($registration, $sendEmail);
            }

            $dispatched++;
            $this->line("  - {$registration->user->email}: {$registration->seminar->name}");
        }

        $this->info("Dispatched {$dispatched} certificate job(s).");

        return self::SUCCESS;
    }
}
