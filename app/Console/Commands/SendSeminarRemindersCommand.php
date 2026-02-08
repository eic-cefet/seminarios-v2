<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\DispatchesGroupedJobs;
use App\Jobs\SendSeminarReminderJob;
use App\Models\Registration;
use Illuminate\Console\Command;

class SendSeminarRemindersCommand extends Command
{
    use DispatchesGroupedJobs;

    protected $signature = 'reminders:seminars
                            {--sync : Send emails synchronously instead of queuing}';

    protected $description = 'Send reminder emails for seminars happening tomorrow';

    public function handle(): int
    {
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
            ->with(['user', 'seminar.seminarLocation'])
            ->get();

        if ($registrations->isEmpty()) {
            $this->info('No reminders to send.');

            return self::SUCCESS;
        }

        $dispatched = $this->dispatchGroupedByUser($registrations, SendSeminarReminderJob::class, 'users to remind');

        $this->info("Dispatched {$dispatched} reminder(s).");

        return self::SUCCESS;
    }
}
