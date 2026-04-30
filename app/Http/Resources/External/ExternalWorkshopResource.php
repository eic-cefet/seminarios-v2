<?php

namespace App\Http\Resources\External;

use App\Http\Resources\Concerns\FormatsDates;
use App\Http\Resources\External\Concerns\SparseFieldset;
use App\Models\Workshop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Workshop */
class ExternalWorkshopResource extends JsonResource
{
    use FormatsDates, SparseFieldset;

    /** @return list<string> */
    public function availableFields(): array
    {
        return ['id', 'name', 'slug', 'description', 'seminars_count', 'created_at', 'updated_at'];
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
            'seminars_count' => $this->whenCounted('seminars'),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
