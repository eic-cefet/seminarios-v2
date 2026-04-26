<?php

namespace App\Services;

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

    private function salt(): string
    {
        $salt = (string) config('audit.hash_salt');

        if ($salt === '') {
            throw new \RuntimeException(
                'audit.hash_salt is empty — set AUDIT_HASH_SALT or APP_KEY before hashing.'
            );
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
