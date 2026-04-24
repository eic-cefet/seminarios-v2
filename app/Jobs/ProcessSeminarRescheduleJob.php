<?php

namespace App\Jobs;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Enums\CommunicationCategory;
use App\Mail\SeminarRescheduled;
use App\Models\AuditLog;
use App\Models\Seminar;
use App\Notifications\SeminarRescheduledNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ProcessSeminarRescheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksAuditContext;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Seminar $seminar,
        public \DateTimeInterface $oldScheduledAt,
    ) {}

    public function handle(): void
    {
        $this->setAuditContext();

        $this->seminar->loadMissing('seminarLocation');

        $registrations = DB::transaction(function () {
            $this->seminar->registrations()->update(['reminder_sent' => false]);

            $registrations = $this->seminar->registrations()->with('user.alertPreference')->get();

            AuditLog::record(AuditEvent::SeminarRescheduled, AuditEventType::System, $this->seminar, [
                'old_scheduled_at' => $this->oldScheduledAt->format('Y-m-d H:i:s'),
                'new_scheduled_at' => $this->seminar->scheduled_at->format('Y-m-d H:i:s'),
                'notified_users' => $registrations->count(),
            ]);

            return $registrations;
        });

        foreach ($registrations as $registration) {
            $registration->user->notify(new SeminarRescheduledNotification(
                $this->seminar,
                previousStartsAt: (string) $this->oldScheduledAt,
            ));

            if (! $registration->user->wantsCommunication(CommunicationCategory::SeminarRescheduled)) {
                continue;
            }

            Mail::to($registration->user)->queue(
                new SeminarRescheduled($registration->user, $this->seminar, $this->oldScheduledAt)
            );
        }
    }
}
