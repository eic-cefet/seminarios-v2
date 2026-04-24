<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditEvent;
use App\Enums\ConsentType;
use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\FormatsUserResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRegistrationRequest;
use App\Mail\AccountDeletionCancelled;
use App\Mail\WelcomeUser;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserConsent;
use App\Services\TwoFactorDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    use FormatsUserResponse;

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            throw ApiException::mismatchedCredentials();
        }

        // Handle legacy passwords with different bcrypt prefixes ($2a$ vs $2y$)
        try {
            $passwordMatches = Hash::check($validated['password'], $user->password);
        } catch (\RuntimeException) {
            // Legacy password format - try direct password_verify which accepts both
            $passwordMatches = password_verify($validated['password'], $user->password);

            // If matched, rehash with current algorithm for future logins
            if ($passwordMatches) {
                $user->password = Hash::make($validated['password']);
                $user->save();
            }
        }

        if (! $passwordMatches) {
            throw ApiException::mismatchedCredentials();
        }

        if ($user->two_factor_confirmed_at !== null) {
            $trustedToken = $request->cookie(TwoFactorDeviceService::COOKIE_NAME);

            if (app(TwoFactorDeviceService::class)->isTrusted($user, $trustedToken)) {
                Auth::login($user, $request->boolean('remember', false));
                AuditLog::record(AuditEvent::UserLogin, auditable: $user, eventData: ['trusted_device' => true]);

                if ($user->anonymization_requested_at !== null && $user->anonymized_at === null) {
                    $user->forceFill(['anonymization_requested_at' => null])->save();
                    Cache::forget('lgpd.deletion-pending:'.$user->id);
                    AuditLog::record(event: AuditEvent::AccountDeletionCancelled, auditable: $user);
                    Mail::to($user->email)->queue(new AccountDeletionCancelled($user));
                }

                return response()->json(['user' => $this->formatUserResponse($user)]);
            }

            $challengeToken = Str::random(80);
            cache()->put(
                "mfa:challenge:{$challengeToken}",
                ['user_id' => $user->id, 'remember' => $request->boolean('remember', false)],
                now()->addMinutes(5),
            );

            return response()->json(['two_factor' => ['challenge_token' => $challengeToken]]);
        }

        Auth::login($user, $request->boolean('remember', false));

        AuditLog::record(AuditEvent::UserLogin, auditable: $user);

        if ($user->anonymization_requested_at !== null && $user->anonymized_at === null) {
            $user->forceFill(['anonymization_requested_at' => null])->save();
            Cache::forget('lgpd.deletion-pending:'.$user->id);
            AuditLog::record(event: AuditEvent::AccountDeletionCancelled, auditable: $user);
            Mail::to($user->email)->queue(new AccountDeletionCancelled($user));
        }

        return response()->json([
            'user' => $this->formatUserResponse($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        AuditLog::record(AuditEvent::UserLogout, auditable: $user, userId: $user?->id);

        return response()->json([
            'message' => 'Logout realizado com sucesso',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            throw ApiException::unauthenticated();
        }

        return response()->json([
            'user' => $this->formatUserResponse($user),
        ]);
    }

    public function register(UserRegistrationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->studentData()->create([
            'course_situation' => $validated['course_situation'],
            'course_role' => $validated['course_role'],
            'course_id' => $validated['course_id'] ?? null,
        ]);

        foreach ([ConsentType::TermsOfService, ConsentType::PrivacyPolicy] as $type) {
            UserConsent::create([
                'user_id' => $user->id,
                'type' => $type,
                'granted' => true,
                'version' => config('lgpd.versions.'.$type->value) ?? '1.0',
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'source' => 'registration',
            ]);
        }

        Mail::to($user)->queue(new WelcomeUser($user));

        Auth::login($user);

        AuditLog::record(AuditEvent::UserRegister, auditable: $user);

        return response()->json([
            'user' => $this->formatUserResponse($user),
        ], 201);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        $user = User::where('email', $request->input('email'))->first();

        if ($user) {
            AuditLog::record(AuditEvent::UserForgotPassword, auditable: $user);
        }

        return response()->json([
            'message' => 'Se o e-mail existir, você receberá um link de recuperação.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $resetUser = null;

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use (&$resetUser) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $resetUser = $user;
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ApiException::invalidToken();
        }

        AuditLog::record(AuditEvent::UserPasswordReset, auditable: $resetUser);

        return response()->json([
            'message' => 'Senha redefinida com sucesso.',
        ]);
    }
}
