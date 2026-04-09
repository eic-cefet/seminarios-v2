<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $model,
    ) {}

    public static function fromConfig(): ?self
    {
        $apiKey = config('ai.api_key');

        if (! $apiKey) {
            return null;
        }

        return new self(
            apiKey: $apiKey,
            baseUrl: config('ai.base_url'),
            model: config('ai.model'),
        );
    }

    /**
     * @throws RuntimeException
     */
    public function chat(string $systemPrompt, string $userMessage, int $maxTokens = 16384): string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'max_completion_tokens' => $maxTokens,
            ]);

        if ($response->failed()) {
            Log::error('AI service request failed. Response: '.$response->body());

            throw new RuntimeException('AI service request failed.');
        }

        $result = $response->json('choices.0.message.content');

        if (! $result) {
            Log::error('AI service returned an empty response.');

            throw new RuntimeException('AI service returned an empty response.');
        }

        return trim($result);
    }
}
