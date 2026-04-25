<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
});

function seedNotification(User $user, ?string $readAt = null): string
{
    $id = (string) Str::uuid();
    DB::table('notifications')->insert([
        'id' => $id,
        'type' => 'App\\Notifications\\CertificateReadyNotification',
        'notifiable_type' => $user::class,
        'notifiable_id' => $user->id,
        'data' => json_encode(['category' => 'certificate_ready', 'title' => 'T', 'body' => 'B', 'action_url' => '/x']),
        'read_at' => $readAt,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

it('lists paginated notifications newest-first', function () {
    seedNotification($this->user);
    seedNotification($this->user, readAt: now());

    $this->getJson('/api/notifications')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'T')
        ->assertJsonPath('data.0.category', 'certificate_ready');
});

it('returns unread count', function () {
    seedNotification($this->user);
    seedNotification($this->user);
    seedNotification($this->user, readAt: now());

    $this->getJson('/api/notifications/unread-count')
        ->assertSuccessful()
        ->assertJsonPath('count', 2);
});

it('marks a notification as read', function () {
    $id = seedNotification($this->user);

    $this->postJson("/api/notifications/{$id}/read")->assertSuccessful();

    expect(DB::table('notifications')->where('id', $id)->value('read_at'))->not->toBeNull();
});

it('marks all as read', function () {
    seedNotification($this->user);
    seedNotification($this->user);

    $this->postJson('/api/notifications/read-all')->assertSuccessful();

    expect(DB::table('notifications')->where('notifiable_id', $this->user->id)->whereNull('read_at')->count())->toBe(0);
});

it('does not expose another user notifications', function () {
    $other = User::factory()->create();
    $id = seedNotification($other);

    $this->postJson("/api/notifications/{$id}/read")->assertNotFound();
});

it('requires auth', function () {
    $this->app['auth']->forgetGuards();

    $this->getJson('/api/notifications')->assertUnauthorized();
});
