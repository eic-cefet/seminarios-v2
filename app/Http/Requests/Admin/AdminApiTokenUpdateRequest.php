<?php

namespace App\Http\Requests\Admin;

use App\Http\Controllers\Admin\AdminApiTokenController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminApiTokenUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'abilities' => ['sometimes', 'array'],
            'abilities.*' => ['string', Rule::in(AdminApiTokenController::AVAILABLE_ABILITIES)],
        ];
    }
}
