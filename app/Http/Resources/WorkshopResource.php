<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkshopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'seminarsCount' => $this->whenCounted('seminars'),
            'seminars' => SeminarResource::collection($this->whenLoaded('seminars')),
        ];
    }
}
