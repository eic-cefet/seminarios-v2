<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'score.required' => 'A nota é obrigatória.',
            'score.integer' => 'A nota deve ser um número inteiro.',
            'score.min' => 'A nota deve ser no mínimo 1.',
            'score.max' => 'A nota deve ser no máximo 5.',
            'comment.max' => 'O comentário não pode ter mais de 1000 caracteres.',
        ];
    }
}
