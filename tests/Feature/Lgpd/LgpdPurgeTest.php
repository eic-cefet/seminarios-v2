<?php

use App\Console\Commands\LgpdPurgeCommand;
use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\PresenceLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

use function Pest\Laravel\artisan;

it('purges sessions idle beyond retention', function () {
    config()->set('lgpd.retention.sessions_days', 30);

    DB::table('sessions')->insert([
        'id' => 'old-session',
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'x',
        'payload' => '',
        'last_activity' => now()->subDays(45)->timestamp,
    ]);
    DB::table('sessions')->insert([
        'id' => 'fresh-session',
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'x',
        'payload' => '',
        'last_activity' => now()->subDays(10)->timestamp,
    ]);

    artisan(LgpdPurgeCommand::class)->assertExitCode(0);

    expect(DB::table('sessions')->where('id', 'old-session')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'fresh-session')->exists())->toBeTrue();
});

it('purges unused personal access tokens past retention', function () {
    config()->set('lgpd.retention.personal_access_tokens_days', 180);

    $user = User::factory()->create();
    $stale = $user->createToken('stale');
    $stale->accessToken->forceFill([
        'last_used_at' => now()->subDays(200),
        'created_at' => now()->subDays(200),
    ])->save();

    $fresh = $user->createToken('fresh');
    $fresh->accessToken->forceFill(['last_used_at' => now()])->save();

    artisan(LgpdPurgeCommand::class);

    expect(PersonalAccessToken::find($stale->accessToken->id))->toBeNull()
        ->and(PersonalAccessToken::find($fresh->accessToken->id))->not->toBeNull();
});

it('purges expired presence links past retention', function () {
    config()->set('lgpd.retention.presence_links_days', 30);

    $old = PresenceLink::factory()->create([
        'expires_at' => now()->subDays(45),
    ]);
    $recent = PresenceLink::factory()->create([
        'expires_at' => now()->subDays(5),
    ]);

    artisan(LgpdPurgeCommand::class);

    expect(PresenceLink::find($old->id))->toBeNull()
        ->and(PresenceLink::find($recent->id))->not->toBeNull();
});

it('logs one retention-purged audit row per run', function () {
    artisan(LgpdPurgeCommand::class);

    expect(AuditLog::where('event_name', AuditEvent::LgpdRetentionPurged->value)->count())->toBe(1);
});

it('supports --dry-run without deleting anything', function () {
    config()->set('lgpd.retention.sessions_days', 30);

    DB::table('sessions')->insert([
        'id' => 'old-session-dry',
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'x',
        'payload' => '',
        'last_activity' => now()->subDays(45)->timestamp,
    ]);

    artisan(LgpdPurgeCommand::class, ['--dry-run' => true])->assertExitCode(0);

    expect(DB::table('sessions')->where('id', 'old-session-dry')->exists())->toBeTrue()
        ->and(AuditLog::where('event_name', AuditEvent::LgpdRetentionPurged->value)->exists())->toBeFalse();
});
