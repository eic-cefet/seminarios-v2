<?php

use App\Enums\CommunicationCategory;
use App\Models\AlertPreference;
use App\Models\User;

function upsertPrefs(User $user, array $attrs): AlertPreference
{
    return AlertPreference::updateOrCreate(
        ['user_id' => $user->id],
        array_merge([
            'opted_in' => false,
            'seminar_reminder_7d' => true,
            'seminar_reminder_24h' => true,
            'evaluation_prompt' => true,
            'announcements' => true,
        ], $attrs),
    );
}

it('returns true for transactional category when row is missing (opt-out default)', function () {
    $user = User::factory()->create();
    $user->alertPreference()?->delete();

    expect($user->fresh()->wantsCommunication(CommunicationCategory::SeminarReminder24h))->toBeTrue();
    expect($user->fresh()->wantsCommunication(CommunicationCategory::EvaluationPrompt))->toBeTrue();
    expect($user->fresh()->wantsCommunication(CommunicationCategory::SeminarReminder7d))->toBeTrue();
    expect($user->fresh()->wantsCommunication(CommunicationCategory::Announcements))->toBeTrue();
});

it('returns false for topic-follow category when row is missing (opt-in default)', function () {
    $user = User::factory()->create();
    $user->alertPreference()?->delete();

    expect($user->fresh()->wantsCommunication(CommunicationCategory::TopicFollow))->toBeFalse();
});

it('returns false when transactional flag is explicitly disabled', function () {
    $user = User::factory()->create();
    upsertPrefs($user, ['seminar_reminder_24h' => false]);

    expect($user->fresh()->wantsCommunication(CommunicationCategory::SeminarReminder24h))->toBeFalse();
});

it('returns true when transactional flag is explicitly enabled', function () {
    $user = User::factory()->create();
    upsertPrefs($user, ['evaluation_prompt' => true]);

    expect($user->fresh()->wantsCommunication(CommunicationCategory::EvaluationPrompt))->toBeTrue();
});

it('returns opted_in value for topic-follow category', function () {
    $user = User::factory()->create();
    upsertPrefs($user, ['opted_in' => true]);

    expect($user->fresh()->wantsCommunication(CommunicationCategory::TopicFollow))->toBeTrue();
});
