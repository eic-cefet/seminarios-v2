<?php

namespace App\Http\Resources\External;

use App\Http\Resources\External\Concerns\SparseFieldset;
use App\Models\SeminarType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SeminarType */
class ExternalSeminarTypeResource extends JsonResource
{
    use SparseFieldset;

    /** @return list<string> */
    public function availableFields(): array
    {
        return ['id', 'name'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
