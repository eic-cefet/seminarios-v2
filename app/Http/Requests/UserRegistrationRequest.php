<?php

namespace App\Http\Requests;

use App\Enums\CourseRole;
use App\Enums\CourseSituation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class UserRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'course_situation' => ['required', Rule::enum(CourseSituation::class)],
            'course_role' => ['required', Rule::enum(CourseRole::class)],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'O e-mail deve ser válido.',
            'email.unique' => 'Este e-mail já está em uso.',
            'password.required' => 'A senha é obrigatória.',
            'password.confirmed' => 'As senhas não coincidem.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'course_situation.required' => 'A situação do curso é obrigatória.',
            'course_role.required' => 'O papel no curso é obrigatório.',
        ];
    }
}
