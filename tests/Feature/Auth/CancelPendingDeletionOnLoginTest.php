<?php

use App\Enums\AuditEvent;
use App\Mail\AccountDeletionCancelled;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

it('cancels pending deletion, forgets the lgpd cache, audits and notifies on login', function () {
    Mail::fake();

    $user = User::factory()->create([
        'anonymization_requested_at' => now(),
        'anonymized_at' => null,
    ]);
    Cache::put("lgpd.deletion-pending:{$user->id}", true, now()->addDay());

    event(new Login('web', $user, false));

    expect($user->fresh()->anonymization_requested_at)->toBeNull()
        ->and(Cache::has("lgpd.deletion-pending:{$user->id}"))->toBeFalse()
        ->and(AuditLog::query()->where('event_name', AuditEvent::AccountDeletionCancelled->value)->exists())->toBeTrue();

    Mail::assertQueued(AccountDeletionCancelled::class, fn ($m) => $m->hasTo($user->email));
});

it('is a no-op for users with no pending deletion', function () {
    Mail::fake();

    $user = User::factory()->create(['anonymization_requested_at' => null]);

    event(new Login('web', $user, false));

    Mail::assertNothingQueued();
});
