<?php

namespace App\Http\Requests\Admin;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AdminSubjectMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('merge', Subject::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'target_id' => ['required', 'integer', 'exists:subjects,id'],
            'source_ids' => ['required', 'array', 'min:1'],
            'source_ids.*' => ['required', 'integer', 'exists:subjects,id', 'different:target_id'],
            'new_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_id.required' => 'O tópico de destino é obrigatório.',
            'target_id.exists' => 'O tópico de destino não existe.',
            'source_ids.required' => 'Ao menos um tópico de origem é obrigatório.',
            'source_ids.min' => 'Ao menos um tópico de origem é obrigatório.',
            'source_ids.*.exists' => 'Um dos tópicos de origem não existe.',
            'source_ids.*.different' => 'Um tópico não pode ser mesclado consigo mesmo.',
            'new_name.max' => 'O novo nome não pode ter mais de 255 caracteres.',
        ];
    }
}
