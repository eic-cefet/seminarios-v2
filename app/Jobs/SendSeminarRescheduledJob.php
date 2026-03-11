<?php

namespace App\Jobs;

use App\Mail\SeminarRescheduled;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendSeminarRescheduledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public User $user,
        public Seminar $seminar,
        public \DateTimeInterface $oldScheduledAt,
    ) {}

    public function handle(): void
    {
        $this->seminar->loadMissing('seminarLocation');

        Mail::to($this->user)->send(
            new SeminarRescheduled($this->user, $this->seminar, $this->oldScheduledAt)
        );
    }
}
