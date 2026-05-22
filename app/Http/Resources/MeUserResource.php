<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeUserResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->load('studentData.course');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'anonymization_requested_at' => $this->anonymization_requested_at?->toIso8601String(),
            'roles' => $this->getRoleNames()->toArray(),
            'two_factor_enabled' => $this->two_factor_confirmed_at !== null,
            'needs_profile_completion' => $this->hasIncompleteProfile(),
            'student_data' => $this->studentData ? [
                'course_situation' => $this->studentData->course_situation,
                'course_role' => $this->studentData->course_role,
                'course' => $this->studentData->course ? [
                    'id' => $this->studentData->course->id,
                    'name' => $this->studentData->course->name,
                ] : null,
            ] : null,
        ];
    }
}
