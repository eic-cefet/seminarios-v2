<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Exceptions\ApiException;
use App\Services\FeatureFlags;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEnvSecretsRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (FeatureFlags::disabled('env_secrets_setup')) {
            throw ApiException::notFound();
        }

        if (! $this->user()?->hasRole(Role::Admin)) {
            throw ApiException::forbidden();
        }

        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'secret_id' => ['required', 'string', 'max:2048', 'regex:/^[A-Za-z0-9_+=,.@:\/-]+$/'],
            'region' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/'],
            'access_key_id' => ['nullable', 'string', 'max:128', 'regex:/^[A-Za-z0-9]+$/', 'required_with:secret_access_key'],
            'secret_access_key' => ['nullable', 'string', 'max:256', 'regex:/^[A-Za-z0-9\/+=]+$/', 'required_with:access_key_id'],
        ];
    }
}
