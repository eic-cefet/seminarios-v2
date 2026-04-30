<?php

namespace App\Http\Requests\External;

use App\Models\Workshop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ExternalWorkshopUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Workshop $workshop */
        $workshop = $this->route('workshop');

        return Gate::allows('update', $workshop);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('workshop')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255', "unique:workshops,name,{$id}"],
            'description' => ['nullable', 'string'],
        ];
    }
}
