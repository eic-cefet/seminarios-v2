<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Services\DatabaseResetService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResetDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Admin) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'confirmation' => [
                'required',
                'string',
                Rule::in([DatabaseResetService::CONFIRMATION_PHRASE]),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'confirmation.required' => 'Digite APAGAR BANCO para confirmar.',
            'confirmation.in' => 'A frase de confirmação está incorreta.',
        ];
    }
}
