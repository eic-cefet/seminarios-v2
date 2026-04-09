<?php

use App\Models\SocialIdentity;
use App\Models\User;

describe('SocialIdentity Model', function () {
    it('belongs to a user', function () {
        $user = User::factory()->create();
        $socialIdentity = SocialIdentity::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '123456789',
            'token' => 'access_token',
            'refresh_token' => 'refresh_token',
        ]);

        expect($socialIdentity->user->id)->toBe($user->id);
    });

    it('hides sensitive token fields', function () {
        $user = User::factory()->create();
        $socialIdentity = SocialIdentity::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '123456789',
            'token' => 'secret_access_token',
            'refresh_token' => 'secret_refresh_token',
        ]);

        $array = $socialIdentity->toArray();
        expect($array)->not->toHaveKey('token');
        expect($array)->not->toHaveKey('refresh_token');
    });

    it('casts token_expires_at to datetime', function () {
        $user = User::factory()->create();
        $expiresAt = now()->addHour();

        $socialIdentity = SocialIdentity::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '123456789',
            'token' => 'access_token',
            'token_expires_at' => $expiresAt,
        ]);

        expect($socialIdentity->token_expires_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    it('has fillable attributes', function () {
        $user = User::factory()->create();

        $socialIdentity = SocialIdentity::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '987654321',
            'token' => 'github_token',
            'refresh_token' => 'github_refresh',
            'token_expires_at' => now()->addDay(),
        ]);

        expect($socialIdentity->provider)->toBe('github');
        expect($socialIdentity->provider_id)->toBe('987654321');
    });
});
