<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class SemestralReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasAnyRole([Role::Admin, Role::Teacher]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'semester' => ['required', 'string', 'regex:/^\d{4}\.[12]$/'],
            'courses' => ['nullable', 'array'],
            'courses.*' => ['integer'],
            'types' => ['nullable', 'array'],
            'types.*' => ['integer'],
            'situations' => ['nullable', 'array'],
            'situations.*' => ['string'],
            'format' => ['required', 'in:browser,excel'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'semester.required' => 'O semestre é obrigatório.',
            'semester.regex' => 'O semestre deve estar no formato AAAA.S (ex: 2026.1).',
            'format.required' => 'O formato é obrigatório.',
            'format.in' => 'O formato deve ser browser ou excel.',
        ];
    }
}
