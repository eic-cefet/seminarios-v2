<?php

namespace App\Http\Requests\Admin;

use App\Http\Controllers\Admin\AdminApiTokenController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminApiTokenUpdateRequest extends FormRequest
{
    /**
     * Authorization is enforced by the `admin` middleware on the route group
     * in routes/admin.php — no per-action gate is needed.
     */
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
