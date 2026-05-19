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
