<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

describe('GET /api/admin/api-tokens', function () {
    it('returns paginated list of own tokens', function () {
        $admin = actingAsAdmin();
        $admin->createToken('test-token');

        $response = $this->getJson('/api/admin/api-tokens');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'abilities', 'last_used_at', 'created_at'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('only returns tokens for the authenticated user', function () {
        $admin = actingAsAdmin();
        $admin->createToken('my-token');

        $otherAdmin = User::factory()->admin()->create();
        $otherAdmin->createToken('other-token');

        $response = $this->getJson('/api/admin/api-tokens');

        $response->assertSuccessful();
        $names = collect($response->json('data'))->pluck('name');
        expect($names)->toContain('my-token');
        expect($names)->not->toContain('other-token');
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $response = $this->getJson('/api/admin/api-tokens');

        $response->assertForbidden();
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/admin/api-tokens');

        $response->assertUnauthorized();
    });
});

describe('POST /api/admin/api-tokens', function () {
    it('creates a token for the authenticated user', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/api-tokens', [
            'name' => 'My API Token',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'abilities', 'token'],
            ]);

        expect($response->json('data.token'))->toStartWith('sk-');
        expect($response->json('data.token'))->not->toContain('|');
        expect(PersonalAccessToken::count())->toBe(1);
    });

    it('creates a token with expiry', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/api-tokens', [
            'name' => 'Expiring Token',
            'expires_in_days' => 30,
        ]);

        $response->assertCreated();
        $token = PersonalAccessToken::first();
        expect($token->expires_at)->not->toBeNull();
        expect($token->expires_at->isFuture())->toBeTrue();
    });

    it('creates a token without expiry when not provided', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/api-tokens', [
            'name' => 'No Expiry Token',
        ]);

        $response->assertCreated();
        $token = PersonalAccessToken::first();
        expect($token->expires_at)->toBeNull();
    });

    it('rejects invalid expiry values', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/api-tokens', [
            'name' => 'Bad Expiry',
            'expires_in_days' => 15,
        ]);

        $response->assertStatus(422);
    });

    it('returned token authenticates external API requests', function () {
        $admin = actingAsAdmin();

        $response = $this->postJson('/api/admin/api-tokens', [
            'name' => 'Integration Test Token',
        ]);

        $token = $response->json('data.token');

        $externalResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/external/v1/seminar-types');

        $externalResponse->assertSuccessful();
    });

    it('works for teacher users', function () {
        actingAsTeacher();

        $response = $this->postJson('/api/admin/api-tokens', [
            'name' => 'Teacher Token',
        ]);

        $response->assertCreated();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $response = $this->postJson('/api/admin/api-tokens', [
            'name' => 'test',
        ]);

        $response->assertForbidden();
    });

    it('validates required name field', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/api-tokens', []);

        $response->assertStatus(422);
    });
});

describe('PUT /api/admin/api-tokens/{id}', function () {
    it('updates token name', function () {
        $admin = actingAsAdmin();
        $token = $admin->createToken('original-name');

        $response = $this->putJson("/api/admin/api-tokens/{$token->accessToken->id}", [
            'name' => 'updated-name',
        ]);

        $response->assertSuccessful();
        expect($response->json('data.name'))->toBe('updated-name');
    });

    it('updates token abilities', function () {
        $admin = actingAsAdmin();
        $token = $admin->createToken('test', ['*']);

        $response = $this->putJson("/api/admin/api-tokens/{$token->accessToken->id}", [
            'abilities' => ['seminars:read', 'seminars:write'],
        ]);

        $response->assertSuccessful();
        expect($response->json('data.abilities'))->toBe(['seminars:read', 'seminars:write']);
    });

    it('sets full access when empty abilities array', function () {
        $admin = actingAsAdmin();
        $token = $admin->createToken('test', ['seminars:read']);

        $response = $this->putJson("/api/admin/api-tokens/{$token->accessToken->id}", [
            'abilities' => [],
        ]);

        $response->assertSuccessful();
        expect($response->json('data.abilities'))->toBe(['*']);
    });

    it('cannot update another users token', function () {
        $admin = actingAsAdmin();
        $otherAdmin = User::factory()->admin()->create();
        $token = $otherAdmin->createToken('other');

        $response = $this->putJson("/api/admin/api-tokens/{$token->accessToken->id}", [
            'name' => 'hijacked',
        ]);

        $response->assertNotFound();
    });

    it('rejects invalid abilities', function () {
        $admin = actingAsAdmin();
        $token = $admin->createToken('test');

        $response = $this->putJson("/api/admin/api-tokens/{$token->accessToken->id}", [
            'abilities' => ['invalid:ability'],
        ]);

        $response->assertStatus(422);
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $this->putJson('/api/admin/api-tokens/1', [
            'name' => 'test',
        ])->assertForbidden();
    });
});

describe('POST /api/admin/api-tokens/{id}/regenerate', function () {
    it('regenerates a token preserving name and abilities', function () {
        $admin = actingAsAdmin();
        $oldToken = $admin->createToken('my-token', ['seminars:read']);
        $oldId = $oldToken->accessToken->id;

        $response = $this->postJson("/api/admin/api-tokens/{$oldId}/regenerate");

        $response->assertSuccessful();
        expect($response->json('data.name'))->toBe('my-token');
        expect($response->json('data.abilities'))->toBe(['seminars:read']);
        expect($response->json('data.token'))->toStartWith('sk-');
        expect($response->json('data.id'))->not->toBe($oldId);

        // Old token should be deleted
        expect(PersonalAccessToken::find($oldId))->toBeNull();
    });

    it('cannot regenerate another users token', function () {
        $admin = actingAsAdmin();
        $other = User::factory()->admin()->create();
        $token = $other->createToken('other');

        $this->postJson("/api/admin/api-tokens/{$token->accessToken->id}/regenerate")
            ->assertNotFound();
    });

    it('returns 404 for non-existent token', function () {
        actingAsAdmin();

        $this->postJson('/api/admin/api-tokens/999/regenerate')
            ->assertNotFound();
    });
});

describe('DELETE /api/admin/api-tokens/{id}', function () {
    it('revokes own token', function () {
        $admin = actingAsAdmin();
        $token = $admin->createToken('to-revoke');

        $response = $this->deleteJson("/api/admin/api-tokens/{$token->accessToken->id}");

        $response->assertSuccessful();
        expect(PersonalAccessToken::count())->toBe(0);
    });

    it('cannot revoke another users token', function () {
        $admin = actingAsAdmin();

        $otherAdmin = User::factory()->admin()->create();
        $token = $otherAdmin->createToken('other-token');

        $response = $this->deleteJson("/api/admin/api-tokens/{$token->accessToken->id}");

        $response->assertNotFound();
        expect(PersonalAccessToken::count())->toBe(1);
    });

    it('returns 404 for non-existent token', function () {
        actingAsAdmin();

        $response = $this->deleteJson('/api/admin/api-tokens/999');

        $response->assertNotFound();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $response = $this->deleteJson('/api/admin/api-tokens/1');

        $response->assertForbidden();
    });
});

it('rejects token creation with an unknown ability', function () {
    actingAsAdmin();

    $response = $this->postJson('/api/admin/api-tokens', [
        'name' => 'Test Token',
        'abilities' => ['definitely-not-a-real-ability'],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['abilities.0']);
});
