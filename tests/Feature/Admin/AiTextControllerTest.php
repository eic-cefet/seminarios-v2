<?php

use App\Models\Rating;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    config([
        'ai.api_key' => 'test-key',
        'ai.base_url' => 'https://api.openai.com/v1',
        'ai.model' => 'gpt-4o-mini',
    ]);
});

function fakeAiResponse(string $content): void
{
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => $content]],
            ],
        ]),
    ]);
}

function fakeAiFailure(): void
{
    Http::fake([
        'api.openai.com/*' => Http::response(['error' => 'fail'], 500),
    ]);
}

function fakeAiEmptyResponse(): void
{
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => null]],
            ],
        ]),
    ]);
}

// ─── POST /ai/transform-text ───

describe('POST /admin/ai/transform-text', function () {
    it('transforms text successfully', function () {
        actingAsAdmin();
        fakeAiResponse('# Formatted text');

        $response = $this->postJson('/api/admin/ai/transform-text', [
            'text' => 'Some text to format',
            'action' => 'format_markdown',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.text', '# Formatted text');
    });

    it('works with all action types', function (string $action) {
        actingAsAdmin();
        fakeAiResponse('Result');

        $response = $this->postJson('/api/admin/ai/transform-text', [
            'text' => 'Some text',
            'action' => $action,
        ]);

        $response->assertOk();
    })->with(['format_markdown', 'shorten', 'explain', 'formal', 'casual']);

    it('returns 503 when AI is not configured', function () {
        actingAsAdmin();
        config(['ai.api_key' => null]);

        $response = $this->postJson('/api/admin/ai/transform-text', [
            'text' => 'Some text',
            'action' => 'shorten',
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('error', 'ai_not_configured');
    });

    it('returns 502 when AI request fails', function () {
        actingAsAdmin();
        fakeAiFailure();

        $response = $this->postJson('/api/admin/ai/transform-text', [
            'text' => 'Some text',
            'action' => 'shorten',
        ]);

        $response->assertStatus(502)
            ->assertJsonPath('error', 'ai_request_failed');
    });

    it('returns 502 when AI returns empty response', function () {
        actingAsAdmin();
        fakeAiEmptyResponse();

        $response = $this->postJson('/api/admin/ai/transform-text', [
            'text' => 'Some text',
            'action' => 'shorten',
        ]);

        $response->assertStatus(502)
            ->assertJsonPath('error', 'ai_empty_response');
    });

    it('validates required fields', function () {
        actingAsAdmin();

        $this->postJson('/api/admin/ai/transform-text', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text', 'action']);
    });

    it('validates action must be valid', function () {
        actingAsAdmin();

        $this->postJson('/api/admin/ai/transform-text', [
            'text' => 'text',
            'action' => 'invalid_action',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    });

    it('validates text max length', function () {
        actingAsAdmin();

        $this->postJson('/api/admin/ai/transform-text', [
            'text' => str_repeat('a', 10001),
            'action' => 'shorten',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);
    });

    it('rejects unauthenticated users', function () {
        $this->postJson('/api/admin/ai/transform-text', [
            'text' => 'text',
            'action' => 'shorten',
        ])->assertUnauthorized();
    });

    it('rejects non-admin users', function () {
        actingAsUser();

        $this->postJson('/api/admin/ai/transform-text', [
            'text' => 'text',
            'action' => 'shorten',
        ])->assertForbidden();
    });

    it('allows teacher users', function () {
        actingAsTeacher();
        fakeAiResponse('Result');

        $this->postJson('/api/admin/ai/transform-text', [
            'text' => 'text',
            'action' => 'shorten',
        ])->assertOk();
    });

    it('trims whitespace from AI response', function () {
        actingAsAdmin();
        fakeAiResponse("  trimmed result  \n");

        $response = $this->postJson('/api/admin/ai/transform-text', [
            'text' => 'text',
            'action' => 'shorten',
        ]);

        $response->assertJsonPath('data.text', 'trimmed result');
    });
});

// ─── POST /ai/suggest-merge-name ───

describe('POST /admin/ai/suggest-merge-name', function () {
    it('suggests a merge name successfully', function () {
        actingAsAdmin();
        fakeAiResponse('Machine Learning');

        $response = $this->postJson('/api/admin/ai/suggest-merge-name', [
            'names' => ['AI', 'Deep Learning'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.text', 'Machine Learning');
    });

    it('returns 503 when AI is not configured', function () {
        actingAsAdmin();
        config(['ai.api_key' => null]);

        $response = $this->postJson('/api/admin/ai/suggest-merge-name', [
            'names' => ['AI', 'ML'],
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('error', 'ai_not_configured');
    });

    it('returns 502 when AI request fails', function () {
        actingAsAdmin();
        fakeAiFailure();

        $response = $this->postJson('/api/admin/ai/suggest-merge-name', [
            'names' => ['AI', 'ML'],
        ]);

        $response->assertStatus(502)
            ->assertJsonPath('error', 'ai_request_failed');
    });

    it('returns 502 when AI returns empty response', function () {
        actingAsAdmin();
        fakeAiEmptyResponse();

        $response = $this->postJson('/api/admin/ai/suggest-merge-name', [
            'names' => ['AI', 'ML'],
        ]);

        $response->assertStatus(502)
            ->assertJsonPath('error', 'ai_empty_response');
    });

    it('validates names is required and min 2', function () {
        actingAsAdmin();

        $this->postJson('/api/admin/ai/suggest-merge-name', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['names']);

        $this->postJson('/api/admin/ai/suggest-merge-name', [
            'names' => ['only one'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['names']);
    });

    it('validates names items are strings with max length', function () {
        actingAsAdmin();

        $this->postJson('/api/admin/ai/suggest-merge-name', [
            'names' => [123, str_repeat('a', 256)],
        ])->assertUnprocessable();
    });
});

// ─── GET /ai/rating-sentiments ───

describe('GET /admin/ai/rating-sentiments', function () {
    it('returns analyzed ratings', function () {
        actingAsAdmin();

        $rating = Rating::factory()->withComment()->create([
            'sentiment' => 'Sentimento positivo.',
            'sentiment_analyzed_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/ai/rating-sentiments');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $rating->id)
            ->assertJsonPath('data.0.sentiment', 'Sentimento positivo.');
    });

    it('excludes ratings without sentiment analysis', function () {
        actingAsAdmin();

        Rating::factory()->withComment()->create([
            'sentiment' => null,
            'sentiment_analyzed_at' => null,
        ]);

        $response = $this->getJson('/api/admin/ai/rating-sentiments');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('returns max 20 ratings ordered by latest analyzed', function () {
        actingAsAdmin();

        Rating::factory()->withComment()->count(25)->create([
            'sentiment' => 'Analysis',
            'sentiment_analyzed_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/ai/rating-sentiments');

        $response->assertOk()
            ->assertJsonCount(20, 'data');
    });

    it('includes user and seminar relations', function () {
        actingAsAdmin();

        Rating::factory()->withComment()->create([
            'sentiment' => 'Positivo',
            'sentiment_analyzed_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/ai/rating-sentiments');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'sentiment', 'sentiment_analyzed_at', 'user', 'seminar'],
                ],
            ]);
    });
});
