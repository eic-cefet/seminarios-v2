<?php

use App\Models\User;

describe('GET /api/external/v1/users', function () {
    it('returns paginated list of users with speaker data', function () {
        actingAsAdmin();
        User::factory()->speaker()->count(3)->create();

        $response = $this->getJson('/api/external/v1/users');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'username', 'speaker_data']],
            ]);
    });

    it('searches by name', function () {
        actingAsAdmin();
        User::factory()->create(['name' => 'João Silva']);
        User::factory()->create(['name' => 'Maria Santos']);

        $response = $this->getJson('/api/external/v1/users?search=João');

        $names = collect($response->json('data'))->pluck('name');
        expect($names)->toContain('João Silva');
        expect($names)->not->toContain('Maria Santos');
    });

    it('searches by email', function () {
        actingAsAdmin();
        User::factory()->create(['email' => 'joao@test.com']);
        User::factory()->create(['email' => 'maria@test.com']);

        $response = $this->getJson('/api/external/v1/users?search=joao');

        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.email'))->toBe('joao@test.com');
    });

    it('filters by exact email', function () {
        actingAsAdmin();
        User::factory()->create(['email' => 'exact@test.com']);
        User::factory()->create(['email' => 'other@test.com']);

        $response = $this->getJson('/api/external/v1/users?email=exact@test.com');

        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.email'))->toBe('exact@test.com');
    });

    it('returns 401 for unauthenticated user', function () {
        $this->getJson('/api/external/v1/users')->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();
        $this->getJson('/api/external/v1/users')->assertForbidden();
    });
});

describe('GET /api/external/v1/users/{id}', function () {
    it('returns user with speaker data', function () {
        actingAsAdmin();
        $user = User::factory()->speaker()->create();

        $response = $this->getJson("/api/external/v1/users/{$user->id}");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'username', 'speaker_data' => ['id', 'slug', 'institution', 'description']],
            ]);
    });

    it('returns null speaker_data when user has none', function () {
        actingAsAdmin();
        $user = User::factory()->create();

        $response = $this->getJson("/api/external/v1/users/{$user->id}");

        $response->assertSuccessful();
        expect($response->json('data.speaker_data'))->toBeNull();
    });

    it('returns 404 for non-existent user', function () {
        actingAsAdmin();
        $this->getJson('/api/external/v1/users/999')->assertNotFound();
    });
});

describe('POST /api/external/v1/users', function () {
    it('creates a user', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/users', [
            'name' => 'New User',
            'email' => 'new@test.com',
            'username' => 'newuser',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New User')
            ->assertJsonPath('data.email', 'new@test.com')
            ->assertJsonPath('data.username', 'newuser')
            ->assertJsonPath('data.speaker_data', null);
    });

    it('does not accept password field', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/users', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'secret123',
        ]);

        $response->assertCreated();
        $user = User::where('email', 'test@test.com')->first();
        expect($user->password)->toBeNull();
    });

    it('rejects duplicate email', function () {
        actingAsAdmin();
        User::factory()->create(['email' => 'taken@test.com']);

        $this->postJson('/api/external/v1/users', [
            'name' => 'Dup',
            'email' => 'taken@test.com',
        ])->assertStatus(422);
    });

    it('validates required fields', function () {
        actingAsAdmin();
        $this->postJson('/api/external/v1/users', [])->assertStatus(422);
    });
});

describe('PUT /api/external/v1/users/{id}', function () {
    it('updates user name', function () {
        actingAsAdmin();
        $user = User::factory()->create();

        $response = $this->putJson("/api/external/v1/users/{$user->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertSuccessful();
        expect($response->json('data.name'))->toBe('Updated Name');
    });

    it('updates username', function () {
        actingAsAdmin();
        $user = User::factory()->create(['username' => 'old']);

        $response = $this->putJson("/api/external/v1/users/{$user->id}", [
            'username' => 'new-username',
        ]);

        expect($response->json('data.username'))->toBe('new-username');
    });

    it('rejects duplicate email on update', function () {
        actingAsAdmin();
        User::factory()->create(['email' => 'taken@test.com']);
        $user = User::factory()->create();

        $this->putJson("/api/external/v1/users/{$user->id}", [
            'email' => 'taken@test.com',
        ])->assertStatus(422);
    });
});
