<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminLocationResource extends JsonResource
{
    use FormatsDates;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'max_vacancies' => $this->max_vacancies,
            'seminars_count' => $this->whenCounted('seminars'),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
