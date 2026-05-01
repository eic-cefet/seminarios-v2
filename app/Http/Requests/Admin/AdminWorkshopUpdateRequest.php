<?php

namespace App\Http\Requests\Admin;

use App\Models\Workshop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdminWorkshopUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workshop = $this->route('workshop');

        return $workshop instanceof Workshop && Gate::allows('update', $workshop);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $workshopId = $this->route('workshop')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('workshops', 'name')->ignore($workshopId)],
            'description' => ['nullable', 'string'],
            'seminar_ids' => ['nullable', 'array'],
            'seminar_ids.*' => ['integer', 'exists:seminars,id'],
        ];
    }
}
