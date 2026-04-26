<?php

namespace App\Jobs;

use App\Mail\SpeakerSeminarReminder;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendSpeakerReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public User $speaker, public int $seminarId) {}

    public function handle(): void
    {
        $claimed = DB::table('seminar_speaker')
            ->where('seminar_id', $this->seminarId)
            ->where('user_id', $this->speaker->id)
            ->whereNull('reminder_24h_sent_at')
            ->update(['reminder_24h_sent_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        $seminar = Seminar::with('seminarLocation')->find($this->seminarId);
        if (! $seminar) {
            return; // @codeCoverageIgnore
        }

        Mail::to($this->speaker)->queue(new SpeakerSeminarReminder($this->speaker, $seminar));
    }
}
