<?php

namespace App\Http\Resources\External;

use App\Models\UserSpeakerData;
use Dedoc\Scramble\Attributes\SchemaName;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserSpeakerData */
#[SchemaName('SpeakerData')]
class ExternalSpeakerDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'institution' => $this->institution,
            'description' => $this->description,
        ];
    }
}
