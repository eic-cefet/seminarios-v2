<?php

namespace App\Jobs;

use App\Mail\EvaluationReminder;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class SendEvaluationReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  Collection<int, int>  $registrationIds  IDs of registrations to send evaluation reminders for
     */
    public function __construct(
        public User $user,
        public Collection $registrationIds,
    ) {}

    public function handle(): void
    {
        $registrations = Registration::whereIn('id', $this->registrationIds)
            ->with(['seminar.seminarLocation'])
            ->get();

        if ($registrations->isEmpty()) {
            return;
        }

        $seminars = $registrations->pluck('seminar')->filter();

        if ($seminars->isEmpty()) {
            return;
        }

        Mail::to($this->user)->send(new EvaluationReminder($this->user, $seminars));

        // Mark evaluation as sent
        Registration::whereIn('id', $this->registrationIds)
            ->update(['evaluation_sent_at' => now()]);
    }
}
