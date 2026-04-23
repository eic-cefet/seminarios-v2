<?php

namespace App\Http\Resources;

use App\Models\AlertPreference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AlertPreference
 */
class AlertPreferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'optedIn' => (bool) $this->opted_in,
            'seminarTypeIds' => $this->seminarTypes->pluck('id')->values()->all(),
            'subjectIds' => $this->subjects->pluck('id')->values()->all(),
        ];
    }
}
