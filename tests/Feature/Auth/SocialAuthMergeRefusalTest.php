<?php

use App\Enums\AuditEvent;
use App\Exceptions\OAuthMergeRefusedException;
use App\Models\AuditLog;
use App\Models\SocialIdentity;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

describe('OAuth silent merge refusal (F-13)', function () {
    it('creates a new user when the email is brand new', function () {
        $fakeUser = new SocialiteUser;
        $fakeUser->id = 'google-fresh-1';
        $fakeUser->name = 'Fresh User';
        $fakeUser->email = 'fresh@example.com';

        Socialite::fake('google', $fakeUser);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect();
        expect($response->headers->get('Location'))->toContain('/auth/callback?code=');

        $this->assertDatabaseHas('users', [
            'email' => 'fresh@example.com',
        ]);

        $this->assertDatabaseHas('social_identities', [
            'provider' => 'google',
            'provider_id' => 'google-fresh-1',
        ]);
    });

    it('signs the user in when a SocialIdentity for this provider already exists', function () {
        $existing = User::factory()->create([
            'email' => 'linked@example.com',
        ]);

        SocialIdentity::create([
            'user_id' => $existing->id,
            'provider' => 'google',
            'provider_id' => 'google-known',
        ]);

        $fakeUser = new SocialiteUser;
        $fakeUser->id = 'google-known';
        $fakeUser->name = 'Linked';
        $fakeUser->email = 'linked@example.com';

        Socialite::fake('google', $fakeUser);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect();
        expect($response->headers->get('Location'))->toContain('/auth/callback?code=');

        // No duplicate user, identity preserved.
        expect(User::where('email', 'linked@example.com')->count())->toBe(1)
            ->and(SocialIdentity::where('user_id', $existing->id)->count())->toBe(1);
    });

    it('refuses the callback when the email matches a local user with no SocialIdentity for this provider', function () {
        $existing = User::factory()->create([
            'email' => 'victim@example.com',
            'name' => 'Victim',
        ]);

        $attacker = new SocialiteUser;
        $attacker->id = 'attacker-google-id';
        $attacker->name = 'Attacker';
        $attacker->email = 'victim@example.com';

        Socialite::fake('google', $attacker);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/auth/callback?error=oauth_merge_refused');

        // Critical: no SocialIdentity row was created for the attacker.
        expect(SocialIdentity::where('user_id', $existing->id)->count())->toBe(0)
            ->and(SocialIdentity::where('provider', 'google')->where('provider_id', 'attacker-google-id')->exists())->toBeFalse();

        // Audit log captures the refusal with the user-facing message.
        $audit = AuditLog::where('event_name', AuditEvent::OAuthMergeRefused->value)->latest()->first();
        expect($audit)->not->toBeNull()
            ->and($audit->auditable_id)->toBe($existing->id)
            ->and($audit->event_data['provider'] ?? null)->toBe('google')
            ->and($audit->event_data['user_message'] ?? null)->toBe(OAuthMergeRefusedException::USER_MESSAGE);
    });

    it('refuses the callback for a different provider when only another provider is linked', function () {
        $existing = User::factory()->create([
            'email' => 'partial@example.com',
        ]);

        SocialIdentity::create([
            'user_id' => $existing->id,
            'provider' => 'google',
            'provider_id' => 'google-real',
        ]);

        $attackerGithub = new SocialiteUser;
        $attackerGithub->id = 'github-attacker-id';
        $attackerGithub->name = 'Github Attacker';
        $attackerGithub->email = 'partial@example.com';

        Socialite::fake('github', $attackerGithub);

        $this->get('/auth/github/callback')
            ->assertRedirect('/auth/callback?error=oauth_merge_refused');

        expect(SocialIdentity::where('user_id', $existing->id)->count())->toBe(1)
            ->and(SocialIdentity::where('provider', 'github')->exists())->toBeFalse();
    });
});
