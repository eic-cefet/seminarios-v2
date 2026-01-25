<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWorkshopResource extends JsonResource
{
    use FormatsDates;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'seminars_count' => $this->whenCounted('seminars'),
            'seminars' => $this->whenLoaded('seminars', function () {
                return $this->seminars->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'slug' => $s->slug,
                    'scheduled_at' => $this->formatDate($s->scheduled_at),
                ]);
            }),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
