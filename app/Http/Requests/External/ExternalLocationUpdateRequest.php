<?php

namespace App\Http\Requests\External;

use App\Models\SeminarLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ExternalLocationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var SeminarLocation $location */
        $location = $this->route('location');

        return Gate::allows('update', $location);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('location')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255', "unique:seminar_locations,name,{$id}"],
            'max_vacancies' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
