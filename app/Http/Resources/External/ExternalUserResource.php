<?php

namespace App\Http\Resources\External;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class ExternalUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'speaker_data' => $this->speakerData ? new ExternalSpeakerDataResource($this->speakerData) : null,
        ];
    }
}
