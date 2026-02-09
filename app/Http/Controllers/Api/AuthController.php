<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\FormatsUserResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRegistrationRequest;
use App\Mail\WelcomeUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    use FormatsUserResponse;

    /**
     * Login with email and password
     */
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
                $user->password = $validated['password'];
                $user->save();
            }
        }

        if (! $passwordMatches) {
            throw ApiException::mismatchedCredentials();
        }

        Auth::login($user, $request->boolean('remember', false));

        return response()->json([
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Logout current user
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout realizado com sucesso',
        ]);
    }

    /**
     * Get current authenticated user
     */
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

    /**
     * Register a new user
     */
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

        Mail::to($user)->send(new WelcomeUser($user));

        Auth::login($user);

        return response()->json([
            'user' => $this->formatUserResponse($user),
        ], 201);
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Always return success to prevent email enumeration
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return response()->json([
            'message' => 'Se o e-mail existir, você receberá um link de recuperação.',
        ]);
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ApiException::invalidToken();
        }

        return response()->json([
            'message' => 'Senha redefinida com sucesso.',
        ]);
    }
}
