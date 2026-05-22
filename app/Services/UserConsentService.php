<?php

namespace App\Services;

use App\Enums\ConsentType;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Http\Request;

final class UserConsentService
{
    public function __construct(private readonly IpHasher $hasher) {}

    /**
     * Record the Terms of Service + Privacy Policy consents collected during signup.
     */
    public function recordSignupConsents(User $user, Request $request, string $source): void
    {
        $ipHash = $this->hasher->hash($request->ip());
        $uaHash = $this->hasher->hashOpaque((string) $request->userAgent());

        foreach ([ConsentType::TermsOfService, ConsentType::PrivacyPolicy] as $type) {
            UserConsent::create([
                'user_id' => $user->id,
                'type' => $type,
                'granted' => true,
                'version' => config('lgpd.versions.'.$type->value) ?? '1.0',
                'ip_hash' => $ipHash,
                'user_agent_hash' => $uaHash,
                'source' => $source,
            ]);
        }
    }
}
