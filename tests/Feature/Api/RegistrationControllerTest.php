<?php

use App\Mail\SeminarRegistrationConfirmation;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use Illuminate\Support\Facades\Mail;

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
            ->assertJsonPath('message', 'Esta apresentação já foi realizada');
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
            ->assertJsonPath('message', 'Você já está inscrito nesta apresentação');
    });

    it('returns 404 for non-existent seminar', function () {
        actingAsUser();

        $response = $this->postJson('/api/seminars/non-existent/register');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Apresentação não encontrada');
    });

    it('returns 404 for inactive seminar', function () {
        actingAsUser();
        $seminar = Seminar::factory()->upcoming()->create(['active' => false]);

        $response = $this->postJson("/api/seminars/{$seminar->slug}/register");

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Apresentação não encontrada');
    });

    it('queues a registration confirmation email after a successful registration', function () {
        Mail::fake();

        $user = actingAsUser();
        $seminar = Seminar::factory()->upcoming()->create(['active' => true]);

        $this->postJson("/api/seminars/{$seminar->slug}/register")
            ->assertCreated();

        Mail::assertQueued(
            SeminarRegistrationConfirmation::class,
            fn ($mail) => $mail->hasTo($user->email) && $mail->seminar->is($seminar),
        );
    });

    it('allows overbooking when seminar capacity is exceeded', function () {
        $user = actingAsUser();
        $location = SeminarLocation::factory()->create(['max_vacancies' => 1]);
        $seminar = Seminar::factory()->upcoming()->create([
            'active' => true,
            'seminar_location_id' => $location->id,
        ]);

        Registration::factory()->create(['seminar_id' => $seminar->id]);

        $response = $this->postJson("/api/seminars/{$seminar->slug}/register");

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Inscrição realizada com sucesso');

        expect(Registration::where('user_id', $user->id)->where('seminar_id', $seminar->id)->exists())
            ->toBeTrue();
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
            ->assertJsonPath('message', 'Você não está inscrito nesta apresentação');
    });

    it('returns unregister_blocked for expired seminar', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->past()->create(['active' => true]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
        ]);

        $response = $this->deleteJson("/api/seminars/{$seminar->slug}/register");

        $response->assertStatus(400)
            ->assertJsonPath('error', 'unregister_blocked');
    });

    it('returns unregister_blocked on the day of the event', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'active' => true,
            'scheduled_at' => now()->endOfDay(),
        ]);

        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
        ]);

        $response = $this->deleteJson("/api/seminars/{$seminar->slug}/register");

        $response->assertStatus(400)
            ->assertJsonPath('error', 'unregister_blocked')
            ->assertJsonPath('message', 'Não é possível cancelar a inscrição no dia do evento');
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
