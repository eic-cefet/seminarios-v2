<?php

use App\Enums\AuditEvent;
use App\Models\AlertPreference;
use App\Models\AuditLog;
use App\Models\SeminarType;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array<string, mixed>
 */
function fullPayload(array $overrides = []): array
{
    return array_merge([
        'new_seminar_alert' => true,
        'seminar_type_ids' => [],
        'subject_ids' => [],
        'seminar_reminder_7d' => true,
        'seminar_reminder_24h' => true,
        'evaluation_prompt' => true,
        'announcements' => true,
        'certificate_ready' => true,
        'seminar_rescheduled' => true,
    ], $overrides);
}

it('requires authentication for show', function () {
    $this->getJson('/api/profile/alert-preferences')->assertUnauthorized();
});

it('requires authentication for update', function () {
    $this->putJson('/api/profile/alert-preferences', [])->assertUnauthorized();
});

it('returns a default preference payload when none exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/profile/alert-preferences')
        ->assertOk()
        ->assertJsonPath('data.newSeminarAlert', false)
        ->assertJsonPath('data.seminarTypeIds', [])
        ->assertJsonPath('data.subjectIds', [])
        ->assertJsonPath('data.seminarReminder7d', true)
        ->assertJsonPath('data.seminarReminder24h', true)
        ->assertJsonPath('data.evaluationPrompt', true)
        ->assertJsonPath('data.announcements', true)
        ->assertJsonPath('data.certificateReady', true)
        ->assertJsonPath('data.seminarRescheduled', true);
});

it('returns existing preference with filters', function () {
    $user = User::factory()->create();
    $type = SeminarType::factory()->create();
    $subject = Subject::factory()->create();
    AlertPreference::factory()->for($user)
        ->forTypes([$type->id])
        ->forSubjects([$subject->id])
        ->create(['new_seminar_alert' => true]);

    $this->actingAs($user)
        ->getJson('/api/profile/alert-preferences')
        ->assertOk()
        ->assertJsonPath('data.newSeminarAlert', true)
        ->assertJsonPath('data.seminarTypeIds', [$type->id])
        ->assertJsonPath('data.subjectIds', [$subject->id]);
});

it('creates a preference on first update', function () {
    $user = User::factory()->create();
    $type = SeminarType::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/profile/alert-preferences', fullPayload([
            'seminar_type_ids' => [$type->id],
        ]))
        ->assertOk()
        ->assertJsonPath('data.newSeminarAlert', true)
        ->assertJsonPath('data.seminarTypeIds', [$type->id]);

    expect(AlertPreference::where('user_id', $user->id)->count())->toBe(1);
});

it('updates existing preference and replaces filters', function () {
    $user = User::factory()->create();
    $keep = SeminarType::factory()->create();
    $drop = SeminarType::factory()->create();
    $pref = AlertPreference::factory()->for($user)
        ->forTypes([$keep->id, $drop->id])
        ->create(['new_seminar_alert' => true]);

    $this->actingAs($user)
        ->putJson('/api/profile/alert-preferences', fullPayload([
            'seminar_type_ids' => [$keep->id],
        ]))
        ->assertOk()
        ->assertJsonPath('data.seminarTypeIds', [$keep->id]);

    expect($pref->fresh()->seminarTypes()->pluck('seminar_types.id')->all())->toBe([$keep->id]);
});

it('allows opt-in with empty filters (ALLOW ALL)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/profile/alert-preferences', fullPayload())
        ->assertOk()
        ->assertJsonPath('data.newSeminarAlert', true);
});

it('rejects unknown seminar type ids', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/profile/alert-preferences', fullPayload([
            'seminar_type_ids' => [9999],
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('seminar_type_ids.0');
});

it('rejects unknown subject ids', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/profile/alert-preferences', fullPayload([
            'subject_ids' => [9999],
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('subject_ids.0');
});

it('updates transactional flags independently of topic-follow opt-in', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/profile/alert-preferences', fullPayload([
            'new_seminar_alert' => false,
            'seminar_reminder_24h' => false,
            'evaluation_prompt' => false,
            'certificate_ready' => false,
        ]))
        ->assertOk()
        ->assertJsonPath('data.newSeminarAlert', false)
        ->assertJsonPath('data.seminarReminder24h', false)
        ->assertJsonPath('data.evaluationPrompt', false)
        ->assertJsonPath('data.certificateReady', false)
        ->assertJsonPath('data.announcements', true);

    $pref = $user->fresh()->alertPreference;
    expect($pref->seminar_reminder_24h)->toBeFalse();
    expect($pref->evaluation_prompt)->toBeFalse();
    expect($pref->certificate_ready)->toBeFalse();
    expect($pref->announcements)->toBeTrue();
});

it('rejects missing transactional flags', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/profile/alert-preferences', [
            'new_seminar_alert' => false,
            'seminar_type_ids' => [],
            'subject_ids' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'seminar_reminder_7d',
            'seminar_reminder_24h',
            'evaluation_prompt',
            'announcements',
            'certificate_ready',
            'seminar_rescheduled',
        ]);
});

it('records an audit log entry on preference update', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/profile/alert-preferences', fullPayload())
        ->assertOk();

    expect(
        AuditLog::where('event_name', AuditEvent::UserCommunicationPreferencesUpdated->value)->exists()
    )->toBeTrue();
});
