<?php

namespace App\Http\Resources\External;

use App\Http\Resources\Concerns\FormatsDates;
use App\Http\Resources\External\Concerns\SparseFieldset;
use App\Models\Seminar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Seminar */
class ExternalSeminarResource extends JsonResource
{
    use FormatsDates, SparseFieldset;

    /** @return list<string> */
    public function availableFields(): array
    {
        return [
            'id', 'name', 'slug', 'description', 'scheduled_at', 'room_link', 'active',
            'location', 'seminar_type', 'workshop', 'subjects', 'speakers',
            'created_at', 'updated_at',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'scheduled_at' => $this->formatDate($this->scheduled_at),
            'room_link' => $this->room_link,
            'active' => $this->active,
            'location' => $this->seminarLocation ? [
                'id' => $this->seminarLocation->id,
                'name' => $this->seminarLocation->name,
                'max_vacancies' => (int) $this->seminarLocation->max_vacancies,
            ] : null,
            'seminar_type' => $this->seminarType?->name,
            'workshop' => $this->workshop ? [
                'id' => $this->workshop->id,
                'name' => $this->workshop->name,
            ] : null,
            /** @var list<string> */
            'subjects' => $this->subjects->pluck('name')->values()->all(),
            'speakers' => ExternalSpeakerResource::collection($this->speakers),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
