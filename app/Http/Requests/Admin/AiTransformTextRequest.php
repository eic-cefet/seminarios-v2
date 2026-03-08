<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiTransformTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:10000'],
            'action' => ['required', 'string', Rule::in([
                'format_markdown',
                'shorten',
                'explain',
                'formal',
                'casual',
            ])],
        ];
    }
}
