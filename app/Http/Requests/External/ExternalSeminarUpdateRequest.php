<?php

namespace App\Http\Requests\External;

use Dedoc\Scramble\Attributes\SchemaName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

#[SchemaName('SeminarUpdateRequest', input: 'SeminarUpdateRequest')]
class ExternalSeminarUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('seminar'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scheduled_at' => ['sometimes', 'date'],
            'room_link' => ['nullable', 'url', 'max:500'],
            'active' => ['sometimes', 'boolean'],

            'location' => ['sometimes', 'array'],
            'location.name' => ['required_with:location', 'string', 'max:255'],
            'location.max_vacancies' => ['nullable', 'integer', 'min:1'],

            'seminar_type_id' => ['nullable', 'integer', 'exists:seminar_types,id'],
            'workshop_id' => ['nullable', 'integer', 'exists:workshops,id'],

            'subjects' => ['sometimes', 'array', 'min:1'],
            'subjects.*' => ['required', 'string', 'max:255'],

            'speakers' => ['sometimes', 'array', 'min:1'],
            'speakers.*.name' => ['required', 'string', 'max:255'],
            'speakers.*.email' => ['required', 'email', 'max:255'],
            'speakers.*.institution' => ['nullable', 'string', 'max:255'],
            'speakers.*.description' => ['nullable', 'string'],
        ];
    }
}
