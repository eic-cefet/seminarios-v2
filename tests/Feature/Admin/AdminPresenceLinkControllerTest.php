<?php

use App\Models\PresenceLink;
use App\Models\Seminar;
use App\Models\User;

describe('GET /api/admin/seminars/{seminar}/presence-link', function () {
    it('returns null when no presence link exists for admin', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create();

        $response = $this->getJson("/api/admin/seminars/{$seminar->id}/presence-link");

        $response->assertSuccessful()
            ->assertJsonPath('data', null);
    });

    it('returns presence link data when exists for admin', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create();
        $presenceLink = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => true,
            'expires_at' => now()->addHours(4),
        ]);

        $response = $this->getJson("/api/admin/seminars/{$seminar->id}/presence-link");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'active',
                    'expires_at',
                    'is_expired',
                    'is_valid',
                    'url',
                    'png_url',
                    'qr_code',
                ],
            ])
            ->assertJsonPath('data.id', $presenceLink->id)
            ->assertJsonPath('data.active', true);
    });

    it('returns 401 for unauthenticated user', function () {
        $seminar = Seminar::factory()->create();

        $response = $this->getJson("/api/admin/seminars/{$seminar->id}/presence-link");

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $seminar = Seminar::factory()->create();

        $response = $this->getJson("/api/admin/seminars/{$seminar->id}/presence-link");

        $response->assertForbidden();
    });

    it('allows teacher to view presence link', function () {
        $teacher = actingAsTeacher();

        $seminar = Seminar::factory()->create(['created_by' => $teacher->id]);

        $response = $this->getJson("/api/admin/seminars/{$seminar->id}/presence-link");

        $response->assertSuccessful();
    });
});

describe('POST /api/admin/seminars/{seminar}/presence-link', function () {
    it('creates presence link for admin', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->upcoming()->create();

        $response = $this->postJson("/api/admin/seminars/{$seminar->id}/presence-link");

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Presence link created successfully')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'active',
                    'expires_at',
                    'is_expired',
                    'is_valid',
                    'url',
                    'png_url',
                    'qr_code',
                ],
            ]);

        expect(PresenceLink::where('seminar_id', $seminar->id)->exists())->toBeTrue();
    });

    it('creates presence link with default inactive state', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->upcoming()->create();

        $response = $this->postJson("/api/admin/seminars/{$seminar->id}/presence-link");

        $response->assertStatus(201)
            ->assertJsonPath('data.active', false);
    });

    it('sets expiration to 4 hours after scheduled_at', function () {
        actingAsAdmin();

        $scheduledAt = now()->addDays(7);
        $seminar = Seminar::factory()->create(['scheduled_at' => $scheduledAt]);

        $response = $this->postJson("/api/admin/seminars/{$seminar->id}/presence-link");

        $response->assertStatus(201);

        $presenceLink = PresenceLink::where('seminar_id', $seminar->id)->first();
        expect($presenceLink->expires_at->format('Y-m-d H:i'))
            ->toBe($scheduledAt->addHours(4)->format('Y-m-d H:i'));
    });

    it('returns 409 when presence link already exists', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create();
        PresenceLink::factory()->create(['seminar_id' => $seminar->id]);

        $response = $this->postJson("/api/admin/seminars/{$seminar->id}/presence-link");

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Presence link already exists for this seminar');
    });

    it('returns 401 for unauthenticated user', function () {
        $seminar = Seminar::factory()->create();

        $response = $this->postJson("/api/admin/seminars/{$seminar->id}/presence-link");

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $seminar = Seminar::factory()->create();

        $response = $this->postJson("/api/admin/seminars/{$seminar->id}/presence-link");

        $response->assertForbidden();
    });

    it('teacher can create presence link for their seminar', function () {
        $teacher = actingAsTeacher();

        $seminar = Seminar::factory()->upcoming()->create(['created_by' => $teacher->id]);

        $response = $this->postJson("/api/admin/seminars/{$seminar->id}/presence-link");

        $response->assertStatus(201);
    });

    it('teacher cannot create presence link for other seminars', function () {
        $teacher = actingAsTeacher();

        $otherUser = User::factory()->create();
        $seminar = Seminar::factory()->create(['created_by' => $otherUser->id]);

        $response = $this->postJson("/api/admin/seminars/{$seminar->id}/presence-link");

        $response->assertForbidden();
    });
});

describe('PATCH /api/admin/seminars/{seminar}/presence-link/toggle', function () {
    it('toggles presence link active state', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->upcoming()->create();
        $presenceLink = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => false,
        ]);

        $response = $this->patchJson("/api/admin/seminars/{$seminar->id}/presence-link/toggle");

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Presence link status updated successfully')
            ->assertJsonPath('data.active', true);

        $presenceLink->refresh();
        expect($presenceLink->active)->toBeTrue();
    });

    it('toggles presence link from active to inactive', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->upcoming()->create();
        $presenceLink = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => true,
        ]);

        $response = $this->patchJson("/api/admin/seminars/{$seminar->id}/presence-link/toggle");

        $response->assertSuccessful()
            ->assertJsonPath('data.active', false);

        $presenceLink->refresh();
        expect($presenceLink->active)->toBeFalse();
    });

    it('updates expiration when activating', function () {
        actingAsAdmin();

        $scheduledAt = now()->addDays(7);
        $seminar = Seminar::factory()->create(['scheduled_at' => $scheduledAt]);
        $presenceLink = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => false,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->patchJson("/api/admin/seminars/{$seminar->id}/presence-link/toggle");

        $response->assertSuccessful();

        $presenceLink->refresh();
        expect($presenceLink->expires_at->gt(now()))->toBeTrue();
    });

    it('returns 404 when presence link does not exist', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create();

        $response = $this->patchJson("/api/admin/seminars/{$seminar->id}/presence-link/toggle");

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Presence link not found');
    });

    it('returns 401 for unauthenticated user', function () {
        $seminar = Seminar::factory()->create();

        $response = $this->patchJson("/api/admin/seminars/{$seminar->id}/presence-link/toggle");

        $response->assertUnauthorized();
    });

    it('returns 403 for non-admin user', function () {
        actingAsUser();

        $seminar = Seminar::factory()->create();

        $response = $this->patchJson("/api/admin/seminars/{$seminar->id}/presence-link/toggle");

        $response->assertForbidden();
    });

    it('teacher can toggle presence link for their seminar', function () {
        $teacher = actingAsTeacher();

        $seminar = Seminar::factory()->upcoming()->create(['created_by' => $teacher->id]);
        PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => false,
        ]);

        $response = $this->patchJson("/api/admin/seminars/{$seminar->id}/presence-link/toggle");

        $response->assertSuccessful();
    });

    it('uses minimum expiry when seminar scheduled time is in the past', function () {
        actingAsAdmin();

        // Seminar scheduled in the past (so scheduled_at + 4 hours is still before now + 1 hour)
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subDays(2),
        ]);
        $presenceLink = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => false,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->patchJson("/api/admin/seminars/{$seminar->id}/presence-link/toggle");

        $response->assertSuccessful();

        $presenceLink->refresh();
        // Expiry should be approximately now + 1 hour (minimum expiry)
        expect($presenceLink->expires_at->gte(now()->addMinutes(59)))->toBeTrue();
        expect($presenceLink->expires_at->lte(now()->addMinutes(61)))->toBeTrue();
    });

});
