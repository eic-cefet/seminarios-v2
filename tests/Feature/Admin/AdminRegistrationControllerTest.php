<?php

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;

describe('GET /api/admin/registrations', function () {
    it('returns paginated list of registrations for admin', function () {
        actingAsAdmin();

        Registration::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/registrations');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'present', 'user', 'seminar'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    });

    it('returns 401 for unauthenticated user', function () {
        $response = $this->getJson('/api/admin/registrations');

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $response = $this->getJson('/api/admin/registrations');

        $response->assertForbidden();
    });

    it('allows teacher to list registrations scoped to their seminars', function () {
        $teacher = actingAsTeacher();

        $teacherSeminar = Seminar::factory()->create(['created_by' => $teacher->id]);
        $otherSeminar = Seminar::factory()->create();

        $teacherRegistration = Registration::factory()->create(['seminar_id' => $teacherSeminar->id]);
        $otherRegistration = Registration::factory()->create(['seminar_id' => $otherSeminar->id]);

        $response = $this->getJson('/api/admin/registrations');

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $teacherRegistration->id);
        expect(collect($response->json('data'))->pluck('id'))
            ->not->toContain($otherRegistration->id);
    });

    it('filters registrations by seminar_id', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create();
        $registrationForSeminar = Registration::factory()->create(['seminar_id' => $seminar->id]);
        $otherRegistration = Registration::factory()->create();

        $response = $this->getJson("/api/admin/registrations?seminar_id={$seminar->id}");

        $response->assertSuccessful();
        $data = $response->json('data');
        expect(collect($data)->pluck('id'))->toContain($registrationForSeminar->id);
        expect(collect($data)->pluck('id'))->not->toContain($otherRegistration->id);
    });

    it('filters registrations by search term (user name)', function () {
        actingAsAdmin();

        $user = User::factory()->create(['name' => 'João Silva']);
        $registrationForUser = Registration::factory()->create(['user_id' => $user->id]);
        $otherRegistration = Registration::factory()->create();

        $response = $this->getJson('/api/admin/registrations?search=João');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect(collect($data)->pluck('id'))->toContain($registrationForUser->id);
    });

    it('filters registrations by search term (user email)', function () {
        actingAsAdmin();

        $user = User::factory()->create(['email' => 'joao@example.com']);
        $registrationForUser = Registration::factory()->create(['user_id' => $user->id]);
        $otherRegistration = Registration::factory()->create();

        $response = $this->getJson('/api/admin/registrations?search=joao@example.com');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect(collect($data)->pluck('id'))->toContain($registrationForUser->id);
    });

    it('orders registrations by created_at descending', function () {
        actingAsAdmin();

        $older = Registration::factory()->create(['created_at' => now()->subDays(2)]);
        $newer = Registration::factory()->create(['created_at' => now()]);

        $response = $this->getJson('/api/admin/registrations');

        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data[0]['id'])->toBe($newer->id);
        expect($data[1]['id'])->toBe($older->id);
    });
});

describe('PATCH /api/admin/registrations/{id}/presence', function () {
    it('toggles presence for admin', function () {
        actingAsAdmin();

        $registration = Registration::factory()->create(['present' => false]);

        $response = $this->patchJson("/api/admin/registrations/{$registration->id}/presence");

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Presença confirmada')
            ->assertJsonPath('data.present', true);

        $registration->refresh();
        expect($registration->present)->toBeTrue();
    });

    it('toggles presence off for admin', function () {
        actingAsAdmin();

        $registration = Registration::factory()->create(['present' => true]);

        $response = $this->patchJson("/api/admin/registrations/{$registration->id}/presence");

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Presença removida')
            ->assertJsonPath('data.present', false);

        $registration->refresh();
        expect($registration->present)->toBeFalse();
    });

    it('returns 401 for unauthenticated user', function () {
        $registration = Registration::factory()->create();

        $response = $this->patchJson("/api/admin/registrations/{$registration->id}/presence");

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $registration = Registration::factory()->create();

        $response = $this->patchJson("/api/admin/registrations/{$registration->id}/presence");

        $response->assertForbidden();
    });

    it('returns 403 for teacher on another teachers seminar', function () {
        actingAsTeacher();

        $registration = Registration::factory()->create();

        $response = $this->patchJson("/api/admin/registrations/{$registration->id}/presence");

        $response->assertForbidden();
    });

    it('allows teacher to toggle presence for their own seminar registration', function () {
        $teacher = actingAsTeacher();

        $seminar = Seminar::factory()->create(['created_by' => $teacher->id]);
        $registration = Registration::factory()->create(['seminar_id' => $seminar->id, 'present' => false]);

        $response = $this->patchJson("/api/admin/registrations/{$registration->id}/presence");

        $response->assertSuccessful()
            ->assertJsonPath('data.present', true);

        $registration->refresh();
        expect($registration->present)->toBeTrue();
    });

    it('returns 404 for non-existent registration', function () {
        actingAsAdmin();

        $response = $this->patchJson('/api/admin/registrations/99999/presence');

        $response->assertNotFound();
    });
});
