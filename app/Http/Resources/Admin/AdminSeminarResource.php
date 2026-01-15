<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSeminarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'scheduled_at' => $this->scheduled_at,
            'link' => $this->link,
            'active' => $this->active,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'seminar_type' => $this->whenLoaded('seminarType', fn () => $this->seminarType ? [
                'id' => $this->seminarType->id,
                'name' => $this->seminarType->name,
            ] : null),
            'location' => $this->whenLoaded('seminarLocation', fn () => $this->seminarLocation ? [
                'id' => $this->seminarLocation->id,
                'name' => $this->seminarLocation->name,
                'max_vacancies' => $this->seminarLocation->max_vacancies,
            ] : null),
            'workshop' => $this->whenLoaded('workshop', fn () => $this->workshop ? [
                'id' => $this->workshop->id,
                'name' => $this->workshop->name,
            ] : null),
            'subjects' => $this->whenLoaded('subjects', fn () => $this->subjects->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
            ])),
            'speakers' => $this->whenLoaded('speakers', fn () => $this->speakers->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
            ])),
            'registrations_count' => $this->whenCounted('registrations'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
