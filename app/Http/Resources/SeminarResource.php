<?php

namespace App\Http\Resources;

use App\Services\RatingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeminarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $durationMinutes = (int) ($this->duration_minutes ?? 60);
        $endsAt = $this->scheduled_at?->copy()->addMinutes($durationMinutes);
        $averageRating = $this->whenHas(
            'ratings_avg_score',
            fn ($value) => $value === null ? null : round((float) $value, 1),
            fn () => ($computed = app(RatingService::class)->averageScore($this->resource)) === null
                ? null
                : round($computed, 1),
        );

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'scheduledAt' => $this->scheduled_at?->toIso8601String(),
            'endsAt' => $endsAt?->toIso8601String(),
            'durationMinutes' => $durationMinutes,
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
            ]),

            // Counts
            'registrationsCount' => $this->whenCounted('registrations'),

            // Aggregations
            'averageRating' => $averageRating,
        ];
    }
}
