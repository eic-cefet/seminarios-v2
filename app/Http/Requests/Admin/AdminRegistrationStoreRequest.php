<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class AdminRegistrationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasAnyRole([Role::Admin, Role::Teacher]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'seminar_id' => ['required', 'integer', 'exists:seminars,id'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'seminar_id.required' => 'A apresentação é obrigatória.',
            'seminar_id.exists' => 'A apresentação informada não existe.',
            'user_ids.required' => 'Informe ao menos um usuário.',
            'user_ids.array' => 'Informe ao menos um usuário.',
            'user_ids.min' => 'Informe ao menos um usuário.',
            'user_ids.*.exists' => 'Um dos usuários informados não existe.',
            'user_ids.*.distinct' => 'Há usuários duplicados na lista.',
        ];
    }
}
