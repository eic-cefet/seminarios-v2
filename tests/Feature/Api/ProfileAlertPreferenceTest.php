<?php

use App\Models\AlertPreference;
use App\Models\SeminarType;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication for show', function () {
    $this->getJson('/api/profile/alert-preferences')->assertUnauthorized();
});

it('requires authentication for update', function () {
    $this->putJson('/api/profile/alert-preferences', [])->assertUnauthorized();
});

it('returns a default opted-out preference when none exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/profile/alert-preferences')
        ->assertOk()
        ->assertJsonPath('data.optedIn', false)
        ->assertJsonPath('data.seminarTypeIds', [])
        ->assertJsonPath('data.subjectIds', []);
});

it('returns existing preference with filters', function () {
    $user = User::factory()->create();
    $type = SeminarType::factory()->create();
    $subject = Subject::factory()->create();
    AlertPreference::factory()->for($user)
        ->forTypes([$type->id])
        ->forSubjects([$subject->id])
        ->create(['opted_in' => true]);

    $this->actingAs($user)
        ->getJson('/api/profile/alert-preferences')
        ->assertOk()
        ->assertJsonPath('data.optedIn', true)
        ->assertJsonPath('data.seminarTypeIds', [$type->id])
        ->assertJsonPath('data.subjectIds', [$subject->id]);
});

it('creates a preference on first update', function () {
    $user = User::factory()->create();
    $type = SeminarType::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/profile/alert-preferences', [
            'opted_in' => true,
            'seminar_type_ids' => [$type->id],
            'subject_ids' => [],
        ])
        ->assertOk()
        ->assertJsonPath('data.optedIn', true)
        ->assertJsonPath('data.seminarTypeIds', [$type->id]);

    expect(AlertPreference::where('user_id', $user->id)->count())->toBe(1);
});

it('updates existing preference and replaces filters', function () {
    $user = User::factory()->create();
    $keep = SeminarType::factory()->create();
    $drop = SeminarType::factory()->create();
    $pref = AlertPreference::factory()->for($user)
        ->forTypes([$keep->id, $drop->id])
        ->create(['opted_in' => true]);

    $this->actingAs($user)
        ->putJson('/api/profile/alert-preferences', [
            'opted_in' => true,
            'seminar_type_ids' => [$keep->id],
            'subject_ids' => [],
        ])
        ->assertOk()
        ->assertJsonPath('data.seminarTypeIds', [$keep->id]);

    expect($pref->fresh()->seminarTypes()->pluck('seminar_types.id')->all())->toBe([$keep->id]);
});

it('allows opt-in with empty filters (ALLOW ALL)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/profile/alert-preferences', [
            'opted_in' => true,
            'seminar_type_ids' => [],
            'subject_ids' => [],
        ])
        ->assertOk()
        ->assertJsonPath('data.optedIn', true);
});

it('rejects unknown seminar type ids', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/profile/alert-preferences', [
            'opted_in' => true,
            'seminar_type_ids' => [9999],
            'subject_ids' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('seminar_type_ids.0');
});

it('rejects unknown subject ids', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->putJson('/api/profile/alert-preferences', [
            'opted_in' => true,
            'seminar_type_ids' => [],
            'subject_ids' => [9999],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('subject_ids.0');
});
