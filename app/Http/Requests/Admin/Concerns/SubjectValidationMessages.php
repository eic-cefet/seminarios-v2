<?php

namespace App\Http\Requests\Admin\Concerns;

trait SubjectValidationMessages
{
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do tópico é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'name.unique' => 'Já existe um tópico com este nome.',
        ];
    }
}
