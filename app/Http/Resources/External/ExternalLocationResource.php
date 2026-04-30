<?php

namespace App\Http\Resources\External;

use App\Http\Resources\External\Concerns\SparseFieldset;
use App\Models\SeminarLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SeminarLocation */
class ExternalLocationResource extends JsonResource
{
    use SparseFieldset;

    /** @return list<string> */
    public function availableFields(): array
    {
        return ['id', 'name', 'max_vacancies'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'max_vacancies' => (int) $this->max_vacancies,
        ];
    }
}
