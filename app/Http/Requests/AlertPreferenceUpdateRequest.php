<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AlertPreferenceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'opted_in' => ['required', 'boolean'],
            'seminar_type_ids' => ['array'],
            'seminar_type_ids.*' => ['integer', 'exists:seminar_types,id'],
            'subject_ids' => ['array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ];
    }
}
