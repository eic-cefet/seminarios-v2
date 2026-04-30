<?php

namespace App\Http\Requests\External;

use App\Models\SeminarType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

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
        ];
    }
}
