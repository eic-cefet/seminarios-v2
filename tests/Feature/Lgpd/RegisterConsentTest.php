<?php

use App\Enums\ConsentType;
use App\Models\User;
use App\Models\UserConsent;

use function Pest\Laravel\postJson;

it('rejects registration without terms and privacy acceptance', function () {
    postJson('/api/auth/register', [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'course_situation' => 'studying',
        'course_role' => 'Aluno',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['accepted_terms', 'accepted_privacy_policy']);

    expect(User::where('email', 'new@example.com')->exists())->toBeFalse();
});

it('rejects registration if terms explicitly refused', function () {
    postJson('/api/auth/register', [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'course_situation' => 'studying',
        'course_role' => 'Aluno',
        'accepted_terms' => false,
        'accepted_privacy_policy' => true,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['accepted_terms']);
});

it('persists consent records when accepted at signup', function () {
    postJson('/api/auth/register', [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'course_situation' => 'studying',
        'course_role' => 'Aluno',
        'accepted_terms' => true,
        'accepted_privacy_policy' => true,
    ])->assertSuccessful();

    $user = User::where('email', 'new@example.com')->firstOrFail();

    expect(UserConsent::where('user_id', $user->id)->where('type', ConsentType::TermsOfService)->where('granted', true)->exists())->toBeTrue()
        ->and(UserConsent::where('user_id', $user->id)->where('type', ConsentType::PrivacyPolicy)->where('granted', true)->exists())->toBeTrue();
});
