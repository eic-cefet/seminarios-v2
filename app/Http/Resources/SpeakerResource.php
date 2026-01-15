<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpeakerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'speakerData' => $this->whenLoaded('speakerData', fn () => [
                'bio' => $this->speakerData->bio,
                'company' => $this->speakerData->company,
                'position' => $this->speakerData->position,
                'linkedin' => $this->speakerData->linkedin,
                'github' => $this->speakerData->github,
            ]),
        ];
    }
}
