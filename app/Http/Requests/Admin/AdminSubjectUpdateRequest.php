<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\SubjectValidationMessages;
use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdminSubjectUpdateRequest extends FormRequest
{
    use SubjectValidationMessages;

    public function authorize(): bool
    {
        $subject = $this->route('subject');

        return $subject instanceof Subject && Gate::allows('update', $subject);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $subjectId = $this->route('subject')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('subjects', 'name')->ignore($subjectId)],
        ];
    }
}
