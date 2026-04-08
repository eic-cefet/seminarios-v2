<?php

use App\Jobs\ProcessSeminarRescheduleJob;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\SeminarType;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSpeakerData;
use App\Models\Workshop;
use Illuminate\Support\Facades\Bus;

describe('GET /api/external/v1/seminars', function () {
    it('returns paginated list of seminars for admin', function () {
        actingAsAdmin();

        Seminar::factory()->count(3)->create();

        $response = $this->getJson('/api/external/v1/seminars');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'scheduled_at', 'active', 'location', 'subjects', 'speakers'],
                ],
            ]);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/external/v1/seminars');

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $response = $this->getJson('/api/external/v1/seminars');

        $response->assertForbidden();
    });

    it('teacher only sees own seminars', function () {
        $teacher = actingAsTeacher();

        $ownSeminar = Seminar::factory()->create(['created_by' => $teacher->id]);
        Seminar::factory()->create();

        $response = $this->getJson('/api/external/v1/seminars');

        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($ownSeminar->id);
        expect($ids)->toHaveCount(1);
    });

    it('filters by active status', function () {
        actingAsAdmin();

        Seminar::factory()->create(['name' => 'Active Seminar', 'active' => true]);
        Seminar::factory()->create(['name' => 'Inactive Seminar', 'active' => false]);

        $response = $this->getJson('/api/external/v1/seminars?active=true');

        $response->assertSuccessful();
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Active Seminar');
    });

    it('filters by search term', function () {
        actingAsAdmin();

        Seminar::factory()->create(['name' => 'TCC Machine Learning']);
        Seminar::factory()->create(['name' => 'Workshop Web Dev']);

        $response = $this->getJson('/api/external/v1/seminars?search=TCC');

        $response->assertSuccessful();
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('TCC Machine Learning');
    });
});

describe('GET /api/external/v1/seminars/{id}', function () {
    it('returns seminar details with speakers and subjects', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()
            ->withSpeakers(2)
            ->withSubjects(3)
            ->create();

        $response = $this->getJson("/api/external/v1/seminars/{$seminar->id}");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'slug', 'description', 'scheduled_at',
                    'location' => ['id', 'name', 'max_vacancies'],
                    'subjects',
                    'speakers' => [
                        '*' => ['id', 'name', 'email'],
                    ],
                ],
            ]);
        expect($response->json('data.speakers'))->toHaveCount(2);
        expect($response->json('data.subjects'))->toHaveCount(3);
    });

    it('returns 403 when teacher views another users seminar', function () {
        $teacher = actingAsTeacher();

        $seminar = Seminar::factory()->create();

        $response = $this->getJson("/api/external/v1/seminars/{$seminar->id}");

        $response->assertForbidden();
    });
});

describe('POST /api/external/v1/seminars', function () {
    it('creates a seminar with auto-created subjects and speakers', function () {
        actingAsAdmin();
        $type = SeminarType::factory()->create(['name' => 'TCC']);

        $response = $this->postJson('/api/external/v1/seminars', [
            'name' => 'TCC - Machine Learning em Redes',
            'description' => 'Apresentação de TCC sobre ML aplicado a redes.',
            'scheduled_at' => '2026-06-15 14:00:00',
            'active' => true,
            'location' => ['name' => 'Sala 101', 'max_vacancies' => 30],
            'seminar_type_id' => $type->id,
            'subjects' => ['Machine Learning', 'Redes de Computadores'],
            'speakers' => [
                [
                    'name' => 'João Silva',
                    'email' => 'joao@example.com',
                    'institution' => 'CEFET-RJ',
                    'description' => 'Aluno de graduação',
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'TCC - Machine Learning em Redes')
            ->assertJsonPath('data.location.name', 'Sala 101')
            ->assertJsonPath('data.location.max_vacancies', 30)
            ->assertJsonPath('data.seminar_type', 'TCC');

        expect($response->json('data.subjects'))->toHaveCount(2);
        expect($response->json('data.speakers'))->toHaveCount(1);
        expect($response->json('data.speakers.0.email'))->toBe('joao@example.com');
        expect($response->json('data.speakers.0.institution'))->toBe('CEFET-RJ');

        // Verify database records were created
        expect(Subject::where('name', 'Machine Learning')->exists())->toBeTrue();
        expect(Subject::where('name', 'Redes de Computadores')->exists())->toBeTrue();
        expect(SeminarLocation::where('name', 'Sala 101')->exists())->toBeTrue();
        expect(User::where('email', 'joao@example.com')->exists())->toBeTrue();
        expect(UserSpeakerData::whereHas('user', fn ($q) => $q->where('email', 'joao@example.com'))->exists())->toBeTrue();
    });

    it('reuses existing subjects and speakers by email', function () {
        actingAsAdmin();

        $existingSubject = Subject::factory()->create(['name' => 'Machine Learning']);
        $existingSpeaker = User::factory()->speaker()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/external/v1/seminars', [
            'name' => 'TCC Reuse Test',
            'scheduled_at' => '2026-06-15 14:00:00',
            'active' => true,
            'location' => ['name' => 'Sala 101'],
            'subjects' => ['Machine Learning'],
            'speakers' => [
                [
                    'name' => 'Existing Speaker',
                    'email' => 'existing@example.com',
                ],
            ],
        ]);

        $response->assertCreated();

        // Should not create duplicates
        expect(Subject::where('name', 'Machine Learning')->count())->toBe(1);
        expect(User::where('email', 'existing@example.com')->count())->toBe(1);

        // Speaker should be the existing user
        expect($response->json('data.speakers.0.id'))->toBe($existingSpeaker->id);
    });

    it('reuses existing location and seminar type', function () {
        actingAsAdmin();

        $location = SeminarLocation::factory()->create(['name' => 'Sala 201']);
        $type = SeminarType::factory()->create(['name' => 'Workshop']);

        $response = $this->postJson('/api/external/v1/seminars', [
            'name' => 'Reuse Location Test',
            'scheduled_at' => '2026-06-15 14:00:00',
            'active' => true,
            'location' => ['name' => 'Sala 201'],
            'seminar_type_id' => $type->id,
            'subjects' => ['Test Subject'],
            'speakers' => [
                ['name' => 'Speaker', 'email' => 'speaker@example.com'],
            ],
        ]);

        $response->assertCreated();
        expect($response->json('data.location.id'))->toBe($location->id);
        expect(SeminarLocation::where('name', 'Sala 201')->count())->toBe(1);
    });

    it('defaults active to true when not provided', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/seminars', [
            'name' => 'Default Active Test',
            'scheduled_at' => '2026-06-15 14:00:00',
            'location' => ['name' => 'Sala 101'],
            'subjects' => ['Test'],
            'speakers' => [
                ['name' => 'Speaker', 'email' => 'speaker@example.com'],
            ],
        ]);

        $response->assertCreated();
        expect($response->json('data.active'))->toBeTrue();
    });

    it('validates required fields', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/seminars', []);

        $response->assertStatus(422);
    });

    it('validates speaker email format', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/external/v1/seminars', [
            'name' => 'Test',
            'scheduled_at' => '2026-06-15 14:00:00',
            'location' => ['name' => 'Sala 101'],
            'subjects' => ['Test'],
            'speakers' => [
                ['name' => 'Speaker', 'email' => 'not-an-email'],
            ],
        ]);

        $response->assertStatus(422);
    });

    it('returns 403 for regular user', function () {
        actingAsUser();

        $response = $this->postJson('/api/external/v1/seminars', [
            'name' => 'Test',
            'scheduled_at' => '2026-06-15 14:00:00',
            'location' => ['name' => 'Sala 101'],
            'subjects' => ['Test'],
            'speakers' => [
                ['name' => 'Speaker', 'email' => 'speaker@example.com'],
            ],
        ]);

        $response->assertForbidden();
    });

    it('works with bearer token authentication', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test-token');

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/external/v1/seminars', [
                'name' => 'Bearer Token Test',
                'scheduled_at' => '2026-06-15 14:00:00',
                'location' => ['name' => 'Sala 101'],
                'subjects' => ['Test'],
                'speakers' => [
                    ['name' => 'Speaker', 'email' => 'speaker@example.com'],
                ],
            ]);

        $response->assertCreated();
        expect($response->json('data.name'))->toBe('Bearer Token Test');
    });

    it('associates seminar with optional workshop', function () {
        actingAsAdmin();
        $workshop = Workshop::factory()->create();

        $response = $this->postJson('/api/external/v1/seminars', [
            'name' => 'Workshop Test',
            'scheduled_at' => '2026-06-15 14:00:00',
            'location' => ['name' => 'Sala 101'],
            'workshop_id' => $workshop->id,
            'subjects' => ['Test'],
            'speakers' => [
                ['name' => 'Speaker', 'email' => 'speaker@example.com'],
            ],
        ]);

        $response->assertCreated();
        expect($response->json('data.workshop.id'))->toBe($workshop->id);
    });
});

describe('PUT /api/external/v1/seminars/{id}', function () {
    it('updates seminar name and regenerates slug', function () {
        $admin = actingAsAdmin();

        $seminar = Seminar::factory()
            ->withSpeakers()
            ->withSubjects()
            ->create(['created_by' => $admin->id]);

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->id}", [
            'name' => 'Updated Seminar Name',
        ]);

        $response->assertSuccessful();
        expect($response->json('data.name'))->toBe('Updated Seminar Name');
        expect($response->json('data.slug'))->toContain('updated-seminar-name');
    });

    it('updates speakers by replacing with new ones', function () {
        $admin = actingAsAdmin();

        $seminar = Seminar::factory()
            ->withSpeakers()
            ->create(['created_by' => $admin->id]);

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->id}", [
            'speakers' => [
                [
                    'name' => 'New Speaker',
                    'email' => 'new-speaker@example.com',
                    'institution' => 'MIT',
                ],
            ],
        ]);

        $response->assertSuccessful();
        expect($response->json('data.speakers'))->toHaveCount(1);
        expect($response->json('data.speakers.0.email'))->toBe('new-speaker@example.com');
    });

    it('updates subjects', function () {
        $admin = actingAsAdmin();

        $seminar = Seminar::factory()
            ->withSubjects()
            ->create(['created_by' => $admin->id]);

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->id}", [
            'subjects' => ['New Subject A', 'New Subject B'],
        ]);

        $response->assertSuccessful();
        $subjects = $response->json('data.subjects');
        expect($subjects)->toHaveCount(2);
        expect($subjects)->toContain('New Subject A');
        expect($subjects)->toContain('New Subject B');
    });

    it('updates location by name', function () {
        $admin = actingAsAdmin();

        $seminar = Seminar::factory()->create(['created_by' => $admin->id]);

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->id}", [
            'location' => ['name' => 'New Room 301', 'max_vacancies' => 100],
        ]);

        $response->assertSuccessful();
        expect($response->json('data.location.name'))->toBe('New Room 301');
        expect($response->json('data.location.max_vacancies'))->toBe(100);
    });

    it('returns 403 when teacher updates another users seminar', function () {
        actingAsTeacher();

        $seminar = Seminar::factory()->create();

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->id}", [
            'name' => 'Unauthorized Update',
        ]);

        $response->assertForbidden();
    });

    it('updates seminar type by id', function () {
        $admin = actingAsAdmin();
        $type = SeminarType::factory()->create(['name' => 'TCC']);

        $seminar = Seminar::factory()->create([
            'created_by' => $admin->id,
            'seminar_type_id' => null,
        ]);

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->id}", [
            'seminar_type_id' => $type->id,
        ]);

        $response->assertSuccessful();
        expect($response->json('data.seminar_type'))->toBe('TCC');
    });

    it('clears seminar type by passing null', function () {
        $admin = actingAsAdmin();

        $type = SeminarType::factory()->create();
        $seminar = Seminar::factory()->create([
            'created_by' => $admin->id,
            'seminar_type_id' => $type->id,
        ]);

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->id}", [
            'seminar_type_id' => null,
        ]);

        $response->assertSuccessful();
        expect($response->json('data.seminar_type'))->toBeNull();
    });

    it('dispatches reschedule job when scheduled_at changes', function () {
        Bus::fake([ProcessSeminarRescheduleJob::class]);

        $admin = actingAsAdmin();

        $seminar = Seminar::factory()->create([
            'created_by' => $admin->id,
            'scheduled_at' => '2026-06-15 14:00:00',
        ]);

        $this->putJson("/api/external/v1/seminars/{$seminar->id}", [
            'scheduled_at' => '2026-06-20 14:00:00',
        ]);

        Bus::assertDispatched(ProcessSeminarRescheduleJob::class);
    });

    it('allows partial updates', function () {
        $admin = actingAsAdmin();

        $seminar = Seminar::factory()->create([
            'created_by' => $admin->id,
            'name' => 'Original Name',
            'active' => true,
        ]);

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->id}", [
            'active' => false,
        ]);

        $response->assertSuccessful();
        expect($response->json('data.active'))->toBeFalse();
        expect($response->json('data.name'))->toBe('Original Name');
    });
});
