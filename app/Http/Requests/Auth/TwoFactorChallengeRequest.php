<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'challenge_token' => ['required', 'string'],
            'code' => ['required_without:recovery_code', 'nullable', 'string', 'regex:/^[0-9]{6}$/'],
            'recovery_code' => ['required_without:code', 'nullable', 'string'],
            'remember_device' => ['nullable', 'boolean'],
        ];
    }
}
