<?php

namespace App\Jobs;

use App\Concerns\TracksAuditContext;
use App\Models\Seminar;
use App\Services\SeminarRescheduleNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSeminarRescheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksAuditContext;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Seminar $seminar,
        public \DateTimeInterface $oldScheduledAt,
    ) {}

    public function handle(?SeminarRescheduleNotifier $notifier = null): void
    {
        $notifier ??= app(SeminarRescheduleNotifier::class);

        $this->setAuditContext();

        $notifier->notify($this->seminar, $this->oldScheduledAt);
    }
}
