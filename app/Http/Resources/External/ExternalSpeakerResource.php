<?php

namespace App\Http\Resources\External;

use App\Http\Resources\External\Concerns\SparseFieldset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class ExternalSpeakerResource extends JsonResource
{
    use SparseFieldset;

    /** @return list<string> */
    public function availableFields(): array
    {
        return ['id', 'name', 'email', 'institution', 'description'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'institution' => $this->speakerData?->institution,
            'description' => $this->speakerData?->description,
        ];
    }
}
