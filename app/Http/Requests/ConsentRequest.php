<?php

namespace App\Http\Requests;

use App\Enums\ConsentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ConsentType::class)],
            'granted' => ['required', 'boolean'],
            'version' => ['nullable', 'string', 'max:16'],
            'anonymous_id' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'O tipo de consentimento é obrigatório.',
            'granted.required' => 'Informe se o consentimento foi concedido ou revogado.',
        ];
    }
}
