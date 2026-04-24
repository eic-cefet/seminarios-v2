<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmTwoFactorRequest;
use App\Models\AuditLog;
use App\Services\TwoFactorDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function enable(Request $request, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        $user = $request->user();

        $enable($user);
        $user->refresh();

        AuditLog::record(AuditEvent::UserMfaEnabled, auditable: $user);

        return response()->json([
            'secret' => decrypt($user->two_factor_secret),
            'qr_code_svg' => $user->twoFactorQrCodeSvg(),
            'recovery_codes' => json_decode(decrypt($user->two_factor_recovery_codes), true),
        ]);
    }

    public function confirm(ConfirmTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->two_factor_secret) {
            throw ApiException::validation(['code' => '2FA not initialised']);
        }

        $valid = app(Google2FA::class)->verifyKey(decrypt($user->two_factor_secret), $request->string('code'));

        if (! $valid) {
            throw ApiException::validation(['code' => 'Código inválido']);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        AuditLog::record(AuditEvent::UserMfaConfirmed, auditable: $user);

        return response()->json(['message' => '2FA confirmado']);
    }

    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $action): JsonResponse
    {
        $user = $request->user();
        $action($user);
        $user->refresh();

        AuditLog::record(AuditEvent::UserMfaRecoveryCodesRegenerated, auditable: $user);

        return response()->json([
            'recovery_codes' => json_decode(decrypt($user->two_factor_recovery_codes), true),
        ]);
    }

    public function disable(Request $request, DisableTwoFactorAuthentication $disable): JsonResponse
    {
        $user = $request->user();

        $disable($user);
        app(TwoFactorDeviceService::class)->revokeAll($user);

        AuditLog::record(AuditEvent::UserMfaDisabled, auditable: $user);

        return response()->json(['message' => '2FA desativado']);
    }
}
