<?php

namespace App\Http\Requests\Admin\Concerns;

trait UserValidationMessages
{
    /**
     * Messages shared between AdminUserStoreRequest and AdminUserUpdateRequest.
     *
     * @return array<string, string>
     */
    protected function sharedUserMessages(): array
    {
        return [
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.email' => 'O e-mail deve ser válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'student_data.course_situation.enum' => 'A situação do curso é inválida.',
            'speaker_data.institution.max' => 'A instituição não pode ter mais de 255 caracteres.',
        ];
    }
}
