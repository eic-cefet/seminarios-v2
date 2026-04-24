<?php

use App\Enums\AuditEvent;
use App\Enums\ConsentType;
use App\Models\AuditLog;
use App\Models\DataExportRequest;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\SocialIdentity;
use App\Models\User;
use App\Models\UserConsent;
use App\Models\UserStudentData;
use App\Services\UserAnonymizationService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = app(UserAnonymizationService::class);
});

it('pseudonymizes name, email, and password on the user row', function () {
    $user = User::factory()->create([
        'name' => 'Maria Pereira',
        'email' => 'maria@example.com',
    ]);

    $this->service->anonymize($user);

    $fresh = User::withTrashed()->find($user->id);
    expect($fresh->name)->toBe('Usuário Removido')
        ->and($fresh->email)->toBe("removed-{$user->id}@deleted.local")
        ->and($fresh->anonymized_at)->not->toBeNull()
        ->and($fresh->deleted_at)->not->toBeNull();
});

it('deletes student/speaker data and social identities', function () {
    $user = User::factory()->create();
    UserStudentData::factory()->for($user)->create();
    SocialIdentity::factory()->for($user)->create();

    $this->service->anonymize($user);

    expect(UserStudentData::where('user_id', $user->id)->exists())->toBeFalse()
        ->and(SocialIdentity::where('user_id', $user->id)->exists())->toBeFalse();
});

it('keeps registrations (academic records) but scrubs rating comments', function () {
    $user = User::factory()->create();
    Registration::factory()->for($user)->create();
    Rating::factory()->for($user)->create(['comment' => 'Muito bom']);

    $this->service->anonymize($user);

    expect(Registration::where('user_id', $user->id)->count())->toBe(1)
        ->and(Rating::where('user_id', $user->id)->first()->comment)->toBe('[removido pelo usuário]');
});

it('deletes past data-export ZIPs from S3 and their DB rows', function () {
    Storage::fake('s3');
    $user = User::factory()->create();
    $request = DataExportRequest::factory()->for($user)->create([
        'status' => DataExportRequest::STATUS_COMPLETED,
        'file_path' => 'lgpd-exports/foo.zip',
    ]);
    Storage::disk('s3')->put($request->file_path, 'data');

    $this->service->anonymize($user);

    expect(DataExportRequest::where('user_id', $user->id)->exists())->toBeFalse();
    Storage::disk('s3')->assertMissing('lgpd-exports/foo.zip');
});

it('scrubs email and name from audit log event_data', function () {
    $user = User::factory()->create(['email' => 'target@example.com']);
    AuditLog::create([
        'user_id' => $user->id,
        'event_name' => 'user.update',
        'event_type' => 'manual',
        'auditable_type' => User::class,
        'auditable_id' => $user->id,
        'event_data' => [
            'old_values' => ['email' => 'target@example.com', 'name' => 'T'],
            'new_values' => ['email' => 'target@example.com', 'name' => 'U'],
        ],
        'origin' => 'test',
        'ip_address' => '127.0.0.1',
    ]);

    $this->service->anonymize($user);

    $log = AuditLog::where('user_id', $user->id)->where('event_name', 'user.update')->first();
    expect($log->event_data['old_values']['email'] ?? null)->toBe('[scrubbed]')
        ->and($log->event_data['new_values']['name'] ?? null)->toBe('[scrubbed]');
});

it('records an AccountAnonymized audit event', function () {
    $user = User::factory()->create();
    UserConsent::factory()->for($user)->granted()->create(['type' => ConsentType::TermsOfService]);

    $this->service->anonymize($user);

    expect(AuditLog::where('event_name', AuditEvent::AccountAnonymized->value)->exists())->toBeTrue();
});
