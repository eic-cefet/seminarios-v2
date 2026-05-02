<?php

use App\Models\Rating;
use App\Models\Seminar;
use App\Models\Subject;
use App\Models\User;
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
            ->assertJsonPath('error', 'ai_request_failed');
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
            ->assertJsonPath('error', 'ai_request_failed');
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

// ─── POST /ai/suggest-subject-tags ───

describe('POST /admin/ai/suggest-subject-tags', function () {
    it('suggests related subjects based on selected topics', function () {
        actingAsAdmin();
        fakeAiResponse('["Docker", "Kubernetes"]');

        $response = $this->postJson('/api/admin/ai/suggest-subject-tags', [
            'subject_names' => ['Cloud Computing'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.suggestions.0', 'Docker')
            ->assertJsonPath('data.suggestions.1', 'Kubernetes');
    });

    it('uses historical co-occurrence data when available', function () {
        actingAsAdmin();

        $subject1 = Subject::factory()->create(['name' => 'React']);
        $subject2 = Subject::factory()->create(['name' => 'TypeScript']);
        $subject3 = Subject::factory()->create(['name' => 'Next.js']);

        $seminar = Seminar::factory()->create();
        $seminar->subjects()->attach([$subject1->id, $subject2->id, $subject3->id]);

        fakeAiResponse('["Vue.js", "Tailwind CSS"]');

        $response = $this->postJson('/api/admin/ai/suggest-subject-tags', [
            'subject_names' => ['React'],
        ]);

        $response->assertOk()
            ->assertJsonCount(2, 'data.suggestions');

        Http::assertSent(function ($request) {
            return str_contains($request->body(), 'TypeScript') &&
                str_contains($request->body(), 'Next.js');
        });
    });

    it('filters out already-selected subjects from suggestions', function () {
        actingAsAdmin();
        fakeAiResponse('["React", "Vue.js", "Angular"]');

        $response = $this->postJson('/api/admin/ai/suggest-subject-tags', [
            'subject_names' => ['React'],
        ]);

        $response->assertOk();

        $suggestions = $response->json('data.suggestions');
        expect($suggestions)->not->toContain('React');
        expect($suggestions)->toContain('Vue.js');
        expect($suggestions)->toContain('Angular');
    });

    it('returns empty array when AI returns invalid JSON', function () {
        actingAsAdmin();
        fakeAiResponse('These are my suggestions: Docker, Kubernetes');

        $response = $this->postJson('/api/admin/ai/suggest-subject-tags', [
            'subject_names' => ['Cloud Computing'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.suggestions', []);
    });

    it('returns 503 when AI is not configured', function () {
        actingAsAdmin();
        config(['ai.api_key' => null]);

        $response = $this->postJson('/api/admin/ai/suggest-subject-tags', [
            'subject_names' => ['React'],
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('error', 'ai_not_configured');
    });

    it('returns 502 when AI request fails', function () {
        actingAsAdmin();
        fakeAiFailure();

        $response = $this->postJson('/api/admin/ai/suggest-subject-tags', [
            'subject_names' => ['React'],
        ]);

        $response->assertStatus(502)
            ->assertJsonPath('error', 'ai_request_failed');
    });

    it('validates subject_names is required and min 1', function () {
        actingAsAdmin();

        $this->postJson('/api/admin/ai/suggest-subject-tags', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['subject_names']);

        $this->postJson('/api/admin/ai/suggest-subject-tags', [
            'subject_names' => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['subject_names']);
    });

    it('validates subject_names items are strings with max length', function () {
        actingAsAdmin();

        $this->postJson('/api/admin/ai/suggest-subject-tags', [
            'subject_names' => [123, str_repeat('a', 256)],
        ])->assertUnprocessable();
    });

    it('rejects unauthenticated users', function () {
        $this->postJson('/api/admin/ai/suggest-subject-tags', [
            'subject_names' => ['React'],
        ])->assertUnauthorized();
    });

    it('rejects non-admin users', function () {
        actingAsUser();

        $this->postJson('/api/admin/ai/suggest-subject-tags', [
            'subject_names' => ['React'],
        ])->assertForbidden();
    });

    it('allows teacher users', function () {
        actingAsTeacher();
        fakeAiResponse('["Vue.js"]');

        $this->postJson('/api/admin/ai/suggest-subject-tags', [
            'subject_names' => ['React'],
        ])->assertOk();
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
            ->assertJsonPath('data.0.sentiment', 'Sentimento positivo.')
            ->assertJsonPath('data.0.sentiment_label', 'positive')
            ->assertJsonPath('summary.total_ratings', 1)
            ->assertJsonPath('summary.average_score', (float) $rating->score);
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
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.per_page', 20);
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
                    ['id', 'sentiment', 'sentiment_label', 'sentiment_analyzed_at', 'user', 'seminar'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to'],
                'summary' => ['total_ratings', 'average_score', 'low_score_count'],
            ]);
    });

    it('filters analyzed ratings by score', function () {
        actingAsAdmin();

        Rating::factory()->withComment()->create([
            'score' => 5,
            'sentiment' => 'Sentimento positivo.',
            'sentiment_analyzed_at' => now(),
        ]);
        Rating::factory()->withComment()->create([
            'score' => 2,
            'sentiment' => 'Sentimento negativo.',
            'sentiment_analyzed_at' => now()->subMinute(),
        ]);

        $response = $this->getJson('/api/admin/ai/rating-sentiments?score=5');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.score', 5)
            ->assertJsonPath('summary.total_ratings', 1)
            ->assertJsonPath('summary.low_score_count', 0);
    });

    it('filters analyzed ratings by derived sentiment label', function () {
        actingAsAdmin();

        Rating::factory()->withComment()->create([
            'sentiment' => 'Sentimento positivo. Aluno gostou do conteúdo.',
            'sentiment_analyzed_at' => now(),
        ]);
        Rating::factory()->withComment()->create([
            'sentiment' => 'Sentimento misto. Houve elogios e críticas.',
            'sentiment_analyzed_at' => now()->subMinute(),
        ]);

        $response = $this->getJson('/api/admin/ai/rating-sentiments?sentiment_label=positive');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sentiment_label', 'positive');
    });

    it('filters analyzed ratings by search term across relations and text', function () {
        actingAsAdmin();

        $seminar = Seminar::factory()->create(['name' => 'Seminário de IA']);
        $user = User::factory()->create(['name' => 'Maria Avaliadora']);

        Rating::factory()->withComment()->create([
            'seminar_id' => $seminar->id,
            'user_id' => $user->id,
            'comment' => 'Gostei bastante do encontro.',
            'sentiment' => 'Sentimento positivo.',
            'sentiment_analyzed_at' => now(),
        ]);

        Rating::factory()->withComment()->create([
            'sentiment' => 'Sentimento negativo.',
            'sentiment_analyzed_at' => now()->subMinute(),
        ]);

        $response = $this->getJson('/api/admin/ai/rating-sentiments?search=Maria');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.name', 'Maria Avaliadora');
    });

    it('returns summary metrics for the filtered result set', function () {
        actingAsAdmin();

        Rating::factory()->withComment()->create([
            'score' => 5,
            'sentiment' => 'Sentimento positivo.',
            'sentiment_analyzed_at' => now(),
        ]);
        Rating::factory()->withComment()->create([
            'score' => 3,
            'sentiment' => 'Sentimento neutro.',
            'sentiment_analyzed_at' => now()->subMinute(),
        ]);

        $response = $this->getJson('/api/admin/ai/rating-sentiments');

        $response->assertOk()
            ->assertJsonPath('summary.total_ratings', 2)
            ->assertJsonPath('summary.average_score', 4.0)
            ->assertJsonPath('summary.low_score_count', 1);
    });
});
