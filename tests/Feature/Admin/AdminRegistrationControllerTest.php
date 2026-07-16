<?php

use App\Enums\AuditEvent;
use App\Mail\SeminarRegistrationConfirmation;
use App\Models\AuditLog;
use App\Models\Registration;
use App\Models\Seminar;
use App\Models\User;
use App\Notifications\BadgesUnlockedNotification;
use App\Services\GamificationService;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

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

    it('reconciles progress when presence is confirmed', function () {
        actingAsAdmin();

        $user = User::factory()->create();
        $registration = Registration::factory()->for($user)->create(['present' => false]);

        $this->patchJson("/api/admin/registrations/{$registration->id}/presence")
            ->assertSuccessful()
            ->assertJsonMissingPath('gamification');

        expect($user->experienceEvents()->where('source_key', "attendance:{$registration->id}")->value('points'))
            ->toBe(100)
            ->and($user->badges()->where('badge_key', 'first_presence')->exists())->toBeTrue();
    });

    it('revokes progress without notifying when presence is removed', function () {
        actingAsAdmin();

        $user = User::factory()->create();
        $registration = Registration::factory()->present()->for($user)->create();
        app(GamificationService::class)->sync($user, notify: false);
        Notification::fake();

        $this->patchJson("/api/admin/registrations/{$registration->id}/presence")
            ->assertSuccessful()
            ->assertJsonPath('data.present', false)
            ->assertJsonMissingPath('gamification');

        expect($user->experienceEvents()->count())->toBe(0)
            ->and($user->badges()->count())->toBe(0);
        Notification::assertNothingSent();
    });

    it('keeps a presence correction when gamification reconciliation fails', function () {
        Exceptions::fake();
        actingAsAdmin();

        $exception = new RuntimeException('gamification unavailable');
        $gamification = Mockery::mock(GamificationService::class);
        $gamification->shouldReceive('sync')->once()->andThrow($exception);
        app()->instance(GamificationService::class, $gamification);

        $registration = Registration::factory()->create(['present' => false]);

        $this->patchJson("/api/admin/registrations/{$registration->id}/presence")
            ->assertSuccessful()
            ->assertJsonPath('data.present', true)
            ->assertJsonMissingPath('gamification');

        expect($registration->fresh()->present)->toBeTrue();
        Exceptions::assertReported(fn (RuntimeException $reported): bool => $reported === $exception);
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

    it('reconciles only users whose authoritative presence changed in a mixed batch', function () {
        Mail::fake();
        Notification::fake();
        actingAsAdmin();
        $seminar = Seminar::factory()->create();
        $newUser = User::factory()->create();
        $restoredUser = User::factory()->create();
        $alreadyPresentUser = User::factory()->create();
        Registration::factory()->for($restoredUser)->for($seminar)->create(['present' => false]);
        Registration::factory()->present()->for($alreadyPresentUser)->for($seminar)->create();

        $this->postJson('/api/admin/registrations', [
            'seminar_id' => $seminar->id,
            'user_ids' => [$newUser->id, $restoredUser->id, $alreadyPresentUser->id],
        ])->assertCreated()
            ->assertJson([
                'data' => ['created' => 1, 'already_registered' => 2, 'marked_present' => 1],
            ]);

        expect($newUser->experienceEvents()->where('reason', 'attendance')->exists())->toBeTrue()
            ->and($restoredUser->experienceEvents()->where('reason', 'attendance')->exists())->toBeTrue()
            ->and($alreadyPresentUser->experienceEvents()->exists())->toBeFalse();
        Notification::assertSentTo($newUser, BadgesUnlockedNotification::class);
        Notification::assertSentTo($restoredUser, BadgesUnlockedNotification::class);
        Notification::assertNotSentTo($alreadyPresentUser, BadgesUnlockedNotification::class);
    });

    it('keeps the batch and continues reconciliation after one user sync fails', function () {
        Exceptions::fake();
        Mail::fake();
        actingAsAdmin();
        $seminar = Seminar::factory()->create();
        $failedUser = User::factory()->create();
        $reconciledUser = User::factory()->create();
        $exception = new RuntimeException('gamification unavailable');
        $realGamification = app(GamificationService::class);
        $gamification = Mockery::mock(GamificationService::class);
        $gamification->shouldReceive('sync')
            ->twice()
            ->with(Mockery::type(User::class), true)
            ->andReturnUsing(function (User $user, bool $notify) use ($exception, $failedUser, $realGamification) {
                if ($user->is($failedUser)) {
                    throw $exception;
                }

                return $realGamification->sync($user, $notify);
            });
        app()->instance(GamificationService::class, $gamification);

        $this->postJson('/api/admin/registrations', [
            'seminar_id' => $seminar->id,
            'user_ids' => [$failedUser->id, $reconciledUser->id],
        ])->assertCreated()
            ->assertJson([
                'data' => ['created' => 2, 'already_registered' => 0, 'marked_present' => 0],
            ]);

        expect(Registration::query()
            ->where('seminar_id', $seminar->id)
            ->whereIn('user_id', [$failedUser->id, $reconciledUser->id])
            ->where('present', true)
            ->count())->toBe(2)
            ->and($reconciledUser->experienceEvents()->where('reason', 'attendance')->exists())->toBeTrue();
        Exceptions::assertReported(fn (RuntimeException $reported): bool => $reported === $exception);
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

        $log = AuditLog::where('event_name', AuditEvent::RegistrationsAddedByAdmin->value)->firstOrFail();
        expect($log->auditable_id)->toBe($seminar->id)
            ->and($log->event_data['created'])->toBe(1)
            ->and($log->event_data['already_registered'])->toBe(0)
            ->and($log->event_data['marked_present'])->toBe(0);
    });

    it('rejects a soft-deleted user id', function () {
        actingAsAdmin();
        $seminar = Seminar::factory()->create();
        $user = User::factory()->create();
        $user->delete();

        $this->postJson('/api/admin/registrations', [
            'seminar_id' => $seminar->id,
            'user_ids' => [$user->id],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['user_ids.0']);

        $this->assertDatabaseMissing('registrations', [
            'seminar_id' => $seminar->id,
            'user_id' => $user->id,
        ]);
    });

    it('rejects a soft-deleted seminar id', function () {
        actingAsAdmin();
        $seminar = Seminar::factory()->create();
        $seminar->delete();
        $user = User::factory()->create();

        $this->postJson('/api/admin/registrations', [
            'seminar_id' => $seminar->id,
            'user_ids' => [$user->id],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['seminar_id']);
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
