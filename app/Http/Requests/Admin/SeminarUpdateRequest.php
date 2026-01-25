<?php

namespace App\Http\Requests\Admin;

use App\Models\Seminar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SeminarUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $seminar = $this->route('seminar');

        return $seminar instanceof Seminar && Gate::allows('update', $seminar);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scheduled_at' => ['sometimes', 'date'],
            'room_link' => ['nullable', 'url', 'max:500'],
            'active' => ['sometimes', 'boolean'],
            'seminar_location_id' => ['sometimes', 'integer', 'exists:seminar_locations,id'],
            'seminar_type_id' => ['nullable', 'integer', 'exists:seminar_types,id'],
            'workshop_id' => ['nullable', 'integer', 'exists:workshops,id'],
            'subject_names' => ['sometimes', 'array', 'min:1'],
            'subject_names.*' => ['required', 'string', 'max:255'],
            'speaker_ids' => ['sometimes', 'array', 'min:1'],
            'speaker_ids.*' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'scheduled_at.date' => 'A data deve ser válida.',
            'room_link.url' => 'O link da sala deve ser uma URL válida.',
            'seminar_location_id.exists' => 'O local selecionado não existe.',
            'seminar_type_id.exists' => 'O tipo de seminário selecionado não existe.',
            'workshop_id.exists' => 'O workshop selecionado não existe.',
            'subject_names.min' => 'Pelo menos um tópico é obrigatório.',
            'speaker_ids.min' => 'Pelo menos um palestrante é obrigatório.',
            'speaker_ids.*.exists' => 'Um dos palestrantes selecionados não existe.',
        ];
    }
}
