<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AdminLgpdAnonymizeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('anonymizeUser', User::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
