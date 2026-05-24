<?php

namespace App\Listeners;

use App\Enums\AuditEvent;
use App\Mail\AccountDeletionCancelled;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class CancelPendingDeletionOnLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || ! $user->isAnonymizationPending()) {
            return;
        }

        $user->forceFill(['anonymization_requested_at' => null])->save();
        Cache::forget("lgpd.deletion-pending:{$user->id}");
        AuditLog::record(event: AuditEvent::AccountDeletionCancelled, auditable: $user);
        Mail::to($user->email)->queue(new AccountDeletionCancelled($user));
    }
}
