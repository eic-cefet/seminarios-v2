<?php

use App\Jobs\AnalyzeRatingSentiment;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

describe('POST /profile/ratings/{seminar} - sentiment dispatch', function () {
    it('dispatches sentiment job when comment is present and feature is enabled', function () {
        Queue::fake();
        config(['features.sentiment_analysis' => ['enabled' => true, 'sample_rate' => 100]]);

        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subHour(),
        ]);
        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 4,
            'comment' => 'Great seminar!',
            'ai_analysis_consent' => true,
        ])->assertOk();

        Queue::assertPushed(AnalyzeRatingSentiment::class);
    });

    it('does not dispatch sentiment job when comment is null', function () {
        Queue::fake();
        config(['features.sentiment_analysis' => ['enabled' => true, 'sample_rate' => 100]]);

        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subHour(),
        ]);
        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 4,
            'comment' => null,
        ])->assertOk();

        Queue::assertNotPushed(AnalyzeRatingSentiment::class);
    });

    it('does not dispatch sentiment job when feature is disabled', function () {
        Queue::fake();
        config(['features.sentiment_analysis' => ['enabled' => false, 'sample_rate' => 100]]);

        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subHour(),
        ]);
        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 4,
            'comment' => 'Great seminar!',
        ])->assertOk();

        Queue::assertNotPushed(AnalyzeRatingSentiment::class);
    });

    it('does not dispatch sentiment job when sample rate is zero', function () {
        Queue::fake();
        config(['features.sentiment_analysis' => ['enabled' => true, 'sample_rate' => 0]]);

        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subHour(),
        ]);
        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 4,
            'comment' => 'Great seminar!',
        ])->assertOk();

        Queue::assertNotPushed(AnalyzeRatingSentiment::class);
    });

    it('submits the rating even when sync sentiment analysis fails', function () {
        config([
            'features.sentiment_analysis' => ['enabled' => true, 'sample_rate' => 100],
            'ai.api_key' => 'test-key',
            'ai.base_url' => 'https://api.openai.com/v1',
            'ai.model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'fail'], 500),
        ]);

        $user = actingAsUser();
        $seminar = Seminar::factory()->create([
            'scheduled_at' => now()->subHour(),
        ]);
        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 4,
            'comment' => 'Great seminar!',
        ])->assertOk();

        $rating = Rating::query()
            ->where('user_id', $user->id)
            ->where('seminar_id', $seminar->id)
            ->first();

        expect($rating)->not->toBeNull();
        expect($rating->sentiment)->toBeNull();
        expect($rating->sentiment_analyzed_at)->toBeNull();
    });

    it('reports the exception and still returns ok when dispatch itself throws', function () {
        config([
            'features.sentiment_analysis' => ['enabled' => true, 'sample_rate' => 100],
            'lgpd.features.ai_sentiment_opt_in' => false,
        ]);

        Bus::fake();
        Bus::shouldReceive('dispatch')->andThrow(new RuntimeException('queue connection failed'));

        $user = actingAsUser();
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->subHour()]);
        Registration::factory()->create([
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'present' => true,
        ]);

        $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 3,
            'comment' => 'Dispatch will throw',
            'ai_analysis_consent' => true,
        ])->assertOk();

        expect(Rating::where('user_id', $user->id)->where('seminar_id', $seminar->id)->exists())->toBeTrue();
    });
});
