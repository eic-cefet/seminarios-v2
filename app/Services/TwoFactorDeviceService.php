<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class TwoFactorDeviceService
{
    public const TTL_DAYS = 30;

    public const COOKIE_NAME = 'mfa_device';

    public function issue(User $user, ?string $label, ?string $ip): string
    {
        $token = Str::random(64);

        $user->trustedDevices()->create([
            'token_hash' => hash('sha256', $token),
            'label' => $label,
            'ip' => $ip,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(self::TTL_DAYS),
        ]);

        return $token;
    }

    public function isTrusted(User $user, ?string $token): bool
    {
        if (! $token) {
            return false;
        }

        $device = $user->trustedDevices()
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();

        if (! $device) {
            return false;
        }

        $device->update(['last_used_at' => now()]);

        return true;
    }

    public function revoke(User $user, int $deviceId): void
    {
        $user->trustedDevices()->whereKey($deviceId)->delete();
    }

    public function revokeAll(User $user): void
    {
        $user->trustedDevices()->delete();
    }
}
