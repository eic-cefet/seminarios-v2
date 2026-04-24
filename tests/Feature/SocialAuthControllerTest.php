<?php

use App\Enums\ConsentType;
use App\Enums\CourseRole;
use App\Enums\CourseSituation;
use App\Models\Course;
use App\Models\User;
use App\Models\UserConsent;
use App\Models\UserStudentData;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Spatie\Permission\Models\Role;

describe('GET /auth/{provider} (redirect)', function () {
    it('redirects to google oauth', function () {
        Socialite::fake('google');

        $response = $this->get('/auth/google');

        $response->assertRedirect();
    });

    it('redirects to github oauth', function () {
        Socialite::fake('github');

        $response = $this->get('/auth/github');

        $response->assertRedirect();
    });
});

describe('GET /auth/{provider}/callback', function () {
    beforeEach(function () {
        // Create the 'user' role that the controller assigns to new users
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    });

    it('creates new user from google oauth and redirects with code', function () {

        $fakeUser = new SocialiteUser;
        $fakeUser->id = 'google-123';
        $fakeUser->name = 'New User';
        $fakeUser->email = 'newuser@example.com';

        Socialite::fake('google', $fakeUser);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect();
        expect($response->headers->get('Location'))->toContain('/auth/callback?code=');

        // User should be created
        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);

        // User should have user role
        $user = User::where('email', 'newuser@example.com')->first();
        expect($user->hasRole('user'))->toBeTrue();
    });

    it('records terms and privacy consent on first-time oauth signup', function () {
        $fakeUser = new SocialiteUser;
        $fakeUser->id = 'google-consent-test';
        $fakeUser->name = 'Consent User';
        $fakeUser->email = 'consentuser@example.com';

        Socialite::fake('google', $fakeUser);

        $this->get('/auth/google/callback')->assertRedirect();

        $user = User::where('email', 'consentuser@example.com')->firstOrFail();

        expect(UserConsent::where('user_id', $user->id)->where('type', ConsentType::TermsOfService)->where('granted', true)->where('source', 'oauth')->exists())->toBeTrue()
            ->and(UserConsent::where('user_id', $user->id)->where('type', ConsentType::PrivacyPolicy)->where('granted', true)->where('source', 'oauth')->exists())->toBeTrue();
    });

    it('does not record consent on existing user oauth login', function () {
        $existingUser = User::factory()->create([
            'email' => 'existing-oauth@example.com',
        ]);

        $fakeUser = new SocialiteUser;
        $fakeUser->id = 'google-existing-login';
        $fakeUser->name = 'Existing User';
        $fakeUser->email = 'existing-oauth@example.com';

        Socialite::fake('google', $fakeUser);

        $this->get('/auth/google/callback')->assertRedirect();

        expect(UserConsent::where('user_id', $existingUser->id)->where('source', 'oauth')->count())->toBe(0);
    });

    it('uses existing user on oauth callback', function () {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing User',
        ]);

        $fakeUser = new SocialiteUser;
        $fakeUser->id = 'google-456';
        $fakeUser->name = 'Different Name';
        $fakeUser->email = 'existing@example.com';

        Socialite::fake('google', $fakeUser);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect();
        expect($response->headers->get('Location'))->toContain('/auth/callback?code=');

        // Should not create new user
        expect(User::where('email', 'existing@example.com')->count())->toBe(1);

        // Original name should be preserved
        expect($existingUser->fresh()->name)->toBe('Existing User');
    });

    it('uses nickname when name is null', function () {
        $fakeUser = new SocialiteUser;
        $fakeUser->id = 'google-789';
        $fakeUser->name = null;
        $fakeUser->nickname = 'mynickname';
        $fakeUser->email = 'nickname@example.com';

        Socialite::fake('google', $fakeUser);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect();
        expect($response->headers->get('Location'))->toContain('/auth/callback?code=');

        $user = User::where('email', 'nickname@example.com')->first();
        expect($user->name)->toBe('mynickname');
    });

    it('uses User when both name and nickname are null', function () {
        $fakeUser = new SocialiteUser;
        $fakeUser->id = 'google-000';
        $fakeUser->name = null;
        $fakeUser->nickname = null;
        $fakeUser->email = 'noname@example.com';

        Socialite::fake('google', $fakeUser);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect();

        $user = User::where('email', 'noname@example.com')->first();
        expect($user->name)->toBe('User');
    });

    it('stores auth code in cache after successful callback', function () {
        $fakeUser = new SocialiteUser;
        $fakeUser->id = 'google-cache-test';
        $fakeUser->name = 'Cache Test User';
        $fakeUser->email = 'cachetest@example.com';

        Socialite::fake('google', $fakeUser);

        $response = $this->get('/auth/google/callback');

        // Extract code from redirect URL
        $location = $response->headers->get('Location');
        preg_match('/code=([^&]+)/', $location, $matches);
        $code = $matches[1] ?? null;

        expect($code)->not->toBeNull();

        // Code should be in cache
        $userId = Cache::get("auth_code:{$code}");
        expect($userId)->not->toBeNull();

        $user = User::where('email', 'cachetest@example.com')->first();
        expect($userId)->toBe($user->id);
    });

    it('works with github provider', function () {
        $fakeUser = new SocialiteUser;
        $fakeUser->id = 'github-123';
        $fakeUser->name = 'GitHub User';
        $fakeUser->email = 'githubuser@example.com';

        Socialite::fake('github', $fakeUser);

        $response = $this->get('/auth/github/callback');

        $response->assertRedirect();
        expect($response->headers->get('Location'))->toContain('/auth/callback?code=');

        $this->assertDatabaseHas('users', [
            'email' => 'githubuser@example.com',
        ]);
    });

    it('redirects with error on oauth failure', function () {
        // Don't provide a fake user - this will cause an error
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andThrow(new Exception('OAuth failed'));

        $socialiteManager = Mockery::mock(Factory::class);
        $socialiteManager->shouldReceive('driver')->with('google')->andReturn($provider);

        $this->app->instance(Factory::class, $socialiteManager);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/auth/callback?error=authentication_failed');
    });
});

describe('POST /api/auth/exchange', function () {
    it('exchanges valid code for session', function () {
        $user = User::factory()->create();

        // Store code in cache
        $code = 'test-auth-code-123';
        Cache::put("auth_code:{$code}", $user->id, now()->addMinutes(5));

        $response = $this->postJson('/api/auth/exchange', [
            'code' => $code,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);

        // Code should be removed from cache
        expect(Cache::get("auth_code:{$code}"))->toBeNull();
    });

    it('returns user with student data if available', function () {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        UserStudentData::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_situation' => CourseSituation::Studying,
            'course_role' => CourseRole::Aluno,
        ]);

        $code = 'test-auth-code-456';
        Cache::put("auth_code:{$code}", $user->id, now()->addMinutes(5));

        $response = $this->postJson('/api/auth/exchange', [
            'code' => $code,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('user.student_data.course_situation', 'studying')
            ->assertJsonPath('user.student_data.course_name', $course->name);
    });

    it('rejects invalid code', function () {
        $response = $this->postJson('/api/auth/exchange', [
            'code' => 'invalid-code',
        ]);

        $response->assertStatus(400);
    });

    it('rejects expired code', function () {
        // Store code with past expiration (simulated by not storing)
        $code = 'expired-code';

        $response = $this->postJson('/api/auth/exchange', [
            'code' => $code,
        ]);

        $response->assertStatus(400);
    });

    it('rejects code for non-existent user', function () {
        $code = 'code-for-deleted-user';
        Cache::put("auth_code:{$code}", 99999, now()->addMinutes(5));

        $response = $this->postJson('/api/auth/exchange', [
            'code' => $code,
        ]);

        $response->assertNotFound();
    });

    it('validates code is required', function () {
        $response = $this->postJson('/api/auth/exchange', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    });
});
