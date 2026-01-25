<?php

use App\Models\Seminar;
use App\Models\Subject;

describe('GET /api/subjects', function () {
    it('returns list of subjects', function () {
        Subject::factory()->count(3)->create();

        $response = $this->getJson('/api/subjects');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'seminarsCount'],
                ],
            ]);
    });

    it('orders subjects by name by default', function () {
        $subjectC = Subject::factory()->create(['name' => 'C Subject']);
        $subjectA = Subject::factory()->create(['name' => 'A Subject']);
        $subjectB = Subject::factory()->create(['name' => 'B Subject']);

        $response = $this->getJson('/api/subjects');

        $response->assertSuccessful()
            ->assertJsonPath('data.0.name', 'A Subject')
            ->assertJsonPath('data.1.name', 'B Subject')
            ->assertJsonPath('data.2.name', 'C Subject');
    });

    it('orders subjects by seminar count when requested', function () {
        $subjectFew = Subject::factory()->create();
        $subjectMany = Subject::factory()->create();

        $seminar1 = Seminar::factory()->create();
        $seminar2 = Seminar::factory()->create();
        $seminar3 = Seminar::factory()->create();

        $seminar1->subjects()->attach($subjectMany);
        $seminar2->subjects()->attach($subjectMany);
        $seminar3->subjects()->attach($subjectMany);
        $seminar1->subjects()->attach($subjectFew);

        $response = $this->getJson('/api/subjects?sort=seminars');

        $response->assertSuccessful()
            ->assertJsonPath('data.0.id', $subjectMany->id)
            ->assertJsonPath('data.1.id', $subjectFew->id);
    });

    it('limits results when requested', function () {
        Subject::factory()->count(10)->create();

        $response = $this->getJson('/api/subjects?limit=5');

        $response->assertSuccessful()
            ->assertJsonCount(5, 'data');
    });
});

describe('GET /api/subjects/{id}', function () {
    it('returns subject by id', function () {
        $subject = Subject::factory()->create(['name' => 'Test Subject']);

        $response = $this->getJson("/api/subjects/{$subject->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $subject->id)
            ->assertJsonPath('data.name', 'Test Subject');
    });

    it('includes seminars count', function () {
        $subject = Subject::factory()->create();
        $seminar = Seminar::factory()->create();
        $seminar->subjects()->attach($subject);

        $response = $this->getJson("/api/subjects/{$subject->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.seminarsCount', 1);
    });

    it('returns 404 for non-existent subject', function () {
        $response = $this->getJson('/api/subjects/99999');

        $response->assertNotFound();
    });
});
