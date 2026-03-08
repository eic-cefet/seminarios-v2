<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AiSuggestSeminarNameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*' => ['required', 'string', 'max:255'],
            'speakers' => ['sometimes', 'array'],
            'speakers.*' => ['string', 'max:255'],
        ];
    }
}
