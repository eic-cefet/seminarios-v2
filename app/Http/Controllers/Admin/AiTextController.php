<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AiSuggestMergeNameRequest;
use App\Http\Requests\Admin\AiSuggestSeminarNameRequest;
use App\Http\Requests\Admin\AiTransformTextRequest;
use App\Models\Rating;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiTextController extends Controller
{
    private const SYSTEM_PROMPTS = [
        'format_markdown' => 'You are a text formatter. Convert the given text into well-structured Markdown. Use headings, lists, bold, and other Markdown features where appropriate. Keep the original meaning and content intact. Return ONLY the formatted text, no explanations.',
        'shorten' => 'You are a text editor. Make the given text more concise while preserving all key information and meaning. Remove redundancy and unnecessary words. Return ONLY the shortened text, no explanations.',
        'explain' => 'You are a text editor. Rewrite the given text to be clearer and easier to understand. Simplify complex sentences and clarify ambiguous parts. Return ONLY the rewritten text, no explanations.',
        'formal' => 'You are a text editor. Rewrite the given text in a formal, professional tone suitable for academic or business contexts. Return ONLY the rewritten text, no explanations.',
        'casual' => 'You are a text editor. Rewrite the given text in a casual, friendly, and approachable tone. Return ONLY the rewritten text, no explanations.',
    ];

    public function transform(AiTransformTextRequest $request)
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $text = $validated['text'];

        $apiKey = config('ai.api_key');
        $baseUrl = config('ai.base_url');
        $model = config('ai.model');

        if (! $apiKey) {
            Log::error('AI service is not configured. Set AI_API_KEY in your environment.');

            return response()->json([
                'error' => 'ai_not_configured',
                'message' => 'AI service is not configured. Set AI_API_KEY in your environment.',
            ], 503);
        }

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPTS[$action]],
                    ['role' => 'user', 'content' => $text],
                ],
                'max_completion_tokens' => 16384,
            ]);

        if ($response->failed()) {
            Log::error('AI service request failed. Response: '.$response->body());

            return response()->json([
                'error' => 'ai_request_failed',
                'message' => 'AI service request failed. Please try again.',
            ], 502);
        }

        $result = $response->json('choices.0.message.content');

        if (! $result) {
            Log::error('AI service returned an empty response.');

            return response()->json([
                'error' => 'ai_empty_response',
                'message' => 'AI service returned an empty response.',
            ], 502);
        }

        return response()->json([
            'data' => [
                'text' => trim($result),
            ],
        ]);
    }

    public function suggestMergeName(AiSuggestMergeNameRequest $request)
    {
        $validated = $request->validated();
        $names = $validated['names'];

        $apiKey = config('ai.api_key');
        $baseUrl = config('ai.base_url');
        $model = config('ai.model');

        if (! $apiKey) {
            Log::error('AI service is not configured. Set AI_API_KEY in your environment.');

            return response()->json([
                'error' => 'ai_not_configured',
                'message' => 'AI service is not configured. Set AI_API_KEY in your environment.',
            ], 503);
        }

        $systemPrompt = 'You are a naming assistant for academic seminar topics. Given a list of topic names that are being merged, suggest a single concise topic name that best represents all of them. Return ONLY the suggested name, nothing else.';

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => implode(', ', $names)],
                ],
                'max_completion_tokens' => 256,
            ]);

        if ($response->failed()) {
            Log::error('AI service request failed. Response: '.$response->body());

            return response()->json([
                'error' => 'ai_request_failed',
                'message' => 'AI service request failed. Please try again.',
            ], 502);
        }

        $result = $response->json('choices.0.message.content');

        if (! $result) {
            Log::error('AI service returned an empty response.');

            return response()->json([
                'error' => 'ai_empty_response',
                'message' => 'AI service returned an empty response.',
            ], 502);
        }

        return response()->json([
            'data' => [
                'text' => trim($result),
            ],
        ]);
    }

    public function suggestSeminarName(AiSuggestSeminarNameRequest $request)
    {
        $validated = $request->validated();
        $subjects = $validated['subjects'];
        $speakers = $validated['speakers'] ?? [];

        $apiKey = config('ai.api_key');
        $baseUrl = config('ai.base_url');
        $model = config('ai.model');

        if (! $apiKey) {
            Log::error('AI service is not configured. Set AI_API_KEY in your environment.');

            return response()->json([
                'error' => 'ai_not_configured',
                'message' => 'AI service is not configured. Set AI_API_KEY in your environment.',
            ], 503);
        }

        $systemPrompt = 'You are a naming assistant for academic seminars. Given the topics and optionally the speaker names, suggest a compelling and descriptive seminar title in Portuguese (Brazilian). Return ONLY the suggested title, nothing else.';

        $userMessage = 'Tópicos: '.implode(', ', $subjects);
        if (! empty($speakers)) {
            $userMessage .= "\nPalestrantes: ".implode(', ', $speakers);
        }

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'max_completion_tokens' => 256,
            ]);

        if ($response->failed()) {
            Log::error('AI service request failed. Response: '.$response->body());

            return response()->json([
                'error' => 'ai_request_failed',
                'message' => 'AI service request failed. Please try again.',
            ], 502);
        }

        $result = $response->json('choices.0.message.content');

        if (! $result) {
            Log::error('AI service returned an empty response.');

            return response()->json([
                'error' => 'ai_empty_response',
                'message' => 'AI service returned an empty response.',
            ], 502);
        }

        return response()->json([
            'data' => [
                'text' => trim($result),
            ],
        ]);
    }

    public function ratingSentiments()
    {
        $ratings = Rating::query()
            ->whereNotNull('sentiment_analyzed_at')
            ->with(['user', 'seminar'])
            ->latest('sentiment_analyzed_at')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $ratings,
        ]);
    }
}
