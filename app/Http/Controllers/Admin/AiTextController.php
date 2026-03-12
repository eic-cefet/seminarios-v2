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
use App\Support\RatingSentimentLabel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function __construct(
        private readonly ?AiService $ai
    ) {}

    public function transform(AiTransformTextRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! $this->ai) {
            return $this->aiNotConfigured();
        }

        try {
            $text = $this->ai->chat(self::SYSTEM_PROMPTS[$validated['action']], $validated['text']);
        } catch (RuntimeException) {
            return $this->aiRequestFailed();
        }

        AuditLog::record(AuditEvent::AiTextTransform, eventData: [
            'action' => $validated['action'],
        ]);

        return response()->json(['data' => ['text' => $text]]);
    }

    public function suggestMergeName(AiSuggestMergeNameRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! $this->ai) {
            return $this->aiNotConfigured();
        }

        $systemPrompt = 'You are a naming assistant for academic seminar topics. Given a list of topic names that are being merged, suggest a single concise topic name that best represents all of them. Return ONLY the suggested name, nothing else.';

        try {
            $text = $this->ai->chat($systemPrompt, implode(', ', $validated['names']), 256);
        } catch (RuntimeException) {
            return $this->aiRequestFailed();
        }

        AuditLog::record(AuditEvent::AiSuggestMergeName, eventData: [
            'names' => $validated['names'],
        ]);

        return response()->json(['data' => ['text' => $text]]);
    }

    public function ratingSentiments(Request $request): JsonResponse
    {
        $ratings = Rating::query()
            ->whereNotNull('sentiment_analyzed_at')
            ->with(['user:id,name', 'seminar:id,name,slug']);

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));

            $ratings->where(function ($query) use ($search) {
                $query
                    ->where('comment', 'like', "%{$search}%")
                    ->orWhere('sentiment', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('seminar', function ($seminarQuery) use ($search) {
                        $seminarQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('score')) {
            $ratings->where('score', (int) $request->query('score'));
        }

        if ($request->filled('sentiment_label')) {
            RatingSentimentLabel::applyFilter(
                $ratings,
                (string) $request->query('sentiment_label'),
            );
        }

        $ratings->latest('sentiment_analyzed_at');

        $summaryQuery = clone $ratings;
        $averageScore = $summaryQuery->avg('score');
        $totalRatings = (clone $ratings)->count();
        $lowScoreCount = (clone $ratings)
            ->where('score', '<=', 3)
            ->count();

        $paginator = $ratings->paginate($this->getPerPage($request, 20));

        return response()->json([
            'data' => AdminRatingResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'summary' => [
                'total_ratings' => $totalRatings,
                'average_score' => $averageScore !== null ? round((float) $averageScore, 1) : null,
                'low_score_count' => $lowScoreCount,
            ],
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    private function aiNotConfigured(): JsonResponse
    {
        return response()->json([
            'error' => 'ai_not_configured',
            'message' => 'AI service is not configured. Set AI_API_KEY in your environment.',
        ], 503);
    }

    private function aiRequestFailed(): JsonResponse
    {
        return response()->json([
            'error' => 'ai_request_failed',
            'message' => 'AI service request failed. Please try again.',
        ], 502);
    }
}
