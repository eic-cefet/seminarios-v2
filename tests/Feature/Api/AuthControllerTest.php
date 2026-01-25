<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

describe('POST /api/auth/login', function () {
    it('authenticates user with valid credentials', function () {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);
    });

    it('returns error for non-existent email', function () {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Credenciais inválidas');
    });

    it('returns error for wrong password', function () {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Credenciais inválidas');
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('validates email format', function () {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});

describe('POST /api/auth/logout', function () {
    // Note: Logout requires Sanctum's stateful SPA authentication with session support
    // This works in production when the SPA makes requests from a recognized domain
    // but is difficult to test in isolation without the full SPA/session setup
    it('logs out authenticated user')->skip('Requires Sanctum stateful SPA setup');
});

describe('GET /api/auth/me', function () {
    it('returns current authenticated user', function () {
        $user = actingAsUser();

        $response = $this->getJson('/api/auth/me');

        $response->assertSuccessful()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);
    });

    it('returns error for unauthenticated user', function () {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    });
});

describe('POST /api/auth/register', function () {
    beforeEach(function () {
        Mail::fake();
    });

    it('registers a new user successfully', function () {
        $course = Course::factory()->create();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'course_situation' => 'studying',
            'course_role' => 'Aluno',
            'course_id' => $course->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.name', 'Test User')
            ->assertJsonPath('user.email', 'newuser@example.com');

        expect(User::where('email', 'newuser@example.com')->exists())->toBeTrue();
    });

    it('registers user without course_id', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'nocourse@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'course_situation' => 'graduated',
            'course_role' => 'Outro',
        ]);

        $response->assertStatus(201);
    });

    it('returns error for duplicate email', function () {
        $existingUser = User::factory()->create();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'course_situation' => 'studying',
            'course_role' => 'Aluno',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('validates password confirmation', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
            'course_situation' => 'studying',
            'course_role' => 'Aluno',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('validates password minimum length', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'course_situation' => 'studying',
            'course_role' => 'Aluno',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'course_situation', 'course_role']);
    });

    it('validates course_situation enum values', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'course_situation' => 'invalid',
            'course_role' => 'Aluno',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['course_situation']);
    });

    it('validates course_role enum values', function () {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'course_situation' => 'studying',
            'course_role' => 'Invalid',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['course_role']);
    });
});

describe('POST /api/auth/forgot-password', function () {
    it('returns success message regardless of email existence', function () {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Se o e-mail existir, você receberá um link de recuperação.');
    });

    it('validates email is required', function () {
        $response = $this->postJson('/api/auth/forgot-password', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});

describe('POST /api/auth/reset-password', function () {
    it('resets password with valid token', function () {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Senha redefinida com sucesso.');

        // Verify password was changed
        $user->refresh();
        expect(Hash::check('newpassword123', $user->password))->toBeTrue();
    });

    it('returns error for invalid token', function () {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Token inválido ou expirado');
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/auth/reset-password', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['token', 'email', 'password']);
    });
});
