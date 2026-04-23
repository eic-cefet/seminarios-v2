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
            'seminarTypeIds' => array_values($this->seminar_type_ids ?? []),
            'subjectIds' => array_values($this->subject_ids ?? []),
        ];
    }
}
