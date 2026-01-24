<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BugReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'files' => ['nullable', 'array', 'max:3'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,gif,pdf', 'max:1024'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'O assunto é obrigatório.',
            'subject.max' => 'O assunto não pode ter mais de 255 caracteres.',
            'message.required' => 'A mensagem é obrigatória.',
            'message.max' => 'A mensagem não pode ter mais de 5000 caracteres.',
            'files.max' => 'Você pode enviar no máximo 3 arquivos.',
            'files.*.file' => 'O arquivo enviado é inválido.',
            'files.*.mimes' => 'Apenas arquivos JPG, PNG, GIF ou PDF são permitidos.',
            'files.*.max' => 'Cada arquivo deve ter no máximo 1MB.',
        ];
    }
}
