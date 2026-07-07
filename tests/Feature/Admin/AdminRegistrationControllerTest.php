<?php

use App\Enums\AuditEvent;
use App\Mail\SeminarRegistrationConfirmation;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

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
        $response->assertJsonMissing(['id' => $otherRegistration->id]);
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

describe('POST /api/admin/registrations', function () {
    it('returns 401 for unauthenticated request', function () {
        $this->postJson('/api/admin/registrations', [])->assertUnauthorized();
    });

    it('returns 403 for regular user', function () {
        actingAsUser();
        $seminar = Seminar::factory()->create();
        $user = User::factory()->create();

        $this->postJson('/api/admin/registrations', [
            'seminar_id' => $seminar->id,
            'user_ids' => [$user->id],
        ])->assertForbidden();
    });

    it('returns 403 for teacher on another teacher\'s seminar', function () {
        actingAsTeacher();
        $otherTeacher = User::factory()->teacher()->create();
        $seminar = Seminar::factory()->create(['created_by' => $otherTeacher->id]);
        $user = User::factory()->create();

        $this->postJson('/api/admin/registrations', [
            'seminar_id' => $seminar->id,
            'user_ids' => [$user->id],
        ])->assertForbidden();
    });

    it('creates present registrations and queues confirmation for admin', function () {
        Mail::fake();
        actingAsAdmin();
        $seminar = Seminar::factory()->create();
        $users = User::factory()->count(2)->create();

        $response = $this->postJson('/api/admin/registrations', [
            'seminar_id' => $seminar->id,
            'user_ids' => $users->pluck('id')->all(),
        ]);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Inscrições adicionadas com sucesso',
                'data' => ['created' => 2, 'already_registered' => 0, 'marked_present' => 0],
            ]);

        foreach ($users as $user) {
            $this->assertDatabaseHas('registrations', [
                'seminar_id' => $seminar->id,
                'user_id' => $user->id,
                'present' => true,
            ]);
        }

        Mail::assertQueued(SeminarRegistrationConfirmation::class, 2);
    });

    it('allows teacher to add registrations to own seminar', function () {
        Mail::fake();
        $teacher = actingAsTeacher();
        $seminar = Seminar::factory()->create(['created_by' => $teacher->id]);
        $user = User::factory()->create();

        $this->postJson('/api/admin/registrations', [
            'seminar_id' => $seminar->id,
            'user_ids' => [$user->id],
        ])->assertCreated();
    });

    it('works for past seminars (walk-in use case)', function () {
        Mail::fake();
        actingAsAdmin();
        $seminar = Seminar::factory()->past()->create();
        $user = User::factory()->create();

        $this->postJson('/api/admin/registrations', [
            'seminar_id' => $seminar->id,
            'user_ids' => [$user->id],
        ])->assertCreated();
    });

    it('is idempotent for already-present registrations and sends no mail', function () {
        Mail::fake();
        actingAsAdmin();
        $seminar = Seminar::factory()->create();
        $user = User::factory()->create();
        Registration::factory()->present()->create([
            'seminar_id' => $seminar->id,
            'user_id' => $user->id,
        ]);

        $response = $this->postJson('/api/admin/registrations', [
            'seminar_id' => $seminar->id,
            'user_ids' => [$user->id],
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => ['created' => 0, 'already_registered' => 1, 'marked_present' => 0],
            ]);
        expect(Registration::where('seminar_id', $seminar->id)->count())->toBe(1);
        Mail::assertNothingQueued();
    });

    it('flips an absent existing registration to present without mail', function () {
        Mail::fake();
        actingAsAdmin();
        $seminar = Seminar::factory()->create();
        $user = User::factory()->create();
        $registration = Registration::factory()->create([
            'seminar_id' => $seminar->id,
            'user_id' => $user->id,
            'present' => false,
        ]);

        $response = $this->postJson('/api/admin/registrations', [
            'seminar_id' => $seminar->id,
            'user_ids' => [$user->id],
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => ['created' => 0, 'already_registered' => 1, 'marked_present' => 1],
            ]);
        expect($registration->refresh()->present)->toBeTrue();
        Mail::assertNothingQueued();
    });

    it('handles a mixed batch of new and existing users', function () {
        Mail::fake();
        actingAsAdmin();
        $seminar = Seminar::factory()->create();
        $existing = User::factory()->create();
        $new = User::factory()->create();
        Registration::factory()->present()->create([
            'seminar_id' => $seminar->id,
            'user_id' => $existing->id,
        ]);

        $response = $this->postJson('/api/admin/registrations', [
            'seminar_id' => $seminar->id,
            'user_ids' => [$existing->id, $new->id],
        ]);

        $response->assertCreated()
            ->assertJson([
                'data' => ['created' => 1, 'already_registered' => 1, 'marked_present' => 0],
            ]);
        Mail::assertQueued(SeminarRegistrationConfirmation::class, 1);
    });

    it('records the admin audit event', function () {
        Mail::fake();
        actingAsAdmin();
        $seminar = Seminar::factory()->create();
        $user = User::factory()->create();

        $this->postJson('/api/admin/registrations', [
            'seminar_id' => $seminar->id,
            'user_ids' => [$user->id],
        ])->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'event_name' => AuditEvent::RegistrationsAddedByAdmin->value,
        ]);
    });

    it('validates the payload', function (array $payload, string $errorField) {
        actingAsAdmin();

        $this->postJson('/api/admin/registrations', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$errorField]);
    })->with([
        'missing seminar' => [['user_ids' => [1]], 'seminar_id'],
        'nonexistent seminar' => [['seminar_id' => 999999, 'user_ids' => [1]], 'seminar_id'],
        'missing users' => [fn () => ['seminar_id' => Seminar::factory()->create()->id], 'user_ids'],
        'empty users' => [fn () => ['seminar_id' => Seminar::factory()->create()->id, 'user_ids' => []], 'user_ids'],
        'nonexistent user' => [fn () => ['seminar_id' => Seminar::factory()->create()->id, 'user_ids' => [999999]], 'user_ids.0'],
        'duplicate users' => [function () {
            $seminar = Seminar::factory()->create();
            $user = User::factory()->create();

            return ['seminar_id' => $seminar->id, 'user_ids' => [$user->id, $user->id]];
        }, 'user_ids.0'],
    ]);
});
