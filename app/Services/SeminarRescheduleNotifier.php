<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Enums\CommunicationCategory;
use App\Mail\SeminarRescheduled;
use App\Models\AuditLog;
use App\Models\Seminar;
use App\Notifications\SeminarRescheduledNotification;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SeminarRescheduleNotifier
{
    public function __construct(private readonly CommunicationGate $gate) {}

    public function notify(Seminar $seminar, DateTimeInterface $oldScheduledAt): void
    {
        $seminar->loadMissing('seminarLocation');

        $registrations = DB::transaction(function () use ($seminar, $oldScheduledAt) {
            $seminar->registrations()->update(['reminder_sent' => false, 'reminder_7d_sent' => false]);

            $registrations = $seminar->registrations()->with('user.alertPreference')->get();

            AuditLog::record(AuditEvent::SeminarRescheduled, AuditEventType::System, $seminar, [
                'old_scheduled_at' => $oldScheduledAt->format('Y-m-d H:i:s'),
                'new_scheduled_at' => $seminar->scheduled_at->format('Y-m-d H:i:s'),
                'notified_users' => $registrations->count(),
            ]);

            return $registrations;
        });

        foreach ($registrations as $registration) {
            $registration->user->notify(new SeminarRescheduledNotification(
                $seminar,
                previousStartsAt: (string) $oldScheduledAt,
            ));

            if (! $this->gate->canEmail($registration->user, CommunicationCategory::SeminarRescheduled)) {
                continue;
            }

            Mail::to($registration->user)->queue(
                new SeminarRescheduled($registration->user, $seminar, $oldScheduledAt)
            );
        }
    }
}
