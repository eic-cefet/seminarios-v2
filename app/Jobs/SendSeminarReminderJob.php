<?php

namespace App\Jobs;

use App\Enums\CommunicationCategory;
use App\Mail\SeminarReminder;
use App\Models\Registration;
use App\Models\User;
use App\Services\CommunicationGate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class SendSeminarReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  Collection<int, int>  $registrationIds  IDs of registrations to send reminders for
     */
    public function __construct(
        public User $user,
        public Collection $registrationIds,
        public CommunicationCategory $category = CommunicationCategory::SeminarReminder24h,
        public string $reminderColumn = 'reminder_sent',
        public string $mailableClass = SeminarReminder::class,
    ) {}

    public function handle(?CommunicationGate $gate = null): void
    {
        $gate ??= app(CommunicationGate::class);

        if (! $gate->canEmail($this->user, $this->category)) {
            return;
        }

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

        $mailableClass = $this->mailableClass;
        Mail::to($this->user)->queue(new $mailableClass($this->user, $seminars));

        Registration::whereIn('id', $this->registrationIds)->update([$this->reminderColumn => true]);
    }
}
