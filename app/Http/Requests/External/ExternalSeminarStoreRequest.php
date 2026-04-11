<?php

namespace App\Http\Requests\External;

use App\Models\Seminar;
use Dedoc\Scramble\Attributes\SchemaName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

#[SchemaName('SeminarStoreRequest', input: 'SeminarStoreRequest')]
class ExternalSeminarStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Seminar::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scheduled_at' => ['required', 'date'],
            'room_link' => ['nullable', 'url', 'max:500'],
            'active' => ['sometimes', 'boolean'],

            'seminar_location_id' => ['required', 'integer', 'exists:seminar_locations,id'],

            'seminar_type_id' => ['nullable', 'integer', 'exists:seminar_types,id'],
            'workshop_id' => ['nullable', 'integer', 'exists:workshops,id'],

            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*' => ['required', 'string', 'max:255'],

            'speaker_ids' => ['required', 'array', 'min:1'],
            'speaker_ids.*' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
