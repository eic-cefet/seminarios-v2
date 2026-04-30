<?php

namespace App\Http\Resources\External;

use App\Models\SeminarLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SeminarLocation */
class ExternalLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'max_vacancies' => (int) $this->max_vacancies,
        ];
    }
}
