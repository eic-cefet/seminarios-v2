<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fan out one RegenerateCertificateJob per already-issued certificate
 * that the given user owns. Dispatched (immediately or with a delay)
 * by UserCertificateRegenerationDispatcher whenever the user's `name`
 * column changes, so previously-issued certificates pick up the
 * corrected printed name without out-of-band side effects (no emails,
 * no notifications).
 *
 * This job is intentionally NOT `ShouldBeUnique` — debouncing happens
 * in front of dispatch via the dispatcher's cache+lock pair. Because
 * `SerializesModels` reloads the user from the database at handle()
 * time, any already-scheduled fan-out automatically picks up the
 * latest name on the user record without being re-dispatched.
 */
class RegenerateUserCertificatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        $this->user->registrations()
            ->whereNotNull('certificate_code')
            ->where('present', true)
            ->each(function ($registration): void {
                RegenerateCertificateJob::dispatch($registration);
            });
    }
}
