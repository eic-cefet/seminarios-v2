<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserCertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'certificate_code' => $this->certificate_code,
            'seminar' => [
                'id' => $this->seminar->id,
                'name' => $this->seminar->name,
                'slug' => $this->seminar->slug,
                'scheduled_at' => $this->seminar->scheduled_at?->toISOString(),
                'seminar_type' => $this->seminar->seminarType ? [
                    'id' => $this->seminar->seminarType->id,
                    'name' => $this->seminar->seminarType->name,
                ] : null,
            ],
        ];
    }
}
