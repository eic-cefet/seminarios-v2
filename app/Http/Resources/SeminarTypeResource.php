<?php

namespace App\Http\Resources;

use App\Support\PresentationTypeGrammar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeminarTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $grammar = PresentationTypeGrammar::for($this->name);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'gender' => $grammar->gender(),
            'noun' => $grammar->noun(),
        ];
    }
}
