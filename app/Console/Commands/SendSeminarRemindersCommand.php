<?php

namespace App\Console\Commands;

use App\Concerns\TracksAuditContext;
use App\Console\Commands\Concerns\DispatchesGroupedJobs;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Jobs\SendSeminarReminderJob;
use App\Models\AuditLog;
use App\Models\Registration;
use Illuminate\Console\Command;

class SendSeminarRemindersCommand extends Command
{
    use DispatchesGroupedJobs, TracksAuditContext;

    protected $signature = 'reminders:seminars
                            {--sync : Send emails synchronously instead of queuing}';

    protected $description = 'Send reminder emails for seminars happening tomorrow';

    public function handle(): int
    {
        $this->setAuditContext();
        $this->info('Finding seminars happening tomorrow...');

        $tomorrowStart = now()->addDay()->startOfDay();
        $tomorrowEnd = now()->addDay()->endOfDay();

        $registrations = Registration::query()
            ->select('registrations.*')
            ->join('seminars', 'registrations.seminar_id', '=', 'seminars.id')
            ->where('registrations.reminder_sent', false)
            ->whereNotNull('seminars.scheduled_at')
            ->whereBetween('seminars.scheduled_at', [$tomorrowStart, $tomorrowEnd])
            ->where('seminars.active', true)
            ->where(function ($q) {
                $q->whereDoesntHave('user.alertPreference')
                    ->orWhereHas('user.alertPreference', fn ($q2) => $q2->where('seminar_reminder_24h', true));
            })
            ->with(['user', 'seminar.seminarLocation'])
            ->get();

        if ($registrations->isEmpty()) {
            $this->info('No reminders to send.');

            return self::SUCCESS;
        }

        $dispatched = $this->dispatchGroupedByUser($registrations, SendSeminarReminderJob::class, 'users to remind');

        $this->info("Dispatched {$dispatched} reminder(s).");

        if ($dispatched > 0) {
            AuditLog::record(AuditEvent::SeminarRemindersSent, AuditEventType::System, eventData: [
                'dispatched' => $dispatched,
            ]);
        }

        return self::SUCCESS;
    }
}
