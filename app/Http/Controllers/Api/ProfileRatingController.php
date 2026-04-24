<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitRatingRequest;
use App\Http\Resources\PendingEvaluationResource;
use App\Jobs\AnalyzeRatingSentiment;
use App\Models\Rating;
use App\Services\FeatureFlags;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ProfileRatingController extends Controller
{
    public function pendingEvaluations(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get registrations where:
        // - User was present
        // - Seminar ended within the last 30 days
        // - User hasn't rated the seminar yet
        $thirtyDaysAgo = now()->subDays(30)->startOfDay();

        $registrations = $user->registrations()
            ->where('present', true)
            ->whereHas('seminar', function ($query) use ($thirtyDaysAgo) {
                $query->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '>=', $thirtyDaysAgo)
                    ->where('scheduled_at', '<=', now());
            })
            ->whereDoesntHave('seminar.ratings', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['seminar.seminarType', 'seminar.seminarLocation'])
            ->get();

        return response()->json([
            'data' => PendingEvaluationResource::collection($registrations),
        ]);
    }

    public function submitRating(SubmitRatingRequest $request, int $seminarId): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validated();

        $registration = $user->registrations()
            ->where('seminar_id', $seminarId)
            ->where('present', true)
            ->first();

        if (! $registration) {
            throw ApiException::forbidden('Você não participou deste seminário.');
        }

        $seminar = $registration->seminar;
        $thirtyDaysAgo = now()->subDays(30)->startOfDay();

        if (! $seminar->scheduled_at || $seminar->scheduled_at < $thirtyDaysAgo) {
            throw ApiException::forbidden('O prazo para avaliar este seminário expirou.');
        }

        $alreadyRated = Rating::query()
            ->where('seminar_id', $seminarId)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyRated) {
            throw ApiException::conflict('Você já avaliou este seminário.');
        }

        $aiAnalysisConsent = (bool) ($validated['ai_analysis_consent'] ?? false);

        $rating = Rating::create([
            'seminar_id' => $seminarId,
            'user_id' => $user->id,
            'score' => $validated['score'],
            'comment' => $validated['comment'] ?? null,
            'ai_analysis_consent' => $aiAnalysisConsent,
        ]);

        $lgpdOptInEnabled = config('lgpd.features.ai_sentiment_opt_in', true);
        $consentGranted = ! $lgpdOptInEnabled || $rating->ai_analysis_consent;

        if ($rating->comment && $consentGranted && FeatureFlags::shouldRun('sentiment_analysis')) {
            try {
                AnalyzeRatingSentiment::dispatch($rating);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return response()->json([
            'message' => 'Avaliação enviada com sucesso!',
            'rating' => [
                'id' => $rating->id,
                'score' => $rating->score,
                'comment' => $rating->comment,
            ],
        ]);
    }
}
