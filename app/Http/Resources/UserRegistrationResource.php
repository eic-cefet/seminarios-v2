<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'present' => $this->present,
            'certificate_code' => $this->certificate_code,
            'created_at' => $this->created_at->toISOString(),
            'seminar' => $this->formatSeminar(includeLocation: true, includeIsExpired: true),
        ];
    }

    protected function formatSeminar(bool $includeLocation = false, bool $includeIsExpired = false): array
    {
        $seminar = $this->seminar;

        $data = [
            'id' => $seminar->id,
            'name' => $seminar->name,
            'slug' => $seminar->slug,
            'scheduled_at' => $seminar->scheduled_at?->toISOString(),
            'seminar_type' => $seminar->seminarType ? [
                'id' => $seminar->seminarType->id,
                'name' => $seminar->seminarType->name,
            ] : null,
        ];

        if ($includeIsExpired) {
            $data['is_expired'] = $seminar->scheduled_at?->isPast() ?? false;
        }

        if ($includeLocation) {
            $data['location'] = $seminar->seminarLocation ? [
                'id' => $seminar->seminarLocation->id,
                'name' => $seminar->seminarLocation->name,
            ] : null;
        }

        return $data;
    }
}
