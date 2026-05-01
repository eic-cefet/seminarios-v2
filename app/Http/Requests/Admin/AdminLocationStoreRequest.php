<?php

namespace App\Http\Requests\Admin;

use App\Models\SeminarLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AdminLocationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', SeminarLocation::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'max_vacancies' => ['required', 'integer', 'min:1'],
        ];
    }
}
