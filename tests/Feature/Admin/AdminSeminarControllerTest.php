<?php

use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\SeminarType;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSpeakerData;
use App\Models\Workshop;

describe('GET /api/admin/seminars', function () {
    it('returns paginated list of seminars for admin', function () {
        actingAsAdmin();

        Seminar::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/seminars');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'scheduled_at', 'active'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/admin/seminars');

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $response = $this->getJson('/api/admin/seminars');

        $response->assertForbidden();
    });

    it('allows teacher to view seminars', function () {
        $teacher = actingAsTeacher();

        // Create seminar by this teacher
        Seminar::factory()->create(['created_by' => $teacher->id]);

        $response = $this->getJson('/api/admin/seminars');

        $response->assertSuccessful();
    });

    it('teacher only sees their own seminars', function () {
        $teacher = actingAsTeacher();

        // Create seminar by this teacher
        $ownSeminar = Seminar::factory()->create(['created_by' => $teacher->id]);

        // Create seminar by another user
        $otherUser = User::factory()->create();
        $otherSeminar = Seminar::factory()->create(['created_by' => $otherUser->id]);

        $response = $this->getJson('/api/admin/seminars');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect(collect($data)->pluck('id'))->toContain($ownSeminar->id);
        expect(collect($data)->pluck('id'))->not->toContain($otherSeminar->id);
    });

    it('filters seminars by search term', function () {
        actingAsAdmin();

        Seminar::factory()->create(['name' => 'Machine Learning Workshop']);
        Seminar::factory()->create(['name' => 'Web Development']);

        $response = $this->getJson('/api/admin/seminars?search=Machine');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Machine Learning Workshop');
    });

    it('filters seminars by active status', function () {
        actingAsAdmin();

        Seminar::factory()->create(['active' => true]);
        Seminar::factory()->create(['active' => false]);

        $response = $this->getJson('/api/admin/seminars?active=1');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['active'])->toBeTrue();
    });

    it('filters upcoming seminars', function () {
        actingAsAdmin();

        Seminar::factory()->upcoming()->create();
        Seminar::factory()->past()->create();

        $response = $this->getJson('/api/admin/seminars?upcoming=1');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
    });
});

describe('GET /api/admin/seminars/{id}', function () {
    it('returns seminar by id for admin', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create(['name' => 'Test Seminar']);

        $response = $this->getJson("/api/admin/seminars/{$seminar->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $seminar->id)
            ->assertJsonPath('data.name', 'Test Seminar');
    });

    it('returns 404 for non-existent seminar', function () {
        actingAsAdmin();

        $response = $this->getJson('/api/admin/seminars/99999');

        $response->assertNotFound();
    });

    it('teacher can view their own seminar', function () {
        $teacher = actingAsTeacher();

        $seminar = Seminar::factory()->create(['created_by' => $teacher->id]);

        $response = $this->getJson("/api/admin/seminars/{$seminar->id}");

        $response->assertSuccessful();
    });

    it('teacher cannot view other seminars', function () {
        $teacher = actingAsTeacher();

        $otherUser = User::factory()->create();
        $seminar = Seminar::factory()->create(['created_by' => $otherUser->id]);

        $response = $this->getJson("/api/admin/seminars/{$seminar->id}");

        $response->assertForbidden();
    });
});

describe('POST /api/admin/seminars', function () {
    it('creates a new seminar for admin', function () {
        actingAsAdmin();

        $location = SeminarLocation::factory()->create();
        $speaker = User::factory()->create();
        UserSpeakerData::create([
            'user_id' => $speaker->id,
            'slug' => 'test-speaker',
        ]);

        $response = $this->postJson('/api/admin/seminars', [
            'name' => 'Novo Seminário',
            'description' => 'Descrição do seminário',
            'scheduled_at' => now()->addDays(7)->toDateTimeString(),
            'active' => true,
            'seminar_location_id' => $location->id,
            'subject_names' => ['Machine Learning', 'AI'],
            'speaker_ids' => [$speaker->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Seminário criado com sucesso')
            ->assertJsonPath('data.name', 'Novo Seminário');

        expect(Seminar::where('name', 'Novo Seminário')->exists())->toBeTrue();

        // Verify subjects were created
        expect(Subject::where('name', 'Machine Learning')->exists())->toBeTrue();
        expect(Subject::where('name', 'AI')->exists())->toBeTrue();
    });

    it('creates seminar with auto-generated slug', function () {
        actingAsAdmin();

        $location = SeminarLocation::factory()->create();
        $speaker = User::factory()->create();
        UserSpeakerData::create(['user_id' => $speaker->id, 'slug' => 'speaker']);

        $response = $this->postJson('/api/admin/seminars', [
            'name' => 'My Amazing Seminar',
            'scheduled_at' => now()->addDays(7)->toDateTimeString(),
            'active' => true,
            'seminar_location_id' => $location->id,
            'subject_names' => ['Test'],
            'speaker_ids' => [$speaker->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'my-amazing-seminar');
    });

    it('creates seminar with optional fields', function () {
        actingAsAdmin();

        $location = SeminarLocation::factory()->create();
        $type = SeminarType::factory()->create();
        $workshop = Workshop::factory()->create();
        $speaker = User::factory()->create();
        UserSpeakerData::create(['user_id' => $speaker->id, 'slug' => 'speaker']);

        $response = $this->postJson('/api/admin/seminars', [
            'name' => 'Full Seminar',
            'scheduled_at' => now()->addDays(7)->toDateTimeString(),
            'active' => true,
            'seminar_location_id' => $location->id,
            'seminar_type_id' => $type->id,
            'workshop_id' => $workshop->id,
            'room_link' => 'https://meet.google.com/abc-defg-hij',
            'subject_names' => ['Test'],
            'speaker_ids' => [$speaker->id],
        ]);

        $response->assertStatus(201);
    });

    it('returns validation error for missing name', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/seminars', [
            'scheduled_at' => now()->addDays(7)->toDateTimeString(),
            'active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('returns validation error for missing scheduled_at', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/seminars', [
            'name' => 'Test Seminar',
            'active' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    });

    it('returns validation error for missing subjects', function () {
        actingAsAdmin();

        $location = SeminarLocation::factory()->create();
        $speaker = User::factory()->create();

        $response = $this->postJson('/api/admin/seminars', [
            'name' => 'Test Seminar',
            'scheduled_at' => now()->addDays(7)->toDateTimeString(),
            'active' => true,
            'seminar_location_id' => $location->id,
            'speaker_ids' => [$speaker->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject_names']);
    });

    it('returns validation error for missing speakers', function () {
        actingAsAdmin();

        $location = SeminarLocation::factory()->create();

        $response = $this->postJson('/api/admin/seminars', [
            'name' => 'Test Seminar',
            'scheduled_at' => now()->addDays(7)->toDateTimeString(),
            'active' => true,
            'seminar_location_id' => $location->id,
            'subject_names' => ['Test'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['speaker_ids']);
    });

    it('teacher can create seminar', function () {
        $teacher = actingAsTeacher();

        $location = SeminarLocation::factory()->create();
        $speaker = User::factory()->create();
        UserSpeakerData::create(['user_id' => $speaker->id, 'slug' => 'speaker']);

        $response = $this->postJson('/api/admin/seminars', [
            'name' => 'Teacher Seminar',
            'scheduled_at' => now()->addDays(7)->toDateTimeString(),
            'active' => true,
            'seminar_location_id' => $location->id,
            'subject_names' => ['Test'],
            'speaker_ids' => [$speaker->id],
        ]);

        $response->assertStatus(201);

        $seminar = Seminar::where('name', 'Teacher Seminar')->first();
        expect($seminar->created_by)->toBe($teacher->id);
    });
});

describe('PUT /api/admin/seminars/{id}', function () {
    it('updates seminar for admin', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create();

        $response = $this->putJson("/api/admin/seminars/{$seminar->id}", [
            'name' => 'Seminário Atualizado',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Seminário atualizado com sucesso')
            ->assertJsonPath('data.name', 'Seminário Atualizado');
    });

    it('updates slug when name changes', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create(['name' => 'Original Name', 'slug' => 'original-name']);

        $response = $this->putJson("/api/admin/seminars/{$seminar->id}", [
            'name' => 'New Name',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.slug', 'new-name');
    });

    it('updates subjects', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create();
        $oldSubject = Subject::factory()->create(['name' => 'Old Subject']);
        $seminar->subjects()->attach($oldSubject);

        $response = $this->putJson("/api/admin/seminars/{$seminar->id}", [
            'subject_names' => ['New Subject 1', 'New Subject 2'],
        ]);

        $response->assertSuccessful();

        $seminar->refresh();
        $subjectNames = $seminar->subjects->pluck('name')->toArray();
        expect($subjectNames)->toContain('New Subject 1');
        expect($subjectNames)->toContain('New Subject 2');
        expect($subjectNames)->not->toContain('Old Subject');
    });

    it('teacher can update their own seminar', function () {
        $teacher = actingAsTeacher();

        $seminar = Seminar::factory()->create(['created_by' => $teacher->id]);

        $response = $this->putJson("/api/admin/seminars/{$seminar->id}", [
            'name' => 'Updated by Teacher',
        ]);

        $response->assertSuccessful();
    });

    it('teacher cannot update other seminars', function () {
        $teacher = actingAsTeacher();

        $otherUser = User::factory()->create();
        $seminar = Seminar::factory()->create(['created_by' => $otherUser->id]);

        $response = $this->putJson("/api/admin/seminars/{$seminar->id}", [
            'name' => 'Trying to Update',
        ]);

        $response->assertForbidden();
    });

    it('updates nullable description field', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create(['description' => 'Original description']);

        $response = $this->putJson("/api/admin/seminars/{$seminar->id}", [
            'description' => null,
        ]);

        $response->assertSuccessful();
        expect($seminar->fresh()->description)->toBeNull();
    });

    it('updates room_link to null', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create(['room_link' => 'https://example.com']);

        $response = $this->putJson("/api/admin/seminars/{$seminar->id}", [
            'room_link' => null,
        ]);

        $response->assertSuccessful();
        expect($seminar->fresh()->room_link)->toBeNull();
    });

    it('updates seminar_type_id to null', function () {
        actingAsAdmin();

        $type = SeminarType::factory()->create();
        $seminar = Seminar::factory()->create(['seminar_type_id' => $type->id]);

        $response = $this->putJson("/api/admin/seminars/{$seminar->id}", [
            'seminar_type_id' => null,
        ]);

        $response->assertSuccessful();
        expect($seminar->fresh()->seminar_type_id)->toBeNull();
    });

    it('updates workshop_id to null', function () {
        actingAsAdmin();

        $workshop = Workshop::factory()->create();
        $seminar = Seminar::factory()->create(['workshop_id' => $workshop->id]);

        $response = $this->putJson("/api/admin/seminars/{$seminar->id}", [
            'workshop_id' => null,
        ]);

        $response->assertSuccessful();
        expect($seminar->fresh()->workshop_id)->toBeNull();
    });

    it('updates scheduled_at', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create();
        $newDate = now()->addDays(30)->toDateTimeString();

        $response = $this->putJson("/api/admin/seminars/{$seminar->id}", [
            'scheduled_at' => $newDate,
        ]);

        $response->assertSuccessful();
    });

    it('updates active status', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create(['active' => true]);

        $response = $this->putJson("/api/admin/seminars/{$seminar->id}", [
            'active' => false,
        ]);

        $response->assertSuccessful();
        expect($seminar->fresh()->active)->toBeFalse();
    });

    it('updates seminar_location_id', function () {
        actingAsAdmin();

        $newLocation = SeminarLocation::factory()->create();
        $seminar = Seminar::factory()->create();

        $response = $this->putJson("/api/admin/seminars/{$seminar->id}", [
            'seminar_location_id' => $newLocation->id,
        ]);

        $response->assertSuccessful();
        expect($seminar->fresh()->seminar_location_id)->toBe($newLocation->id);
    });

    it('updates speaker_ids', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create();
        $newSpeaker = User::factory()->create();
        UserSpeakerData::create(['user_id' => $newSpeaker->id, 'slug' => 'new-speaker']);

        $response = $this->putJson("/api/admin/seminars/{$seminar->id}", [
            'speaker_ids' => [$newSpeaker->id],
        ]);

        $response->assertSuccessful();
        expect($seminar->fresh()->speakers->pluck('id')->toArray())->toBe([$newSpeaker->id]);
    });
});

describe('DELETE /api/admin/seminars/{id}', function () {
    it('soft deletes seminar for admin', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create();

        $response = $this->deleteJson("/api/admin/seminars/{$seminar->id}");

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Seminário excluído com sucesso');

        expect(Seminar::find($seminar->id))->toBeNull();
        expect(Seminar::withTrashed()->find($seminar->id))->not->toBeNull();
    });

    it('teacher can delete their own seminar', function () {
        $teacher = actingAsTeacher();

        $seminar = Seminar::factory()->create(['created_by' => $teacher->id]);

        $response = $this->deleteJson("/api/admin/seminars/{$seminar->id}");

        $response->assertSuccessful();
    });

    it('teacher cannot delete other seminars', function () {
        $teacher = actingAsTeacher();

        $otherUser = User::factory()->create();
        $seminar = Seminar::factory()->create(['created_by' => $otherUser->id]);

        $response = $this->deleteJson("/api/admin/seminars/{$seminar->id}");

        $response->assertForbidden();
    });
});

describe('GET /admin/seminar-types', function () {
    it('returns list of seminar types', function () {
        actingAsAdmin();

        SeminarType::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/seminar-types');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ]);
    });
});

describe('GET /admin/workshops-dropdown', function () {
    it('returns list of workshops', function () {
        actingAsAdmin();

        Workshop::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/workshops-dropdown');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ]);
    });
});

describe('GET /admin/locations-dropdown', function () {
    it('returns list of locations', function () {
        actingAsAdmin();

        SeminarLocation::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/locations-dropdown');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'max_vacancies'],
                ],
            ]);
    });
});
