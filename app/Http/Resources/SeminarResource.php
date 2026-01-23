<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeminarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'scheduledAt' => $this->scheduled_at?->toIso8601String(),
            'roomLink' => $this->room_link,
            'active' => $this->active,
            'isExpired' => $this->scheduled_at?->isPast() ?? false,

            // Relationships
            'seminarType' => new SeminarTypeResource($this->whenLoaded('seminarType')),
            'workshop' => new WorkshopResource($this->whenLoaded('workshop')),
            'subjects' => SubjectResource::collection($this->whenLoaded('subjects')),
            'speakers' => SpeakerResource::collection($this->whenLoaded('speakers')),
            'location' => $this->whenLoaded('seminarLocation', fn () => [
                'id' => $this->seminarLocation->id,
                'name' => $this->seminarLocation->name,
                'address' => $this->seminarLocation->address,
            ]),

            // Counts
            'registrationsCount' => $this->whenCounted('registrations'),

            // Aggregations
            'averageRating' => $this->whenHas('ratings_avg_score', fn () => round($this->ratings_avg_score, 1)),
        ];
    }
}
