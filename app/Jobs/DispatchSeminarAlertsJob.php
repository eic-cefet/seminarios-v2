<?php

namespace App\Jobs;

use App\Concerns\TracksAuditContext;
use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Mail\NewSeminarAlert;
use App\Models\AuditLog;
use App\Models\Seminar;
use App\Services\SeminarAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DispatchSeminarAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TracksAuditContext;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Seminar $seminar) {}

    public function handle(SeminarAlertService $service): void
    {
        $this->setAuditContext();

        if (! $this->seminar->active) {
            return;
        }

        $recipients = $service->matchingRecipients($this->seminar);
        $dispatched = 0;

        foreach ($recipients as $user) {
            $inserted = DB::table('seminar_alert_dispatches')->insertOrIgnore([
                'user_id' => $user->id,
                'seminar_id' => $this->seminar->id,
                'sent_at' => now(),
            ]);

            if ($inserted === 0) {
                continue;
            }

            Mail::to($user)->queue(new NewSeminarAlert($user, $this->seminar));
            $dispatched++;
        }

        if ($dispatched > 0) {
            AuditLog::record(AuditEvent::SeminarAlertDispatched, AuditEventType::System, $this->seminar, [
                'dispatched' => $dispatched,
            ]);
        }
    }
}
