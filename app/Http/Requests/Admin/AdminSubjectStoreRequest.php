<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\SubjectValidationMessages;
use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AdminSubjectStoreRequest extends FormRequest
{
    use SubjectValidationMessages;

    public function authorize(): bool
    {
        return Gate::allows('create', Subject::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:subjects,name'],
        ];
    }
}
