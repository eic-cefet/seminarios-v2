<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourseSituation;
use App\Http\Requests\Admin\Concerns\UserValidationMessages;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdminUserStoreRequest extends FormRequest
{
    use UserValidationMessages;

    public function authorize(): bool
    {
        return Gate::allows('create', User::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['nullable', 'string', Rule::in(['admin', 'teacher'])],
            'student_data' => ['nullable', 'array'],
            'student_data.course_name' => ['nullable', 'string', 'max:255'],
            'student_data.course_situation' => ['nullable', 'string', Rule::enum(CourseSituation::class)],
            'student_data.course_role' => ['nullable', 'string'],
            'speaker_data' => ['nullable', 'array'],
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
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'role.in' => 'O papel deve ser admin ou teacher.',
        ];
    }
}
