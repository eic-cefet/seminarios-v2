<?php

use App\Jobs\AnalyzeRatingSentiment;
use App\Models\Rating;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'ai.api_key' => 'test-key',
        'ai.base_url' => 'https://api.openai.com/v1',
        'ai.model' => 'gpt-4o-mini',
    ]);
});

it('analyzes sentiment and updates the rating', function () {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Sentimento positivo. Aluno gostou da apresentação.']],
            ],
        ]),
    ]);

    $rating = Rating::factory()->create([
        'comment' => 'Ótimo seminário!',
        'score' => 5,
        'sentiment' => null,
        'sentiment_analyzed_at' => null,
    ]);

    (new AnalyzeRatingSentiment($rating))->handle();

    $rating->refresh();
    expect($rating->sentiment)->toBe('Sentimento positivo. Aluno gostou da apresentação.');
    expect($rating->sentiment_analyzed_at)->not->toBeNull();
});

it('skips ratings without comments', function () {
    Http::fake();

    $rating = Rating::factory()->create([
        'comment' => null,
        'sentiment' => null,
        'sentiment_analyzed_at' => null,
    ]);

    (new AnalyzeRatingSentiment($rating))->handle();

    Http::assertNothingSent();
    $rating->refresh();
    expect($rating->sentiment)->toBeNull();
});

it('skips ratings with empty comments', function () {
    Http::fake();

    $rating = Rating::factory()->create([
        'comment' => '',
        'sentiment' => null,
        'sentiment_analyzed_at' => null,
    ]);

    (new AnalyzeRatingSentiment($rating))->handle();

    Http::assertNothingSent();
});

it('skips already analyzed ratings', function () {
    Http::fake();

    $rating = Rating::factory()->create([
        'comment' => 'Good seminar',
        'sentiment' => 'Already analyzed',
        'sentiment_analyzed_at' => now(),
    ]);

    (new AnalyzeRatingSentiment($rating))->handle();

    Http::assertNothingSent();
});

it('skips when AI is not configured', function () {
    config(['ai.api_key' => null]);
    Log::shouldReceive('error')->once()->with('AI service is not configured. Set AI_API_KEY in your environment.');

    $rating = Rating::factory()->create([
        'comment' => 'Test comment',
        'sentiment' => null,
        'sentiment_analyzed_at' => null,
    ]);

    (new AnalyzeRatingSentiment($rating))->handle();

    $rating->refresh();
    expect($rating->sentiment)->toBeNull();
});

it('handles AI request failure gracefully', function () {
    Http::fake([
        'api.openai.com/*' => Http::response(['error' => 'fail'], 500),
    ]);
    Log::shouldReceive('error')->once();

    $rating = Rating::factory()->create([
        'comment' => 'Test comment',
        'sentiment' => null,
        'sentiment_analyzed_at' => null,
    ]);

    (new AnalyzeRatingSentiment($rating))->handle();

    $rating->refresh();
    expect($rating->sentiment)->toBeNull();
    expect($rating->sentiment_analyzed_at)->toBeNull();
});

it('handles empty AI response gracefully', function () {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => null]],
            ],
        ]),
    ]);
    Log::shouldReceive('error')->once();

    $rating = Rating::factory()->create([
        'comment' => 'Test comment',
        'sentiment' => null,
        'sentiment_analyzed_at' => null,
    ]);

    (new AnalyzeRatingSentiment($rating))->handle();

    $rating->refresh();
    expect($rating->sentiment)->toBeNull();
});
