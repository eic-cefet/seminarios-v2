<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeminarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $durationMinutes = (int) ($this->duration_minutes ?? 60);
        $endsAt = $this->scheduled_at?->copy()->addMinutes($durationMinutes);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'ends_at' => $endsAt?->toIso8601String(),
            'duration_minutes' => $durationMinutes,
            'room_link' => $this->room_link,
            'active' => $this->active,
            'is_expired' => $this->scheduled_at?->isPast() ?? false,

            // Relationships
            'seminar_type' => new SeminarTypeResource($this->whenLoaded('seminarType')),
            'workshop' => new WorkshopResource($this->whenLoaded('workshop')),
            'subjects' => SubjectResource::collection($this->whenLoaded('subjects')),
            'speakers' => SpeakerResource::collection($this->whenLoaded('speakers')),
            'location' => $this->whenLoaded('seminarLocation', fn () => [
                'id' => $this->seminarLocation->id,
                'name' => $this->seminarLocation->name,
            ]),

            // Counts
            'registrations_count' => $this->whenCounted('registrations'),

            // Aggregations
            'average_rating' => $this->whenHas(
                'ratings_avg_score',
                fn ($value) => $value === null ? null : round((float) $value, 1),
            ),
        ];
    }
}
