<?php

namespace App\Services;

use Illuminate\Support\Str;

class IpHasher
{
    public function hash(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        $network = $this->normalizeToNetwork($ip);
        if ($network === null) {
            return null;
        }

        return hash_hmac('sha256', $network, $this->salt());
    }

    public function hashOpaque(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return hash_hmac('sha256', $value, $this->salt());
    }

    /**
     * Verify the hash salt is configured. Use as a pre-flight check before
     * destructive backfills so migrations fail fast without leaving the
     * schema in a half-migrated state.
     *
     * @throws \RuntimeException
     */
    public function assertConfigured(): void
    {
        $this->salt();
    }

    private function salt(): string
    {
        $salt = Str::after((string) config('app.key', ''), 'base64:');

        if ($salt === '') {
            throw new \RuntimeException('APP_KEY is empty — cannot derive audit hash salt.');
        }

        return $salt;
    }

    private function normalizeToNetwork(string $ip): ?string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);

            return "{$parts[0]}.{$parts[1]}.{$parts[2]}.0";
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);
            if ($packed === false) {
                return null; // @codeCoverageIgnore
            }
            // /48 prefix = first 6 bytes; zero-fill the remainder.
            $masked = substr($packed, 0, 6).str_repeat("\0", 10);
            $expanded = inet_ntop($masked);

            return $expanded === false ? null : $expanded;
        }

        return null;
    }
}
