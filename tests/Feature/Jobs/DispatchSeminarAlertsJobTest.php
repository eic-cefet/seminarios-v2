<?php

use App\Enums\AuditEvent;
use App\Jobs\DispatchSeminarAlertsJob;
use App\Mail\NewSeminarAlert;
use App\Models\AlertPreference;
use App\Models\AuditLog;
use App\Models\Seminar;
use App\Models\SeminarAlertDispatch;
use App\Models\SeminarType;
use App\Models\User;
use App\Services\SeminarAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('queues mail to each matching user and records dispatch rows', function () {
    Mail::fake();

    $type = SeminarType::factory()->create();

    $allow = User::factory()->create();
    AlertPreference::factory()->for($allow)->create();

    $byType = User::factory()->create();
    AlertPreference::factory()->for($byType)->forTypes([$type->id])->create();

    $nonMatching = User::factory()->create();
    $otherType = SeminarType::factory()->create();
    AlertPreference::factory()->for($nonMatching)->forTypes([$otherType->id])->create();

    $seminar = Seminar::factory()->create([
        'active' => true,
        'seminar_type_id' => $type->id,
    ]);

    (new DispatchSeminarAlertsJob($seminar))->handle(app(SeminarAlertService::class));

    Mail::assertQueued(NewSeminarAlert::class, 2);
    Mail::assertQueued(NewSeminarAlert::class, fn ($m) => $m->hasTo($allow->email));
    Mail::assertQueued(NewSeminarAlert::class, fn ($m) => $m->hasTo($byType->email));

    expect(SeminarAlertDispatch::count())->toBe(2);
    expect(SeminarAlertDispatch::where('user_id', $nonMatching->id)->exists())->toBeFalse();
});

it('is idempotent — running twice sends each user only once', function () {
    Mail::fake();
    $user = User::factory()->create();
    AlertPreference::factory()->for($user)->create();
    $seminar = Seminar::factory()->create(['active' => true]);

    $service = app(SeminarAlertService::class);
    (new DispatchSeminarAlertsJob($seminar))->handle($service);
    (new DispatchSeminarAlertsJob($seminar))->handle($service);

    Mail::assertQueued(NewSeminarAlert::class, 1);
    expect(SeminarAlertDispatch::where('seminar_id', $seminar->id)->count())->toBe(1);
});

it('writes an audit log entry with the dispatch count', function () {
    Mail::fake();
    $user = User::factory()->create();
    AlertPreference::factory()->for($user)->create();
    $seminar = Seminar::factory()->create(['active' => true]);

    (new DispatchSeminarAlertsJob($seminar))->handle(app(SeminarAlertService::class));

    $log = AuditLog::where('event_name', AuditEvent::SeminarAlertDispatched->value)->first();
    expect($log)->not->toBeNull();
    expect($log->event_data['dispatched'] ?? null)->toBe(1);
});

it('does nothing when seminar is not active', function () {
    Mail::fake();
    $user = User::factory()->create();
    AlertPreference::factory()->for($user)->create();
    $seminar = Seminar::factory()->create(['active' => false]);

    (new DispatchSeminarAlertsJob($seminar))->handle(app(SeminarAlertService::class));

    Mail::assertNothingQueued();
    expect(SeminarAlertDispatch::count())->toBe(0);
});
