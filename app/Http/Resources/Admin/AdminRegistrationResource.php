<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRegistrationResource extends JsonResource
{
    use FormatsDates;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'present' => $this->present,
            'reminder_sent' => $this->reminder_sent,
            'certificate_code' => $this->certificate_code,
            'certificate_sent' => $this->certificate_sent,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'seminar' => $this->whenLoaded('seminar', fn () => [
                'id' => $this->seminar->id,
                'name' => $this->seminar->name,
                'slug' => $this->seminar->slug,
                'scheduled_at' => $this->formatDate($this->seminar->scheduled_at),
            ]),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
