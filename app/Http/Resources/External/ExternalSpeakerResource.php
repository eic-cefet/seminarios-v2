<?php

namespace App\Http\Resources\External;

use App\Models\User;
use Dedoc\Scramble\Attributes\SchemaName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
#[SchemaName('Speaker')]
class ExternalSpeakerResource extends JsonResource
{
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
