<?php

use App\Models\AuditLog;
use App\Models\Seminar;
use App\Models\Subject;
use Illuminate\Support\Facades\Artisan;

describe('CleanupOrphanSubjectsCommand', function () {
    it('reports no orphans when all subjects have seminars', function () {
        $subject = Subject::factory()->create();
        $subject->seminars()->attach(Seminar::factory()->create());

        $this->artisan('subjects:cleanup-orphans')
            ->expectsOutputToContain('No orphan subjects found')
            ->assertExitCode(0);
    });

    it('reports no orphans when no subjects exist', function () {
        $this->artisan('subjects:cleanup-orphans')
            ->expectsOutputToContain('No orphan subjects found')
            ->assertExitCode(0);
    });

    it('shows orphans in dry-run mode without deleting', function () {
        $orphan = Subject::factory()->create(['name' => 'Unused Topic']);
        $kept = Subject::factory()->create(['name' => 'Active Topic']);
        $kept->seminars()->attach(Seminar::factory()->create());

        $exitCode = Artisan::call('subjects:cleanup-orphans', ['--dry-run' => true]);
        $output = Artisan::output();

        expect($exitCode)->toBe(0);
        expect($output)
            ->toContain('Found 1 orphan subject(s)')
            ->toContain('Unused Topic')
            ->toContain('Dry run complete');

        expect(Subject::count())->toBe(2);
    });

    it('soft-deletes orphan subjects when confirmed', function () {
        $orphan1 = Subject::factory()->create(['name' => 'Orphan A']);
        $orphan2 = Subject::factory()->create(['name' => 'Orphan B']);
        $kept = Subject::factory()->create(['name' => 'Active']);
        $kept->seminars()->attach(Seminar::factory()->create());

        $this->artisan('subjects:cleanup-orphans')
            ->expectsConfirmation('Soft-delete 2 orphan subject(s)?', 'yes')
            ->expectsOutputToContain('Soft-deleted 2 subject(s)')
            ->assertExitCode(0);

        expect(Subject::count())->toBe(1);
        expect(Subject::withTrashed()->count())->toBe(3);
        expect($kept->fresh()->trashed())->toBeFalse();
        expect(Subject::withTrashed()->find($orphan1->id)->trashed())->toBeTrue();
        expect(Subject::withTrashed()->find($orphan2->id)->trashed())->toBeTrue();
    });

    it('does not delete when declined', function () {
        Subject::factory()->create(['name' => 'Orphan']);

        $this->artisan('subjects:cleanup-orphans')
            ->expectsConfirmation('Soft-delete 1 orphan subject(s)?', 'no')
            ->expectsOutputToContain('Cancelled')
            ->assertExitCode(0);

        expect(Subject::count())->toBe(1);
    });

    it('records audit log', function () {
        $orphan = Subject::factory()->create();

        AuditLog::query()->delete();

        $this->artisan('subjects:cleanup-orphans')
            ->expectsConfirmation('Soft-delete 1 orphan subject(s)?', 'yes')
            ->assertExitCode(0);

        $log = AuditLog::where('event_name', 'command.orphan_subjects_cleaned_up')->first();

        expect($log)->not->toBeNull();
        expect($log->event_data['deleted_ids'])->toBe([$orphan->id]);
        expect($log->event_data['count'])->toBe(1);
    });
});
