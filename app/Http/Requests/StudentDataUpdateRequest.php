<?php

namespace App\Http\Requests;

use App\Enums\CourseRole;
use App\Enums\CourseSituation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentDataUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'course_situation' => ['required', Rule::enum(CourseSituation::class)],
            'course_role' => ['required', Rule::enum(CourseRole::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'course_situation.required' => 'A situação do curso é obrigatória.',
            'course_role.required' => 'O papel no curso é obrigatório.',
            'course_id.exists' => 'O curso selecionado não existe.',
        ];
    }
}
