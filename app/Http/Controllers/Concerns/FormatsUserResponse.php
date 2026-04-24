<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;

trait FormatsUserResponse
{
    private function formatUserResponse(User $user): array
    {
        $user->load('studentData.course');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'anonymization_requested_at' => $user->anonymization_requested_at?->toIso8601String(),
            'roles' => $user->getRoleNames()->toArray(),
            'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
            'student_data' => $user->studentData ? [
                'course_situation' => $user->studentData->course_situation,
                'course_role' => $user->studentData->course_role,
                'course' => $user->studentData->course ? [
                    'id' => $user->studentData->course->id,
                    'name' => $user->studentData->course->name,
                ] : null,
            ] : null,
        ];
    }
}
