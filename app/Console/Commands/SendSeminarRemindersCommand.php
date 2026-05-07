<?php

namespace App\Console\Commands;

use App\Concerns\TracksAuditContext;
use App\Console\Commands\Concerns\DispatchesGroupedJobs;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Enums\CommunicationCategory;
use App\Jobs\SendSeminarReminderJob;
use App\Jobs\SendSpeakerReminderJob;
use App\Mail\SeminarReminder;
use App\Mail\SeminarReminder7d;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        // We deliberately do NOT add a filter like "registration.created_at <=
        // scheduled_at - $days days" here. The cron's daily cadence already covers
        // both stated concerns:
        //   - "Don't 7d-remind someone who registered 4 days before the seminar":
        //     the 7d cron picks seminars exactly 7 days out, so a registration
        //     for a seminar 4 days out is never picked up at all.
        //   - "Don't 24h-remind someone who registered 12h before the seminar":
        //     today's 10:00 cron run picks tomorrow's seminars; a registration
        //     created after the run for tomorrow simply didn't exist when the
        //     window's cron fired and won't be picked up.
        // Adding an explicit datetime filter on created_at would be defence in
        // depth but is fragile against the exact time of day, so we trust the
        // cron timing.
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
            ->with(['user', 'seminar.seminarLocation', 'seminar.seminarType'])
            ->get();

        if ($registrations->isEmpty()) {
            $this->info('No reminders to send.');
        } else {
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
        }

        if ($days === 1) {
            $speakerCount = $this->dispatchSpeakerReminders($windowStart, $windowEnd);
            $this->info("Dispatched {$speakerCount} speaker reminder(s).");
        }

        return self::SUCCESS;
    }

    private function dispatchSpeakerReminders(Carbon $windowStart, Carbon $windowEnd): int
    {
        $rows = DB::table('seminar_speaker')
            ->join('seminars', 'seminars.id', '=', 'seminar_speaker.seminar_id')
            ->where('seminars.active', true)
            ->whereBetween('seminars.scheduled_at', [$windowStart, $windowEnd])
            ->whereNull('seminar_speaker.reminder_24h_sent_at')
            ->select('seminar_speaker.user_id', 'seminar_speaker.seminar_id')
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');
        $sync = (bool) $this->option('sync');

        $count = 0;
        foreach ($rows as $row) {
            $user = $users->get($row->user_id);
            if (! $user) {
                continue; // @codeCoverageIgnore
            }
            if ($sync) {
                (new SendSpeakerReminderJob($user, (int) $row->seminar_id))->handle();
            } else {
                SendSpeakerReminderJob::dispatch($user, (int) $row->seminar_id);
            }
            $count++;
        }

        return $count;
    }
}
