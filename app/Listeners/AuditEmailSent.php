<?php

namespace App\Listeners;

use App\Enums\AuditEvent;
use App\Enums\AuditEventType;
use App\Models\AuditLog;
use App\Services\FeatureFlags;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;

class AuditEmailSent
{
    public function handle(MessageSent $event): void
    {
        if (! FeatureFlags::enabled('email_audit')) {
            return;
        }

        $message = $event->sent->getOriginalMessage();
        $headers = $message->getHeaders();

        $refIdHeader = $headers->get('X-Entity-Ref-ID');

        if (! $refIdHeader) {
            return;
        }

        $mailClassHeader = $headers->get('X-Mail-Class');

        AuditLog::record(
            AuditEvent::EmailSent,
            AuditEventType::System,
            eventData: [
                'mail' => $mailClassHeader?->getBodyAsString(),
                'to' => array_map(
                    fn (Address $address) => $address->getAddress(),
                    $message->getTo(),
                ),
                'subject' => $message->getSubject(),
                'ref_id' => $refIdHeader->getBodyAsString(),
            ],
        );
    }
}
