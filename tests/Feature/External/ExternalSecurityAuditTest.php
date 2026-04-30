<?php

use App\Models\AuditLog;
use App\Models\SeminarLocation;
use App\Models\SeminarType;
use App\Models\User;
use App\Models\Workshop;
use Laravel\Sanctum\PersonalAccessToken;

describe('Baseline security: admin gate on every external endpoint', function () {
    it('blocks a plain-user token (no admin role) from every external read endpoint', function (string $method, string $url) {
        $user = User::factory()->create();
        $token = $user->createToken('audit-non-admin', ['*'])->plainTextToken;

        $response = $this->withToken($token)->json($method, $url);

        expect($response->status())->toBeIn([401, 403]);
    })->with([
        'seminars index' => ['GET', '/api/external/v1/seminars'],
        'workshops index' => ['GET', '/api/external/v1/workshops'],
        'locations index' => ['GET', '/api/external/v1/locations'],
        'users index' => ['GET', '/api/external/v1/users'],
        'seminar-types index' => ['GET', '/api/external/v1/seminar-types'],
    ]);

    it('rejects unauthenticated requests to every external read endpoint', function (string $method, string $url) {
        $response = $this->json($method, $url);

        expect($response->status())->toBe(401);
    })->with([
        'seminars index' => ['GET', '/api/external/v1/seminars'],
        'workshops index' => ['GET', '/api/external/v1/workshops'],
        'locations index' => ['GET', '/api/external/v1/locations'],
        'users index' => ['GET', '/api/external/v1/users'],
        'seminar-types index' => ['GET', '/api/external/v1/seminar-types'],
    ]);
});

describe('Audit-log coverage of external mutations', function () {
    it('writes an AuditLog row when a Location is created via external API', function () {
        actingAsAdmin();
        $before = AuditLog::count();

        $this->postJson('/api/external/v1/locations', [
            'name' => 'Audit Test Location',
            'max_vacancies' => 5,
        ])->assertCreated();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });

    it('writes an AuditLog row when a SeminarType is created via external API', function () {
        actingAsAdmin();
        $before = AuditLog::count();

        $this->postJson('/api/external/v1/seminar-types', [
            'name' => 'Audit Test Type',
        ])->assertCreated();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });

    it('writes an AuditLog row when a User is created via external API', function () {
        actingAsAdmin();
        $before = AuditLog::count();

        $this->postJson('/api/external/v1/users', [
            'name' => 'Audit User',
            'email' => 'audit-user@example.com',
        ])->assertCreated();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });

    it('writes an AuditLog row when a Workshop is created via external API', function () {
        actingAsAdmin();
        $before = AuditLog::count();

        $this->postJson('/api/external/v1/workshops', [
            'name' => 'Audit Workshop',
            'date' => '2030-01-01',
        ])->assertCreated();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });

    it('writes an AuditLog row when a Location is updated via external API', function () {
        actingAsAdmin();
        $location = SeminarLocation::factory()->create();
        $before = AuditLog::count();

        $this->putJson("/api/external/v1/locations/{$location->id}", [
            'name' => 'Renamed Location',
        ])->assertSuccessful();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });

    it('writes an AuditLog row when a SeminarType is updated via external API', function () {
        actingAsAdmin();
        $type = SeminarType::factory()->create();
        $before = AuditLog::count();

        $this->putJson("/api/external/v1/seminar-types/{$type->id}", [
            'name' => 'Renamed Type',
        ])->assertSuccessful();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });

    it('writes an AuditLog row when a Workshop is updated via external API', function () {
        actingAsAdmin();
        $workshop = Workshop::factory()->create();
        $before = AuditLog::count();

        $this->putJson("/api/external/v1/workshops/{$workshop->slug}", [
            'name' => 'Renamed Workshop',
        ])->assertSuccessful();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });

    it('writes an AuditLog row when speaker-data is updated via external API', function () {
        actingAsAdmin();
        $user = User::factory()->create();
        $before = AuditLog::count();

        $this->putJson("/api/external/v1/users/{$user->id}/speaker-data", [
            'institution' => 'CEFET-RJ',
            'description' => 'Test bio',
        ])->assertSuccessful();

        expect(AuditLog::count())->toBeGreaterThan($before);
    });
});

describe('Token metadata tracking baseline', function () {
    it('updates Sanctum last_used_at when a token is used against the external API', function () {
        $admin = User::factory()->admin()->create();
        $accessToken = $admin->createToken('audit-last-used', ['*']);
        $tokenId = $accessToken->accessToken->id;

        expect($accessToken->accessToken->last_used_at)->toBeNull();

        $this->withToken($accessToken->plainTextToken)
            ->getJson('/api/external/v1/locations')
            ->assertSuccessful();

        $refreshed = PersonalAccessToken::query()->find($tokenId);
        expect($refreshed->last_used_at)->not->toBeNull();
    });

    it('does not store last_used_ip or last_used_user_agent on Sanctum tokens (documents current gap)', function () {
        $admin = User::factory()->admin()->create();
        $accessToken = $admin->createToken('audit-no-meta', ['*']);

        $this->withToken($accessToken->plainTextToken)
            ->withHeaders(['User-Agent' => 'AuditAgent/1.0'])
            ->getJson('/api/external/v1/locations')
            ->assertSuccessful();

        $refreshed = PersonalAccessToken::query()->find($accessToken->accessToken->id);
        $attributes = $refreshed->getAttributes();

        expect($attributes)->not->toHaveKey('last_used_ip')
            ->and($attributes)->not->toHaveKey('last_used_user_agent');
    });
});
