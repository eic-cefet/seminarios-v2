<?php

use App\Jobs\AnalyzeRatingSentiment;
use App\Models\Rating;
use App\Models\Registration;
use App\Models\Seminar;
use App\Notifications\BadgesUnlockedNotification;
use App\Services\GamificationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

describe('POST /profile/ratings/{seminar} - sentiment dispatch', function () {
    it('returns evaluation progress and the first evaluation badge', function () {
        Notification::fake();

        $user = actingAsUser();
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->subHour()]);
        Registration::factory()->present()->for($user)->for($seminar)->create();
        app(GamificationService::class)->sync($user, notify: false);

        $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 5,
            'comment' => 'Excelente apresentação.',
        ])->assertOk()
            ->assertJsonPath('gamification.xp_earned', 45)
            ->assertJsonPath('gamification.new_badges.0.key', 'first_evaluation')
            ->assertJsonPath('gamification.new_badges.0.description', 'Avalie uma apresentação.')
            ->assertJsonMissingPath('gamification.new_badges.0.metric')
            ->assertJsonMissingPath('gamification.new_badges.0.threshold');

        Notification::assertSentToTimes($user, BadgesUnlockedNotification::class, 1);
    });

    it('keeps a submitted rating when gamification reconciliation fails', function () {
        Exceptions::fake();

        $exception = new RuntimeException('gamification unavailable');
        $gamification = Mockery::mock(GamificationService::class);
        $gamification->shouldReceive('sync')->once()->andThrow($exception);
        app()->instance(GamificationService::class, $gamification);

        $user = actingAsUser();
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->subHour()]);
        Registration::factory()->present()->for($user)->for($seminar)->create();

        $this->postJson("/api/profile/ratings/{$seminar->id}", [
            'score' => 4,
            'comment' => 'Feedback válido.',
        ])->assertOk()
            ->assertJsonPath('gamification', null);

        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'seminar_id' => $seminar->id,
            'score' => 4,
        ]);
        Exceptions::assertReported(fn (RuntimeException $reported): bool => $reported === $exception);
    });

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

    it('returns conflict when a parallel rating slips past the check', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->subDays(5)]);
        Registration::factory()->for($user)->for($seminar)
            ->create(['present' => true]);

        Rating::factory()->for($user)->for($seminar)->create();

        $this->postJson("/api/profile/ratings/{$seminar->id}", ['score' => 5, 'comment' => 'oi'])
            ->assertConflict()
            ->assertJsonFragment(['message' => 'Você já avaliou esta apresentação.']);
    });

    it('returns conflict when the unique constraint fires after the existence pre-check', function () {
        $user = actingAsUser();
        $seminar = Seminar::factory()->create(['scheduled_at' => now()->subDays(5)]);
        Registration::factory()->for($user)->for($seminar)
            ->create(['present' => true]);

        // Simulate a parallel insert winning the race after our exists() check.
        Rating::creating(function (): never {
            throw new UniqueConstraintViolationException(
                'sqlite',
                'insert into ratings ...',
                [],
                new RuntimeException('UNIQUE constraint failed: ratings.seminar_id, ratings.user_id'),
            );
        });

        $this->postJson("/api/profile/ratings/{$seminar->id}", ['score' => 4, 'comment' => 'race'])
            ->assertConflict()
            ->assertJsonFragment(['message' => 'Você já avaliou esta apresentação.']);
    });
});
