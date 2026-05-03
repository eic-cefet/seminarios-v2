<?php

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\MfaTrustedDevice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

use function Pest\Laravel\postJson;

function seedSessionRow(string $id, ?int $userId): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $userId,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'agent',
        'payload' => '',
        'last_activity' => now()->timestamp,
    ]);
}

it('deletes other database sessions belonging to the user when password is reset', function () {
    $user = User::factory()->create();
    seedSessionRow('session-other-1', $user->id);
    seedSessionRow('session-other-2', $user->id);

    $token = Password::createToken($user);

    postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertSuccessful();

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});

it('deletes all trusted devices belonging to the user when password is reset', function () {
    $user = User::factory()->create();
    MfaTrustedDevice::factory()->for($user)->count(3)->create();

    $token = Password::createToken($user);

    postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertSuccessful();

    expect(MfaTrustedDevice::where('user_id', $user->id)->count())->toBe(0);
});

it('does not touch sessions or trusted devices belonging to other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    seedSessionRow('mine', $user->id);
    seedSessionRow('theirs', $otherUser->id);
    MfaTrustedDevice::factory()->for($user)->create();
    $otherDevice = MfaTrustedDevice::factory()->for($otherUser)->create();

    $token = Password::createToken($user);

    postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertSuccessful();

    expect(DB::table('sessions')->where('id', 'theirs')->exists())->toBeTrue();
    expect(MfaTrustedDevice::where('id', $otherDevice->id)->exists())->toBeTrue();
    expect(MfaTrustedDevice::where('user_id', $user->id)->count())->toBe(0);
});

it('records an audit event with the deleted-row counts in metadata', function () {
    $user = User::factory()->create();
    seedSessionRow('session-other-1', $user->id);
    seedSessionRow('session-other-2', $user->id);
    MfaTrustedDevice::factory()->for($user)->count(2)->create();

    $token = Password::createToken($user);

    postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertSuccessful();

    $log = AuditLog::where('event_name', AuditEvent::UserPasswordResetSessionsCleared->value)->first();

    expect($log)->not->toBeNull()
        ->and($log->auditable_id)->toBe($user->id)
        ->and($log->auditable_type)->toBe($user->getMorphClass())
        ->and($log->event_data)->toBe([
            'sessions_deleted' => 2,
            'trusted_devices_deleted' => 2,
        ]);
});
