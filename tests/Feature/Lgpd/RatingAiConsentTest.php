<?php

use App\Jobs\AnalyzeRatingSentiment;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config()->set('lgpd.features.ai_sentiment_opt_in', true);
    config()->set('features.sentiment_analysis', ['enabled' => true, 'sample_rate' => 100]);
});

it('does not dispatch sentiment analysis without consent', function () {
    Queue::fake();
    $user = actingAsUser();
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->subHour()]);
    Registration::factory()->for($user)->for($seminar)->create(['present' => true]);

    $this->postJson("/api/profile/ratings/{$seminar->id}", [
        'score' => 5,
        'comment' => 'Muito bom',
        'ai_analysis_consent' => false,
    ])->assertSuccessful();

    Queue::assertNotPushed(AnalyzeRatingSentiment::class);
    expect(Rating::where('user_id', $user->id)->first()->ai_analysis_consent)->toBeFalse();
});

it('dispatches sentiment analysis only with consent', function () {
    Queue::fake();
    $user = actingAsUser();
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->subHour()]);
    Registration::factory()->for($user)->for($seminar)->create(['present' => true]);

    $this->postJson("/api/profile/ratings/{$seminar->id}", [
        'score' => 5,
        'comment' => 'Muito bom',
        'ai_analysis_consent' => true,
    ])->assertSuccessful();

    Queue::assertPushed(AnalyzeRatingSentiment::class);
    expect(Rating::where('user_id', $user->id)->first()->ai_analysis_consent)->toBeTrue();
});

it('defaults to no consent when field omitted', function () {
    Queue::fake();
    $user = actingAsUser();
    $seminar = Seminar::factory()->create(['scheduled_at' => now()->subHour()]);
    Registration::factory()->for($user)->for($seminar)->create(['present' => true]);

    $this->postJson("/api/profile/ratings/{$seminar->id}", [
        'score' => 5,
        'comment' => 'Sem opinião sobre IA',
    ])->assertSuccessful();

    Queue::assertNotPushed(AnalyzeRatingSentiment::class);
    expect(Rating::where('user_id', $user->id)->first()->ai_analysis_consent)->toBeFalse();
});
