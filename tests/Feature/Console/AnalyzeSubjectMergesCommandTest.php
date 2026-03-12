<?php

use App\Models\Seminar;
use App\Models\Subject;
use App\Services\AiService;
use Illuminate\Support\Facades\Artisan;

describe('AnalyzeSubjectMergesCommand', function () {
    it('fails when ai service is not configured', function () {
        app()->singleton(AiService::class, fn () => null);

        $this->artisan('subjects:ai-merge')
            ->expectsOutputToContain('AI service is not configured')
            ->assertExitCode(1);
    });

    it('exits early when fewer than 2 subjects exist', function () {
        Subject::factory()->create();

        $ai = Mockery::mock(AiService::class);
        app()->singleton(AiService::class, fn () => $ai);

        $this->artisan('subjects:ai-merge')
            ->expectsOutputToContain('Not enough subjects to analyze')
            ->assertExitCode(0);
    });

    it('reports no suggestions when ai finds no duplicates', function () {
        Subject::factory()->count(3)->create();

        mockAi('[]');

        $this->artisan('subjects:ai-merge')
            ->expectsOutputToContain('No merge suggestions found')
            ->assertExitCode(0);
    });

    it('shows suggestions in dry-run mode without merging', function () {
        $s1 = Subject::factory()->create(['name' => 'Machine Learning']);
        $s2 = Subject::factory()->create(['name' => 'ML']);

        mockAi(
            json_encode([[$s1->id, $s2->id]]),
            'Machine Learning',
        );

        $exitCode = Artisan::call('subjects:ai-merge', ['--dry-run' => true]);
        $output = Artisan::output();

        expect($exitCode)->toBe(0);
        expect($output)
            ->toContain('Found 1 merge group(s)')
            ->toContain('Machine Learning')
            ->toContain('ML')
            ->toContain('Suggested name: Machine Learning')
            ->toContain('Dry run complete');

        expect(Subject::count())->toBe(2);
    });

    it('merges subjects when confirmed', function () {
        $seminar1 = Seminar::factory()->create();
        $seminar2 = Seminar::factory()->create();

        $s1 = Subject::factory()->create(['name' => 'Machine Learning']);
        $s2 = Subject::factory()->create(['name' => 'ML']);

        $seminar1->subjects()->attach($s1);
        $seminar2->subjects()->attach($s2);

        mockAi(
            json_encode([[$s1->id, $s2->id]]),
            'Machine Learning',
        );

        $this->artisan('subjects:ai-merge')
            ->expectsConfirmation('Merge this group into "Machine Learning"?', 'yes')
            ->expectsOutputToContain('Merged into')
            ->assertExitCode(0);

        // Target kept, source soft-deleted
        expect(Subject::count())->toBe(1);
        expect(Subject::withTrashed()->count())->toBe(2);

        // Both seminars now linked to the target
        $target = Subject::first();
        expect($target->name)->toBe('Machine Learning');
        expect($target->seminars()->count())->toBe(2);
    });

    it('skips group when user declines', function () {
        $s1 = Subject::factory()->create(['name' => 'AI']);
        $s2 = Subject::factory()->create(['name' => 'Artificial Intelligence']);

        mockAi(
            json_encode([[$s1->id, $s2->id]]),
            'Artificial Intelligence',
        );

        $this->artisan('subjects:ai-merge')
            ->expectsConfirmation('Merge this group into "Artificial Intelligence"?', 'no')
            ->expectsOutputToContain('Skipped')
            ->assertExitCode(0);

        expect(Subject::count())->toBe(2);
    });

    it('picks the subject with most seminars as target', function () {
        $s1 = Subject::factory()->create(['name' => 'ML']);
        $s2 = Subject::factory()->create(['name' => 'Machine Learning']);

        // s2 has more seminars
        $s2->seminars()->attach(Seminar::factory()->count(3)->create());
        $s1->seminars()->attach(Seminar::factory()->create());

        mockAi(
            json_encode([[$s1->id, $s2->id]]),
            'Machine Learning',
        );

        $this->artisan('subjects:ai-merge')
            ->expectsConfirmation('Merge this group into "Machine Learning"?', 'yes')
            ->assertExitCode(0);

        // s2 should be the target (more seminars)
        $target = Subject::first();
        expect($target->id)->toBe($s2->id);
        expect($target->seminars()->count())->toBe(4);

        // s1 should be soft-deleted
        expect(Subject::withTrashed()->find($s1->id)->trashed())->toBeTrue();
    });

    it('handles invalid json from ai gracefully', function () {
        Subject::factory()->count(2)->create();

        mockAi('not valid json');

        $this->artisan('subjects:ai-merge')
            ->expectsOutputToContain('No merge suggestions found')
            ->assertExitCode(0);
    });

    it('skips group when name suggestion fails', function () {
        $s1 = Subject::factory()->create(['name' => 'Topic A']);
        $s2 = Subject::factory()->create(['name' => 'Topic B']);

        $ai = Mockery::mock(AiService::class);
        $ai->shouldReceive('chat')
            ->once()
            ->andReturn(json_encode([[$s1->id, $s2->id]]));
        $ai->shouldReceive('chat')
            ->once()
            ->andThrow(new RuntimeException('API error'));

        app()->singleton(AiService::class, fn () => $ai);

        $this->artisan('subjects:ai-merge')
            ->expectsOutputToContain('Could not get a name suggestion')
            ->assertExitCode(0);

        expect(Subject::count())->toBe(2);
    });

    it('filters out invalid ids from ai response', function () {
        $s1 = Subject::factory()->create(['name' => 'Topic A']);
        $s2 = Subject::factory()->create(['name' => 'Topic B']);

        mockAi(
            json_encode([[$s1->id, $s2->id, 99999]]),
            'Topic AB',
        );

        $this->artisan('subjects:ai-merge')
            ->expectsConfirmation('Merge this group into "Topic AB"?', 'yes')
            ->assertExitCode(0);

        expect(Subject::count())->toBe(1);
    });
});

function mockAi(string ...$responses): void
{
    $ai = Mockery::mock(AiService::class);
    $ai->shouldReceive('chat')->andReturn(...$responses);

    app()->singleton(AiService::class, fn () => $ai);
}
