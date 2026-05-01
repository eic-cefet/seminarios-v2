<?php

namespace App\Http\Requests\Admin;

use App\Models\SeminarLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AdminLocationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $location = $this->route('location');

        return $location instanceof SeminarLocation && Gate::allows('update', $location);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'max_vacancies' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
