<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to OAuth provider
     *
     * Note: Route constraint (->where('provider', 'google|github')) ensures only valid providers reach this method
     */
    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle OAuth provider callback
     *
     * Note: Route constraint (->where('provider', 'google|github')) ensures only valid providers reach this method
     */
    public function callback(string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->user();

            // Find or create user
            $user = User::where('email', $socialUser->getEmail())->first();

            if (! $user) {
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                    'email' => $socialUser->getEmail(),
                    'password' => bcrypt(Str::random(32)),
                    'email_verified_at' => now(),
                ]);

                $user->assignRole('user');
            }

            // Generate one-time code
            $code = Str::random(64);

            // Store in cache with 5-minute expiration
            Cache::put("auth_code:{$code}", $user->id, now()->addMinutes(5));

            // Redirect to frontend callback with code
            return redirect("/auth/callback?code={$code}");

        } catch (\Exception $e) {
            return redirect('/auth/callback?error=authentication_failed');
        }
    }

    /**
     * Exchange one-time code for session
     */
    public function exchange(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = $request->input('code');

        // Pull (get and delete) the user ID from cache
        $userId = Cache::pull("auth_code:{$code}");

        if (! $userId) {
            throw ApiException::invalidToken();
        }

        $user = User::find($userId);

        if (! $user) {
            throw ApiException::notFound('Usuário');
        }

        $user->load('studentData');

        // Log the user in
        Auth::login($user, remember: true);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'student_data' => $user->studentData ? [
                    'course_situation' => $user->studentData->course_situation,
                    'course_role' => $user->studentData->course_role,
                    'course_name' => $user->studentData->course->name,
                ] : null,
            ],
        ]);
    }
}
