<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'student_data' => $this->whenLoaded('studentData', fn () => $this->studentData ? [
                'course_name' => $this->studentData->course_name,
                'course_situation' => $this->studentData->course_situation,
                'course_role' => $this->studentData->course_role,
            ] : null),
            'speaker_data' => $this->whenLoaded('speakerData', fn () => $this->speakerData ? [
                'slug' => $this->speakerData->slug,
                'institution' => $this->speakerData->institution,
                'description' => $this->speakerData->description,
            ] : null),
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
