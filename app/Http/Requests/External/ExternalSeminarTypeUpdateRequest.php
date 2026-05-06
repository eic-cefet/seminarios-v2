<?php

namespace App\Http\Requests\External;

use App\Enums\Gender;
use App\Models\SeminarType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ExternalSeminarTypeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SeminarType $type */
        $type = $this->route('seminar_type');

        return Gate::allows('update', $type);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('seminar_type')?->id;

        return [
            'name' => ['required', 'string', 'max:255', "unique:seminar_types,name,{$id}"],
            'name_plural' => ['nullable', 'string', 'max:255'],
            'gender' => ['sometimes', 'string', Rule::enum(Gender::class)],
        ];
    }
}
