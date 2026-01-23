<?php

namespace App\Console\Commands;

use App\Jobs\SendEvaluationReminderJob;
use App\Models\Registration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendEvaluationRemindersCommand extends Command
{
    protected $signature = 'reminders:evaluations
                            {--sync : Send emails synchronously instead of queuing}';

    protected $description = 'Send evaluation reminders to users who attended seminars 2+ days ago but haven\'t evaluated yet';

    public function handle(): int
    {
        $this->info('Finding users with pending evaluations...');

        // Find registrations where:
        // - User was present
        // - Seminar ended at least 2 days ago
        // - Seminar ended no more than 30 days ago (restriction)
        // - Evaluation reminder not yet sent
        // - User hasn't rated the seminar yet
        $twoDaysAgo = now()->subDays(2)->endOfDay();
        $thirtyDaysAgo = now()->subDays(30)->startOfDay();

        $registrations = Registration::query()
            ->select('registrations.*')
            ->join('seminars', 'registrations.seminar_id', '=', 'seminars.id')
            ->where('registrations.present', true)
            ->whereNull('registrations.evaluation_sent_at')
            ->whereNotNull('seminars.scheduled_at')
            ->where('seminars.scheduled_at', '<=', $twoDaysAgo)
            ->where('seminars.scheduled_at', '>=', $thirtyDaysAgo)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('ratings')
                    ->whereColumn('ratings.seminar_id', 'registrations.seminar_id')
                    ->whereColumn('ratings.user_id', 'registrations.user_id');
            })
            ->with(['user', 'seminar'])
            ->get();

        if ($registrations->isEmpty()) {
            $this->info('No pending evaluations found.');

            return self::SUCCESS;
        }

        // Group registrations by user
        $grouped = $registrations->groupBy('user_id');

        $this->info("Found {$grouped->count()} users with pending evaluations.");

        $dispatched = 0;

        foreach ($grouped as $userId => $userRegistrations) {
            $user = $userRegistrations->first()->user;

            if (! $user) {
                continue;
            }

            $registrationIds = $userRegistrations->pluck('id');

            if ($this->option('sync')) {
                (new SendEvaluationReminderJob($user, $registrationIds))->handle();
            } else {
                SendEvaluationReminderJob::dispatch($user, $registrationIds);
            }

            $dispatched++;

            $seminarCount = $userRegistrations->count();
            $this->line("  - {$user->email}: {$seminarCount} seminar(s)");
        }

        $this->info("Dispatched {$dispatched} evaluation reminder(s).");

        return self::SUCCESS;
    }
}
