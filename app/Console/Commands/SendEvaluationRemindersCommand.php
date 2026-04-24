<?php

namespace App\Console\Commands;

use App\Concerns\TracksAuditContext;
use App\Console\Commands\Concerns\DispatchesGroupedJobs;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Jobs\SendEvaluationReminderJob;
use App\Models\AuditLog;
use App\Models\Registration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendEvaluationRemindersCommand extends Command
{
    use DispatchesGroupedJobs, TracksAuditContext;

    protected $signature = 'reminders:evaluations
                            {--sync : Send emails synchronously instead of queuing}';

    protected $description = 'Send evaluation reminders to users who attended seminars 2+ days ago but haven\'t evaluated yet';

    public function handle(): int
    {
        $this->setAuditContext();
        $this->info('Finding users with pending evaluations...');

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
            ->where(function ($q) {
                $q->whereDoesntHave('user.alertPreference')
                    ->orWhereHas('user.alertPreference', fn ($q2) => $q2->where('evaluation_prompt', true));
            })
            ->with(['user', 'seminar'])
            ->get();

        if ($registrations->isEmpty()) {
            $this->info('No pending evaluations found.');

            return self::SUCCESS;
        }

        $dispatched = $this->dispatchGroupedByUser($registrations, SendEvaluationReminderJob::class, 'users with pending evaluations');

        $this->info("Dispatched {$dispatched} evaluation reminder(s).");

        if ($dispatched > 0) {
            AuditLog::record(AuditEvent::EvaluationRemindersSent, AuditEventType::System, eventData: [
                'dispatched' => $dispatched,
            ]);
        }

        return self::SUCCESS;
    }
}
