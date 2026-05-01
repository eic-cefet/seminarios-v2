<?php

namespace App\Http\Requests\Admin;

use App\Http\Controllers\Admin\AdminApiTokenController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminApiTokenStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'expires_in_days' => ['nullable', 'integer', 'in:7,30,60,90,180'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', Rule::in(AdminApiTokenController::AVAILABLE_ABILITIES)],
        ];
    }
}
