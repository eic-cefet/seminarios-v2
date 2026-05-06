<?php

namespace App\Http\Requests\External;

use App\Enums\Gender;
use App\Models\SeminarType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ExternalSeminarTypeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', SeminarType::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:seminar_types,name'],
            'name_plural' => ['nullable', 'string', 'max:255'],
            'gender' => ['sometimes', 'string', Rule::enum(Gender::class)],
        ];
    }
}
