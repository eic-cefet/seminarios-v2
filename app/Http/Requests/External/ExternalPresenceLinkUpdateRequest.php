<?php

namespace App\Http\Requests\External;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class ExternalPresenceLinkUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'active' => ['sometimes', 'boolean'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->has('active') && ! $this->has('expires_at')) {
                $v->errors()->add(
                    'body',
                    'At least one of `active` or `expires_at` must be provided.'
                );
            }
        });
    }
}
