<?php

namespace App\Http\Resources;

use App\Models\AlertPreference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AlertPreference
 */
class AlertPreferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'newSeminarAlert' => (bool) $this->new_seminar_alert,
            'seminarTypeIds' => $this->seminarTypes->pluck('id')->values()->all(),
            'subjectIds' => $this->subjects->pluck('id')->values()->all(),
            'seminarReminder7d' => (bool) $this->seminar_reminder_7d,
            'seminarReminder24h' => (bool) $this->seminar_reminder_24h,
            'evaluationPrompt' => (bool) $this->evaluation_prompt,
            'announcements' => (bool) $this->announcements,
            'workshopAnnouncements' => (bool) $this->workshop_announcements,
            'certificateReady' => (bool) $this->certificate_ready,
            'seminarRescheduled' => (bool) $this->seminar_rescheduled,
        ];
    }
}
