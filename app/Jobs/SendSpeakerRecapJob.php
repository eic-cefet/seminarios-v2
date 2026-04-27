<?php

namespace App\Jobs;

use App\Mail\SpeakerSeminarRecap;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendSpeakerRecapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public User $speaker, public int $seminarId) {}

    public function handle(): void
    {
        $seminar = Seminar::find($this->seminarId);
        if (! $seminar) {
            return;
        }

        $claimed = DB::table('seminar_speaker')
            ->where('seminar_id', $this->seminarId)
            ->where('user_id', $this->speaker->id)
            ->whereNull('recap_sent_at')
            ->update(['recap_sent_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        $count = Registration::where('seminar_id', $this->seminarId)
            ->where('present', true)
            ->count();

        Mail::to($this->speaker)->queue(new SpeakerSeminarRecap($this->speaker, $seminar, $count));
    }
}
