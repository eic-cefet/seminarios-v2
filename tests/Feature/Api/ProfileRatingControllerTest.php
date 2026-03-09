<?php

use App\Jobs\AnalyzeRatingSentiment;
use App\Models\Registration;
use App\Models\Seminar;
use App\Services\FeatureFlags;
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
});
