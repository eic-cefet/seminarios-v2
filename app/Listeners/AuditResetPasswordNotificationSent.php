<?php

namespace App\Listeners;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Notifications\ResetPassword;
use App\Services\FeatureFlags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSent;

class AuditResetPasswordNotificationSent
{
    public function handle(NotificationSent $event): void
    {
        if (! $event->notification instanceof ResetPassword) {
            return;
        }

        if ($event->channel !== 'mail') {
            return;
        }

        if (! FeatureFlags::enabled('email_audit')) {
            return;
        }

        $notifiable = $event->notifiable;
        $email = method_exists($notifiable, 'getEmailForPasswordReset')
            ? $notifiable->getEmailForPasswordReset()
            : null;

        AuditLog::record(
            AuditEvent::EmailSent,
            AuditEventType::System,
            auditable: $notifiable instanceof Model ? $notifiable : null,
            eventData: [
                'mail' => ResetPassword::class,
                'to' => $email,
                'ref_id' => $event->notification->refId,
            ],
        );
    }
}
