<?php

namespace App\Jobs;

use App\Enums\CommunicationCategory;
use App\Mail\WorkshopAvailable;
use App\Models\User;
use App\Models\Workshop;
use App\Services\CommunicationGate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendWorkshopAvailableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public User $user, public int $workshopId) {}

    public function handle(?CommunicationGate $gate = null): void
    {
        $gate ??= app(CommunicationGate::class);

        if (! $gate->canEmail($this->user, CommunicationCategory::WorkshopAnnouncements)) {
            return;
        }

        $workshop = Workshop::find($this->workshopId);
        if (! $workshop) {
            return;
        }

        Mail::to($this->user)->queue(new WorkshopAvailable($this->user, $workshop));
    }
}
