<?php

namespace App\Console\Commands;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Jobs\SendSpeakerRecapJob;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendSpeakerRecapsCommand extends Command
{
    use TracksAuditContext;

    protected $signature = 'reminders:speaker-recaps';

    protected $description = 'Send attendance recap emails to speakers two days after their seminar';

    public function handle(): int
    {
        $this->setAuditContext();

        $cutoff = now()->subDays(2)->endOfDay();

        $rows = DB::table('seminar_speaker')
            ->join('seminars', 'seminars.id', '=', 'seminar_speaker.seminar_id')
            ->whereNull('seminar_speaker.recap_sent_at')
            ->whereNotNull('seminars.scheduled_at')
            ->where('seminars.scheduled_at', '<=', $cutoff)
            ->select('seminar_speaker.user_id', 'seminar_speaker.seminar_id')
            ->get();

        $count = 0;
        foreach ($rows as $row) {
            $user = User::find($row->user_id);
            if (! $user) {
                continue; // @codeCoverageIgnore
            }
            SendSpeakerRecapJob::dispatch($user, (int) $row->seminar_id);
            $count++;
        }

        $this->info("Dispatched {$count} speaker recap(s).");

        if ($count > 0) {
            AuditLog::record(AuditEvent::SpeakerRecapsSent, AuditEventType::System, eventData: [
                'dispatched' => $count,
            ]);
        }

        return self::SUCCESS;
    }
}
