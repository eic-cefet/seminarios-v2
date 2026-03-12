<?php

use App\Jobs\AnalyzeRatingSentiment;
use App\Models\Rating;
use App\Models\Seminar;
use Illuminate\Support\Facades\Queue;

describe('AnalyzePendingSentimentsCommand', function () {
    it('dispatches jobs for ratings with comments and no analysis', function () {
        Queue::fake();

        $seminar = Seminar::factory()->create();

        $pending1 = Rating::factory()->for($seminar)->create([
            'comment' => 'Great seminar',
            'sentiment_analyzed_at' => null,
        ]);

        $pending2 = Rating::factory()->for($seminar)->create([
            'comment' => 'Could be better',
            'sentiment_analyzed_at' => null,
        ]);

        $this->artisan('ratings:analyze-sentiments')
            ->expectsOutputToContain('Dispatching 2 sentiment analysis job(s)')
            ->assertExitCode(0);

        Queue::assertPushed(AnalyzeRatingSentiment::class, 2);
    });

    it('skips ratings without comments', function () {
        Queue::fake();

        $seminar = Seminar::factory()->create();

        Rating::factory()->for($seminar)->create([
            'comment' => null,
            'sentiment_analyzed_at' => null,
        ]);

        Rating::factory()->for($seminar)->create([
            'comment' => '',
            'sentiment_analyzed_at' => null,
        ]);

        $this->artisan('ratings:analyze-sentiments')
            ->expectsOutputToContain('No pending ratings to analyze.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });

    it('skips ratings already analyzed', function () {
        Queue::fake();

        $seminar = Seminar::factory()->create();

        Rating::factory()->for($seminar)->create([
            'comment' => 'Nice talk',
            'sentiment_analyzed_at' => now(),
        ]);

        $this->artisan('ratings:analyze-sentiments')
            ->expectsOutputToContain('No pending ratings to analyze.')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    });
});
