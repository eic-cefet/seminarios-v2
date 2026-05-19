<?php

use App\Models\PresenceLink;
use App\Models\Seminar;
use App\Models\User;

describe('GET /api/external/v1/seminars/{slug}/presence-link', function () {
    it('returns data null when no presence link exists', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:read']);
        $seminar = Seminar::factory()->create();

        $response = $this->withToken($token->plainTextToken)
            ->getJson("/api/external/v1/seminars/{$seminar->slug}/presence-link");

        $response->assertSuccessful()
            ->assertJsonPath('data', null);
    });

    it('returns the payload when a link exists, without qr_code by default', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:read']);
        $seminar = Seminar::factory()->create();
        $presenceLink = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => true,
            'expires_at' => now()->addHours(4),
        ]);

        $response = $this->withToken($token->plainTextToken)
            ->getJson("/api/external/v1/seminars/{$seminar->slug}/presence-link");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => ['id', 'uuid', 'active', 'expires_at', 'is_expired', 'is_valid', 'url', 'png_url'],
            ])
            ->assertJsonMissingPath('data.qr_code')
            ->assertJsonPath('data.id', $presenceLink->id)
            ->assertJsonPath('data.uuid', $presenceLink->uuid)
            ->assertJsonPath('data.active', true)
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.url', url("/p/{$presenceLink->uuid}"))
            ->assertJsonPath('data.png_url', url("/p/{$presenceLink->uuid}.png"));
    });

    it('includes qr_code base64 when include=qr_code is requested', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:read']);
        $seminar = Seminar::factory()->create();
        PresenceLink::factory()->create(['seminar_id' => $seminar->id, 'active' => true]);

        $response = $this->withToken($token->plainTextToken)
            ->getJson("/api/external/v1/seminars/{$seminar->slug}/presence-link?include=qr_code");

        $response->assertSuccessful()
            ->assertJsonStructure(['data' => ['qr_code']]);
        expect($response->json('data.qr_code'))->toStartWith('data:image/png;base64,');
    });

    it('returns 422 when include contains an unknown value', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:read']);
        $seminar = Seminar::factory()->create();
        PresenceLink::factory()->create(['seminar_id' => $seminar->id]);

        $this->withToken($token->plainTextToken)
            ->getJson("/api/external/v1/seminars/{$seminar->slug}/presence-link?include=qr_code,bogus")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['include']);
    });

    it('returns 401 for unauthenticated requests on GET', function () {
        $seminar = Seminar::factory()->create();

        $this->getJson("/api/external/v1/seminars/{$seminar->slug}/presence-link")
            ->assertUnauthorized();
    });

    it('returns 403 when the token lacks presence-link:read', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['seminars:read']);
        $seminar = Seminar::factory()->create();

        $this->withToken($token->plainTextToken)
            ->getJson("/api/external/v1/seminars/{$seminar->slug}/presence-link")
            ->assertForbidden();
    });

    it('returns 403 when a teacher tries to view another teachers seminar presence link', function () {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $token = $teacher->createToken('test', ['presence-link:read']);
        $seminar = Seminar::factory()->create(['created_by' => $otherTeacher->id]);
        PresenceLink::factory()->create(['seminar_id' => $seminar->id]);

        $this->withToken($token->plainTextToken)
            ->getJson("/api/external/v1/seminars/{$seminar->slug}/presence-link")
            ->assertForbidden();
    });

    it('returns 404 when the seminar slug does not exist', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:read']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/external/v1/seminars/does-not-exist/presence-link')
            ->assertNotFound();
    });

    it('returns 304 when If-Modified-Since matches the latest update', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:read']);
        $seminar = Seminar::factory()->create();
        PresenceLink::factory()->create(['seminar_id' => $seminar->id]);

        $first = $this->withToken($token->plainTextToken)
            ->getJson("/api/external/v1/seminars/{$seminar->slug}/presence-link");
        $first->assertSuccessful();
        $lastModified = $first->headers->get('Last-Modified');
        expect($lastModified)->not->toBeNull();

        $second = $this->withToken($token->plainTextToken)
            ->withHeaders(['If-Modified-Since' => $lastModified])
            ->getJson("/api/external/v1/seminars/{$seminar->slug}/presence-link");

        $second->assertStatus(304);
    });
});

describe('POST /api/external/v1/seminars/{slug}/presence-link', function () {
    it('creates an active presence link when none exists', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDay()]);

        $response = $this->withToken($token->plainTextToken)
            ->postJson("/api/external/v1/seminars/{$seminar->slug}/presence-link");

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'uuid', 'active', 'expires_at', 'is_valid', 'url', 'png_url'],
            ])
            ->assertJsonPath('data.active', true)
            ->assertJsonPath('data.is_valid', true);

        $link = $seminar->fresh()->presenceLink;
        expect($link)->not->toBeNull();
        expect($link->active)->toBeTrue();
        expect($link->expires_at)->not->toBeNull();
        expect($link->expires_at->greaterThan(now()))->toBeTrue();
    });

    it('uses now+1h as the expiry when scheduled_at+4h would be in the past', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->subDays(2)]);

        $this->withToken($token->plainTextToken)
            ->postJson("/api/external/v1/seminars/{$seminar->slug}/presence-link")
            ->assertCreated()
            ->assertJsonPath('data.active', true)
            ->assertJsonPath('data.is_valid', true);

        $link = $seminar->fresh()->presenceLink;
        expect($link->expires_at->greaterThan(now()))->toBeTrue();
        expect($link->expires_at->lessThanOrEqualTo(now()->addHours(2)))->toBeTrue();
    });

    it('returns the existing link with 200 when one already exists (idempotent)', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create();
        $existing = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => true,
        ]);

        $response = $this->withToken($token->plainTextToken)
            ->postJson("/api/external/v1/seminars/{$seminar->slug}/presence-link");

        $response->assertSuccessful()
            ->assertStatus(200)
            ->assertJsonPath('data.id', $existing->id)
            ->assertJsonPath('data.uuid', $existing->uuid)
            ->assertJsonPath('data.active', true);

        expect(PresenceLink::where('seminar_id', $seminar->id)->count())->toBe(1);
    });

    it('returns 401 for unauthenticated requests on POST', function () {
        $seminar = Seminar::factory()->create();
        $this->postJson("/api/external/v1/seminars/{$seminar->slug}/presence-link")
            ->assertUnauthorized();
    });

    it('returns 403 when the token has only presence-link:read', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:read']);
        $seminar = Seminar::factory()->create();

        $this->withToken($token->plainTextToken)
            ->postJson("/api/external/v1/seminars/{$seminar->slug}/presence-link")
            ->assertForbidden();
    });

    it('returns 403 when a teacher tries to create on another teachers seminar', function () {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $token = $teacher->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create(['created_by' => $otherTeacher->id]);

        $this->withToken($token->plainTextToken)
            ->postJson("/api/external/v1/seminars/{$seminar->slug}/presence-link")
            ->assertForbidden();
    });
});

describe('PATCH /api/external/v1/seminars/{slug}/presence-link', function () {
    it('activates the link and auto-computes expires_at when only active is sent', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDay()]);
        $link = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => false,
            'expires_at' => null,
        ]);

        $response = $this->withToken($token->plainTextToken)
            ->patchJson("/api/external/v1/seminars/{$seminar->slug}/presence-link", [
                'active' => true,
            ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $link->id)
            ->assertJsonPath('data.active', true);

        $fresh = $link->fresh();
        expect($fresh->active)->toBeTrue();
        expect($fresh->expires_at)->not->toBeNull();
        expect($fresh->expires_at->greaterThan(now()))->toBeTrue();
    });

    it('deactivates the link and clears expires_at when only active=false is sent', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create();
        $link = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => true,
            'expires_at' => now()->addHours(4),
        ]);

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/external/v1/seminars/{$seminar->slug}/presence-link", [
                'active' => false,
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.active', false);

        $fresh = $link->fresh();
        expect($fresh->active)->toBeFalse();
        expect($fresh->expires_at)->toBeNull();
    });

    it('uses an explicit expires_at over the auto-computed value', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->addDay()]);
        $link = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => false,
            'expires_at' => null,
        ]);
        $explicitExpiry = now()->addDays(7)->startOfHour();

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/external/v1/seminars/{$seminar->slug}/presence-link", [
                'active' => true,
                'expires_at' => $explicitExpiry->toIso8601String(),
            ])
            ->assertSuccessful();

        $fresh = $link->fresh();
        expect($fresh->active)->toBeTrue();
        expect($fresh->expires_at->equalTo($explicitExpiry))->toBeTrue();
    });

    it('updates only expires_at without touching active', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create();
        $link = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => true,
            'expires_at' => now()->addHours(4),
        ]);
        $newExpiry = now()->addDays(2)->startOfHour();

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/external/v1/seminars/{$seminar->slug}/presence-link", [
                'expires_at' => $newExpiry->toIso8601String(),
            ])
            ->assertSuccessful();

        $fresh = $link->fresh();
        expect($fresh->active)->toBeTrue();
        expect($fresh->expires_at->equalTo($newExpiry))->toBeTrue();
    });

    it('clears expires_at when an explicit null is sent', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create();
        $link = PresenceLink::factory()->create([
            'seminar_id' => $seminar->id,
            'active' => true,
            'expires_at' => now()->addHours(4),
        ]);

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/external/v1/seminars/{$seminar->slug}/presence-link", [
                'expires_at' => null,
            ])
            ->assertSuccessful();

        expect($link->fresh()->expires_at)->toBeNull();
    });

    it('accepts PUT in addition to PATCH', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create();
        PresenceLink::factory()->create(['seminar_id' => $seminar->id, 'active' => false]);

        $this->withToken($token->plainTextToken)
            ->putJson("/api/external/v1/seminars/{$seminar->slug}/presence-link", [
                'active' => true,
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.active', true);
    });

    it('returns 422 when the body is empty', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create();
        PresenceLink::factory()->create(['seminar_id' => $seminar->id]);

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/external/v1/seminars/{$seminar->slug}/presence-link", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    });

    it('returns 422 when active is not a boolean', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create();
        PresenceLink::factory()->create(['seminar_id' => $seminar->id]);

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/external/v1/seminars/{$seminar->slug}/presence-link", [
                'active' => 'maybe',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['active']);
    });

    it('returns 422 when expires_at is not a valid date', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create();
        PresenceLink::factory()->create(['seminar_id' => $seminar->id]);

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/external/v1/seminars/{$seminar->slug}/presence-link", [
                'expires_at' => 'not-a-date',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['expires_at']);
    });

    it('returns 404 when patching a seminar with no presence link', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create();

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/external/v1/seminars/{$seminar->slug}/presence-link", [
                'active' => true,
            ])
            ->assertNotFound();
    });

    it('returns 403 when the token lacks presence-link:write on PATCH', function () {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test', ['presence-link:read']);
        $seminar = Seminar::factory()->create();
        PresenceLink::factory()->create(['seminar_id' => $seminar->id]);

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/external/v1/seminars/{$seminar->slug}/presence-link", [
                'active' => true,
            ])
            ->assertForbidden();
    });

    it('returns 403 when a teacher tries to patch another teachers seminar', function () {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $token = $teacher->createToken('test', ['presence-link:write']);
        $seminar = Seminar::factory()->create(['created_by' => $otherTeacher->id]);
        PresenceLink::factory()->create(['seminar_id' => $seminar->id]);

        $this->withToken($token->plainTextToken)
            ->patchJson("/api/external/v1/seminars/{$seminar->slug}/presence-link", [
                'active' => true,
            ])
            ->assertForbidden();
    });
});
