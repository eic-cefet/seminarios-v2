<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\FormatsUserResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\TwoFactorDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    use FormatsUserResponse;

    private const MAX_ATTEMPTS_PER_TOKEN = 5;

    public function __invoke(TwoFactorChallengeRequest $request, TwoFactorDeviceService $devices): JsonResponse
    {
        $key = "mfa:challenge:{$request->string('challenge_token')}";
        $attemptsKey = "{$key}:attempts";
        $payload = cache()->get($key);

        if (! $payload) {
            throw ApiException::validation(['challenge_token' => 'Desafio inválido ou expirado']);
        }

        if ((int) cache()->get($attemptsKey, 0) >= self::MAX_ATTEMPTS_PER_TOKEN) {
            cache()->forget($key);
            cache()->forget($attemptsKey);

            throw ApiException::validation(['challenge_token' => 'Muitas tentativas. Faça login novamente.']);
        }

        cache()->increment($attemptsKey);
        cache()->put($attemptsKey, (int) cache()->get($attemptsKey), now()->addMinutes(5));

        /** @var User $user */
        $user = User::findOrFail($payload['user_id']);

        if ($request->filled('recovery_code')) {
            $matched = $this->consumeRecoveryCode($user, $request->string('recovery_code'));

            if (! $matched) {
                AuditLog::record(AuditEvent::UserMfaChallengeFailed, auditable: $user, userId: $user->id);

                throw ApiException::validation(['recovery_code' => 'Código de recuperação inválido']);
            }

            AuditLog::record(AuditEvent::UserMfaRecoveryCodeUsed, auditable: $user, userId: $user->id);
        } else {
            $code = $request->string('code')->toString();
            $usedKey = "mfa:used:{$user->id}:{$code}";

            if (cache()->has($usedKey)) {
                AuditLog::record(AuditEvent::UserMfaChallengeFailed, auditable: $user, userId: $user->id);

                throw ApiException::validation(['code' => 'Código já utilizado']);
            }

            $ok = app(Google2FA::class)->verifyKey(decrypt($user->two_factor_secret), $code);

            if (! $ok) {
                AuditLog::record(AuditEvent::UserMfaChallengeFailed, auditable: $user, userId: $user->id);

                throw ApiException::validation(['code' => 'Código inválido']);
            }

            // Block this code from being reused for the rest of its validity window.
            cache()->put($usedKey, true, now()->addMinutes(2));

            AuditLog::record(AuditEvent::UserMfaUsed, auditable: $user, userId: $user->id);
        }

        cache()->forget($key);
        cache()->forget($attemptsKey);

        Auth::login($user, (bool) ($payload['remember'] ?? false));
        AuditLog::record(AuditEvent::UserLogin, auditable: $user);

        $response = response()->json(['user' => $this->formatUserResponse($user)]);

        if ($request->boolean('remember_device')) {
            $token = $devices->issue($user, $request->userAgent(), $request->ip());
            AuditLog::record(AuditEvent::UserMfaDeviceRemembered, auditable: $user);

            $response->cookie(cookie(
                TwoFactorDeviceService::COOKIE_NAME,
                $token,
                minutes: 60 * 24 * TwoFactorDeviceService::TTL_DAYS,
                path: '/',
                domain: null,
                secure: config('session.secure'),
                httpOnly: true,
                raw: false,
                sameSite: 'lax',
            ));
        }

        return $response;
    }

    private function consumeRecoveryCode(User $user, string $input): bool
    {
        return DB::transaction(function () use ($user, $input) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->first();

            $codes = json_decode(decrypt($locked->two_factor_recovery_codes), true) ?: [];
            $filtered = array_values(array_filter($codes, fn ($c) => ! hash_equals($c, $input)));

            if (count($filtered) === count($codes)) {
                return false;
            }

            $locked->forceFill(['two_factor_recovery_codes' => encrypt(json_encode($filtered))])->save();

            return true;
        });
    }
}
