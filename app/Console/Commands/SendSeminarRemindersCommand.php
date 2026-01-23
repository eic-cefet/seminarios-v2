<?php

namespace App\Console\Commands;

use App\Jobs\SendSeminarReminderJob;
use App\Models\Registration;
use Illuminate\Console\Command;

class SendSeminarRemindersCommand extends Command
{
    protected $signature = 'reminders:seminars
                            {--sync : Send emails synchronously instead of queuing}';

    protected $description = 'Send reminder emails for seminars happening tomorrow';

    public function handle(): int
    {
        $this->info('Finding seminars happening tomorrow...');

        // Find registrations for seminars scheduled tomorrow
        // that haven't been reminded yet
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

        // Group registrations by user to send one email per user
        $grouped = $registrations->groupBy('user_id');

        $this->info("Found {$grouped->count()} users to remind.");

        $dispatched = 0;

        foreach ($grouped as $userId => $userRegistrations) {
            $user = $userRegistrations->first()->user;

            if (! $user) {
                continue;
            }

            $registrationIds = $userRegistrations->pluck('id');

            if ($this->option('sync')) {
                (new SendSeminarReminderJob($user, $registrationIds))->handle();
            } else {
                SendSeminarReminderJob::dispatch($user, $registrationIds);
            }

            $dispatched++;

            $seminarCount = $userRegistrations->count();
            $this->line("  - {$user->email}: {$seminarCount} seminar(s)");
        }

        $this->info("Dispatched {$dispatched} reminder(s).");

        return self::SUCCESS;
    }
}
