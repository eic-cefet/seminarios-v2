<?php

namespace App\Jobs;

use App\Models\Rating;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyzeRatingSentiment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Rating $rating
    ) {}

    public function handle(): void
    {
        if (empty($this->rating->comment)) {
            return;
        }

        if ($this->rating->sentiment_analyzed_at !== null) {
            return;
        }

        $apiKey = config('ai.api_key');
        $baseUrl = config('ai.base_url');
        $model = config('ai.model');

        if (! $apiKey) {
            Log::error('AI service is not configured. Set AI_API_KEY in your environment.');

            return;
        }

        $systemPrompt = 'You are a sentiment analyzer for academic seminar reviews. Analyze the following review and provide a brief sentiment summary in Portuguese (Brazilian). Include: overall sentiment (positivo/negativo/neutro/misto), key points mentioned. Keep it under 100 words. Return ONLY the analysis.';

        $userMessage = "Nota: {$this->rating->score}/5\nComentário: {$this->rating->comment}";

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'max_completion_tokens' => 512,
            ]);

        if ($response->failed()) {
            Log::error('AI sentiment analysis failed. Response: '.$response->body());

            return;
        }

        $result = $response->json('choices.0.message.content');

        if (! $result) {
            Log::error('AI sentiment analysis returned an empty response.');

            return;
        }

        $this->rating->update([
            'sentiment' => trim($result),
            'sentiment_analyzed_at' => now(),
        ]);
    }
}
