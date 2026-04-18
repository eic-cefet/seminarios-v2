<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourseSituation;
use App\Http\Requests\Admin\Concerns\UserValidationMessages;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdminUserUpdateRequest extends FormRequest
{
    use UserValidationMessages;

    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && Gate::allows('update', $user);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['sometimes', 'string', Rule::in(['admin', 'teacher', 'user'])],
            'student_data' => ['nullable', 'array'],
            'student_data.course_name' => ['nullable', 'string', 'max:255'],
            'student_data.course_situation' => ['nullable', 'string', Rule::enum(CourseSituation::class)],
            'student_data.course_role' => ['nullable', 'string'],
            'speaker_data' => ['nullable', 'array'],
            'speaker_data.slug' => ['nullable', 'string', 'max:255'],
            'speaker_data.institution' => ['nullable', 'string', 'max:255'],
            'speaker_data.description' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->sharedUserMessages(),
            'role.in' => 'O papel deve ser admin, teacher ou user.',
            'speaker_data.slug.max' => 'O slug não pode ter mais de 255 caracteres.',
        ];
    }
}
