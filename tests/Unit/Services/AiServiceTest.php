<?php

use App\Services\AiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    config()->set('ai.api_key', 'test-key');
    config()->set('ai.base_url', 'https://api.test.example/v1');
    config()->set('ai.model', 'gpt-test');
});

it('builds an instance from config when an API key is set', function (): void {
    expect(AiService::fromConfig())->toBeInstanceOf(AiService::class);
});

it('returns null from fromConfig when no API key is configured', function (): void {
    config()->set('ai.api_key', null);

    expect(AiService::fromConfig())->toBeNull();
});

it('returns trimmed chat completion content on success and posts the right payload', function (): void {
    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => '  hello world  ']],
            ],
        ], 200),
    ]);

    $result = AiService::fromConfig()->chat('be terse', 'hi');

    expect($result)->toBe('hello world');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.test.example/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['model'] === 'gpt-test'
            && $request['messages'][0] === ['role' => 'system', 'content' => 'be terse']
            && $request['messages'][1] === ['role' => 'user', 'content' => 'hi']
            && $request['max_completion_tokens'] === 16384;
    });
});

it('honors a custom max_completion_tokens value', function (): void {
    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'ok']]],
        ], 200),
    ]);

    AiService::fromConfig()->chat('s', 'u', 256);

    Http::assertSent(fn ($request) => $request['max_completion_tokens'] === 256);
});

it('throws and logs when the upstream request fails', function (): void {
    Log::spy();
    Http::fake([
        '*/chat/completions' => Http::response(['error' => 'down'], 502),
    ]);

    expect(fn () => AiService::fromConfig()->chat('s', 'u'))
        ->toThrow(RuntimeException::class, 'AI service request failed.');

    Log::shouldHaveReceived('error')->once()->withArgs(
        fn (string $message) => str_contains($message, 'AI service request failed'),
    );
});

it('throws and logs when the upstream returns empty content', function (): void {
    Log::spy();
    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => '']]],
        ], 200),
    ]);

    expect(fn () => AiService::fromConfig()->chat('s', 'u'))
        ->toThrow(RuntimeException::class, 'AI service returned an empty response.');

    Log::shouldHaveReceived('error')->once()->withArgs(
        fn (string $message) => str_contains($message, 'empty response'),
    );
});
