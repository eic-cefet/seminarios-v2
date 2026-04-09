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
            'roles' => $user->getRoleNames()->toArray(),
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
