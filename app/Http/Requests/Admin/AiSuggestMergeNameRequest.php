<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AiSuggestMergeNameRequest extends FormRequest
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
            'names' => ['required', 'array', 'min:2'],
            'names.*' => ['required', 'string', 'max:255'],
        ];
    }
}
