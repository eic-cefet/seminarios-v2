<?php

use App\Models\Seminar;
use App\Models\SeminarType;
use App\Models\Subject;

describe('GET /api/seminars', function () {
    it('returns paginated list of active seminars', function () {
        Seminar::factory()->count(3)->create(['active' => true]);
        Seminar::factory()->create(['active' => false]);

        $response = $this->getJson('/api/seminars');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'scheduledAt'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('filters seminars by type', function () {
        $type = SeminarType::factory()->create(['name' => 'Palestra']);
        $otherType = SeminarType::factory()->create(['name' => 'Workshop']);

        Seminar::factory()->count(2)->create([
            'active' => true,
            'seminar_type_id' => $type->id,
        ]);
        Seminar::factory()->create([
            'active' => true,
            'seminar_type_id' => $otherType->id,
        ]);

        $response = $this->getJson('/api/seminars?type=Palestra');

        $response->assertSuccessful()
            ->assertJsonCount(2, 'data');
    });

    it('filters seminars by subject', function () {
        $subject = Subject::factory()->create();

        $seminarWithSubject = Seminar::factory()->create(['active' => true]);
        $seminarWithSubject->subjects()->attach($subject);

        Seminar::factory()->create(['active' => true]);

        $response = $this->getJson("/api/seminars?subject={$subject->id}");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $seminarWithSubject->id);
    });

    it('filters upcoming seminars only', function () {
        Seminar::factory()->upcoming()->create(['active' => true]);
        Seminar::factory()->past()->create(['active' => true]);

        $response = $this->getJson('/api/seminars?upcoming=1');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data');
    });

    it('filters expired seminars only', function () {
        Seminar::factory()->upcoming()->create(['active' => true]);
        Seminar::factory()->past()->create(['active' => true]);

        $response = $this->getJson('/api/seminars?expired=1');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data');
    });

    it('sorts seminars by scheduled_at', function () {
        $older = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->addDays(1),
        ]);
        $newer = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->addDays(10),
        ]);

        $response = $this->getJson('/api/seminars?sort=scheduled_at&direction=asc');

        $response->assertSuccessful()
            ->assertJsonPath('data.0.id', $older->id)
            ->assertJsonPath('data.1.id', $newer->id);

        $response = $this->getJson('/api/seminars?sort=scheduled_at&direction=desc');

        $response->assertSuccessful()
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
    });

    it('paginates results', function () {
        Seminar::factory()->count(20)->create(['active' => true]);

        $response = $this->getJson('/api/seminars?per_page=5');

        $response->assertSuccessful()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20);
    });
});

describe('GET /api/seminars/upcoming', function () {
    it('returns upcoming seminars limited to 6', function () {
        Seminar::factory()->count(10)->upcoming()->create(['active' => true]);
        Seminar::factory()->past()->create(['active' => true]);

        $response = $this->getJson('/api/seminars/upcoming');

        $response->assertSuccessful()
            ->assertJsonCount(6, 'data');
    });

    it('orders by scheduled_at ascending', function () {
        $later = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->addDays(5),
        ]);
        $sooner = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/seminars/upcoming');

        $response->assertSuccessful()
            ->assertJsonPath('data.0.id', $sooner->id)
            ->assertJsonPath('data.1.id', $later->id);
    });

    it('excludes inactive seminars', function () {
        Seminar::factory()->upcoming()->create(['active' => true]);
        Seminar::factory()->upcoming()->create(['active' => false]);

        $response = $this->getJson('/api/seminars/upcoming');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data');
    });
});

describe('GET /api/seminars/{slug}', function () {
    it('returns seminar by slug', function () {
        $seminar = Seminar::factory()->create([
            'active' => true,
            'slug' => 'test-seminar-slug',
        ]);

        $response = $this->getJson('/api/seminars/test-seminar-slug');

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $seminar->id)
            ->assertJsonPath('data.slug', 'test-seminar-slug');
    });

    it('returns 404 for non-existent slug', function () {
        $response = $this->getJson('/api/seminars/non-existent-slug');

        $response->assertNotFound();
    });

    it('returns 404 for inactive seminar', function () {
        Seminar::factory()->create([
            'active' => false,
            'slug' => 'inactive-seminar',
        ]);

        $response = $this->getJson('/api/seminars/inactive-seminar');

        $response->assertNotFound();
    });

    it('includes related data in response', function () {
        $seminar = Seminar::factory()->create(['active' => true]);
        $subject = Subject::factory()->create();
        $seminar->subjects()->attach($subject);

        $response = $this->getJson("/api/seminars/{$seminar->slug}");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'scheduledAt',
                    'seminarType',
                    'subjects',
                    'speakers',
                    'registrationsCount',
                ],
            ]);
    });
});

describe('GET /api/subjects/{subject}/seminars', function () {
    it('returns seminars for a subject', function () {
        $subject = Subject::factory()->create();

        $seminarWithSubject = Seminar::factory()->create(['active' => true]);
        $seminarWithSubject->subjects()->attach($subject);

        $otherSeminar = Seminar::factory()->create(['active' => true]);

        $response = $this->getJson("/api/subjects/{$subject->id}/seminars");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $seminarWithSubject->id);
    });

    it('filters upcoming seminars by subject', function () {
        $subject = Subject::factory()->create();

        $upcomingSeminar = Seminar::factory()->upcoming()->create(['active' => true]);
        $upcomingSeminar->subjects()->attach($subject);

        $pastSeminar = Seminar::factory()->past()->create(['active' => true]);
        $pastSeminar->subjects()->attach($subject);

        $response = $this->getJson("/api/subjects/{$subject->id}/seminars?upcoming=1");

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $upcomingSeminar->id);
    });

    it('returns 404 for non-existent subject', function () {
        $response = $this->getJson('/api/subjects/99999/seminars');

        $response->assertNotFound();
    });
});
