<?php

namespace App\Console\Commands;

use App\Concerns\TracksAuditContext;
use App\Console\Commands\Concerns\DispatchesGroupedJobs;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Enums\CommunicationCategory;
use App\Jobs\SendSeminarReminderJob;
use App\Mail\SeminarReminder;
use App\Mail\SeminarReminder7d;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SendSeminarRemindersCommand extends Command
{
    use DispatchesGroupedJobs, TracksAuditContext;

    protected $signature = 'reminders:seminars
                            {--days=1 : Reminder window in days (1 or 7)}
                            {--sync : Send emails synchronously instead of queuing}';

    protected $description = 'Send reminder emails for seminars happening in N days (1 or 7)';

    public function handle(): int
    {
        $this->setAuditContext();

        $days = (int) $this->option('days');
        if (! in_array($days, [1, 7], true)) {
            $this->error('--days must be 1 or 7');

            return self::FAILURE;
        }

        [$category, $reminderColumn, $prefColumn, $mailableClass, $auditEvent] = match ($days) {
            1 => [
                CommunicationCategory::SeminarReminder24h,
                'reminder_sent',
                'seminar_reminder_24h',
                SeminarReminder::class,
                AuditEvent::SeminarRemindersSent,
            ],
            7 => [
                CommunicationCategory::SeminarReminder7d,
                'reminder_7d_sent',
                'seminar_reminder_7d',
                SeminarReminder7d::class,
                AuditEvent::Seminar7dRemindersSent,
            ],
        };

        $this->info("Finding seminars happening in {$days} day(s)...");

        $windowStart = now()->addDays($days)->startOfDay();
        $windowEnd = now()->addDays($days)->endOfDay();

        $registrations = Registration::query()
            ->select('registrations.*')
            ->join('seminars', 'registrations.seminar_id', '=', 'seminars.id')
            ->where("registrations.{$reminderColumn}", false)
            ->whereNotNull('seminars.scheduled_at')
            ->whereBetween('seminars.scheduled_at', [$windowStart, $windowEnd])
            ->where('seminars.active', true)
            ->where(function ($q) use ($prefColumn) {
                $q->whereDoesntHave('user.alertPreference')
                    ->orWhereHas('user.alertPreference', fn ($q2) => $q2->where($prefColumn, true));
            })
            ->with(['user', 'seminar.seminarLocation'])
            ->get();

        if ($registrations->isEmpty()) {
            $this->info('No reminders to send.');

            return self::SUCCESS;
        }

        $dispatched = $this->dispatchGroupedByUser(
            $registrations,
            SendSeminarReminderJob::class,
            'users to remind',
            fn (User $user, Collection $ids) => new SendSeminarReminderJob(
                user: $user,
                registrationIds: $ids,
                category: $category,
                reminderColumn: $reminderColumn,
                mailableClass: $mailableClass,
            ),
        );

        $this->info("Dispatched {$dispatched} reminder(s).");

        if ($dispatched > 0) {
            AuditLog::record($auditEvent, AuditEventType::System, eventData: [
                'dispatched' => $dispatched,
                'days' => $days,
            ]);
        }

        return self::SUCCESS;
    }
}
