<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\PendingEvaluationResource;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileRatingController extends Controller
{
    /**
     * Get the authenticated user's pending evaluations (seminars attended but not rated)
     */
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

    /**
     * Submit a rating for a seminar
     */
    public function submitRating(Request $request, int $seminarId): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // Check if user attended this seminar
        $registration = $user->registrations()
            ->where('seminar_id', $seminarId)
            ->where('present', true)
            ->first();

        if (! $registration) {
            throw ApiException::forbidden('Você não participou deste seminário.');
        }

        // Check if seminar is within 30 days
        $seminar = $registration->seminar;
        $thirtyDaysAgo = now()->subDays(30)->startOfDay();

        if (! $seminar->scheduled_at || $seminar->scheduled_at < $thirtyDaysAgo) {
            throw ApiException::forbidden('O prazo para avaliar este seminário expirou.');
        }

        // Check if already rated
        $existingRating = Rating::query()
            ->where('seminar_id', $seminarId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingRating) {
            throw ApiException::conflict('Você já avaliou este seminário.');
        }

        // Create rating
        $rating = Rating::create([
            'seminar_id' => $seminarId,
            'user_id' => $user->id,
            'score' => $validated['score'],
            'comment' => $validated['comment'] ?? null,
        ]);

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
