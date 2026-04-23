<?php

use App\Models\AlertPreference;
use App\Models\Seminar;
use App\Models\SeminarAlertDispatch;
use App\Models\SeminarType;
use App\Models\Subject;
use App\Models\User;
use App\Services\SeminarAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SeminarAlertService::class);
});

it('returns no recipients when no one has opted in', function () {
    $seminar = Seminar::factory()->create(['active' => true]);

    $recipients = $this->service->matchingRecipients($seminar);

    expect($recipients)->toBeEmpty();
});

it('includes users opted-in with no filters (ALLOW ALL)', function () {
    $user = User::factory()->create();
    AlertPreference::factory()->for($user)->create();
    $seminar = Seminar::factory()->create(['active' => true]);

    $recipients = $this->service->matchingRecipients($seminar);

    expect($recipients->pluck('id')->all())->toBe([$user->id]);
});

it('excludes opted-out users even if filters match', function () {
    $type = SeminarType::factory()->create();
    $seminar = Seminar::factory()->create(['active' => true, 'seminar_type_id' => $type->id]);
    $user = User::factory()->create();
    AlertPreference::factory()->for($user)->optedOut()->forTypes([$type->id])->create();

    $recipients = $this->service->matchingRecipients($seminar);

    expect($recipients)->toBeEmpty();
});

it('filters by seminar type when user has type preferences', function () {
    $wantedType = SeminarType::factory()->create();
    $otherType = SeminarType::factory()->create();

    $matching = User::factory()->create();
    AlertPreference::factory()->for($matching)->forTypes([$wantedType->id])->create();

    $nonMatching = User::factory()->create();
    AlertPreference::factory()->for($nonMatching)->forTypes([$otherType->id])->create();

    $seminar = Seminar::factory()->create(['active' => true, 'seminar_type_id' => $wantedType->id]);

    $recipients = $this->service->matchingRecipients($seminar);

    expect($recipients->pluck('id')->all())->toBe([$matching->id]);
});

it('filters by subject overlap when user has subject preferences', function () {
    $subjectA = Subject::factory()->create();
    $subjectB = Subject::factory()->create();
    $subjectC = Subject::factory()->create();

    $matching = User::factory()->create();
    AlertPreference::factory()->for($matching)->forSubjects([$subjectA->id, $subjectC->id])->create();

    $nonMatching = User::factory()->create();
    AlertPreference::factory()->for($nonMatching)->forSubjects([$subjectC->id])->create();

    $seminar = Seminar::factory()->create(['active' => true]);
    $seminar->subjects()->sync([$subjectA->id, $subjectB->id]);

    $recipients = $this->service->matchingRecipients($seminar);

    expect($recipients->pluck('id')->all())->toBe([$matching->id]);
});

it('requires BOTH filter sets to match when user has both', function () {
    $type = SeminarType::factory()->create();
    $subject = Subject::factory()->create();
    $otherSubject = Subject::factory()->create();

    $typeOnly = User::factory()->create();
    AlertPreference::factory()->for($typeOnly)
        ->forTypes([$type->id])
        ->forSubjects([$otherSubject->id])
        ->create();

    $both = User::factory()->create();
    AlertPreference::factory()->for($both)
        ->forTypes([$type->id])
        ->forSubjects([$subject->id])
        ->create();

    $seminar = Seminar::factory()->create(['active' => true, 'seminar_type_id' => $type->id]);
    $seminar->subjects()->sync([$subject->id]);

    $recipients = $this->service->matchingRecipients($seminar);

    expect($recipients->pluck('id')->all())->toBe([$both->id]);
});

it('treats an empty array filter the same as null (ALLOW ALL)', function () {
    $user = User::factory()->create();
    AlertPreference::factory()->for($user)
        ->forTypes([])
        ->forSubjects([])
        ->create();

    $seminar = Seminar::factory()->create(['active' => true]);

    $recipients = $this->service->matchingRecipients($seminar);

    expect($recipients->pluck('id')->all())->toBe([$user->id]);
});

it('excludes users already dispatched for this seminar', function () {
    $user = User::factory()->create();
    AlertPreference::factory()->for($user)->create();
    $seminar = Seminar::factory()->create(['active' => true]);

    SeminarAlertDispatch::create([
        'user_id' => $user->id,
        'seminar_id' => $seminar->id,
        'sent_at' => now(),
    ]);

    $recipients = $this->service->matchingRecipients($seminar);

    expect($recipients)->toBeEmpty();
});
