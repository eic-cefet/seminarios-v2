<?php

use App\Models\AuditLog;
use App\Models\Subject;
use App\Services\AiService;
use Illuminate\Support\Facades\Artisan;

describe('NormalizeSubjectNamesCommand', function () {
    it('fails when ai service is not configured', function () {
        app()->singleton(AiService::class, fn () => null);

        $this->artisan('subjects:ai-normalize')
            ->expectsOutputToContain('AI service is not configured')
            ->assertExitCode(1);
    });

    it('exits early when no subjects exist', function () {
        $ai = Mockery::mock(AiService::class);
        app()->singleton(AiService::class, fn () => $ai);

        $this->artisan('subjects:ai-normalize')
            ->expectsOutputToContain('No subjects found')
            ->assertExitCode(0);
    });

    it('reports no fixes needed when ai finds nothing to change', function () {
        Subject::factory()->create(['name' => 'Machine Learning']);

        mockNormalizeAi('[]');

        $this->artisan('subjects:ai-normalize')
            ->expectsOutputToContain('No fixes needed')
            ->assertExitCode(0);
    });

    it('shows suggestions in dry-run mode without applying', function () {
        $s = Subject::factory()->create(['name' => 'Inteligencia Artificial']);

        mockNormalizeAi(json_encode([
            ['id' => $s->id, 'name' => 'Inteligência Artificial'],
        ]));

        $exitCode = Artisan::call('subjects:ai-normalize', ['--dry-run' => true]);
        $output = Artisan::output();

        expect($exitCode)->toBe(0);
        expect($output)
            ->toContain('Found 1 name(s) to fix')
            ->toContain('Inteligencia Artificial')
            ->toContain('Inteligência Artificial')
            ->toContain('Dry run complete');

        expect($s->fresh()->name)->toBe('Inteligencia Artificial');
    });

    it('applies fixes when confirmed', function () {
        $s1 = Subject::factory()->create(['name' => 'Inteligencia Artificial']);
        $s2 = Subject::factory()->create(['name' => 'machine learning']);

        mockNormalizeAi(json_encode([
            ['id' => $s1->id, 'name' => 'Inteligência Artificial'],
            ['id' => $s2->id, 'name' => 'Machine Learning'],
        ]));

        $this->artisan('subjects:ai-normalize')
            ->expectsConfirmation('Apply all fixes?', 'yes')
            ->expectsOutputToContain('Applied 2 fix(es)')
            ->assertExitCode(0);

        expect($s1->fresh()->name)->toBe('Inteligência Artificial');
        expect($s2->fresh()->name)->toBe('Machine Learning');
    });

    it('does not apply fixes when declined', function () {
        $s = Subject::factory()->create(['name' => 'machine learning']);

        mockNormalizeAi(json_encode([
            ['id' => $s->id, 'name' => 'Machine Learning'],
        ]));

        $this->artisan('subjects:ai-normalize')
            ->expectsConfirmation('Apply all fixes?', 'no')
            ->expectsOutputToContain('Cancelled')
            ->assertExitCode(0);

        expect($s->fresh()->name)->toBe('machine learning');
    });

    it('records audit logs for each fix', function () {
        $s = Subject::factory()->create(['name' => 'Inteligencia']);

        mockNormalizeAi(json_encode([
            ['id' => $s->id, 'name' => 'Inteligência'],
        ]));

        AuditLog::query()->delete();

        $this->artisan('subjects:ai-normalize')
            ->expectsConfirmation('Apply all fixes?', 'yes')
            ->assertExitCode(0);

        $log = AuditLog::where('event_name', 'command.ai_subjects_normalized')->first();

        expect($log)->not->toBeNull();
        expect($log->event_data['old_name'])->toBe('Inteligencia');
        expect($log->event_data['new_name'])->toBe('Inteligência');
    });

    it('handles invalid json from ai gracefully', function () {
        Subject::factory()->create();

        mockNormalizeAi('not json');

        $this->artisan('subjects:ai-normalize')
            ->expectsOutputToContain('No fixes needed')
            ->assertExitCode(0);
    });

    it('handles ai request failure gracefully', function () {
        Subject::factory()->create();

        $ai = Mockery::mock(AiService::class);
        $ai->shouldReceive('chat')->andThrow(new RuntimeException('API error'));
        app()->singleton(AiService::class, fn () => $ai);

        $this->artisan('subjects:ai-normalize')
            ->expectsOutputToContain('No fixes needed')
            ->assertExitCode(0);
    });

    it('filters out invalid ids from ai response', function () {
        $s = Subject::factory()->create(['name' => 'machine learning']);

        mockNormalizeAi(json_encode([
            ['id' => $s->id, 'name' => 'Machine Learning'],
            ['id' => 99999, 'name' => 'Ghost Subject'],
        ]));

        $this->artisan('subjects:ai-normalize')
            ->expectsConfirmation('Apply all fixes?', 'yes')
            ->expectsOutputToContain('Applied 1 fix(es)')
            ->assertExitCode(0);
    });

    it('skips fix when subject is deleted before applying', function () {
        $s = Subject::factory()->create(['name' => 'Inteligencia']);

        mockNormalizeAi(json_encode([
            ['id' => $s->id, 'name' => 'Inteligência'],
        ]));

        // Delete the subject after the AI response is prepared but
        // the command will still try to apply the fix.
        // We mock AiService to delete the subject mid-flow via a side effect.
        $ai = Mockery::mock(AiService::class);
        $ai->shouldReceive('chat')->andReturnUsing(function () use ($s) {
            // Delete the subject so it won't be found during apply
            $s->forceDelete();

            return json_encode([['id' => $s->id, 'name' => 'Inteligência']]);
        });
        app()->singleton(AiService::class, fn () => $ai);

        $this->artisan('subjects:ai-normalize')
            ->expectsConfirmation('Apply all fixes?', 'yes')
            ->expectsOutputToContain('Applied 0 fix(es)')
            ->assertExitCode(0);
    });

    it('skips subjects where ai suggests the same name', function () {
        $s = Subject::factory()->create(['name' => 'Machine Learning']);

        mockNormalizeAi(json_encode([
            ['id' => $s->id, 'name' => 'Machine Learning'],
        ]));

        $this->artisan('subjects:ai-normalize')
            ->expectsOutputToContain('No fixes needed')
            ->assertExitCode(0);
    });
});

function mockNormalizeAi(string $response): void
{
    $ai = Mockery::mock(AiService::class);
    $ai->shouldReceive('chat')->andReturn($response);

    app()->singleton(AiService::class, fn () => $ai);
}
