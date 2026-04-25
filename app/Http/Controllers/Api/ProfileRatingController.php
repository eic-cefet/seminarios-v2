<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitRatingRequest;
use App\Http\Resources\PendingEvaluationResource;
use App\Jobs\AnalyzeRatingSentiment;
use App\Models\Rating;
use App\Services\FeatureFlags;
use App\Services\RatingService;
use App\Support\Locking\LockKey;
use App\Support\Locking\Mutex;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ProfileRatingController extends Controller
{
    public function pendingEvaluations(Request $request, RatingService $ratings): JsonResponse
    {
        return response()->json([
            'data' => PendingEvaluationResource::collection(
                $ratings->pendingEvaluationsFor($request->user())
            ),
        ]);
    }

    public function submitRating(SubmitRatingRequest $request, RatingService $ratings, int $seminarId): JsonResponse
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

        if (! $ratings->isWithinWindow($seminar)) {
            throw ApiException::forbidden('O prazo para avaliar este seminário expirou.');
        }

        $aiAnalysisConsent = (bool) ($validated['ai_analysis_consent'] ?? false);

        $rating = Mutex::for(LockKey::ratingCreation($seminarId, $user->id))
            ->protect(function () use ($seminarId, $user, $validated, $aiAnalysisConsent) {
                if (Rating::query()
                    ->where('seminar_id', $seminarId)
                    ->where('user_id', $user->id)
                    ->exists()
                ) {
                    throw ApiException::conflict('Você já avaliou este seminário.');
                }

                try {
                    return Rating::create([
                        'seminar_id' => $seminarId,
                        'user_id' => $user->id,
                        'score' => $validated['score'],
                        'comment' => $validated['comment'] ?? null,
                        'ai_analysis_consent' => $aiAnalysisConsent,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    // Race fallback: another request created the rating between
                    // our pre-check and insert despite the Mutex.
                    throw ApiException::conflict('Você já avaliou este seminário.');
                }
            });

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
