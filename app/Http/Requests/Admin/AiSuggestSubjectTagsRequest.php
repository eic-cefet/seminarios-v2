<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AiSuggestSubjectTagsRequest extends FormRequest
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
            'subject_names' => ['required', 'array', 'min:1'],
            'subject_names.*' => ['required', 'string', 'max:255'],
        ];
    }
}
