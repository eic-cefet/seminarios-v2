<?php

namespace App\Http\Resources;

use App\Models\SeminarType;
use App\Support\PresentationTypeGrammar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PendingEvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seminar' => [
                'id' => $this->seminar->id,
                'name' => $this->seminar->name,
                'slug' => $this->seminar->slug,
                'scheduled_at' => $this->seminar->scheduled_at?->toISOString(),
                'seminar_type' => $this->formatSeminarType($this->seminar->seminarType),
                'location' => $this->seminar->seminarLocation ? [
                    'id' => $this->seminar->seminarLocation->id,
                    'name' => $this->seminar->seminarLocation->name,
                ] : null,
            ],
        ];
    }

    private function formatSeminarType(?SeminarType $type): ?array
    {
        if ($type === null) {
            return null;
        }

        $grammar = PresentationTypeGrammar::for($type->name);

        return [
            'id' => $type->id,
            'name' => $type->name,
            'gender' => $grammar->gender(),
            'noun' => $grammar->noun(),
        ];
    }
}
