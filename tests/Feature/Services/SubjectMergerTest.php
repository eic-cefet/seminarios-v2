<?php

use App\Models\AuditLog;
use App\Models\Seminar;
use App\Models\Subject;
use App\Services\SubjectMerger;

it('merges sources into the target, rewires seminars, soft-deletes via model events', function () {
    $target = Subject::factory()->create(['name' => 'IA']);
    $sourceA = Subject::factory()->create(['name' => 'I.A.']);
    $sourceB = Subject::factory()->create(['name' => 'Inteligência Artificial']);
    $seminar = Seminar::factory()->create();
    $seminar->subjects()->attach([$sourceA->id, $sourceB->id]);

    $result = app(SubjectMerger::class)->merge($target, [$sourceA->id, $sourceB->id], 'Inteligência Artificial');

    expect($result)->toBeInstanceOf(Subject::class)
        ->and($target->fresh()->name)->toBe('Inteligência Artificial')
        ->and(Subject::whereIn('id', [$sourceA->id, $sourceB->id])->count())->toBe(0)
        ->and(Subject::withTrashed()->whereIn('id', [$sourceA->id, $sourceB->id])->count())->toBe(2)
        ->and($seminar->subjects()->pluck('subjects.id')->all())->toBe([$target->id]);

    // Auditable fires per-model on soft-delete:
    expect(AuditLog::query()->where('event_name', 'subject.soft_deleted')->where('auditable_id', $sourceA->id)->exists())
        ->toBeTrue()
        ->and(AuditLog::query()->where('event_name', 'subject.soft_deleted')->where('auditable_id', $sourceB->id)->exists())
        ->toBeTrue();
});

it('does not rename the target when new name is null or empty', function () {
    $target = Subject::factory()->create(['name' => 'Original Name']);
    $source = Subject::factory()->create(['name' => 'Other']);

    app(SubjectMerger::class)->merge($target, [$source->id], null);

    expect($target->fresh()->name)->toBe('Original Name');

    app(SubjectMerger::class)->merge($target, [], '');

    expect($target->fresh()->name)->toBe('Original Name');
});

it('rewires pivot rows without violating unique constraint when target shares a seminar with a source', function () {
    $target = Subject::factory()->create();
    $source = Subject::factory()->create();
    $seminar = Seminar::factory()->create();
    // Both target and source are attached to the same seminar
    $seminar->subjects()->attach([$target->id, $source->id]);

    app(SubjectMerger::class)->merge($target, [$source->id]);

    expect($seminar->subjects()->pluck('subjects.id')->all())->toBe([$target->id]);
});

it('returns the affected seminar ids', function () {
    $target = Subject::factory()->create();
    $sourceA = Subject::factory()->create();
    $sourceB = Subject::factory()->create();
    $seminarA = Seminar::factory()->create();
    $seminarB = Seminar::factory()->create();
    $seminarA->subjects()->attach([$sourceA->id]);
    $seminarB->subjects()->attach([$sourceB->id]);

    $affectedSeminarIds = app(SubjectMerger::class)->affectedSeminarIds([$sourceA->id, $sourceB->id]);

    expect($affectedSeminarIds)->toEqualCanonicalizing([$seminarA->id, $seminarB->id]);
});
