<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AiSuggestMergeNameRequest;
use App\Http\Requests\Admin\AiTransformTextRequest;
use App\Http\Resources\Admin\AdminRatingResource;
use App\Models\AuditLog;
use App\Models\Rating;
use App\Services\AiService;
use RuntimeException;

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

        $ai = AiService::fromConfig();
        if (! $ai) {
            return $this->aiNotConfigured();
        }

        try {
            $text = $ai->chat(self::SYSTEM_PROMPTS[$validated['action']], $validated['text']);
        } catch (RuntimeException) {
            return $this->aiRequestFailed();
        }

        AuditLog::record(AuditEvent::AiTextTransform, eventData: [
            'action' => $validated['action'],
        ]);

        return response()->json(['data' => ['text' => $text]]);
    }

    public function suggestMergeName(AiSuggestMergeNameRequest $request)
    {
        $validated = $request->validated();

        $ai = AiService::fromConfig();
        if (! $ai) {
            return $this->aiNotConfigured();
        }

        $systemPrompt = 'You are a naming assistant for academic seminar topics. Given a list of topic names that are being merged, suggest a single concise topic name that best represents all of them. Return ONLY the suggested name, nothing else.';

        try {
            $text = $ai->chat($systemPrompt, implode(', ', $validated['names']), 256);
        } catch (RuntimeException) {
            return $this->aiRequestFailed();
        }

        AuditLog::record(AuditEvent::AiSuggestMergeName, eventData: [
            'names' => $validated['names'],
        ]);

        return response()->json(['data' => ['text' => $text]]);
    }

    public function ratingSentiments()
    {
        $ratings = Rating::query()
            ->whereNotNull('sentiment_analyzed_at')
            ->with(['user', 'seminar'])
            ->latest('sentiment_analyzed_at')
            ->limit(20)
            ->get();

        return AdminRatingResource::collection($ratings);
    }

    private function aiNotConfigured()
    {
        return response()->json([
            'error' => 'ai_not_configured',
            'message' => 'AI service is not configured. Set AI_API_KEY in your environment.',
        ], 503);
    }

    private function aiRequestFailed()
    {
        return response()->json([
            'error' => 'ai_request_failed',
            'message' => 'AI service request failed. Please try again.',
        ], 502);
    }
}
