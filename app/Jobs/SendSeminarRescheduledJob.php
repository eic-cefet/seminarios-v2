<?php

namespace App\Jobs;

use App\Mail\SeminarRescheduled;
use App\Models\Seminar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSeminarRescheduledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $maxExceptions = 3;

    public string $oldScheduledAt;

    public function __construct(
        public User $user,
        public Seminar $seminar,
        \DateTimeInterface $oldScheduledAt,
    ) {
        $this->oldScheduledAt = $oldScheduledAt->format(\DateTimeInterface::ATOM);
    }

    public function handle(): void
    {
        $this->seminar->loadMissing('seminarLocation');

        Mail::to($this->user)->send(
            new SeminarRescheduled($this->user, $this->seminar, Carbon::parse($this->oldScheduledAt))
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendSeminarRescheduledJob failed', [
            'user_id' => $this->user->id,
            'seminar_id' => $this->seminar->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
