<?php

namespace App\Jobs;

use App\Mail\AccountAnonymized;
use App\Models\User;
use App\Services\UserAnonymizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class AnonymizeUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(public int $userId) {}

    public function handle(UserAnonymizationService $service): void
    {
        $user = User::withTrashed()->findOrFail($this->userId);

        if ($user->anonymized_at !== null) {
            return;
        }

        $originalEmail = $user->email;
        $originalName = $user->name;

        $service->anonymize($user);

        Mail::to($originalEmail)->queue(new AccountAnonymized($originalName));
    }
}
