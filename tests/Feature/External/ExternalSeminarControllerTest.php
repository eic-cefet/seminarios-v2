<?php

use App\Jobs\ProcessSeminarRescheduleJob;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\SeminarType;
use App\Models\Subject;
use App\Models\User;
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
        $this->getJson('/api/external/v1/seminars')->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();
        $this->getJson('/api/external/v1/seminars')->assertForbidden();
    });

    it('teacher only sees own seminars', function () {
        $teacher = actingAsTeacher();
        $ownSeminar = Seminar::factory()->create(['created_by' => $teacher->id]);
        Seminar::factory()->create();

        $response = $this->getJson('/api/external/v1/seminars');

        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($ownSeminar->id)->toHaveCount(1);
    });

    it('filters by active status', function () {
        actingAsAdmin();
        Seminar::factory()->create(['name' => 'Active', 'active' => true]);
        Seminar::factory()->create(['name' => 'Inactive', 'active' => false]);

        $response = $this->getJson('/api/external/v1/seminars?active=true');

        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('Active');
    });

    it('filters by search term', function () {
        actingAsAdmin();
        Seminar::factory()->create(['name' => 'TCC Machine Learning']);
        Seminar::factory()->create(['name' => 'Workshop Web Dev']);

        $response = $this->getJson('/api/external/v1/seminars?search=TCC');

        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.name'))->toBe('TCC Machine Learning');
    });
});

describe('GET /api/external/v1/seminars/{slug}', function () {
    it('returns seminar details with speakers and subjects', function () {
        actingAsAdmin();
        $seminar = Seminar::factory()->withSpeakers(2)->withSubjects(3)->create();

        $response = $this->getJson("/api/external/v1/seminars/{$seminar->slug}");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'slug', 'description', 'scheduled_at',
                    'location' => ['id', 'name', 'max_vacancies'],
                    'subjects',
                    'speakers' => [['id', 'name', 'email']],
                ],
            ]);
        expect($response->json('data.speakers'))->toHaveCount(2);
        expect($response->json('data.subjects'))->toHaveCount(3);
    });

    it('returns 403 when teacher views another users seminar', function () {
        actingAsTeacher();
        $seminar = Seminar::factory()->create();

        $this->getJson("/api/external/v1/seminars/{$seminar->slug}")->assertForbidden();
    });

    it('resolves a seminar by slug, not id', function () {
        actingAsAdmin();
        $seminar = Seminar::factory()->create(['name' => 'Distributed Systems Seminar']);

        $this->getJson("/api/external/v1/seminars/{$seminar->slug}")
            ->assertSuccessful()
            ->assertJsonPath('data.id', $seminar->id)
            ->assertJsonPath('data.slug', $seminar->slug);
    });
});

describe('POST /api/external/v1/seminars', function () {
    it('creates a seminar with location_id, type_id, subjects, and speaker_ids', function () {
        actingAsAdmin();
        $location = SeminarLocation::factory()->create(['name' => 'Sala 101', 'max_vacancies' => 30]);
        $type = SeminarType::factory()->create(['name' => 'TCC']);
        $speaker = User::factory()->speaker()->create();

        $response = $this->postJson('/api/external/v1/seminars', [
            'name' => 'TCC - Machine Learning em Redes',
            'description' => 'Apresentação de TCC.',
            'scheduled_at' => '2026-06-15 14:00:00',
            'active' => true,
            'seminar_location_id' => $location->id,
            'seminar_type_id' => $type->id,
            'subjects' => ['Machine Learning', 'Redes de Computadores'],
            'speaker_ids' => [$speaker->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'TCC - Machine Learning em Redes')
            ->assertJsonPath('data.location.name', 'Sala 101')
            ->assertJsonPath('data.location.max_vacancies', 30)
            ->assertJsonPath('data.seminar_type', 'TCC');

        expect($response->json('data.subjects'))->toHaveCount(2);
        expect($response->json('data.speakers'))->toHaveCount(1);
        expect($response->json('data.speakers.0.id'))->toBe($speaker->id);
        expect(Subject::where('name', 'Machine Learning')->exists())->toBeTrue();
    });

    it('reuses existing subjects', function () {
        actingAsAdmin();
        $location = SeminarLocation::factory()->create();
        $subject = Subject::factory()->create(['name' => 'Machine Learning']);
        $speaker = User::factory()->create();

        $this->postJson('/api/external/v1/seminars', [
            'name' => 'Reuse Test',
            'scheduled_at' => '2026-06-15 14:00:00',
            'seminar_location_id' => $location->id,
            'subjects' => ['Machine Learning'],
            'speaker_ids' => [$speaker->id],
        ])->assertCreated();

        expect(Subject::where('name', 'Machine Learning')->count())->toBe(1);
    });

    it('defaults active to true', function () {
        actingAsAdmin();
        $location = SeminarLocation::factory()->create();
        $speaker = User::factory()->create();

        $response = $this->postJson('/api/external/v1/seminars', [
            'name' => 'Default Active',
            'scheduled_at' => '2026-06-15 14:00:00',
            'seminar_location_id' => $location->id,
            'subjects' => ['Test'],
            'speaker_ids' => [$speaker->id],
        ]);

        expect($response->json('data.active'))->toBeTrue();
    });

    it('validates required fields', function () {
        actingAsAdmin();
        $this->postJson('/api/external/v1/seminars', [])->assertStatus(422);
    });

    it('validates speaker_ids exist', function () {
        actingAsAdmin();
        $location = SeminarLocation::factory()->create();

        $this->postJson('/api/external/v1/seminars', [
            'name' => 'Test',
            'scheduled_at' => '2026-06-15 14:00:00',
            'seminar_location_id' => $location->id,
            'subjects' => ['Test'],
            'speaker_ids' => [99999],
        ])->assertStatus(422);
    });

    it('returns 403 for regular user', function () {
        actingAsUser();
        $this->postJson('/api/external/v1/seminars', [
            'name' => 'Test',
            'scheduled_at' => '2026-06-15 14:00:00',
            'seminar_location_id' => 1,
            'subjects' => ['Test'],
            'speaker_ids' => [1],
        ])->assertForbidden();
    });

    it('works with bearer token authentication', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test-token');
        $location = SeminarLocation::factory()->create();
        $speaker = User::factory()->create();

        $response = $this->withToken($token->plainTextToken)
            ->postJson('/api/external/v1/seminars', [
                'name' => 'Bearer Token Test',
                'scheduled_at' => '2026-06-15 14:00:00',
                'seminar_location_id' => $location->id,
                'subjects' => ['Test'],
                'speaker_ids' => [$speaker->id],
            ]);

        $response->assertCreated();
    });

    it('associates seminar with optional workshop', function () {
        actingAsAdmin();
        $location = SeminarLocation::factory()->create();
        $workshop = Workshop::factory()->create();
        $speaker = User::factory()->create();

        $response = $this->postJson('/api/external/v1/seminars', [
            'name' => 'Workshop Test',
            'scheduled_at' => '2026-06-15 14:00:00',
            'seminar_location_id' => $location->id,
            'workshop_id' => $workshop->id,
            'subjects' => ['Test'],
            'speaker_ids' => [$speaker->id],
        ]);

        expect($response->json('data.workshop.id'))->toBe($workshop->id);
    });
});

describe('PUT /api/external/v1/seminars/{slug}', function () {
    it('updates seminar name and regenerates slug', function () {
        $admin = actingAsAdmin();
        $seminar = Seminar::factory()->withSpeakers()->withSubjects()
            ->create(['created_by' => $admin->id]);

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->slug}", [
            'name' => 'Updated Seminar Name',
        ]);

        $response->assertSuccessful();
        expect($response->json('data.name'))->toBe('Updated Seminar Name');
        expect($response->json('data.slug'))->toContain('updated-seminar-name');
    });

    it('updates speaker_ids', function () {
        $admin = actingAsAdmin();
        $seminar = Seminar::factory()->withSpeakers()
            ->create(['created_by' => $admin->id]);
        $newSpeaker = User::factory()->speaker()->create();

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->slug}", [
            'speaker_ids' => [$newSpeaker->id],
        ]);

        expect($response->json('data.speakers'))->toHaveCount(1);
        expect($response->json('data.speakers.0.id'))->toBe($newSpeaker->id);
    });

    it('updates subjects', function () {
        $admin = actingAsAdmin();
        $seminar = Seminar::factory()->withSubjects()
            ->create(['created_by' => $admin->id]);

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->slug}", [
            'subjects' => ['New Subject A', 'New Subject B'],
        ]);

        $subjects = $response->json('data.subjects');
        expect($subjects)->toHaveCount(2)->toContain('New Subject A', 'New Subject B');
    });

    it('updates location by id', function () {
        $admin = actingAsAdmin();
        $seminar = Seminar::factory()->create(['created_by' => $admin->id]);
        $newLocation = SeminarLocation::factory()->create(['name' => 'New Room', 'max_vacancies' => 100]);

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->slug}", [
            'seminar_location_id' => $newLocation->id,
        ]);

        expect($response->json('data.location.name'))->toBe('New Room');
        expect($response->json('data.location.max_vacancies'))->toBe(100);
    });

    it('returns 403 when teacher updates another users seminar', function () {
        actingAsTeacher();
        $seminar = Seminar::factory()->create();

        $this->putJson("/api/external/v1/seminars/{$seminar->slug}", [
            'name' => 'Unauthorized',
        ])->assertForbidden();
    });

    it('updates seminar type by id', function () {
        $admin = actingAsAdmin();
        $type = SeminarType::factory()->create(['name' => 'TCC']);
        $seminar = Seminar::factory()->create(['created_by' => $admin->id, 'seminar_type_id' => null]);

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->slug}", [
            'seminar_type_id' => $type->id,
        ]);

        expect($response->json('data.seminar_type'))->toBe('TCC');
    });

    it('clears seminar type by passing null', function () {
        $admin = actingAsAdmin();
        $type = SeminarType::factory()->create();
        $seminar = Seminar::factory()->create(['created_by' => $admin->id, 'seminar_type_id' => $type->id]);

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->slug}", [
            'seminar_type_id' => null,
        ]);

        expect($response->json('data.seminar_type'))->toBeNull();
    });

    it('dispatches reschedule job when scheduled_at changes', function () {
        Bus::fake([ProcessSeminarRescheduleJob::class]);
        $admin = actingAsAdmin();
        $seminar = Seminar::factory()->create(['created_by' => $admin->id, 'scheduled_at' => '2026-06-15 14:00:00']);

        $this->putJson("/api/external/v1/seminars/{$seminar->slug}", [
            'scheduled_at' => '2026-06-20 14:00:00',
        ]);

        Bus::assertDispatched(ProcessSeminarRescheduleJob::class);
    });

    it('allows partial updates', function () {
        $admin = actingAsAdmin();
        $seminar = Seminar::factory()->create(['created_by' => $admin->id, 'name' => 'Original', 'active' => true]);

        $response = $this->putJson("/api/external/v1/seminars/{$seminar->slug}", [
            'active' => false,
        ]);

        expect($response->json('data.active'))->toBeFalse();
        expect($response->json('data.name'))->toBe('Original');
    });
});
