<?php

namespace App\Jobs;

use App\Concerns\TracksAuditContext;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fan out one RegenerateCertificateJob per already-issued certificate
 * that the given user owns. Triggered automatically by the User model
 * whenever the `name` column changes so previously-issued certificates
 * pick up the corrected printed name without out-of-band side effects
 * (no emails, no notifications).
 */
class RegenerateUserCertificatesJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksAuditContext;

    public int $tries = 3;

    public int $backoff = 60;

    public int $uniqueFor = 600;

    public function uniqueId(): string
    {
        return (string) $this->user->id;
    }

    public function __construct(public User $user) {}

    public function handle(): void
    {
        $this->setAuditContext();

        $this->user->registrations()
            ->whereNotNull('certificate_code')
            ->where('present', true)
            ->each(function ($registration): void {
                RegenerateCertificateJob::dispatch($registration);
            });
    }
}
