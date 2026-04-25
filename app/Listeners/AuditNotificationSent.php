<?php

namespace App\Listeners;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Services\FeatureFlags;
use Illuminate\Notifications\Events\NotificationSent;

class AuditNotificationSent
{
    public function handle(NotificationSent $event): void
    {
        if (! FeatureFlags::enabled('notification_audit')) {
            return;
        }

        if ($event->channel !== 'database') {
            return;
        }

        AuditLog::record(
            AuditEvent::NotificationSent,
            AuditEventType::System,
            $event->notifiable,
            [
                'notification' => $event->notification::class,
                'channel' => $event->channel,
                'notifiable_id' => $event->notifiable->getKey(),
            ],
        );
    }
}
