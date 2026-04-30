<?php

namespace App\Http\Requests\External;

use App\Models\SeminarType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

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
        ];
    }
}
