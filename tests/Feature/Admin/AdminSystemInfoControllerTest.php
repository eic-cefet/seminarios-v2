<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => Cache::forget('admin:system:info'));

describe('GET /admin/system/info', function () {
    it('returns the system info payload for admin users', function () {
        actingAsAdmin();

        $response = $this->getJson('/api/admin/system/info');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'runtime' => ['php_version', 'laravel_version', 'app_version', 'environment', 'debug', 'timezone', 'locale'],
                    'server' => ['os_family', 'os_release', 'hostname', 'server_software', 'sapi', 'architecture'],
                    'memory' => ['limit_bytes', 'current_bytes', 'peak_bytes'],
                    'database' => ['driver', 'database', 'host', 'version'],
                    'drivers' => ['cache', 'queue', 'session', 'mail', 'filesystem'],
                    'storage' => ['free_bytes', 'total_bytes'],
                    'extensions',
                    'php_config' => ['max_execution_time', 'post_max_size', 'upload_max_filesize', 'opcache_enabled'],
                    'scheduler',
                ],
            ]);

        $first = $response->json('data.scheduler.0');
        expect($first['command'])->toBeString();
        expect($first['expression'])->toBeString();
    });

    it('caches the payload between requests', function () {
        actingAsAdmin();

        $this->getJson('/api/admin/system/info')->assertSuccessful();

        expect(Cache::has('admin:system:info'))->toBeTrue();
    });

    it('returns 401 for unauthenticated users', function () {
        $this->getJson('/api/admin/system/info')->assertUnauthorized();
    });

    it('returns 403 for regular users', function () {
        actingAsUser();

        $this->getJson('/api/admin/system/info')->assertForbidden();
    });

    it('returns 403 for teacher users (admin-only endpoint)', function () {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->getJson('/api/admin/system/info')
            ->assertForbidden();
    });
});
