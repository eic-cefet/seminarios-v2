<?php

namespace App\Http\Requests\Admin;

use App\Support\SemesterRange;
use Illuminate\Foundation\Http\FormRequest;

class AdminStudentSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'semester' => ['nullable', 'string', 'regex:/^\d{4}\.[12]$/'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function semesterRange(): SemesterRange
    {
        $semester = $this->validated('semester');

        return $semester ? SemesterRange::fromString($semester) : SemesterRange::current();
    }
}
