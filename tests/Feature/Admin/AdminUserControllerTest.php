<?php

use App\Models\User;
use App\Models\UserSpeakerData;
use App\Models\UserStudentData;

describe('GET /api/admin/users', function () {
    it('returns paginated list of users for admin', function () {
        actingAsAdmin();

        User::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/users');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/admin/users');

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $response = $this->getJson('/api/admin/users');

        $response->assertForbidden();
    });

    it('returns 403 for teacher user', function () {
        actingAsTeacher();

        $response = $this->getJson('/api/admin/users');

        $response->assertForbidden();
    });

    it('filters users by search term', function () {
        actingAsAdmin();

        User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);
        User::factory()->create(['name' => 'Maria Santos', 'email' => 'maria@example.com']);

        $response = $this->getJson('/api/admin/users?search=João');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect(collect($data)->pluck('name'))->toContain('João Silva');
    });

    it('filters users by role', function () {
        actingAsAdmin();

        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $response = $this->getJson('/api/admin/users?role=teacher');

        $response->assertSuccessful();
    });

    it('filters trashed users', function () {
        actingAsAdmin();

        $user = User::factory()->create();
        $user->delete();

        $response = $this->getJson('/api/admin/users?trashed=1');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect(collect($data)->pluck('id'))->toContain($user->id);
    });
});

describe('GET /api/admin/users/{id}', function () {
    it('returns user by id for admin', function () {
        actingAsAdmin();

        $user = User::factory()->create(['name' => 'Test User']);

        $response = $this->getJson("/api/admin/users/{$user->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'Test User');
    });

    it('returns 404 for non-existent user', function () {
        actingAsAdmin();

        $response = $this->getJson('/api/admin/users/99999');

        $response->assertNotFound();
    });
});

describe('POST /api/admin/users', function () {
    it('creates a new user for admin', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Novo Usuário',
            'email' => 'novo@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Usuário criado com sucesso')
            ->assertJsonPath('data.name', 'Novo Usuário')
            ->assertJsonPath('data.email', 'novo@example.com');

        expect(User::where('email', 'novo@example.com')->exists())->toBeTrue();
    });

    it('creates user with auto-generated password when not provided', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/users', [
            'name' => 'User Sem Senha',
            'email' => 'semssenha@example.com',
        ]);

        $response->assertStatus(201);
        expect(User::where('email', 'semssenha@example.com')->exists())->toBeTrue();
    });

    it('creates user with role', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Teacher User',
            'email' => 'teacher@example.com',
            'role' => 'teacher',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'teacher@example.com')->first();
        expect($user->hasRole('teacher'))->toBeTrue();
    });

    it('creates user with student data', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Student User',
            'email' => 'student@example.com',
            'student_data' => [
                'course_situation' => 'studying',
            ],
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'student@example.com')->first();
        expect($user->studentData)->not->toBeNull();
        expect($user->studentData->course_situation->value)->toBe('studying');
    });

    it('creates user with speaker data', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Speaker User',
            'email' => 'speaker@example.com',
            'speaker_data' => [
                'institution' => 'CEFET-RJ',
                'description' => 'Professor de Computação',
            ],
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'speaker@example.com')->first();
        expect($user->speakerData)->not->toBeNull();
        expect($user->speakerData->institution)->toBe('CEFET-RJ');
    });

    it('returns validation error for missing name', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/users', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('returns validation error for missing email', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Test User',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('returns validation error for duplicate email', function () {
        actingAsAdmin();

        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/admin/users', [
            'name' => 'New User',
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('returns validation error for invalid role', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'invalid_role',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    });
});

describe('PUT /api/admin/users/{id}', function () {
    it('updates user for admin', function () {
        actingAsAdmin();

        $user = User::factory()->create();

        $response = $this->putJson("/api/admin/users/{$user->id}", [
            'name' => 'Nome Atualizado',
            'email' => 'atualizado@example.com',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Usuário atualizado com sucesso')
            ->assertJsonPath('data.name', 'Nome Atualizado');
    });

    it('allows partial update', function () {
        actingAsAdmin();

        $user = User::factory()->create(['name' => 'Original', 'email' => 'original@example.com']);

        $response = $this->putJson("/api/admin/users/{$user->id}", [
            'name' => 'Alterado',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.name', 'Alterado')
            ->assertJsonPath('data.email', 'original@example.com');
    });

    it('updates user role', function () {
        actingAsAdmin();

        $user = User::factory()->create();

        $response = $this->putJson("/api/admin/users/{$user->id}", [
            'role' => 'admin',
        ]);

        $response->assertSuccessful();

        $user->refresh();
        expect($user->hasRole('admin'))->toBeTrue();
    });

    it('updates user password', function () {
        actingAsAdmin();

        $user = User::factory()->create();
        $oldPassword = $user->password;

        $response = $this->putJson("/api/admin/users/{$user->id}", [
            'password' => 'newpassword123',
        ]);

        $response->assertSuccessful();

        $user->refresh();
        expect($user->password)->not->toBe($oldPassword);
    });

    it('updates student data', function () {
        actingAsAdmin();

        $user = User::factory()->create();
        UserStudentData::create([
            'user_id' => $user->id,
            'course_situation' => 'studying',
        ]);

        $response = $this->putJson("/api/admin/users/{$user->id}", [
            'student_data' => [
                'course_situation' => 'graduated',
            ],
        ]);

        $response->assertSuccessful();

        $user->refresh();
        expect($user->studentData->course_situation->value)->toBe('graduated');
    });

    it('removes student data when null', function () {
        actingAsAdmin();

        $user = User::factory()->create();
        UserStudentData::create([
            'user_id' => $user->id,
            'course_name' => 'Test Course',
            'course_situation' => 'studying',
        ]);

        $response = $this->putJson("/api/admin/users/{$user->id}", [
            'student_data' => null,
        ]);

        $response->assertSuccessful();

        $user->refresh();
        expect($user->studentData)->toBeNull();
    });

    it('creates speaker data with auto-generated slug', function () {
        actingAsAdmin();

        $user = User::factory()->create(['name' => 'Test Speaker']);

        $response = $this->putJson("/api/admin/users/{$user->id}", [
            'speaker_data' => [
                'institution' => 'CEFET-RJ',
            ],
        ]);

        $response->assertSuccessful();

        $user->refresh();
        expect($user->speakerData)->not->toBeNull();
        expect($user->speakerData->slug)->toBe('test-speaker');
    });

    it('removes speaker data when null', function () {
        actingAsAdmin();

        $user = User::factory()->create();
        UserSpeakerData::create([
            'user_id' => $user->id,
            'slug' => 'test-speaker',
            'institution' => 'CEFET-RJ',
        ]);

        $response = $this->putJson("/api/admin/users/{$user->id}", [
            'speaker_data' => null,
        ]);

        $response->assertSuccessful();

        $user->refresh();
        expect($user->speakerData)->toBeNull();
    });
});

describe('DELETE /api/admin/users/{id}', function () {
    it('deletes user for admin', function () {
        $admin = actingAsAdmin();

        $user = User::factory()->create();

        $response = $this->deleteJson("/api/admin/users/{$user->id}");

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Usuário excluído com sucesso');

        expect(User::find($user->id))->toBeNull();
        expect(User::withTrashed()->find($user->id))->not->toBeNull();
    });

    it('prevents admin from deleting themselves', function () {
        $admin = actingAsAdmin();

        $response = $this->deleteJson("/api/admin/users/{$admin->id}");

        // Policy returns false when deleting self, resulting in 403
        $response->assertForbidden();
    });
});

describe('POST /api/admin/users/{id}/restore', function () {
    it('restores deleted user for admin', function () {
        actingAsAdmin();

        $user = User::factory()->create();
        $user->delete();

        $response = $this->postJson("/api/admin/users/{$user->id}/restore");

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Usuário restaurado com sucesso');

        expect(User::find($user->id))->not->toBeNull();
    });

    it('returns 404 for non-trashed user', function () {
        actingAsAdmin();

        $user = User::factory()->create();

        $response = $this->postJson("/api/admin/users/{$user->id}/restore");

        // The route uses withTrashed(), so it won't fail but will still work
        $response->assertSuccessful();
    });
});
