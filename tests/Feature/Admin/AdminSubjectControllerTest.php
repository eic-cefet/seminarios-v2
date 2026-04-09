<?php

use App\Models\Seminar;
use App\Models\Subject;

describe('GET /api/admin/subjects', function () {
    it('returns paginated list of subjects for admin', function () {
        actingAsAdmin();

        Subject::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/subjects');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'seminars_count'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/admin/subjects');

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $response = $this->getJson('/api/admin/subjects');

        $response->assertForbidden();
    });

    it('returns 403 for teacher user', function () {
        actingAsTeacher();

        $response = $this->getJson('/api/admin/subjects');

        $response->assertForbidden();
    });

    it('filters subjects by search term', function () {
        actingAsAdmin();

        Subject::factory()->create(['name' => 'Machine Learning']);
        Subject::factory()->create(['name' => 'Web Development']);

        $response = $this->getJson('/api/admin/subjects?search=Machine');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Machine Learning');
    });

    it('orders subjects by name', function () {
        actingAsAdmin();

        Subject::factory()->create(['name' => 'Zebra']);
        Subject::factory()->create(['name' => 'Apple']);
        Subject::factory()->create(['name' => 'Banana']);

        $response = $this->getJson('/api/admin/subjects');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data[0]['name'])->toBe('Apple');
        expect($data[1]['name'])->toBe('Banana');
        expect($data[2]['name'])->toBe('Zebra');
    });
});

describe('GET /api/admin/subjects/{id}', function () {
    it('returns subject by id for admin', function () {
        actingAsAdmin();

        $subject = Subject::factory()->create(['name' => 'Test Subject']);

        $response = $this->getJson("/api/admin/subjects/{$subject->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $subject->id)
            ->assertJsonPath('data.name', 'Test Subject');
    });

    it('returns 404 for non-existent subject', function () {
        actingAsAdmin();

        $response = $this->getJson('/api/admin/subjects/99999');

        $response->assertNotFound();
    });

    it('includes seminars count', function () {
        actingAsAdmin();

        $subject = Subject::factory()->create();
        $seminar = Seminar::factory()->create();
        $seminar->subjects()->attach($subject);

        $response = $this->getJson("/api/admin/subjects/{$subject->id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.seminars_count', 1);
    });
});

describe('POST /api/admin/subjects', function () {
    it('creates a new subject for admin', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/subjects', [
            'name' => 'Novo Tópico',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Tópico criado com sucesso')
            ->assertJsonPath('data.name', 'Novo Tópico');

        expect(Subject::where('name', 'Novo Tópico')->exists())->toBeTrue();
    });

    it('returns validation error for missing name', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/subjects', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('returns validation error for duplicate name', function () {
        actingAsAdmin();

        Subject::factory()->create(['name' => 'Existing Subject']);

        $response = $this->postJson('/api/admin/subjects', [
            'name' => 'Existing Subject',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });
});

describe('PUT /api/admin/subjects/{id}', function () {
    it('updates subject for admin', function () {
        actingAsAdmin();

        $subject = Subject::factory()->create();

        $response = $this->putJson("/api/admin/subjects/{$subject->id}", [
            'name' => 'Tópico Atualizado',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Tópico atualizado com sucesso')
            ->assertJsonPath('data.name', 'Tópico Atualizado');
    });

    it('returns validation error for duplicate name', function () {
        actingAsAdmin();

        Subject::factory()->create(['name' => 'Other Subject']);
        $subject = Subject::factory()->create(['name' => 'My Subject']);

        $response = $this->putJson("/api/admin/subjects/{$subject->id}", [
            'name' => 'Other Subject',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('allows updating to same name', function () {
        actingAsAdmin();

        $subject = Subject::factory()->create(['name' => 'My Subject']);

        $response = $this->putJson("/api/admin/subjects/{$subject->id}", [
            'name' => 'My Subject',
        ]);

        $response->assertSuccessful();
    });
});

describe('DELETE /api/admin/subjects/{id}', function () {
    it('deletes subject without seminars', function () {
        actingAsAdmin();

        $subject = Subject::factory()->create();

        $response = $this->deleteJson("/api/admin/subjects/{$subject->id}");

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Tópico excluído com sucesso');

        expect(Subject::find($subject->id))->toBeNull();
    });

    it('returns error when subject has seminars', function () {
        actingAsAdmin();

        $subject = Subject::factory()->create();
        $seminar = Seminar::factory()->create();
        $seminar->subjects()->attach($subject);

        $response = $this->deleteJson("/api/admin/subjects/{$subject->id}");

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Este assunto está associado a seminários e não pode ser excluído');

        expect(Subject::find($subject->id))->not->toBeNull();
    });
});

describe('POST /api/admin/subjects/merge', function () {
    it('merges subjects successfully', function () {
        actingAsAdmin();

        $target = Subject::factory()->create(['name' => 'Target Subject']);
        $source1 = Subject::factory()->create(['name' => 'Source 1']);
        $source2 = Subject::factory()->create(['name' => 'Source 2']);

        // Create seminars with source subjects
        $seminar1 = Seminar::factory()->create();
        $seminar1->subjects()->attach($source1);

        $seminar2 = Seminar::factory()->create();
        $seminar2->subjects()->attach($source2);

        $response = $this->postJson('/api/admin/subjects/merge', [
            'target_id' => $target->id,
            'source_ids' => [$source1->id, $source2->id],
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Tópicos mesclados com sucesso');

        // Verify source subjects were deleted
        expect(Subject::find($source1->id))->toBeNull();
        expect(Subject::find($source2->id))->toBeNull();

        // Verify seminars now have target subject
        expect($seminar1->fresh()->subjects->pluck('id')->contains($target->id))->toBeTrue();
        expect($seminar2->fresh()->subjects->pluck('id')->contains($target->id))->toBeTrue();
    });

    it('merges subjects with new name', function () {
        actingAsAdmin();

        $target = Subject::factory()->create(['name' => 'Old Name']);
        $source = Subject::factory()->create();

        $response = $this->postJson('/api/admin/subjects/merge', [
            'target_id' => $target->id,
            'source_ids' => [$source->id],
            'new_name' => 'New Combined Name',
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.name', 'New Combined Name');
    });

    it('returns validation error when target in source', function () {
        actingAsAdmin();

        $subject1 = Subject::factory()->create();
        $subject2 = Subject::factory()->create();

        $response = $this->postJson('/api/admin/subjects/merge', [
            'target_id' => $subject1->id,
            'source_ids' => [$subject1->id, $subject2->id],
        ]);

        // Validation rule rejects when source_ids contains target_id
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source_ids.0']);
    });

    it('returns validation error for same source as target', function () {
        actingAsAdmin();

        $subject = Subject::factory()->create();

        $response = $this->postJson('/api/admin/subjects/merge', [
            'target_id' => $subject->id,
            'source_ids' => [$subject->id],
        ]);

        // Validation rejects when target is in source_ids
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source_ids.0']);
    });

    it('returns validation error for missing target_id', function () {
        actingAsAdmin();

        $response = $this->postJson('/api/admin/subjects/merge', [
            'source_ids' => [1, 2],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_id']);
    });

    it('returns validation error for missing source_ids', function () {
        actingAsAdmin();

        $subject = Subject::factory()->create();

        $response = $this->postJson('/api/admin/subjects/merge', [
            'target_id' => $subject->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source_ids']);
    });

    it('returns error when database exception occurs during merge', function () {
        actingAsAdmin();

        $target = Subject::factory()->create();
        $source = Subject::factory()->create();

        // Create a seminar with the source subject to ensure there's data to migrate
        $seminar = Seminar::factory()->create();
        $seminar->subjects()->attach($source);

        // Use a DB listener to detect when the merge starts accessing seminar_subject
        // and throw an exception before completion
        $queryCount = 0;
        \Illuminate\Support\Facades\DB::listen(function ($query) use (&$queryCount) {
            // Count queries that touch seminar_subject after the transaction begins
            if (str_contains($query->sql, 'seminar_subject')) {
                $queryCount++;
                // After the first select (pluck), throw on the check or insert
                if ($queryCount >= 2) {
                    throw new \Exception('Simulated database failure');
                }
            }
        });

        $response = $this->postJson('/api/admin/subjects/merge', [
            'target_id' => $target->id,
            'source_ids' => [$source->id],
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Não foi possível mesclar os assuntos');
    });
});
