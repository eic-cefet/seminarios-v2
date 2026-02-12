<?php

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarLocation;

describe('POST /api/seminars/{slug}/register', function () {
    it('registers authenticated user for a seminar', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->upcoming()->create(['active' => true]);

        $response = $this->postJson("/api/seminars/{$seminar->slug}/register");

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Inscrição realizada com sucesso')
            ->assertJsonStructure(['registration' => ['id', 'seminar_id', 'created_at']]);

        expect(Registration::where('user_id', $user->id)->where('seminar_id', $seminar->id)->exists())
            ->toBeTrue();
    });

    it('returns 401 for unauthenticated user', function () {
        $seminar = Seminar::factory()->upcoming()->create(['active' => true]);

        $response = $this->postJson("/api/seminars/{$seminar->slug}/register");

        $response->assertStatus(401);
    });

    it('returns error for expired seminar', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->past()->create(['active' => true]);

        $response = $this->postJson("/api/seminars/{$seminar->slug}/register");

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Este seminário já foi realizado');
    });

    it('returns error when already registered', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->upcoming()->create(['active' => true]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
        ]);

        $response = $this->postJson("/api/seminars/{$seminar->slug}/register");

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Você já está inscrito neste seminário');
    });

    it('returns 404 for non-existent seminar', function () {
        actingAsUser();

        $response = $this->postJson('/api/seminars/non-existent/register');

        $response->assertStatus(404);
    });

    it('returns 404 for inactive seminar', function () {
        actingAsUser();
        $seminar = Seminar::factory()->upcoming()->create(['active' => false]);

        $response = $this->postJson("/api/seminars/{$seminar->slug}/register");

        $response->assertStatus(404);
    });

    it('returns error when seminar is full', function () {
        actingAsUser();
        $location = SeminarLocation::factory()->create(['max_vacancies' => 1]);
        $seminar = Seminar::factory()->upcoming()->create([
            'active' => true,
            'seminar_location_id' => $location->id,
        ]);

        // Fill the single vacancy
        Registration::factory()->create(['seminar_id' => $seminar->id]);

        $response = $this->postJson("/api/seminars/{$seminar->slug}/register");

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Este seminário atingiu sua capacidade máxima');
    });
});

describe('DELETE /api/seminars/{slug}/register', function () {
    it('cancels registration for authenticated user', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->upcoming()->create(['active' => true]);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
        ]);

        $response = $this->deleteJson("/api/seminars/{$seminar->slug}/register");

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Inscrição cancelada com sucesso');

        expect(Registration::find($registration->id))->toBeNull();
    });

    it('returns 401 for unauthenticated user', function () {
        $seminar = Seminar::factory()->upcoming()->create(['active' => true]);

        $response = $this->deleteJson("/api/seminars/{$seminar->slug}/register");

        $response->assertStatus(401);
    });

    it('returns error when not registered', function () {
        actingAsUser();
        $seminar = Seminar::factory()->upcoming()->create(['active' => true]);

        $response = $this->deleteJson("/api/seminars/{$seminar->slug}/register");

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Você não está inscrito neste seminário');
    });
});

describe('GET /api/seminars/{slug}/registration (status)', function () {
    it('returns registration status for authenticated user', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create(['active' => true]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
        ]);

        $response = $this->getJson("/api/seminars/{$seminar->slug}/registration");

        $response->assertSuccessful()
            ->assertJsonPath('registered', true)
            ->assertJsonStructure(['registration' => ['id', 'created_at']]);
    });

    it('returns not registered for authenticated user without registration', function () {
        actingAsUser();
        $seminar = Seminar::factory()->create(['active' => true]);

        $response = $this->getJson("/api/seminars/{$seminar->slug}/registration");

        $response->assertSuccessful()
            ->assertJsonPath('registered', false)
            ->assertJsonPath('registration', null);
    });

    it('returns not registered for unauthenticated user', function () {
        $seminar = Seminar::factory()->create(['active' => true]);

        $response = $this->getJson("/api/seminars/{$seminar->slug}/registration");

        $response->assertSuccessful()
            ->assertJsonPath('registered', false);
    });
});
