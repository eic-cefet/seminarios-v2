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
            'seminar' => [
                'id' => $this->seminar->id,
                'name' => $this->seminar->name,
                'slug' => $this->seminar->slug,
                'scheduled_at' => $this->seminar->scheduled_at?->toISOString(),
                'is_expired' => $this->seminar->scheduled_at?->isPast() ?? false,
                'seminar_type' => $this->seminar->seminarType ? [
                    'id' => $this->seminar->seminarType->id,
                    'name' => $this->seminar->seminarType->name,
                ] : null,
                'location' => $this->seminar->seminarLocation ? [
                    'id' => $this->seminar->seminarLocation->id,
                    'name' => $this->seminar->seminarLocation->name,
                ] : null,
            ],
        ];
    }
}
