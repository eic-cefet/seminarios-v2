<?php

namespace App\Http\Requests\External;

use App\Models\Workshop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ExternalWorkshopStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Workshop::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:workshops,name'],
            'description' => ['nullable', 'string'],
        ];
    }
}
